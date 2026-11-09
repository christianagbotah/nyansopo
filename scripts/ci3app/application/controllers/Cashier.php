<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Cashier Controller
 * Handles daily fee collection at central office
 */
class Cashier extends CI_Controller {

    public function __construct() {
        parent::__construct();
        
        // Check if user is logged in and has permission to collect daily fees
        if ($this->session->userdata('admin_id') == '') {
            redirect(site_url('login'), 'refresh');
        }
        
        // Check database permission (not just role)
        if (!$this->Daily_fee_model->can_collect_daily_fees()) {
            redirect(site_url('login'), 'refresh');
        }
        
        // Conductors cannot access office collection
        if ($this->session->userdata('login_type') == 'conductor') {
            redirect(site_url('login'), 'refresh');
        }
    }

    /**
     * Cashier dashboard
     */
    public function index() {
        $page_data['page_name'] = 'cashier_dashboard';
        $page_data['page_title'] = get_phrase('cashier_dashboard');
        $this->load->view('backend/main', $page_data);
    }

    /**
     * Daily fee collection interface (POS style)
     */
    public function daily_fee_collection() {
        $page_data['page_name'] = 'daily_fee_collection';
        $page_data['page_title'] = get_phrase('daily_fee_collection');
        $this->load->view('backend/main', $page_data);
    }

    /**
     * Search student for fee collection
     */
    public function search_student_for_daily_fees() {
        $search_term = $this->input->post('search_term');
        
        $this->db->select('s.student_id, s.name, s.student_code, s.photo, e.class_id, c.name as class_name');
        $this->db->from('student s');
        $this->db->join('enroll e', 's.student_id = e.student_id');
        $this->db->join('class c', 'e.class_id = c.class_id');
        $this->db->where('e.mute', 0);
        $this->db->group_start();
        $this->db->like('s.name', $search_term);
        $this->db->or_like('s.student_code', $search_term);
        $this->db->group_end();
        $this->db->order_by('s.name', 'ASC');
        $this->db->limit(20);
        
        $students = $this->db->get()->result_array();
        
        // Enrich with wallet info
        foreach ($students as &$student) {
            $outstanding = $this->Daily_fee_model->get_student_outstanding($student['student_id']);
            $student['wallet'] = $outstanding['wallet'];
            $student['total_balance'] = $outstanding['total_balance'];
            $student['total_arrears'] = $outstanding['total_arrears'];
            $student['net_position'] = $outstanding['net_position'];
        }
        
        echo json_encode([
            'status' => 'success',
            'students' => $students
        ]);
    }

    /**
     * Get student detailed wallet information
     */
    public function get_student_wallet_details() {
        $student_id = $this->input->post('student_id');
        
        $outstanding = $this->Daily_fee_model->get_student_outstanding($student_id);
        $payment_history = $this->Daily_fee_model->get_payment_history($student_id, 10);
        
        // Get student info
        $this->db->select('s.*, e.class_id, c.name as class_name, e.section_id, sec.section_name');
        $this->db->from('student s');
        $this->db->join('enroll e', 's.student_id = e.student_id');
        $this->db->join('class c', 'e.class_id = c.class_id');
        $this->db->join('section sec', 'e.section_id = sec.section_id', 'left');
        $this->db->where('s.student_id', $student_id);
        $this->db->where('e.mute', 0);
        $student = $this->db->get()->row_array();
        
        echo json_encode([
            'status' => 'success',
            'student' => $student,
            'wallet' => $outstanding['wallet'],
            'total_balance' => $outstanding['total_balance'],
            'total_arrears' => $outstanding['total_arrears'],
            'net_position' => $outstanding['net_position'],
            'payment_history' => $payment_history
        ]);
    }

    /**
     * Process daily fee payment at cashier office
     */
    public function process_daily_fee_payment() {
        // Verify user can collect all fee types
        if (!$this->Daily_fee_model->can_collect_daily_fees()) {
            echo json_encode(['status' => 'error', 'message' => get_phrase('permission_denied')]);
            return;
        }
        
        $student_id = $this->input->post('student_id');
        $feeding_amount = $this->input->post('feeding_amount') ?? 0;
        $breakfast_amount = $this->input->post('breakfast_amount') ?? 0;
        $classes_amount = $this->input->post('classes_amount') ?? 0;
        $water_amount = $this->input->post('water_amount') ?? 0;
        $transport_amount = $this->input->post('transport_amount') ?? 0;
        $payment_method = $this->input->post('payment_method') ?? 1;
        $payment_type = $this->input->post('payment_type') ?? 'mixed'; // arrears, current, advance, mixed
        $notes = $this->input->post('notes');
        
        // CRITICAL: Get toggle values (not amounts - amounts can be 0 for arrears)
        $breakfast_opted = $this->input->post('breakfast_opted') ?? 0;
        $water_opted = $this->input->post('water_opted') ?? 0;
        
        $cashier_id = $this->session->userdata('admin_id');
        
        // Generate receipt number
        $receipt_number = 'DFR' . date('Ymd') . str_pad($student_id, 5, '0', STR_PAD_LEFT) . substr(time(), -4);
        
        $result = $this->Daily_fee_model->process_payment([
            'student_id' => $student_id,
            'feeding_amount' => $feeding_amount,
            'breakfast_amount' => $breakfast_amount,
            'classes_amount' => $classes_amount,
            'water_amount' => $water_amount,
            'transport_amount' => $transport_amount,
            'payment_method' => $payment_method,
            'payment_type' => $payment_type,
            'collected_by' => $cashier_id,
            'collection_point' => 'office',
            'receipt_number' => $receipt_number,
            'notes' => $notes,
            'breakfast_opted' => $breakfast_opted,  // Pass toggle values to model
            'water_opted' => $water_opted
        ]);
        
        if ($result['status'] == 'success') {
            echo json_encode([
                'status' => 'success',
                'message' => get_phrase('payment_processed_successfully'),
                'transaction_code' => $result['transaction_code'],
                'receipt_number' => $receipt_number
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => get_phrase('payment_processing_failed')
            ]);
        }
    }

    /**
     * Print daily fee receipt
     */
    public function print_daily_fee_receipt($transaction_code) {
        // Get transaction details
        $transaction = $this->db->get_where('daily_fee_transactions', ['transaction_code' => $transaction_code])->row_array();
        
        if (!$transaction) {
            show_404();
            return;
        }
        
        // Get student details
        $student = $this->db->select('s.*, e.class_id, c.name as class_name')
                           ->from('student s')
                           ->join('enroll e', 's.student_id = e.student_id')
                           ->join('class c', 'e.class_id = c.class_id')
                           ->where('s.student_id', $transaction['student_id'])
                           ->get()->row_array();
        
        // Get cashier details
        $cashier = $this->db->get_where('admin', ['admin_id' => $transaction['collected_by']])->row_array();
        
        // Get school details
        $school = $this->db->get_where('settings', ['type' => 'system_name'])->row();
        
        $page_data['transaction'] = $transaction;
        $page_data['student'] = $student;
        $page_data['cashier'] = $cashier;
        $page_data['school_name'] = $school->description ?? 'School Management System';
        
        $this->load->view('backend/cashier/daily_fee_receipt', $page_data);
    }

    /**
     * Daily collection report
     */
    public function daily_collection_report() {
        $date = $this->input->get('date') ?? date('Y-m-d');
        $timestamp = strtotime($date);
        
        $cashier_id = $this->session->userdata('admin_id');
        
        // Get today's collections by this cashier
        $this->db->select('dft.*, s.name as student_name, s.student_code');
        $this->db->from('daily_fee_transactions dft');
        $this->db->join('student s', 'dft.student_id = s.student_id');
        $this->db->where('dft.collected_by', $cashier_id);
        $this->db->where('DATE(FROM_UNIXTIME(dft.payment_date))', $date);
        $this->db->order_by('dft.created_at', 'DESC');
        
        $collections = $this->db->get()->result_array();
        
        // Calculate totals
        $totals = [
            'feeding' => 0,
            'breakfast' => 0,
            'classes' => 0,
            'water' => 0,
            'transport' => 0,
            'total' => 0,
            'count' => count($collections)
        ];
        
        foreach ($collections as $collection) {
            $totals['feeding'] += $collection['feeding_amount'];
            $totals['breakfast'] += $collection['breakfast_amount'];
            $totals['classes'] += $collection['classes_amount'];
            $totals['water'] += $collection['water_amount'];
            $totals['transport'] += $collection['transport_amount'];
            $totals['total'] += $collection['total_amount'];
        }
        
        $page_data['collections'] = $collections;
        $page_data['totals'] = $totals;
        $page_data['date'] = $date;
        $page_data['page_name'] = 'daily_collection_report';
        $page_data['page_title'] = get_phrase('daily_collection_report');
        
        $this->load->view('backend/main', $page_data);
    }

    /**
     * Update student daily fee preferences
     */
    public function update_student_preferences() {
        $student_id = $this->input->post('student_id');
        $breakfast_subscribed = $this->input->post('breakfast_subscribed') ?? 0;
        $water_subscribed = $this->input->post('water_subscribed') ?? 1;
        $auto_deduct_enabled = $this->input->post('auto_deduct_enabled') ?? 1;
        
        $this->db->where('student_id', $student_id);
        $exists = $this->db->get('student_daily_fee_preferences')->num_rows();
        
        $data = [
            'breakfast_subscribed' => $breakfast_subscribed,
            'water_subscribed' => $water_subscribed,
            'auto_deduct_enabled' => $auto_deduct_enabled,
            'updated_at' => time()
        ];
        
        if ($exists) {
            $this->db->where('student_id', $student_id);
            $this->db->update('student_daily_fee_preferences', $data);
        } else {
            $data['student_id'] = $student_id;
            $this->db->insert('student_daily_fee_preferences', $data);
        }
        
        echo json_encode([
            'status' => 'success',
            'message' => get_phrase('preferences_updated_successfully')
        ]);
    }

    /**
     * Daily Fees Discount Profiles Management (Restricted to daily_fees category only)
     */
    public function discount_profiles($param1 = '') {
        // Only allow daily_fees category for cashiers
        $allowed_category = 'daily_fees';
        
        if($param1 == 'stats') {
            $total = $this->db->where('discount_category', $allowed_category)->count_all_results('discount_profiles');
            $active = $this->db->where('discount_category', $allowed_category)->where('is_active', 1)->count_all_results('discount_profiles');
            $inactive = $this->db->where('discount_category', $allowed_category)->where('is_active', 0)->count_all_results('discount_profiles');
            
            echo json_encode([
                'status' => 'success', 
                'data' => [
                    'total' => $total, 
                    'active' => $active, 
                    'inactive' => $inactive, 
                    'invoice' => 0,  // Always 0 for cashiers
                    'daily_fees' => $total
                ]
            ]);
            return;
        }
        
        if($this->input->get('ajax')) {
            $profiles = $this->db->where('discount_category', $allowed_category)->get('discount_profiles')->result_array();
            echo json_encode(['status' => 'success', 'profiles' => $profiles]);
            return;
        }
        
        if($param1 == 'get_data') {
            $profile_id = $this->input->get('profile_id');
            $profile = $this->db->where('profile_id', $profile_id)->where('discount_category', $allowed_category)->get('discount_profiles')->row_array();
            echo json_encode($profile);
            return;
        }
        
        if($param1 == 'create') {
            $discount_types = $this->input->post('discount_type');
            
            if (is_array($discount_types)) {
                $discount_types = array_filter($discount_types);
                $discount_type_str = implode(',', $discount_types);
            } else {
                $discount_type_str = trim($discount_types);
            }
            
            $data = [
                'profile_name' => $this->input->post('profile_name'),
                'discount_category' => $allowed_category,  // Force daily_fees
                'discount_method' => $this->input->post('discount_method') ?: 'percentage',
                'discount_value' => $this->input->post('discount_value') ?: 0,
                'description' => $this->input->post('description'),
                'is_active' => 1,
                'created_by' => $this->session->userdata('admin_id'),
                'discount_type' => $discount_type_str ?: NULL,
                'bill_item_ids' => NULL
            ];
            
            try {
                $this->db->insert('discount_profiles', $data);
                echo json_encode(['status' => 'success', 'message' => get_phrase('profile_created_successfully')]);
            } catch (Exception $e) {
                $error = $this->db->error();
                if($error['code'] == 1062) {
                    echo json_encode(['status' => 'error', 'message' => 'A profile with this name already exists.']);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $error['message']]);
                }
            }
            return;
        }
        
        if($param1 == 'update') {
            $profile_id = $this->input->post('profile_id');
            $discount_types = $this->input->post('discount_type');
            
            if (is_array($discount_types)) {
                $discount_types = array_filter($discount_types);
                $discount_type_str = implode(',', $discount_types);
            } else {
                $discount_type_str = trim($discount_types);
            }
            
            $data = [
                'profile_name' => $this->input->post('profile_name'),
                'discount_method' => $this->input->post('discount_method') ?: 'percentage',
                'discount_value' => $this->input->post('discount_value') ?: 0,
                'description' => $this->input->post('description'),
                'discount_type' => $discount_type_str ?: NULL
            ];
            
            try {
                $this->db->where('profile_id', $profile_id)
                         ->where('discount_category', $allowed_category)
                         ->update('discount_profiles', $data);
                echo json_encode(['status' => 'success', 'message' => get_phrase('profile_updated_successfully')]);
            } catch (Exception $e) {
                $error = $this->db->error();
                if($error['code'] == 1062) {
                    echo json_encode(['status' => 'error', 'message' => 'A profile with this name already exists.']);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $error['message']]);
                }
            }
            return;
        }
        
        if($param1 == 'delete') {
            $profile_id = $this->input->post('profile_id');
            $this->db->where('profile_id', $profile_id)->where('discount_category', $allowed_category)->delete('discount_profiles');
            echo json_encode(['status' => 'success', 'message' => get_phrase('profile_deleted_successfully')]);
            return;
        }
        
        if($param1 == 'toggle_status') {
            $profile_id = $this->input->post('profile_id');
            $current = $this->db->where('profile_id', $profile_id)->where('discount_category', $allowed_category)->get('discount_profiles')->row();
            if($current) {
                $new_status = $current->is_active ? 0 : 1;
                $this->db->where('profile_id', $profile_id)->where('discount_category', $allowed_category)->update('discount_profiles', ['is_active' => $new_status]);
                echo json_encode(['status' => 'success', 'message' => $new_status ? get_phrase('profile_activated') : get_phrase('profile_deactivated')]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Profile not found']);
            }
            return;
        }
        
        // Load view with daily_fees profiles only
        $page_data['profiles'] = $this->db->where('discount_category', $allowed_category)->get('discount_profiles')->result_array();
        $page_data['restricted_mode'] = true;  // Flag to hide category dropdown
        $page_data['allowed_category'] = $allowed_category;
        $page_data['page_name'] = 'discount_profiles';
        $page_data['page_title'] = get_phrase('daily_fees_discount_profiles');
        $page_data['account_type'] = $this->session->userdata('login_type');
        $this->load->view('backend/main', $page_data);
    }
}

    /****DAILY FEE RATES MANAGEMENT*****/
    function daily_fee_rates($param1 = '', $param2 = '') {
        if ($param1 == 'create') {
            $data = [
                'class_id' => $this->input->post('class_id'),
                'section_id' => $this->input->post('section_id') ?: NULL,
                'year' => $this->input->post('year'),
                'term' => $this->input->post('term'),
                'feeding_rate' => $this->input->post('feeding_rate') ?: 0,
                'classes_rate' => $this->input->post('classes_rate') ?: 0,
                'transport_rate' => $this->input->post('transport_rate') ?: 0,
                'breakfast_rate' => $this->input->post('breakfast_rate') ?: 0,
                'water_rate' => $this->input->post('water_rate') ?: 0,
                'created_at' => time()
            ];
            $this->db->insert('daily_fee_rates', $data);
            echo json_encode(['status' => 'success', 'message' => get_phrase('rates_saved_successfully')]);
            return;
        }

        if ($param1 == 'update' && $param2) {
            $data = [
                'feeding_rate' => $this->input->post('feeding_rate') ?: 0,
                'classes_rate' => $this->input->post('classes_rate') ?: 0,
                'transport_rate' => $this->input->post('transport_rate') ?: 0,
                'breakfast_rate' => $this->input->post('breakfast_rate') ?: 0,
                'water_rate' => $this->input->post('water_rate') ?: 0,
                'updated_at' => time()
            ];
            $this->db->where('id', $param2);
            $this->db->update('daily_fee_rates', $data);
            echo json_encode(['status' => 'success', 'message' => get_phrase('rates_updated_successfully')]);
            return;
        }
        $page_data['page_name'] = 'daily_fee_rates';
        $page_data['page_title'] = get_phrase('daily_fee_rates');
        $page_data['account_type'] = $this->session->userdata('login_type');
        $this->load->view('backend/main', $page_data);
    }

    function get_rate_data($rate_id) {
        $rate = $this->db->get_where('daily_fee_rates', ['id' => $rate_id])->row();
        echo json_encode($rate);
    }

    function daily_fee_rates_bulk_save() {
        $class_ids = $this->input->post('class_ids');
        $rate_ids = $this->input->post('rate_ids');
        $feeding_rates = $this->input->post('feeding_rates');
        $classes_rates = $this->input->post('classes_rates');
        $transport_rates = $this->input->post('transport_rates');
        $breakfast_rates = $this->input->post('breakfast_rates');
        $water_rates = $this->input->post('water_rates');
        $section_ids = $this->input->post('section_ids');
        $running_year = get_settings('running_year');
        $running_term = get_settings('running_term');

        $created = 0;
        $updated = 0;
        $errors = [];

        for ($i = 0; $i < count($class_ids); $i++) {
            $data = [
                'class_id' => $class_ids[$i],
                'section_id' => !empty($section_ids[$i]) ? $section_ids[$i] : NULL,
                'year' => $running_year,
                'feeding_rate' => floatval($feeding_rates[$i]),
                'classes_rate' => floatval($classes_rates[$i]),
                'transport_rate' => floatval($transport_rates[$i]),
                'breakfast_rate' => floatval($breakfast_rates[$i]),
                'water_rate' => floatval($water_rates[$i])
            ];

            if (!empty($rate_ids[$i])) {
                $data['updated_at'] = time();
                $this->db->where('id', $rate_ids[$i]);
                $this->db->update('daily_fee_rates', $data);
                $updated++;
            } else {
                $data['term'] = $running_term;
                $data['created_at'] = time();
                $this->db->insert('daily_fee_rates', $data);
                $created++;
            }
        }

        echo json_encode([
            'status' => 'success', 
            'message' => "Successfully saved: $created created, $updated updated",
            'created' => $created,
            'updated' => $updated
        ]);
    }

    function daily_fee_rates_content() {
        $running_year = get_settings('running_year');
        $running_term = get_settings('running_term');
        $classes = $this->db->order_by('name_numeric', 'ASC')->get('class')->result_array();
        
        foreach ($classes as $class):
            $sections = $this->db->get_where('section', ['class_id' => $class['class_id']])->result_array();
            if (empty($sections)) {
                $rate = $this->db->get_where('daily_fee_rates', [
                    'section_id' => NULL,
                    'class_id' => $class['class_id'],
                    'year' => $running_year,
                    'term' => $running_term
                ])->row();
                ?>
                <tr>
                    <input type="hidden" name="class_ids[]" value="<?php echo $class['class_id']; ?>">
                    <input type="hidden" name="section_ids[]" value="">
                    <input type="hidden" name="rate_ids[]" value="<?php echo $rate->id ?? ''; ?>">
                    <td><?php echo $class['name']; ?></td>
                    <td><span class="badge badge-secondary">No Sections</span></td>
                    <td><input type="number" step="0.01" name="feeding_rates[]" class="form-control modern-input" value="<?php echo $rate->feeding_rate ?? '0.00'; ?>"></td>
                    <td><input type="number" step="0.01" name="classes_rates[]" class="form-control modern-input" value="<?php echo $rate->classes_rate ?? '0.00'; ?>"></td>
                    <td><input type="number" step="0.01" name="transport_rates[]" class="form-control modern-input" value="<?php echo $rate->transport_rate ?? '0.00'; ?>"></td>
                    <td><input type="number" step="0.01" name="breakfast_rates[]" class="form-control modern-input" value="<?php echo $rate->breakfast_rate ?? '0.00'; ?>"></td>
                    <td><input type="number" step="0.01" name="water_rates[]" class="form-control modern-input" value="<?php echo $rate->water_rate ?? '0.00'; ?>"></td>
                </tr>
                <?php
            } else {
                foreach ($sections as $section):
                    $rate = $this->db->get_where('daily_fee_rates', [
                        'section_id' => $section['section_id'],
                        'class_id' => $class['class_id'],
                        'year' => $running_year,
                        'term' => $running_term
                    ])->row();
                    ?>
                    <tr>
                        <input type="hidden" name="class_ids[]" value="<?php echo $class['class_id']; ?>">
                        <input type="hidden" name="section_ids[]" value="<?php echo $section['section_id']; ?>">
                        <input type="hidden" name="rate_ids[]" value="<?php echo $rate->id ?? ''; ?>">
                        <td><?php echo $class['name']; ?></td>
                        <td><span class="badge badge-info"><?php echo $section['section_name']; ?></span></td>
                        <td><input type="number" step="0.01" name="feeding_rates[]" class="form-control modern-input" value="<?php echo $rate->feeding_rate ?? '0.00'; ?>"></td>
                        <td><input type="number" step="0.01" name="classes_rates[]" class="form-control modern-input" value="<?php echo $rate->classes_rate ?? '0.00'; ?>"></td>
                        <td><input type="number" step="0.01" name="transport_rates[]" class="form-control modern-input" value="<?php echo $rate->transport_rate ?? '0.00'; ?>"></td>
                        <td><input type="number" step="0.01" name="breakfast_rates[]" class="form-control modern-input" value="<?php echo $rate->breakfast_rate ?? '0.00'; ?>"></td>
                        <td><input type="number" step="0.01" name="water_rates[]" class="form-control modern-input" value="<?php echo $rate->water_rate ?? '0.00'; ?>"></td>
                    </tr>
                    <?php
                endforeach;
            }
        endforeach;
    }

    public function import_daily_fee_rates_form() {
        $this->load->view('backend/admin/import_daily_fee_rates_modal');
    }

    public function import_daily_fee_rates() {
        $rates_json = $this->input->post('rates');
        $rates = json_decode($rates_json, true);

        if (!$rates || count($rates) == 0) {
            echo json_encode(['status' => 'error', 'message' => 'No rates to import']);
            return;
        }

        $running_year = get_settings('running_year');
        $running_term = get_settings('running_term');
        $created = 0;
        $updated = 0;

        foreach ($rates as $rate) {
            $existing = $this->db->get_where('daily_fee_rates', [
                'section_id' => $rate['section_id'] ?: NULL,
                'class_id' => $rate['class_id'],
                'year' => $running_year,
                'term' => $running_term
            ])->row();

            $data = [
                'class_id' => $rate['class_id'],
                'section_id' => $rate['section_id'] ?: NULL,
                'feeding_rate' => floatval($rate['feeding_rate']),
                'classes_rate' => floatval($rate['classes_rate']),
                'transport_rate' => floatval($rate['transport_rate']),
                'breakfast_rate' => floatval($rate['breakfast_rate']),
                'water_rate' => floatval($rate['water_rate']),
                'updated_at' => time()
            ];

            if ($existing) {
                $this->db->where('id', $existing->id);
                $this->db->update('daily_fee_rates', $data);
                $updated++;
            } else {
                $data['year'] = $running_year;
                $data['term'] = $running_term;
                $data['created_at'] = time();
                $this->db->insert('daily_fee_rates', $data);
                $created++;
            }
        }

        echo json_encode([
            'status' => 'success',
            'message' => "Rates imported: $created created, $updated updated",
            'created' => $created,
            'updated' => $updated
        ]);
    }

    public function get_previous_term_rates() {
        $year = $this->input->post('year');
        $term = $this->input->post('term');

        $rates = $this->db->select('r.*, c.name as class_name, c.name_numeric')
            ->from('daily_fee_rates r')
            ->join('class c', 'c.class_id = r.class_id')
            ->where('r.year', $year)
            ->where('r.term', $term)
            ->order_by('c.name_numeric', 'ASC')
            ->get()
            ->result_array();

        echo json_encode(['status' => 'success', 'rates' => $rates]);
    }
}
