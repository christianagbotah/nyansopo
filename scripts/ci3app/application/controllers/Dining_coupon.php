<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Dining_coupon extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('Payment_status_model');
        $this->load->helper('dining_coupon');
    }

    public function generateTeacherCoupon() {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }

        try {
            $teacher_id = $this->session->userdata('login_user_id');
            $role = $this->session->userdata('login_type');
            
            if ($role !== 'teacher') {
                echo json_encode(['status' => 'error', 'message' => get_phrase('access_denied')]);
                return;
            }

            $date = $this->input->post('date');
            $fee_type = $this->input->post('fee_type') ?: 'feeding';
            
            if (!$this->validateDate($date)) {
                echo json_encode(['status' => 'error', 'message' => get_phrase('invalid_date_format')]);
                return;
            }

            $class_ids = getTeacherClassIds($teacher_id);
            
            if (empty($class_ids)) {
                echo json_encode(['status' => 'error', 'message' => get_phrase('no_assigned_classes')]);
                return;
            }

            $coupon_data = $this->generateCouponData($date, $class_ids, $fee_type);
            
            if (empty($coupon_data['students'])) {
                echo json_encode(['status' => 'warning', 'message' => get_phrase('no_students_have_made_payment_on_this_date')]);
                return;
            }

            $html = $this->renderCouponHtml($coupon_data, $fee_type);
            
            echo json_encode(['status' => 'success', 'html' => $html]);

        } catch (Exception $e) {
            log_message('error', 'Teacher coupon generation failed: ' . $e->getMessage());
            echo json_encode(['status' => 'error', 'message' => get_phrase('unable_to_generate_coupon')]);
        }
    }

    public function generateCashierCoupon() {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }

        try {
            $role = $this->session->userdata('login_type');
            
            if (!canGenerateCoupons($role)) {
                echo json_encode(['status' => 'error', 'message' => get_phrase('access_denied')]);
                return;
            }

            $date = $this->input->post('date');
            $class_filter = $this->input->post('class_filter');
            $fee_type = $this->input->post('fee_type') ?: 'feeding';
            
            if (!$this->validateDate($date)) {
                echo json_encode(['status' => 'error', 'message' => get_phrase('invalid_date_format')]);
                return;
            }

            $class_ids = [];
            if ($class_filter === 'all' || empty($class_filter)) {
                $class_ids = getAllClassIds();
            } else {
                $class_ids = is_array($class_filter) ? $class_filter : [$class_filter];
            }

            $coupon_data = $this->generateCouponData($date, $class_ids, $fee_type);
            
            if (empty($coupon_data['students'])) {
                echo json_encode(['status' => 'warning', 'message' => get_phrase('no_students_found_for_the_selected_date_and_class_filter')]);
                return;
            }

            $html = $this->renderCouponHtml($coupon_data, $fee_type);
            
            echo json_encode(['status' => 'success', 'html' => $html]);

        } catch (Exception $e) {
            log_message('error', 'Cashier coupon generation failed: ' . $e->getMessage());
            echo json_encode(['status' => 'error', 'message' => get_phrase('unable_to_generate_coupon')]);
        }
    }

    private function generateCouponData($date, $class_ids, $fee_type = 'feeding') {
        $timestamp = strtotime($date . ' 00:00:00');
        $running_year = get_settings('running_year');
        $running_term = get_settings('running_term');

        // Map fee type to database columns
        $fee_type_map = [
            'feeding' => ['amount_field' => 'feeding_amount', 'charge_field' => 'feeding_charged'],
            'breakfast' => ['amount_field' => 'breakfast_amount', 'charge_field' => 'breakfast_charged'],
            'transport' => ['amount_field' => 'transport_amount', 'charge_field' => 'transport_charged'],
            'classes' => ['amount_field' => 'classes_amount', 'charge_field' => 'classes_charged'],
            'water' => ['amount_field' => 'water_amount', 'charge_field' => 'water_charged']
        ];

        $amount_field = $fee_type_map[$fee_type]['amount_field'];
        $charge_field = $fee_type_map[$fee_type]['charge_field'];

        // Query students who were CHARGED on this date (from daily_charge_log)
        $this->db->select('s.student_id, s.name as student_name, s.student_code, c.name as class_name, c.name_numeric, sec.name as section_name');
        $this->db->select('dcl.' . $charge_field . ' as amount_charged', FALSE);
        $this->db->select('dft.' . $amount_field . ' as amount_paid', FALSE);
        $this->db->from('student s');
        $this->db->join('enroll e', 'e.student_id = s.student_id');
        $this->db->join('class c', 'c.class_id = e.class_id');
        $this->db->join('section sec', 'sec.section_id = e.section_id', 'left');
        // Join daily_charge_log to get students who were charged
        $this->db->join('daily_charge_log dcl', 'dcl.student_id = s.student_id AND dcl.charge_date = ' . $timestamp . ' AND dcl.' . $charge_field . ' > 0', 'inner');
        // Left join daily_fee_transactions to check if they paid cash that day
        $this->db->join('daily_fee_transactions dft', 'dft.student_id = s.student_id AND dft.payment_date = ' . $timestamp . ' AND dft.' . $amount_field . ' > 0', 'left');
        $this->db->where_in('c.class_id', $class_ids);
        $this->db->where('e.year', $running_year);
        $this->db->where('e.term', $running_term);
        $this->db->order_by('c.name, c.name_numeric, s.name');
        
        $query = $this->db->get();
        $students_charged = $query->result_array();
        
        // Filter students: only include those who paid cash OR had prepaid deducted
        $students = [];
        
        foreach ($students_charged as $student) {
            $student_id = $student['student_id'];
            $amount_charged = floatval($student['amount_charged']);
            $amount_paid = floatval($student['amount_paid']);
            
            // If they paid cash on this day, include them (Direct Payment)
            if ($amount_paid > 0) {
                $students[] = [
                    'student_id' => $student_id,
                    'student_name' => $student['student_name'],
                    'student_code' => $student['student_code'],
                    'class_name' => $student['class_name'],
                    'name_numeric' => $student['name_numeric'],
                    'section_name' => $student['section_name'],
                    'amount_paid' => $amount_charged,
                    'payment_type' => 'Direct Payment'
                ];
                continue;
            }
            
            // They didn't pay cash - check if prepaid balance was deducted on this charge date
            // When student is marked present, apply_charge() logs action_type='charge'
            // If balance was deducted (balance_before > balance_after), they used prepaid
            $charge_audit = $this->db->select('balance_before, balance_after, arrears_before, arrears_after')
                ->from('daily_fee_audit_log')
                ->where('student_id', $student_id)
                ->where('fee_type', $fee_type)
                ->where('action_type', 'charge')
                ->where('created_at >=', $timestamp)
                ->where('created_at <', $timestamp + 86400) // Same day
                ->order_by('id', 'DESC')
                ->limit(1)
                ->get()
                ->row();
            
            if ($charge_audit) {
                // Check if balance was deducted (prepaid used)
                $balance_deducted = $charge_audit->balance_before - $charge_audit->balance_after;
                
                // If any amount was deducted from balance, they used prepaid
                if ($balance_deducted > 0) {
                    $students[] = [
                        'student_id' => $student_id,
                        'student_name' => $student['student_name'],
                        'student_code' => $student['student_code'],
                        'class_name' => $student['class_name'],
                        'name_numeric' => $student['name_numeric'],
                        'section_name' => $student['section_name'],
                        'amount_paid' => $amount_charged,
                        'payment_type' => 'Prepaid'
                    ];
                }
                // If balance_deducted = 0, it means all went to arrears (no payment) - EXCLUDE
            }
            // If no charge audit found, EXCLUDE (shouldn't happen in normal operation)
        }

        return [
            'date' => $date,
            'school_name' => get_settings('system_name'),
            'total_students' => count($students),
            'students' => $students,
            'fee_type' => $fee_type
        ];
    }

    private function renderCouponHtml($coupon_data, $fee_type) {
        $fee_type_labels = [
            'feeding' => 'Feeding/Lunch',
            'breakfast' => 'Breakfast',
            'transport' => 'Transport',
            'classes' => 'Classes',
            'water' => 'Water'
        ];
        
        $fee_type_colors = [
            'feeding' => '#10b981',
            'breakfast' => '#f59e0b',
            'transport' => '#8b5cf6',
            'classes' => '#3b82f6',
            'water' => '#06b6d4'
        ];
        
        $fee_label = $fee_type_labels[$fee_type] ?? ucfirst($fee_type);
        $fee_color = $fee_type_colors[$fee_type] ?? '#667eea';
        
        // Calculate valid and expiry times
        $valid_date = date('d M Y', strtotime($coupon_data['date']));
        
        // For water (weekly), expiry is Friday of the week at 5:00 PM
        // For other fee types (daily), expiry is same day at 5:00 PM
        if ($fee_type === 'water') {
            // Get Friday of the current week
            $current_day = date('N', strtotime($coupon_data['date'])); // 1=Monday, 7=Sunday
            $days_to_friday = 5 - $current_day; // Friday is day 5
            if ($days_to_friday < 0) {
                $days_to_friday = 0; // Already past Friday, use today
            }
            $expiry_date = date('d M Y', strtotime($coupon_data['date'] . ' +' . $days_to_friday . ' days'));
            $expiry_time = '5:00 PM (Friday)';
        } else {
            $expiry_date = $valid_date;
            $expiry_time = '5:00 PM'; // End of school day
        }
        
        $html = '<!DOCTYPE html>
<html>
<head>
    <title>' . get_phrase('dining_coupon') . '</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: Arial, sans-serif; 
            font-size: 10pt; 
            margin: 0;
            padding: 5mm;
            background: #fff;
        }
        
        /* A4 Page Setup: 210mm x 297mm */
        .coupon-page {
            width: 200mm;
            min-height: 287mm;
            page-break-after: always;
            padding: 0;
        }
        
        .coupon-page:last-child {
            page-break-after: auto;
        }
        
        /* Grid: 4 columns x 5 rows = 20 coupons per page */
        .coupon-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            grid-template-rows: repeat(5, 1fr);
            gap: 3mm;
            height: 287mm;
        }
        
        /* Individual Coupon Card */
        .coupon-card {
            border: 2px dashed #ccc;
            border-radius: 4px;
            padding: 3mm;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            background: #fff;
            position: relative;
            overflow: hidden;
        }
        
        .coupon-card::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: ' . $fee_color . ';
        }
        
        .coupon-header {
            text-align: center;
            border-bottom: 1px solid #eee;
            padding-bottom: 2mm;
            margin-bottom: 2mm;
        }
        
        .coupon-header .school-name {
            font-size: 6pt;
            font-weight: bold;
            color: #333;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        
        .coupon-header .coupon-title {
            font-size: 8pt;
            font-weight: bold;
            color: ' . $fee_color . ';
            margin-top: 1mm;
        }
        
        .coupon-body {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .coupon-info {
            margin: 1mm 0;
        }
        
        .coupon-info-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 1mm 0;
            font-size: 7pt;
        }
        
        .coupon-info-row .label {
            color: #666;
            font-weight: normal;
            flex-shrink: 0;
        }
        
        .coupon-info-row .value {
            font-weight: bold;
            color: #333;
            text-align: right;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            max-width: 70%;
        }
        
        /* Name row - special styling for single line */
        .coupon-info-row.name-row {
            display: block;
        }
        
        .coupon-info-row.name-row .label {
            display: block;
            font-size: 6pt;
            margin-bottom: 0.5mm;
        }
        
        .coupon-info-row.name-row .value {
            display: block;
            font-size: 8pt;
            font-weight: bold;
            color: #333;
            text-align: left;
            max-width: 100%;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .coupon-footer {
            border-top: 1px solid #eee;
            padding-top: 2mm;
            margin-top: 2mm;
        }
        
        .date-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 6pt;
            margin-bottom: 1mm;
        }
        
        .date-row .valid-label {
            color: #666;
        }
        
        .date-row .valid-date {
            font-weight: bold;
            color: #333;
        }
        
        .date-row .expiry-label {
            color: #999;
        }
        
        .date-row .expiry-date {
            color: #999;
            text-decoration: line-through;
        }
        
        .payment-type {
            text-align: center;
            font-size: 5pt;
            color: #888;
            margin-top: 1mm;
        }
        
        .amount-badge {
            background: ' . $fee_color . ';
            color: white;
            padding: 1mm 2mm;
            border-radius: 2px;
            font-size: 8pt;
            font-weight: bold;
        }
        
        /* Print Styles */
        @media print {
            body { 
                margin: 0; 
                padding: 0;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            
            .coupon-page {
                width: 100%;
                height: 100%;
                page-break-after: always;
                margin: 0;
                padding: 5mm;
            }
            
            .coupon-page:last-child {
                page-break-after: auto;
            }
            
            .coupon-card {
                border: 2px dashed #ccc;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            
            .coupon-card::before {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            
            .amount-badge {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
        
        /* Page break handling */
        @page {
            size: A4;
            margin: 0;
        }
    </style>
</head>
<body>';

        // Split students into pages of 20 (4 columns x 5 rows)
        $students_per_page = 20;
        $pages = array_chunk($coupon_data['students'], $students_per_page);
        
        foreach ($pages as $page_students) {
            $html .= '<div class="coupon-page">
                <div class="coupon-grid">';
            
            foreach ($page_students as $student) {
                // Build class display with section
                $section_part = !empty($student['section_name']) ? ' ' . $student['section_name'] : '';
                $class_display = trim($student['class_name'] . ' ' . $student['name_numeric'] . $section_part);
                $amount = isset($student['amount_paid']) ? number_format($student['amount_paid'], 2) : '0.00';
                
                $html .= '<div class="coupon-card">
                    <div class="coupon-header">
                        <div class="school-name">' . htmlspecialchars($coupon_data['school_name']) . '</div>
                        <div class="coupon-title">' . $fee_label . ' Coupon</div>
                    </div>
                    <div class="coupon-body">
                        <div class="coupon-info">
                            <div class="coupon-info-row name-row">
                                <span class="label">Name</span>
                                <span class="value">' . htmlspecialchars($student['student_name']) . '</span>
                            </div>
                            <div class="coupon-info-row">
                                <span class="label">Code:</span>
                                <span class="value">' . htmlspecialchars($student['student_code']) . '</span>
                            </div>
                            <div class="coupon-info-row">
                                <span class="label">Class:</span>
                                <span class="value">' . htmlspecialchars($class_display) . '</span>
                            </div>
                            <div class="coupon-info-row">
                                <span class="label">Amount:</span>
                                <span class="amount-badge">GH₵ ' . $amount . '</span>
                            </div>
                        </div>
                    </div>
                    <div class="coupon-footer">
                        <div class="date-row">
                            <span class="valid-label">Valid:</span>
                            <span class="valid-date">' . $valid_date . '</span>
                        </div>
                        <div class="date-row">
                            <span class="expiry-label">Expires:</span>
                            <span class="expiry-date">' . ($fee_type === 'water' ? $expiry_date . ' ' . $expiry_time : $expiry_time) . '</span>
                        </div>
                        <div class="payment-type">' . $student['payment_type'] . '</div>
                    </div>
                </div>';
            }
            
            // Fill empty slots if less than 20 students on last page
            $empty_slots = $students_per_page - count($page_students);
            for ($i = 0; $i < $empty_slots; $i++) {
                $html .= '<div class="coupon-card" style="border-style: dotted; opacity: 0.3;"></div>';
            }
            
            $html .= '</div>
            </div>';
        }

        $html .= '</body>
</html>';

        return $html;
    }

    private function validateDate($date) {
        if (empty($date)) {
            return false;
        }
        $d = DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }
}
