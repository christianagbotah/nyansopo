<?php
$invoice_code = $this->uri->segment(3);
$invoice = $this->db->where('invoice_code', $invoice_code)->get('invoice')->row();

if(!$invoice) {
    echo '<div style="padding: 40px; text-align: center;">';
    echo '<i class="fa fa-exclamation-triangle" style="font-size: 48px; color: #ef4444; margin-bottom: 16px;"></i>';
    echo '<h3>Invoice not found</h3>';
    echo '</div>';
    return;
}

$student = $this->db->where('student_id', $invoice->student_id)->get('student')->row();
$currency = $this->db->get_where('settings', array('type' => 'currency'))->row()->description;

// Get current discount profile if exists
$current_profile = $this->db->select('sd.*, dp.profile_name, dp.discount_method, dp.discount_value')
    ->from('student_discounts sd')
    ->join('discount_profiles dp', 'sd.profile_id = dp.profile_id')
    ->where('sd.student_id', $invoice->student_id)
    ->where('sd.invoice_code', $invoice_code)
    ->where('sd.discount_category', 'invoice')
    ->where('sd.status', 'approved')
    ->get()->row();
?>

<style>
#invoiceDiscountProfileForm .profile-card {
    background: white;
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    padding: 16px;
    margin-bottom: 12px;
    cursor: pointer;
    transition: all 0.3s;
}
#invoiceDiscountProfileForm .profile-card:hover {
    border-color: #3b82f6;
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.2);
}
#invoiceDiscountProfileForm .profile-card.selected {
    border-color: #10b981;
    background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
}
</style>

<?php echo form_open('admin/assign_discount_profile_to_invoice', array('id' => 'invoiceDiscountProfileForm')); ?>
    <input type="hidden" name="student_id" value="<?php echo $invoice->student_id; ?>">
    <input type="hidden" name="invoice_code" value="<?php echo $invoice_code; ?>">
    <input type="hidden" name="profile_id" id="selected_profile_id">
    
    <div style="padding: 24px;">
        <!-- Invoice Info -->
        <div style="background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%); padding: 20px; border-radius: 12px; margin-bottom: 24px;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <div style="font-size: 13px; color: #1e40af; font-weight: 600; margin-bottom: 4px;">Invoice #<?php echo $invoice_code; ?></div>
                    <div style="font-size: 24px; font-weight: 700; color: #1e3a8a;"><?php echo $student->name; ?></div>
                    <div style="font-size: 13px; color: #3b82f6; margin-top: 4px;">
                        <i class="fa fa-calendar"></i> Term <?php echo $invoice->term; ?> / <?php echo $invoice->year; ?>
                    </div>
                </div>
                <div style="text-align: right;">
                    <div style="font-size: 13px; color: #1e40af; margin-bottom: 4px;">Total Amount</div>
                    <div style="font-size: 28px; font-weight: 700; color: #1e3a8a;"><?php echo $currency . ' ' . number_format($invoice->total_amount, 2); ?></div>
                </div>
            </div>
        </div>

        <?php if($current_profile): ?>
        <!-- Current Profile -->
        <div style="background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); border: 2px solid #f59e0b; border-radius: 12px; padding: 20px; margin-bottom: 24px;">
            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px;">
                <i class="fa fa-info-circle" style="color: #92400e; font-size: 24px;"></i>
                <div style="font-size: 16px; font-weight: 700; color: #78350f;">Current Discount Profile</div>
            </div>
            <div style="color: #78350f; font-size: 14px;">
                <strong><?php echo $current_profile->profile_name; ?></strong> - 
                <?php 
                echo $current_profile->discount_method === 'percentage' 
                    ? $current_profile->discount_value . '%' 
                    : $currency . ' ' . number_format($current_profile->discount_value, 2); 
                ?>
            </div>
            <div style="margin-top: 12px; font-size: 13px; color: #92400e;">
                <i class="fa fa-exclamation-triangle"></i> Selecting a new profile will replace the current one
            </div>
        </div>
        <?php endif; ?>

        <!-- Profile Selection -->
        <div style="margin-bottom: 24px;">
            <label style="display: block; font-size: 15px; font-weight: 700; color: #1f2937; margin-bottom: 12px;">
                <i class="fa fa-tag"></i> Select Discount Profile
            </label>
            <div id="profiles-list">
                <?php
                $profiles = $this->db->where('is_active', 1)->where('discount_category', 'invoice')->get('discount_profiles')->result_array();
                if(empty($profiles)):
                ?>
                <div style="padding: 40px; text-align: center; color: #6b7280;">
                    <i class="fa fa-inbox" style="font-size: 48px; margin-bottom: 12px;"></i>
                    <div>No active discount profiles found</div>
                </div>
                <?php else: ?>
                <?php foreach($profiles as $profile): 
                    $method_display = $profile['discount_method'] === 'percentage' 
                        ? $profile['discount_value'] . '%' 
                        : $currency . ' ' . number_format($profile['discount_value'], 2);
                ?>
                <div class="profile-card" onclick="selectProfile(<?php echo $profile['profile_id']; ?>)" data-profile-id="<?php echo $profile['profile_id']; ?>">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div style="flex: 1;">
                            <div style="font-size: 16px; font-weight: 700; color: #1f2937; margin-bottom: 4px;">
                                <?php echo $profile['profile_name']; ?>
                            </div>
                            <div style="font-size: 13px; color: #6b7280;">
                                <?php echo ucfirst($profile['discount_method']); ?> Discount
                            </div>
                        </div>
                        <div style="text-align: right;">
                            <div style="font-size: 24px; font-weight: 700; color: #059669;">
                                <?php echo $method_display; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Action Buttons -->
        <div style="display: flex; gap: 12px; justify-content: flex-end;">
            <button type="button" class="btn btn-default" data-dismiss="modal" 
                    style="padding: 12px 32px; border-radius: 8px; font-weight: 600; font-size: 14px; border: 2px solid #e5e7eb;">
                <i class="fa fa-times"></i> Cancel
            </button>
            <button type="submit" class="btn btn-primary" 
                    style="padding: 12px 32px; border-radius: 8px; font-weight: 600; font-size: 14px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none;">
                <i class="fa fa-check-circle"></i> Assign Profile
            </button>
        </div>
    </div>
<?php echo form_close(); ?>

<script>
let selectedProfileId = null;

function selectProfile(profileId) {
    selectedProfileId = profileId;
    $('#selected_profile_id').val(profileId);
    
    $('#invoiceDiscountProfileForm .profile-card').removeClass('selected');
    $('#invoiceDiscountProfileForm .profile-card[data-profile-id="' + profileId + '"]').addClass('selected');
}

$(document).ready(function() {
    $('#invoiceDiscountProfileForm').submit(function(e) {
        e.preventDefault();
        
        if(!selectedProfileId) {
            showAjaxModal_alert('Please select a discount profile', 'warning');
            return;
        }
        
        $('.close')[0].click();
        showAjaxModal_alert('Assigning discount profile...', 'loading');
        
        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json'
        }).done(function(response) {
            showAjaxModal_alert(response.message, response.status, false);
            if(response.status === 'success') {
                setTimeout(() => {
                    $('.close').click();
                    if(typeof refreshBulkInvoices === 'function') {
                        refreshBulkInvoices();
                    }
                }, 1500);
            }
        }).fail(function() {
            showAjaxModal_alert('An error occurred', 'error');
        });
    });
});
</script>
