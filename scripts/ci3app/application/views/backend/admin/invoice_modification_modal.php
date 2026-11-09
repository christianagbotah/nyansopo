<?php
$invoice_code = $this->uri->segment(3);
$user_id = $this->session->userdata('login_user_id');
$admin_level = $this->db->get_where('admin', array('admin_id' => $user_id))->row()->level;

// Get invoice items using same query as View Invoices tab
$invoice_items = $this->db->where('invoice_code', $invoice_code)->get('invoice')->result_array();

if(empty($invoice_items)) {
    echo '<div style="padding: 40px; text-align: center;">';
    echo '<i class="fa fa-exclamation-triangle" style="font-size: 48px; color: #ef4444; margin-bottom: 16px;"></i>';
    echo '<h3>Invoice not found</h3>';
    echo '<p>The invoice record could not be found.</p>';
    echo '</div>';
    return;
}

$first_item = $invoice_items[0];
$student_id = $first_item['student_id'];
$student = $this->db->where('student_id', $student_id)->get('student')->row();
$currency_row = $this->db->get_where('settings', array('type' => 'currency'))->row();
$currency = $currency_row ? $currency_row->description : 'GHS';
$theme_color_row = $this->db->get_where('settings', array('type' => 'theme_color'))->row();
$theme_color = $theme_color_row ? $theme_color_row->description : '667eea';

if(strpos($theme_color, '#') !== 0) {
    $theme_color = '#' . $theme_color;
}

function adjustBrightness($hex, $steps) {
    $hex = str_replace('#', '', $hex);
    if(strlen($hex) == 3) {
        $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
    }
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));
    $r = max(0, min(255, $r + $steps));
    $g = max(0, min(255, $g + $steps));
    $b = max(0, min(255, $b + $steps));
    return '#' . str_pad(dechex($r), 2, '0', STR_PAD_LEFT) . str_pad(dechex($g), 2, '0', STR_PAD_LEFT) . str_pad(dechex($b), 2, '0', STR_PAD_LEFT);
}
$gradient_light = adjustBrightness($theme_color, 20);
$gradient_dark = adjustBrightness($theme_color, -30);

// Get class info
$enroll = $this->db->where('student_id', $student_id)->where('year', $first_item['year'])->get('enroll')->row();
$class_name = $enroll ? getFullClassName($enroll->class_id) : 'N/A';

// Check for discount using same query as View Invoices tab
$discount = $this->db->where('invoice_code', $invoice_code)->where('status', 'approved')->get('invoice_discounts')->row();
$discount_profile = null;
$applicable_bill_items = 'All Bill Items';
if($discount) {
    $discount_profile = $this->db->where('profile_id', $discount->profile_id)->get('discount_profiles')->row();
    if($discount_profile && $discount_profile->bill_item_ids !== '*') {
        $bill_item_ids = explode(',', $discount_profile->bill_item_ids);
        $bill_items = $this->db->where_in('id', $bill_item_ids)->get('bill_item')->result_array();
        $applicable_bill_items = implode(', ', array_column($bill_items, 'title'));
    }
}

// Calculate totals
$total_amount = 0;
$total_paid = 0;
foreach($invoice_items as $item) {
    $total_amount += $item['amount'];
    $total_paid += $item['amount_paid'];
}
?>

<style>
.modal-action-btn {
    flex: 1;
    padding: 20px;
    border: 3px solid transparent;
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.3s;
    text-align: center;
}
.modal-action-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 16px rgba(0,0,0,0.15);
}
.modal-action-btn.selected {
    border-color: currentColor;
    box-shadow: 0 0 0 3px rgba(255,255,255,0.5), 0 8px 16px rgba(0,0,0,0.2);
}
.modal-action-btn-edit {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
}
.modal-action-btn-delete {
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    color: white;
}
.invoice-item-row {
    padding: 12px;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    margin-bottom: 12px;
    transition: all 0.3s;
}
.invoice-item-row:hover {
    border-color: <?php echo $theme_color; ?>;
    background: #f9fafb;
}
</style>

<?php echo form_open('admin/request_invoice_modification', array('id' => 'invoiceModificationForm')); ?>
    <input type="hidden" name="invoice_code" value="<?php echo $invoice_code; ?>">
    <input type="hidden" name="student_id" value="<?php echo $student_id; ?>">
    <input type="hidden" name="request_type" id="request_type" value="">
    
    <script>
    (function() {
        var selectedAction = null;
        var currency = '<?php echo $currency; ?>';
        var invoiceCode = '<?php echo $invoice_code; ?>';
        var list_bill_items_array = [];
        var bill_list_item_ids = [];

        function loadInvoiceItems() {
            $('#invoice_items_container').html('<center class="py-5"><i class="fa fa-spinner fa-spin fa-2x"></i><p class="mt-3">Loading invoice items...</p></center>');
            
            $.ajax({
                url: '<?php echo site_url('admin/getStudentBillByInvoiceCode'); ?>',
                type: 'POST',
                dataType: 'json',
                data: { invoice_code: invoiceCode },
                cache: false
            }).done(function(response) {
                list_bill_items_array = response.items;
                $('#invoice_items_container').html(response.list);
                if(typeof ids !== 'undefined') {
                    bill_list_item_ids = ids;
                }
                calculateTotal();
            }).fail(function(err) {
                $('#invoice_items_container').html('<div class="alert alert-danger">Failed to load invoice items</div>');
            });
        }

        window.add_invoice_item_list = function(id, type) {
            var title_value = $('#' + id + '_title').val();
            list_bill_items_array.push(title_value);
            bill_list_item_ids.push(id);
            
            $.ajax({
                url: '<?php echo site_url('admin/add_list_invoice_item/'); ?>' + type,
                data: { 
                    bill_items_array: list_bill_items_array,
                    exclude_items: list_bill_items_array
                },
                success: function(response) {
                    $('#invoice_items_container').append(response);
                    calculateTotal();
                }
            });
        };

        window.getItemDetails = function(row_id, val) {
            if(!val) return;
            var baseId = row_id.replace('_title', '');
            
            $.ajax({
                url: '<?php echo site_url('admin/invoice/get_bill_item_details'); ?>',
                type: 'POST',
                data: { title: val },
                dataType: 'json'
            }).done(function(response) {
                $('#' + baseId + '_category').val(response.category);
                $('#' + baseId + '_description').val(response.description);
                $('#' + baseId + '_amount').val(response.amount);
                calculateTotal();
            }).fail(function(xhr, status, error) {
                console.error('Failed to load bill item details:', error);
            });
        };

        window.remove_invoice_item_list = function(id, type, val) {
            list_bill_items_array = list_bill_items_array.filter(function(item) { return item !== val; });
            $('#' + id).remove();
            bill_list_item_ids = bill_list_item_ids.filter(function(index) { return index != id; });
            calculateTotal();
        };

        function calculateTotal() {
            var total = 0;
            $('.total_amount').each(function() {
                total += parseFloat($(this).val()) || 0;
            });
            $('#new_total').text(currency + ' ' + total.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,'));
        }

        // Event delegation for amount field changes - set up once
        $(document).on('input change', '.total_amount', function() {
            calculateTotal();
        });

        // Initialize event delegation on page load
        $(document).ready(function() {
            $(document).on('input change', '.total_amount', calculateTotal);
        });

        // Event delegation for action buttons
        $(document).off('click', '.modal-action-btn').on('click', '.modal-action-btn', function() {
            var action = $(this).data('action');
            selectedAction = action;
            $('#request_type').val(action);
            
            $('.modal-action-btn').removeClass('selected');
            $(this).addClass('selected');
            
            if(action === 'edit') {
                $('#editFields').slideDown(300);
                $('#deleteWarning, #discountFields').slideUp(300);
                loadInvoiceItems();
            } else if(action === 'delete') {
                $('#deleteWarning').slideDown(300);
                $('#editFields, #discountFields').slideUp(300);
            } else if(action === 'discount') {
                $('#discountFields').slideDown(300);
                $('#editFields, #deleteWarning').slideUp(300);
                loadDiscountProfiles();
            }
        });
        
        function loadDiscountProfiles() {
            var studentId = <?php echo $student_id; ?>;
            $.ajax({
                url: '<?php echo site_url("admin/get_student_class_residence"); ?>',
                type: 'POST',
                data: { student_id: studentId },
                dataType: 'json',
                success: function(studentData) {
                    window.studentClassData = studentData;
                }
            });
        }

        $(document).on('change', '#discount_profile_select', function() {
            var profileId = $(this).val();
            if(profileId && window.studentClassData) {
                $.ajax({
                    url: '<?php echo site_url("admin/getDiscountProfileDetails"); ?>',
                    type: 'POST',
                    data: { 
                        profile_id: profileId, 
                        invoice_code: invoiceCode,
                        class_id: window.studentClassData.class_id,
                        residence_type: window.studentClassData.residence_type
                    },
                    dataType: 'json',
                    success: function(response) {
                        if(response.status === 'success' && response.profile) {
                            var methodText = response.profile.discount_method === 'percentage' ? 'Percentage Discount' : 'Fixed Amount Discount';
                            var valueText = response.profile.discount_method === 'percentage' 
                                ? '<span style="font-size: 32px; font-weight: 800; color: #059669;">' + response.profile.discount_value + '%</span>' 
                                : '<span style="font-size: 32px; font-weight: 800; color: #059669;">' + currency + ' ' + parseFloat(response.profile.discount_value).toFixed(2) + '</span>';
                            
                            var html = '<div style="display: flex; gap: 20px; align-items: start;">';
                            html += '<div style="flex: 0 0 200px; text-align: center; background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%); padding: 20px; border-radius: 10px; border: 2px solid #10b981;">';
                            html += '<div style="font-size: 12px; color: #065f46; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">' + methodText + '</div>';
                            html += valueText + '</div>';
                            html += '<div style="flex: 1;"><h6 style="color: #065f46; font-weight: 700; margin-bottom: 12px; font-size: 16px;"><i class="fa fa-info-circle"></i> Profile Details</h6>';
                            html += '<div style="font-size: 14px; color: #047857; line-height: 1.8;">';
                            html += '<div style="margin-bottom: 8px;"><strong style="color: #064e3b;">Profile Name:</strong> <span style="font-weight: 600;">' + response.profile.profile_name + '</span></div>';
                            
                            if(response.profile.bill_item_ids === '*') {
                                html += '<div style="margin-bottom: 8px;"><strong style="color: #064e3b;">Applies to:</strong> <span style="background: #fef3c7; color: #92400e; padding: 4px 10px; border-radius: 6px; font-weight: 600; font-size: 12px;">All Bill Items</span></div>';
                            } else if(response.profile.items && response.profile.items.length > 0) {
                                html += '<div style="margin-bottom: 8px;"><strong style="color: #064e3b;">Applies to:</strong></div><ul style="margin: 5px 0 0 20px; font-size: 13px;">';
                                response.profile.items.forEach(function(item) {
                                    html += '<li style="margin-bottom: 4px;"><span style="background: #dbeafe; color: #1e40af; padding: 2px 8px; border-radius: 4px; font-weight: 600;">' + item.discount_type + '</span></li>';
                                });
                                html += '</ul>';
                            }
                            html += '</div></div></div>';
                            $('#profile-preview').html(html).slideDown(300);
                        }
                    }
                });
            } else {
                $('#profile-preview').slideUp(300);
            }
        });

        // Form submission
        $('#invoiceModificationForm').off('submit').on('submit', function(e) {
            e.preventDefault();
            
            if(!selectedAction) {
                showAjaxModal_alert('Please select an action (Edit, Delete, or Apply Discount)', 'warning');
                return;
            }
            
            if(selectedAction === 'discount') {
                var profileId = $('#discount_profile_select').val();
                if(!profileId) {
                    showAjaxModal_alert('Please select a discount profile', 'warning');
                    return;
                }
                
                $.ajax({
                    url: '<?php echo site_url("admin/check_existing_profile"); ?>',
                    type: 'POST',
                    data: { student_id: <?php echo $student_id; ?>, invoice_code: invoiceCode },
                    dataType: 'json',
                    success: function(check) {
                        if(check.has_profile) {
                            if(check.profile_id == profileId) {
                                showAjaxModal_alert('This profile is already assigned to this invoice', 'warning');
                            } else {
                                showConfirmModal(
                                    'Replace Existing Profile?',
                                    'Student already has "' + check.profile_name + '" assigned. Replace it?',
                                    function() {
                                        processProfileAssignment(profileId, 'replace');
                                    },
                                    'Replace Profile',
                                    'warning'
                                );
                            }
                        } else {
                            processProfileAssignment(profileId, 'add');
                        }
                    }
                });
                return;
            }
            
            var formData = $(this).serializeArray();
            
            // Collect invoice items if editing
            if(selectedAction === 'edit') {
                var items = [];
                $('#invoice_items_container .row').each(function() {
                    var rowId = $(this).attr('id');
                    if(rowId) {
                        var title = $('#' + rowId + '_title').val();
                        var description = $('#' + rowId + '_description').val();
                        var amount = $('#' + rowId + '_amount').val();
                        if(title && amount) {
                            items.push({
                                title: title,
                                description: description,
                                amount: amount
                            });
                        }
                    }
                });
                formData.push({name: 'items', value: JSON.stringify(items)});
            }
            
            showAjaxModal_alert('Processing...', 'loading');
            
            $.ajax({
                url: $(this).attr('action'),
                type: 'POST',
                data: $.param(formData),
                dataType: 'json'
            }).done(function(response) {
                if(response.status === 'success') {
                    showAjaxModal_alert(response.message + ' Christian', 'success', false);
                    setTimeout(function() { 
                        // Close all modal instances
                        $('#createModal, #ajaxModal, #invoiceModificationModal, .modal').modal('hide');
                        $('.modal-backdrop').remove();
                        $('body').removeClass('modal-open').css('padding-right', '');
                        
                        // Trigger custom event for parent page to handle refresh
                        var event = $.Event('invoiceModificationComplete');
                        $(document).trigger(event, {
                            invoice_code: invoiceCode,
                            action: selectedAction
                        });
                        
                        // Fallback: reload only if event wasn't handled
                        if(!event.isDefaultPrevented()) {
                            setTimeout(function() {
                                location.reload();
                            }, 500);
                        }
                    }, 2000);
                } else {
                    showAjaxModal_alert(response.message, 'error');
                }
            }).fail(function() {
                showAjaxModal_alert('An error occurred', 'error');
            });
        });
        
        function processProfileAssignment(profileId, action) {
            $('.close')[0].click();
            showAjaxModal_alert('Assigning profile...', 'loading');
            
            $.ajax({
                url: '<?php echo site_url("admin/assign_profile_to_invoice"); ?>',
                type: 'POST',
                data: {
                    student_id: <?php echo $student_id; ?>,
                    invoice_code: invoiceCode,
                    profile_id: profileId,
                    action: action
                },
                dataType: 'json',
                success: function(response) {
                    if(response.status === 'success') {
                        showAjaxModal_alert(response.message, 'success', false);
                        setTimeout(function() { 
                            // Close all modal instances
                            $('#createModal, #ajaxModal, #invoiceModificationModal, .modal').modal('hide');
                            $('.modal-backdrop').remove();
                            $('body').removeClass('modal-open').css('padding-right', '');
                            
                            // Trigger custom event for parent page to handle refresh
                            var event = $.Event('invoiceModificationComplete');
                            $(document).trigger(event, {
                                invoice_code: invoiceCode,
                                action: 'discount'
                            });
                            
                            // Fallback: reload only if event wasn't handled
                            if(!event.isDefaultPrevented()) {
                                setTimeout(function() {
                                    location.reload();
                                }, 500);
                            }
                        }, 2000);
                    } else {
                        showAjaxModal_alert(response.message, 'error');
                    }
                },
                error: function() {
                    showAjaxModal_alert('An error occurred', 'error');
                }
            });
        }
    })();
    </script>
    
    <div style="padding: 24px;">
        <!-- Invoice Info Card -->
        <div style="background: linear-gradient(135deg, <?php echo $gradient_light; ?> 0%, <?php echo $gradient_dark; ?> 100%); color: white; padding: 24px; border-radius: 12px; margin-bottom: 24px; box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);">
            <div style="display: flex; align-items: center; gap: 16px; justify-content: space-between;">
                <div style="display: flex; align-items: center; gap: 16px;">
                    <div style="background: rgba(255,255,255,0.2); padding: 16px; border-radius: 50%; backdrop-filter: blur(10px);">
                        <i class="fa fa-file-invoice" style="font-size: 28px;"></i>
                    </div>
                    <div>
                        <div style="font-size: 13px; opacity: 0.9; margin-bottom: 4px;">Invoice #<?php echo $invoice_code; ?></div>
                        <div style="font-size: 28px; font-weight: 700;"><?php echo $currency . ' ' . number_format($total_amount, 2); ?></div>
                        <?php if($discount): ?>
                        <div style="font-size: 12px; opacity: 0.9; margin-top: 4px;">
                            <i class="fa fa-tag"></i> Discount: <?php echo $currency . ' ' . number_format($discount->discount_amount, 2); ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div style="text-align: right; border-left: 2px solid rgba(255,255,255,0.3); padding-left: 20px;">
                    <div style="font-size: 15px; font-weight: 600; margin-bottom: 6px;">
                        <i class="fa fa-user"></i> <?php echo $student->name; ?>
                    </div>
                    <div style="font-size: 13px; opacity: 0.9;">
                        <i class="fa fa-id-card"></i> <?php echo $student->student_code; ?>
                    </div>
                    <div style="font-size: 13px; opacity: 0.9;">
                        <i class="fa fa-school"></i> <?php echo $class_name; ?>
                    </div>
                    <div style="font-size: 12px; opacity: 0.85; margin-top: 4px;">
                        <i class="fa fa-calendar"></i> <?php echo $first_item['year'] . ' | Term ' . $first_item['term']; ?>
                    </div>
                </div>
            </div>
        </div>

        <?php if($discount): ?>
        <!-- Discount Details Card -->
        <div style="background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); border-left: 4px solid #f59e0b; border-radius: 12px; padding: 20px; margin-bottom: 24px;">
            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px;">
                <i class="fa fa-tag" style="color: #92400e; font-size: 24px;"></i>
                <div style="font-size: 16px; font-weight: 700; color: #78350f;">Discount Applied to This Invoice</div>
            </div>
            <div style="color: #78350f; font-size: 14px;">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 12px;">
                    <div>
                        <strong>Profile:</strong> <?php echo $discount_profile ? $discount_profile->profile_name : 'N/A'; ?>
                    </div>
                    <div>
                        <strong>Category:</strong> <?php echo ucwords(str_replace('_', ' ', $discount->discount_category)); ?>
                    </div>
                    <div>
                        <strong>Method:</strong> <?php echo $discount->discount_method == 'percentage' ? $discount->discount_value . '%' : 'Fixed Amount'; ?>
                    </div>
                    <div>
                        <strong>Amount:</strong> <?php echo $currency . ' ' . number_format($discount->discount_amount, 2); ?>
                    </div>
                </div>
                <div style="margin-top: 12px; padding-top: 12px; border-top: 1px solid #fbbf24;">
                    <strong>Applies To:</strong> <?php echo $applicable_bill_items; ?>
                </div>
                <div style="margin-top: 12px; padding-top: 12px; border-top: 1px solid #fbbf24;">
                    <strong><i class="fa fa-info-circle"></i> Note:</strong> Modifying this invoice may affect the applied discount. The discount will need to be re-evaluated after modification.
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Action Selection -->
        <div style="margin-bottom: 24px;">
            <label style="display: block; font-size: 15px; font-weight: 700; color: #1f2937; margin-bottom: 12px;">
                <i class="fa fa-hand-pointer"></i> Select Action
            </label>
            <div style="display: flex; gap: 16px;">
                <div class="modal-action-btn modal-action-btn-edit" data-action="edit" id="btn-edit">
                    <i class="fa fa-edit" style="font-size: 32px; display: block; margin-bottom: 8px;"></i>
                    <div style="font-size: 18px; font-weight: 700; margin-bottom: 4px;">Edit Invoice</div>
                    <div style="font-size: 13px; opacity: 0.9;">Modify invoice items or amounts</div>
                </div>
                <div class="modal-action-btn" data-action="discount" id="btn-discount" style="background: linear-gradient(135deg, #f0f9ff 0%, #0ea5e9 100%); color: white;">
                    <i class="fa fa-tag" style="font-size: 32px; display: block; margin-bottom: 8px;"></i>
                    <div style="font-size: 18px; font-weight: 700; margin-bottom: 4px;">Apply Discount</div>
                    <div style="font-size: 13px; opacity: 0.9;">Assign discount profile</div>
                </div>
                <div class="modal-action-btn modal-action-btn-delete" data-action="delete" id="btn-delete">
                    <i class="fa fa-trash-alt" style="font-size: 32px; display: block; margin-bottom: 8px;"></i>
                    <div style="font-size: 18px; font-weight: 700; margin-bottom: 4px;">Delete Invoice</div>
                    <div style="font-size: 13px; opacity: 0.9;">Remove this invoice completely</div>
                </div>
            </div>
        </div>

        <!-- Edit Fields -->
        <div id="editFields" style="display: none; margin-bottom: 24px;">
            <div style="background: #f0fdf4; border: 2px solid #86efac; border-radius: 12px; padding: 20px;">
                <div style="font-size: 15px; font-weight: 700; color: #166534; margin-bottom: 16px;">
                    <i class="fa fa-edit"></i> Edit Invoice Items
                </div>
                <div id="invoice_items_container" class="p-5 overflow-y-auto max-h-[40vh]">
                    <!-- Invoice items will be loaded here via AJAX -->
                </div>
                <div style="margin-top: 16px; padding-top: 16px; border-top: 2px solid #86efac;">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <strong style="font-size: 16px; color: #166534;">New Total:</strong>
                        <strong style="font-size: 20px; color: #166534;" id="new_total"><?php echo $currency . ' ' . number_format($total_amount, 2); ?></strong>
                    </div>
                </div>
            </div>
        </div>

        <!-- Discount Fields -->
        <div id="discountFields" style="display: none; margin-bottom: 24px;">
            <div style="background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%); border: 2px solid #0ea5e9; border-radius: 16px; padding: 25px;">
                <h5 style="color: #0c4a6e; font-weight: 700; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
                    <i class="fa fa-tag" style="color: #0ea5e9;"></i>
                    <?php echo get_phrase('assign_discount_profile'); ?>
                </h5>
                
                <!-- Invoice Items List -->
                <div style="background: white; border-radius: 10px; padding: 20px; margin-bottom: 20px; border: 2px solid #bfdbfe;">
                    <h6 style="color: #1e40af; font-weight: 700; margin-bottom: 15px;"><i class="fa fa-list"></i> Invoice Items</h6>
                    <?php foreach($invoice_items as $item): ?>
                    <div class="bill-item-card" style="background: #f8fafc; border-left: 4px solid #3b82f6; padding: 12px 18px; margin-bottom: 10px; border-radius: 8px;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <div style="font-weight: 600; color: #1e40af; font-size: 14px;"><?php echo $item['title']; ?></div>
                                <?php if(!empty($item['description'])): ?>
                                <div style="font-size: 12px; color: #64748b; margin-top: 2px;"><?php echo $item['description']; ?></div>
                                <?php endif; ?>
                            </div>
                            <div style="font-weight: 700; color: #1e40af; font-size: 16px;"><?php echo $currency . ' ' . number_format($item['amount'], 2); ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <div class="bill-total-card" style="background: #dbeafe; border-left: 4px solid #1e40af; font-weight: bold; padding: 14px 18px; border-radius: 8px; margin-top: 15px;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <div style="font-size: 16px; color: #1e3a8a;">Total Amount</div>
                            <div style="font-size: 20px; color: #1e3a8a;"><?php echo $currency . ' ' . number_format($total_amount, 2); ?></div>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label style="font-weight: 600; color: #374151; margin-bottom: 10px;"><?php echo get_phrase('select_discount_profile'); ?></label>
                    <select name="profile_id" id="discount_profile_select" class="form-control" style="height: 50px; border: 2px solid #3b82f6; border-radius: 10px; font-weight: 600;">
                        <option value=""><?php echo get_phrase('select_profile'); ?></option>
                        <?php
                        $profiles = $this->db->where('is_active', 1)->where('discount_category', 'invoice')->get('discount_profiles')->result_array();
                        foreach($profiles as $profile):
                            $method_display = $profile['discount_method'] === 'percentage' ? $profile['discount_value'] . '%' : $currency . ' ' . number_format($profile['discount_value'], 2);
                        ?>
                        <option value="<?php echo $profile['profile_id']; ?>" 
                                data-method="<?php echo $profile['discount_method']; ?>" 
                                data-value="<?php echo $profile['discount_value']; ?>">
                            <?php echo $profile['profile_name'] . ' (' . $method_display . ')'; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div id="profile-preview" style="display: none; background: white; border-radius: 10px; padding: 20px; margin: 15px 0; border: 2px solid #10b981; box-shadow: 0 4px 6px rgba(16, 185, 129, 0.1);"></div>
            </div>
        </div>

        <!-- Delete Warning -->
        <div id="deleteWarning" style="display: none; margin-bottom: 24px;">
            <div style="background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%); border: 2px solid #ef4444; border-radius: 12px; padding: 20px;">
                <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px;">
                    <i class="fa fa-exclamation-circle" style="color: #dc2626; font-size: 32px;"></i>
                    <div style="font-size: 16px; font-weight: 700; color: #991b1b;">⚠️ Permanent Deletion Warning</div>
                </div>
                <div style="color: #7f1d1d; font-size: 14px; line-height: 1.6;">
                    <p style="margin: 0 0 8px 0;"><strong>You are about to permanently delete this invoice:</strong></p>
                    <ul style="margin: 8px 0; padding-left: 20px;">
                        <li>Invoice #<?php echo $invoice_code; ?> for <strong><?php echo $student->name; ?> (<?php echo $student->student_code; ?>)</strong></li>
                        <li>Total amount: <strong><?php echo $currency . ' ' . number_format($total_amount, 2); ?></strong></li>
                        <li>All <?php echo count($invoice_items); ?> invoice items will be removed</li>
                        <?php if($discount): ?>
                        <li>Associated discount will be removed</li>
                        <?php endif; ?>
                        <li>This action cannot be undone once approved</li>
                    </ul>
                    <p style="margin: 8px 0 0 0; font-weight: 600;">⚠️ Please ensure this is the correct action before proceeding.</p>
                </div>
            </div>
        </div>

        <!-- Reason Field -->
        <div style="margin-bottom: 24px;">
            <label style="display: block; font-size: 15px; font-weight: 700; color: #1f2937; margin-bottom: 8px;">
                <i class="fa fa-comment-dots"></i> Reason for Modification <span style="color: #ef4444;">*</span>
            </label>
            <textarea name="reason" class="form-control" rows="4" required 
                      placeholder="Please provide a detailed reason for this modification request..."
                      style="border: 2px solid #e5e7eb; border-radius: 12px; padding: 16px; font-size: 14px; resize: vertical; transition: all 0.3s;"
                      onfocus="this.style.borderColor='<?php echo $theme_color; ?>'; this.style.boxShadow='0 0 0 3px rgba(102, 126, 234, 0.1)'"
                      onblur="this.style.borderColor='#e5e7eb'; this.style.boxShadow='none'"></textarea>
        </div>

        <?php if($admin_level != 1): ?>
        <!-- Warning Box for non-super admin -->
        <div style="background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); border-left: 4px solid #f59e0b; border-radius: 8px; padding: 16px; margin-bottom: 24px;">
            <div style="display: flex; align-items: center; gap: 12px;">
                <i class="fa fa-exclamation-triangle" style="color: #92400e; font-size: 24px;"></i>
                <div style="color: #78350f; font-size: 14px; line-height: 1.6;">
                    <strong>Important:</strong> This request will be sent to super admin for approval. You will be notified once a decision is made.
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Action Buttons -->
        <div style="display: flex; gap: 12px; justify-content: flex-end;">
            <button type="button" class="btn btn-default" data-dismiss="modal" 
                    style="padding: 12px 32px; border-radius: 8px; font-weight: 600; font-size: 14px; border: 2px solid #e5e7eb;">
                <i class="fa fa-times"></i> Cancel
            </button>
            <button type="submit" class="btn btn-primary" 
                    style="padding: 12px 32px; border-radius: 8px; font-weight: 600; font-size: 14px; background: linear-gradient(135deg, <?php echo $gradient_light; ?> 0%, <?php echo $gradient_dark; ?> 100%); border: none; box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);">
                <i class="fa fa-paper-plane"></i> <?php echo $admin_level == 1 ? 'Apply Changes' : 'Submit Request'; ?>
            </button>
        </div>
    </div>
<?php echo form_close(); ?>
