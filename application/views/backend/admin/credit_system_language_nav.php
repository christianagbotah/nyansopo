<?php
/**
 * ADD THESE LANGUAGE PHRASES TO application/language/english/system_lang.php
 */

// Student Credit System
$lang['student_credits'] = 'Student Credits';
$lang['credit_balance'] = 'Credit Balance';
$lang['credit_applied'] = 'Credit Applied';
$lang['overpayment_credited'] = 'Overpayment Credited';
$lang['credit_history'] = 'Credit History';
$lang['adjust_credit'] = 'Adjust Credit';
$lang['credit_adjustment'] = 'Credit Adjustment';
$lang['available_credit'] = 'Available Credit';
$lang['credit_will_be_applied'] = 'Credit will be applied to next bill';
$lang['credit_created'] = 'Credit Created';
$lang['credit_transfer'] = 'Credit Transfer';
$lang['transfer_credit'] = 'Transfer Credit';
$lang['credit_management'] = 'Credit Management';
$lang['active_credits'] = 'Active Credits';
$lang['total_credits_earned'] = 'Total Credits Earned';
$lang['total_credits_used'] = 'Total Credits Used';
$lang['credit_utilization_rate'] = 'Credit Utilization Rate';
$lang['students_with_credits'] = 'Students with Credits';
$lang['credit_statistics'] = 'Credit Statistics';
$lang['manual_adjustment'] = 'Manual Adjustment';
$lang['credit_source'] = 'Credit Source';
$lang['applied_to_invoice'] = 'Applied to Invoice';
$lang['remaining_credit'] = 'Remaining Credit';
$lang['credit_status'] = 'Credit Status';
$lang['credit_notes'] = 'Credit Notes';
$lang['add_credit'] = 'Add Credit';
$lang['reduce_credit'] = 'Reduce Credit';
$lang['adjustment_reason'] = 'Adjustment Reason';
$lang['from_student'] = 'From Student';
$lang['to_student'] = 'To Student';
$lang['transfer_amount'] = 'Transfer Amount';
$lang['transfer_reason'] = 'Transfer Reason';
$lang['credit_system_enabled'] = 'Credit System Enabled';
$lang['credit_system_disabled'] = 'Credit System Disabled';
$lang['insufficient_credit'] = 'Insufficient Credit Balance';
$lang['credit_transferred_successfully'] = 'Credit transferred successfully';
$lang['credit_adjusted_successfully'] = 'Credit adjusted successfully';
$lang['credit_applied_to_invoice'] = 'Credit applied to invoice';
$lang['no_credits_available'] = 'No credits available';
$lang['credit_expired'] = 'Credit Expired';
$lang['credit_cancelled'] = 'Credit Cancelled';
$lang['fully_applied'] = 'Fully Applied';
$lang['partially_applied'] = 'Partially Applied';

/**
 * ADD THESE NAVIGATION ITEMS
 */

// For Admin Navigation (application/views/backend/admin/navigation.php)
// Add this under Financial section or create new Credit Management section
?>

<!-- Add to admin navigation -->
<li class="has-sub">
    <a href="javascript:;">
        <i class="fa fa-credit-card"></i>
        <span><?php echo get_phrase('credit_management'); ?></span>
    </a>
    <ul>
        <li>
            <a href="<?php echo site_url('admin/student_credits'); ?>">
                <i class="fa fa-list"></i>
                <span><?php echo get_phrase('student_credits'); ?></span>
            </a>
        </li>
        <li>
            <a href="<?php echo site_url('admin/credit_statistics'); ?>">
                <i class="fa fa-bar-chart"></i>
                <span><?php echo get_phrase('credit_statistics'); ?></span>
            </a>
        </li>
    </ul>
</li>

<?php
/**
 * OR add as single menu item under existing Financial section
 */
?>

<!-- Alternative: Add as single item under Financial section -->
<li>
    <a href="<?php echo site_url('admin/student_credits'); ?>">
        <i class="fa fa-credit-card"></i>
        <span><?php echo get_phrase('student_credits'); ?></span>
    </a>
</li>

<?php
/**
 * DASHBOARD WIDGET - Add to admin dashboard
 */
?>

<!-- Add this widget to admin dashboard -->
<div class="col-lg-3 col-md-6">
    <div class="panel panel-success">
        <div class="panel-heading">
            <div class="row">
                <div class="col-xs-3">
                    <i class="fa fa-credit-card fa-5x"></i>
                </div>
                <div class="col-xs-9 text-right">
                    <?php
                    // Get total active credits
                    $this->load->model('Credit_model');
                    $credit_stats = $this->Credit_model->get_credit_statistics();
                    ?>
                    <div class="huge">GH₵ <?=number_format($credit_stats['total_active_credits'], 0)?></div>
                    <div><?php echo get_phrase('active_credits'); ?></div>
                </div>
            </div>
        </div>
        <a href="<?=site_url('admin/student_credits')?>">
            <div class="panel-footer">
                <span class="pull-left"><?php echo get_phrase('view_details'); ?></span>
                <span class="pull-right"><i class="fa fa-arrow-circle-right"></i></span>
                <div class="clearfix"></div>
            </div>
        </a>
    </div>
</div>

<?php
/**
 * SETTINGS INTEGRATION - Add to system settings
 */
?>

<!-- Add these settings to your settings management -->
<div class="form-group">
    <label><?php echo get_phrase('credit_system_enabled'); ?></label>
    <select class="form-control" name="credit_system_enabled">
        <option value="1" <?php if(get_settings('credit_system_enabled') == '1') echo 'selected'; ?>>
            <?php echo get_phrase('yes'); ?>
        </option>
        <option value="0" <?php if(get_settings('credit_system_enabled') == '0') echo 'selected'; ?>>
            <?php echo get_phrase('no'); ?>
        </option>
    </select>
</div>

<div class="form-group">
    <label><?php echo get_phrase('auto_apply_credits'); ?></label>
    <select class="form-control" name="auto_apply_credits">
        <option value="1" <?php if(get_settings('auto_apply_credits') == '1') echo 'selected'; ?>>
            <?php echo get_phrase('yes'); ?>
        </option>
        <option value="0" <?php if(get_settings('auto_apply_credits') == '0') echo 'selected'; ?>>
            <?php echo get_phrase('no'); ?>
        </option>
    </select>
</div>

<div class="form-group">
    <label><?php echo get_phrase('credit_expiry_months'); ?></label>
    <input type="number" class="form-control" name="credit_expiry_months" 
           value="<?php echo get_settings('credit_expiry_months'); ?>" 
           placeholder="12" min="1" max="60">
    <small class="text-muted">Number of months before credits expire (0 = never expire)</small>
</div>

<?php
/**
 * NOTIFICATION TEMPLATES
 */
?>

<!-- Email/SMS notification templates for credit system -->

<!-- Credit Created Notification -->
<div class="notification-template" id="credit_created_template">
    <h4>Credit Created Notification</h4>
    <p><strong>Subject:</strong> Credit Added to Your Account</p>
    <p><strong>Message:</strong></p>
    <div style="background: #f8f9fa; padding: 10px; border-left: 4px solid #28a745;">
        Dear [STUDENT_NAME],<br><br>
        
        A credit of GH₵ [CREDIT_AMOUNT] has been added to your account from receipt #[RECEIPT_CODE].<br><br>
        
        This credit will be automatically applied to your next bill, reducing the amount you need to pay.<br><br>
        
        Current Credit Balance: GH₵ [TOTAL_CREDIT]<br><br>
        
        Thank you for your payment.<br><br>
        
        [SCHOOL_NAME]<br>
        [SCHOOL_CONTACT]
    </div>
</div>

<!-- Credit Applied Notification -->
<div class="notification-template" id="credit_applied_template">
    <h4>Credit Applied Notification</h4>
    <p><strong>Subject:</strong> Credit Applied to Your Bill</p>
    <p><strong>Message:</strong></p>
    <div style="background: #f8f9fa; padding: 10px; border-left: 4px solid #007bff;">
        Dear [STUDENT_NAME],<br><br>
        
        A credit of GH₵ [APPLIED_AMOUNT] has been applied to invoice #[INVOICE_CODE].<br><br>
        
        Original Amount: GH₵ [ORIGINAL_AMOUNT]<br>
        Credit Applied: -GH₵ [APPLIED_AMOUNT]<br>
        New Amount Due: GH₵ [NEW_DUE_AMOUNT]<br><br>
        
        Remaining Credit Balance: GH₵ [REMAINING_CREDIT]<br><br>
        
        [SCHOOL_NAME]<br>
        [SCHOOL_CONTACT]
    </div>
</div>

<?php
/**
 * QUICK SETUP CHECKLIST
 */
?>

<!-- Quick Setup Checklist for Implementation -->
<div class="setup-checklist">
    <h3>Credit System Implementation Checklist</h3>
    
    <div class="checklist-item">
        <input type="checkbox" id="db_schema"> 
        <label for="db_schema">Run database schema (student_credit_system.sql)</label>
    </div>
    
    <div class="checklist-item">
        <input type="checkbox" id="credit_model"> 
        <label for="credit_model">Upload Credit_model.php</label>
    </div>
    
    <div class="checklist-item">
        <input type="checkbox" id="admin_methods"> 
        <label for="admin_methods">Add credit methods to Admin.php controller</label>
    </div>
    
    <div class="checklist-item">
        <input type="checkbox" id="student_credits_view"> 
        <label for="student_credits_view">Upload student_credits.php view</label>
    </div>
    
    <div class="checklist-item">
        <input type="checkbox" id="credit_widget"> 
        <label for="credit_widget">Add credit widget to student profiles</label>
    </div>
    
    <div class="checklist-item">
        <input type="checkbox" id="receipt_integration"> 
        <label for="receipt_integration">Integrate credit display in receipts</label>
    </div>
    
    <div class="checklist-item">
        <input type="checkbox" id="navigation"> 
        <label for="navigation">Add navigation menu items</label>
    </div>
    
    <div class="checklist-item">
        <input type="checkbox" id="language_phrases"> 
        <label for="language_phrases">Add language phrases</label>
    </div>
    
    <div class="checklist-item">
        <input type="checkbox" id="dashboard_widget"> 
        <label for="dashboard_widget">Add dashboard widget</label>
    </div>
    
    <div class="checklist-item">
        <input type="checkbox" id="payment_integration"> 
        <label for="payment_integration">Integrate with existing payment processing</label>
    </div>
    
    <div class="checklist-item">
        <input type="checkbox" id="invoice_integration"> 
        <label for="invoice_integration">Integrate with invoice creation</label>
    </div>
    
    <div class="checklist-item">
        <input type="checkbox" id="testing"> 
        <label for="testing">Test overpayment scenarios</label>
    </div>
</div>

<style>
.setup-checklist {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    margin: 20px 0;
}

.checklist-item {
    margin: 10px 0;
    padding: 5px;
}

.checklist-item input[type="checkbox"] {
    margin-right: 10px;
    transform: scale(1.2);
}

.checklist-item label {
    font-weight: normal;
    cursor: pointer;
}

.notification-template {
    background: white;
    border: 1px solid #ddd;
    border-radius: 5px;
    padding: 15px;
    margin: 15px 0;
}
</style>