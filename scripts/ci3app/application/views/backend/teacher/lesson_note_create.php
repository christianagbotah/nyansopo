<style>
/* Enterprise-grade form styling */
.ln-form-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 24px;
}

.ln-card {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    margin-bottom: 24px;
}

.ln-card-header {
    padding: 20px 24px;
    border-bottom: 1px solid #e5e7eb;
}

.ln-card-header h2 {
    font-size: 28px;
    font-weight: 700;
    color: #1f2937;
    margin: 0;
}

.ln-card-header p {
    font-size: 16px;
    font-weight: 400;
    color: #4b5563;
    margin-top: 8px;
    margin-bottom: 0;
    line-height: 1.5;
}

/* Help text and small descriptions */
.text-xs {
    font-size: 13px !important;
}

.text-sm {
    font-size: 14px !important;
}

.text-gray-500 {
    color: #6b7280 !important;
}

.text-gray-800 {
    color: #1f2937 !important;
}

.font-medium {
    font-weight: 500 !important;
}

/* Core competencies card styling */
.ln-checkbox-item .font-medium {
    font-weight: 700 !important;
    font-size: 15px;
}

.ln-checkbox-item .text-xs {
    font-weight: 400 !important;
    font-size: 13px !important;
}

.ln-card-body {
    padding: 24px;
}

.ln-form-group {
    margin-bottom: 24px;
}

.ln-form-group label {
    display: block;
    font-size: 14px;
    font-weight: 600;
    color: #374151;
    margin-bottom: 8px;
}

.ln-form-group label .required {
    color: #dc2626;
    margin-left: 4px;
}

.ln-form-control {
    width: 100%;
    padding: 12px 16px;
    font-size: 15px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    transition: border-color 0.2s, box-shadow 0.2s;
    background-color: #fff;
    height: 46px; /* Fixed height for consistency */
    line-height: 1.5;
}

/* Normalize date input specifically */
.ln-form-control[type="date"] {
    height: 46px;
    padding: 11px 16px; /* Slightly less padding to account for date picker icon */
    line-height: 1.5;
}

/* Normalize select elements */
.ln-form-control select,
select.ln-form-control {
    height: 46px;
    padding: 11px 16px;
    line-height: 1.5;
}

.ln-form-control:focus {
    outline: none;
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.ln-form-control-lg {
    padding: 14px 18px;
    font-size: 16px;
}

.ln-grid {
    display: grid;
    gap: 24px;
}

.ln-grid-2 {
    grid-template-columns: repeat(2, 1fr);
}

.ln-grid-3 {
    grid-template-columns: repeat(3, 1fr);
}

.ln-grid-4 {
    grid-template-columns: repeat(4, 1fr);
}

.ln-grid-5 {
    grid-template-columns: repeat(5, 1fr);
}

/* Responsive breakpoints - keep 5 columns on medium+ screens */
@media (max-width: 768px) {
    /* Stack on mobile only */
    .ln-grid-2, .ln-grid-3, .ln-grid-4, .ln-grid-5 { 
        grid-template-columns: 1fr; 
    }
}

@media (min-width: 769px) and (max-width: 1024px) {
    /* Tablet - keep all 5 in one row */
    .ln-grid-5 { 
        grid-template-columns: repeat(5, 1fr);
        gap: 16px; /* Slightly smaller gap for tablet */
    }
}

/* Step indicators */
.ln-steps {
    display: flex;
    justify-content: center;
    margin-bottom: 32px;
    padding: 0 24px;
}

.ln-step-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    flex: 1;
    max-width: 200px;
    position: relative;
}

.ln-step-circle {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 18px;
    background: #e5e7eb;
    color: #6b7280;
    transition: all 0.3s;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.ln-step-item.active .ln-step-circle {
    background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
    color: #fff;
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.4);
    transform: scale(1.1);
}

.ln-step-item.completed .ln-step-circle {
    background: linear-gradient(135deg, #16a34a 0%, #15803d 100%);
    color: #fff;
    box-shadow: 0 4px 12px rgba(22, 163, 74, 0.4);
}

.ln-step-line {
    position: absolute;
    top: 24px;
    left: 50%;
    width: 100%;
    height: 3px;
    background: #e5e7eb;
    border-radius: 2px;
    z-index: -1;
}

.ln-step-item.completed .ln-step-line {
    background: #16a34a;
}

.ln-step-item:last-child .ln-step-line {
    display: none;
}

.ln-step-label {
    margin-top: 12px;
    font-size: 14px;
    font-weight: 500;
    color: #6b7280;
    white-space: nowrap;
    text-align: center;
}

.ln-step-item.active .ln-step-label {
    color: #2563eb;
    font-weight: 600;
}

.ln-step-item.completed .ln-step-label {
    color: #16a34a;
    font-weight: 600;
}

/* Step content */
.ln-step-content {
    display: none;
}

.ln-step-content.active {
    display: block;
}

/* Section headers */
.ln-section-title {
    font-size: 20px;
    font-weight: 700;
    color: #1f2937;
    margin-bottom: 16px;
    padding-bottom: 12px;
    border-bottom: 2px solid #e5e7eb;
}

.ln-section-subtitle {
    font-size: 15px;
    font-weight: 400;
    color: #4b5563;
    margin-top: -12px;
    margin-bottom: 20px;
    line-height: 1.6;
}

/* Buttons */
.ln-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 12px 24px;
    font-size: 15px;
    font-weight: 500;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s;
    border: none;
}

.ln-btn-primary {
    background: #2563eb;
    color: #fff;
}

.ln-btn-primary:hover {
    background: #1d4ed8;
}

.ln-btn-secondary {
    background: #f3f4f6;
    color: #374151;
}

.ln-btn-secondary:hover {
    background: #e5e7eb;
}

.ln-btn-success {
    background: #16a34a;
    color: #fff;
}

.ln-btn-success:hover {
    background: #15803d;
}

.ln-btn-lg {
    padding: 14px 32px;
    font-size: 16px;
}

/* Checkbox grid */
.ln-checkbox-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 16px;
}

@media (min-width: 768px) {
    .ln-checkbox-grid {
        grid-template-columns: repeat(4, 1fr);
    }
}

.ln-checkbox-item {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 16px;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s;
}

.ln-checkbox-item:hover {
    background: #f9fafb;
    border-color: #d1d5db;
}

.ln-checkbox-item input[type="checkbox"] {
    width: 20px;
    height: 20px;
    margin-top: 2px;
    cursor: pointer;
}

.ln-checkbox-item.checked {
    background: #eff6ff;
    border-color: #2563eb;
}

/* Dynamic rows */
.ln-dynamic-row {
    display: flex;
    gap: 12px;
    margin-bottom: 12px;
    align-items: center;
}

.ln-dynamic-row .ln-form-control {
    flex: 1;
}

.ln-dynamic-row .ln-btn-add {
    flex-shrink: 0;
    width: 44px;
    height: 44px;
    padding: 0;
}

/* Week input styling */
input[type="week"] {
    position: relative;
}

input[type="week"]::-webkit-calendar-picker-indicator {
    cursor: pointer;
    opacity: 0.6;
}

input[type="week"]::-webkit-calendar-picker-indicator:hover {
    opacity: 1;
}

/* Alert boxes */
.ln-alert {
    padding: 16px 20px;
    border-radius: 8px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 12px;
}

.ln-alert-warning {
    background: #fef3c7;
    border: 1px solid #fcd34d;
    color: #92400e;
}

.ln-alert-info {
    background: #dbeafe;
    border: 1px solid #93c5fd;
    color: #1e40af;
}

/* Navigation footer */
.ln-form-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 24px;
    margin-top: 24px;
    border-top: 1px solid #e5e7eb;
}

.ln-form-footer .ln-btn-group {
    display: flex;
    gap: 12px;
}

/* Select2 customization */
.select2-container--default .select2-selection--single {
    height: 48px;
    padding: 8px 12px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
}

.select2-container--default .select2-selection--single .select2-selection__rendered {
    line-height: 30px;
    font-size: 15px;
}

.select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 46px;
}
</style>

<div class="ln-form-container">
    <div class="ln-card">
        <div class="ln-card-header">
            <h2 class="text-2xl font-bold text-gray-800"><?php echo get_phrase('create_lesson_note');?></h2>
            <p class="text-gray-600 mt-1"><?php echo get_phrase('create_ges_compliant_lesson_note');?></p>
        </div>
        
        <div class="ln-card-body">
            <!-- Progress Steps -->
            <div class="ln-steps">
                <div class="ln-step-item active" data-step="1">
                    <div class="ln-step-circle">1</div>
                    <div class="ln-step-label"><?php echo get_phrase('basic_info');?></div>
                    <div class="ln-step-line"></div>
                </div>
                <div class="ln-step-item" data-step="2">
                    <div class="ln-step-circle">2</div>
                    <div class="ln-step-label"><?php echo get_phrase('curriculum');?></div>
                    <div class="ln-step-line"></div>
                </div>
                <div class="ln-step-item" data-step="3">
                    <div class="ln-step-circle">3</div>
                    <div class="ln-step-label"><?php echo get_phrase('content');?></div>
                    <div class="ln-step-line"></div>
                </div>
                <div class="ln-step-item" data-step="4">
                    <div class="ln-step-circle">4</div>
                    <div class="ln-step-label"><?php echo get_phrase('review');?></div>
                </div>
            </div>

            <!-- Form -->
            <?php 
            $form_attributes = array(
                'id' => 'lesson-note-form',
                'method' => 'POST',
                'enctype' => 'multipart/form-data'
            );
            echo form_open('teacher/lesson_note_create/save', $form_attributes);
            ?>
                <input type="hidden" name="status" id="status-field" value="pending">

                <!-- Step 1: Basic Information -->
                <div class="ln-step-content active" data-step="1">
                    <h3 class="ln-section-title"><?php echo get_phrase('basic_information');?></h3>
                    <p class="ln-section-subtitle"><?php echo get_phrase('enter_basic_lesson_details');?></p>
                    
                    <!-- Professional 5-column grid for desktop, responsive for mobile -->
                    <div class="ln-grid ln-grid-5">
                        <div class="ln-form-group">
                            <label><?php echo get_phrase('lesson_date');?><span class="required">*</span></label>
                            <input type="date" name="lesson_date" id="lesson_date" required class="ln-form-control">
                        </div>
                        
                        <div class="ln-form-group">
                            <label><?php echo get_phrase('class');?><span class="required">*</span></label>
                            <select name="class_id" id="class_id" required class="ln-form-control">
                                <option value=""><?php echo get_phrase('select_class');?></option>
                                <?php 
                                $teacher_id = $this->session->userdata('teacher_id');
                                
                                // Get all unique classes the teacher has access to
                                $all_class_ids = array();
                                
                                // Add classes where teacher teaches subjects
                                foreach ($classes_with_subjects as $class) {
                                    $all_class_ids[$class['class_id']] = $class;
                                }
                                
                                // Add classes where teacher is class teacher (but only if they teach subjects there)
                                // Class teachers can only create lesson notes for subjects they teach
                                
                                // Sort by class order using getAllClassList
                                $ordered_class_ids = getAllClassList(); // Get all classes in order
                                
                                foreach ($ordered_class_ids as $cid) {
                                    if (isset($all_class_ids[$cid])) {
                                        $class = $all_class_ids[$cid];
                                        $class_name = $this->crud_model->get_class_name($cid);
                                        $class_name_numeric = $this->crud_model->get_class_name_numeric($cid);
                                        $class_section = $this->crud_model->get_class_section($cid);
                                        $display_name = $class_name . ' ' . $class_name_numeric . ' ' . $class_section;
                                ?>
                                <option value="<?php echo $cid;?>"><?php echo $display_name;?></option>
                                <?php 
                                    }
                                }
                                ?>
                            </select>
                        </div>
                        
                        <div class="ln-form-group">
                            <label><?php echo get_phrase('term');?><span class="required">*</span></label>
                            <select name="term" id="term" required class="ln-form-control">
                                <option value=""><?php echo get_phrase('select_term');?></option>
                                <option value="1" <?php echo ($running_term == '1') ? 'selected' : '';?>><?php echo get_phrase('term_1');?></option>
                                <option value="2" <?php echo ($running_term == '2') ? 'selected' : '';?>><?php echo get_phrase('term_2');?></option>
                                <option value="3" <?php echo ($running_term == '3') ? 'selected' : '';?>><?php echo get_phrase('term_3');?></option>
                            </select>
                        </div>
                        
                        <div class="ln-form-group">
                            <label><?php echo get_phrase('week');?><span class="required">*</span></label>
                            <select name="week_number" id="week_number" required class="ln-form-control">
                                <option value=""><?php echo get_phrase('select_week');?></option>
                                <?php for ($i = 1; $i <= 12; $i++): ?>
                                <option value="<?php echo $i;?>"><?php echo get_phrase('week') . ' ' . $i;?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        
                        <div class="ln-form-group">
                            <label><?php echo get_phrase('subject');?><span class="required">*</span></label>
                            <select name="subject_id" id="subject_id" required class="ln-form-control">
                                <option value=""><?php echo get_phrase('select_class_first');?></option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="ln-form-group">
                        <label><?php echo get_phrase('title');?><span class="required">*</span></label>
                        <input type="text" name="title" id="title" required class="ln-form-control ln-form-control-lg" placeholder="<?php echo get_phrase('enter_lesson_note_title');?>">
                    </div>
                    
                    <div class="ln-form-group">
                        <label><?php echo get_phrase('description');?></label>
                        <textarea name="description" id="description" rows="4" class="ln-form-control" placeholder="<?php echo get_phrase('brief_description_of_lesson');?>"></textarea>
                    </div>
                </div>

                <!-- Step 2: Curriculum Details -->
                <div class="ln-step-content" data-step="2">
                    <h3 class="ln-section-title"><?php echo get_phrase('curriculum_details');?></h3>
                    <p class="ln-section-subtitle"><?php echo get_phrase('select_curriculum_components');?></p>
                    
                    <div id="curriculum-loading" class="hidden ln-alert ln-alert-info">
                        <i class="entypo-cycle animate-spin text-xl"></i>
                        <span><?php echo get_phrase('loading_curriculum_data');?></span>
                    </div>
                    
                    <div id="curriculum-not-available" class="hidden ln-alert ln-alert-warning">
                        <i class="entypo-info text-xl"></i>
                        <span><?php echo get_phrase('curriculum_data_not_available_for_selected_subject_class');?></span>
                    </div>
                    
                    <div id="curriculum-fields">
                        <div class="ln-grid ln-grid-2">
                            <div class="ln-form-group">
                                <label><?php echo get_phrase('strand');?></label>
                                <select name="strand_id" id="strand_id" class="ln-form-control ln-form-control-lg">
                                    <option value=""><?php echo get_phrase('select_strand');?></option>
                                </select>
                            </div>
                            
                            <div class="ln-form-group">
                                <label><?php echo get_phrase('sub_strand');?></label>
                                <select name="sub_strand_id" id="sub_strand_id" class="ln-form-control ln-form-control-lg">
                                    <option value=""><?php echo get_phrase('select_sub_strand');?></option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="ln-form-group">
                            <label><?php echo get_phrase('content_standard');?></label>
                            <select name="content_standard_id" id="content_standard_id" class="ln-form-control ln-form-control-lg">
                                <option value=""><?php echo get_phrase('select_content_standard');?></option>
                            </select>
                        </div>
                        
                        <div class="ln-form-group">
                            <label><?php echo get_phrase('learning_indicators');?></label>
                            <select name="learning_indicators[]" id="learning_indicators" multiple class="ln-form-control" style="min-height: 150px;">
                            </select>
                            <p class="text-xs text-gray-500 mt-2"><?php echo get_phrase('hold_ctrl_to_select_multiple');?></p>
                        </div>
                        
                        <!-- Core Competencies -->
                        <div class="ln-form-group">
                            <label><?php echo get_phrase('core_competencies');?><span class="required">*</span></label>
                            <p class="text-sm text-gray-500 mb-3"><?php echo get_phrase('select_at_least_one_competency');?></p>
                            <div class="ln-checkbox-grid">
                                <?php foreach ($core_competencies as $competency): ?>
                                <label class="ln-checkbox-item">
                                    <input type="checkbox" name="core_competencies[]" value="<?php echo $competency->competency_id;?>">
                                    <div>
                                        <span class="font-medium text-gray-800"><?php echo $competency->name;?></span>
                                        <p class="text-xs text-gray-500 mt-1"><?php echo $competency->description;?></p>
                                    </div>
                                </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 3: Lesson Content -->
                <div class="ln-step-content" data-step="3">
                    <h3 class="ln-section-title"><?php echo get_phrase('lesson_content');?></h3>
                    <p class="ln-section-subtitle"><?php echo get_phrase('enter_lesson_content_and_resources');?></p>
                    
                    <div class="ln-form-group">
                        <label><?php echo get_phrase('lesson_objectives');?></label>
                        <textarea name="lesson_objectives" id="lesson_objectives" rows="5" class="ln-form-control" placeholder="<?php echo get_phrase('what_students_should_learn');?>"></textarea>
                    </div>
                    
                    <div class="ln-form-group">
                        <label><?php echo get_phrase('lesson_activities');?></label>
                        <textarea name="lesson_activities" id="lesson_activities" rows="5" class="ln-form-control" placeholder="<?php echo get_phrase('teaching_and_learning_activities');?>"></textarea>
                    </div>
                    
                    <div class="ln-form-group">
                        <label><?php echo get_phrase('lesson_content');?></label>
                        <textarea name="lesson_content" id="lesson_content" rows="8" class="ln-form-control" placeholder="<?php echo get_phrase('detailed_lesson_content');?>"></textarea>
                    </div>
                    
                    <!-- Teaching and Learning Resources -->
                    <div class="ln-form-group">
                        <label><?php echo get_phrase('teaching_learning_resources');?><span class="required">*</span></label>
                        <div id="resources-container">
                            <div class="ln-dynamic-row">
                                <select name="resource_name[]" class="ln-form-control">
                                    <option value=""><?php echo get_phrase('select_resource');?></option>
                                    <?php foreach ($teaching_resources as $resource): ?>
                                    <option value="<?php echo $resource->name;?>"><?php echo $resource->name;?></option>
                                    <?php endforeach; ?>
                                    <option value="__custom__"><?php echo get_phrase('other_custom');?></option>
                                </select>
                                <input type="text" name="resource_details[]" placeholder="<?php echo get_phrase('quantity_details');?>" class="ln-form-control" style="max-width: 200px;">
                                <button type="button" onclick="addResourceRow();" class="ln-btn ln-btn-secondary ln-btn-add">
                                    <i class="entypo-plus"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Assessment Methods -->
                    <div class="ln-form-group">
                        <label><?php echo get_phrase('assessment_methods');?><span class="required">*</span></label>
                        <div id="assessments-container">
                            <div class="ln-dynamic-row">
                                <select name="assessment_method[]" class="ln-form-control">
                                    <option value=""><?php echo get_phrase('select_method');?></option>
                                    <?php foreach ($assessment_methods as $method): ?>
                                    <option value="<?php echo $method->name;?>"><?php echo $method->name;?></option>
                                    <?php endforeach; ?>
                                    <option value="__custom__"><?php echo get_phrase('other_custom');?></option>
                                </select>
                                <input type="text" name="assessment_notes[]" placeholder="<?php echo get_phrase('notes');?>" class="ln-form-control" style="max-width: 200px;">
                                <button type="button" onclick="addAssessmentRow();" class="ln-btn ln-btn-secondary ln-btn-add">
                                    <i class="entypo-plus"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Reference Materials -->
                    <div class="ln-form-group">
                        <label><?php echo get_phrase('reference_materials');?></label>
                        <div id="references-container" class="space-y-4">
                            <div class="reference-row bg-gray-50 rounded-lg p-4">
                                <div class="ln-grid ln-grid-4">
                                    <input type="text" name="ref_title[]" placeholder="<?php echo get_phrase('title');?>" class="ln-form-control">
                                    <input type="text" name="ref_author[]" placeholder="<?php echo get_phrase('author');?>" class="ln-form-control">
                                    <input type="text" name="ref_publisher[]" placeholder="<?php echo get_phrase('publisher');?>" class="ln-form-control">
                                    <input type="text" name="ref_year[]" placeholder="<?php echo get_phrase('year');?>" class="ln-form-control">
                                </div>
                                <div class="ln-grid ln-grid-3 mt-3">
                                    <input type="text" name="ref_pages[]" placeholder="<?php echo get_phrase('page_numbers');?>" class="ln-form-control">
                                    <input type="url" name="ref_url[]" placeholder="<?php echo get_phrase('url');?>" class="ln-form-control">
                                    <button type="button" onclick="addReferenceRow();" class="ln-btn ln-btn-secondary">
                                        <i class="entypo-plus"></i> <?php echo get_phrase('add_reference');?>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- File Upload -->
                    <div class="ln-form-group">
                        <label><?php echo get_phrase('supporting_file');?></label>
                        <input type="file" name="lesson_file" id="lesson_file" class="ln-form-control" accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.jpg,.jpeg,.png">
                        <p class="text-xs text-gray-500 mt-2"><?php echo get_phrase('allowed_formats');?>: PDF, DOC, DOCX, PPT, PPTX, XLS, XLSX, JPG, PNG (Max: 10MB)</p>
                    </div>
                </div>

                <!-- Step 4: Review -->
                <div class="ln-step-content" data-step="4">
                    <h3 class="ln-section-title"><?php echo get_phrase('review_and_submit');?></h3>
                    <p class="ln-section-subtitle"><?php echo get_phrase('review_all_details_before_submitting');?></p>
                    
                    <div id="review-content" class="space-y-6">
                        <!-- Will be populated by JavaScript -->
                    </div>
                </div>

                <!-- Navigation Buttons -->
                <div class="ln-form-footer">
                    <button type="button" id="prev-btn" onclick="prevStep();" class="ln-btn ln-btn-secondary" style="display: none;">
                        <i class="entypo-left-open"></i> <?php echo get_phrase('previous');?>
                    </button>
                    <div class="ln-btn-group">
                        <button type="button" onclick="saveDraft();" class="ln-btn ln-btn-secondary">
                            <i class="entypo-save"></i> <?php echo get_phrase('save_draft');?>
                        </button>
                        <button type="button" id="next-btn" onclick="nextStep();" class="ln-btn ln-btn-primary ln-btn-lg">
                            <?php echo get_phrase('next');?> <i class="entypo-right-open"></i>
                        </button>
                        <button type="submit" id="submit-btn" class="ln-btn ln-btn-success ln-btn-lg" style="display: none;">
                            <i class="entypo-check"></i> <?php echo get_phrase('submit_for_review');?>
                        </button>
                    </div>
                </div>
            <?php echo form_close(); ?>
        </div>
    </div>
</div>

<script>
var currentStep = 1;
var totalSteps = 4;

// Subject data by class (populated from PHP)
var subjectsByClass = <?php echo json_encode($subjects_by_class); ?>;

$(document).ready(function() {
    // Set default date to today
    var today = new Date().toISOString().split('T')[0];
    $('#lesson_date').val(today);
    
    // Set default week to current week (1-12)
    var now = new Date();
    var onejan = new Date(now.getFullYear(), 0, 1);
    var week = Math.ceil((((now - onejan) / 86400000) + onejan.getDay() + 1) / 7);
    // Map to term week (1-12)
    var termWeek = ((week - 1) % 12) + 1;
    $('#week_number').val(termWeek);
    
    // Class change handler - load subjects for selected class
    $('#class_id').change(function() {
        loadSubjectsForClass($(this).val());
    });
    
    // Load curriculum when class/subject changes
    $('#subject_id').change(function() {
        loadCurriculum();
    });
    
    // Cascading dropdowns
    $('#strand_id').change(function() {
        loadSubStrands($(this).val());
    });
    
    $('#sub_strand_id').change(function() {
        loadContentStandards($(this).val());
    });
    
    $('#content_standard_id').change(function() {
        loadLearningIndicators($(this).val());
    });
    
    // Checkbox styling
    $('.ln-checkbox-item input[type="checkbox"]').change(function() {
        $(this).closest('.ln-checkbox-item').toggleClass('checked', $(this).is(':checked'));
    });
});

// Load subjects for a specific class (only subjects the teacher teaches in that class)
function loadSubjectsForClass(classId) {
    var subjectSelect = $('#subject_id');
    subjectSelect.html('<option value=""><?php echo get_phrase("loading");?></option>');
    
    if (!classId) {
        subjectSelect.html('<option value=""><?php echo get_phrase("select_class_first");?></option>');
        return;
    }
    
    // Get subjects for this class from the pre-loaded data
    var subjects = subjectsByClass[classId] || [];
    
    if (subjects.length === 0) {
        subjectSelect.html('<option value=""><?php echo get_phrase("no_subjects_available");?></option>');
        return;
    }
    
    var options = '<option value=""><?php echo get_phrase("select_subject");?></option>';
    $.each(subjects, function(i, subject) {
        options += '<option value="' + subject.subject_id + '">' + subject.name + '</option>';
    });
    subjectSelect.html(options);
}

function loadCurriculum() {
    var classId = $('#class_id').val();
    var subjectId = $('#subject_id').val();
    
    if (!classId || !subjectId) {
        return;
    }
    
    $('#curriculum-loading').removeClass('hidden');
    $('#curriculum-not-available').addClass('hidden');
    
    $.ajax({
        url: '<?php echo site_url("teacher/get_curriculum_strands");?>/' + classId + '/' + subjectId,
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            $('#curriculum-loading').addClass('hidden');
            
            if (response.length === 0) {
                $('#curriculum-not-available').removeClass('hidden');
                return;
            }
            
            var options = '<option value=""><?php echo get_phrase("select_strand");?></option>';
            $.each(response, function(i, strand) {
                options += '<option value="' + strand.strand_id + '">' + strand.name + '</option>';
            });
            $('#strand_id').html(options);
        },
        error: function() {
            $('#curriculum-loading').addClass('hidden');
            $('#curriculum-not-available').removeClass('hidden');
        }
    });
}

function loadSubStrands(strandId) {
    if (!strandId) {
        $('#sub_strand_id').html('<option value=""><?php echo get_phrase("select_sub_strand");?></option>');
        return;
    }
    
    $.ajax({
        url: '<?php echo site_url("teacher/get_curriculum_sub_strands");?>/' + strandId,
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            var options = '<option value=""><?php echo get_phrase("select_sub_strand");?></option>';
            $.each(response, function(i, subStrand) {
                options += '<option value="' + subStrand.sub_strand_id + '">' + subStrand.name + '</option>';
            });
            $('#sub_strand_id').html(options);
        }
    });
}

function loadContentStandards(subStrandId) {
    if (!subStrandId) {
        $('#content_standard_id').html('<option value=""><?php echo get_phrase("select_content_standard");?></option>');
        return;
    }
    
    $.ajax({
        url: '<?php echo site_url("teacher/get_curriculum_content_standards");?>/' + subStrandId,
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            var options = '<option value=""><?php echo get_phrase("select_content_standard");?></option>';
            $.each(response, function(i, standard) {
                options += '<option value="' + standard.content_standard_id + '">' + standard.code + ' - ' + standard.description + '</option>';
            });
            $('#content_standard_id').html(options);
        }
    });
}

function loadLearningIndicators(contentStandardId) {
    if (!contentStandardId) {
        $('#learning_indicators').html('');
        return;
    }
    
    $.ajax({
        url: '<?php echo site_url("teacher/get_curriculum_learning_indicators");?>/' + contentStandardId,
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            var options = '';
            $.each(response, function(i, indicator) {
                options += '<option value="' + indicator.indicator_id + '">' + indicator.code + ' - ' + indicator.description + '</option>';
            });
            $('#learning_indicators').html(options);
        }
    });
}

function addResourceRow() {
    var row = '<div class="ln-dynamic-row">' +
        '<select name="resource_name[]" class="ln-form-control">' +
        '<option value=""><?php echo get_phrase("select_resource");?></option>' +
        '<?php foreach ($teaching_resources as $resource): ?><option value="<?php echo $resource->name;?>"><?php echo $resource->name;?></option><?php endforeach; ?>' +
        '<option value="__custom__"><?php echo get_phrase("other_custom");?></option>' +
        '</select>' +
        '<input type="text" name="resource_details[]" placeholder="<?php echo get_phrase("quantity_details");?>" class="ln-form-control" style="max-width: 200px;">' +
        '<button type="button" onclick="$(this).parent().remove();" class="ln-btn ln-btn-secondary ln-btn-add" style="background:#fee2e2;color:#dc2626;"><i class="entypo-minus"></i></button>' +
        '</div>';
    $('#resources-container').append(row);
}

function addAssessmentRow() {
    var row = '<div class="ln-dynamic-row">' +
        '<select name="assessment_method[]" class="ln-form-control">' +
        '<option value=""><?php echo get_phrase("select_method");?></option>' +
        '<?php foreach ($assessment_methods as $method): ?><option value="<?php echo $method->name;?>"><?php echo $method->name;?></option><?php endforeach; ?>' +
        '<option value="__custom__"><?php echo get_phrase("other_custom");?></option>' +
        '</select>' +
        '<input type="text" name="assessment_notes[]" placeholder="<?php echo get_phrase("notes");?>" class="ln-form-control" style="max-width: 200px;">' +
        '<button type="button" onclick="$(this).parent().remove();" class="ln-btn ln-btn-secondary ln-btn-add" style="background:#fee2e2;color:#dc2626;"><i class="entypo-minus"></i></button>' +
        '</div>';
    $('#assessments-container').append(row);
}

function addReferenceRow() {
    var row = '<div class="reference-row bg-gray-50 rounded-lg p-4 mt-4">' +
        '<div class="ln-grid ln-grid-4">' +
        '<input type="text" name="ref_title[]" placeholder="<?php echo get_phrase("title");?>" class="ln-form-control">' +
        '<input type="text" name="ref_author[]" placeholder="<?php echo get_phrase("author");?>" class="ln-form-control">' +
        '<input type="text" name="ref_publisher[]" placeholder="<?php echo get_phrase("publisher");?>" class="ln-form-control">' +
        '<input type="text" name="ref_year[]" placeholder="<?php echo get_phrase("year");?>" class="ln-form-control">' +
        '</div>' +
        '<div class="ln-grid ln-grid-3 mt-3">' +
        '<input type="text" name="ref_pages[]" placeholder="<?php echo get_phrase("page_numbers");?>" class="ln-form-control">' +
        '<input type="url" name="ref_url[]" placeholder="<?php echo get_phrase("url");?>" class="ln-form-control">' +
        '<button type="button" onclick="$(this).closest(\'.reference-row\').remove();" class="ln-btn ln-btn-secondary" style="background:#fee2e2;color:#dc2626;"><i class="entypo-minus"></i> <?php echo get_phrase("remove");?></button>' +
        '</div>' +
        '</div>';
    $('#references-container').append(row);
}

function nextStep() {
    if (!validateStep(currentStep)) {
        return;
    }
    
    if (currentStep < totalSteps) {
        goToStep(currentStep + 1);
    }
}

function prevStep() {
    if (currentStep > 1) {
        goToStep(currentStep - 1);
    }
}

function goToStep(step) {
    // Hide all steps
    $('.ln-step-content').removeClass('active');
    
    // Show target step
    $('.ln-step-content[data-step="' + step + '"]').addClass('active');
    
    // Update indicators
    $('.ln-step-item').each(function() {
        var indicatorStep = parseInt($(this).data('step'));
        if (indicatorStep < step) {
            $(this).removeClass('active').addClass('completed');
        } else if (indicatorStep === step) {
            $(this).removeClass('completed').addClass('active');
        } else {
            $(this).removeClass('active completed');
        }
    });
    
    // Update buttons
    if (step === 1) {
        $('#prev-btn').hide();
    } else {
        $('#prev-btn').show();
    }
    
    if (step === totalSteps) {
        $('#next-btn').hide();
        $('#submit-btn').show();
        populateReview();
    } else {
        $('#next-btn').show();
        $('#submit-btn').hide();
    }
    
    currentStep = step;
}

function validateStep(step) {
    var valid = true;
    var firstError = null;
    
    if (step === 1) {
        var requiredFields = ['lesson_date', 'week_number', 'term', 'class_id', 'subject_id', 'title'];
        $.each(requiredFields, function(i, field) {
            if (!$('#' + field).val()) {
                $('#' + field).css('border-color', '#dc2626');
                if (!firstError) firstError = $('#' + field);
                valid = false;
            } else {
                $('#' + field).css('border-color', '#d1d5db');
            }
        });
    } else if (step === 2) {
        if ($('input[name="core_competencies[]"]:checked').length === 0) {
            showAjaxModal_alert('<?php echo get_phrase("please_select_at_least_one_core_competency");?>');
            valid = false;
        }
    } else if (step === 3) {
        var hasResource = false;
        $('select[name="resource_name[]"]').each(function() {
            if ($(this).val()) hasResource = true;
        });
        
        var hasAssessment = false;
        $('select[name="assessment_method[]"]').each(function() {
            if ($(this).val()) hasAssessment = true;
        });
        
        if (!hasResource) {
            showAjaxModal_alert('<?php echo get_phrase("please_add_at_least_one_teaching_resource");?>');
            valid = false;
        } else if (!hasAssessment) {
            showAjaxModal_alert('<?php echo get_phrase("please_add_at_least_one_assessment_method");?>');
            valid = false;
        }
    }
    
    if (firstError) {
        firstError.focus();
    }
    
    return valid;
}

function populateReview() {
    var className = $('#class_id option:selected').text();
    var subjectName = $('#subject_id option:selected').text();
    var termName = $('#term option:selected').text();
    var weekNumber = $('#week_number').val();
    var lessonDate = $('#lesson_date').val();
    var title = $('#title').val();
    var description = $('#description').val();
    
    var html = '<div class="bg-gray-50 rounded-lg p-6">' +
        '<h4 class="font-semibold text-lg text-gray-800 mb-4"><?php echo get_phrase("basic_information");?></h4>' +
        '<div class="grid grid-cols-2 gap-4">' +
        '<div><span class="text-gray-500"><?php echo get_phrase("date");?>:</span> <span class="font-medium">' + lessonDate + '</span></div>' +
        '<div><span class="text-gray-500"><?php echo get_phrase("week");?>:</span> <span class="font-medium"><?php echo get_phrase("week");?> ' + weekNumber + '</span></div>' +
        '<div><span class="text-gray-500"><?php echo get_phrase("term");?>:</span> <span class="font-medium">' + termName + '</span></div>' +
        '<div><span class="text-gray-500"><?php echo get_phrase("class");?>:</span> <span class="font-medium">' + className + '</span></div>' +
        '<div><span class="text-gray-500"><?php echo get_phrase("subject");?>:</span> <span class="font-medium">' + subjectName + '</span></div>' +
        '</div>' +
        '<div class="mt-4"><span class="text-gray-500"><?php echo get_phrase("title");?>:</span> <span class="font-medium">' + title + '</span></div>' +
        (description ? '<div class="mt-2"><span class="text-gray-500"><?php echo get_phrase("description");?>:</span> <span class="font-medium">' + description + '</span></div>' : '') +
        '</div>';
    
    $('#review-content').html(html);
}

function saveDraft() {
    $('#status-field').val('draft');
    $('#lesson-note-form').submit();
}
</script>