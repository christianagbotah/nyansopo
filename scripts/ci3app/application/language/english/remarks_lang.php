<?php
/**
 * Configurable Remarks System - Language File
 * 
 * Language phrases for head teacher remarks and teacher remarks templates
 */

defined('BASEPATH') OR exit('No direct script access allowed');

// Navigation & Module Names
$lang['head_teacher_remarks'] = 'Head Teacher Remarks';
$lang['teacher_remarks_templates'] = 'Teacher Remarks Templates';
$lang['head_teacher_remarks_ranges'] = 'Head Teacher Remarks Ranges';

// Actions
$lang['add_new_range'] = 'Add New Range';
$lang['add_new_template'] = 'Add New Template';
$lang['test_percentage'] = 'Test Percentage';
$lang['export_json'] = 'Export JSON';
$lang['import_json'] = 'Import JSON';
$lang['initialize_defaults'] = 'Initialize Defaults';
$lang['show_inactive'] = 'Show Inactive';
$lang['bulk_add'] = 'Bulk Add';
$lang['add_all'] = 'Add All';

// Fields
$lang['min_percentage'] = 'Min Percentage';
$lang['max_percentage'] = 'Max Percentage';
$lang['remark_text'] = 'Remark Text';
$lang['display_order'] = 'Display Order';
$lang['category'] = 'Category';

// Forms
$lang['add_remark_range'] = 'Add Remark Range';
$lang['edit_remark_range'] = 'Edit Remark Range';
$lang['add_remark_template'] = 'Add Remark Template';
$lang['edit_remark_template'] = 'Edit Remark Template';
$lang['range_0_to_100'] = 'Range: 0 to 100';
$lang['leave_blank_for_auto'] = 'Leave blank for auto';
$lang['enter_full_remark_sentence'] = 'Enter full remark sentence';
$lang['helps_organize_templates'] = 'Helps organize templates for easier selection';

// Test Percentage
$lang['test_percentage_score'] = 'Test Percentage Score';
$lang['test_percentage_description'] = 'Enter a percentage to see which remark would be assigned';
$lang['enter_percentage'] = 'Enter Percentage';

// Import/Export
$lang['import_from_json'] = 'Import from JSON';
$lang['import_json_instructions'] = 'Select a JSON file exported from this system to import remark data';
$lang['select_json_file'] = 'Select JSON File';

// Confirmations
$lang['delete_confirmation'] = 'Are you sure you want to delete this?';
$lang['initialize_default_ranges_question'] = 'This will create default remark ranges. Continue?';
$lang['initialize_default_templates_question'] = 'This will create default remark templates. Continue?';

// Categories
$lang['filter_by_category'] = 'Filter by Category';
$lang['all_categories'] = 'All Categories';
$lang['no_category'] = 'No Category';
$lang['category_for_all'] = 'Category for All';
$lang['positive_remarks'] = 'Positive Remarks';
$lang['neutral_remarks'] = 'Neutral Remarks';
$lang['negative_remarks'] = 'Negative Remarks';

// Views
$lang['show_grouped_view'] = 'Show Grouped View';
$lang['show_table_view'] = 'Show Table View';
$lang['no_templates_in_category'] = 'No templates in this category';

// Bulk Add
$lang['bulk_add_templates'] = 'Bulk Add Templates';
$lang['bulk_add_instructions'] = 'Enter one remark per line. All remarks will be added with the selected category.';
$lang['enter_remarks_one_per_line'] = 'Enter Remarks (One Per Line)';
$lang['bulk_add_placeholder'] = "Excellent work this term\nShows great improvement\nNeeds more attention";
$lang['remarks_entered'] = 'Remarks Entered';
$lang['please_enter_at_least_one_remark'] = 'Please enter at least one remark';

// Select2 / Dropdown
$lang['select_or_type_remark'] = 'Select from template or type custom remark';
$lang['select_template_or_type_custom_remark'] = 'Select a template or type your own remark';

// Status Messages
$lang['remark_range_added_successfully'] = 'Remark range added successfully';
$lang['remark_range_updated_successfully'] = 'Remark range updated successfully';
$lang['remark_range_deleted_successfully'] = 'Remark range deleted successfully';
$lang['remark_template_added_successfully'] = 'Remark template added successfully';
$lang['remark_template_updated_successfully'] = 'Remark template updated successfully';
$lang['remark_template_deleted_successfully'] = 'Remark template deleted successfully';
$lang['status_updated_successfully'] = 'Status updated successfully';
$lang['display_order_updated_successfully'] = 'Display order updated successfully';
$lang['import_successful'] = 'Import successful';
$lang['export_successful'] = 'Export successful';
$lang['defaults_initialized_successfully'] = 'Default values initialized successfully';

// Error Messages
$lang['overlapping_range_error'] = 'This range overlaps with an existing range';
$lang['invalid_percentage_range'] = 'Invalid percentage range';
$lang['min_must_be_less_than_max'] = 'Minimum percentage must be less than maximum percentage';
$lang['percentage_out_of_range'] = 'Percentage must be between 0 and 100';
$lang['remark_text_required'] = 'Remark text is required';
$lang['import_failed'] = 'Import failed';
$lang['invalid_json_file'] = 'Invalid JSON file';
$lang['no_file_selected'] = 'No file selected';
$lang['operation_failed'] = 'Operation failed';

/* End of file remarks_lang.php */
/* Location: ./application/language/english/remarks_lang.php */
