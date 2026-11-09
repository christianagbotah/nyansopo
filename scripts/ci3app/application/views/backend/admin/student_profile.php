<style>
.profile-card { background: #fff; border-radius: 16px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); transition: all 0.3s; }
.profile-card:hover { box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); }
.info-badge { display: inline-flex; align-items: center; gap: 8px; padding: 8px 16px; border-radius: 9999px; font-size: 16px; font-weight: 500; }
.tab-modern { position: relative; padding: 12px 24px; font-weight: 600; transition: all 0.3s; border-radius: 8px; font-size: 16px; }
.tab-modern.active { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
.stat-card { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 12px; padding: 20px; color: white; }
div.dataTables_wrapper div.dataTables_filter input { width: 70% !important; }
.sticky-header { position: sticky; top: 0; z-index: 10; background: linear-gradient(to bottom right, rgb(249 250 251), rgb(243 244 246)); }
.tab-content-scrollable { max-height: calc(100vh - 280px); overflow-y: auto; }
@media (max-width: 768px) {
  .tab-modern { padding: 8px 12px; font-size: 14px; }
  .dataTables_wrapper .dataTables_length,
  .dataTables_wrapper .dataTables_filter { float: none !important; text-align: left !important; }
  .dataTables_wrapper .dataTables_length { display: inline-block; width: 48%; }
  .dataTables_wrapper .dataTables_filter { display: inline-block; width: 48%; float: right !important; }
  .dataTables_wrapper .dataTables_filter input { width: 100% !important; }
}
</style>

<?php

  //currency
  $currency = $this->db->get_where('settings', array('type' => 'currency'))->row()->description;
  $fmt = new NumberFormatter('en_US', NumberFormatter::CURRENCY);

  $this->db->select_max('timestamp');
  $att_timestamp = $this->db->get('attendance')->row()->timestamp;
  
  $param3 = $param3 ?? '';
  $inv_number_len = 0;

  $student_info = $this->db->get_where('student', array('student_id' => $student_id))->result_array();
  foreach ($student_info as $row):
    $enroll_info = $this->db->get_where('enroll', array(
      'student_id' => $row['student_id'], 'year' => $running_year
    ));
    $class_id = $enroll_info->last_row()->class_id;

    $section_id = $this->db->get_where('section', array('class_id' => $class_id))->row()->section_id;

    $exams = $this->crud_model->get_student_exams($student_id);

    $gender = $this->db->get_where('student', array('student_id' => $row['student_id']))->row()->sex;

    $student_id = $row['student_id']; //$this->db->get_where('invoice' , array('invoice_code' => 

    $class_name = $this->crud_model->get_class_name($class_id);
    $student_name = $this->crud_model->getStudentInfoById($student_id)->name;
?>
<!-- Modern Student Profile -->
<div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 p-4 md:p-6">
  <!-- Student Selector -->
  <div class="mb-6 sticky-header pb-4">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
      <h1 class="text-2xl md:text-3xl font-bold text-gray-800">Student Profile</h1>
      <div class="flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-3">
        <label class="text-sm font-medium text-gray-700">Switch Student:</label>
        <select class="form-control select2 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 w-full sm:w-auto" id="other_students">
          <?php foreach($other_students as $os) { ?>
            <option value="<?=$os['student_id']?>" <?=$student_id == $os['student_id'] ? 'selected' : ''; ?>><?=$this->crud_model->getStudentNameById($os['student_id'])?></option>
          <?php } ?>
        </select>
      </div>
    </div>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
    <!-- Left Sidebar - Student Info Card -->
    <div class="lg:col-span-1">
      <div class="profile-card p-4 md:p-6 lg:sticky lg:top-24 lg:max-h-[calc(100vh-7rem)] lg:overflow-y-auto">
        <div class="text-center">
          <div class="relative inline-block mb-4">
            <img src="<?php echo $this->crud_model->get_image_url('student', $student_id, $gender); ?>" class="w-32 h-32 rounded-full object-cover border-4 border-blue-500 shadow-lg" />
            <?php
              $class_name_numeric = $this->db->get_where('class', array('class_id' => $enroll_info->last_row()->class_id))->row()->name_numeric;
              $class_name = $this->db->get_where('class', array('class_id' => $enroll_info->last_row()->class_id))->row()->name;
              $section_name = $this->db->get_where('section', array('section_id' => $enroll_info->last_row()->section_id))->row()->name;
              $last_term_seen = $enroll_info->last_row()->term;
              $r_year = get_settings('running_year');
              $r_term = get_settings('running_term');
              $this->db->where('student_id', $student_id);
              $this->db->where('year', $r_year);
              $this->db->where('term', $r_term);
              $student_row_current_session = $this->db->get('enroll')->num_rows();
              $status_class = 'bg-green-500';
              $status_text = 'ACTIVE';
              if($student_row_current_session == 0) {
                if($class_name == 'JHS' && $class_name_numeric == 3 && $last_term_seen == 3) {
                  $status_class = 'bg-blue-500';
                  $status_text = 'COMPLETED';
                } else {
                  $status_class = 'bg-gray-400';
                  $status_text = 'INACTIVE';
                }
              }
            ?>
            <span class="absolute bottom-0 right-0 <?=$status_class?> text-white text-xs font-bold px-3 py-1 rounded-full"><?=$status_text?></span>
          </div>
          <h2 class="text-2xl font-bold text-gray-800 mb-2"><?php echo $row['name']; ?></h2>
          <a href="<?php echo site_url('admin/student_information/'.$enroll_info->last_row()->class_id);?>" class="text-blue-600 hover:text-blue-700 font-medium">
            <?php echo $class_name.' '.$class_name_numeric. ' | Section '.$section_name; ?>
          </a>
          <?php if (!isset($is_teacher_view) || !$is_teacher_view): ?>
          <button onclick="destroySelect2()" class="mt-6 w-full bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white font-semibold py-3 px-6 rounded-lg transition-all duration-300 shadow-md hover:shadow-lg">
            <i class="entypo-pencil mr-2"></i>Edit Profile
          </button>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Right Content Area -->
    <div class="lg:col-span-3">

      <!-- Modern Tabs -->
      <div class="profile-card mb-6 sticky-header" style="top: 80px;">
        <div class="flex flex-wrap gap-2 p-2 md:p-4 border-b border-gray-200 overflow-x-auto">
          <button class="tab-modern active text-base" onclick="showTab('tab1', this)" data-tab="basic_info">
            <i class="entypo-home mr-2"></i><?php echo get_phrase('basic_info'); ?>
          </button>
          <?php if (!isset($is_teacher_view) || !$is_teacher_view): ?>
          <button class="tab-modern text-base" onclick="showTab('tab2', this)" data-tab="parent_info">
            <i class="entypo-user mr-2"></i><?php echo get_phrase('parent_info'); ?>
          </button>
          <?php endif; ?>
          <button class="tab-modern text-base" onclick="showTab('tab3', this)" data-tab="exams">
            <i class="entypo-graduation-cap mr-2"></i><?php echo get_phrase('exam_marks'); ?>
          </button>
          <?php if (!isset($is_teacher_view) || !$is_teacher_view): ?>
          <button class="tab-modern text-base" onclick="showTab('tab4', this)" data-tab="login">
            <i class="entypo-key mr-2"></i><?php echo get_phrase('Login'); ?>
          </button>
          <button class="tab-modern text-base" onclick="showTab('tab6', this)" data-tab="account">
            <i class="entypo-credit-card mr-2"></i><?php echo get_phrase('accounts'); ?>
          </button>
          <?php endif; ?>
        </div>
      </div>

      <!-- Tab Content -->
      <div class="tab-content-wrapper tab-content">
        <div class="tab-pane-modern active" id="tab1">
          <?php
            $enrollment_query = $this->crud_model->getStudentCurrentEnrollmentStatusRow($row['student_id']);
            $basic_info = [
              ['icon' => 'entypo-user', 'label' => 'name', 'value' => $row['name'], 'color' => 'blue'],
              ['icon' => 'entypo-users', 'label' => 'parent', 'value' => $row['parent_id'] == NULL ? 'N/A' : $this->db->get_where('parent', array('parent_id' => $row['parent_id']))->row()->name, 'color' => 'purple'],
              ['icon' => 'entypo-book', 'label' => 'class', 'value' => $class_name.' '.$class_name_numeric, 'color' => 'green'],
              ['icon' => 'entypo-folder', 'label' => 'section', 'value' => $section_name, 'color' => 'yellow'],
              ['icon' => 'entypo-mail', 'label' => 'email', 'value' => $row['email'], 'color' => 'red'],
              ['icon' => 'entypo-phone', 'label' => 'phone', 'value' => $row['phone'] ?? 'N/A', 'color' => 'indigo'],
              ['icon' => 'entypo-location', 'label' => 'address', 'value' => $row['address'] ?? 'N/A', 'color' => 'pink'],
              ['icon' => 'entypo-water', 'label' => 'blood_group', 'value' => $row['blood_group'] ?? 'N/A', 'color' => 'red'],
              ['icon' => 'entypo-male', 'label' => 'gender', 'value' => $row['sex'] ?? 'N/A', 'color' => 'blue'],
              ['icon' => 'entypo-calendar', 'label' => 'birthday', 'value' => $row['birthday'], 'color' => 'orange'],
              ['icon' => 'entypo-home', 'label' => 'residence_type', 'value' => $enrollment_query->residence_type, 'color' => 'teal'],
              ['icon' => 'entypo-traffic-cone', 'label' => 'transport', 'value' => isset($enrollment_query->transport_id) && $enrollment_query->transport_id ? $this->db->get_where('transport', array('transport_id' => $enrollment_query->transport_id))->row()->route_name : 'N/A', 'color' => 'cyan'],
              ['icon' => 'entypo-suitcase', 'label' => 'dormitory', 'value' => isset($enrollment_query->dormitory_id) && $enrollment_query->dormitory_id ? $this->db->get_where('dormitory', array('dormitory_id' => $enrollment_query->dormitory_id))->row()->name : 'N/A', 'color' => 'violet'],
              ['icon' => 'entypo-leaf', 'label' => 'special_diet', 'value' => $row['special_diet'] == 0 ? 'NO' : 'YES', 'color' => 'green']
            ];
          ?>
          <!-- Personal Information Section -->
          <div class="profile-card p-6 mb-6">
            <h3 class="text-2xl font-bold text-gray-800 mb-5 flex items-center gap-3">
              <i class="entypo-user text-2xl text-blue-600"></i>Personal Information
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
              <?php
                $personal = [
                  ['icon' => 'entypo-user', 'label' => 'Full Name', 'value' => $row['name'], 'color' => 'blue', 'teacher_visible' => true],
                  ['icon' => 'entypo-calendar', 'label' => 'Birthday', 'value' => date('M d, Y', strtotime($row['birthday'])), 'color' => 'orange', 'teacher_visible' => true],
                  ['icon' => 'entypo-male', 'label' => 'Gender', 'value' => ucfirst($row['sex'] ?? 'N/A'), 'color' => 'purple', 'teacher_visible' => true],
                  ['icon' => 'entypo-water', 'label' => 'Blood Group', 'value' => $row['blood_group'] ?? 'N/A', 'color' => 'red', 'teacher_visible' => true],
                  ['icon' => 'entypo-flag', 'label' => 'Nationality', 'value' => $row['nationality'] ?? 'N/A', 'color' => 'green', 'teacher_visible' => true],
                  ['icon' => 'entypo-credit-card', 'label' => 'Ghana Card ID', 'value' => $row['ghana_card_id'] ?? 'N/A', 'color' => 'indigo', 'teacher_visible' => false],
                  ['icon' => 'entypo-location', 'label' => 'Place of Birth', 'value' => $row['place_of_birth'] ?? 'N/A', 'color' => 'pink', 'teacher_visible' => true],
                  ['icon' => 'entypo-home', 'label' => 'Hometown', 'value' => $row['hometown'] ?? 'N/A', 'color' => 'teal', 'teacher_visible' => true],
                  ['icon' => 'entypo-calendar', 'label' => 'Admission Date', 'value' => $row['admission_date'] ? date('M d, Y', strtotime($row['admission_date'])) : 'N/A', 'color' => 'cyan', 'teacher_visible' => true]
                ];
                foreach($personal as $info) { 
                  // Skip sensitive fields for teachers
                  if (isset($is_teacher_view) && $is_teacher_view && !$info['teacher_visible']) {
                    continue;
                  }
                  ?>
                  <div class="bg-gray-50 p-5 rounded-lg hover:bg-gray-100 transition">
                    <div class="flex items-center gap-3 mb-3">
                      <i class="<?=$info['icon']?> text-2xl text-<?=$info['color']?>-600"></i>
                      <p class="text-base font-semibold text-gray-600 uppercase tracking-wide"><?=$info['label']?></p>
                    </div>
                    <p class="text-xl font-bold text-gray-800 ml-9"><?=$info['value']?></p>
                  </div>
                <?php } ?>
            </div>
          </div>

          <!-- Contact Information -->
          <?php if (!isset($is_teacher_view) || !$is_teacher_view): ?>
          <div class="profile-card p-6 mb-6">
            <h3 class="text-2xl font-bold text-gray-800 mb-5 flex items-center gap-3">
              <i class="entypo-phone text-2xl text-green-600"></i>Contact Information
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
              <?php
                $contact = [
                  ['icon' => 'entypo-mail', 'label' => 'Email', 'value' => $row['email'], 'color' => 'red'],
                  ['icon' => 'entypo-phone', 'label' => 'Phone', 'value' => $row['phone'] ?? 'N/A', 'color' => 'green'],
                  ['icon' => 'entypo-mobile', 'label' => 'Student Phone', 'value' => $row['student_phone'] ?? 'N/A', 'color' => 'blue'],
                  ['icon' => 'entypo-phone', 'label' => 'Emergency Contact', 'value' => $row['emergency_contact'] ?? 'N/A', 'color' => 'orange'],
                  ['icon' => 'entypo-location', 'label' => 'Address', 'value' => $row['address'] ?? 'N/A', 'color' => 'purple', 'full' => true]
                ];
                foreach($contact as $info) { ?>
                  <div class="bg-gray-50 p-5 rounded-lg hover:bg-gray-100 transition <?=isset($info['full']) ? 'md:col-span-2' : ''?>">
                    <div class="flex items-center gap-3 mb-3">
                      <i class="<?=$info['icon']?> text-2xl text-<?=$info['color']?>-600"></i>
                      <p class="text-base font-semibold text-gray-600 uppercase tracking-wide"><?=$info['label']?></p>
                    </div>
                    <p class="text-xl font-bold text-gray-800 ml-9 break-words"><?=$info['value']?></p>
                  </div>
                <?php } ?>
            </div>
          </div>
          <?php endif; ?>

          <!-- Academic Information -->
          <div class="profile-card p-6 mb-6">
            <h3 class="text-2xl font-bold text-gray-800 mb-5 flex items-center gap-3">
              <i class="entypo-book text-2xl text-purple-600"></i>Academic Information
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
              <?php
                $academic = [
                  ['icon' => 'entypo-book', 'label' => 'Class', 'value' => $class_name.' '.$class_name_numeric, 'color' => 'blue'],
                  ['icon' => 'entypo-folder', 'label' => 'Section', 'value' => $section_name, 'color' => 'green'],
                  ['icon' => 'entypo-home', 'label' => 'Residence Type', 'value' => ucfirst($enrollment_query->residence_type), 'color' => 'orange'],
                  ['icon' => 'entypo-traffic-cone', 'label' => 'Transport', 'value' => isset($enrollment_query->transport_id) && $enrollment_query->transport_id ? $this->db->get_where('transport', array('transport_id' => $enrollment_query->transport_id))->row()->route_name : 'N/A', 'color' => 'cyan'],
                  ['icon' => 'entypo-suitcase', 'label' => 'Dormitory', 'value' => isset($enrollment_query->dormitory_id) && $enrollment_query->dormitory_id ? $this->db->get_where('dormitory', array('dormitory_id' => $enrollment_query->dormitory_id))->row()->name : 'N/A', 'color' => 'purple'],
                  ['icon' => 'entypo-leaf', 'label' => 'Special Diet', 'value' => $row['special_diet'] == 1 ? 'YES' : 'NO', 'color' => 'green']
                ];
                foreach($academic as $info) { ?>
                  <div class="bg-gray-50 p-5 rounded-lg hover:bg-gray-100 transition">
                    <div class="flex items-center gap-3 mb-3">
                      <i class="<?=$info['icon']?> text-2xl text-<?=$info['color']?>-600"></i>
                      <p class="text-base font-semibold text-gray-600 uppercase tracking-wide"><?=$info['label']?></p>
                    </div>
                    <p class="text-xl font-bold text-gray-800 ml-9"><?=$info['value']?></p>
                  </div>
                <?php } ?>
            </div>
          </div>

          <!-- Health & Special Needs -->
          <?php if($row['medical_conditions'] || $row['allergies'] || $row['disability_status'] || $row['nhis_number']) { ?>
          <div class="profile-card p-6 mb-6 border-l-4 border-red-500">
            <h3 class="text-2xl font-bold text-gray-800 mb-5 flex items-center gap-3">
              <i class="entypo-heart text-2xl text-red-600"></i>Health & Medical Information
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <?php if($row['nhis_number']) { ?>
                <div class="bg-red-50 p-4 rounded-lg">
                  <div class="flex items-center gap-3 mb-2">
                    <i class="entypo-credit-card text-xl text-red-600"></i>
                    <p class="text-sm font-medium text-gray-500">NHIS Number</p>
                  </div>
                  <p class="text-lg font-semibold text-gray-800 ml-8"><?=$row['nhis_number']?></p>
                  <span class="ml-8 mt-1 inline-block px-2 py-1 text-xs rounded-full <?=$row['nhis_status']=='active'?'bg-green-100 text-green-800':'bg-yellow-100 text-yellow-800'?>"><?=ucfirst($row['nhis_status'] ?? 'pending')?></span>
                </div>
              <?php } ?>
              <?php if($row['medical_conditions']) { ?>
                <div class="bg-orange-50 p-4 rounded-lg md:col-span-2">
                  <div class="flex items-center gap-3 mb-2">
                    <i class="entypo-attention text-xl text-orange-600"></i>
                    <p class="text-sm font-medium text-gray-500">Medical Conditions</p>
                  </div>
                  <p class="text-base text-gray-700 ml-8"><?=$row['medical_conditions']?></p>
                </div>
              <?php } ?>
              <?php if($row['allergies']) { ?>
                <div class="bg-yellow-50 p-4 rounded-lg md:col-span-2">
                  <div class="flex items-center gap-3 mb-2">
                    <i class="entypo-warning text-xl text-yellow-600"></i>
                    <p class="text-sm font-medium text-gray-500">Allergies</p>
                  </div>
                  <p class="text-base text-gray-700 ml-8"><?=$row['allergies']?></p>
                </div>
              <?php } ?>
              <?php if($row['disability_status'] == 1) { ?>
                <div class="bg-blue-50 p-4 rounded-lg md:col-span-2">
                  <div class="flex items-center gap-3 mb-2">
                    <i class="entypo-info text-xl text-blue-600"></i>
                    <p class="text-sm font-medium text-gray-500">Special Needs & Support</p>
                  </div>
                  <?php if($row['special_needs']) { ?><p class="text-base text-gray-700 ml-8 mb-2"><strong>Needs:</strong> <?=$row['special_needs']?></p><?php } ?>
                  <?php if($row['learning_support']) { ?><p class="text-base text-gray-700 ml-8"><strong>Support:</strong> <?=$row['learning_support']?></p><?php } ?>
                </div>
              <?php } ?>
            </div>
          </div>
          <?php } ?>

          <!-- Technology Access -->
          <?php if(isset($row['digital_literacy']) || isset($row['home_technology_access'])) { ?>
          <div class="profile-card p-6 mb-6">
            <h3 class="text-2xl font-bold text-gray-800 mb-5 flex items-center gap-3">
              <i class="entypo-monitor text-2xl text-indigo-600"></i>Digital Literacy & Technology
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div class="bg-indigo-50 p-4 rounded-lg">
                <div class="flex items-center gap-3 mb-2">
                  <i class="entypo-graduation-cap text-xl text-indigo-600"></i>
                  <p class="text-xs font-medium text-gray-500">Digital Literacy Level</p>
                </div>
                <p class="text-base font-semibold text-gray-800 ml-8"><?=ucfirst($row['digital_literacy'] ?? 'beginner')?></p>
              </div>
              <div class="bg-purple-50 p-4 rounded-lg">
                <div class="flex items-center gap-3 mb-2">
                  <i class="entypo-laptop text-xl text-purple-600"></i>
                  <p class="text-xs font-medium text-gray-500">Home Technology Access</p>
                </div>
                <p class="text-base font-semibold text-gray-800 ml-8"><?=$row['home_technology_access'] == 1 ? 'Available' : 'Not Available'?></p>
              </div>
            </div>
          </div>
          <?php } ?>
        </div>
        <?php if (!isset($is_teacher_view) || !$is_teacher_view): ?>
        <div class="tab-pane-modern" id="tab2" style="display:none;">
          <?php 
            // Check if parent exists
            if ($row['parent_id'] == NULL) { ?>
            <div class="profile-card p-12 text-center">
              <i class="entypo-user text-6xl text-gray-300 mb-4"></i>
              <p class="text-xl text-gray-500"><?php echo get_phrase('parent_information_is_not_available'); ?></p>
            </div>
          <?php } else {
              // Get primary parent/guardian info
              $parent_info = $this->db->get_where('parent', array('parent_id' => $row['parent_id']))->row();
              $guardian_gender = isset($parent_info->guardian_gender) ? $parent_info->guardian_gender : 'Guardian';
              
              // Check if there are multiple phone numbers (father & mother)
              $phones = !empty($parent_info->phone) ? explode(',', $parent_info->phone) : [];
              $has_multiple_contacts = count($phones) > 1;
            ?>
            <!-- Primary Guardian Card -->
            <div class="profile-card p-6 mb-6 border-l-4 border-indigo-500">
              <div class="flex items-center justify-between mb-6">
                <h3 class="text-2xl font-bold text-gray-800 flex items-center gap-3">
                  <div class="w-12 h-12 rounded-full bg-gradient-to-br from-indigo-500 to-indigo-700 flex items-center justify-center">
                    <i class="<?=$guardian_gender=='Male'?'entypo-male':'entypo-female'?> text-2xl text-white"></i>
                  </div>
                  Primary Guardian (<?=$guardian_gender?>)
                </h3>
                <span class="px-4 py-2 bg-indigo-100 text-indigo-800 rounded-full text-sm font-semibold">Primary Contact</span>
              </div>
              <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-gradient-to-br from-indigo-50 to-indigo-100 p-5 rounded-xl">
                  <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 rounded-lg bg-indigo-600 flex items-center justify-center">
                      <i class="entypo-user text-xl text-white"></i>
                    </div>
                    <p class="text-xs font-semibold text-indigo-700 uppercase tracking-wide">Full Name</p>
                  </div>
                  <p class="text-lg font-bold text-gray-800 ml-13"><?=$parent_info->name?></p>
                </div>
                <div class="bg-gradient-to-br from-green-50 to-green-100 p-5 rounded-xl">
                  <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 rounded-lg bg-green-600 flex items-center justify-center">
                      <i class="entypo-phone text-xl text-white"></i>
                    </div>
                    <p class="text-xs font-semibold text-green-700 uppercase tracking-wide">Phone Number</p>
                  </div>
                  <p class="text-lg font-bold text-gray-800 ml-13"><?=$parent_info->phone??'N/A'?></p>
                </div>
                <div class="bg-gradient-to-br from-orange-50 to-orange-100 p-5 rounded-xl">
                  <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 rounded-lg bg-orange-600 flex items-center justify-center">
                      <i class="entypo-briefcase text-xl text-white"></i>
                    </div>
                    <p class="text-xs font-semibold text-orange-700 uppercase tracking-wide">Occupation</p>
                  </div>
                  <p class="text-lg font-bold text-gray-800 ml-13"><?=$parent_info->profession??'N/A'?></p>
                </div>
                <div class="bg-gradient-to-br from-red-50 to-red-100 p-5 rounded-xl">
                  <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 rounded-lg bg-red-600 flex items-center justify-center">
                      <i class="entypo-mail text-xl text-white"></i>
                    </div>
                    <p class="text-xs font-semibold text-red-700 uppercase tracking-wide">Email</p>
                  </div>
                  <p class="text-base font-bold text-gray-800 ml-13 break-words"><?=$parent_info->email?></p>
                </div>
              </div>
            </div>

            <!-- Father's Information Card -->
            <div class="profile-card p-6 mb-6 border-l-4 border-blue-600">
              <div class="flex items-center justify-between mb-6">
                <h3 class="text-2xl font-bold text-gray-800 flex items-center gap-3">
                  <div class="w-12 h-12 rounded-full bg-gradient-to-br from-blue-600 to-blue-800 flex items-center justify-center">
                    <i class="entypo-male text-2xl text-white"></i>
                  </div>
                  Father's Information
                </h3>
                <span class="px-4 py-2 bg-blue-100 text-blue-800 rounded-full text-sm font-semibold">Father</span>
              </div>
              <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-gradient-to-br from-blue-50 to-blue-100 p-5 rounded-xl">
                  <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 rounded-lg bg-blue-600 flex items-center justify-center">
                      <i class="entypo-user text-xl text-white"></i>
                    </div>
                    <p class="text-xs font-semibold text-blue-700 uppercase tracking-wide">Father's Name</p>
                  </div>
                  <p class="text-lg font-bold text-gray-800 ml-13"><?=$guardian_gender=='Male'?$parent_info->name:'N/A'?></p>
                </div>
                <div class="bg-gradient-to-br from-green-50 to-green-100 p-5 rounded-xl">
                  <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 rounded-lg bg-green-600 flex items-center justify-center">
                      <i class="entypo-phone text-xl text-white"></i>
                    </div>
                    <p class="text-xs font-semibold text-green-700 uppercase tracking-wide">Father's Phone</p>
                  </div>
                  <p class="text-lg font-bold text-gray-800 ml-13"><?=$has_multiple_contacts?trim($phones[0]):($guardian_gender=='Male'?$parent_info->phone:'N/A')?></p>
                </div>
                <div class="bg-gradient-to-br from-indigo-50 to-indigo-100 p-5 rounded-xl">
                  <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 rounded-lg bg-indigo-600 flex items-center justify-center">
                      <i class="entypo-briefcase text-xl text-white"></i>
                    </div>
                    <p class="text-xs font-semibold text-indigo-700 uppercase tracking-wide">Father's Occupation</p>
                  </div>
                  <p class="text-lg font-bold text-gray-800 ml-13"><?=$guardian_gender=='Male'?($parent_info->profession??'N/A'):'N/A'?></p>
                </div>
                <div class="bg-gradient-to-br from-cyan-50 to-cyan-100 p-5 rounded-xl">
                  <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 rounded-lg bg-cyan-600 flex items-center justify-center">
                      <i class="entypo-mail text-xl text-white"></i>
                    </div>
                    <p class="text-xs font-semibold text-cyan-700 uppercase tracking-wide">Father's Email</p>
                  </div>
                  <p class="text-base font-bold text-gray-800 ml-13 break-words"><?=$guardian_gender=='Male'?$parent_info->email:'N/A'?></p>
                </div>
              </div>
            </div>

            <!-- Mother's Information Card -->
            <div class="profile-card p-6 mb-6 border-l-4 border-pink-500">
              <div class="flex items-center justify-between mb-6">
                <h3 class="text-2xl font-bold text-gray-800 flex items-center gap-3">
                  <div class="w-12 h-12 rounded-full bg-gradient-to-br from-pink-500 to-pink-700 flex items-center justify-center">
                    <i class="entypo-female text-2xl text-white"></i>
                  </div>
                  Mother's Information
                </h3>
                <span class="px-4 py-2 bg-pink-100 text-pink-800 rounded-full text-sm font-semibold">Mother</span>
              </div>
              <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-gradient-to-br from-pink-50 to-pink-100 p-5 rounded-xl">
                  <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 rounded-lg bg-pink-500 flex items-center justify-center">
                      <i class="entypo-user text-xl text-white"></i>
                    </div>
                    <p class="text-xs font-semibold text-pink-700 uppercase tracking-wide">Mother's Name</p>
                  </div>
                  <p class="text-lg font-bold text-gray-800 ml-13"><?=$guardian_gender=='Female'?$parent_info->name:'N/A'?></p>
                </div>
                <div class="bg-gradient-to-br from-rose-50 to-rose-100 p-5 rounded-xl">
                  <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 rounded-lg bg-rose-500 flex items-center justify-center">
                      <i class="entypo-phone text-xl text-white"></i>
                    </div>
                    <p class="text-xs font-semibold text-rose-700 uppercase tracking-wide">Mother's Phone</p>
                  </div>
                  <p class="text-lg font-bold text-gray-800 ml-13"><?=$has_multiple_contacts&&count($phones)>1?trim($phones[1]):($guardian_gender=='Female'?$parent_info->phone:'N/A')?></p>
                </div>
                <div class="bg-gradient-to-br from-purple-50 to-purple-100 p-5 rounded-xl">
                  <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 rounded-lg bg-purple-500 flex items-center justify-center">
                      <i class="entypo-briefcase text-xl text-white"></i>
                    </div>
                    <p class="text-xs font-semibold text-purple-700 uppercase tracking-wide">Mother's Occupation</p>
                  </div>
                  <p class="text-lg font-bold text-gray-800 ml-13"><?=$guardian_gender=='Female'?($parent_info->profession??'N/A'):'N/A'?></p>
                </div>
                <div class="bg-gradient-to-br from-fuchsia-50 to-fuchsia-100 p-5 rounded-xl">
                  <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 rounded-lg bg-fuchsia-500 flex items-center justify-center">
                      <i class="entypo-mail text-xl text-white"></i>
                    </div>
                    <p class="text-xs font-semibold text-fuchsia-700 uppercase tracking-wide">Mother's Email</p>
                  </div>
                  <p class="text-base font-bold text-gray-800 ml-13 break-words"><?=$guardian_gender=='Female'?$parent_info->email:($row['parent_email']??'N/A')?></p>
                </div>
              </div>
            </div>

            <!-- Family Address Card -->
            <div class="profile-card p-6 mb-6 border-l-4 border-purple-500">
              <div class="flex items-center justify-between mb-6">
                <h3 class="text-2xl font-bold text-gray-800 flex items-center gap-3">
                  <div class="w-12 h-12 rounded-full bg-gradient-to-br from-purple-500 to-purple-700 flex items-center justify-center">
                    <i class="entypo-home text-2xl text-white"></i>
                  </div>
                  Family Address
                </h3>
              </div>
              <div class="bg-gradient-to-br from-purple-50 to-purple-100 p-6 rounded-xl">
                <div class="flex items-start gap-4">
                  <div class="w-12 h-12 rounded-lg bg-purple-500 flex items-center justify-center flex-shrink-0">
                    <i class="entypo-location text-2xl text-white"></i>
                  </div>
                  <div class="flex-1">
                    <p class="text-sm font-semibold text-purple-700 uppercase tracking-wide mb-2">Residential Address</p>
                    <p class="text-lg font-bold text-gray-800 break-words"><?=$parent_info->address ?? 'N/A'?></p>
                  </div>
                </div>
              </div>
            </div>

            <!-- Additional Parent Contacts (if multiple phones exist) -->
            <?php if($has_multiple_contacts && count($phones) > 2) { ?>
            <div class="profile-card p-6 mb-6 border-l-4 border-cyan-500">
              <h3 class="text-xl font-bold text-gray-800 mb-4 flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-cyan-500 flex items-center justify-center">
                  <i class="entypo-phone text-xl text-white"></i>
                </div>
                Additional Contact Numbers
              </h3>
              <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <?php for($i=2; $i<count($phones); $i++) { ?>
                  <div class="bg-gradient-to-br from-cyan-50 to-cyan-100 p-5 rounded-xl">
                    <div class="flex items-center gap-3 mb-3">
                      <div class="w-10 h-10 rounded-lg bg-cyan-500 flex items-center justify-center">
                        <i class="entypo-phone text-xl text-white"></i>
                      </div>
                      <p class="text-xs font-semibold text-cyan-600 uppercase tracking-wide">Contact <?=$i+1?></p>
                    </div>
                    <p class="text-lg font-bold text-gray-800 ml-13"><?=trim($phones[$i])?></p>
                  </div>
                <?php } ?>
              </div>
            </div>
            <?php } ?>

            <!-- Additional Contact Information -->
            <?php if($row['parent_email'] || $row['emergency_contact']) { ?>
            <div class="profile-card p-6 mb-6 border-l-4 border-orange-500">
              <h3 class="text-xl font-bold text-gray-800 mb-4 flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-orange-500 flex items-center justify-center">
                  <i class="entypo-phone text-xl text-white"></i>
                </div>
                Emergency & Alternative Contacts
              </h3>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <?php if($row['parent_email']) { ?>
                  <div class="bg-gradient-to-br from-orange-50 to-orange-100 p-5 rounded-xl">
                    <div class="flex items-center gap-3 mb-3">
                      <div class="w-10 h-10 rounded-lg bg-orange-500 flex items-center justify-center">
                        <i class="entypo-mail text-xl text-white"></i>
                      </div>
                      <p class="text-xs font-semibold text-orange-600 uppercase tracking-wide">Alternative Email</p>
                    </div>
                    <p class="text-base font-bold text-gray-800 ml-13 break-words"><?=$row['parent_email']?></p>
                  </div>
                <?php } ?>
                <?php if($row['emergency_contact']) { ?>
                  <div class="bg-gradient-to-br from-red-50 to-red-100 p-5 rounded-xl">
                    <div class="flex items-center gap-3 mb-3">
                      <div class="w-10 h-10 rounded-lg bg-red-500 flex items-center justify-center">
                        <i class="entypo-phone text-xl text-white"></i>
                      </div>
                      <p class="text-xs font-semibold text-red-600 uppercase tracking-wide">Emergency Contact</p>
                    </div>
                    <p class="text-lg font-bold text-gray-800 ml-13"><?=$row['emergency_contact']?></p>
                  </div>
                <?php } ?>
              </div>
            </div>
            <?php } ?>

            <!-- Parent Portal Access -->
            <div class="profile-card p-6 border-l-4 border-indigo-500">
              <h3 class="text-xl font-bold text-gray-800 mb-4 flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-indigo-500 flex items-center justify-center">
                  <i class="entypo-key text-xl text-white"></i>
                </div>
                Parent Portal Access
              </h3>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="bg-gradient-to-br from-indigo-50 to-indigo-100 p-5 rounded-xl">
                  <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 rounded-lg bg-indigo-500 flex items-center justify-center">
                      <i class="entypo-mail text-xl text-white"></i>
                    </div>
                    <p class="text-xs font-semibold text-indigo-600 uppercase tracking-wide">Login Username</p>
                  </div>
                  <p class="text-base font-bold text-gray-800 ml-13 break-words"><?=$parent_info->email?></p>
                </div>
                <div class="bg-gradient-to-br from-purple-50 to-purple-100 p-5 rounded-xl">
                  <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 rounded-lg bg-purple-500 flex items-center justify-center">
                      <i class="entypo-key text-xl text-white"></i>
                    </div>
                    <p class="text-xs font-semibold text-purple-600 uppercase tracking-wide">Authentication Key</p>
                  </div>
                  <p class="text-sm font-mono font-bold text-gray-800 ml-13 bg-white px-3 py-2 rounded border-2 border-purple-200"><?=$parent_info->authentication_key?></p>
                </div>
              </div>
              <div class="mt-4 bg-blue-50 border-l-4 border-blue-500 p-4 rounded">
                <p class="text-sm text-blue-800"><i class="entypo-info-circled mr-2"></i><strong>Note:</strong> Parents can access the portal using their registered email and password to view student progress, attendance, and payments.</p>
              </div>
            </div>
          <?php } ?>
        </div>
        <?php endif; ?>
      
      <?php 
          if($class_name == 'CRECHE') { ?>
            <div class="tab-pane-modern" id="tab3" style="display:none;">
        <?php if(empty($exams)) { ?>
          <div class="profile-card p-12 text-center">
            <i class="entypo-graduation-cap text-6xl text-gray-300 mb-4"></i>
            <p class="text-xl text-gray-500"><?php echo get_phrase('no_examinations_found'); ?></p>
          </div>
        <?php } else { ?>
        <?php foreach ($exams as $row2) {

          $running_year = $this->crud_model->get_exams_year($row2['exam_id']);
          $running_term = $this->crud_model->get_exams_term($row2['exam_id']);

          $this_class_id = $this->crud_model->get_exams_class_id($row2['exam_id'], $student_id);
          $this_class_numeric = $this->crud_model->get_class_name_numeric($this_class_id);
          $this_class_name = $this->crud_model->get_class_name($this_class_id);
          $this_sec_name = $this->crud_model->get_class_section($this_class_id);

          $full_class_name = $this_class_name.' '.$this_class_numeric.$this_sec_name;

          

          ?>
          <div class="profile-card" style="margin-top: 20px; padding: 16px; display: flex; align-items: center; justify-content: space-between; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 12px;">
            <div style="display: flex; align-items: center; gap: 12px;">
              <i class="fa fa-graduation-cap" style="font-size: 24px; opacity: 0.8;"></i>
              <h3 style="margin: 0; font-size: 18px; font-weight: 600;"><?php echo $this->crud_model->get_exams_name($row2['exam_id']).'-'.$full_class_name; ?></h3>
            </div>
            <div style="text-align: right; font-size: 14px; opacity: 0.9;">
              <span><?php echo get_phrase('term:').' '.$running_term. ' | '.get_phrase('year:').' '.explode('-', $running_year)[1]; ?></span>
            </div>
          </div>

          <div class="row">
            <div style="text-align: center">
              <table class="table">
                <thead>
                  <?php 
                    $grading_sys = $this->db->get('grade_creche')->result_array();
                    foreach($grading_sys as $grade): 
                  ?>
                  <tr>
                    <th style="font-size: 16px"><?= $grade['abbrev']; ?></th>
                    <th style="font-size: 16px">-</th>
                    <th style="font-size: 16px"><?= $grade['full_name']; ?></th>
                  </tr>
                <?php endforeach; ?>
                </thead>
              </table>
            </div>
          </div>

           <?php 
              $subj_category = $this->db->get('subject_category_creche')->result_array();
              foreach($subj_category as $cat): ?>

              <div class="row">
                  <table class="table table-bordered">
                    <thead>   
                      <tr>
                        <th><?= $cat['name']; ?></th>
                        <th width="80"><?= 'GRADING'; ?></th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php 
                      $subjects_creche = $this->db->get_where('subject_creche' , array(
                            'class_id' => $class_id , 'category_id' => $cat['category_id'], 'year' => $running_year, 'term' => $running_term
                        ))->result_array();
                        foreach($subjects_creche as $subj): ?>
                    <tr>
                      <td><?= $subj['name']; ?></td>
                      <td style="letter-spacing: 5px;">
                        <?php 
                            $grading = $this->db->get_where('mark' , array(
                                                    'subject_id' => $subj['subject_id'],
                                                        'exam_id' => $row2['exam_id'],
                                                            'class_id' => $class_id,
                                                                'student_id' => $student_id , 
                                                                    'year' => $running_year,
                                                                        'term' => $running_term));
                                        if ( $grading->num_rows() > 0) {
                                            $grading_array = $grading->result_array();
                                            foreach ($grading_array as $row4) {
                                                echo $this->db->get_where('grade_creche', array('grade_id' => $row4['test1']))->row()->abbrev;
                                            }
                                        }else{
                                          echo "Pending...";
                                        }
                          ?>
                      </td>
                    </tr>

                  <?php endforeach; ?>
                    </tbody>
                    
                  </table>
              </div>

          <?php endforeach; ?>

      <?php 
      if($this->db->get_where('subject_creche' , array('class_id' => $class_id , 'category_id' => '0', 'year' => $running_year, 'term' => $running_term))->num_rows() > 0) {
      ?>

          <table class="table table-bordered table-responsive table-striped table-hover table-active">
                       <thead>
                        <tr>
                            <th style="text-align: center; font-weight: bold;">S/N</th>
                            <th style="text-align: center; font-weight: bold;">SUBJECT</th>
                            <th style="text-align: center; font-weight: bold;">CLASS SCORE</th>
                            <th style="text-align: center; font-weight: bold;">EXAM SCORE</th>
                            <th style="text-align: center; font-weight: bold;">TOTAL SCORE</th>
                            <th style="text-align: center; font-weight: bold;">GRADE</th>
                            <th style="text-align: center; font-weight: bold;">REMARK</th>
                            <th style="text-align: center; font-weight: bold;">POSITION</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php
                            $class_score_total = 0;
                            $exam_score_total =0; 
                            $total_marks = 0;
                            $total_grade_point = 0;
                            $subjects = $this->db->get_where('subject_creche' , array(
                                'class_id' => $class_id , 'category_id' => '0', 'year' => $running_year, 'term' => $running_term
                            ))->result_array();

                            $i = 1;
                            foreach ($subjects as $row3):
                        ?>
                            <tr>
                                <td style="text-align: center;"><?php echo $i;?></td>
                                <td><?php if(strlen($row3['name']) <= 4) {
                                    echo strtoupper($row3['name']);
                                }else{ 
                                    echo $row3['name'];
                                };?></td>
                                 <td style="text-align: center;">
                                    <?php
                                        $class_score_query = $this->db->get_where('mark' , array(
                                                    'subject_id' => $row3['subject_id'],
                                                        'exam_id' => $row2['exam_id'],
                                                            'class_id' => $class_id,
                                                                'student_id' => $student_id , 
                                                                    'year' => $running_year,
                                                                        'term' => $running_term));
                                        if ( $class_score_query->num_rows() > 0) {
                                            $class_score = $class_score_query->result_array();
                                            foreach ($class_score as $row4) {
                                                echo $row4['class_score'];
                                                $class_score_total += $row4['class_score'];
                                                $total_marks += $row4['class_score'];
                                            }
                                        }else{
                                          echo "N/A";
                                        }
                                    ?>
                                </td>
                                 <td style="text-align: center;">
                                    <?php
                                        $exam_score_query = $this->db->get_where('mark' , array(
                                                    'subject_id' => $row3['subject_id'],
                                                        'exam_id' => $row2['exam_id'],
                                                            'class_id' => $class_id,
                                                                'student_id' => $student_id , 
                                                                    'year' => $running_year,
                                                                        'term' => $running_term));
                                        if ( $exam_score_query->num_rows() > 0) {
                                            $exam_score = $exam_score_query->result_array();
                                            foreach ($exam_score as $row4) {
                                                echo $row4['exam_score'];
                                                $exam_score_total += $row4['exam_score'];
                                                $total_marks += $row4['exam_score'];
                                            }
                                        }else{
                                          echo "N/A";
                                        }
                                    ?>
                                </td>
                                <td style="text-align: center;">
                                    <?php
                                        $obtained_mark_query = $this->db->get_where('mark' , array(
                                                    'subject_id' => $row3['subject_id'],
                                                        'exam_id' => $row2['exam_id'],
                                                            'class_id' => $class_id,
                                                                'student_id' => $student_id , 
                                                                    'year' => $running_year,
                                                                        'term' => $running_term));
                                        if ( $obtained_mark_query->num_rows() > 0) {
                                            $marks = $obtained_mark_query->result_array();
                                            foreach ($marks as $row4) {
                                                echo $row4['mark_obtained'];
                                                $total_marks;
                                            }
                                        }else{
                                          echo "N/A";
                                        }
                                    ?>
                                </td>
                               <!-- <td style="text-align: center;">
                                    <?php

                                    $highest_mark = $this->crud_model->get_highest_marks( $row2['exam_id'] , $class_id , $row3['subject_id'] );
                                    echo $highest_mark;


        
                                    ?>
                                </td>. //take highest mark column out-->
                                <td style="text-align: center;">
                                    <?php
                                        if($obtained_mark_query->num_rows() > 0) {
                                            if ($row4['mark_obtained'] >= 0 || $row4['mark_obtained'] != '') {
                                                $grade = $this->crud_model->get_grade($row4['mark_obtained']);
                                                echo $grade['grade_point'];
                                               /** $total_grade_point += $grade['grade_point'];No grade point**/
                                            }
                                        }else{
                                          echo "N/A";
                                        }
                                    ?>
                                </td>
                                <td style="text-align: center;">
                                    <?php
                                        if($obtained_mark_query->num_rows() > 0) {
                                            if ($row4['mark_obtained'] >= 0 || $row4['mark_obtained'] != '') {
                                                $grade = $this->crud_model->get_grade($row4['mark_obtained']);
                                                echo $grade['name'];
                                                /**$total_grade_point += $grade['grade_point'];==No grade point**/
                                            }
                                        }else{
                                          echo "N/A";
                                        }
                                    ?>
                                </td>
                                <td style="text-align: center;">
                                <?php
                                 $this->crud_model->get_total_score($row2['exam_id'] , $class_id , $row3['subject_id'], $row['student_id'], $running_year, $running_term);
                                 ?>
                                </td>
                              <!--  <td style="text-align: center;">
                                    <?php // if($obtained_mark_query->num_rows() > 0) 
                                            //echo $row4['comment'];
                                    ?>
                                </td>.//take comment column out-->
                            </tr>
                        <?php 
                        $i++;
                        endforeach;?>

                        <tr>
                            <th colspan="2" style="text-align: center;">TOTAL</th>
                            <td style="text-align: center; font-weight: bold;"><?php echo $class_score_total?$class_score_total:'N/A'; ?></td>
                            <td style="text-align: center; font-weight: bold;"><?php echo $exam_score_total?$exam_score_total:'N/A'; ?></td>
                            <td style="text-align: center; font-weight: bold;"><?php echo $total_marks?$total_marks:'N/A'; ?></td>
                            <td colspan="3"></td>
                        </tr>
                    </tbody>
                   </table><br>

                 <?php } ?>

           <div class="row">
             <div class="col-md-3 col-lg-3 col-sm-3 col-xs-3">
               <a href="<?php echo site_url('admin/student_results_sheet_print_view_creche/'.$student_id.'/'.$row2['exam_id'].'/'.$class_id.'/'.$running_term.'/'.$running_year);?>"
               class="btn btn-primary" target="_blank">
               <?php echo get_phrase('print_marksheet');?> </a>
             </div>
             <div class="col-md-6 col-lg-6 col-sm-6 col-xs-6"></div>
             <div class="col-md-3 col-lg-3 col-sm-3 col-xs-3">
              <div class="col-md-3 col-lg-3 col-sm-3 col-xs-3">
               <a href="#"
               class="btn btn-info" target="_self" data-url="<?php echo site_url('admin/student_results_sheet_creche/'.$student_id.'/'.$row2['exam_id'].'/'.$class_id.'/'.$running_term.'/'.$running_year);?>" onclick="loadExamResults($(this).attr('data-url'))" style="text-align: right;">
               <?php echo get_phrase('results_archives');?>
           </a>
             </div>
           </div>
           <hr/>
           </div> <br><br><br>
        <?php } ?>
        <?php } ?>
      </div>
            <?php
          } 
          //JHS
          else if($class_name == 'JHSS') {
            ?>
            <div class="tab-pane-modern" id="tab3" style="display:none;">
              <div class="row" style="margin-bottom: 20px;">
                <div class="col-md-8 col-lg-8 col-sm-8 col-xs-8"></div>
                <div class="col-md-4 col-lg-4 col-sm-4 col-xs-4">
                  <a class="btn btn-success btn-lg" href="<?php echo site_url('admin/cummulative_reports/' .$student_id) ?>" target="_blank">Grade Points Reports</a>
                </div>
              </div>
        <?php if(empty($exams)) { ?>
          <div class="profile-card p-12 text-center">
            <i class="entypo-graduation-cap text-6xl text-gray-300 mb-4"></i>
            <p class="text-xl text-gray-500"><?php echo get_phrase('no_examinations_found'); ?></p>
          </div>
        <?php } else { ?>
        <?php foreach ($exams as $row2) { 
          $running_year = $this->crud_model->get_exams_year($row2['exam_id']);
          $running_sem = $this->crud_model->get_exams_sem($row2['exam_id']);
          $exam_class_id = $this->crud_model->get_exams_class_id($row2['exam_id'], $student_id);
          $exam_class_numeric = $this->crud_model->get_class_name_numeric($exam_class_id);
          $exam_class_name = $this->crud_model->get_class_name($exam_class_id);

          $exam_sec_name = $this->crud_model->get_class_section($exam_class_id);
          $full_class_name = $this_class_name.' '.$exam_class_numeric.$this_sec_name;

          //INCASE THE STUDENT HAD BEEN IN CRECHE AND  BASIC SCHOOLS
          //INCASE THIS STUDENT WAS IN CRECHE BEFORE
          if($exam_class_name == 'CRECHE') {
            ?>
              <div class="profile-card" style="margin-top: 20px; padding: 16px; display: flex; align-items: center; justify-content: space-between; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 12px;">
            <div style="display: flex; align-items: center; gap: 12px;">
              <i class="fa fa-graduation-cap" style="font-size: 24px; opacity: 0.8;"></i>
              <h3 style="margin: 0; font-size: 18px; font-weight: 600;"><?php echo $this->crud_model->get_exams_name($row2['exam_id']).'-'.$full_class_name; ?></h3>
            </div>
            <div style="text-align: right; font-size: 14px; opacity: 0.9;">
              <span><?php echo get_phrase('term:').' '.$running_term. ' | '.get_phrase('year:').' '.explode('-', $running_year)[1]; ?></span>
            </div>
          </div>
            <div class="col-md-4 col-lg-4 col-sm-4 col-xs-4"><h3 style="text-align: right;"><?php echo get_phrase('term:').' '.$running_term. ' | '.get_phrase('year:').' '.explode('-', $running_year)[1]; ?></h3></div>
          </div>

          <div class="row">
            <div style="text-align: center">
              <table class="table">
                <thead>
                  <?php 
                    $grading_sys = $this->db->get('grade_creche')->result_array();
                    foreach($grading_sys as $grade): 
                  ?>
                  <tr>
                    <th style="font-size: 16px"><?= $grade['abbrev']; ?></th>
                    <th style="font-size: 16px">-</th>
                    <th style="font-size: 16px"><?= $grade['full_name']; ?></th>
                  </tr>
                <?php endforeach; ?>
                </thead>
              </table>
            </div>
          </div>

           <?php 
              $subj_category = $this->db->get('subject_category_creche')->result_array();
              foreach($subj_category as $cat): ?>

              <div class="row">
                  <table class="table table-bordered">
                    <thead>   
                      <tr>
                        <th><?= $cat['name']; ?></th>
                        <th width="80"><?= 'GRADING'; ?></th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php 
                      $subjects_creche = $this->db->get_where('subject_creche' , array(
                            'class_id' => $exam_class_id , 'category_id' => $cat['category_id'], 'year' => $running_year, 'term' => $running_term
                        ))->result_array();
                        foreach($subjects_creche as $subj): ?>
                    <tr>
                      <td><?= $subj['name']; ?></td>
                      <td style="letter-spacing: 5px;">
                        <?php 
                            $grading = $this->db->get_where('mark' , array(
                                                    'subject_id' => $subj['subject_id'],
                                                        'exam_id' => $row2['exam_id'],
                                                            'class_id' => $exam_class_id,
                                                                'student_id' => $student_id , 
                                                                    'year' => $running_year,
                                                                        'term' => $running_term));
                                        if ( $grading->num_rows() > 0) {
                                            $grading_array = $grading->result_array();
                                            foreach ($grading_array as $row4) {
                                                echo $this->db->get_where('grade_creche', array('grade_id' => $row4['test1']))->row()->abbrev;
                                            }
                                        }else{
                                          echo "Pending...";
                                        }
                          ?>
                      </td>
                    </tr>

                  <?php endforeach; ?>
                    </tbody>
                    
                  </table>
              </div>

          <?php endforeach; ?>

      <?php 
      if($this->db->get_where('subject_creche' , array('class_id' => $exam_class_id , 'category_id' => '0', 'year' => $running_year, 'term' => $running_term))->num_rows() > 0) {
      ?>

          <table class="table table-bordered table-responsive table-striped table-hover table-active">
                       <thead>
                        <tr>
                            <th style="text-align: center; font-weight: bold;">S/N</th>
                            <th style="text-align: center; font-weight: bold;">SUBJECT</th>
                            <th style="text-align: center; font-weight: bold;">CLASS SCORE</th>
                            <th style="text-align: center; font-weight: bold;">EXAM SCORE</th>
                            <th style="text-align: center; font-weight: bold;">TOTAL SCORE</th>
                            <th style="text-align: center; font-weight: bold;">GRADE</th>
                            <th style="text-align: center; font-weight: bold;">REMARK</th>
                            <th style="text-align: center; font-weight: bold;">POSITION</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php
                            $class_score_total = 0;
                            $exam_score_total =0; 
                            $total_marks = 0;
                            $total_grade_point = 0;
                            $subjects = $this->db->get_where('subject_creche' , array(
                                'class_id' => $exam_class_id , 'category_id' => '0', 'year' => $running_year, 'term' => $running_term
                            ))->result_array();

                            $i = 1;
                            foreach ($subjects as $row3):
                        ?>
                            <tr>
                                <td style="text-align: center;"><?php echo $i;?></td>
                                <td><?php if(strlen($row3['name']) <= 4) {
                                    echo strtoupper($row3['name']);
                                }else{ 
                                    echo $row3['name'];
                                };?></td>
                                 <td style="text-align: center;">
                                    <?php
                                        $class_score_query = $this->db->get_where('mark' , array(
                                                    'subject_id' => $row3['subject_id'],
                                                        'exam_id' => $row2['exam_id'],
                                                            'class_id' => $exam_class_id,
                                                                'student_id' => $student_id , 
                                                                    'year' => $running_year,
                                                                        'term' => $running_term));
                                        if ( $class_score_query->num_rows() > 0) {
                                            $class_score = $class_score_query->result_array();
                                            foreach ($class_score as $row4) {
                                                echo $row4['class_score'];
                                                $class_score_total += $row4['class_score'];
                                                $total_marks += $row4['class_score'];
                                            }
                                        }else{
                                          echo "N/A";
                                        }
                                    ?>
                                </td>
                                 <td style="text-align: center;">
                                    <?php
                                        $exam_score_query = $this->db->get_where('mark' , array(
                                                    'subject_id' => $row3['subject_id'],
                                                        'exam_id' => $row2['exam_id'],
                                                            'class_id' => $exam_class_id,
                                                                'student_id' => $student_id , 
                                                                    'year' => $running_year,
                                                                        'term' => $running_term));
                                        if ( $exam_score_query->num_rows() > 0) {
                                            $exam_score = $exam_score_query->result_array();
                                            foreach ($exam_score as $row4) {
                                                echo $row4['exam_score'];
                                                $exam_score_total += $row4['exam_score'];
                                                $total_marks += $row4['exam_score'];
                                            }
                                        }else{
                                          echo "N/A";
                                        }
                                    ?>
                                </td>
                                <td style="text-align: center;">
                                    <?php
                                        $obtained_mark_query = $this->db->get_where('mark' , array(
                                                    'subject_id' => $row3['subject_id'],
                                                        'exam_id' => $row2['exam_id'],
                                                            'class_id' => $exam_class_id,
                                                                'student_id' => $student_id , 
                                                                    'year' => $running_year,
                                                                        'term' => $running_term));
                                        if ( $obtained_mark_query->num_rows() > 0) {
                                            $marks = $obtained_mark_query->result_array();
                                            foreach ($marks as $row4) {
                                                echo $row4['mark_obtained'];
                                                $total_marks;
                                            }
                                        }else{
                                          echo "N/A";
                                        }
                                    ?>
                                </td>
                               <!-- <td style="text-align: center;">
                                    <?php

                                    $highest_mark = $this->crud_model->get_highest_marks( $row2['exam_id'] , $exam_class_id , $row3['subject_id'] );
                                    echo $highest_mark;


        
                                    ?>
                                </td>. //take highest mark column out-->
                                <td style="text-align: center;">
                                    <?php
                                        if($obtained_mark_query->num_rows() > 0) {
                                            if ($row4['mark_obtained'] >= 0 || $row4['mark_obtained'] != '') {
                                                $grade = $this->crud_model->get_grade($row4['mark_obtained']);
                                                echo $grade['grade_point'];
                                               /** $total_grade_point += $grade['grade_point'];No grade point**/
                                            }
                                        }else{
                                          echo "N/A";
                                        }
                                    ?>
                                </td>
                                <td style="text-align: center;">
                                    <?php
                                        if($obtained_mark_query->num_rows() > 0) {
                                            if ($row4['mark_obtained'] >= 0 || $row4['mark_obtained'] != '') {
                                                $grade = $this->crud_model->get_grade($row4['mark_obtained']);
                                                echo $grade['name'];
                                                /**$total_grade_point += $grade['grade_point'];==No grade point**/
                                            }
                                        }else{
                                          echo "N/A";
                                        }
                                    ?>
                                </td>
                                <td style="text-align: center;">
                                <?php
                                 $this->crud_model->get_total_score($row2['exam_id'] , $exam_class_id , $row3['subject_id'], $row['student_id'], $running_year, $running_term);
                                 ?>
                                </td>
                              <!--  <td style="text-align: center;">
                                    <?php // if($obtained_mark_query->num_rows() > 0) 
                                            //echo $row4['comment'];
                                    ?>
                                </td>.//take comment column out-->
                            </tr>
                        <?php 
                        $i++;
                        endforeach;?>

                        <tr>
                            <th colspan="2" style="text-align: center;">TOTAL</th>
                            <td style="text-align: center; font-weight: bold;"><?php echo $class_score_total?$class_score_total:'N/A'; ?></td>
                            <td style="text-align: center; font-weight: bold;"><?php echo $exam_score_total?$exam_score_total:'N/A'; ?></td>
                            <td style="text-align: center; font-weight: bold;"><?php echo $total_marks?$total_marks:'N/A'; ?></td>
                            <td colspan="3"></td>
                        </tr>
                    </tbody>
                   </table><br>

                 <?php } ?>

           <div class="row">
             <div class="col-md-3 col-lg-3 col-sm-3 col-xs-3">
               <a href="<?php echo site_url('admin/student_marksheet_print_view_creche/'.$student_id.'/'.$row2['exam_id']);?>"
               class="btn btn-primary" target="_blank">
               <?php echo get_phrase('print_marksheet');?>
           </a>
             </div>
             <div class="col-md-6 col-lg-6 col-sm-6 col-xs-6"></div>
             <div class="col-md-3 col-lg-3 col-sm-3 col-xs-3">
              <div class="col-md-3 col-lg-3 col-sm-3 col-xs-3">
               <a href="#"
               class="btn btn-info" target="_self" data-url="<?php echo site_url('admin/student_results_sheet_creche/'.$student_id.'/'.$row2['exam_id'].'/'.$exam_class_id.'/'.$running_term.'/'.$running_year);?>" onclick="loadExamResults($(this).attr('data-url'))" style="text-align: right;">
               <?php echo get_phrase('results_archives');?>
           </a>
             </div>
           </div>
           <hr/>
           </div> <br><br><br>
            <?php
            //END OF CRECHE EXAMS FOR BASICS
          } else if($exam_class_name == 'BASIC') {
          ?>
          <div class="tile-stats tile-white-gray" style="margin-top: 20px;">
            <div class="col-md-8 col-lg-8 col-sm-8 col-xs-8"><h3><?php echo $this->crud_model->get_exams_name($row2['exam_id']).'-'.$full_class_name; ?></h3></div>
            <div class="col-md-4 col-lg-4 col-sm-4 col-xs-4"><h3 style="text-align: right;"><?php echo get_phrase('term:').' '.$running_term. ' | '.get_phrase('year:').' '.explode('-', $running_year)[1]; ?></h3></div>
          </div>
          <table class="table table-bordered table-responsive table-striped table-hover table-active">
                       <thead>
                        <tr>
                            <th style="text-align: center; font-weight: bold;">S/N</th>
                            <th style="text-align: center; font-weight: bold;">SUBJECT</th>
                            <th style="text-align: center; font-weight: bold;">CLASS SCORE</th>
                            <th style="text-align: center; font-weight: bold;">EXAM SCORE</th>
                            <th style="text-align: center; font-weight: bold;">TOTAL SCORE</th>
                            <th style="text-align: center; font-weight: bold;">GRADE</th>
                            <th style="text-align: center; font-weight: bold;">REMARK</th>
                            <th style="text-align: center; font-weight: bold;">POSITION</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php
                            $class_score_total = 0;
                            $exam_score_total =0; 
                            $total_marks = 0;
                            $total_grade_point = 0;
                            $subjects = $this->db->get_where('subject' , array(
                                'class_id' => $exam_class_id , 'year' => $running_year, 'term' => $running_term
                            ))->result_array();

                            $i = 1;
                            foreach ($subjects as $row3):
                        ?>
                            <tr>
                                <td style="text-align: center;"><?php echo $i;?></td>
                                <td><?php if(strlen($row3['name']) <= 4) {
                                    echo strtoupper($row3['name']);
                                }else{ 
                                    echo $row3['name'];
                                };?></td>
                                 <td style="text-align: center;">
                                    <?php
                                        $class_score_query = $this->db->get_where('mark' , array(
                                                    'subject_id' => $row3['subject_id'],
                                                        'exam_id' => $row2['exam_id'],
                                                            'class_id' => $exam_class_id,
                                                                'student_id' => $student_id , 
                                                                    'year' => $running_year,
                                                                        'term' => $running_term));
                                        if ( $class_score_query->num_rows() > 0) {
                                            $class_score = $class_score_query->result_array();
                                            foreach ($class_score as $row4) {
                                                echo $row4['class_score'];
                                                $class_score_total += $row4['class_score'];
                                                $total_marks += $row4['class_score'];
                                            }
                                        }else{
                                          echo "N/A";
                                        }
                                    ?>
                                </td>
                                 <td style="text-align: center;">
                                    <?php
                                        $exam_score_query = $this->db->get_where('mark' , array(
                                                    'subject_id' => $row3['subject_id'],
                                                        'exam_id' => $row2['exam_id'],
                                                            'class_id' => $exam_class_id,
                                                                'student_id' => $student_id , 
                                                                    'year' => $running_year,
                                                                        'term' => $running_term));
                                        if ( $exam_score_query->num_rows() > 0) {
                                            $exam_score = $exam_score_query->result_array();
                                            foreach ($exam_score as $row4) {
                                                echo $row4['exam_score'];
                                                $exam_score_total += $row4['exam_score'];
                                                $total_marks += $row4['exam_score'];
                                            }
                                        }else{
                                          echo "N/A";
                                        }
                                    ?>
                                </td>
                                <td style="text-align: center;">
                                    <?php
                                        $obtained_mark_query = $this->db->get_where('mark' , array(
                                                    'subject_id' => $row3['subject_id'],
                                                        'exam_id' => $row2['exam_id'],
                                                            'class_id' => $exam_class_id,
                                                                'student_id' => $student_id , 
                                                                    'year' => $running_year,
                                                                        'term' => $running_term));
                                        if ( $obtained_mark_query->num_rows() > 0) {
                                            $marks = $obtained_mark_query->result_array();
                                            foreach ($marks as $row4) {
                                                echo $row4['mark_obtained'];
                                                $total_marks;
                                            }
                                        }else{
                                          echo "N/A";
                                        }
                                    ?>
                                </td>
                               <!-- <td style="text-align: center;">
                                    <?php

                                    $highest_mark = $this->crud_model->get_highest_marks( $row2['exam_id'] , $exam_class_id , $row3['subject_id'] );
                                    echo $highest_mark;


        
                                    ?>
                                </td>. //take highest mark column out-->
                                <td style="text-align: center;">
                                    <?php
                                        if($obtained_mark_query->num_rows() > 0) {
                                            if ($row4['mark_obtained'] >= 0 || $row4['mark_obtained'] != '') {
                                                $grade = $this->crud_model->get_grade($row4['mark_obtained']);
                                                echo $grade['grade_point'];
                                               /** $total_grade_point += $grade['grade_point'];No grade point**/
                                            }
                                        }else{
                                          echo "N/A";
                                        }
                                    ?>
                                </td>
                                <td style="text-align: center;">
                                    <?php
                                        if($obtained_mark_query->num_rows() > 0) {
                                            if ($row4['mark_obtained'] >= 0 || $row4['mark_obtained'] != '') {
                                                $grade = $this->crud_model->get_grade($row4['mark_obtained']);
                                                echo $grade['name'];
                                                /**$total_grade_point += $grade['grade_point'];==No grade point**/
                                            }
                                        }else{
                                          echo "N/A";
                                        }
                                    ?>
                                </td>
                                <td style="text-align: center;">
                                <?php
                                 $this->crud_model->get_total_score($row2['exam_id'] , $exam_class_id , $row3['subject_id'], $row['student_id'], $running_year, $running_term);
                                 ?>
                                </td>
                              <!--  <td style="text-align: center;">
                                    <?php // if($obtained_mark_query->num_rows() > 0) 
                                            //echo $row4['comment'];
                                    ?>
                                </td>.//take comment column out-->
                            </tr>
                        <?php 
                        $i++;
                        endforeach;?>

                        <tr>
                            <th colspan="2" style="text-align: center;">TOTAL</th>
                            <td style="text-align: center; font-weight: bold;"><?php echo $class_score_total?$class_score_total:'N/A'; ?></td>
                            <td style="text-align: center; font-weight: bold;"><?php echo $exam_score_total?$exam_score_total:'N/A'; ?></td>
                            <td style="text-align: center; font-weight: bold;"><?php echo $total_marks?$total_marks:'N/A'; ?></td>
                            <td colspan="3"></td>
                        </tr>
                    </tbody>
                   </table>
             <br>
           <div class="row">
             <div class="col-md-3 col-lg-3 col-sm-3 col-xs-3">
               <a href="<?php echo site_url('admin/student_marksheet_print_view/'.$student_id.'/'.$row2['exam_id']);?>"
               class="btn btn-primary" target="_blank">
               <?php echo get_phrase('print_marksheet');?>
           </a>
             </div>
             <div class="col-md-6 col-lg-6 col-sm-6 col-xs-6"></div>
             <div class="col-md-3 col-lg-3 col-sm-3 col-xs-3">
              <div class="col-md-3 col-lg-3 col-sm-3 col-xs-3">
               <a href="#" data-url="<?php echo site_url('admin/student_results_sheet/'.$student_id.'/'.$row2['exam_id'].'/'.$exam_class_id.'/'.$running_term.'/'.$running_year);?>" class="btn btn-info" onclick="loadExamResults($(this).attr('data-url'))" target="_self" style="text-align: right;">
               <?php echo get_phrase('results_archives');?>
           </a>
             </div>
           </div>
           <hr/>
           </div> <br><br><br>

           <!--END OF BASIC SCHOOL RESULTS-->
        <?php } else {

          ?>
          <div class="tile-stats tile-white-gray" style="margin-top: 20px;">
            <div class="col-md-8 col-lg-8 col-sm-8 col-xs-8"><h3><?php echo $this->crud_model->get_exams_name($row2['exam_id']).'-'.$full_class_name; ?></h3></div>
            <div class="col-md-4 col-lg-4 col-sm-4 col-xs-4"><h3 style="text-align: right;"><?php echo get_phrase('semester:').' '.$running_sem. ' | '.get_phrase('year:').' '.explode('-', $running_year)[1]; ?></h3></div>
          </div>
          <table class="table table-bordered table-responsive table-striped table-hover table-active">
                       <thead>
                        <tr>
                            <th style="text-align: center; font-weight: bold;">S/N</th>
                            <th style="text-align: center; font-weight: bold;">SUBJECT</th>
                            <th style="text-align: center; font-weight: bold;">CLASS SCORE</th>
                            <th style="text-align: center; font-weight: bold;">EXAM SCORE</th>
                            <th style="text-align: center; font-weight: bold;">TOTAL SCORE</th>
                            <th style="text-align: center; font-weight: bold;">GRADE</th>
                            <th style="text-align: center; font-weight: bold;">REMARK</th>
                            <th style="text-align: center; font-weight: bold;">POSITION</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php
                            $class_score_total = 0;
                            $exam_score_total =0; 
                            $total_marks = 0;
                            $total_grade_point = 0;
                            $subjects = $this->db->get_where('subject' , array(
                                'class_id' => $exam_class_id , 'year' => $running_year, 'sem' => $running_sem
                            ))->result_array();

                            $i = 1;
                            foreach ($subjects as $row3):
                        ?>
                            <tr>
                                <td style="text-align: center;"><?php echo $i;?></td>
                                <td><?php if(strlen($row3['name']) <= 4) {
                                    echo strtoupper($row3['name']);
                                }else{ 
                                    echo $row3['name'];
                                };?></td>
                                 <td style="text-align: center;">
                                    <?php
                                        $class_score_query = $this->db->get_where('mark' , array(
                                                    'subject_id' => $row3['subject_id'],
                                                        'exam_id' => $row2['exam_id'],
                                                            'class_id' => $exam_class_id,
                                                                'student_id' => $student_id , 
                                                                    'year' => $running_year,
                                                                        'sem' => $running_sem));
                                        if ( $class_score_query->num_rows() > 0) {
                                            $class_score = $class_score_query->result_array();
                                            foreach ($class_score as $row4) {
                                                echo $row4['class_score'];
                                                $class_score_total += $row4['class_score'];
                                                $total_marks += $row4['class_score'];
                                            }
                                        }else{
                                          echo "N/A";
                                        }
                                    ?>
                                </td>
                                 <td style="text-align: center;">
                                    <?php
                                        $exam_score_query = $this->db->get_where('mark' , array(
                                                    'subject_id' => $row3['subject_id'],
                                                        'exam_id' => $row2['exam_id'],
                                                            'class_id' => $exam_class_id,
                                                                'student_id' => $student_id , 
                                                                    'year' => $running_year,
                                                                        'sem' => $running_sem));
                                        if ( $exam_score_query->num_rows() > 0) {
                                            $exam_score = $exam_score_query->result_array();
                                            foreach ($exam_score as $row4) {
                                                echo $row4['exam_score'];
                                                $exam_score_total += $row4['exam_score'];
                                                $total_marks += $row4['exam_score'];
                                            }
                                        }else{
                                          echo "N/A";
                                        }
                                    ?>
                                </td>
                                <td style="text-align: center;">
                                    <?php
                                        $obtained_mark_query = $this->db->get_where('mark' , array(
                                                    'subject_id' => $row3['subject_id'],
                                                        'exam_id' => $row2['exam_id'],
                                                            'class_id' => $exam_class_id,
                                                                'student_id' => $student_id , 
                                                                    'year' => $running_year,
                                                                        'sem' => $running_sem));
                                        if ( $obtained_mark_query->num_rows() > 0) {
                                            $marks = $obtained_mark_query->result_array();
                                            foreach ($marks as $row4) {
                                                echo $row4['mark_obtained'];
                                                $total_marks;
                                            }
                                        }else{
                                          echo "N/A";
                                        }
                                    ?>
                                </td>
                               <!-- <td style="text-align: center;">
                                    <?php

                                    $highest_mark = $this->crud_model->get_highest_marks( $row2['exam_id'] , $exam_class_id , $row3['subject_id'] );
                                    echo $highest_mark;


        
                                    ?>
                                </td>. //take highest mark column out-->
                                <td style="text-align: center;">
                                    <?php
                                        if($obtained_mark_query->num_rows() > 0) {
                                            if ($row4['mark_obtained'] >= 0 || $row4['mark_obtained'] != '') {
                                                $grade = $this->crud_model->get_grade($row4['mark_obtained']);
                                                echo $grade['grade_point'];
                                               /** $total_grade_point += $grade['grade_point'];No grade point**/
                                            }
                                        }else{
                                          echo "N/A";
                                        }
                                    ?>
                                </td>
                                <td style="text-align: center;">
                                    <?php
                                        if($obtained_mark_query->num_rows() > 0) {
                                            if ($row4['mark_obtained'] >= 0 || $row4['mark_obtained'] != '') {
                                                $grade = $this->crud_model->get_grade($row4['mark_obtained']);
                                                echo $grade['name'];
                                                /**$total_grade_point += $grade['grade_point'];==No grade point**/
                                            }
                                        }else{
                                          echo "N/A";
                                        }
                                    ?>
                                </td>
                                <td style="text-align: center;">
                                <?php
                                 $this->crud_model->get_total_score($row2['exam_id'] , $exam_class_id , $row3['subject_id'], $row['student_id'], $running_year, $running_sem);
                                 ?>
                                </td>
                              <!--  <td style="text-align: center;">
                                    <?php // if($obtained_mark_query->num_rows() > 0) 
                                            //echo $row4['comment'];
                                    ?>
                                </td>.//take comment column out-->
                            </tr>
                        <?php 
                        $i++;
                        endforeach;?>

                        <tr>
                            <th colspan="2" style="text-align: center;">TOTAL</th>
                            <td style="text-align: center; font-weight: bold;"><?php echo $class_score_total?$class_score_total:'N/A'; ?></td>
                            <td style="text-align: center; font-weight: bold;"><?php echo $exam_score_total?$exam_score_total:'N/A'; ?></td>
                            <td style="text-align: center; font-weight: bold;"><?php echo $total_marks?$total_marks:'N/A'; ?></td>
                            <td colspan="3"></td>
                        </tr>
                    </tbody>
                   </table>
          <br>
           <div class="row">
             <div class="col-md-3 col-lg-3 col-sm-3 col-xs-3">
               <a href="<?php echo site_url('admin/student_marksheet_print_view/'.$student_id.'/'.$row2['exam_id']);?>"
               class="btn btn-primary" target="_blank">
               <?php echo get_phrase('print_marksheet');?>
           </a>
             </div>
             <div class="col-md-6 col-lg-6 col-sm-6 col-xs-6"></div>
             <div class="col-md-3 col-lg-3 col-sm-3 col-xs-3">
              <div class="col-md-3 col-lg-3 col-sm-3 col-xs-3">
               <a href="#" data-url="<?php echo site_url('admin/student_results_sheet/'.$student_id.'/'.$row2['exam_id'].'/'.$exam_class_id.'/'.$running_sem.'/'.$running_year);?>" class="btn btn-info" target="_self" onclick="loadExamResults($(this).attr('data-url'))" style="text-align: right;">
               <?php echo get_phrase('results_archives');?>
           </a>
             </div>
           </div>
           <hr/>
           </div> <br><br><br>
        <?php } 
        //END OF JHS RESULTS
        } ?>
        <?php } ?>
      </div>
            <?php
          } //end of JHS

          //for the general ones....
          else {
            ?>
            <div class="tab-pane-modern" id="tab3" style="display:none;">
              <div class="row" style="margin-bottom: 20px;">
                <div class="col-md-8 col-lg-8 col-sm-8 col-xs-8"></div>
                <div class="col-md-4 col-lg-4 col-sm-4 col-xs-4">
                  <a class="btn btn-success btn-lg" href="<?php echo site_url('admin/cummulative_reports/' .$student_id) ?>" target="_blank">Grade Points Reports</a>
                </div>
              </div>
        <?php if(empty($exams)) { ?>
          <div class="profile-card p-12 text-center">
            <i class="entypo-graduation-cap text-6xl text-gray-300 mb-4"></i>
            <p class="text-xl text-gray-500"><?php echo get_phrase('no_examinations_found'); ?></p>
          </div>
        <?php } else { ?>
        <?php foreach ($exams as $row2) { 
          $running_year = $this->crud_model->get_exams_year($row2['exam_id']);
          $running_term = $this->crud_model->get_exams_term($row2['exam_id']);

          $exam_class_id = $this->crud_model->get_exams_class_id($row2['exam_id'], $student_id);
          $exam_class_numeric = $this->crud_model->get_class_name_numeric($exam_class_id);
          $exam_class_name = $this->crud_model->get_class_name($exam_class_id);

          $exam_sec_name = $this->crud_model->get_class_section($exam_class_id);
          $full_class_name = $exam_class_name.' '.$exam_class_numeric.$exam_sec_name;

          //INCASE THIS STUDENT WAS IN CRECHE BEFORE
          if($exam_class_name == 'CRECHE') {
            ?>
              <div class="profile-card" style="margin-top: 20px; padding: 16px; display: flex; align-items: center; justify-content: space-between; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 12px;">
            <div style="display: flex; align-items: center; gap: 12px;">
              <i class="fa fa-graduation-cap" style="font-size: 24px; opacity: 0.8;"></i>
              <h3 style="margin: 0; font-size: 18px; font-weight: 600;"><?php echo $this->crud_model->get_exams_name($row2['exam_id']).'-'.$full_class_name; ?></h3>
            </div>
            <div style="text-align: right; font-size: 14px; opacity: 0.9;">
              <span><?php echo get_phrase('term:').' '.$running_term. ' | '.get_phrase('year:').' '.explode('-', $running_year)[1]; ?></span>
            </div>
          </div>
            <div class="col-md-8 col-lg-8 col-sm-8 col-xs-8"><h3><?php echo $this->crud_model->get_exams_name($row2['exam_id']).'-'.$full_class_name; ?></h3></div>
            <div class="col-md-4 col-lg-4 col-sm-4 col-xs-4"><h3 style="text-align: right;"><?php echo get_phrase('term:').' '.$running_term. ' | '.get_phrase('year:').' '.explode('-', $running_year)[1]; ?></h3></div>
          </div>

          <div class="row">
            <div style="text-align: center">
              <table class="table">
                <thead>
                  <?php 
                    $grading_sys = $this->db->get('grade_creche')->result_array();
                    foreach($grading_sys as $grade): 
                  ?>
                  <tr>
                    <th style="font-size: 16px"><?= $grade['abbrev']; ?></th>
                    <th style="font-size: 16px">-</th>
                    <th style="font-size: 16px"><?= $grade['full_name']; ?></th>
                  </tr>
                <?php endforeach; ?>
                </thead>
              </table>
            </div>
          </div>

           <?php 
              $subj_category = $this->db->get('subject_category_creche')->result_array();
              foreach($subj_category as $cat): ?>

              <div class="row">
                  <table class="table table-bordered">
                    <thead>   
                      <tr>
                        <th><?= $cat['name']; ?></th>
                        <th width="80"><?= 'GRADING'; ?></th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php 
                      $subjects_creche = $this->db->get_where('subject_creche' , array(
                            'class_id' => $exam_class_id , 'category_id' => $cat['category_id'], 'year' => $running_year, 'term' => $running_term
                        ))->result_array();
                        foreach($subjects_creche as $subj): ?>
                    <tr>
                      <td><?= $subj['name']; ?></td>
                      <td style="letter-spacing: 5px;">
                        <?php 
                            $grading = $this->db->get_where('mark' , array(
                                                    'subject_id' => $subj['subject_id'],
                                                        'exam_id' => $row2['exam_id'],
                                                            'class_id' => $exam_class_id,
                                                                'student_id' => $student_id , 
                                                                    'year' => $running_year,
                                                                        'term' => $running_term));
                                        if ( $grading->num_rows() > 0) {
                                            $grading_array = $grading->result_array();
                                            foreach ($grading_array as $row4) {
                                                echo $this->db->get_where('grade_creche', array('grade_id' => $row4['test1']))->row()->abbrev;
                                            }
                                        }else{
                                          echo "Pending...";
                                        }
                          ?>
                      </td>
                    </tr>

                  <?php endforeach; ?>
                    </tbody>
                    
                  </table>
              </div>

          <?php endforeach; ?>

      <?php 
      if($this->db->get_where('subject_creche' , array('class_id' => $exam_class_id , 'category_id' => '0', 'year' => $running_year, 'term' => $running_term))->num_rows() > 0) {
      ?>

          <table class="table table-bordered table-responsive table-striped table-hover table-active">
                       <thead>
                        <tr>
                            <th style="text-align: center; font-weight: bold;">S/N</th>
                            <th style="text-align: center; font-weight: bold;">SUBJECT</th>
                            <th style="text-align: center; font-weight: bold;">CLASS SCORE</th>
                            <th style="text-align: center; font-weight: bold;">EXAM SCORE</th>
                            <th style="text-align: center; font-weight: bold;">TOTAL SCORE</th>
                            <th style="text-align: center; font-weight: bold;">GRADE</th>
                            <th style="text-align: center; font-weight: bold;">REMARK</th>
                            <th style="text-align: center; font-weight: bold;">POSITION</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php
                            $class_score_total = 0;
                            $exam_score_total =0; 
                            $total_marks = 0;
                            $total_grade_point = 0;
                            $subjects = $this->db->get_where('subject_creche' , array(
                                'class_id' => $exam_class_id , 'category_id' => '0', 'year' => $running_year, 'term' => $running_term
                            ))->result_array();

                            $i = 1;
                            foreach ($subjects as $row3):
                        ?>
                            <tr>
                                <td style="text-align: center;"><?php echo $i;?></td>
                                <td><?php if(strlen($row3['name']) <= 4) {
                                    echo strtoupper($row3['name']);
                                }else{ 
                                    echo $row3['name'];
                                };?></td>
                                 <td style="text-align: center;">
                                    <?php
                                        $class_score_query = $this->db->get_where('mark' , array(
                                                    'subject_id' => $row3['subject_id'],
                                                        'exam_id' => $row2['exam_id'],
                                                            'class_id' => $exam_class_id,
                                                                'student_id' => $student_id , 
                                                                    'year' => $running_year,
                                                                        'term' => $running_term));
                                        if ( $class_score_query->num_rows() > 0) {
                                            $class_score = $class_score_query->result_array();
                                            foreach ($class_score as $row4) {
                                                echo $row4['class_score'];
                                                $class_score_total += $row4['class_score'];
                                                $total_marks += $row4['class_score'];
                                            }
                                        }else{
                                          echo "N/A";
                                        }
                                    ?>
                                </td>
                                 <td style="text-align: center;">
                                    <?php
                                        $exam_score_query = $this->db->get_where('mark' , array(
                                                    'subject_id' => $row3['subject_id'],
                                                        'exam_id' => $row2['exam_id'],
                                                            'class_id' => $exam_class_id,
                                                                'student_id' => $student_id , 
                                                                    'year' => $running_year,
                                                                        'term' => $running_term));
                                        if ( $exam_score_query->num_rows() > 0) {
                                            $exam_score = $exam_score_query->result_array();
                                            foreach ($exam_score as $row4) {
                                                echo $row4['exam_score'];
                                                $exam_score_total += $row4['exam_score'];
                                                $total_marks += $row4['exam_score'];
                                            }
                                        }else{
                                          echo "N/A";
                                        }
                                    ?>
                                </td>
                                <td style="text-align: center;">
                                    <?php
                                        $obtained_mark_query = $this->db->get_where('mark' , array(
                                                    'subject_id' => $row3['subject_id'],
                                                        'exam_id' => $row2['exam_id'],
                                                            'class_id' => $exam_class_id,
                                                                'student_id' => $student_id , 
                                                                    'year' => $running_year,
                                                                        'term' => $running_term));
                                        if ( $obtained_mark_query->num_rows() > 0) {
                                            $marks = $obtained_mark_query->result_array();
                                            foreach ($marks as $row4) {
                                                echo $row4['mark_obtained'];
                                                $total_marks;
                                            }
                                        }else{
                                          echo "N/A";
                                        }
                                    ?>
                                </td>
                               <!-- <td style="text-align: center;">
                                    <?php

                                    $highest_mark = $this->crud_model->get_highest_marks( $row2['exam_id'] , $exam_class_id , $row3['subject_id'] );
                                    echo $highest_mark;


        
                                    ?>
                                </td>. //take highest mark column out-->
                                <td style="text-align: center;">
                                    <?php
                                        if($obtained_mark_query->num_rows() > 0) {
                                            if ($row4['mark_obtained'] >= 0 || $row4['mark_obtained'] != '') {
                                                $grade = $this->crud_model->get_grade($row4['mark_obtained']);
                                                echo $grade['grade_point'];
                                               /** $total_grade_point += $grade['grade_point'];No grade point**/
                                            }
                                        }else{
                                          echo "N/A";
                                        }
                                    ?>
                                </td>
                                <td style="text-align: center;">
                                    <?php
                                        if($obtained_mark_query->num_rows() > 0) {
                                            if ($row4['mark_obtained'] >= 0 || $row4['mark_obtained'] != '') {
                                                $grade = $this->crud_model->get_grade($row4['mark_obtained']);
                                                echo $grade['name'];
                                                /**$total_grade_point += $grade['grade_point'];==No grade point**/
                                            }
                                        }else{
                                          echo "N/A";
                                        }
                                    ?>
                                </td>
                                <td style="text-align: center;">
                                <?php
                                 $this->crud_model->get_total_score($row2['exam_id'] , $exam_class_id , $row3['subject_id'], $row['student_id'], $running_year, $running_term);
                                 ?>
                                </td>
                              <!--  <td style="text-align: center;">
                                    <?php // if($obtained_mark_query->num_rows() > 0) 
                                            //echo $row4['comment'];
                                    ?>
                                </td>.//take comment column out-->
                            </tr>
                        <?php 
                        $i++;
                        endforeach;?>

                        <tr>
                            <th colspan="2" style="text-align: center;">TOTAL</th>
                            <td style="text-align: center; font-weight: bold;"><?php echo $class_score_total?$class_score_total:'N/A'; ?></td>
                            <td style="text-align: center; font-weight: bold;"><?php echo $exam_score_total?$exam_score_total:'N/A'; ?></td>
                            <td style="text-align: center; font-weight: bold;"><?php echo $total_marks?$total_marks:'N/A'; ?></td>
                            <td colspan="3"></td>
                        </tr>
                    </tbody>
                   </table><br>

                 <?php } ?>

           <div class="row">
             <div class="col-md-3 col-lg-3 col-sm-3 col-xs-3">
               <a href="<?php echo site_url('admin/student_marksheet_print_view_creche/'.$student_id.'/'.$row2['exam_id']);?>"
               class="btn btn-primary" target="_blank">
               <?php echo get_phrase('print_marksheet');?>
           </a>
             </div>
             <div class="col-md-6 col-lg-6 col-sm-6 col-xs-6"></div>
             <div class="col-md-3 col-lg-3 col-sm-3 col-xs-3">
              <div class="col-md-3 col-lg-3 col-sm-3 col-xs-3">
               <a href="#"
               class="btn btn-info" target="_self" data-url="<?php echo site_url('admin/student_results_sheet_creche/'.$student_id.'/'.$row2['exam_id'].'/'.$exam_class_id.'/'.$running_term.'/'.$running_year);?>" onclick="loadExamResults($(this).attr('data-url'))" style="text-align: right;">
               <?php echo get_phrase('results_archives');?>
           </a>
             </div>
           </div>
           <hr/>
           </div> <br><br><br>
            <?php
            //END OF CRECHE EXAMS FOR BASICS
          } else {
          ?>
          <div class="tile-stats tile-white-gray" style="margin-top: 20px;">
            <div class="col-md-8 col-lg-8 col-sm-8 col-xs-8"><h3><?php echo $this->crud_model->get_exams_name($row2['exam_id']).'-'.$full_class_name; ?></h3></div>
            <div class="col-md-4 col-lg-4 col-sm-4 col-xs-4"><h3 style="text-align: right;"><?php echo get_phrase('term:').' '.$running_term. ' | '.get_phrase('year:').' '.explode('-', $running_year)[1]; ?></h3></div>
          </div>
          <table class="table table-bordered table-responsive table-striped table-hover table-active">
                       <thead>
                        <tr>
                            <th style="text-align: center; font-weight: bold;">S/N</th>
                            <th style="text-align: center; font-weight: bold;">SUBJECT</th>
                            <th style="text-align: center; font-weight: bold;">CLASS SCORE</th>
                            <th style="text-align: center; font-weight: bold;">EXAM SCORE</th>
                            <th style="text-align: center; font-weight: bold;">TOTAL SCORE</th>
                            <th style="text-align: center; font-weight: bold;">GRADE</th>
                            <th style="text-align: center; font-weight: bold;">REMARK</th>
                            <th style="text-align: center; font-weight: bold;">POSITION</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php
                            $class_score_total = 0;
                            $exam_score_total =0; 
                            $total_marks = 0;
                            $total_grade_point = 0;
                            $subjects = $this->db->get_where('subject' , array(
                                'class_id' => $exam_class_id , 'year' => $running_year, 'term' => $running_term
                            ))->result_array();

                            $i = 1;
                            foreach ($subjects as $row3):
                        ?>
                            <tr>
                                <td style="text-align: center;"><?php echo $i;?></td>
                                <td><?php if(strlen($row3['name']) <= 4) {
                                    echo strtoupper($row3['name']);
                                }else{ 
                                    echo $row3['name'];
                                };?></td>
                                 <td style="text-align: center;">
                                    <?php
                                        $class_score_query = $this->db->get_where('mark' , array(
                                                    'subject_id' => $row3['subject_id'],
                                                        'exam_id' => $row2['exam_id'],
                                                            'class_id' => $exam_class_id,
                                                                'student_id' => $student_id , 
                                                                    'year' => $running_year,
                                                                        'term' => $running_term));
                                        if ( $class_score_query->num_rows() > 0) {
                                            $class_score = $class_score_query->result_array();
                                            foreach ($class_score as $row4) {
                                                echo $row4['class_score'];
                                                $class_score_total += $row4['class_score'];
                                                $total_marks += $row4['class_score'];
                                            }
                                        }else{
                                          echo "N/A";
                                        }
                                    ?>
                                </td>
                                 <td style="text-align: center;">
                                    <?php
                                        $exam_score_query = $this->db->get_where('mark' , array(
                                                    'subject_id' => $row3['subject_id'],
                                                        'exam_id' => $row2['exam_id'],
                                                            'class_id' => $exam_class_id,
                                                                'student_id' => $student_id , 
                                                                    'year' => $running_year,
                                                                        'term' => $running_term));
                                        if ( $exam_score_query->num_rows() > 0) {
                                            $exam_score = $exam_score_query->result_array();
                                            foreach ($exam_score as $row4) {
                                                echo $row4['exam_score'];
                                                $exam_score_total += $row4['exam_score'];
                                                $total_marks += $row4['exam_score'];
                                            }
                                        }else{
                                          echo "N/A";
                                        }
                                    ?>
                                </td>
                                <td style="text-align: center;">
                                    <?php
                                        $obtained_mark_query = $this->db->get_where('mark' , array(
                                                    'subject_id' => $row3['subject_id'],
                                                        'exam_id' => $row2['exam_id'],
                                                            'class_id' => $exam_class_id,
                                                                'student_id' => $student_id , 
                                                                    'year' => $running_year,
                                                                        'term' => $running_term));
                                        if ( $obtained_mark_query->num_rows() > 0) {
                                            $marks = $obtained_mark_query->result_array();
                                            foreach ($marks as $row4) {
                                                echo $row4['mark_obtained'];
                                                $total_marks;
                                            }
                                        }else{
                                          echo "N/A";
                                        }
                                    ?>
                                </td>
                               <!-- <td style="text-align: center;">
                                    <?php

                                    $highest_mark = $this->crud_model->get_highest_marks( $row2['exam_id'] , $exam_class_id , $row3['subject_id'] );
                                    echo $highest_mark;


        
                                    ?>
                                </td>. //take highest mark column out-->
                                <td style="text-align: center;">
                                    <?php
                                        if($obtained_mark_query->num_rows() > 0) {
                                            if ($row4['mark_obtained'] >= 0 || $row4['mark_obtained'] != '') {
                                                $grade = $this->crud_model->get_grade($row4['mark_obtained']);
                                                echo $grade['grade_point'];
                                               /** $total_grade_point += $grade['grade_point'];No grade point**/
                                            }
                                        }else{
                                          echo "N/A";
                                        }
                                    ?>
                                </td>
                                <td style="text-align: center;">
                                    <?php
                                        if($obtained_mark_query->num_rows() > 0) {
                                            if ($row4['mark_obtained'] >= 0 || $row4['mark_obtained'] != '') {
                                                $grade = $this->crud_model->get_grade($row4['mark_obtained']);
                                                echo $grade['name'];
                                                /**$total_grade_point += $grade['grade_point'];==No grade point**/
                                            }
                                        }else{
                                          echo "N/A";
                                        }
                                    ?>
                                </td>
                                <td style="text-align: center;">
                                <?php
                                 $this->crud_model->get_total_score($row2['exam_id'] , $exam_class_id , $row3['subject_id'], $row['student_id'], $running_year, $running_term);
                                 ?>
                                </td>
                              <!--  <td style="text-align: center;">
                                    <?php // if($obtained_mark_query->num_rows() > 0) 
                                            //echo $row4['comment'];
                                    ?>
                                </td>.//take comment column out-->
                            </tr>
                        <?php 
                        $i++;
                        endforeach;?>

                        <tr>
                            <th colspan="2" style="text-align: center;">TOTAL</th>
                            <td style="text-align: center; font-weight: bold;"><?php echo $class_score_total?$class_score_total:'N/A'; ?></td>
                            <td style="text-align: center; font-weight: bold;"><?php echo $exam_score_total?$exam_score_total:'N/A'; ?></td>
                            <td style="text-align: center; font-weight: bold;"><?php echo $total_marks?$total_marks:'N/A'; ?></td>
                            <td colspan="3"></td>
                        </tr>
                    </tbody>
                   </table>
            <br>
           <div class="row">
             <div class="col-md-3 col-lg-3 col-sm-3 col-xs-3">
               <a href="<?php echo site_url('admin/student_marksheet_print_view/'.$student_id.'/'.$row2['exam_id']);?>"
               class="btn btn-primary" target="_blank">
               <?php echo get_phrase('print_marksheet');?>
           </a>
             </div>
             <div class="col-md-6 col-lg-6 col-sm-6 col-xs-6"></div>
             <div class="col-md-3 col-lg-3 col-sm-3 col-xs-3">
              <div class="col-md-3 col-lg-3 col-sm-3 col-xs-3">
               <a href="#" data-url="<?php echo site_url('admin/student_results_sheet/'.$student_id.'/'.$row2['exam_id'].'/'.$exam_class_id.'/'.$running_term.'/'.$running_year);?>" class="btn btn-info" onclick="loadExamResults($(this).attr('data-url'))" target="_self" style="text-align: right;">
               <?php echo get_phrase('results_archives');?>
           </a>
             </div>
           </div>
           <hr/>
           </div> <br><br><br>
        <?php }
        //END OF BASIC
        } ?>
        <?php } ?>
      </div>
            <?php
          }

      ?>

        <?php if (!isset($is_teacher_view) || !$is_teacher_view): ?>
        <div class="tab-pane-modern" id="tab4" style="display:none;">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="profile-card p-6">
              <div class="flex items-start gap-4">
                <div class="w-12 h-12 rounded-lg bg-blue-100 flex items-center justify-center flex-shrink-0">
                  <i class="entypo-key text-2xl text-blue-600"></i>
                </div>
                <div class="flex-1">
                  <p class="text-sm font-medium text-gray-500 mb-2"><?php echo get_phrase('authentication_key'); ?></p>
                  <p class="text-xl font-bold text-gray-800 tracking-wider font-mono bg-gray-100 px-4 py-2 rounded"><?php echo $row['authentication_key']; ?></p>
                </div>
              </div>
            </div>
            <div class="profile-card p-6">
              <div class="flex items-start gap-4">
                <div class="w-12 h-12 rounded-lg bg-green-100 flex items-center justify-center flex-shrink-0">
                  <i class="entypo-user text-2xl text-green-600"></i>
                </div>
                <div class="flex-1">
                  <p class="text-sm font-medium text-gray-500 mb-2"><?php echo get_phrase('username'); ?></p>
                  <p class="text-xl font-bold text-gray-800"><?php echo $row['username']; ?></p>
                </div>
              </div>
            </div>
            <div class="profile-card p-6 md:col-span-2">
              <div class="flex items-start gap-4">
                <div class="w-12 h-12 rounded-lg bg-red-100 flex items-center justify-center flex-shrink-0">
                  <i class="entypo-lock text-2xl text-red-600"></i>
                </div>
                <div class="flex-1">
                  <p class="text-sm font-medium text-gray-500 mb-2"><?php echo get_phrase('Password'); ?></p>
                  <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded">
                    <p class="text-red-700"><strong>Not Available.</strong> In case of password lost, ask the student to use their email to request a new password.</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <?php endif; ?>

        <?php if (!isset($is_teacher_view) || !$is_teacher_view): ?>
        <div class="tab-pane-modern" id="tab6" style="display:none;">
        <?php
          $this->db->select('invoice_code');
          $this->db->distinct();
          $this->db->where('can_delete !=', 'trash');
          $payables_array = $this->db->get_where('invoice', array(
            'student_id' => $row['student_id'], 'due <' => 0))->result_array();

          $this->db->select('invoice_code');
          $this->db->distinct();
          $this->db->where('can_delete !=', 'trash');
          $receivables_array = $this->db->get_where('invoice', array(
            'student_id' => $row['student_id'], 'due >' => 0))->result_array();


          // Get wallet balances for daily fees
          $wallet = $this->db->get_where('daily_fee_wallet', array('student_id' => $row['student_id']))->row();
          
          if($wallet) {
              $tf_owe = max(0, $wallet->feeding_arrears);
              $tf_refund = max(0, $wallet->feeding_balance) * -1;
              $tc_owe = max(0, $wallet->classes_arrears);
              $tc_refund = max(0, $wallet->classes_balance) * -1;
              $tb_owe = max(0, $wallet->breakfast_arrears ?? 0);
              $tb_refund = max(0, $wallet->breakfast_balance ?? 0) * -1;
              $tw_owe = max(0, $wallet->water_arrears ?? 0);
              $tw_refund = max(0, $wallet->water_balance ?? 0) * -1;
          } else {
              $tf_owe = 0;
              $tf_refund = 0;
              $tc_owe = 0;
              $tc_refund = 0;
              $tb_owe = 0;
              $tb_refund = 0;
              $tw_owe = 0;
              $tw_refund = 0;
          }

            //if($amount > 0) {
            //transport owe
            // $this->db->select_sum('due');
            // $this->db->from('transport_fare');
            // $this->db->where('due >', 0);
            // $this->db->where('student_id', $row['student_id']);
            // $this->db->where('timestamp', $timestamp);
            // $tt_owe = $this->db->get()->row()->due;
        
            // $this->db->select_sum('due');
            // $this->db->from('transport_fare');
            // $this->db->where('due <', 0);
            // $this->db->where('student_id', $row['student_id']);
            // $this->db->where('timestamp', $timestamp);
            // $tt_refund = $this->db->get()->row()->due;
            $tt_owe = 0;
            $tt_refund = 0;

        
                                    
         ?>

         <!-- Start of accounts receivables -->
          <div class="row mt-16">
            <div class="grid grid-cols-1">
              <section class="px-2">
                    <div class="font-bold text-2xl text-gray-500 text-right">ACCOUNTS RECEIVABLES</div>
                    <div class="font-extrabold text-2xl text-gray-600 text-right" id="total_receivables"></div>
                    <div class="flex flex-col p-5 w-full max-w-full border-t-8 bg-white shadow-md border border-t-green-500 rounded-xl">
                        <table class="w-full text-lg text-left text-gray-500 dark:text-gray-400 datatable" id="receivables">
                            <thead class="text-lg text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">

                                <tr>
                                  <th scope="col" class="px-4 py-3">DATE</th>
                                  <th scope="col" class="px-4 py-3" style="text-align: left !important">INVOICE#</th>
                                  <th scope="col" class="px-4 py-3" style="text-align: right !important">AMOUNT</th>
                                  <th scope="col" class="px-4 py-3 text-right action_column" style="text-align: right; !important">ACTION</th>

                                </tr>
                              </thead>
                              <tfoot class="text-lg text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                <tr>
                                  
                                  <th scope="col" class="px-4 py-3">DATE</th>
                                  <th scope="col" class="px-4 py-3" style="text-align: left !important">INVOICE#</th>
                                  <th scope="col" class="px-4 py-3" style="text-align: right !important">AMOUNT</th>
                                  <th scope="col" class="px-4 py-3 text-right" style="text-align: right; !important">ACTION</th>

                                </tr>
                            </tfoot>
                            <tbody>
                         <?php
                            $count = 1;
                            foreach ($receivables_array as $rec):

                              $title = $this->db->get_where('invoice', array('invoice_code' => $rec['invoice_code'], 'student_id' => $row['student_id'], 'due >' => 0))->row()->title;
                              $this->db->select_sum('due');
                              $amount_rec = $this->db->get_where('invoice', array('invoice_code' => $rec['invoice_code'], 'student_id' => $row['student_id'], 'due >' => 0))->row()->due;
                              $rdate = $this->db->get_where('invoice', array('invoice_code' => $rec['invoice_code'], 'student_id' => $row['student_id'], 'due >' => 0))->row()->creation_timestamp;
                          ?>
                            <tr class="border-b dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700 text-xl font-semibold">
                              <td class="px-4 py-3 uppercase"><?php echo date('d M Y', $rdate); ?></td>
                              <td class="px-4 py-3"><?php echo $rec['invoice_code']; ?></td>
                              <td class="px-4 py-3" align="right"><strong><?php echo numfmt_format_currency($fmt, $amount_rec, $currency); ?></strong></td>
                              <td class="px-4 py-3" align="right">
                                  <a href="#" class="btn btn-success rounded-lg h-16 content-center" onclick="invoice_pay_modal('<?=$row['student_id'] ?>')" style="color: #d803f8;"><i class="entypo-bookmarks"></i>&nbsp;Take Payment</a>
                              </td>
                            </tr>
                        <?php endforeach;

                          if($tf_owe != 0) {
                            // Get fee collection mode to determine payment link
                            $collection_mode = get_settings('daily_fee_collection_mode') ?: 'classroom';
                            
                            // Determine payment URL based on collection mode
                            if ($collection_mode == 'cashier') {
                              // Cashier only - go to cashier portal
                              $payment_url = site_url('fee_collection/collect?student_id=' . $row['student_id'] . '&fee_type=feeding');
                            } else {
                              // Classroom or Hybrid - go to new attendance page
                              $payment_url = site_url('attendance/mark?class_id=' . $class_id . '&date=' . date('Y-m-d') . '&student_id=' . $row['student_id']);
                            }
                         ?>

                            <tr class="border-b dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700 text-xl font-semibold">
                              <td class="px-4 py-3 uppercase"><?php echo 'As at today'; ?></td>
                              <td class="px-4 py-3"><?php echo 'FEEDING FEE'; ?></td>
                              <td class="px-4 py-3" align="right"><strong><?php echo numfmt_format_currency($fmt, $tf_owe, $currency); ?></strong></td>
                              
                              <td class="px-4 py-3" align="right">
                                  <a href="<?php echo $payment_url; ?>" class="btn btn-success rounded-lg h-16 content-center" style="color: #d803f8;"><i class="entypo-bookmarks"></i>&nbsp;Take Payment</a>
                              </td>
                            </tr>
                          <?php }

                          if($tc_owe != 0) {
                            // Get fee collection mode to determine payment link
                            $collection_mode = get_settings('daily_fee_collection_mode') ?: 'classroom';
                            
                            // Determine payment URL based on collection mode
                            if ($collection_mode == 'cashier') {
                              // Cashier only - go to cashier portal
                              $payment_url = site_url('fee_collection/collect?student_id=' . $row['student_id'] . '&fee_type=classes');
                            } else {
                              // Classroom or Hybrid - go to new attendance page
                              $payment_url = site_url('attendance/mark?class_id=' . $class_id . '&date=' . date('Y-m-d') . '&student_id=' . $row['student_id']);
                            }
                          ?>
                            <tr class="border-b dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700 text-xl font-semibold">
                              <td class="px-4 py-3 uppercase"><?php echo 'As at today'; ?></td>
                              <td class="px-4 py-3"><?php echo 'CLASSES FEE'; ?></td>
                              <td class="px-4 py-3" align="right"><strong><?php echo numfmt_format_currency($fmt, $tc_owe, $currency); ?></strong></td>
                              
                              <td class="px-4 py-3" align="right">
                                  <a href="<?php echo $payment_url; ?>" class="btn btn-success rounded-lg h-16 content-center" style="color: #d803f8;"><i class="entypo-bookmarks"></i>&nbsp;Take Payment</a>
                              </td>
                            </tr>
                          <?php }

                          if($tt_owe != 0) {
                            // Get fee collection mode to determine payment link
                            $collection_mode = get_settings('daily_fee_collection_mode') ?: 'classroom';
                            
                            // Determine payment URL based on collection mode
                            if ($collection_mode == 'cashier') {
                              // Cashier only - go to cashier portal
                              $payment_url = site_url('fee_collection/collect?student_id=' . $row['student_id'] . '&fee_type=transport');
                            } else {
                              // Classroom or Hybrid - go to new attendance page
                              $payment_url = site_url('attendance/mark?class_id=' . $class_id . '&date=' . date('Y-m-d') . '&student_id=' . $row['student_id']);
                            }
                          ?>
                            <tr class="border-b dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700 text-xl font-semibold">
                              <td class="px-4 py-3 uppercase"><?php echo 'As at today'; ?></td>
                              <td class="px-4 py-3"><?php echo 'TRANSPORT FARE'; ?></td>
                              <td class="px-4 py-3" align="right"><strong><?php echo numfmt_format_currency($fmt, $tt_owe, $currency); ?></strong></td>
                              
                              <td class="px-4 py-3" align="right">
                                  <a href="<?php echo $payment_url; ?>" class="btn btn-success rounded-lg h-16 content-center" style="color: #d803f8;"><i class="entypo-bookmarks"></i>&nbsp;Take Payment</a>
                              </td>
                            </tr>
                            <?php
                              }

                              if($tb_owe != 0) {
                                // Get fee collection mode to determine payment link
                                $collection_mode = get_settings('daily_fee_collection_mode') ?: 'classroom';
                                
                                // Determine payment URL based on collection mode
                                if ($collection_mode == 'cashier') {
                                  // Cashier only - go to cashier portal
                                  $payment_url = site_url('fee_collection/collect?student_id=' . $row['student_id'] . '&fee_type=breakfast');
                                } else {
                                  // Classroom or Hybrid - go to new attendance page
                                  $payment_url = site_url('attendance/mark?class_id=' . $class_id . '&date=' . date('Y-m-d') . '&student_id=' . $row['student_id']);
                                }
                              ?>
                                <tr class="border-b dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700 text-xl font-semibold">
                                  <td class="px-4 py-3 uppercase"><?php echo 'As at today'; ?></td>
                                  <td class="px-4 py-3"><?php echo 'BREAKFAST FEE'; ?></td>
                                  <td class="px-4 py-3" align="right"><strong><?php echo numfmt_format_currency($fmt, $tb_owe, $currency); ?></strong></td>
                                  
                                  <td class="px-4 py-3" align="right">
                                      <a href="<?php echo $payment_url; ?>" class="btn btn-success rounded-lg h-16 content-center" style="color: #d803f8;"><i class="entypo-bookmarks"></i>&nbsp;Take Payment</a>
                                  </td>
                                </tr>
                              <?php }

                              if($tw_owe != 0) {
                                // Get fee collection mode to determine payment link
                                $collection_mode = get_settings('daily_fee_collection_mode') ?: 'classroom';
                                
                                // Determine payment URL based on collection mode
                                if ($collection_mode == 'cashier') {
                                  // Cashier only - go to cashier portal
                                  $payment_url = site_url('fee_collection/collect?student_id=' . $row['student_id'] . '&fee_type=water');
                                } else {
                                  // Classroom or Hybrid - go to new attendance page
                                  $payment_url = site_url('attendance/mark?class_id=' . $class_id . '&date=' . date('Y-m-d') . '&student_id=' . $row['student_id']);
                                }
                              ?>
                                <tr class="border-b dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700 text-xl font-semibold">
                                  <td class="px-4 py-3 uppercase"><?php echo 'As at today'; ?></td>
                                  <td class="px-4 py-3"><?php echo 'WATER FEE'; ?></td>
                                  <td class="px-4 py-3" align="right"><strong><?php echo numfmt_format_currency($fmt, $tw_owe, $currency); ?></strong></td>
                                  
                                  <td class="px-4 py-3" align="right">
                                      <a href="<?php echo $payment_url; ?>" class="btn btn-success rounded-lg h-16 content-center" style="color: #d803f8;"><i class="entypo-bookmarks"></i>&nbsp;Take Payment</a>
                                  </td>
                                </tr>
                            <?php
                              }
                            ?>
                       </tbody>
                   </table>
                 </div>
              </section>
            </div>
          </div>

           <hr>

         <!-- Start of accounts payables -->
         <div class="row mt-16">
            <div class="grid grid-cols-1">
                <section class="px-2">
                    <div class="font-bold text-2xl text-gray-500 text-right">ACCOUNTS PAYABLES</div>
                    <div class="font-extrabold text-2xl text-gray-600 text-right" id="total_payables"></div>
                    <div class="flex flex-col p-5 w-full max-w-full border-t-8 bg-white shadow-md border border-t-red-500 rounded-xl">
                        <table class="w-full text-lg text-left text-gray-500 dark:text-gray-400 datatable" id="payables">
                            <thead class="text-lg text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">

                                <tr>
                                  <th scope="col" class="px-4 py-3">DATE</th>
                                  <th scope="col" class="px-4 py-3" style="text-align: left !important">INVOICE#</th>
                                  <th scope="col" class="px-4 py-3" style="text-align: right !important">AMOUNT</th>
                                  <th scope="col" class="px-4 py-3 text-right action_column" style="text-align: right; !important">ACTION</th>

                                </tr>
                            </thead>

                            <tfoot class="text-lg text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                <tr>
                                  
                                  <th scope="col" class="px-4 py-3">DATE</th>
                                  <th scope="col" class="px-4 py-3" style="text-align: left !important">INVOICE#</th>
                                  <th scope="col" class="px-4 py-3" style="text-align: right !important">AMOUNT</th>
                                  <th scope="col" class="px-4 py-3 text-right" style="text-align: right; !important">ACTION</th>

                                </tr>
                            </tfoot>
                          <tbody>
                       <?php
                          foreach ($payables_array as $pay):

                            $title_pay = $this->db->get_where('invoice', array('invoice_code' => $pay['invoice_code'], 'student_id' => $row['student_id'], 'due <' => 0))->row()->title;
                            $this->db->select_sum('due');
                            $amount_pay = abs($this->db->get_where('invoice', array('invoice_code' => $pay['invoice_code'], 'student_id' => $row['student_id'], 'due <' => 0))->row()->due);
                            $pdate = $this->db->get_where('invoice', array('invoice_code' => $pay['invoice_code'], 'student_id' => $row['student_id'], 'due <' => 0))->row()->creation_timestamp;
                        ?>
                          <tr class="border-b dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700 text-xl font-semibold">
                            <td class="px-4 py-3 uppercase"><?php echo date('d M Y', $pdate); ?></td>
                            <td class="px-4 py-3"><?php echo $pay['invoice_code']; ?></td>
                            <td class="px-4 py-3" align="right"><strong><?php echo numfmt_format_currency($fmt, $amount_pay, $currency); ?></strong></td>
                            
                            <td class="px-4 py-3" align="right">
                                <a href="#" class="btn btn-danger rounded-lg h-16 content-center" disabled onclick="invoice_refund_modal('<?=$row['student_id'] ?>')" style="color: #d803f8;"><i class="entypo-bookmarks"></i>&nbsp; Make Refund</a>
                            </td>
                          </tr>
                      <?php endforeach; 

                      if($tf_refund != 0) {
                       ?>

                          <tr class="border-b dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700 text-xl font-semibold">
                            <td class="px-4 py-3 uppercase"><?php echo 'As at today'; ?></td>
                            <td class="px-4 py-3"><?php echo 'FEEDING FEE'; ?></td>
                            <td class="px-4 py-3" align="right"><strong><?php echo numfmt_format_currency($fmt, $tf_refund, $currency); ?></strong></td>
                            
                            <td class="px-4 py-3" align="right">
                                <a href="#" class="btn btn-danger rounded-lg h-16 content-center" disabled onclick="fct_refund_modal('<?=$row['student_id'] ?>', 'feeding')" style="color: #d803f8;"><i class="entypo-bookmarks"></i>&nbsp;Make Refund</a>
                            </td>
                          </tr>
                        <?php }

                        if($tc_refund != 0) {
                        ?>
                          <tr class="border-b dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700 text-xl font-semibold">
                            <td class="px-4 py-3 uppercase"><?php echo 'As at today'; ?></td>
                            <td class="px-4 py-3"><?php echo 'CLASSES FEE'; ?></td>
                            <td class="px-4 py-3" align="right"><strong><?php echo numfmt_format_currency($fmt, $tc_refund, $currency); ?></strong></td>
                            <td class="px-4 py-3" align="right">
                                <a href="#" class="btn btn-danger rounded-lg h-16 content-center" onclick="fct_refund_modal('<?=$row['student_id'] ?>', 'classes')" style="color: #d803f8;"><i class="entypo-bookmarks"></i>&nbsp;Make Refund</a>
                            </td>
                          </tr>
                        <?php }

                        if($tt_refund != 0) {
                        ?>
                          <tr class="border-b dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700 text-xl font-semibold">
                            <td class="px-4 py-3 uppercase"><?php echo 'As at today'; ?></td>
                            <td class="px-4 py-3"><?php echo 'TRANSPORT FARE'; ?></td>
                            <td class="px-4 py-3" align="right"><strong><?php echo numfmt_format_currency($fmt, $tt_refund, $currency); ?></strong></td>
                            <td class="px-4 py-3" align="right">
                                <a href="#" class="btn btn-danger rounded-lg h-16 content-center " onclick="fct_refund_modal('<?=$row['student_id'] ?>', 'transport')" style="color: #d803f8;"><i class="entypo-bookmarks"></i>&nbsp;Make Refund</a>
                            </td>
                          </tr>
                          <?php
                            }

                            if($tb_refund != 0) {
                            ?>
                              <tr class="border-b dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700 text-xl font-semibold">
                                <td class="px-4 py-3 uppercase"><?php echo 'As at today'; ?></td>
                                <td class="px-4 py-3"><?php echo 'BREAKFAST FEE'; ?></td>
                                <td class="px-4 py-3" align="right"><strong><?php echo numfmt_format_currency($fmt, $tb_refund, $currency); ?></strong></td>
                                <td class="px-4 py-3" align="right">
                                    <a href="#" class="btn btn-danger rounded-lg h-16 content-center " onclick="fct_refund_modal('<?=$row['student_id'] ?>', 'breakfast')" style="color: #d803f8;"><i class="entypo-bookmarks"></i>&nbsp;Make Refund</a>
                                </td>
                              </tr>
                            <?php }

                            if($tw_refund != 0) {
                            ?>
                              <tr class="border-b dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700 text-xl font-semibold">
                                <td class="px-4 py-3 uppercase"><?php echo 'As at today'; ?></td>
                                <td class="px-4 py-3"><?php echo 'WATER FEE'; ?></td>
                                <td class="px-4 py-3" align="right"><strong><?php echo numfmt_format_currency($fmt, $tw_refund, $currency); ?></strong></td>
                                <td class="px-4 py-3" align="right">
                                    <a href="#" class="btn btn-danger rounded-lg h-16 content-center " onclick="fct_refund_modal('<?=$row['student_id'] ?>', 'water')" style="color: #d803f8;"><i class="entypo-bookmarks"></i>&nbsp;Make Refund</a>
                                </td>
                              </tr>
                          <?php
                            }
                          ?>
                     </tbody>
                   </table>
                 </div>
               </section>
             </div>
         </div> <!-- end of payables -->
              

         <!--Payment -->
         <hr>
         <div class="row mt-16">

          <?php


            $grand_total = 0;


            ?>
            <div class="grid grid-cols-1">
                <section class="px-2">
                    <div class="font-bold text-2xl text-gray-500 text-right">BILLED INVOICES PAYMENTS HISTORY</div>
                    <div class="font-extrabold text-2xl text-gray-600 text-right" id="accumulated"></div>
                    <div class="flex flex-col p-5 w-full max-w-full border-t-8 bg-white shadow-md border border-t-sky-500 rounded-xl">
                        <table class="w-full text-lg text-left text-gray-500 dark:text-gray-400 datatable" id="exp_table">
                            <thead class="text-lg text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                <tr>
                                  
                                  <th scope="col" class="px-4 py-3">DATE</th>
                                  <th scope="col" class="px-4 py-3" style="text-align: left !important">RECEIPT#</th>
                                  <th scope="col" class="px-4 py-3" style="text-align: left !important">METHOD</th>
                                  <th scope="col" class="px-4 py-3" style="text-align: right !important">AMOUNT</th>
                                  <th scope="col" class="px-4 py-3 text-right action_column" style="text-align: right !important">ACTION</th>

                                </tr>
                            </thead>

                            <tfoot class="text-lg text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                <tr>
                                  
                                  <th scope="col" class="px-4 py-3">DATE</th>
                                  <th scope="col" class="px-4 py-3" style="text-align: left !important">RECEIPT#</th>
                                  <th scope="col" class="px-4 py-3" style="text-align: left !important">METHOD</th>
                                  <th scope="col" class="px-4 py-3" style="text-align: right !important">AMOUNT</th>
                                  <th scope="col" class="px-4 py-3 text-right" style="text-align: right !important">ACTION</th>

                                </tr>
                            </tfoot>
                          <tbody>
                            
                              
                            <?php
                        $count = 1;
                        $total_amount_received = 0;

                        if ($param3 == '') {
                          $this->db->select('receipt_code');
                          $this->db->distinct();
                          $this->db->from('payment');
                          $this->db->where('can_delete !=', 'trash');
                          //$this->db->where('invoice_code =', 0);
                          //$this->db->or_where('invoice_code !=', '');
                          $this->db->where('invoice_code !=', null);
                          $this->db->where('student_id', $student_id);
                          $this->db->order_by('receipt_code', 'desc');
                          $rec_code = $this->db->get()->result_array();

                        } else if ($param3 != '' && $param4 != '') {
                          $this->db->select('receipt_code');
                          $this->db->distinct();
                          $this->db->from('payment');
                          $this->db->where('can_delete !=', 'trash');
                          //$this->db->where('invoice_code =', 0);
                          //$this->db->or_where('invoice_code !=', '');
                          $this->db->where('invoice_code !=', null);
                          $this->db->where('student_id', $student_id);
                          $this->db->where('year', $param3);

                          if ($class_name == 'JHSS') {
                            $this->db->where('sem', $param4);
                          } else {
                            $this->db->where('term', $param4);
                          }

                          $this->db->order_by('receipt_code', 'desc');
                          $rec_code = $this->db->get()->result_array();

                        } else {
                          $this->db->select('receipt_code');
                          $this->db->distinct();
                          $this->db->from('payment');
                          $this->db->where('can_delete !=', 'trash');
                          //$this->db->where('invoice_code =', 0);
                          //$this->db->or_where('invoice_code !=', '');
                          $this->db->where('invoice_code !=', null);
                          $this->db->where('student_id', $student_id);
                          //$this->db->where('day_timestamp', $param3);
                          $this->db->order_by('receipt_code', 'desc');
                          $rec_code = $this->db->get()->result_array();
                        }

                        foreach ($rec_code as $rec):

                          $payments = $this->db->get_where('payment', array(
                            'receipt_code' => $rec['receipt_code'],
                          ))->result_array();

                          foreach ($payments as $row2):
                            $total_amount_received += $row2['amount'];
                          endforeach;

                          $timestamp = $this->db->get_where('payment', array(
                            'receipt_code' => $rec['receipt_code'],
                          ))->row()->timestamp;

                          $year = $this->db->get_where('payment', array(
                            'receipt_code' => $rec['receipt_code'],
                          ))->row()->year;

                          if ($class_name == 'JHSS') {
                            $sem = $this->db->get_where('payment', array(
                              'receipt_code' => $rec['receipt_code'],
                            ))->row()->sem;
                          } else {
                            $term = $this->db->get_where('payment', array(
                              'receipt_code' => $rec['receipt_code'],
                            ))->row()->term;
                          }

                          $method = $this->db->get_where('payment', array(
                            'receipt_code' => $rec['receipt_code'],
                          ))->row()->payment_method;
                          
                          // Get payment method name instead of ID
                          $method_name = get_payment_method_name($method);
                          ?>
                        <tr class="border-b dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700 text-xl font-semibold">
                          <td class="px-4 py-3 whitespace-nowrap"><?php echo date('d M, Y H:i:s', $timestamp); ?></td>
                          <td class="px-4 py-3" align="left" style="text-align: left !important"><?php echo $rec['receipt_code']; ?></td>
                          <td class="px-4 py-3" align="left" style="text-align: left !important"><?php echo $method_name; ?></td>
                          <td class="px-4 py-3" align="right"><?php echo numfmt_format_currency($fmt, $total_amount_received, $currency); ?></td>
                          <td class="w-4 px-4 py-3" align="right"><button class="btn btn-info rounded-lg h-16 content-center" onclick="window.open('<?php echo site_url('admin/receipt/'); ?><?php echo $rec['receipt_code']; ?>/<?php echo $student_id; ?>/<?php echo $total_amount_received; ?>/<?php echo $timestamp; ?>', '_blank')"><i class="entypo-eye"></i> View Receipt</button>
                          </td>
                                    </tr>
                                  <?php

                        //reset the total amount received value back to 0
                        $grand_total += $total_amount_received;
                        $total_amount_received = 0;
                      endforeach;

                      $grand_total_figure = numfmt_format_currency($fmt, $grand_total, $currency);
                      ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>

        </div>
        <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>
<?php endforeach; ?>

<script type="text/javascript">
 $(function() {
     $('#accumulated').html('<span class="text-md font-medium text-gray-500">ACCUMULATED:</span> <?=$grand_total_figure;?>');

     $('#exp_table').dataTable();

     $('#receivables').dataTable();

     $('#payables').dataTable();

     $('#payment_ta').dataTable();

     
 });



  function modal_view_receipt(receipt_code, student_id, total_amount_received, date_time) {

       /** invoice_code = invoice_code.toString();
        let invoice_original_len = '<?php //echo $inv_number_len; ?>';
        let current_invoice_len = invoice_code.length;

        if(invoice_code.substring(0, 1) == '_') {
            invoice_code = invoice_code.substring(1);
        } else {
            invoice_code = invoice_code;
        } **/

        let receipt_style = '<?php echo $receipt_style; ?>';

        if(receipt_style == 'style_1') {
            showAjaxModal_receipt('<?php echo site_url('modal/popup_receipt/modal_receipt/'); ?>' + receipt_code + '/' + student_id + '/' + total_amount_received + '/' + date_time, 'take_payment');

        } else if(receipt_style == 'style_2') {
            showAjaxModal_receipt('<?php echo site_url('modal/popup_receipt/modal_receipt_2/'); ?>' + receipt_code + '/' + student_id + '/' + total_amount_received + '/' + date_time, 'take_payment');

        } else if(receipt_style == 'style_3') {
            showAjaxModal_receipt('<?php echo site_url('modal/popup_receipt/modal_receipt_3/'); ?>' + receipt_code + '/' + student_id + '/' + total_amount_received + '/' + date_time, 'take_payment');
        } else if(receipt_style == 'style_thermal') {
            window.open('<?php echo site_url('admin/receipt_print/'); ?>' + receipt_code + '/' + student_id + '/' + total_amount_received + '/' + date_time, '_blank');
        }

    }

 function destroySelect2() {

  $('.select2').select2('destroy');

  //launch the modal now
  showAjaxModal('<?php echo site_url('modal/popup/modal_student_edit/'.$student_id);?>', 'modal_student_edit');
 }

  // Modern tab switching
  function showTab(tabId, btn) {
    $('.tab-pane-modern').hide();
    $('#' + tabId).fadeIn(300);
    $('.tab-modern').removeClass('active');
    $(btn).addClass('active');
  }


  //redirect if a student is changed
    $('#other_students').change(function(event) {
        /* Act on the event */
        $('html, body').animate({
          scrollTop: ($('#top').offset().top )
        }, 1000); 

        const url = '<?php echo site_url('admin/student_profile/') ?>' + $(this).val();

        navigation(url);
        
    });


    //when results form is submitted
    function loadExamResults(url) {

        //Scroll to the top
          $('html, body').animate({
              scrollTop: ($('#top').offset().top )
          }, 1000);


          $('#main_page').empty();

            //SHOW LOADER
          $('#main_page').html('<center><div style="font-size: 20px; font-weight: bolder; margin-top: 200px; ">Fetching Exam Results...<br><i class="fa fa-3x fa-spinner fa-pulse"></i></div></center>');

            let formUrl = url;

            $.ajax({
              url: formUrl,
              type: 'POST',
              dataType: 'html',
              cache: false,
          })
          .done(function(data) {

            $('#main_page').empty();
            $('#main_page').html(data);
          });
    }

 function invoice_pay_modal(student_id, date = '', term = '') {

     /** invoice_code = invoice_code.toString();
      let invoice_original_len = '<?php //echo $inv_number_len; ?>';
      let current_invoice_len = invoice_code.length;

      if(invoice_code.substring(0, 1) == '_') {
          invoice_code = invoice_code.substring(1);
      } else {
          invoice_code = invoice_code;
      } **/
      if(date != '' && term == '') {
          showAjaxModal('<?php echo site_url('modal/popup/modal_take_payment/');?>' + student_id + '/' + date, 'take_payment');
      } else if(date != '' && term != '') {
          showAjaxModal('<?php echo site_url('modal/popup/modal_take_payment/');?>' + student_id + '/' + date + '/' + term, 'take_payment');
      }  else {
          showAjaxModal('<?php echo site_url('modal/popup/modal_take_payment/');?>' + student_id, 'take_payment');
      }
  }


  function view_receipts_modal(student_id, date = '', term = '') {

     /** invoice_code = invoice_code.toString();
      let invoice_original_len = '<?php //echo $inv_number_len; ?>';
      let current_invoice_len = invoice_code.length;

      if(invoice_code.substring(0, 1) == '_') {
          invoice_code = invoice_code.substring(1);
      } else {
          invoice_code = invoice_code;
      } **/
      if(date != '' && term == '') {
          showAjaxModal('<?php echo site_url('modal/popup/modal_view_receipts/');?>' + student_id + '/' + date, 'take_payment');
      } else if(date != '' && term != '') {
          showAjaxModal('<?php echo site_url('modal/popup/modal_view_receipts/');?>' + student_id + '/' + date + '/' + term, 'take_payment');
      }  else {
          showAjaxModal('<?php echo site_url('modal/popup/modal_view_receipts/');?>' + student_id, 'take_payment');
      }
  }

  function invoice_refund_modal(student_id, date = '', term = '') {

     /** invoice_code = invoice_code.toString();
      let invoice_original_len = '<?php //echo $inv_number_len; ?>';
      let current_invoice_len = invoice_code.length;

      if(invoice_code.substring(0, 1) == '_') {
          invoice_code = invoice_code.substring(1);
      } else {
          invoice_code = invoice_code;
      } **/
      if(date != '' && term == '') {
          showAjaxModal('<?php echo site_url('modal/popup/modal_refund/');?>' + student_id + '/' + date, 'make_refund');
      } else if(date != '' && term != '') {
          showAjaxModal('<?php echo site_url('modal/popup/modal_refund/');?>' + student_id + '/' + date + '/' + term, 'make_refund');
      }  else {
          showAjaxModal('<?php echo site_url('modal/popup/modal_refund/');?>' + student_id, 'make_refund');
      }
  }

  function fct_pay_modal(student_id, type = '') {

    showAjaxModal('<?php echo site_url('modal/popup/modal_fct_pay/');?>' + student_id + '/' + type, 'make_refund');

  }

  function fct_refund_modal(student_id, type = '') {

    showAjaxModal('<?php echo site_url('modal/popup/modal_fct_refund/');?>' + student_id + '/' + type, 'make_refund');

  }
</script>
