<style>
.profile-container { max-width: 1200px; margin: 0 auto; }
.profile-card { background: white; border-radius: 16px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); overflow: hidden; margin-bottom: 24px; }
.profile-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 32px; text-align: center; color: white; position: relative; }
.profile-header::before { content: ''; position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="20" cy="20" r="3" fill="rgba(255,255,255,0.1)"/><circle cx="80" cy="40" r="2" fill="rgba(255,255,255,0.1)"/><circle cx="50" cy="80" r="2.5" fill="rgba(255,255,255,0.1)"/></svg>'); opacity: 0.3; }
.profile-avatar { width: 120px; height: 120px; border-radius: 50%; border: 4px solid white; margin: 0 auto 16px; position: relative; z-index: 1; object-fit: cover; box-shadow: 0 4px 12px rgba(0,0,0,0.15); }
.profile-name { font-size: 24px; font-weight: 700; margin-bottom: 4px; position: relative; z-index: 1; }
.profile-role { font-size: 14px; opacity: 0.9; position: relative; z-index: 1; }
.profile-body { padding: 32px; }
.section-title { font-size: 18px; font-weight: 700; color: #1a202c; margin-bottom: 24px; display: flex; align-items: center; gap: 10px; }
.section-title i { color: #667eea; font-size: 20px; }
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 24px; }
.form-row-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 24px; margin-bottom: 24px; }
.form-field { display: flex; flex-direction: column; }
.field-label { font-size: 14px; font-weight: 600; color: #374151; margin-bottom: 8px; }
.field-input { padding: 12px 16px; border: 2px solid #e5e7eb; border-radius: 10px; font-size: 15px; transition: all 0.3s; background: #f9fafb; }
.field-input:focus { outline: none; border-color: #667eea; background: white; box-shadow: 0 0 0 4px rgba(102,126,234,0.1); }
.password-field .field-input { padding-right: 45px; }
.photo-upload { display: flex; align-items: center; gap: 20px; padding: 20px; background: #f9fafb; border-radius: 10px; border: 2px dashed #e5e7eb; }
.photo-preview { width: 100px; height: 100px; border-radius: 50%; object-fit: cover; border: 3px solid #e5e7eb; }
.upload-controls { flex: 1; }
.upload-label { display: inline-block; padding: 10px 20px; background: #667eea; color: white; border-radius: 8px; cursor: pointer; font-size: 14px; font-weight: 600; transition: all 0.3s; }
.upload-label:hover { background: #5568d3; transform: translateY(-1px); }
.upload-label input { display: none; }
.btn-update { padding: 12px 32px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; border-radius: 10px; font-size: 15px; font-weight: 600; cursor: pointer; transition: all 0.3s; }
.btn-update:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(102,126,234,0.3); }
.password-field { position: relative; }
.password-toggle { position: absolute; right: 16px; top: 12px; cursor: pointer; color: #9ca3af; font-size: 16px; }
.password-toggle:hover { color: #667eea; }
@media (max-width: 768px) {
.form-row, .form-row-3 { grid-template-columns: 1fr; gap: 16px; }
.profile-body { padding: 20px; }
.photo-upload { flex-direction: column; text-align: center; }
}
</style>

<div class="profile-container">
    <?php foreach($edit_data as $row): ?>
    
    <div class="profile-card">
        <div class="profile-header">
            <img src="<?php echo $this->crud_model->get_image_url('student', $row['student_id']);?>" alt="Profile" class="profile-avatar">
            <div class="profile-name"><?php echo $row['name'];?></div>
            <div class="profile-role">Student</div>
        </div>
        
        <div class="profile-body">
            <div class="section-title">
                <i class="fas fa-user-circle"></i>
                <?php echo get_phrase('personal_information');?>
            </div>
            
            <?php echo form_open_multipart(site_url('student/manage_profile/update_profile_info'), array('id' => 'profile_form'));?>
                <div class="form-row">
                    <div class="form-field">
                        <label class="field-label"><?php echo get_phrase('name');?></label>
                        <input type="text" class="field-input" name="name" value="<?php echo $row['name'];?>" required>
                    </div>
                    <div class="form-field">
                        <label class="field-label"><?php echo get_phrase('email');?></label>
                        <input type="email" class="field-input" name="email" value="<?php echo $row['email'];?>" required>
                    </div>
                </div>
                
                <div class="form-field" style="margin-bottom: 24px;">
                    <label class="field-label"><?php echo get_phrase('photo');?></label>
                    <div class="photo-upload">
                        <img src="<?php echo $this->crud_model->get_image_url('student', $row['student_id']);?>" alt="Preview" class="photo-preview" id="photo_preview">
                        <div class="upload-controls">
                            <label class="upload-label">
                                <i class="fas fa-camera"></i> <?php echo get_phrase('choose_photo');?>
                                <input type="file" name="userfile" accept="image/*" onchange="previewPhoto(this)">
                            </label>
                            <p style="margin: 8px 0 0 0; font-size: 13px; color: #6b7280;">JPG, PNG or GIF (Max 2MB)</p>
                        </div>
                    </div>
                </div>
                
                <button type="submit" class="btn-update">
                    <i class="fas fa-save"></i> <?php echo get_phrase('update_profile');?>
                </button>
            </form>
        </div>
    </div>
    
    <div class="profile-card">
        <div class="profile-body">
            <div class="section-title">
                <i class="fas fa-lock"></i>
                <?php echo get_phrase('change_password');?>
            </div>
            
            <?php echo form_open(site_url('student/manage_profile/change_password'), array('id' => 'password_form'));?>
                <div class="form-row-3">
                    <div class="form-field">
                        <label class="field-label"><?php echo get_phrase('current_password');?></label>
                        <div class="password-field">
                            <input type="password" class="field-input" name="password" id="current_password" required>
                            <i class="fas fa-eye password-toggle" onclick="togglePassword('current_password')"></i>
                        </div>
                    </div>
                    <div class="form-field">
                        <label class="field-label"><?php echo get_phrase('new_password');?></label>
                        <div class="password-field">
                            <input type="password" class="field-input" name="new_password" id="new_password" required>
                            <i class="fas fa-eye password-toggle" onclick="togglePassword('new_password')"></i>
                        </div>
                    </div>
                    <div class="form-field">
                        <label class="field-label"><?php echo get_phrase('confirm_new_password');?></label>
                        <div class="password-field">
                            <input type="password" class="field-input" name="confirm_new_password" id="confirm_password" required>
                            <i class="fas fa-eye password-toggle" onclick="togglePassword('confirm_password')"></i>
                        </div>
                    </div>
                </div>
                
                <button type="submit" class="btn-update">
                    <i class="fas fa-key"></i> <?php echo get_phrase('update_password');?>
                </button>
            </form>
        </div>
    </div>
    
    <?php endforeach; ?>
</div>

<script>
function previewPhoto(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('photo_preview').src = e.target.result;
        }
        reader.readAsDataURL(input.files[0]);
    }
}

function togglePassword(fieldId) {
    var field = document.getElementById(fieldId);
    var icon = field.nextElementSibling;
    if (field.type === 'password') {
        field.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        field.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}
</script>
