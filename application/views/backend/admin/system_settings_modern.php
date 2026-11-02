<style>
.settings-container { max-width: 1400px; margin: 0 auto; padding: 20px; }
.settings-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; border-radius: 12px; margin-bottom: 30px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
.settings-header h1 { margin: 0; font-size: 28px; font-weight: 600; color: white !important; }
.settings-header p { margin: 10px 0 0; opacity: 0.9; }
.settings-tabs { display: flex; gap: 10px; margin-bottom: 30px; flex-wrap: wrap; border-bottom: 2px solid #e5e7eb; position: sticky; top: 0; background: white; z-index: 100; padding: 10px 0; }
.settings-tab { padding: 12px 24px; background: transparent; border: none; cursor: pointer; font-size: 15px; font-weight: 500; color: #6b7280; border-bottom: 3px solid transparent; transition: all 0.3s; }
.settings-tab:hover { color: #667eea; }
.settings-tab.active { color: #667eea; border-bottom-color: #667eea; }
.settings-section { display: none; }
.settings-section.active { display: block; }
.settings-card { background: white; border-radius: 12px; padding: 25px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
.settings-card-title { font-size: 18px; font-weight: 600; color: #1f2937; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #f3f4f6; }
.form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; }
.form-group { margin-bottom: 20px; }
.form-group label { display: block; font-size: 14px; font-weight: 500; color: #374151; margin-bottom: 8px; }
.form-group input, .form-group select, .form-group textarea { width: 100%; padding: 12px 14px; min-height: 44px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px; transition: all 0.3s; }
.form-group input:focus, .form-group select:focus, .form-group textarea:focus { outline: none; border-color: #667eea; box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1); }
.upload-card { background: #f9fafb; border: 2px dashed #d1d5db; border-radius: 12px; padding: 30px; text-align: center; transition: all 0.3s; }
.upload-card:hover { border-color: #667eea; background: #f3f4f6; }
.upload-preview { width: 150px; height: 150px; margin: 0 auto 15px; border-radius: 8px; overflow: hidden; border: 2px solid #e5e7eb; }
.upload-preview img { width: 100%; height: 100%; object-fit: contain; }
.btn { padding: 12px 24px; border: none; border-radius: 8px; font-size: 14px; font-weight: 500; cursor: pointer; transition: all 0.3s; }
.btn-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
.btn-primary:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4); }
.btn-success { background: #10b981; color: white; }
.btn-success:hover { background: #059669; }

/* Modern Toggle Switch */
.toggle-container { display: flex; align-items: center; gap: 15px; padding: 20px; background: #f9fafb; border-radius: 12px; border: 2px solid #e5e7eb; transition: all 0.3s; }
.toggle-container:hover { border-color: #667eea; background: #f3f4f6; }
.toggle-switch { position: relative; display: inline-block; width: 60px; height: 34px; }
.toggle-switch input { opacity: 0; width: 0; height: 0; }
.toggle-slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #cbd5e1; transition: .4s; border-radius: 34px; }
.toggle-slider:before { position: absolute; content: ""; height: 26px; width: 26px; left: 4px; bottom: 4px; background-color: white; transition: .4s; border-radius: 50%; box-shadow: 0 2px 4px rgba(0,0,0,0.2); }
input:checked + .toggle-slider { background: linear-gradient(135deg, #10b981 0%, #059669 100%); }
input:checked + .toggle-slider:before { transform: translateX(26px); }
.toggle-label { flex: 1; }
.toggle-label strong { display: block; font-size: 16px; color: #1f2937; margin-bottom: 5px; }
.toggle-label small { display: block; color: #6b7280; line-height: 1.5; }
.toggle-status { padding: 6px 12px; border-radius: 6px; font-size: 13px; font-weight: 600; min-width: 80px; text-align: center; }
.toggle-status.enabled { background: #d1fae5; color: #065f46; }
.toggle-status.disabled { background: #fee2e2; color: #991b1b; }


@media (max-width: 768px) {
  .settings-header { padding: 20px; }
  .settings-header h1 { font-size: 22px; }
  .settings-tabs { overflow-x: auto; flex-wrap: nowrap; }
  .settings-tab { white-space: nowrap; }
  .form-grid { grid-template-columns: 1fr; }
  .settings-card { padding: 15px; }
  .save-btn-fixed { bottom: 15px !important; right: 15px !important; }
  .save-btn-fixed button { min-width: 150px !important; font-size: 13px !important; }
}
</style>

<script>
// Define switchTab function immediately to prevent "not defined" errors
// Attach to window object to ensure global accessibility
window.switchTab = function(tabName) {
  console.log('Switching to tab:', tabName);
  
  // Remove active class from all tabs and sections
  document.querySelectorAll('.settings-tab').forEach(tab => tab.classList.remove('active'));
  document.querySelectorAll('.settings-section').forEach(section => section.classList.remove('active'));
  
  // Find and activate the correct tab button
  const tabButtons = document.querySelectorAll('.settings-tab');
  tabButtons.forEach(tab => {
    if (tab.textContent.trim().toLowerCase() === tabName.toLowerCase() || 
        tab.getAttribute('onclick').includes(tabName)) {
      tab.classList.add('active');
    }
  });
  
  // Activate the section
  const section = document.getElementById(tabName);
  if (section) {
    section.classList.add('active');
    console.log('Tab switched successfully:', tabName);
  } else {
    console.error('Section not found:', tabName);
  }
};

// Also keep regular function declaration for backwards compatibility
function switchTab(tabName) {
  window.switchTab(tabName);
}

console.log('switchTab function loaded successfully');
</script>

<div class="settings-container">
  <div class="settings-header">
    <h1><?php echo get_phrase('system_settings'); ?></h1>
    <p>Manage your school's system configuration and preferences</p>
  </div>

  <div class="settings-tabs">
    <button class="settings-tab active" onclick="switchTab('general')">General</button>
    <button class="settings-tab" onclick="switchTab('branding')">Branding</button>
    <button class="settings-tab" onclick="switchTab('academic')">Academic</button>
    <button class="settings-tab" onclick="switchTab('payment')">Payment</button>
    <button class="settings-tab" onclick="switchTab('security')">Security</button>
    <button class="settings-tab" onclick="switchTab('advanced')">Advanced</button>
  </div>

  <!-- Branding Section -->
  <div id="branding" class="settings-section">
    <div class="form-grid">
      <!-- School Logo Upload -->
      <div class="settings-card">
        <div class="settings-card-title">School Logo</div>
        <div class="upload-card">
          <div class="upload-preview" id="logo_preview">
            <img src="<?php echo base_url(); ?>uploads/school_logo.png?v=<?php echo time(); ?>" alt="Logo">
          </div>
          <input type="file" name="school_logo" id="school_logo_input" accept="image/*" class="form-control">
          <button type="button" class="btn btn-primary" style="margin-top: 15px;" onclick="uploadFile('school_logo', 'logo')">
            <i class="fa fa-upload"></i> Upload Logo
          </button>
          <div id="logo_status" style="margin-top: 10px; font-size: 13px;"></div>
        </div>
      </div>

      <!-- Head Teacher Signature Upload -->
      <div class="settings-card">
        <div class="settings-card-title">Head Teacher Signature</div>
        <div class="upload-card">
          <div class="upload-preview" id="signature_preview" style="height: 100px;">
            <img src="<?php echo base_url(); ?>uploads/signature/admin/head_teacher.png?v=<?php echo time(); ?>" alt="Signature">
          </div>
          <input type="file" name="head_teacher_signature" id="signature_input" accept="image/*" class="form-control">
          <button type="button" class="btn btn-success" style="margin-top: 15px;" onclick="uploadFile('signature', 'signature')">
            <i class="fa fa-upload"></i> Upload Signature
          </button>
          <div id="signature_status" style="margin-top: 10px; font-size: 13px;"></div>
        </div>
      </div>

      <!-- SSNIT Logo Upload -->
      <div class="settings-card">
        <div class="settings-card-title">SSNIT Logo</div>
        <div class="upload-card">
          <div class="upload-preview" id="ssnit_preview">
            <img src="<?php echo base_url(); ?>uploads/ssnit_logo.png?v=<?php echo time(); ?>" alt="SSNIT Logo">
          </div>
          <input type="file" name="ssnit_logo" id="ssnit_logo_input" accept="image/*" class="form-control">
          <button type="button" class="btn btn-primary" style="margin-top: 15px;" onclick="uploadFile('ssnit_logo', 'ssnit')">
            <i class="fa fa-upload"></i> Upload SSNIT Logo
          </button>
          <div id="ssnit_status" style="margin-top: 10px; font-size: 13px;"></div>
        </div>
      </div>
    </div>
  </div>

  <!-- General Section -->
  <?php echo form_open(site_url('admin/system_settings/do_update'), array('id' => 'system_settings_form')); ?>
  <div id="general" class="settings-section active">
    <div class="settings-card">
      <div class="settings-card-title">School Information</div>
      <div class="form-grid">
        <div class="form-group">
          <label>System Name *</label>
          <input type="text" name="system_name" class="form-control" value="<?php echo $this->db->get_where('settings', array('type' => 'system_name'))->row()->description; ?>" required>
        </div>
        <div class="form-group">
          <label>School's Slogan</label>
          <input type="text" name="system_title" class="form-control" value="<?php echo $this->db->get_where('settings', array('type' => 'system_title'))->row()->description; ?>">
        </div>
        <div class="form-group">
          <label>Location *</label>
          <input type="text" name="location" class="form-control" value="<?php echo $this->db->get_where('settings', array('type' => 'location'))->row()->description; ?>" required>
        </div>
        <div class="form-group">
          <label>Address *</label>
          <textarea name="address" class="form-control" rows="3" style="resize: vertical; min-height: 80px;" required><?php echo $this->db->get_where('settings', array('type' => 'address'))->row()->description; ?></textarea>
        </div>
        <div class="form-group">
          <label>Box Number</label>
          <input type="text" name="box_number" class="form-control" value="<?php echo $this->db->get_where('settings', array('type' => 'box_number'))->row()->description; ?>">
        </div>
        <div class="form-group">
          <label>Digital Address</label>
          <input type="text" name="digital_address" class="form-control" value="<?php echo $this->db->get_where('settings', array('type' => 'digital_address'))->row()->description; ?>">
        </div>
        <div class="form-group">
          <label>Website Address</label>
          <input type="text" name="website_address" class="form-control" value="<?php echo $this->db->get_where('settings', array('type' => 'website_address'))->row()->description; ?>">
        </div>
        <div class="form-group">
          <label>Phone *</label>
          <input type="tel" name="phone[]" class="form-control" value="<?php echo $this->db->get_where('settings', array('type' => 'phone'))->row()->description; ?>" required>
        </div>
        <div class="form-group">
          <label>System Email *</label>
          <input type="email" name="system_email" class="form-control" value="<?php echo $this->db->get_where('settings', array('type' => 'system_email'))->row()->description; ?>" required>
        </div>
        <div class="form-group">
          <label>SSNIT Establishment Number</label>
          <input type="text" name="ssnit_number" class="form-control" value="<?php echo $this->db->get_where('settings', array('type' => 'ssnit_number'))->row()->description; ?>">
        </div>
        <div class="form-group">
          <label>Currency *</label>
          <input type="text" name="currency" class="form-control" value="<?php echo $this->db->get_where('settings', array('type' => 'currency'))->row()->description; ?>" required>
        </div>
        <div class="form-group">
          <label>Language</label>
          <select name="language" class="form-control">
            <?php
            $fields = $this->db->list_fields('language');
            $current_default_language = $this->db->get_where('settings', array('type' => 'language'))->row()->description;
            foreach ($fields as $field) {
              if ($field == 'phrase_id' || $field == 'phrase') continue;
              ?>
              <option value="<?php echo $field; ?>" <?php if ($current_default_language == $field) echo 'selected'; ?>><?php echo ucfirst($field); ?></option>
            <?php } ?>
          </select>
        </div>
      </div>
    </div>
  </div>

  <!-- Academic Section -->
  <div id="academic" class="settings-section">
    <!-- Two-column grid for Academic cards -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(450px, 1fr)); gap: 20px; margin-bottom: 20px;">
      
      <!-- Academic Configuration Card -->
      <div class="settings-card" style="margin-bottom: 0;">
        <div class="settings-card-title"><i class="fa fa-graduation-cap"></i> Academic Configuration</div>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
          <div class="form-group">
            <label>Running Session</label>
            <select name="running_year" class="form-control" disabled>
              <?php
              $running_year = $this->db->get_where('settings', array('type' => 'running_year'))->row()->description;
              $year_display = $running_year;
              ?>
              <option value="<?= $running_year; ?>"><?php echo $year_display; ?></option>
            </select>
          </div>
          <div class="form-group">
            <label>Running Term</label>
            <select name="running_term" class="form-control" disabled>
              <?php $running_term = $this->db->get_where('settings', array('type' => 'running_term'))->row()->description; ?>
              <option value="<?= $running_term; ?>"><?php echo $running_term; ?></option>
            </select>
          </div>
          <div class="form-group">
            <label>Term Ending *</label>
            <?php 
              $term_ending = $this->db->get_where('settings', array('type' => 'term_ending'))->row()->description;
              // Convert MM/DD/YYYY to YYYY-MM-DD for HTML5 date input
              if ($term_ending && strpos($term_ending, '/') !== false) {
                $date_parts = explode('/', $term_ending);
                if (count($date_parts) == 3) {
                  $term_ending = $date_parts[2] . '-' . str_pad($date_parts[0], 2, '0', STR_PAD_LEFT) . '-' . str_pad($date_parts[1], 2, '0', STR_PAD_LEFT);
                }
              }
            ?>
            <input type="date" name="term_ending" class="form-control" value="<?php echo $term_ending; ?>" required>
          </div>
          <div class="form-group">
            <label>Next Term Begins *</label>
            <?php 
              $next_term_begins = $this->db->get_where('settings', array('type' => 'next_term_begins'))->row()->description;
              // Convert MM/DD/YYYY to YYYY-MM-DD for HTML5 date input
              if ($next_term_begins && strpos($next_term_begins, '/') !== false) {
                $date_parts = explode('/', $next_term_begins);
                if (count($date_parts) == 3) {
                  $next_term_begins = $date_parts[2] . '-' . str_pad($date_parts[0], 2, '0', STR_PAD_LEFT) . '-' . str_pad($date_parts[1], 2, '0', STR_PAD_LEFT);
                }
              }
            ?>
            <input type="date" name="next_term_begins" class="form-control" value="<?php echo $next_term_begins; ?>" required>
          </div>
        </div>
      </div>
      
      <!-- School Days Configuration Card -->
      <div class="settings-card" style="margin-bottom: 0;">
        <div class="settings-card-title"><i class="fa fa-calendar"></i> School Days Configuration</div>
      <p style="color: #6b7280; font-size: 14px; margin-bottom: 20px;">
        Set the total number of school days for the current term/semester. This will be stored with the term record for audit trail and used to auto-calculate student attendance on marksheets.
      </p>
      <div class="form-grid">
        <?php
        // Get current running year and term
        $running_year = get_settings('running_year');
        $running_term = get_settings('running_term');
        
        // Fetch days_opened from terms table for current term
        $term_record = $this->db->get_where('terms', array('year' => $running_year, 'term' => $running_term))->row();
        $current_days_opened = $term_record && isset($term_record->days_opened) ? $term_record->days_opened : '';
        ?>
        
        <div class="form-group" style="grid-column: span 2;">
          <label><i class="fa fa-calendar"></i> Days Opened for Current Term (<?php echo 'Year ' . $running_year . ', Term ' . $running_term; ?>) *</label>
          <input type="number" name="days_opened" class="form-control" 
                 value="<?php echo $current_days_opened; ?>" 
                 min="0" max="200" placeholder="e.g., 60" required>
          <small style="color: #9ca3af; display: block; margin-top: 5px;">
            Total school days for the current term. This value is saved with the term record for audit purposes and used to calculate attendance on student marksheets.
          </small>
        </div>
      </div>
      
      <div style="margin-top: 20px; padding: 15px; background: #eff6ff; border-left: 4px solid #3b82f6; border-radius: 8px;">
        <strong style="color: #1e40af; display: block; margin-bottom: 8px;"><i class="fa fa-info-circle"></i> Auto-Attendance Calculation:</strong>
        <p style="color: #1e40af; font-size: 13px; line-height: 1.6; margin: 0;">
          Once set, the system will automatically calculate student attendance on marksheets by:
          <br>• <strong>Days Opened:</strong> Pulled from these settings
          <br>• <strong>Days Present:</strong> Counted from attendance records (Present + Late)
          <br>• <strong>Days Absent:</strong> Automatically calculated (Days Opened - Days Present)
        </p>
      </div>
      </div><!-- Close School Days Configuration Card -->
      
    </div><!-- Close Two-column grid -->
    
    <!-- Grading System Card (Full Width) -->
    <div class="settings-card">
      <div class="settings-card-title">Grading System</div>
      <div class="toggle-container">
        <label class="toggle-switch">
          <?php 
            $waec_grading = $this->db->get_where('settings', array('type' => 'raw_score'))->row(); 
            $is_waec_enabled = ($waec_grading && $waec_grading->description == 'Yes');
          ?>
          <input type="checkbox" name="waec_grading_enabled" value="yes" <?php if ($is_waec_enabled) echo 'checked'; ?> onchange="updateWaecToggleStatus(this)">
          <span class="toggle-slider"></span>
        </label>
        
        <div class="toggle-label">
          <strong><i class="fa fa-graduation-cap"></i> WAEC Standard Grading</strong>
          <small>Use West African Examinations Council (WAEC) grading standards for assessments</small>
        </div>
        
        <div class="toggle-status <?php echo $is_waec_enabled ? 'enabled' : 'disabled'; ?>" id="waecGradingStatus">
          <?php echo $is_waec_enabled ? 'ENABLED' : 'DISABLED'; ?>
        </div>
      </div>

      <div style="margin-top: 20px; padding: 15px; background: #f0f9ff; border-left: 4px solid #0ea5e9; border-radius: 8px;">
        <strong style="color: #0c4a6e; display: block; margin-bottom: 8px;"><i class="fa fa-info-circle"></i> WAEC Grading Scale:</strong>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 10px; color: #0c4a6e; font-size: 13px;">
          <div>A1: 80-100%</div>
          <div>B2: 70-79%</div>
          <div>B3: 65-69%</div>
          <div>C4: 60-64%</div>
          <div>C5: 55-59%</div>
          <div>C6: 50-54%</div>
          <div>D7: 45-49%</div>
          <div>E8: 40-44%</div>
          <div>F9: 0-39%</div>
        </div>
      </div>
    </div>

    <!-- Portfolio Assessment Card (Full Width) -->
    <div class="settings-card">
      <div class="settings-card-title"><i class="fa fa-chart-line"></i> Portfolio Assessment Module</div>
      <div class="toggle-container" style="border-color: #667eea;">
        <label class="toggle-switch">
          <?php 
            $portfolio_check = $this->db->get_where('settings', array('type' => 'portfolio_assessment_enabled'));
            $portfolio_assessment_enabled = ($portfolio_check->num_rows() > 0) ? $portfolio_check->row()->description : '0';
            $is_portfolio_enabled = ($portfolio_assessment_enabled == '1');
          ?>
          <input type="checkbox" name="portfolio_assessment_enabled" value="1" <?php if ($is_portfolio_enabled) echo 'checked'; ?> onchange="updatePortfolioToggleStatus(this)">
          <span class="toggle-slider"></span>
        </label>
        
        <div class="toggle-label">
          <strong><i class="fa fa-chart-line"></i> Enable Portfolio Assessment</strong>
          <small>Cumulative weekly assessment tracking where all marks entered for the term are accumulated and used for class test automatically</small>
        </div>
        
        <div class="toggle-status <?php echo $is_portfolio_enabled ? 'enabled' : 'disabled'; ?>" id="portfolioAssessmentStatus">
          <?php echo $is_portfolio_enabled ? 'ENABLED' : 'DISABLED'; ?>
        </div>
      </div>

      <div style="margin-top: 20px; padding: 15px; background: #f0f4ff; border-left: 4px solid #667eea; border-radius: 8px;">
        <strong style="color: #4338ca; display: block; margin-bottom: 8px;"><i class="fa fa-info-circle"></i> Portfolio Assessment Features:</strong>
        <ul style="color: #4338ca; font-size: 13px; line-height: 1.8; margin: 0; padding-left: 20px;">
          <li><strong>Weekly Mark Entry:</strong> Enter daily assessments for Monday-Friday with strand codes</li>
          <li><strong>Automatic Calculation:</strong> System calculates 20% of cumulative average</li>
          <li><strong>Class Test Integration:</strong> Portfolio marks automatically transfer to class test</li>
          <li><strong>Graphical Reports:</strong> View visual progress reports with Chart.js graphs</li>
        </ul>
      </div>
    </div>

    <!-- Assessment Marks Configuration Card (Full Width) -->
    <div class="settings-card">
      <div class="settings-card-title"><i class="fa fa-sliders-h"></i> Assessment Marks Configuration</div>
      
      <div style="padding: 15px; background: #f0f9ff; border-left: 4px solid #3b82f6; border-radius: 8px; margin-bottom: 20px;">
        <strong style="color: #1e40af; display: block; margin-bottom: 8px;"><i class="fa fa-info-circle"></i> Configure Maximum Marks for Each Assessment Component</strong>
        <p style="color: #1e40af; font-size: 13px; margin: 0;">Set the maximum marks for each assessment type. These values will be used for validation and display in the marks management interface.</p>
      </div>

      <div class="row">
        <div class="col-md-6">
          <div class="form-group">
            <label class="control-label"><i class="fa fa-pencil-alt"></i> Class Test Max Marks</label>
            <?php 
              $test1_max_check = $this->db->get_where('settings', array('type' => 'test1_max_marks'));
              $test1_max = ($test1_max_check->num_rows() > 0) ? $test1_max_check->row()->description : '20';
            ?>
            <input type="number" name="test1_max_marks" value="<?php echo $test1_max; ?>" class="form-control" min="1" max="100" required>
            <small class="text-muted">Default: 20 marks</small>
          </div>
        </div>
        
        <div class="col-md-6">
          <div class="form-group">
            <label class="control-label"><i class="fa fa-users"></i> Group Work Max Marks</label>
            <?php 
              $group_work_max_check = $this->db->get_where('settings', array('type' => 'group_work_max_marks'));
              $group_work_max = ($group_work_max_check->num_rows() > 0) ? $group_work_max_check->row()->description : '10';
            ?>
            <input type="number" name="group_work_max_marks" value="<?php echo $group_work_max; ?>" class="form-control" min="1" max="100" required>
            <small class="text-muted">Default: 10 marks</small>
          </div>
        </div>
      </div>

      <div class="row">
        <div class="col-md-6">
          <div class="form-group">
            <label class="control-label"><i class="fa fa-file-alt"></i> Test 2 Max Marks</label>
            <?php 
              $test2_max_check = $this->db->get_where('settings', array('type' => 'test2_max_marks'));
              $test2_max = ($test2_max_check->num_rows() > 0) ? $test2_max_check->row()->description : '20';
            ?>
            <input type="number" name="test2_max_marks" value="<?php echo $test2_max; ?>" class="form-control" min="1" max="100" required>
            <small class="text-muted">Default: 20 marks (Manual Mode), 10 marks (Portfolio Mode)</small>
          </div>
        </div>
        
        <div class="col-md-6">
          <div class="form-group">
            <label class="control-label"><i class="fa fa-project-diagram"></i> Project Max Marks</label>
            <?php 
              $project_max_check = $this->db->get_where('settings', array('type' => 'project_max_marks'));
              $project_max = ($project_max_check->num_rows() > 0) ? $project_max_check->row()->description : '10';
            ?>
            <input type="number" name="project_max_marks" value="<?php echo $project_max; ?>" class="form-control" min="1" max="100" required>
            <small class="text-muted">Default: 10 marks</small>
          </div>
        </div>
      </div>

      <div style="margin-top: 15px; padding: 15px; background: #fef3c7; border-left: 4px solid #f59e0b; border-radius: 8px;">
        <strong style="color: #92400e; display: block; margin-bottom: 8px;"><i class="fa fa-calculator"></i> Sub Total Calculation</strong>
        <p style="color: #92400e; font-size: 13px; margin: 0;">
          <strong>Sub Total = </strong>Class Test + Group Work + Test 2 + Project
          <br><strong>Current Sub Total Max = </strong>
          <span id="sub_total_display"><?php echo ((int)$test1_max + (int)$group_work_max + (int)$test2_max + (int)$project_max); ?></span> marks
        </p>
      </div>
    </div>

    <!-- Portfolio Past Date Editing Card (Full Width) -->
    <div class="settings-card">
      <div class="settings-card-title"><i class="fa fa-calendar-check"></i> Portfolio Past Date Editing</div>
      <div class="toggle-container" style="border-color: #f59e0b;">
        <label class="toggle-switch">
          <?php 
            $allow_past_edit_check = $this->db->get_where('settings', array('type' => 'allow_portfolio_past_edit'));
            $allow_portfolio_past_edit = ($allow_past_edit_check->num_rows() > 0) ? $allow_past_edit_check->row()->description : '0';
            $is_past_edit_enabled = ($allow_portfolio_past_edit == '1');
          ?>
          <input type="checkbox" name="allow_portfolio_past_edit" value="1" <?php if ($is_past_edit_enabled) echo 'checked'; ?> onchange="updatePastEditToggleStatus(this)">
          <span class="toggle-slider"></span>
        </label>
        
        <div class="toggle-label">
          <strong><i class="fa fa-unlock-alt"></i> Allow Past Date Editing (All Teachers)</strong>
          <small>When enabled, all teachers can edit portfolio assessment marks for past dates. Useful when marks were not entered on time. Use with caution - only enable when needed.</small>
        </div>
        
        <div class="toggle-status <?php echo $is_past_edit_enabled ? 'enabled' : 'disabled'; ?>" id="pastEditStatus">
          <?php echo $is_past_edit_enabled ? 'ENABLED' : 'DISABLED'; ?>
        </div>
      </div>

      <div style="margin-top: 20px; padding: 15px; background: <?php echo $is_past_edit_enabled ? '#fef3c7' : '#f3f4f6'; ?>; border-left: 4px solid <?php echo $is_past_edit_enabled ? '#f59e0b' : '#9ca3af'; ?>; border-radius: 8px;">
        <strong style="color: <?php echo $is_past_edit_enabled ? '#92400e' : '#4b5563'; ?>; display: block; margin-bottom: 8px;">
          <i class="fa fa-<?php echo $is_past_edit_enabled ? 'exclamation-triangle' : 'info-circle'; ?>"></i> 
          <?php echo $is_past_edit_enabled ? 'Warning: Past Date Editing is Currently Enabled' : 'Security: Past Date Editing is Disabled'; ?>
        </strong>
        <ul style="color: <?php echo $is_past_edit_enabled ? '#92400e' : '#4b5563'; ?>; font-size: 13px; line-height: 1.8; margin: 0; padding-left: 20px;">
          <li><strong>When Enabled:</strong> All teachers can edit marks for previous dates (highlighted in yellow)</li>
          <li><strong>When Disabled:</strong> Only today's marks can be edited (past dates are read-only)</li>
          <li><strong>Use Case:</strong> Teacher was absent or forgot to enter marks on the due date</li>
          <li><strong>Best Practice:</strong> Enable only when needed, then disable again after corrections</li>
        </ul>
      </div>
    </div>
  </div>

  <!-- Payment Section -->
  <div id="payment" class="settings-section">
    <div class="settings-card">
      <div class="settings-card-title">Payment Settings</div>
      <div class="form-grid">
        <div class="form-group">
          <label>Half Payment Expected By</label>
          <select name="half_payment_week" class="form-control">
            <?php $half_payment = $this->db->get_where('settings', array('type' => 'half_payment_week'))->row()->description; ?>
            <option value="First Week of the Term" <?php if ($half_payment == 'First Week of the Term') echo 'selected'; ?>>First Week</option>
            <option value="Second Week of the Term" <?php if ($half_payment == 'Second Week of the Term') echo 'selected'; ?>>Second Week</option>
            <option value="Third Week of the Term" <?php if ($half_payment == 'Third Week of the Term') echo 'selected'; ?>>Third Week</option>
            <option value="Forth Week of the Term" <?php if ($half_payment == 'Forth Week of the Term') echo 'selected'; ?>>Fourth Week</option>
          </select>
        </div>
        <div class="form-group">
          <label>Full Payment Expected By *</label>
          <?php 
            $full_payment_date = $this->db->get_where('settings', array('type' => 'full_payment_date'))->row()->description;
            // Convert MM/DD/YYYY to YYYY-MM-DD for HTML5 date input
            if ($full_payment_date && strpos($full_payment_date, '/') !== false) {
              $date_parts = explode('/', $full_payment_date);
              if (count($date_parts) == 3) {
                $full_payment_date = $date_parts[2] . '-' . str_pad($date_parts[0], 2, '0', STR_PAD_LEFT) . '-' . str_pad($date_parts[1], 2, '0', STR_PAD_LEFT);
              }
            }
          ?>
          <input type="date" name="full_payment_date" class="form-control" value="<?php echo $full_payment_date; ?>" required>
        </div>
        <div class="form-group">
          <label>Mobile Money Account Name *</label>
          <input type="text" name="mo_account_name" class="form-control" value="<?php echo $this->db->get_where('settings', array('type' => 'mo_account_name'))->row()->description; ?>" required>
        </div>
        <div class="form-group">
          <label>Mobile Money Account Number</label>
          <input type="tel" name="mo_account_number" class="form-control" value="<?php echo $this->db->get_where('settings', array('type' => 'mo_account_number'))->row()->description; ?>">
        </div>
      </div>
    </div>
  </div>

  <!-- Security Section -->
  <div id="security" class="settings-section">
    <div class="settings-card">
      <div class="settings-card-title"><i class="fa fa-shield"></i> Financial Security</div>
      
      <div class="toggle-container">
        <label class="toggle-switch">
          <?php 
            $auto_lock = $this->db->get_where('settings', array('type' => 'auto_lock_enabled'))->row(); 
            $is_enabled = ($auto_lock && $auto_lock->description == 'yes');
          ?>
          <input type="checkbox" name="auto_lock_enabled" value="yes" <?php if ($is_enabled) echo 'checked'; ?> onchange="updateToggleStatus(this)">
          <span class="toggle-slider"></span>
        </label>
        
        <div class="toggle-label">
          <strong><i class="fa fa-lock"></i> Auto-Lock Invoices & Payments</strong>
          <small>Automatically lock financial records to prevent unauthorized modifications</small>
        </div>
        
        <div class="toggle-status <?php echo $is_enabled ? 'enabled' : 'disabled'; ?>" id="autoLockStatus">
          <?php echo $is_enabled ? 'ENABLED' : 'DISABLED'; ?>
        </div>
      </div>

      <div style="margin-top: 20px; padding: 15px; background: #eff6ff; border-left: 4px solid #3b82f6; border-radius: 8px;">
        <strong style="color: #1e40af; display: block; margin-bottom: 8px;"><i class="fa fa-info-circle"></i> When Auto-Lock is Enabled:</strong>
        <ul style="margin: 0; padding-left: 20px; color: #1e40af;">
          <li>✓ Payments are locked immediately after creation</li>
          <li>✓ Invoices are locked when fully paid</li>
          <li>✓ Locked records require approval to edit</li>
          <li>✓ Maintains financial data integrity</li>
        </ul>
      </div>
    </div>
  </div>

  <!-- Advanced Section -->
  <div id="advanced" class="settings-section">
    <div class="settings-card">
      <div class="settings-card-title">ID & Invoice Configuration</div>
      <div class="form-grid">
        <div class="form-group">
          <label>Staff ID Prefix</label>
          <input type="text" name="teacher_code_prefix" class="form-control" value="<?php echo $this->db->get_where('settings', array('type' => 'teacher_code_prefix'))->row()->description; ?>">
        </div>
        <div class="form-group">
          <label>Staff ID Format *</label>
          <input type="text" name="teacher_code_format" class="form-control" value="<?php echo $this->db->get_where('settings', array('type' => 'teacher_code_format'))->row()->description; ?>" required>
        </div>
        <div class="form-group">
          <label>Student ID Prefix</label>
          <input type="text" name="student_code_prefix" class="form-control" value="<?php echo $this->db->get_where('settings', array('type' => 'student_code_prefix'))->row()->description; ?>">
        </div>
        <div class="form-group">
          <label>Student ID Format *</label>
          <input type="text" name="student_code_format" class="form-control" value="<?php echo $this->db->get_where('settings', array('type' => 'student_code_format'))->row()->description; ?>" required>
        </div>
        <div class="form-group">
          <label>Invoice Number Format (Numeric Only) *</label>
          <input type="number" name="invoice_number_format" class="form-control" value="<?php echo $this->db->get_where('settings', array('type' => 'invoice_number_format'))->row()->description; ?>" required>
        </div>
        <div class="form-group">
          <label>Receipt Style</label>
          <select name="receipt_style" class="form-control">
            <?php $receipt_style = $this->db->get_where('settings', array('type' => 'receipt_style'))->row()->description; ?>
            <option value="style_1" <?php if ($receipt_style == 'style_1') echo 'selected'; ?>>Style One (A5 Standard)</option>
            <option value="style_2" <?php if ($receipt_style == 'style_2') echo 'selected'; ?>>Style Two (A5 Detailed)</option>
            <option value="style_3" <?php if ($receipt_style == 'style_3') echo 'selected'; ?>>Style Three (A5 Professional)</option>
            <option value="style_thermal" <?php if ($receipt_style == 'style_thermal') echo 'selected'; ?>>Thermal Receipt (80mm POS)</option>
          </select>
          <small style="display: block; margin-top: 8px; color: #6b7280;">
            <i class="fa fa-receipt"></i> Select receipt format. Thermal receipts are optimized for 80mm thermal printers.
          </small>
          </select>
        </div>
        <div class="form-group">
          <label>School Has Boarding</label>
          <select name="boarding_system" class="form-control">
            <?php $boarding_system = $this->db->get_where('settings', array('type' => 'boarding_system'))->row()->description; ?>
            <option value="yes" <?php if ($boarding_system == 'yes') echo 'selected'; ?>>Yes</option>
            <option value="no" <?php if ($boarding_system == 'no') echo 'selected'; ?>>No</option>
          </select>
        </div>
        <div class="form-group">
          <label>Purchase Code *</label>
          <input type="text" name="purchase_code" class="form-control" value="<?php echo $this->db->get_where('settings', array('type' => 'purchase_code'))->row()->description; ?>" required>
        </div>
      </div>
    </div>

    <!-- Exam Report Templates -->
    <div class="settings-card">
      <div class="settings-card-title"><i class="fa fa-file-text"></i> Exam Report Templates</div>
      
      <div class="form-grid">
        <div class="form-group">
          <label>Terminal Report Style</label>
          <select name="terminal_report_style" class="form-control">
            <?php $terminal_report_style = $this->db->get_where('settings', array('type' => 'terminal_report_style'))->row()->description; ?>
            <option value="style_1" <?php if ($terminal_report_style == 'style_1') echo 'selected'; ?>>Style One</option>
            <option value="style_2" <?php if ($terminal_report_style == 'style_2') echo 'selected'; ?>>Style Two</option>
            <option value="style_3" <?php if ($terminal_report_style == 'style_3') echo 'selected'; ?>>Style Three</option>
          </select>
          <small style="display: block; margin-top: 8px; color: #6b7280;">
            Select the report card design for general (non-creche) terminal reports.
          </small>
        </div>

        <div class="form-group">
          <label>Creche Exam Report Template *</label>
          <select name="creche_exam_template" class="form-control" required>
            <?php 
              $current_template = $this->db->get_where('settings', array('type' => 'creche_exam_template'))->row();
              $selected_template = $current_template ? $current_template->description : 'template1';
            ?>
            <option value="template1" <?php if ($selected_template === 'template1') echo 'selected'; ?>>
              Template 1 (Booklet - Detailed)
            </option>
            <option value="template2" <?php if ($selected_template === 'template2') echo 'selected'; ?>>
              Template 2 (Single Page - Simple)
            </option>
          </select>
          <small style="display: block; margin-top: 8px; color: #6b7280;">
            Select the report card design for creche terminal reports.
          </small>
        </div>
      </div>
      
      <div style="margin-top: 15px; padding: 15px; background: #f0f9ff; border-left: 4px solid #0ea5e9; border-radius: 8px;">
        <strong style="color: #0c4a6e; display: block; margin-bottom: 8px;">
          <i class="fa fa-info-circle"></i> Template Information:
        </strong>
        <div style="color: #0c4a6e; font-size: 13px; line-height: 1.8;">
          <strong>Terminal Report Styles:</strong> Controls report card design for primary/secondary students.<br>
          <strong>Creche Template 1:</strong> Multi-page booklet format with detailed assessments and comprehensive feedback sections.<br>
          <strong>Creche Template 2:</strong> Single-page simple format with condensed assessment areas and essential student information.<br>
          <em style="display: block; margin-top: 8px;">Changes take effect immediately on next print.</em>
        </div>
      </div>
    </div>

    <!-- Sync Infrastructure Mode -->
    <div class="settings-card">
      <div class="settings-card-title"><i class="fa fa-refresh"></i> Sync Infrastructure</div>
      
      <div class="toggle-container">
        <label class="toggle-switch">
          <input type="checkbox" name="offline_online_mode" 
            <?php 
              $offline_online_mode = $this->db->get_where('settings', array('type' => 'offline_online_mode'))->row(); 
              if ($offline_online_mode && $offline_online_mode->description == '1') echo 'checked';
            ?> 
            onchange="updateOfflineOnlineModeStatus(this)">
          <span class="toggle-slider"></span>
        </label>
        <div class="toggle-label">
          <strong>Offline/Online Mode</strong>
          <small>Enable full offline + online sync capability. When enabled, adds sync columns, tracking, and sync UI to the system. When disabled, runs as standard online-only application without sync overhead.</small>
        </div>
        <span id="offlineOnlineModeStatus" class="toggle-status <?php echo ($offline_online_mode && $offline_online_mode->description == '1') ? 'enabled' : 'disabled'; ?>">
          <?php echo ($offline_online_mode && $offline_online_mode->description == '1') ? 'ENABLED' : 'DISABLED'; ?>
        </span>
      </div>

      <div style="margin-top: 15px; padding: 15px; background: #fef3c7; border-left: 4px solid #f59e0b; border-radius: 8px;">
        <strong style="color: #92400e; display: block; margin-bottom: 8px;"><i class="fa fa-exclamation-triangle"></i> Important Note:</strong>
        <p style="margin: 0; color: #78350f; font-size: 13px; line-height: 1.6;">
          • <strong>Enabled (1)</strong>: Full sync infrastructure active - use for clients needing offline/online capability<br>
          • <strong>Disabled (0)</strong>: Online-only mode - standard web application, no sync overhead<br>
          • This controls whether sync infrastructure exists at all (not about automatic vs manual sync)<br>
          • Changes require page refresh to take full effect
        </p>
      </div>
    </div>
  </div>

  <div class="save-btn-fixed" style="position: fixed; bottom: 30px; right: 30px; z-index: 999;">
    <button type="submit" class="btn btn-primary" style="min-width: 200px; box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);">Save All Settings</button>
  </div>
  <?php echo form_close(); ?>
</div>

<script>
// Define all other functions in global scope
function getTabDisplayName(tabId) {
  const tabNames = {
    'general': 'General',
    'branding': 'Branding',
    'academic': 'Academic',
    'payment': 'Payment',
    'security': 'Security',
    'advanced': 'Advanced'
  };
  return tabNames[tabId] || tabId;
}

function isValidEmail(email) {
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  return emailRegex.test(email);
}

function updateToggleStatus(checkbox) {
  const statusEl = document.getElementById('autoLockStatus');
  if (checkbox.checked) {
    statusEl.textContent = 'ENABLED';
    statusEl.classList.remove('disabled');
    statusEl.classList.add('enabled');
  } else {
    statusEl.textContent = 'DISABLED';
    statusEl.classList.remove('enabled');
    statusEl.classList.add('disabled');
  }
}

function updateWaecToggleStatus(checkbox) {
  const statusEl = document.getElementById('waecGradingStatus');
  const rawNavEl = document.getElementById('raw_nav');
  const rawNavTeacherEl = document.getElementById('raw_nav_teacher');
  
  if (checkbox.checked) {
    statusEl.textContent = 'ENABLED';
    statusEl.classList.remove('disabled');
    statusEl.classList.add('enabled');
    // Show WAEC menu items if they exist
    if (rawNavEl) rawNavEl.style.display = 'block';
    if (rawNavTeacherEl) rawNavTeacherEl.style.display = 'block';
  } else {
    statusEl.textContent = 'DISABLED';
    statusEl.classList.remove('enabled');
    statusEl.classList.add('disabled');
    // Hide WAEC menu items if they exist
    if (rawNavEl) rawNavEl.style.display = 'none';
    if (rawNavTeacherEl) rawNavTeacherEl.style.display = 'none';
  }
}

function updatePortfolioToggleStatus(checkbox) {
  const statusEl = document.getElementById('portfolioAssessmentStatus');
  
  if (checkbox.checked) {
    statusEl.textContent = 'ENABLED';
    statusEl.classList.remove('disabled');
    statusEl.classList.add('enabled');
    // Show a success message
    console.log('Portfolio Assessment enabled');
  } else {
    statusEl.textContent = 'DISABLED';
    statusEl.classList.remove('enabled');
    statusEl.classList.add('disabled');
    console.log('Portfolio Assessment disabled');
  }
}

// Update sub total display when assessment marks change
function updateSubTotalDisplay() {
  const test1 = parseInt(document.querySelector('input[name="test1_max_marks"]').value) || 0;
  const groupWork = parseInt(document.querySelector('input[name="group_work_max_marks"]').value) || 0;
  const test2 = parseInt(document.querySelector('input[name="test2_max_marks"]').value) || 0;
  const project = parseInt(document.querySelector('input[name="project_max_marks"]').value) || 0;
  
  const subTotal = test1 + groupWork + test2 + project;
  document.getElementById('sub_total_display').textContent = subTotal;
}

// Add event listeners when page loads
$(document).ready(function() {
  // Listen for changes on assessment mark inputs
  $('input[name="test1_max_marks"], input[name="group_work_max_marks"], input[name="test2_max_marks"], input[name="project_max_marks"]').on('input change', function() {
    updateSubTotalDisplay();
  });
});

function updatePastEditToggleStatus(checkbox) {
  const statusEl = document.getElementById('pastEditStatus');
  
  // Update toggle status visually
  if (checkbox.checked) {
    statusEl.textContent = 'ENABLED';
    statusEl.classList.remove('disabled');
    statusEl.classList.add('enabled');
  } else {
    statusEl.textContent = 'DISABLED';
    statusEl.classList.remove('enabled');
    statusEl.classList.add('disabled');
  }
  
  // Save to database via AJAX
  $.ajax({
    url: '<?= site_url("admin/update_system_settings") ?>',
    type: 'POST',
    data: {
      type: 'allow_portfolio_past_edit',
      description: checkbox.checked ? '1' : '0'
    },
    success: function(response) {
      if(checkbox.checked) {
        toastr.success('Past date editing is now enabled for all teachers. They can edit previous dates in portfolio assessments.', 'Past Date Editing Enabled', {
          timeOut: 6000,
          closeButton: true,
          progressBar: true
        });
      } else {
        toastr.info('Past date editing has been disabled. Teachers can only edit today\'s marks.', 'Past Date Editing Disabled', {
          timeOut: 4000,
          closeButton: true
        });
      }
      
      // Update the warning box styling
      const warningBox = statusEl.closest('.settings-card').querySelector('[style*="background"]');
      if(warningBox) {
        if(checkbox.checked) {
          warningBox.style.background = '#fef3c7';
          warningBox.style.borderLeftColor = '#f59e0b';
          warningBox.querySelector('strong').style.color = '#92400e';
          warningBox.querySelector('ul').style.color = '#92400e';
        } else {
          warningBox.style.background = '#f3f4f6';
          warningBox.style.borderLeftColor = '#9ca3af';
          warningBox.querySelector('strong').style.color = '#4b5563';
          warningBox.querySelector('ul').style.color = '#4b5563';
        }
      }
    },
    error: function() {
      toastr.error('Failed to save setting. Please try again.', 'Error');
      // Revert checkbox
      checkbox.checked = !checkbox.checked;
      updatePastEditToggleStatus(checkbox);
    }
  });
}

function updateOfflineOnlineModeStatus(checkbox) {
  const statusEl = document.getElementById('offlineOnlineModeStatus');
  if (checkbox.checked) {
    statusEl.textContent = 'ENABLED';
    statusEl.classList.remove('disabled');
    statusEl.classList.add('enabled');
  } else {
    statusEl.textContent = 'DISABLED';
    statusEl.classList.remove('enabled');
    statusEl.classList.add('disabled');
  }
}

// jQuery code - wait for document ready
$(document).ready(function() {
  // Handle form submission
  $('form').on('submit', function(e) {
    e.preventDefault();
  
    // Find all validation errors
    let errors = [];
    let firstErrorField = null;
    let firstErrorTab = null;
  
    // Check all required fields
    $(this).find('input[required], select[required], textarea[required]').each(function() {
      let $field = $(this);
      let value = $field.val();
    
    if (!value || (typeof value === 'string' && value.trim() === '')) {
      let label = $field.closest('.form-group').find('label').first().text().replace('*', '').trim();
      let $section = $field.closest('.settings-section');
      let tabId = $section.attr('id');
      
      if (!firstErrorField) {
        firstErrorField = $field;
        firstErrorTab = tabId;
      }
      
      errors.push({
        field: label || $field.attr('name') || 'Unknown field',
        tab: getTabDisplayName(tabId)
      });
    }
  });
  
  // Check email format
  $(this).find('input[type="email"]').each(function() {
    let $field = $(this);
    let value = $field.val();
    
    if (value && value.trim() !== '' && !isValidEmail(value.trim())) {
      let label = $field.closest('.form-group').find('label').first().text().replace('*', '').trim();
      let $section = $field.closest('.settings-section');
      let tabId = $section.attr('id');
      
      if (!firstErrorField) {
        firstErrorField = $field;
        firstErrorTab = tabId;
      }
      
      errors.push({
        field: (label || $field.attr('name') || 'Email field') + ' (invalid format)',
        tab: getTabDisplayName(tabId)
      });
    }
  });
  
  if (errors.length > 0) {
    // Create error message
    let errorMsg = '<div style="text-align: left;">';
    errorMsg += '<h4 style="color: #dc3545; margin-bottom: 15px;"><i class="fa fa-exclamation-triangle"></i> Form Validation Failed</h4>';
    errorMsg += '<p style="color: #721c24; margin: 10px 0;">Please fix the following errors:</p>';
    errorMsg += '<ul style="margin: 10px 0; padding-left: 20px; max-height: 200px; overflow-y: auto;">';
    
    errors.forEach(function(error) {
      errorMsg += '<li style="margin: 5px 0; color: #721c24;">';
      errorMsg += '<strong>' + error.field + '</strong> ';
      errorMsg += '<span style="color: #6b7280; font-size: 12px;">(in ' + error.tab + ' tab)</span>';
      errorMsg += '</li>';
    });
    
    errorMsg += '</ul>';
    errorMsg += '<p style="margin-top: 15px; font-size: 12px; color: #6c757d;">';
    errorMsg += '<i class="fa fa-info-circle"></i> Click OK to navigate to the first error.';
    errorMsg += '</p></div>';
    
    showAjaxModal_alert(errorMsg, 'error');
    
    // Navigate to error tab and highlight field after a short delay
    setTimeout(function() {
      if (firstErrorTab && firstErrorField) {
        // Switch to the error tab
        switchTab(firstErrorTab);
        
        // Scroll to top
        $('html, body').animate({ scrollTop: 0 }, 300);
        
        // Highlight and focus the error field
        setTimeout(function() {
          firstErrorField.focus();
          firstErrorField.css({
            'border': '2px solid #dc3545',
            'background-color': '#fef2f2',
            'box-shadow': '0 0 0 3px rgba(220, 53, 69, 0.2)'
          });
          
          // Remove highlight after 4 seconds
          setTimeout(function() {
            firstErrorField.css({
              'border': '',
              'background-color': '',
              'box-shadow': ''
            });
          }, 4000);
        }, 300);
      }
    }, 1000);
    
    return false;
  }
  
  // If no errors, proceed with submission
  // First, check if any files are selected but not uploaded
  const filesToUpload = [];
  
  // Check school logo
  const logoInput = document.getElementById('school_logo_input');
  if (logoInput && logoInput.files && logoInput.files.length > 0) {
    filesToUpload.push({ type: 'school_logo', previewId: 'logo', inputId: 'school_logo_input' });
  }
  
  // Check signature
  const signatureInput = document.getElementById('signature_input');
  if (signatureInput && signatureInput.files && signatureInput.files.length > 0) {
    filesToUpload.push({ type: 'signature', previewId: 'signature', inputId: 'signature_input' });
  }
  
  // Check SSNIT logo
  const ssnitInput = document.getElementById('ssnit_logo_input');
  if (ssnitInput && ssnitInput.files && ssnitInput.files.length > 0) {
    filesToUpload.push({ type: 'ssnit_logo', previewId: 'ssnit', inputId: 'ssnit_logo_input' });
  }
  
  // If files need to be uploaded, upload them first
  if (filesToUpload.length > 0) {
    $('html, body').animate({ scrollTop: 0 }, 500);
    showAjaxModal_alert('UPLOADING FILES (' + filesToUpload.length + ')...<br><small>Please wait...</small>', 'loading');
    
    // Upload files sequentially
    uploadFilesSequentially(filesToUpload, 0, function(success) {
      if (success) {
        // All files uploaded successfully, now submit the form
        submitSettingsForm();
      } else {
        // File upload failed
        $('.alert_modal').modal('hide');
        showAjaxModal_alert('❌ FILE UPLOAD FAILED<br><small>Please try uploading files manually</small>', 'error');
      }
    });
    
    return false;
  }
  
  // No files to upload, proceed directly with form submission
  submitSettingsForm();
  return false;
  }); // End of form submit handler
}); // End of document ready

// Function to upload files sequentially
function uploadFilesSequentially(files, index, callback) {
  if (index >= files.length) {
    // All files uploaded successfully
    callback(true);
    return;
  }
  
  const file = files[index];
  const fileInput = document.getElementById(file.inputId);
  const statusDiv = document.getElementById(file.previewId + '_status');
  
  let endpoint, fieldName;
  
  switch(file.type) {
    case 'school_logo':
      endpoint = '<?php echo base_url(); ?>admin/ajax_upload_logo';
      fieldName = 'userfile';
      break;
    case 'signature':
      endpoint = '<?php echo base_url(); ?>admin/ajax_upload_signature';
      fieldName = 'signature';
      break;
    case 'ssnit_logo':
      endpoint = '<?php echo base_url(); ?>admin/ajax_upload_ssnit_logo';
      fieldName = 'ssnit_logo';
      break;
    default:
      // Skip unknown file types
      uploadFilesSequentially(files, index + 1, callback);
      return;
  }
  
  const formData = new FormData();
  formData.append(fieldName, fileInput.files[0]);
  
  // Update loading message
  $('.alert_modal .modal-body').html('UPLOADING FILE ' + (index + 1) + ' OF ' + files.length + '...<br><small>' + fileInput.files[0].name + '</small>');
  
  $.ajax({
    url: endpoint,
    type: 'POST',
    data: formData,
    processData: false,
    contentType: false,
    success: function(response) {
      if (typeof response === 'string') {
        try {
          response = JSON.parse(response);
        } catch (e) {
          console.error('Failed to parse response:', e);
        }
      }
      
      if (response.success) {
        // Update preview image
        const previewImg = document.querySelector('#' + file.previewId + '_preview img');
        if (previewImg && response.path) {
          previewImg.src = response.path;
        }
        
        // Clear file input
        fileInput.value = '';
        
        // Upload next file
        uploadFilesSequentially(files, index + 1, callback);
      } else {
        // Upload failed
        callback(false);
      }
    },
    error: function(xhr, status, error) {
      console.error('File upload error:', error);
      callback(false);
    }
  });
}

// Function to submit settings form
function submitSettingsForm() {
  const form = $('#system_settings_form');
  
  // Handle checkbox values
  const autoLockCheckbox = $('input[name="auto_lock_enabled"]');
  if (!autoLockCheckbox.is(':checked')) {
    $('input[name="auto_lock_enabled"][type="hidden"]').remove();
    form.append('<input type="hidden" name="auto_lock_enabled" value="no">');
  }
  
  const waecGradingCheckbox = $('input[name="waec_grading_enabled"]');
  if (!waecGradingCheckbox.is(':checked')) {
    $('input[name="waec_grading_enabled"][type="hidden"]').remove();
    form.append('<input type="hidden" name="waec_grading_enabled" value="no">');
  }
  
  $('html, body').animate({ scrollTop: 0 }, 500);
  showAjaxModal_alert('UPDATING SYSTEM SETTINGS, PLEASE WAIT...', 'loading');
  
  // Submit the form via AJAX
  $.ajax({
    url: form.attr('action'),
    type: 'POST',
    data: form.serialize(),
    success: function(response) {
      // Hide loading modal
      $('.alert_modal').modal('hide');
      
      // Show success message with delay before reload
      showAjaxModal_alert('✅ SETTINGS UPDATED SUCCESSFULLY!<br><small>Page will reload in 2 seconds...</small>', 'success');
      
      // Reload page after 2 seconds
      setTimeout(function() {
        location.reload();
      }, 2000);
    },
    error: function(xhr, status, error) {
      // Hide loading modal
      $('.alert_modal').modal('hide');
      
      // Show error message
      showAjaxModal_alert('❌ ERROR: Failed to update settings.<br><small>' + error + '</small>', 'error');
    }
  });
}

// AJAX File Upload Function
function uploadFile(type, previewId) {
  let inputId, endpoint, fieldName;
  
  // Determine which file input and endpoint to use
  switch(type) {
    case 'school_logo':
      inputId = 'school_logo_input';
      endpoint = '<?php echo base_url(); ?>admin/ajax_upload_logo';
      fieldName = 'userfile';
      break;
    case 'signature':
      inputId = 'signature_input';
      endpoint = '<?php echo base_url(); ?>admin/ajax_upload_signature';
      fieldName = 'signature';
      break;
    case 'ssnit_logo':
      inputId = 'ssnit_logo_input';
      endpoint = '<?php echo base_url(); ?>admin/ajax_upload_ssnit_logo';
      fieldName = 'ssnit_logo';
      break;
    default:
      return;
  }
  
  const fileInput = document.getElementById(inputId);
  const statusDiv = document.getElementById(previewId + '_status');
  
  if (!fileInput.files || fileInput.files.length === 0) {
    statusDiv.innerHTML = '<span style="color: #dc3545;"><i class="fa fa-exclamation-circle"></i> Please select a file first</span>';
    return;
  }
  
  const file = fileInput.files[0];
  
  // Validate file type
  if (!file.type.match('image.*')) {
    statusDiv.innerHTML = '<span style="color: #dc3545;"><i class="fa fa-exclamation-circle"></i> Please select an image file</span>';
    return;
  }
  
  // Validate file size (5MB max)
  if (file.size > 5 * 1024 * 1024) {
    statusDiv.innerHTML = '<span style="color: #dc3545;"><i class="fa fa-exclamation-circle"></i> File size must be less than 5MB</span>';
    return;
  }
  
  // Create FormData
  const formData = new FormData();
  formData.append(fieldName, file);
  
  // Show uploading status
  statusDiv.innerHTML = '<span style="color: #667eea;"><i class="fa fa-spinner fa-spin"></i> Uploading...</span>';
  
  // Upload via AJAX
  $.ajax({
    url: endpoint,
    type: 'POST',
    data: formData,
    processData: false,
    contentType: false,
    xhr: function() {
      const xhr = new window.XMLHttpRequest();
      // Upload progress
      xhr.upload.addEventListener('progress', function(e) {
        if (e.lengthComputable) {
          const percentComplete = Math.round((e.loaded / e.total) * 100);
          statusDiv.innerHTML = '<span style="color: #667eea;"><i class="fa fa-spinner fa-spin"></i> Uploading... ' + percentComplete + '%</span>';
        }
      }, false);
      return xhr;
    },
    success: function(response) {
      // Parse response if it's a string
      if (typeof response === 'string') {
        try {
          response = JSON.parse(response);
        } catch (e) {
          console.error('Failed to parse response:', e);
        }
      }
      
      if (response.success) {
        // Show success message
        statusDiv.innerHTML = '<span style="color: #10b981;"><i class="fa fa-check-circle"></i> ' + response.message + '</span>';
        
        // Update preview image with the actual uploaded file path from server
        const previewImg = document.querySelector('#' + previewId + '_preview img');
        if (previewImg && response.path) {
          // Use the path returned from server (already has timestamp)
          previewImg.src = response.path;
        }
        
        // Clear file input
        fileInput.value = '';
        
        // Clear status after 3 seconds
        setTimeout(function() {
          statusDiv.innerHTML = '';
        }, 3000);
      } else {
        // Show error from server
        statusDiv.innerHTML = '<span style="color: #dc3545;"><i class="fa fa-times-circle"></i> ' + (response.message || 'Upload failed') + '</span>';
        
        setTimeout(function() {
          statusDiv.innerHTML = '';
        }, 5000);
      }
    },
    error: function(xhr, status, error) {
      // Show error message
      let errorMsg = 'Upload failed';
      if (xhr.responseJSON && xhr.responseJSON.message) {
        errorMsg = xhr.responseJSON.message;
      } else if (xhr.responseText) {
        try {
          const parsed = JSON.parse(xhr.responseText);
          errorMsg = parsed.message || errorMsg;
        } catch (e) {
          errorMsg = xhr.responseText || errorMsg;
        }
      }
      statusDiv.innerHTML = '<span style="color: #dc3545;"><i class="fa fa-times-circle"></i> ' + errorMsg + '</span>';
      
      // Clear error after 5 seconds
      setTimeout(function() {
        statusDiv.innerHTML = '';
      }, 5000);
    }
  });
}

// Preview selected image before upload
document.addEventListener('DOMContentLoaded', function() {
  // School Logo preview
  const logoInput = document.getElementById('school_logo_input');
  if (logoInput) {
    logoInput.addEventListener('change', function(e) {
      if (e.target.files && e.target.files[0]) {
        const reader = new FileReader();
        reader.onload = function(event) {
          document.querySelector('#logo_preview img').src = event.target.result;
        };
        reader.readAsDataURL(e.target.files[0]);
      }
    });
  }
  
  // Signature preview
  const signatureInput = document.getElementById('signature_input');
  if (signatureInput) {
    signatureInput.addEventListener('change', function(e) {
      if (e.target.files && e.target.files[0]) {
        const reader = new FileReader();
        reader.onload = function(event) {
          document.querySelector('#signature_preview img').src = event.target.result;
        };
        reader.readAsDataURL(e.target.files[0]);
      }
    });
  }
  
  // SSNIT Logo preview
  const ssnitInput = document.getElementById('ssnit_logo_input');
  if (ssnitInput) {
    ssnitInput.addEventListener('change', function(e) {
      if (e.target.files && e.target.files[0]) {
        const reader = new FileReader();
        reader.onload = function(event) {
          document.querySelector('#ssnit_preview img').src = event.target.result;
        };
        reader.readAsDataURL(e.target.files[0]);
      }
    });
  }
  
}); // End of $(document).ready()
</script>
