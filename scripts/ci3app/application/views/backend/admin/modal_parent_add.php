<style>
.parent-form-modal {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
    padding: 20px;
}
.parent-form-modal .form-header {
    text-align: center;
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 2px solid #e8f5e9;
}
.parent-form-modal .form-header .header-icon {
    width: 70px;
    height: 70px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 15px;
}
.parent-form-modal .form-header .header-icon i {
    font-size: 32px;
    color: #fff;
}
.parent-form-modal .form-header h3 {
    margin: 0;
    color: #333;
    font-weight: 600;
}
.parent-form-modal .form-header p {
    margin: 5px 0 0;
    color: #666;
    font-size: 14px;
}
.parent-form-modal .form-section {
    margin-bottom: 25px;
}
.parent-form-modal .section-title {
    font-size: 13px;
    font-weight: 600;
    color: #667eea;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 15px;
    display: flex;
    align-items: center;
    gap: 8px;
}
.parent-form-modal .section-title i {
    font-size: 14px;
}
.parent-form-modal .form-row {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
    margin-bottom: 15px;
}
@media (max-width: 768px) {
    .parent-form-modal .form-row {
        grid-template-columns: 1fr;
    }
}
.parent-form-modal .form-group {
    margin-bottom: 0;
}
.parent-form-modal .form-group label {
    display: block;
    font-size: 13px;
    font-weight: 600;
    color: #555;
    margin-bottom: 6px;
}
.parent-form-modal .form-group label .required {
    color: #e53935;
    margin-left: 2px;
}
.parent-form-modal .form-control {
    width: 100%;
    padding: 14px 16px;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    font-size: 14px;
    transition: all 0.3s ease;
    background: #fff;
    min-height: 48px;
    line-height: 1.5;
}
.parent-form-modal .form-control:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}
.parent-form-modal .form-control::placeholder {
    color: #aaa;
}
.parent-form-modal select.form-control {
    cursor: pointer;
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23555' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 12px center;
    padding-right: 35px;
}
.parent-form-modal .checkbox-wrapper {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 16px;
    background: #f8f9fa;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s ease;
}
.parent-form-modal .checkbox-wrapper:hover {
    background: #e8f5e9;
}
.parent-form-modal .checkbox-wrapper input[type="checkbox"] {
    width: 20px;
    height: 20px;
    cursor: pointer;
    accent-color: #667eea;
}
.parent-form-modal .checkbox-wrapper .checkbox-label {
    font-size: 14px;
    font-weight: 500;
    color: #333;
}
.parent-form-modal .checkbox-wrapper .checkbox-status {
    font-size: 12px;
    font-weight: 600;
    padding: 3px 10px;
    border-radius: 12px;
    margin-left: auto;
}
.parent-form-modal .checkbox-wrapper .checkbox-status.active {
    background: #e8f5e9;
    color: #2e7d32;
}
.parent-form-modal .checkbox-wrapper .checkbox-status.inactive {
    background: #f5f5f5;
    color: #999;
}
.parent-form-modal .designation-section {
    margin-top: 15px;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 8px;
    border-left: 3px solid #667eea;
    display: none;
}
.parent-form-modal .designation-section.show {
    display: block;
    animation: slideDown 0.3s ease;
}
@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
.parent-form-modal .form-actions {
    margin-top: 30px;
    padding-top: 20px;
    border-top: 2px solid #e8f5e9;
    display: flex;
    gap: 12px;
    justify-content: flex-end;
}
.parent-form-modal .btn {
    padding: 12px 24px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    border: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}
.parent-form-modal .btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: #fff;
}
.parent-form-modal .btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}
.parent-form-modal .btn-default {
    background: #f5f5f5;
    color: #666;
}
.parent-form-modal .btn-default:hover {
    background: #e0e0e0;
}
</style>

<div class="parent-form-modal">
    <!-- Form Header -->
    <div class="form-header">
        <div class="header-icon">
            <i class="fa fa-user-plus"></i>
        </div>
        <h3><?php echo get_phrase('add_parent'); ?></h3>
        <p>Fill in the parent/guardian information below</p>
    </div>

    <?php echo form_open(site_url('admin/parent/create/'), array('id' => 'parent_add_form')); ?>
    
    <!-- Personal Information Section -->
    <div class="form-section">
        <div class="section-title">
            <i class="fa fa-user"></i> Personal Information
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Full Name <span class="required">*</span></label>
                <input type="text" class="form-control" name="name" id="p_name" placeholder="Enter parent's full name" required autofocus>
            </div>
            <div class="form-group">
                <label>Guardian Gender</label>
                <select name="guardian_gender" class="form-control" id="guardian_gender">
                    <option value="Male">Male</option>
                    <option value="Female">Female</option>
                </select>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" class="form-control" name="email" placeholder="Enter email address">
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" class="form-control" name="password" placeholder="Create a password">
            </div>
        </div>
    </div>

    <!-- Contact Information Section -->
    <div class="form-section">
        <div class="section-title">
            <i class="fa fa-phone"></i> Contact Information
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Phone Number</label>
                <input type="tel" class="form-control" name="phone[]" placeholder="Enter phone number">
            </div>
            <div class="form-group">
                <label>Profession</label>
                <input type="text" class="form-control" name="profession" placeholder="Enter profession">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group" style="grid-column: span 2;">
                <label>Address</label>
                <input type="text" class="form-control" name="address" placeholder="Enter full address">
            </div>
        </div>
    </div>

    <!-- PTA Information Section -->
    <div class="form-section">
        <div class="section-title">
            <i class="fa fa-users"></i> PTA Information
        </div>
        <div class="form-group">
            <label class="checkbox-wrapper" for="executive">
                <input type="checkbox" name="executive" id="executive" value="0" onchange="toggleExecutive()">
                <span class="checkbox-label">PTA Executive Member</span>
                <span class="checkbox-status inactive" id="executive_status">NO</span>
            </label>
        </div>
        <div class="designation-section" id="designation_section">
            <div class="form-group">
                <label>Designation <span class="required">*</span></label>
                <input type="text" class="form-control" name="designation" id="designation" placeholder="Enter PTA designation (e.g., Chairman, Secretary)">
            </div>
        </div>
    </div>

    <!-- Form Actions -->
    <div class="form-actions">
        <button type="button" class="btn btn-default" onclick="$('.close').click();">
            <i class="fa fa-times"></i> Cancel
        </button>
        <button type="submit" class="btn btn-primary">
            <i class="fa fa-plus"></i> Add Parent
        </button>
    </div>

    <?php echo form_close(); ?>
</div>

<script type="text/javascript">
function toggleExecutive() {
    var checkbox = $('#executive');
    var isChecked = checkbox.is(':checked');
    var statusSpan = $('#executive_status');
    var designationSection = $('#designation_section');
    var designationInput = $('#designation');
    
    if (isChecked) {
        checkbox.val(1);
        statusSpan.text('YES').removeClass('inactive').addClass('active');
        designationSection.addClass('show');
        designationInput.attr('required', 'required');
    } else {
        checkbox.val(0);
        statusSpan.text('NO').removeClass('active').addClass('inactive');
        designationSection.removeClass('show');
        designationInput.removeAttr('required');
    }
}

// Initialize on load
$(function() {
    toggleExecutive();
});

// Form submission
$('#parent_add_form').submit(function(event) {
    event.preventDefault();

    showAjaxModal_alert('<center><div style="font-size: 20px; font-weight: bolder; margin-top: 0px;">Processing Data...<br><i class="fa fa-3x fa-spinner fa-pulse"></i></div></center>', 'Loading');

    let ajax_url = $(this).attr('action');
    $.ajax({
        url: ajax_url,
        type: 'POST',
        dataType: 'json',
        data: new FormData(this),
        cache: false,
        contentType: false,
        processData: false
    })
    .done(function(response) {
        if (response.status === 'success') {
            showAjaxModal_alert('<div style="text-align: center; padding: 20px;"><i class="fa fa-check-circle" style="font-size: 48px; color: #4caf50;"></i><h4 style="margin-top: 15px;">Parent Added Successfully!</h4></div>', 'Success');
            setTimeout(() => {
                $('.close').click();
                navigation('<?php echo site_url('admin/parent/'); ?>');
            }, 2000);
        } else if (response.status === 'error') {
            if (response.message === 'form-error') {
                showAjaxModal_alert('Some of your information are not correct! Please check and try again.', 'Error');
            } else if (response.message === 'email-invalid') {
                showAjaxModal_alert('Email is not valid!', 'Error');
            } else if (response.message === 'email-not-available') {
                showAjaxModal_alert('This email already exists in the system!', 'Error');
            } else {
                showAjaxModal_alert('An error occurred: ' + (response.message || 'Unknown error'), 'Error');
            }
        } else {
            showAjaxModal_alert('An error occurred. Please try again.', 'Error');
        }
    })
    .fail(function(xhr, status, error) {
        showAjaxModal_alert('Request failed: ' + error, 'Error');
    });
});
</script>
