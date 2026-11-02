<?php
$student_info = $this->db->get_where('student', array('student_id' => $student_id))->row();
$currency_row = $this->db->get_where('settings', array('type' => 'currency'))->row();
$currency = $currency_row ? $currency_row->description : 'GHS';
$boarding_system_row = $this->db->get_where('settings', array('type' => 'boarding_system'))->row();
$boarding_system = $boarding_system_row ? $boarding_system_row->description : 'no';
$theme_color_row = $this->db->get_where('settings', array('type' => 'theme_color'))->row();
$theme_color = $theme_color_row ? $theme_color_row->description : '667eea';

// Ensure theme color has # prefix
if(strpos($theme_color, '#') !== 0) {
    $theme_color = '#' . $theme_color;
}

// Create gradient colors from theme color
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

// Get student class info
$running_year = get_settings('running_year');
$running_term = get_settings('running_term');
$enroll = $this->db->get_where('enroll', array('student_id' => $student_id, 'year' => $running_year))->row();
$class_name = '';
if($enroll) {
    $class_name = getFullClassName($enroll->class_id);
}
?>

<!-- Modern Header with Gradient -->
<div style="background: linear-gradient(135deg, <?php echo $gradient_light; ?> 0%, <?php echo $gradient_dark; ?> 100%); padding: 2rem; margin: -20px -20px 0 -20px; border-radius: 12px 12px 0 0; position: relative;">
    <!-- Enlarged Close Button (Danger) -->
    <button type="button" class="close" data-dismiss="modal" style="position: absolute; top: 15px; right: 15px; background: #ef4444; color: white; border: none; width: 50px; height: 50px; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 24px; transition: all 0.3s; box-shadow: 0 4px 12px rgba(239, 68, 68, 0.4); opacity: 1; text-shadow: none;" onmouseover="this.style.background='#dc2626'; this.style.transform='scale(1.1) rotate(90deg)'; this.style.boxShadow='0 6px 16px rgba(239, 68, 68, 0.6)'" onmouseout="this.style.background='#ef4444'; this.style.transform='scale(1) rotate(0deg)'; this.style.boxShadow='0 4px 12px rgba(239, 68, 68, 0.4)'">&times;</button>
    <div style="display: flex; align-items: center; gap: 20px; color: white;">
        <div style="background: rgba(255,255,255,0.2); padding: 20px; border-radius: 50%; backdrop-filter: blur(10px);">
            <i class="fa fa-receipt" style="font-size: 36px;"></i>
        </div>
        <div style="flex: 1;">
            <h2 style="margin: 0; font-size: 20px; font-weight: 700; color: white;"><?php echo $student_info->name; ?></h2>
            <p style="margin: 5px 0 0 0; opacity: 0.9; font-size: 14px;">
                <i class="fa fa-id-card"></i> <?php echo $student_info->student_code; ?> 
                <?php if($class_name): ?>
                    <span style="margin-left: 15px;"><i class="fa fa-school"></i> <?php echo $class_name; ?></span>
                <?php endif; ?>
            </p>
        </div>
    </div>
</div>

<div class="modal-body" style="padding: 25px; background: #f8f9fa;">
    <!-- Advanced Filters Card -->
    <div style="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); margin-bottom: 20px;">
        <div style="display: flex; align-items: center; margin-bottom: 15px;">
            <i class="fa fa-filter" style="color: <?php echo $theme_color; ?>; font-size: 18px; margin-right: 10px;"></i>
            <h4 style="margin: 0; font-weight: 600; color: #2d3748;">Advanced Filters</h4>
        </div>
        <!-- Single row with filters and buttons -->
        <div style="display: flex; gap: 10px; align-items: flex-end; flex-wrap: wrap;">
            <div style="flex: 1; min-width: 150px;">
                <label style="display: block; margin-bottom: 6px; font-weight: 600; font-size: 13px; color: #4a5568;"><i class="fa fa-calendar"></i> Start Date</label>
                <input type="text" id="receipt_start_date" class="form-control datepicker" placeholder="Start Date" style="height: 42px; border: 2px solid #e2e8f0; border-radius: 8px;">
            </div>
            <div style="flex: 1; min-width: 150px;">
                <label style="display: block; margin-bottom: 6px; font-weight: 600; font-size: 13px; color: #4a5568;"><i class="fa fa-calendar-check"></i> End Date</label>
                <input type="text" id="receipt_end_date" class="form-control datepicker" placeholder="End Date" style="height: 42px; border: 2px solid #e2e8f0; border-radius: 8px;">
            </div>
            <div style="flex: 1; min-width: 150px;">
                <label style="display: block; margin-bottom: 6px; font-weight: 600; font-size: 13px; color: #4a5568;"><i class="fa fa-calendar-alt"></i> Year</label>
                <select id="receipt_year" class="form-control" style="height: 42px; border: 2px solid #e2e8f0; border-radius: 8px;">
                    <option value="">All Years</option>
                    <?php echo populate_academic_year('yes', ''); ?>
                </select>
            </div>
            <div style="flex: 1; min-width: 150px;">
                <label style="display: block; margin-bottom: 6px; font-weight: 600; font-size: 13px; color: #4a5568;"><i class="fa fa-list"></i> Term</label>
                <select id="receipt_term" class="form-control" style="height: 42px; border: 2px solid #e2e8f0; border-radius: 8px;">
                    <option value="">All Terms</option>
                    <option value="1">1</option>
                    <option value="2">2</option>
                    <option value="3">3</option>
                </select>
            </div>
            <!-- Buttons aligned with inputs -->
            <button type="button" id="filter_receipts_btn" style="background: linear-gradient(135deg, <?php echo $gradient_light; ?> 0%, <?php echo $gradient_dark; ?> 100%); color: white; border: none; height: 42px; padding: 0 24px; border-radius: 8px; font-weight: 600; font-size: 14px; cursor: pointer; box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4); transition: all 0.3s; white-space: nowrap;">
                <i class="fa fa-sync-alt"></i> Apply
            </button>
            <button type="button" id="clear_receipt_filters_btn" style="background: white; color: <?php echo $theme_color; ?>; border: 2px solid <?php echo $theme_color; ?>; height: 42px; padding: 0 24px; border-radius: 8px; font-weight: 600; font-size: 14px; cursor: pointer; transition: all 0.3s; white-space: nowrap;">
                <i class="fa fa-redo"></i> Reset
            </button>
        </div>
    </div>

    <!-- Receipts Table Card -->
    <div style="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
        <div class="table-responsive" style="overflow-x: auto;">
            <table class="table table-hover" id="student_receipts_table" style="width: 100%; margin: 0; table-layout: auto;" width="100%">
                <thead style="background: linear-gradient(135deg, <?php echo $gradient_light; ?> 0%, <?php echo $gradient_dark; ?> 100%); color: white !important;">
                    <tr>
                        <th style="padding: 15px; font-weight: 600; border: none; white-space: nowrap; min-width: 120px; color: white !important;">Receipt #</th>
                        <th style="padding: 15px; font-weight: 600; border: none; white-space: nowrap; min-width: 120px; color: white !important;">Invoice #</th>
                        <th style="padding: 15px; font-weight: 600; border: none; text-align: right; white-space: nowrap; min-width: 120px; color: white !important;">Amount</th>
                        <th style="padding: 15px; font-weight: 600; border: none; white-space: nowrap; min-width: 140px; color: white !important;">Method</th>
                        <th style="padding: 15px; font-weight: 600; border: none; white-space: nowrap; min-width: 140px; color: white !important;">Date</th>
                        <th style="padding: 15px; font-weight: 600; border: none; white-space: nowrap; min-width: 120px; color: white !important;">Year | Term</th>
                        <th style="padding: 15px; font-weight: 600; border: none; text-align: center; white-space: nowrap; min-width: 180px; color: white !important;">Actions</th>
                    </tr>
                </thead>
                <tbody id="receipts_tbody">
                    <?php
                    // Fetch receipts using receipt_code grouping since single payment can cover multiple invoices
                    // Exclude daily fee receipts (where invoice_code and invoice_id are NULL)
                    $this->db->select('*');
                    $this->db->from('payment');
                    $this->db->where('student_id', $student_id);
                    $this->db->where('invoice_code IS NOT NULL', NULL, FALSE);
                    $this->db->where('invoice_id IS NOT NULL', NULL, FALSE);
                    $this->db->group_by('receipt_code');
                    $this->db->order_by('timestamp', 'DESC');
                    $receipts = $this->db->get()->result_array();
                    $has_receipts = count($receipts) > 0;
                    if($has_receipts):
                        foreach($receipts as $receipt):
                            // Get all invoice codes covered by this receipt
                            $invoice_codes = $this->db->select('invoice_code, amount')
                                ->from('payment')
                                ->where('receipt_code', $receipt['receipt_code'])
                                ->where('student_id', $student_id)
                                ->get()
                                ->result_array();
                            $invoice_codes_list = array_unique(array_column($invoice_codes, 'invoice_code'));
                            $total_amount = array_sum(array_column($invoice_codes, 'amount'));
                            $invoice_display = count($invoice_codes_list) > 1 ? 
                                '#' . implode(', #', array_slice($invoice_codes_list, 0, 2)) . (count($invoice_codes_list) > 2 ? '...' : '') : 
                                '#' . $invoice_codes_list[0];
                            $payment_method = '';
                            $method_icon = '';
                            $method_color = '';
                            $method = isset($receipt['payment_method']) ? $receipt['payment_method'] : 0;
                            // Handle both numeric IDs and string values
                            if($method == '1' || $method == 'cash') {
                                $payment_method = 'Cash';
                                $method_icon = 'fa-money-bill-wave';
                                $method_color = '#10b981';
                            } elseif($method == '2' || $method == 'cheque') {
                                $payment_method = 'Cheque';
                                $method_icon = 'fa-money-check';
                                $method_color = '#3b82f6';
                            } elseif($method == '4' || $method == 'card') {
                                $payment_method = 'Card';
                                $method_icon = 'fa-credit-card';
                                $method_color = '#8b5cf6';
                            } elseif($method == '3' || $method == 'momo' || $method == 'mobile_money') {
                                $payment_method = 'Mobile Money';
                                $method_icon = 'fa-mobile-alt';
                                $method_color = '#f59e0b';
                            } elseif($method == 'bank_transfer') {
                                $payment_method = 'Bank Transfer';
                                $method_icon = 'fa-university';
                                $method_color = '#06b6d4';
                            } else {
                                $payment_method = 'Cash';
                                $method_icon = 'fa-wallet';
                                $method_color = '#10b981';
                            }
                    ?>
                    <tr style="border-bottom: 1px solid #e2e8f0;">
                        <td style="padding: 15px;"><span style="font-weight: 700; color: <?php echo $theme_color; ?>; font-size: 14px;">#<?php echo $receipt['receipt_code']; ?></span></td>
                        <td style="padding: 15px;">
                            <span style="font-weight: 600; color: #4a5568;" title="<?php echo implode(', ', array_map(function($c) { return '#'.$c; }, $invoice_codes_list)); ?>">
                                <?php echo $invoice_display; ?>
                                <?php if(count($invoice_codes_list) > 1): ?>
                                    <span style="background: #3b82f6; color: white; padding: 2px 6px; border-radius: 10px; font-size: 10px; margin-left: 5px;"><?php echo count($invoice_codes_list); ?></span>
                                <?php endif; ?>
                            </span>
                        </td>
                        <td style="padding: 15px; text-align: right;"><span style="font-weight: 700; color: #10b981; font-size: 15px;"><?php echo $currency . number_format($total_amount, 2); ?></span></td>
                        <td style="padding: 15px;"><span style="color: <?php echo $method_color; ?>; font-weight: 600;"><i class="fa <?php echo $method_icon; ?>"></i> <?php echo $payment_method; ?></span></td>
                        <td style="padding: 15px;" class="white-space: nowrap">
                            <div style="font-size: 13px; color: #4a5568;">
                                <div style="font-weight: 600;"><?php echo date('M d, Y', $receipt['timestamp']); ?></div>
                                <div style="font-size: 11px; color: #a0aec0;"><?php echo date('h:i A', $receipt['timestamp']); ?></div>
                            </div>
                        </td>
                        <td style="padding: 15px;"><span style="font-weight: 600; color: #4a5568;"><?php echo $receipt['year'] . ' | ' . $receipt['term']; ?></span></td>
                        <td style="padding: 15px; text-align: center;">
                            <div style="display: flex; gap: 8px; justify-content: center; align-items: center;">
                                <button onclick="viewReceiptDetails('<?php echo $receipt['receipt_code']; ?>', '<?php echo $student_id; ?>', '<?php echo $total_amount; ?>', '<?php echo $receipt['timestamp']; ?>')" 
                                        style="background: linear-gradient(135deg, <?php echo $gradient_light; ?> 0%, <?php echo $gradient_dark; ?> 100%); color: white; border: none; padding: 8px 16px; border-radius: 6px; font-weight: 600; font-size: 12px; cursor: pointer; transition: all 0.3s; box-shadow: 0 2px 6px rgba(102, 126, 234, 0.3); white-space: nowrap;"
                                        onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 10px rgba(102, 126, 234, 0.5)'"
                                        onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 6px rgba(102, 126, 234, 0.3)'">
                                    <i class="fa fa-eye"></i> View
                                </button>
                                <button onclick="requestModification(<?php echo $receipt['payment_id']; ?>)" 
                                        style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white; border: none; padding: 8px 16px; border-radius: 6px; font-weight: 600; font-size: 12px; cursor: pointer; transition: all 0.3s; box-shadow: 0 2px 6px rgba(245, 158, 11, 0.3); white-space: nowrap;"
                                        onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 10px rgba(245, 158, 11, 0.5)'"
                                        onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 6px rgba(245, 158, 11, 0.3)'">
                                    <i class="fa fa-edit"></i> Modify
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php 
                        endforeach;
                    else:
                    ?>
                    <tr>
                        <td colspan="7" style="padding: 60px 40px; text-align: center; background: #f9fafb;">
                            <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 15px;">
                                <div style="background: linear-gradient(135deg, <?php echo $gradient_light; ?> 0%, <?php echo $gradient_dark; ?> 100%); width: 80px; height: 80px; border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);">
                                    <i class="fa fa-receipt" style="font-size: 36px; color: white;"></i>
                                </div>
                                <div>
                                    <h4 style="font-size: 18px; font-weight: 700; color: #2d3748; margin: 0 0 8px 0;">No Receipts Found</h4>
                                    <p style="font-size: 14px; color: #718096; margin: 0; max-width: 400px;">This student has not made any payments yet. Receipts will appear here once payments are recorded.</p>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal-footer" style="background: #f8f9fa; border-top: 2px solid #e2e8f0; padding: 20px;">
    <button type="button" class="btn btn-default" data-dismiss="modal" style="padding: 12px 30px; border-radius: 8px; font-weight: 600; font-size: 14px;">
        <i class="fa fa-times"></i> Close
    </button>
</div>

<script>
$(document).ready(function() {
    $('.datepicker').datepicker({
        format: 'dd-mm-yyyy',
        autoclose: true
    });
    
    <?php if($has_receipts): ?>
    var table = $('#student_receipts_table').DataTable({
        "order": [[4, "desc"]],
        "autoWidth": false
    });
    $('#student_receipts_table colgroup').remove();
    <?php endif; ?>
    
    // Filter receipts
    $('#filter_receipts_btn').click(function() {
        filterReceipts();
    });
    
    $('#clear_receipt_filters_btn').click(function() {
        $('#receipt_start_date').val('');
        $('#receipt_end_date').val('');
        $('#receipt_year').val('');
        $('#receipt_term').val('');
        filterReceipts();
    });
});

function filterReceipts() {
    const student_id = <?php echo $student_id; ?>;
    const start_date = $('#receipt_start_date').val();
    const end_date = $('#receipt_end_date').val();
    const year = $('#receipt_year').val();
    const term = $('#receipt_term').val();
    
    $.ajax({
        url: '<?php echo site_url('admin/filter_student_receipts'); ?>',
        type: 'POST',
        data: {
            student_id: student_id,
            start_date: start_date,
            end_date: end_date,
            year: year,
            term: term
        },
        beforeSend: function() {
            $('#receipts_tbody').html('<tr><td colspan="7" class="text-center"><i class="fa fa-spinner fa-spin"></i> <?php echo get_phrase('loading'); ?>...</td></tr>');
        },
        success: function(response) {
            $('#receipts_tbody').html(response);
            if($.fn.DataTable.isDataTable('#student_receipts_table')) {
                $('#student_receipts_table').DataTable().destroy();
            }
            var table = $('#student_receipts_table').DataTable({
                "order": [[4, "desc"]],
                "autoWidth": false
            });
            $('#student_receipts_table colgroup').remove();
        }
    });
}

function viewReceiptDetails(receipt_code, student_id, amount, timestamp) {
    window.open('<?php echo site_url('admin/receipt/'); ?>' + receipt_code + '/' + student_id + '/' + amount + '/' + timestamp, '_blank');
}

function requestModification(paymentId) {
    loadModalContent('createModal', 
        '<?php echo site_url("admin/receipt_modification_modal/"); ?>' + paymentId, 
        '<i class="fa fa-edit"></i> Request Receipt Modification');
}
</script>
