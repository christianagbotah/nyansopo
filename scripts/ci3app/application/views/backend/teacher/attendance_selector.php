<?php
$teacher_id = $this->session->userdata('teacher_id');
$running_year = $this->db->get_where('settings', ['type' => 'running_year'])->row()->description;
$running_term = $this->db->get_where('settings', ['type' => 'running_term'])->row()->description;

// Get teacher's assigned classes
$this->db->select('c.*, s.section_id, s.name as section_name');
$this->db->from('teacher_class_assignment tca');
$this->db->join('class c', 'c.class_id = tca.class_id');
$this->db->join('section s', 's.class_id = c.class_id');
$this->db->where('tca.teacher_id', $teacher_id);
$this->db->where('tca.year', $running_year);
$this->db->where('tca.term', $running_term);
$this->db->group_by('c.class_id, s.section_id');
$classes = $this->db->get()->result_array();
?>

<div class="min-h-screen bg-gradient-to-br from-slate-50 to-blue-50 p-6">
    <div class="max-w-4xl mx-auto">
        <div class="bg-white rounded-2xl shadow-lg p-8">
            <h2 class="text-3xl font-bold bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent mb-6">
                <i class="fas fa-calendar-check mr-3"></i>Select Class for Attendance
            </h2>
            
            <?php if (empty($classes)): ?>
            <div class="text-center py-12">
                <i class="fas fa-inbox text-6xl text-gray-300 mb-4"></i>
                <p class="text-gray-500 text-lg">No classes assigned to you</p>
                <p class="text-gray-400 text-sm mt-2">Contact admin to assign classes</p>
            </div>
            <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <?php foreach ($classes as $class): 
                    $students_count = $this->db->where(['class_id' => $class['class_id'], 'section_id' => $class['section_id'], 'year' => $running_year, 'term' => $running_term, 'mute' => '0'])->count_all_results('enroll');
                ?>
                <a href="<?php echo site_url('admin/manage_attendance_view/' . $class['class_id'] . '/' . $class['section_id'] . '/' . strtotime(date('Y-m-d'))); ?>" 
                   class="block bg-gradient-to-br from-blue-50 to-purple-50 rounded-xl p-6 border-2 border-blue-200 hover:border-blue-400 hover:shadow-lg transition-all">
                    <h3 class="text-xl font-bold text-gray-800 mb-2">
                        <?php echo $class['name'] . ' ' . $class['name_numeric'] . ' ' . $class['section_name']; ?>
                    </h3>
                    <p class="text-gray-600">
                        <i class="fas fa-users mr-2"></i><?php echo $students_count; ?> Students
                    </p>
                    <div class="mt-4 text-blue-600 font-semibold">
                        Mark Attendance <i class="fas fa-arrow-right ml-2"></i>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
@media (max-width: 768px) {
    .min-h-screen { padding: 1rem !important; }
    .p-8 { padding: 1.5rem !important; }
    .text-3xl { font-size: 1.5rem !important; }
}
</style>
