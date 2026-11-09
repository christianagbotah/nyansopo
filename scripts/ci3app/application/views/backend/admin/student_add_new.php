<?php 
$parent_id = ''; 
$class_id = ''; 
$alert_type = '';
$alert_content = '';
$display_mode = $display = 'none';
?>

<style>
.admission-container { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; padding: 2rem 0; }
.admission-card { background: white; border-radius: 20px; box-shadow: 0 20px 60px rgba(0,0,0,0.3); max-width: 1400px; margin: 0 auto; }
.section-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 1.5rem; border-radius: 12px; margin-bottom: 2rem; font-weight: 700; font-size: 1.1rem; }
.form-label { font-weight: 600; color: #374151; margin-bottom: 0.5rem; display: block; font-size: 0.95rem; }
.form-input { width: 100%; padding: 0.75rem 1rem; border: 2px solid #e5e7eb; border-radius: 10px; font-size: 1rem; transition: all 0.3s; }
.form-input:focus { outline: none; border-color: #667eea; box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1); }
.form-group { margin-bottom: 1.5rem; }
.btn-submit { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 1rem 3rem; border-radius: 12px; font-weight: 700; font-size: 1.1rem; border: none; cursor: pointer; transition: all 0.3s; width: 100%; }
.btn-submit:hover { transform: translateY(-2px); box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4); }
.grid-2 { display: grid; grid-template-columns: repeat(2, 1fr); gap: 2rem; }
.grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.5rem; }
@media (max-width: 768px) { .grid-2, .grid-3 { grid-template-columns: 1fr; } }
.required::after { content: " *"; color: #ef4444; }
.info-box { background: #dbeafe; border-left: 4px solid #3b82f6; padding: 1rem; border-radius: 8px; margin-bottom: 2rem; }
</style>

<div class="admission-container">
    <div class="admission-card p-8">
        <div class="text-center mb-6">
            <h1 class="text-4xl font-bold text-gray-900 mb-2">Student Admission Form</h1>
            <p class="text-gray-600">Complete all required fields to enroll a new student</p>
        </div>

        <div class="info-box">
            <p class="text-sm text-blue-900 mb-2"><strong>Important Notes:</strong></p>
            <ul class="text-sm text-blue-800 list-disc ml-5">
                <li>Admitting new students will automatically create an enrollment to the selected class</li>
                <li>Fields marked with <span class="text-red-600">*</span> are required</li>
                <li>Ensure all information is accurate before submission</li>
            </ul>
        </div>

        <?php if(validation_errors()): ?>
        <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-4 rounded">
            <p class="text-red-700"><?php echo validation_errors(); ?></p>
        </div>
        <?php endif; ?>

        <?php echo form_open(site_url('admin/student/create/'), array('class' => 'admission-form', 'enctype' => 'multipart/form-data', 'id' => 'student_add_form')); ?>

        <!-- SECTION 1: STUDENT PERSONAL INFORMATION -->
        <div class="section-header">
            <i class="fa fa-user"></i> Student Personal Information
        </div>

        <div class="grid-3">
            <div class="form-group">
                <label class="form-label required">First Name</label>
                <input type="text" name="first_name" class="form-input" required value="<?php echo set_value('first_name'); ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Middle Name</label>
                <input type="text" name="middle_name" class="form-input" value="<?php echo set_value('middle_name'); ?>">
            </div>
            <div class="form-group">
                <label class="form-label required">Last Name</label>
                <input type="text" name="last_name" class="form-input" required value="<?php echo set_value('last_name'); ?>">
            </div>
        </div>

        <div class="grid-2">
            <div class="form-group">
                <label class="form-label required">Date of Birth</label>
                <input type="date" name="birthday" class="form-input" required value="<?php echo set_value('birthday'); ?>">
            </div>
            <div class="form-group">
                <label class="form-label required">Gender</label>
                <select name="sex" class="form-input" required>
                    <option value="">Select Gender</option>
                    <option value="Male">Male</option>
                    <option value="Female">Female</option>
                </select>
            </div>
        </div>

        <div class="grid-2">
            <div class="form-group">
                <label class="form-label">Blood Group</label>
                <select name="blood_group" class="form-input">
                    <option value="">Select Blood Group</option>
                    <option value="A+">A+</option>
                    <option value="A-">A-</option>
                    <option value="B+">B+</option>
                    <option value="B-">B-</option>
                    <option value="O+">O+</option>
                    <option value="O-">O-</option>
                    <option value="AB+">AB+</option>
                    <option value="AB-">AB-</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Religion</label>
                <select name="religion" class="form-input">
                    <option value="">Select Religion</option>
                    <option value="Christianity">Christianity</option>
                    <option value="Islam">Islam</option>
                    <option value="Traditional">Traditional</option>
                    <option value="Others">Others</option>
                </select>
            </div>
        </div>

        <div class="grid-2">
            <div class="form-group">
                <label class="form-label">Nationality</label>
                <input type="text" name="nationality" class="form-input" value="<?php echo set_value('nationality', 'Ghanaian'); ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Ghana Card ID</label>
                <input type="text" name="ghana_card_id" class="form-input" value="<?php echo set_value('ghana_card_id'); ?>">
            </div>
        </div>

        <div class="form-group">
            <label class="form-label required">Residential Address</label>
            <textarea name="address" class="form-input" rows="3" required><?php echo set_value('address'); ?></textarea>
        </div>

        <div class="grid-2">
            <div class="form-group">
                <label class="form-label">Student Phone</label>
                <input type="tel" name="student_phone" class="form-input" value="<?php echo set_value('student_phone'); ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Student Email</label>
                <input type="email" name="email" class="form-input" value="<?php echo set_value('email'); ?>">
            </div>
        </div>

        <!-- SECTION 2: ACADEMIC INFORMATION -->
        <div class="section-header mt-6">
            <i class="fa fa-graduation-cap"></i> Academic Information
        </div>

        <div class="grid-2">
            <div class="form-group">
                <label class="form-label required">Class</label>
                <select name="class_id" class="form-input" required>
                    <option value="">Select Class</option>
                    <?php
                    $classes = $this->db->get('class')->result_array();
                    foreach($classes as $class):
                    ?>
                    <option value="<?php echo $class['class_id']; ?>"><?php echo $class['name'].' '.$class['name_numeric']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label required">Residence Type</label>
                <select name="residence_type" class="form-input" required>
                    <option value="">Select Type</option>
                    <option value="Day">Day Student</option>
                    <option value="Boarding">Boarding Student</option>
                </select>
            </div>
        </div>

        <div class="grid-2">
            <div class="form-group">
                <label class="form-label">Previous School</label>
                <input type="text" name="previous_school" class="form-input" value="<?php echo set_value('previous_school'); ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Admission Date</label>
                <input type="date" name="admission_date" class="form-input" value="<?php echo set_value('admission_date', date('Y-m-d')); ?>">
            </div>
        </div>

        <!-- SECTION 3: PARENT/GUARDIAN INFORMATION -->
        <div class="section-header mt-6">
            <i class="fa fa-users"></i> Parent / Guardian Information
        </div>

        <div class="form-group">
            <label class="form-label required">Select Parent/Guardian</label>
            <select name="parent_id" id="parent_id" class="form-input select2" required>
                <option value="">Select Guardian or Register New</option>
                <option value="new">Register New Guardian</option>
                <?php
                $parents = $this->db->get('parent')->result_array();
                foreach($parents as $row):
                ?>
                <option value="<?php echo $row['parent_id']; ?>"><?php echo $row['name']; ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="grid-2">
            <div class="form-group">
                <label class="form-label">Guardian Phone</label>
                <input type="tel" name="phone" class="form-input" value="<?php echo set_value('phone'); ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Guardian Email</label>
                <input type="email" name="parent_email" class="form-input" value="<?php echo set_value('parent_email'); ?>">
            </div>
        </div>

        <div class="grid-2">
            <div class="form-group">
                <label class="form-label">Father's Name</label>
                <input type="text" name="father_name" class="form-input" value="<?php echo set_value('father_name'); ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Father's Phone</label>
                <input type="tel" name="father_phone" class="form-input" value="<?php echo set_value('father_phone'); ?>">
            </div>
        </div>

        <div class="grid-2">
            <div class="form-group">
                <label class="form-label">Father's Occupation</label>
                <input type="text" name="father_occupation" class="form-input" value="<?php echo set_value('father_occupation'); ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Mother's Name</label>
                <input type="text" name="mother_name" class="form-input" value="<?php echo set_value('mother_name'); ?>">
            </div>
        </div>

        <div class="grid-2">
            <div class="form-group">
                <label class="form-label">Mother's Phone</label>
                <input type="tel" name="mother_phone" class="form-input" value="<?php echo set_value('mother_phone'); ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Mother's Occupation</label>
                <input type="text" name="mother_occupation" class="form-input" value="<?php echo set_value('mother_occupation'); ?>">
            </div>
        </div>

        <!-- SECTION 4: MEDICAL & SPECIAL NEEDS -->
        <div class="section-header mt-6">
            <i class="fa fa-heartbeat"></i> Medical & Special Needs
        </div>

        <div class="grid-2">
            <div class="form-group">
                <label class="form-label">Allergies</label>
                <textarea name="allergies" class="form-input" rows="2"><?php echo set_value('allergies'); ?></textarea>
            </div>
            <div class="form-group">
                <label class="form-label">Medical Conditions</label>
                <textarea name="medical_conditions" class="form-input" rows="2"><?php echo set_value('medical_conditions'); ?></textarea>
            </div>
        </div>

        <div class="grid-2">
            <div class="form-group">
                <label class="form-label">Special Diet</label>
                <select name="special_diet" class="form-input">
                    <option value="0">No</option>
                    <option value="1">Yes</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Emergency Contact</label>
                <input type="tel" name="emergency_contact" class="form-input" value="<?php echo set_value('emergency_contact'); ?>">
            </div>
        </div>

        <!-- SECTION 5: PHOTO UPLOAD -->
        <div class="section-header mt-6">
            <i class="fa fa-camera"></i> Student Photo
        </div>

        <div class="form-group">
            <label class="form-label">Upload Photo</label>
            <input type="file" name="userfile" class="form-input" accept="image/*">
            <p class="text-sm text-gray-500 mt-2">Accepted formats: JPG, PNG (Max 2MB)</p>
        </div>

        <!-- SUBMIT BUTTON -->
        <div class="mt-8">
            <button type="submit" class="btn-submit">
                <i class="fa fa-check-circle"></i> Submit Admission Form
            </button>
        </div>

        <?php echo form_close(); ?>
    </div>
</div>

<script>
$(document).ready(function() {
    $('.select2').select2();
    
    $('#student_add_form').submit(function(e) {
        e.preventDefault();
        
        var formData = new FormData(this);
        
        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            beforeSend: function() {
                $('.btn-submit').html('<i class="fa fa-spinner fa-spin"></i> Processing...').prop('disabled', true);
            },
            success: function(response) {
                if(response == 'done' || response.trim() == 'done') {
                    alert('Student admitted successfully!');
                    window.location.reload();
                } else {
                    alert('Error: ' + response);
                    $('.btn-submit').html('<i class="fa fa-check-circle"></i> Submit Admission Form').prop('disabled', false);
                }
            },
            error: function() {
                alert('An error occurred. Please try again.');
                $('.btn-submit').html('<i class="fa fa-check-circle"></i> Submit Admission Form').prop('disabled', false);
            }
        });
    });
});
</script>
