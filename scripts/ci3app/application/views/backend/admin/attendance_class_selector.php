<div class="modal-header bg-gradient-to-r from-blue-600 to-purple-600 text-white">
    <h4 class="modal-title"><i class="fas fa-calendar-check mr-2"></i><?php echo get_phrase('select_class_and_date'); ?></h4>
    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
</div>

<div class="modal-body p-6">
    <form id="attendance_selector_form">
        <div class="mb-4">
            <label class="font-semibold text-gray-700 mb-2 block">
                <i class="fas fa-school mr-2"></i><?php echo get_phrase('select_class'); ?>
            </label>
            <select name="class_id" id="class_selector" class="form-control" required>
                <option value=""><?php echo get_phrase('select_class'); ?></option>
                <?php
                $login_type = $this->session->userdata('login_type');
                if($login_type == 'admin') {
                    $classes = $this->db->order_by('name', 'asc')->order_by('name_numeric', 'asc')->get('class')->result_array();
                } else {
                    $teacher_id = $this->session->userdata('teacher_id');
                    $this->db->select('c.*');
                    $this->db->from('teacher_class_assignment tca');
                    $this->db->join('class c', 'c.class_id = tca.class_id');
                    $this->db->where('tca.teacher_id', $teacher_id);
                    $this->db->group_by('c.class_id');
                    $classes = $this->db->get()->result_array();
                }
                foreach($classes as $class):
                ?>
                <option value="<?php echo $class['class_id']; ?>">
                    <?php echo $class['name'] . ' ' . $class['name_numeric']; ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-4">
            <label class="font-semibold text-gray-700 mb-2 block">
                <i class="fas fa-calendar mr-2"></i><?php echo get_phrase('select_date'); ?>
            </label>
            <input type="date" name="date" id="date_selector" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
        </div>

        <div class="flex gap-3 mt-6">
            <button type="submit" class="flex-1 bg-gradient-to-r from-blue-600 to-purple-600 text-white px-6 py-3 rounded-lg font-semibold hover:from-blue-700 hover:to-purple-700 transition-all">
                <i class="fas fa-arrow-right mr-2"></i><?php echo get_phrase('proceed'); ?>
            </button>
            <button type="button" class="px-6 py-3 bg-gray-200 text-gray-700 rounded-lg font-semibold hover:bg-gray-300 transition-all" data-dismiss="modal">
                <?php echo get_phrase('cancel'); ?>
            </button>
        </div>
    </form>
</div>

<script>
$('#attendance_selector_form').submit(function(e) {
    e.preventDefault();
    const class_id = $('#class_selector').val();
    const date = $('#date_selector').val();
    
    if(!class_id) {
        showAjaxModal_alert('<?php echo get_phrase('please_select_class'); ?>', 'error');
        return;
    }
    
    const timestamp = new Date(date).getTime() / 1000;
    const section_id = 1; // Default section, will be handled by backend
    
    window.location.href = '<?php echo site_url('admin/manage_attendance_view'); ?>/' + class_id + '/' + section_id + '/' + Math.floor(timestamp);
});
</script>

<style>
.modal-header {
    border-radius: 0.5rem 0.5rem 0 0;
}
.form-control {
    padding: 0.75rem;
    border: 2px solid #e5e7eb;
    border-radius: 0.5rem;
    font-size: 1rem;
}
.form-control:focus {
    border-color: #667eea;
    outline: none;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}
</style>
