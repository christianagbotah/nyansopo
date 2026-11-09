<style>
/* Template form styling */
.template-form-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 24px;
}

.template-card {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    margin-bottom: 24px;
}

.template-card-header {
    padding: 24px;
    border-bottom: 1px solid #e5e7eb;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 12px 12px 0 0;
}

.template-card-header h2 {
    color: #fff;
    font-size: 24px;
    font-weight: 600;
    margin: 0;
}

.template-card-header p {
    color: rgba(255,255,255,0.9);
    margin: 8px 0 0 0;
}

.template-card-body {
    padding: 32px;
}

.template-section {
    margin-bottom: 32px;
}

.template-section-title {
    font-size: 18px;
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 16px;
    padding-bottom: 12px;
    border-bottom: 2px solid #e5e7eb;
    display: flex;
    align-items: center;
    gap: 12px;
}

.template-section-title i {
    color: #667eea;
    font-size: 20px;
}

.template-form-group {
    margin-bottom: 24px;
}

.template-form-group label {
    display: block;
    font-size: 14px;
    font-weight: 600;
    color: #374151;
    margin-bottom: 8px;
}

.template-form-group label .required {
    color: #dc2626;
    margin-left: 4px;
}

.template-form-group .help-text {
    font-size: 13px;
    color: #6b7280;
    margin-top: 6px;
}

.template-form-control {
    width: 100%;
    padding: 12px 16px;
    font-size: 15px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    transition: all 0.2s;
}

.template-form-control:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.template-grid-2 {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 24px;
}

@media (max-width: 768px) {
    .template-grid-2 {
        grid-template-columns: 1fr;
    }
}

/* Tag selector */
.tag-selector {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    padding: 16px;
    background: #f9fafb;
    border-radius: 8px;
    border: 1px solid #e5e7eb;
}

.tag-checkbox {
    display: none;
}

.tag-label {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 16px;
    border-radius: 20px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
    border: 2px solid transparent;
    background: #fff;
}

.tag-checkbox:checked + .tag-label {
    border-color: currentColor;
    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
    transform: translateY(-2px);
}

/* Rich text editor wrapper */
.editor-wrapper {
    border: 1px solid #d1d5db;
    border-radius: 8px;
    overflow: hidden;
}

.editor-wrapper:focus-within {
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

/* Toggle switch */
.toggle-switch {
    position: relative;
    display: inline-block;
    width: 56px;
    height: 28px;
}

.toggle-switch input {
    opacity: 0;
    width: 0;
    height: 0;
}

.toggle-slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #d1d5db;
    transition: 0.3s;
    border-radius: 28px;
}

.toggle-slider:before {
    position: absolute;
    content: "";
    height: 20px;
    width: 20px;
    left: 4px;
    bottom: 4px;
    background-color: white;
    transition: 0.3s;
    border-radius: 50%;
}

input:checked + .toggle-slider {
    background-color: #667eea;
}

input:checked + .toggle-slider:before {
    transform: translateX(28px);
}

/* Action buttons */
.template-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 24px;
    margin-top: 32px;
    border-top: 2px solid #e5e7eb;
}

.template-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 24px;
    font-size: 15px;
    font-weight: 500;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s;
    border: none;
}

.template-btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: #fff;
}

.template-btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}

.template-btn-secondary {
    background: #f3f4f6;
    color: #374151;
}

.template-btn-secondary:hover {
    background: #e5e7eb;
}

.template-btn-lg {
    padding: 14px 32px;
    font-size: 16px;
}
</style>

<div class="template-form-container">
    <div class="template-card">
        <div class="template-card-header">
            <h2>
                <?php if (isset($template)): ?>
                    <i class="entypo-pencil"></i> <?php echo get_phrase('edit_template');?>
                <?php else: ?>
                    <i class="entypo-plus"></i> <?php echo get_phrase('create_new_template');?>
                <?php endif; ?>
            </h2>
            <p><?php echo get_phrase('create_reusable_lesson_note_structures');?></p>
        </div>
        
        <div class="template-card-body">
            <?php 
            $form_attributes = array('id' => 'template-form', 'method' => 'POST');
            echo form_open('teacher/save_lesson_note_template', $form_attributes);
            ?>
                <?php if (isset($template)): ?>
                <input type="hidden" name="template_id" value="<?php echo $template->template_id;?>">
                <?php endif; ?>
                
                <!-- Basic Information -->
                <div class="template-section">
                    <h3 class="template-section-title">
                        <i class="entypo-info"></i>
                        <?php echo get_phrase('basic_information');?>
                    </h3>
                    
                    <div class="template-form-group">
                        <label><?php echo get_phrase('template_name');?><span class="required">*</span></label>
                        <input type="text" name="template_name" id="template_name" required 
                               class="template-form-control" 
                               placeholder="<?php echo get_phrase('enter_template_name');?>"
                               value="<?php echo isset($template) ? $template->template_name : '';?>">
                        <p class="help-text"><?php echo get_phrase('give_your_template_descriptive_name');?></p>
                    </div>
                    
                    <div class="template-form-group">
                        <label><?php echo get_phrase('description');?></label>
                        <textarea name="description" id="description" rows="3" 
                                  class="template-form-control" 
                                  placeholder="<?php echo get_phrase('brief_description_of_template');?>"><?php echo isset($template) ? $template->description : '';?></textarea>
                        <p class="help-text"><?php echo get_phrase('explain_when_to_use_this_template');?></p>
                    </div>
                    
                    <div class="template-grid-2">
                        <div class="template-form-group">
                            <label><?php echo get_phrase('subject');?></label>
                            <select name="subject_id" id="subject_id" class="template-form-control">
                                <option value=""><?php echo get_phrase('all_subjects');?></option>
                                <?php foreach ($subjects as $subject): ?>
                                <option value="<?php echo $subject->subject_id;?>" 
                                        <?php echo (isset($template) && $template->subject_id == $subject->subject_id) ? 'selected' : '';?>>
                                    <?php echo $subject->name;?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="help-text"><?php echo get_phrase('leave_blank_for_all_subjects');?></p>
                        </div>
                        
                        <div class="template-form-group">
                            <label><?php echo get_phrase('class');?></label>
                            <select name="class_id" id="class_id" class="template-form-control">
                                <option value=""><?php echo get_phrase('all_classes');?></option>
                                <?php foreach ($classes as $class): ?>
                                <option value="<?php echo $class['class_id'];?>" 
                                        <?php echo (isset($template) && $template->class_id == $class['class_id']) ? 'selected' : '';?>>
                                    <?php echo $class['name'];?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="help-text"><?php echo get_phrase('leave_blank_for_all_classes');?></p>
                        </div>
                    </div>
                    
                    <div class="template-form-group">
                        <label><?php echo get_phrase('tags');?></label>
                        <div class="tag-selector">
                            <?php foreach ($tags as $tag): ?>
                            <input type="checkbox" name="tag_ids[]" value="<?php echo $tag->tag_id;?>" 
                                   id="tag_<?php echo $tag->tag_id;?>" class="tag-checkbox"
                                   <?php echo (isset($template) && in_array($tag->tag_id, array_column($template->tags, 'tag_id'))) ? 'checked' : '';?>>
                            <label for="tag_<?php echo $tag->tag_id;?>" class="tag-label" 
                                   style="color: <?php echo $tag->tag_color;?>;">
                                <i class="entypo-tag"></i>
                                <?php echo $tag->tag_name;?>
                            </label>
                            <?php endforeach; ?>
                        </div>
                        <p class="help-text"><?php echo get_phrase('select_tags_to_categorize_template');?></p>
                    </div>
                </div>
                
                <!-- Template Content -->
                <div class="template-section">
                    <h3 class="template-section-title">
                        <i class="entypo-doc-text"></i>
                        <?php echo get_phrase('template_content');?>
                    </h3>
                    
                    <div class="template-form-group">
                        <label><?php echo get_phrase('teaching_methods');?></label>
                        <div class="editor-wrapper">
                            <textarea name="teaching_methods" id="teaching_methods" class="template-form-control" rows="6"
                                      placeholder="<?php echo get_phrase('enter_teaching_methods_one_per_line');?>"><?php echo isset($template) ? implode("\n", json_decode($template->teaching_methods ?? '[]', true)) : '';?></textarea>
                        </div>
                        <p class="help-text"><?php echo get_phrase('enter_each_method_on_new_line');?></p>
                    </div>
                    
                    <div class="template-form-group">
                        <label><?php echo get_phrase('learning_activities');?></label>
                        <div class="editor-wrapper">
                            <textarea name="learning_activities" id="learning_activities" class="template-form-control" rows="6"
                                      placeholder="<?php echo get_phrase('enter_learning_activities_one_per_line');?>"><?php echo isset($template) ? implode("\n", json_decode($template->learning_activities ?? '[]', true)) : '';?></textarea>
                        </div>
                        <p class="help-text"><?php echo get_phrase('enter_each_activity_on_new_line');?></p>
                    </div>
                    
                    <div class="template-form-group">
                        <label><?php echo get_phrase('assessment_methods');?></label>
                        <div class="editor-wrapper">
                            <textarea name="assessment_methods" id="assessment_methods" class="template-form-control" rows="6"
                                      placeholder="<?php echo get_phrase('enter_assessment_methods_one_per_line');?>"><?php echo isset($template) ? implode("\n", json_decode($template->assessment_methods ?? '[]', true)) : '';?></textarea>
                        </div>
                        <p class="help-text"><?php echo get_phrase('enter_each_method_on_new_line');?></p>
                    </div>
                    
                    <div class="template-form-group">
                        <label><?php echo get_phrase('resources');?></label>
                        <div class="editor-wrapper">
                            <textarea name="resources" id="resources" class="template-form-control" rows="6"
                                      placeholder="<?php echo get_phrase('enter_resources_one_per_line');?>"><?php echo isset($template) ? implode("\n", json_decode($template->resources ?? '[]', true)) : '';?></textarea>
                        </div>
                        <p class="help-text"><?php echo get_phrase('enter_each_resource_on_new_line');?></p>
                    </div>
                    
                    <div class="template-form-group">
                        <label><?php echo get_phrase('differentiation_strategies');?></label>
                        <div class="editor-wrapper">
                            <textarea name="differentiation_strategies" id="differentiation_strategies" 
                                      class="template-form-control" rows="4"
                                      placeholder="<?php echo get_phrase('strategies_for_different_learners');?>"><?php echo isset($template) ? $template->differentiation_strategies : '';?></textarea>
                        </div>
                    </div>
                    
                    <div class="template-form-group">
                        <label><?php echo get_phrase('homework_assignment');?></label>
                        <div class="editor-wrapper">
                            <textarea name="homework_assignment" id="homework_assignment" 
                                      class="template-form-control" rows="4"
                                      placeholder="<?php echo get_phrase('typical_homework_for_this_type_of_lesson');?>"><?php echo isset($template) ? $template->homework_assignment : '';?></textarea>
                        </div>
                    </div>
                    
                    <div class="template-form-group">
                        <label><?php echo get_phrase('reflection_notes');?></label>
                        <div class="editor-wrapper">
                            <textarea name="reflection_notes" id="reflection_notes" 
                                      class="template-form-control" rows="4"
                                      placeholder="<?php echo get_phrase('notes_for_reflection_after_lesson');?>"><?php echo isset($template) ? $template->reflection_notes : '';?></textarea>
                        </div>
                    </div>
                </div>
                
                <!-- Sharing Settings -->
                <div class="template-section">
                    <h3 class="template-section-title">
                        <i class="entypo-share"></i>
                        <?php echo get_phrase('sharing_settings');?>
                    </h3>
                    
                    <div class="template-form-group">
                        <label style="display: flex; align-items: center; justify-content: space-between;">
                            <span>
                                <?php echo get_phrase('make_public');?>
                                <p class="help-text" style="margin: 4px 0 0 0; font-weight: normal;">
                                    <?php echo get_phrase('allow_other_teachers_to_use_this_template');?>
                                </p>
                            </span>
                            <label class="toggle-switch">
                                <input type="checkbox" name="is_public" value="1" 
                                       <?php echo (isset($template) && $template->is_public) ? 'checked' : '';?>>
                                <span class="toggle-slider"></span>
                            </label>
                        </label>
                    </div>
                </div>
                
                <!-- Action Buttons -->
                <div class="template-actions">
                    <a href="<?php echo site_url('teacher/lesson_note_templates');?>" 
                       class="template-btn template-btn-secondary">
                        <i class="entypo-cancel"></i>
                        <?php echo get_phrase('cancel');?>
                    </a>
                    <button type="submit" class="template-btn template-btn-primary template-btn-lg">
                        <i class="entypo-floppy"></i>
                        <?php echo isset($template) ? get_phrase('update_template') : get_phrase('create_template');?>
                    </button>
                </div>
            <?php echo form_close(); ?>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Form validation
    $('#template-form').submit(function(e) {
        var templateName = $('#template_name').val().trim();
        
        if (!templateName) {
            e.preventDefault();
            alert('<?php echo get_phrase("please_enter_template_name");?>');
            $('#template_name').focus();
            return false;
        }
        
        return true;
    });
});
</script>
