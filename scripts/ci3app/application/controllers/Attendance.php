<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Attendance extends CI_Controller {
    
    public function __construct() {
        parent::__construct();
        $this->load->model('Attendance_enterprise_model');
        
        $login_type = $this->session->userdata('login_type');
        if($login_type != 'admin' && $login_type != 'teacher') {
            redirect(site_url('login'), 'refresh');
        }
    }
    
    public function dashboard() {
        $data['page_name'] = 'attendance/dashboard';
        $data['page_title'] = get_phrase('attendance_management');
        $this->load->view('backend/main', $data);
    }
    
    public function dashboard2() {
        $this->load->view('backend/attendance/dashboard');
    }
    
    public function mark() {
        $class_id = $this->input->get('class_id');
        $date = $this->input->get('date') ?: date('Y-m-d');
        
        $login_type = $this->session->userdata('login_type');
        $user_id = $this->session->userdata('login_user_id');
        
        $section = $this->db->get_where('section', ['class_id' => $class_id])->row();
        $section_id = $section ? $section->section_id : 0;
        
        // Use enhanced permission system
        $permissions = $this->Daily_fee_model->get_fee_collection_permissions('attendance_portal');
        
        $data['page_name'] = 'mark_attendance';
        $data['page_title'] = get_phrase('mark_attendance');
        $data['class_id'] = $class_id;
        $data['section_id'] = $section_id;
        $data['date'] = $date;
        $data['students'] = [];
        $data['fee_rates'] = [];
        $data['attendance_records'] = [];
        $data['students_water_paid_this_week'] = []; // Initialize to prevent undefined variable error
        $data['permissions'] = $permissions;
        $data['teacher_id'] = $login_type == 'teacher' ? $user_id : '';
        $data['running_year'] = $this->db->get_where('settings', ['type' => 'running_year'])->row()->description;
        $data['running_term'] = $this->db->get_where('settings', ['type' => 'running_term'])->row()->description;
        
        if($class_id) {
            // Optimize: Use single query with joins instead of multiple queries
            $data['students'] = $this->Attendance_enterprise_model->get_class_students($class_id, $section_id, $date);
            $data['fee_rates'] = $this->Attendance_enterprise_model->get_daily_fee_rates($class_id);
            
            $timestamp = strtotime($date);
            
            // Optimize: Add index hint for better performance
            $records = $this->db->select('student_id, status, permission_reason')
                ->where('class_id', $class_id)
                ->where('timestamp', $timestamp)
                ->get('attendance')
                ->result_array();
            
            $data['attendance_records'] = [];
            foreach($records as $rec) {
                $data['attendance_records'][$rec['student_id']] = $rec;
            }
            
            // Get payment status for all students
            $this->load->model('Payment_status_model');
            $student_ids = array_column($data['students'], 'student_id');
            
            if (!empty($student_ids)) {
                $data['payment_status'] = $this->Payment_status_model->getBulkPaymentStatus($student_ids, $date);
                
                // Optimize: Get paid amounts in single query with specific fields only
                $transactions = $this->db->select('student_id, feeding_amount as feeding_paid, breakfast_amount as breakfast_paid, classes_amount as classes_paid, water_amount as water_paid, transport_amount as transport_paid')
                    ->where_in('student_id', $student_ids)
                    ->where('payment_date', $timestamp)
                    ->get('daily_fee_transactions')
                    ->result_array();
                
                $data['paid_amounts'] = [];
                foreach ($transactions as $txn) {
                    $data['paid_amounts'][$txn['student_id']] = $txn;
                }
                
                // Optimize: Batch check for students with full discount
                $data['students_with_full_discount'] = [];
                foreach ($student_ids as $student_id) {
                    if ($this->Discount_model->has_full_discount_on_all_daily_fees(
                        $student_id, 
                        $data['running_year'], 
                        $data['running_term'], 
                        $class_id, 
                        $timestamp
                    )) {
                        $data['students_with_full_discount'][] = $student_id;
                    }
                }
            } else {
                $data['payment_status'] = [];
                $data['paid_amounts'] = [];
                $data['students_with_full_discount'] = [];
            }
        } else {
            $data['payment_status'] = [];
            $data['paid_amounts'] = [];
            $data['students_with_full_discount'] = [];
        }
        
        $this->load->view('backend/main', $data);
    }
    
    public function get_student_data() {
        $class_id = $this->input->post('class_id');
        $section_id = $this->input->post('section_id');
        $date = $this->input->post('date') ?: date('Y-m-d');
        
        if(!$class_id) {
            echo json_encode(['status' => 'error', 'message' => 'Class ID required']);
            return;
        }
        
        // Get section if not provided
        if(!$section_id) {
            $section = $this->db->get_where('section', ['class_id' => $class_id])->row();
            $section_id = $section ? $section->section_id : 0;
        }
        
        // Get students
        $students = $this->Attendance_enterprise_model->get_class_students($class_id, $section_id, $date);
        
        // Get existing attendance records for this date
        $timestamp = strtotime($date);
        $attendance_records = $this->db->where('class_id', $class_id)
            ->where('timestamp', $timestamp)
            ->get('attendance')
            ->result_array();
        
        // Map attendance records by student_id
        $attendance_map = [];
        foreach($attendance_records as $record) {
            $attendance_map[$record['student_id']] = $record['status'];
        }
        
        // Add status to each student
        foreach($students as &$student) {
            $student['status'] = isset($attendance_map[$student['student_id']]) ? $attendance_map[$student['student_id']] : 0;
        }
        
        echo json_encode([
            'status' => 'success',
            'data' => $students
        ]);
    }
    
    public function save() {
        $class_id = $this->input->post('class_id');
        $section_id = $this->input->post('section_id');
        $date = $this->input->post('date');
        $attendance = $this->input->post('attendance');
        $permission_reason = $this->input->post('permission_reason'); // NEW: Get permission reasons
        $fees = $this->input->post('fees');
        
        if(!$attendance) {
            echo json_encode(['status' => 'error', 'message' => get_phrase('no_students_selected')]);
            return;
        }
        
        // Build students array
        $students = [];
        foreach($attendance as $student_id => $status) {
            $students[$student_id] = [
                'status' => $status,
                'permission_reason' => ($status == 6 && isset($permission_reason[$student_id])) ? $permission_reason[$student_id] : null, // NEW: Add permission reason
                'feeding_paid' => $fees[$student_id]['feeding_paid'] ?? 0,
                'breakfast_paid' => $fees[$student_id]['breakfast_paid'] ?? 0,
                'classes_paid' => $fees[$student_id]['classes_paid'] ?? 0,
                'water_paid' => $fees[$student_id]['water_paid'] ?? 0,
                'transport_paid' => $fees[$student_id]['transport_paid'] ?? 0,
                'transport_id' => $fees[$student_id]['transport_id'] ?? null,
                'transport_direction' => $fees[$student_id]['transport_direction'] ?? 'none',
                'transport_boarded' => isset($fees[$student_id]['transport_boarded']) ? 1 : 0,
                'transport_status' => 'present'
            ];
        }
        
        $result = $this->Attendance_enterprise_model->save_attendance_and_fees($class_id, $section_id, $date, $students);
        echo json_encode($result);
    }
    public function get_stats() {
        $date = $this->input->get('date') ?: date('Y-m-d');
        $login_type = $this->session->userdata('login_type');
        $running_year = get_settings('running_year');
        $running_term = get_settings('running_term');
        
        // Get expected total based on role
        if($login_type == 'teacher') {
            $teacher_id = $this->session->userdata('teacher_id');
            
            // Total enrolled students in teacher's assigned classes (from class.teacher_id) - excluding muted students
            $this->db->select('COUNT(DISTINCT e.student_id) as total');
            $this->db->from('enroll e');
            $this->db->join('class c', 'c.class_id = e.class_id');
            $this->db->join('student s', 's.student_id = e.student_id');
            $this->db->where('c.teacher_id', $teacher_id);
            $this->db->where('e.year', $running_year);
            $this->db->where('e.term', $running_term);
            $this->db->where('s.mute', '0');
            $result = $this->db->get()->row();
            $total = $result ? $result->total : 0;
            
            // Get stats for teacher's classes only
            $this->db->select('COUNT(CASE WHEN a.status = 1 THEN 1 END) as present');
            $this->db->select('COUNT(CASE WHEN a.status = 2 THEN 1 END) as absent');
            $this->db->select('COUNT(CASE WHEN a.status = 3 THEN 1 END) as late');
            $this->db->select('COUNT(CASE WHEN a.status = 4 THEN 1 END) as sick_home');
            $this->db->select('COUNT(CASE WHEN a.status = 5 THEN 1 END) as sick_clinic');
            $this->db->from('attendance a');
            $this->db->join('class c', 'c.class_id = a.class_id');
            $this->db->where('c.teacher_id', $teacher_id);
            $this->db->where('a.timestamp', strtotime($date));
            $stats = $this->db->get()->row_array();
        } else {
            // Total enrolled students in the school - excluding muted students
            // Use subquery to get distinct student_ids from enroll, then join with student to filter by mute
            $this->db->select('COUNT(*) as total');
            $this->db->from('(SELECT DISTINCT e.student_id FROM enroll e WHERE e.year = "'.$running_year.'" AND e.term = "'.$running_term.'") as enrolled');
            $this->db->join('student s', 's.student_id = enrolled.student_id');
            $this->db->where('s.mute', '0');
            $result = $this->db->get()->row();
            $total = $result ? $result->total : 0;
            
            $stats = $this->Attendance_enterprise_model->get_daily_stats($date);
        }
        
        $total_absent = ($stats['absent'] ?? 0) + ($stats['sick_home'] ?? 0) + ($stats['sick_clinic'] ?? 0);
        $present_percent = $total > 0 ? round((($stats['present'] ?? 0) / $total) * 100, 1) : 0;
        $absent_percent = $total > 0 ? round(($total_absent / $total) * 100, 1) : 0;
        
        echo json_encode([
            'status' => 'success',
            'data' => [
                'total' => $total,
                'present' => $stats['present'] ?? 0,
                'absent' => $total_absent,
                'late' => $stats['late'] ?? 0,
                'sick_home' => $stats['sick_home'] ?? 0,
                'sick_clinic' => $stats['sick_clinic'] ?? 0,
                'present_percent' => $present_percent,
                'absent_percent' => $absent_percent,
                'by_class' => $stats['by_class'] ?? []
            ]
        ]);
    }
    
    public function get_classes() {
        $login_type = $this->session->userdata('login_type');
        
        if($login_type == 'admin') {
            $this->db->order_by('name', 'asc');
            $this->db->order_by('name_numeric', 'asc');
            $classes = $this->db->get('class')->result_array();
        } else {
            $teacher_id = $this->session->userdata('teacher_id');
            $classes = $this->Attendance_enterprise_model->get_teacher_assigned_classes($teacher_id);
        }
        
        echo json_encode($classes);
    }
    
    public function barcode_scanner() {
        $this->load->view('backend/attendance/barcode_scanner');
    }
    
    public function get_student_by_code() {
        $code = $this->input->post('code');
        $date = $this->input->post('date') ?: date('Y-m-d');
        
        $student = $this->db->get_where('student', ['student_code' => $code])->row_array();
        
        if(!$student) {
            echo json_encode(['status' => 'error', 'message' => get_phrase('student_not_found')]);
            return;
        }
        
        $student['photo'] = $student['photo'] ?: 'default.jpg';
        $student['fee_rates'] = $this->Attendance_enterprise_model->get_daily_fee_rates($student['class_id']);
        
        echo json_encode(['status' => 'success', 'student' => $student]);
    }
    
    public function get_status_details() {
        $date = $this->input->get('date') ?: date('Y-m-d');
        $status = $this->input->get('status');
        $login_type = $this->session->userdata('login_type');
        $running_year = get_settings('running_year');
        $running_term = get_settings('running_term');
        
        $this->db->select('s.student_id, s.name, s.student_code, c.name as class_name, c.name_numeric, sec.name as section_name, a.status');
        $this->db->from('attendance a');
        $this->db->join('student s', 's.student_id = a.student_id');
        $this->db->join('enroll e', 'e.student_id = s.student_id AND e.year = "'.$running_year.'" AND e.term = "'.$running_term.'"');
        $this->db->join('class c', 'c.class_id = e.class_id');
        $this->db->join('section sec', 'sec.section_id = e.section_id', 'left');
        $this->db->where('a.timestamp', strtotime($date));
        $this->db->where('a.status', $status);
        
        if($login_type == 'teacher') {
            $teacher_id = $this->session->userdata('teacher_id');
            $this->db->where('c.teacher_id', $teacher_id);
        }
        
        $this->db->order_by('c.name', 'ASC');
        $this->db->order_by('s.name', 'ASC');
        $students = $this->db->get()->result_array();
        
        echo json_encode(['status' => 'success', 'students' => $students]);
    }
    
    public function sync() {
        // Placeholder for sync functionality
        echo json_encode(['status' => 'success', 'message' => 'Sync complete']);
    }
    
    public function quick_mark_modal() {
        $login_type = $this->session->userdata('login_type');
        $user_id = $this->session->userdata('login_user_id');
        
        $data['date'] = date('Y-m-d');
        $data['login_type'] = $login_type;
        $data['teacher_id'] = $login_type == 'teacher' ? $user_id : '';
        $this->load->view('backend/attendance/quick_mark_modal', $data);
    }
    
    public function export_modal() {
        $this->load->view('backend/attendance/export_modal');
    }
    
    public function report() {
        $login_type = $this->session->userdata('login_type');
        $teacher_id = $login_type == 'teacher' ? $this->session->userdata('login_user_id') : null;
        
        $data['page_name'] = 'attendance/report';
        $data['page_title'] = get_phrase('attendance_report');
        $data['login_type'] = $login_type;
        $data['teacher_id'] = $teacher_id;
        $this->load->view('backend/main', $data);
    }
    
    public function print_report() {
        $class_id = $this->input->get('class_id');
        $start_date = $this->input->get('start_date');
        $end_date = $this->input->get('end_date');
        $login_type = $this->session->userdata('login_type');
        $teacher_id = $login_type == 'teacher' ? $this->session->userdata('login_user_id') : null;
        
        // Restrict teacher to their classes only
        if($login_type == 'teacher' && $class_id) {
            $teacher_class = $this->db->get_where('class', ['class_id' => $class_id, 'teacher_id' => $teacher_id])->row();
            if(!$teacher_class) {
                show_error('Access denied. You can only view reports for your assigned classes.');
                return;
            }
        }
        
        $start_timestamp = strtotime($start_date);
        $end_timestamp = strtotime($end_date);
        $total_days = ceil(($end_timestamp - $start_timestamp) / 86400) + 1;
        
        $this->db->select('student.student_id, student.name, class.name as class_name, class.name_numeric, section.name as section_name');
        $this->db->from('student');
        $this->db->join('enroll', 'enroll.student_id = student.student_id');
        $this->db->join('class', 'class.class_id = enroll.class_id');
        $this->db->join('section', 'section.section_id = enroll.section_id', 'left');
        if($class_id) $this->db->where('enroll.class_id', $class_id);
        if($login_type == 'teacher') $this->db->where('class.teacher_id', $teacher_id);
        $students = $this->db->get()->result_array();
        
        $total_present = 0;
        $total_absent = 0;
        $total_late = 0;
        $total_sick_home = 0;
        $total_sick_clinic = 0;
        $total_excused = 0;
        
        foreach($students as &$student) {
            $student['class_name'] = $student['class_name'] . ($student['name_numeric'] ? ' ' . $student['name_numeric'] : '');
            $student['section_name'] = $student['section_name'] ?? '';
            
            $this->db->where('student_id', $student['student_id']);
            $this->db->where('timestamp >=', $start_timestamp);
            $this->db->where('timestamp <=', $end_timestamp);
            $this->db->where('status', 1);
            $student['present'] = $this->db->count_all_results('attendance');
            
            $this->db->where('student_id', $student['student_id']);
            $this->db->where('timestamp >=', $start_timestamp);
            $this->db->where('timestamp <=', $end_timestamp);
            $this->db->where('status', 2);
            $student['absent'] = $this->db->count_all_results('attendance');
            
            $this->db->where('student_id', $student['student_id']);
            $this->db->where('timestamp >=', $start_timestamp);
            $this->db->where('timestamp <=', $end_timestamp);
            $this->db->where('status', 3);
            $student['late'] = $this->db->count_all_results('attendance');
            
            $this->db->where('student_id', $student['student_id']);
            $this->db->where('timestamp >=', $start_timestamp);
            $this->db->where('timestamp <=', $end_timestamp);
            $this->db->where('status', 4);
            $student['sick_home'] = $this->db->count_all_results('attendance');
            
            $this->db->where('student_id', $student['student_id']);
            $this->db->where('timestamp >=', $start_timestamp);
            $this->db->where('timestamp <=', $end_timestamp);
            $this->db->where('status', 5);
            $student['sick_clinic'] = $this->db->count_all_results('attendance');
            
            $this->db->where('student_id', $student['student_id']);
            $this->db->where('timestamp >=', $start_timestamp);
            $this->db->where('timestamp <=', $end_timestamp);
            $this->db->where('status', 6);
            $student['excused'] = $this->db->count_all_results('attendance');
            
            $student['percentage'] = $total_days > 0 ? round(($student['present'] / $total_days) * 100, 1) : 0;
            
            $total_present += $student['present'];
            $total_absent += $student['absent'];
            $total_late += $student['late'];
            $total_sick_home += $student['sick_home'];
            $total_sick_clinic += $student['sick_clinic'];
            $total_excused += $student['excused'];
        }
        
        $attendance_rate = ($total_present + $total_absent) > 0 ? round(($total_present / ($total_present + $total_absent)) * 100, 1) : 0;
        
        $data['class_id'] = $class_id;
        $data['start_date'] = $start_date;
        $data['end_date'] = $end_date;
        $data['system_name'] = get_settings('system_name');
        $data['students'] = $students;
        $data['stats'] = [
            'total_days' => $total_days,
            'total_present' => $total_present,
            'total_absent' => $total_absent,
            'total_late' => $total_late,
            'total_sick_home' => $total_sick_home,
            'total_sick_clinic' => $total_sick_clinic,
            'total_excused' => $total_excused,
            'attendance_rate' => $attendance_rate
        ];
        
        $this->load->view('backend/attendance/print_report', $data);
    }
    
    public function send_notifications() {
        $date = date('Y-m-d');
        $timestamp = strtotime($date);
        
        $this->db->select('student.*, parent.phone');
        $this->db->from('attendance');
        $this->db->join('student', 'student.student_id = attendance.student_id');
        $this->db->join('parent', 'parent.parent_id = student.parent_id');
        $this->db->where('attendance.timestamp', $timestamp);
        $this->db->where('attendance.status', 2);
        $absent_students = $this->db->get()->result_array();
        
        if(empty($absent_students)) {
            echo json_encode(['status' => 'error', 'message' => get_phrase('no_absent_students_today')]);
            return;
        }
        
        $sent = 0;
        
        foreach($absent_students as $student) {
            if($student['phone']) {
                $message = get_phrase('absence_notification') . ': ' . $student['name'] . ' ' . get_phrase('was_absent_on') . ' ' . date('d/m/Y');
                $this->Sms_model->send_sms($student['phone'], $message);
                $sent++;
            }
        }
        
        echo json_encode(['status' => 'success', 'message' => $sent . ' ' . get_phrase('notifications_sent')]);
    }
    
    public function get_report_data() {
        $report_type = $this->input->post('report_type') ?: 'summary';
        $class_id = $this->input->post('class_id');
        $status = $this->input->post('status');
        $start_date = $this->input->post('start_date');
        $end_date = $this->input->post('end_date');
        $student_ids_param = $this->input->post('student_ids');
        $login_type = $this->session->userdata('login_type');
        $teacher_id = $login_type == 'teacher' ? $this->session->userdata('login_user_id') : null;
        
        // Parse student IDs from comma-separated string
        $student_ids = null;
        if($student_ids_param) {
            $student_ids = is_array($student_ids_param) ? $student_ids_param : explode(',', $student_ids_param);
            $student_ids = array_filter(array_map('trim', $student_ids));
        }
        
        // Restrict teacher to their classes only
        if($login_type == 'teacher' && $class_id) {
            $teacher_class = $this->db->get_where('class', ['class_id' => $class_id, 'teacher_id' => $teacher_id])->row();
            if(!$teacher_class) {
                echo json_encode(['status' => 'error', 'message' => 'Access denied. You can only view reports for your assigned classes.']);
                return;
            }
        }
        
        $start_timestamp = strtotime($start_date);
        $end_timestamp = strtotime($end_date);
        $total_days = ceil(($end_timestamp - $start_timestamp) / 86400) + 1;
        
        // Get students list
        $this->db->select('student.student_id, student.name, class.name as class_name, class.name_numeric, section.name as section_name');
        $this->db->from('student');
        $this->db->join('enroll', 'enroll.student_id = student.student_id');
        $this->db->join('class', 'class.class_id = enroll.class_id');
        $this->db->join('section', 'section.section_id = enroll.section_id', 'left');
        
        // Filter by student IDs if provided (per-student mode)
        if($student_ids && count($student_ids) > 0) {
            $this->db->where_in('student.student_id', $student_ids);
        } else if($class_id) {
            // Filter by class if no student IDs (class mode)
            $this->db->where('enroll.class_id', $class_id);
        }
        
        if($login_type == 'teacher') {
            $this->db->where('class.teacher_id', $teacher_id);
        }
        
        $students = $this->db->get()->result_array();
        
        if(empty($students)) {
            echo json_encode([
                'status' => 'success',
                'data' => [
                    'stats' => [
                        'total_days' => $total_days,
                        'total_present' => 0,
                        'total_absent' => 0,
                        'total_late' => 0,
                        'total_sick_home' => 0,
                        'total_sick_clinic' => 0,
                        'attendance_rate' => 0
                    ],
                    'students' => []
                ]
            ]);
            return;
        }
        
        // Load model for optimized batch queries
        $this->load->model('Attendance_enterprise_model');
        
        // Extract student IDs for batch query
        $batch_student_ids = array_column($students, 'student_id');
        
        // Fetch all attendance records in one batch query
        $attendance_data = $this->Attendance_enterprise_model->get_attendance_by_student_ids(
            $batch_student_ids, 
            $start_timestamp, 
            $end_timestamp,
            $status
        );
        
        $total_present = 0;
        $total_absent = 0;
        $total_late = 0;
        $total_sick_home = 0;
        $total_sick_clinic = 0;
        
        foreach($students as &$student) {
            $student['class_name'] = $student['class_name'] . ($student['name_numeric'] ? ' ' . $student['name_numeric'] : '');
            $student['section_name'] = $student['section_name'] ?? '';
            
            $student_id = $student['student_id'];
            $student['present'] = isset($attendance_data[$student_id][1]) ? $attendance_data[$student_id][1] : 0;
            $student['absent'] = isset($attendance_data[$student_id][2]) ? $attendance_data[$student_id][2] : 0;
            $student['late'] = isset($attendance_data[$student_id][3]) ? $attendance_data[$student_id][3] : 0;
            $student['sick_home'] = isset($attendance_data[$student_id][4]) ? $attendance_data[$student_id][4] : 0;
            $student['sick_clinic'] = isset($attendance_data[$student_id][5]) ? $attendance_data[$student_id][5] : 0;
            
            $student['percentage'] = $total_days > 0 ? round(($student['present'] / $total_days) * 100, 1) : 0;
            
            $total_present += $student['present'];
            $total_absent += $student['absent'];
            $total_late += $student['late'];
            $total_sick_home += $student['sick_home'];
            $total_sick_clinic += $student['sick_clinic'];
        }
        
        $attendance_rate = ($total_present + $total_absent) > 0 ? round(($total_present / ($total_present + $total_absent)) * 100, 1) : 0;
        
        echo json_encode([
            'status' => 'success',
            'data' => [
                'stats' => [
                    'total_days' => $total_days,
                    'total_present' => $total_present,
                    'total_absent' => $total_absent,
                    'total_late' => $total_late,
                    'total_sick_home' => $total_sick_home,
                    'total_sick_clinic' => $total_sick_clinic,
                    'attendance_rate' => $attendance_rate
                ],
                'students' => $students
            ]
        ]);
    }
    
    public function export() {
        $format = $this->input->get('format') ?: 'excel';
        $start = $this->input->get('start') ?: date('Y-m-01');
        $end = $this->input->get('end') ?: date('Y-m-d');
        $class_id = $this->input->get('class_id');
        
        $start_timestamp = strtotime($start);
        $end_timestamp = strtotime($end);
        $total_days = ceil(($end_timestamp - $start_timestamp) / 86400) + 1;
        
        $this->db->select('student.student_id, student.name, class.name as class_name');
        $this->db->from('student');
        $this->db->join('enroll', 'enroll.student_id = student.student_id');
        $this->db->join('class', 'class.class_id = enroll.class_id');
        if($class_id) $this->db->where('enroll.class_id', $class_id);
        $students = $this->db->get()->result_array();
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="attendance_' . $start . '_to_' . $end . '.csv"');
        
        $output = fopen('php://output', 'w');
        fputcsv($output, ['Student', 'Class', 'Present', 'Absent', 'Late', 'Sick-Home', 'Sick-Clinic', 'Excused', 'Attendance %']);
        
        foreach($students as $student) {
            $this->db->where('student_id', $student['student_id']);
            $this->db->where('timestamp >=', $start_timestamp);
            $this->db->where('timestamp <=', $end_timestamp);
            $this->db->where('status', 1);
            $present = $this->db->count_all_results('attendance');
            
            $this->db->where('student_id', $student['student_id']);
            $this->db->where('timestamp >=', $start_timestamp);
            $this->db->where('timestamp <=', $end_timestamp);
            $this->db->where('status', 2);
            $absent = $this->db->count_all_results('attendance');
            
            $this->db->where('student_id', $student['student_id']);
            $this->db->where('timestamp >=', $start_timestamp);
            $this->db->where('timestamp <=', $end_timestamp);
            $this->db->where('status', 3);
            $late = $this->db->count_all_results('attendance');
            
            $this->db->where('student_id', $student['student_id']);
            $this->db->where('timestamp >=', $start_timestamp);
            $this->db->where('timestamp <=', $end_timestamp);
            $this->db->where('status', 4);
            $sick_home = $this->db->count_all_results('attendance');
            
            $this->db->where('student_id', $student['student_id']);
            $this->db->where('timestamp >=', $start_timestamp);
            $this->db->where('timestamp <=', $end_timestamp);
            $this->db->where('status', 5);
            $sick_clinic = $this->db->count_all_results('attendance');
            
            $this->db->where('student_id', $student['student_id']);
            $this->db->where('timestamp >=', $start_timestamp);
            $this->db->where('timestamp <=', $end_timestamp);
            $this->db->where('status', 6);
            $excused = $this->db->count_all_results('attendance');
            
            $percentage = $total_days > 0 ? round(($present / $total_days) * 100, 1) : 0;
            fputcsv($output, [$student['name'], $student['class_name'], $present, $absent, $late, $sick_home, $sick_clinic, $excused, $percentage . '%']);
        }
        
        fclose($output);
    }
    
    public function export_report() {
        $class_id = $this->input->get('class_id');
        $start_date = $this->input->get('start_date');
        $end_date = $this->input->get('end_date');
        $login_type = $this->session->userdata('login_type');
        $teacher_id = $login_type == 'teacher' ? $this->session->userdata('login_user_id') : null;
        
        // Restrict teacher to their classes only
        if($login_type == 'teacher' && $class_id) {
            $teacher_class = $this->db->get_where('class', ['class_id' => $class_id, 'teacher_id' => $teacher_id])->row();
            if(!$teacher_class) {
                show_error('Access denied. You can only export reports for your assigned classes.');
                return;
            }
        }
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="attendance_report_' . date('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        fputcsv($output, ['Student Name', 'Class', 'Present Days', 'Absent Days', 'Late Days', 'Sick-Home', 'Sick-Clinic', 'Excused', 'Attendance %']);
        
        $start_timestamp = strtotime($start_date);
        $end_timestamp = strtotime($end_date);
        $total_days = ceil(($end_timestamp - $start_timestamp) / 86400) + 1;
        
        $this->db->select('student.student_id, student.name, class.name as class_name');
        $this->db->from('student');
        $this->db->join('enroll', 'enroll.student_id = student.student_id');
        $this->db->join('class', 'class.class_id = enroll.class_id');
        if($class_id) $this->db->where('enroll.class_id', $class_id);
        if($login_type == 'teacher') $this->db->where('class.teacher_id', $teacher_id);
        $students = $this->db->get()->result_array();
        
        foreach($students as $student) {
            $this->db->where('student_id', $student['student_id']);
            $this->db->where('timestamp >=', $start_timestamp);
            $this->db->where('timestamp <=', $end_timestamp);
            $this->db->where('status', 1);
            $present = $this->db->count_all_results('attendance');
            
            $this->db->where('student_id', $student['student_id']);
            $this->db->where('timestamp >=', $start_timestamp);
            $this->db->where('timestamp <=', $end_timestamp);
            $this->db->where('status', 2);
            $absent = $this->db->count_all_results('attendance');
            
            $this->db->where('student_id', $student['student_id']);
            $this->db->where('timestamp >=', $start_timestamp);
            $this->db->where('timestamp <=', $end_timestamp);
            $this->db->where('status', 3);
            $late = $this->db->count_all_results('attendance');
            
            $this->db->where('student_id', $student['student_id']);
            $this->db->where('timestamp >=', $start_timestamp);
            $this->db->where('timestamp <=', $end_timestamp);
            $this->db->where('status', 4);
            $sick_home = $this->db->count_all_results('attendance');
            
            $this->db->where('student_id', $student['student_id']);
            $this->db->where('timestamp >=', $start_timestamp);
            $this->db->where('timestamp <=', $end_timestamp);
            $this->db->where('status', 5);
            $sick_clinic = $this->db->count_all_results('attendance');
            
            $this->db->where('student_id', $student['student_id']);
            $this->db->where('timestamp >=', $start_timestamp);
            $this->db->where('timestamp <=', $end_timestamp);
            $this->db->where('status', 6);
            $excused = $this->db->count_all_results('attendance');
            
            $percentage = $total_days > 0 ? round(($present / $total_days) * 100, 1) : 0;
            
            fputcsv($output, [$student['name'], $student['class_name'], $present, $absent, $late, $sick_home, $sick_clinic, $excused, $percentage . '%']);
        }
        
        fclose($output);
    }
}
