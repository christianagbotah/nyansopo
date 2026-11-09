<?php
$currency = $this->db->get_where('settings', array('type' => 'currency'))->row()->description;
?>
<style>
.modern-discount-card {
    background: white;
    border-radius: 16px;
    padding: 30px;
    margin-top: 20px;
    box-shadow: 0 8px 24px rgba(0,0,0,0.12);
    border: 1px solid #e2e8f0;
}
.discount-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 20px;
    border-radius: 12px;
    margin-bottom: 25px;
    display: flex;
    align-items: center;
    gap: 12px;
}
.discount-header i {
    font-size: 24px;
}
.discount-header h5 {
    margin: 0;
    font-weight: 700;
    font-size: 18px;
    color: white;
}
.modern-form-group {
    margin-bottom: 20px;
}
.modern-label {
    font-weight: 600;
    color: #2d3748;
    margin-bottom: 8px;
    font-size: 13px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    display: block;
}
.modern-input,
.modern-select,
.modern-textarea {
    width: 100%;
    padding: 12px 16px;
    border: 2px solid #e2e8f0;
    border-radius: 10px;
    font-size: 14px;
    transition: all 0.3s;
    background: #f8fafc;
}
.modern-input:focus,
.modern-select:focus,
.modern-textarea:focus {
    outline: none;
    border-color: #667eea;
    background: white;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}
.modern-btn {
    padding: 14px 24px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    border-radius: 10px;
    font-weight: 700;
    font-size: 15px;
    cursor: pointer;
    transition: all 0.3s;
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}
.modern-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
}
.modern-btn i {
    margin-right: 8px;
}
</style>

<div class="modern-discount-card">
    <div class="discount-header">
        <i class="fa fa-tag"></i>
        <h5><?php echo get_phrase('apply_discount'); ?></h5>
    </div>
    
    <?php echo form_open('', array('id' => 'discount-form')); ?>
        <input type="hidden" name="invoice_code" value="<?php echo $invoice_code; ?>">
        <input type="hidden" name="student_id" value="<?php echo $student_id; ?>">
        
        <div class="row">
            <div class="col-md-6">
                <div class="modern-form-group">
                    <label class="modern-label" style="display: flex; justify-content: space-between; align-items: center;">
                        <span><i class="fa fa-list-alt"></i> <?php echo get_phrase('discount_type'); ?></span>
                        <button type="button" onclick="quickAddInvoiceDiscountType()" class="btn btn-xs" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; border-radius: 6px; padding: 4px 10px; font-weight: 600; text-transform: none; letter-spacing: normal;">
                            <i class="fa fa-plus-circle"></i> Add Type
                        </button>
                    </label>
                    <select name="discount_type" id="discount_type_select" class="modern-select" required>
                        <option value=""><?php echo get_phrase('select_discount_type'); ?></option>
                    </select>
                </div>
            </div>
            <div class="col-md-6">
                <div class="modern-form-group">
                    <label class="modern-label">
                        <i class="fa fa-calculator"></i> <?php echo get_phrase('discount_method'); ?>
                    </label>
                    <select name="discount_method" class="modern-select" required>
                        <option value="percentage"><?php echo get_phrase('percentage'); ?> (%)</option>
                        <option value="fixed"><?php echo get_phrase('fixed_amount'); ?> (<?php echo $currency; ?>)</option>
                    </select>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="modern-form-group">
                    <label class="modern-label">
                        <i class="fa fa-percent"></i> <?php echo get_phrase('discount_value'); ?>
                    </label>
                    <input type="number" step="0.01" name="discount_value" class="modern-input" placeholder="0.00" required>
                </div>
            </div>
            <div class="col-md-6">
                <div class="modern-form-group">
                    <label class="modern-label">
                        <i class="fa fa-comment-alt"></i> <?php echo get_phrase('reason'); ?> (Optional)
                    </label>
                    <textarea name="reason" class="modern-textarea" rows="3" placeholder="Enter reason for discount..."></textarea>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-12">
                <button type="submit" class="modern-btn">
                    <i class="fa fa-check-circle"></i> <?php echo get_phrase('apply_discount'); ?>
                </button>
            </div>
        </div>
    <?php echo form_close(); ?>
</div>

<script>
$(document).ready(function() {
    loadInvoiceDiscountTypes();
});

function loadInvoiceDiscountTypes() {
    // Load invoice discount types only (category_id = 1)
    $.get('<?php echo site_url("admin/get_invoice_discount_types"); ?>', function(types) {
        var select = $('#discount_type_select');
        var currentValue = select.val();
        select.html('<option value=""><?php echo get_phrase('select_discount_type'); ?></option>');
        types.forEach(function(type) {
            select.append(
                '<option value="' + type.discount_type_id + '">' + 
                (type.icon || '') + ' ' + type.name + 
                '</option>'
            );
        });
        if(currentValue) select.val(currentValue);
    }, 'json');
}

function quickAddInvoiceDiscountType() {
    // Load the discount type form in modal with category_id = 1 (invoice)
    $.get('<?php echo site_url("admin/discount_type_form"); ?>?category_id=1', function(html) {
        $('#modal_ajax').html(html).modal('show');
        
        // Override the form submission to reload types after save
        setTimeout(function() {
            $('#discount-type-form').off('submit').on('submit', function(e) {
                e.preventDefault();
                showAjaxModal_alert('Saving discount type...', 'loading');
                
                $.ajax({
                    url: $(this).attr('action'),
                    type: 'POST',
                    data: new FormData(this),
                    cache: false,
                    contentType: false,
                    processData: false,
                    dataType: 'json'
                }).done(function(response) {
                    if(response.status === 'success') {
                        showAjaxModal_alert(response.message, 'success');
                        $('#modal_ajax').modal('hide');
                        
                        // Reload discount types without resetting form
                        loadInvoiceDiscountTypes();
                    } else {
                        showAjaxModal_alert(response.message || 'Operation failed', 'error');
                    }
                }).fail(function() {
                    showAjaxModal_alert('An error occurred', 'error');
                });
            });
        }, 500);
    });
}
</script>
