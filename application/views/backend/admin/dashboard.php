<?php
// Check if user is cashier and load cashier dashboard
$admin_id = $this->session->userdata('admin_id');
$admin_level = $this->db->get_where('admin', array('admin_id' => $admin_id))->row()->level;

if ($admin_level == 4) {
	// Load cashier dashboard
	$this->load->view('backend/admin/dashboard_cashier');
	return;
}

$this->db->select('student_id');
$this->db->from('enroll');
$this->db->where('mute', '0');
$this->db->where('year', $running_year);
$this->db->where('term', $running_term);
$raw_info = $this->db->get();
$st_ids = $raw_info->result_array();

if ($raw_info->num_rows() != 0) {
	$st_ids_array = array();
	$i = 0;
	foreach ($st_ids as $row) {
		$st_ids_array[$i] = $row['student_id'];
		$i++;
	}
	$this->db->select('parent_id');
	$this->db->distinct();
	$this->db->from('student');
	$this->db->where_in('student_id', $st_ids_array);
	$pt_ids = $this->db->get()->result_array();
	$pt_ids_array = array();
	$j = 0;
	foreach ($pt_ids as $row2) {
		$pt_ids_array[$j] = $row2['parent_id'];
		$j++;
	}
} else {
	$pt_ids_array = array(0);
}

$un_year = $running_year;
$forYear = 'yes';
$search = isset($search) ? $search : '';

if ($search == 'search') {
	$un_year = $year;
	$un_term = $term;
	$un_sem = $sem;
	$timestamp = $date;
} else {
	$un_year = $running_year;
	$un_term = $running_term;
	$un_sem = $running_sem;
	$timestamp = strtotime(date('d-m-Y'));
}

$name = $this->db->get_where($this->session->userdata('login_type'), array($this->session->userdata('login_type') . '_id' => $this->session->userdata('login_user_id')))->row()->name;
$admin_level = $this->db->get_where('admin', array('name' => $name))->row()->level;

// Get currency
$currency = $this->db->get_where('settings', array('type' => 'currency'))->row()->description;

// Get student credits statistics (with safety check)
$total_active_credits = 0;
$students_with_credits = 0;
if (file_exists(APPPATH . 'models/Credit_model.php')) {
    try {
        $this->load->model('Credit_model');
        if (isset($this->Credit_model) && method_exists($this->Credit_model, 'get_credit_statistics')) {
            $credit_stats = $this->Credit_model->get_credit_statistics();
            $total_active_credits = $credit_stats['total_active_credits'];
            $students_with_credits = $credit_stats['students_with_credits'];
        }
    } catch (Exception $e) {
        // Silently fail - credit statistics are optional
        log_message('error', 'Credit_model error: ' . $e->getMessage());
    }
}

// Get daily revenue from daily_fee_transactions for the selected date
$this->load->model('Daily_fee_model');
$daily_revenue = $this->Daily_fee_model->get_daily_revenue_by_date($timestamp);

// Get financial data - Current Term
$this->db->select_sum('amount');
$this->db->where('year', $un_year);
$this->db->where('term', $un_term);
$this->db->where('payment_type', 'income');
$this->db->where('can_delete !=', 'trash');
$total_revenue = $this->db->get('payment')->row()->amount ?? 0;
$total_revenue = floatval($total_revenue); // Ensure numeric value

// Previous term for comparison
$prev_term = $un_term > 1 ? $un_term - 1 : 3;
// Handle year format "2025-2026" - if term is 1, we need previous year "2024-2025"
if ($un_term > 1) {
	$prev_year = $un_year;
} else {
	// Extract start and end years from format "2025-2026"
	$year_parts = explode('-', $un_year);
	if (count($year_parts) == 2) {
		$start_year = intval($year_parts[0]) - 1;
		$end_year = intval($year_parts[1]) - 1;
		$prev_year = $start_year . '-' . $end_year;
	} else {
		// Fallback if format is different
		$prev_year = $un_year;
	}
}
$this->db->select_sum('amount');
$this->db->where('year', $prev_year);
$this->db->where('term', $prev_term);
$this->db->where('payment_type', 'income');
$this->db->where('can_delete !=', 'trash');
$prev_revenue = $this->db->get('payment')->row()->amount ?? 0;
$prev_revenue = floatval($prev_revenue); // Ensure numeric value
$revenue_change = $prev_revenue > 0 ? round((($total_revenue - $prev_revenue) / $prev_revenue) * 100, 1) : 0;

// Total billed vs collected (Collection Rate)
$this->db->select_sum('amount');
$this->db->where('year', $un_year);
$this->db->where('term', $un_term);
$total_billed = $this->db->get('invoice')->row()->amount ?? 0;
$total_billed = floatval($total_billed); // Ensure numeric value
$collection_rate = $total_billed > 0 ? round(($total_revenue / $total_billed) * 100, 1) : 0;

// Get pending payments - split by mute status
// Active students with unpaid invoices (mute = 0)
$active_pending_query = "
	SELECT COUNT(DISTINCT i.student_id) as total 
	FROM invoice i
	INNER JOIN enroll e ON i.student_id = e.student_id 
		AND i.year = e.year 
		AND i.term = e.term
	WHERE i.status = 'unpaid' 
		AND e.mute = '0'
		AND i.year = ?
		AND i.term = ?
";
$active_pending_payments = $this->db->query($active_pending_query, [$un_year, $un_term])->row()->total ?? 0;

// Muted students with unpaid invoices (mute = 1) - Bad Debt
$bad_debt_query = "
	SELECT COUNT(DISTINCT i.student_id) as total 
	FROM invoice i
	INNER JOIN enroll e ON i.student_id = e.student_id 
		AND i.year = e.year 
		AND i.term = e.term
	WHERE i.status = 'unpaid' 
		AND e.mute = '1'
		AND i.year = ?
		AND i.term = ?
";
$bad_debt_count = $this->db->query($bad_debt_query, [$un_year, $un_term])->row()->total ?? 0;

// Keep old variable for backward compatibility (use active pending)
$pending_payments = $active_pending_payments;

// Get class distribution
$classes = $this->db->get('class')->result_array();
?>

<style>
.currency-symbol { font-size: 0.6em; vertical-align: super; margin-right: 2px; }
.form-label { font-weight: 600; color: #374151; font-size: 13px; margin-bottom: 8px; display: block; }
.form-control { border: 2px solid #e5e7eb; border-radius: 10px; padding: 10px 14px; font-size: 14px; transition: all 0.3s; height: 42px; width: 100%; }
.form-control:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1); outline: none; }
.btn-modern { padding: 10px 20px; border: none; border-radius: 10px; font-weight: 600; font-size: 14px; cursor: pointer; transition: all 0.3s; display: inline-flex; align-items: center; justify-content: center; gap: 8px; height: 42px; }
.btn-primary-modern { background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: white; box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3); }
.btn-primary-modern:hover { background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%); box-shadow: 0 6px 16px rgba(59, 130, 246, 0.4); transform: translateY(-2px); }
@keyframes fadeInUp {
	from { opacity: 0; transform: translateY(20px); }
	to { opacity: 1; transform: translateY(0); }
}
@keyframes shimmer {
	0% { background-position: -1000px 0; }
	100% { background-position: 1000px 0; }
}
@keyframes pulse {
	0%, 100% { opacity: 1; }
	50% { opacity: 0.8; }
}
.dashboard-card {
	background: #fff;
	border-radius: 16px;
	box-shadow: 0 4px 12px rgba(0,0,0,0.06);
	padding: 28px;
	transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
	position: relative;
	overflow: hidden;
	animation: fadeInUp 0.6s ease-out;
}
.dashboard-card::before {
	content: '';
	position: absolute;
	top: 0;
	left: -100%;
	width: 100%;
	height: 100%;
	background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
	transition: left 0.5s;
}
.dashboard-card:hover {
	transform: translateY(-8px) scale(1.02);
	box-shadow: 0 12px 28px rgba(0,0,0,0.15);
}
.dashboard-card:hover::before {
	left: 100%;
}
.stat-icon {
	width: 64px;
	height: 64px;
	border-radius: 16px;
	display: flex;
	align-items: center;
	justify-content: center;
	font-size: 30px;
	color: #fff;
	box-shadow: 0 4px 12px rgba(0,0,0,0.15);
	transition: transform 0.3s;
}
.dashboard-card:hover .stat-icon {
	transform: rotate(5deg) scale(1.1);
}
.quick-action-btn {
	background: #fff;
	border: 2px solid #e5e7eb;
	border-radius: 12px;
	padding: 24px;
	text-align: center;
	transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
	cursor: pointer;
	position: relative;
	overflow: hidden;
}
.quick-action-btn::after {
	content: '';
	position: absolute;
	top: 50%;
	left: 50%;
	width: 0;
	height: 0;
	border-radius: 50%;
	background: rgba(59, 130, 246, 0.1);
	transform: translate(-50%, -50%);
	transition: width 0.6s, height 0.6s;
}
.quick-action-btn:hover::after {
	width: 300px;
	height: 300px;
}
.quick-action-btn:hover {
	border-color: #3b82f6;
	background: #eff6ff;
	transform: translateY(-4px);
	box-shadow: 0 8px 20px rgba(59, 130, 246, 0.2);
}
.quick-action-btn i {
	transition: transform 0.3s;
}
.quick-action-btn:hover i {
	transform: scale(1.2);
}
.chart-container {
	position: relative;
	height: 320px;
}
.metric-badge {
	animation: pulse 2s infinite;
}
.gradient-card {
	position: relative;
	overflow: hidden;
}
.gradient-card::after {
	content: '';
	position: absolute;
	top: -50%;
	right: -50%;
	width: 200%;
	height: 200%;
	background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
	opacity: 0;
	transition: opacity 0.3s;
}
.gradient-card:hover::after {
	opacity: 1;
}
@media (max-width: 768px) {
	.chart-container { height: 280px; }
	.dashboard-card { padding: 20px; }
	.stat-icon { width: 56px; height: 56px; font-size: 26px; }
}
</style>

<div class="p-4 md:p-6 bg-gray-50 min-h-screen">
	<!-- Header Section -->
	<div class="mb-6 md:mb-8">
		<div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
			<div>
				<h1 class="text-2xl sm:text-3xl lg:text-4xl font-bold text-gray-800 mb-1">Dashboard Overview</h1>
				<p class="text-lg sm:text-base lg:text-lg text-gray-600">Welcome back! Here's what's happening in your school today.</p>
			</div>
			<div class="flex items-center gap-2 text-lg text-gray-600">
				<i class="fa fa-calendar"></i>
				<span class="font-medium"><?php echo date('l, F d, Y'); ?></span>
			</div>
		</div>
	</div>

	<!-- Filter Section (Super Admin Only) -->
	<?php if ($account_type == 'admin' && $admin_level == 1): ?>
	<div class="dashboard-card mb-6">
		<h3 class="text-xl font-semibold text-gray-700 mb-4">Filter Data</h3>
		<?php echo form_open(site_url('admin/dashboard/search')); ?>
		<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
			<div>
				<label class="form-label">Date</label>
				<input type="text" name="date_sel" id="date_sel" class="form-control datepicker" data-format="dd-mm-yyyy" value="<?php echo date('d-m-Y', $timestamp); ?>">
			</div>
			<div>
				<label class="form-label">Term</label>
				<select name="term" class="form-control">
					<option value="">Select Term</option>
					<?php for ($i = 1; $i <= 3; $i++): ?>
					<option value="<?php echo $i; ?>" <?php if ($un_term == $i) echo 'selected'; ?>><?php echo $i; ?></option>
					<?php endfor; ?>
				</select>
			</div>
			<div>
				<label class="form-label">Year</label>
				<select name="year" class="form-control">
					<option value="">Select Year</option>
					<?php echo populate_academic_year($forYear, $un_year); ?>
				</select>
			</div>
			<div>
				<label class="form-label">&nbsp;</label>
				<button type="submit" class="btn-modern btn-primary-modern w-full">
					<i class="fa fa-search"></i> Search
				</button>
			</div>
		</div>
		<?php echo form_close(); ?>
	</div>
	<?php endif; ?>

	<!-- Key Metrics Cards -->
	<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6 mb-6" style="animation-delay: 0.1s;">
		<!-- Active Students -->
		<a href="<?php echo site_url('admin/all_students/' . $un_year . '/' . $un_term . '/' . $un_sem); ?>" class="block">
			<div class="dashboard-card" style="border-left: 5px solid #667eea; padding: 20px;">
				<div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
					<div class="flex flex-col items-start gap-2">
						<div class="stat-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); width: 56px; height: 56px; font-size: 26px;">
							<i class="fa fa-users"></i>
						</div>
						<h3 class="text-gray-700 font-medium" style="font-size: 13px;">Total Students</h3>
					</div>
					<div class="text-right ml-auto">
						<p class="text-4xl font-extrabold text-gray-900 leading-none mb-1">
							<?php
							$this->db->select('student_id');
							$this->db->distinct();
							$this->db->where('year', $un_year);
							$this->db->where('term', $un_term);
							$this->db->where('mute', '0');
							echo $this->db->get('enroll')->num_rows();
							?>
						</p>
						<p class="text-gray-600" style="font-size: 12px;">Currently enrolled</p>
					</div>
				</div>
			</div>
		</a>

		<!-- Teachers -->
		<a href="<?php echo site_url('admin/teacher'); ?>" class="block">
			<div class="dashboard-card" style="border-left: 5px solid #f5576c; padding: 20px;">
				<div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
					<div class="flex flex-col items-start gap-2">
						<div class="stat-icon" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); width: 56px; height: 56px; font-size: 26px;">
							<i class="entypo-users"></i>
						</div>
						<h3 class="text-gray-700 font-medium" style="font-size: 13px;">Active Teachers</h3>
					</div>
					<div class="text-right ml-auto">
						<p class="text-4xl font-extrabold text-gray-900 leading-none mb-1"><?php echo $this->db->get_where('teacher', array('active_status' => 1))->num_rows(); ?></p>
						<p class="text-gray-600" style="font-size: 12px;">Teaching staff</p>
					</div>
				</div>
			</div>
		</a>

		<!-- Parents -->
		<a href="<?php echo site_url('admin/parent'); ?>" class="block">
			<div class="dashboard-card" style="border-left: 5px solid #00f2fe; padding: 20px;">
				<div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
					<div class="flex flex-col items-start gap-2">
						<div class="stat-icon" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); width: 56px; height: 56px; font-size: 26px;">
							<i class="entypo-user"></i>
						</div>
						<h3 class="text-gray-700 font-medium" style="font-size: 13px;">Active Parents</h3>
					</div>
					<div class="text-right ml-auto">
						<p class="text-4xl font-extrabold text-gray-900 leading-none mb-1">
							<?php
							$this->db->where_in('parent_id', $pt_ids_array);
							echo $this->db->get('parent')->num_rows();
							?>
						</p>
						<p class="text-gray-600" style="font-size: 12px;">Registered guardians</p>
					</div>
				</div>
			</div>
		</a>

		<!-- Attendance Today -->
		<a href="<?php echo site_url('admin/students_att/' . $timestamp); ?>" class="block" target="_blank" rel="noopener noreferrer">
			<div class="dashboard-card" style="border-left: 5px solid #fee140; padding: 20px;">
				<div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
					<div class="flex flex-col items-start gap-2">
						<div class="stat-icon" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); width: 56px; height: 56px; font-size: 26px;">
							<i class="entypo-chart-bar"></i>
						</div>
						<h3 class="text-gray-700 font-medium" style="font-size: 13px;">Attendance</h3>
					</div>
					<div class="text-right ml-auto">
						<p class="text-4xl font-extrabold text-gray-900 leading-none mb-1">
							<?php
							$check = array('timestamp' => $timestamp, 'status' => '1');
							$present_count = $this->db->get_where('attendance', $check)->num_rows();
							$late_count = $this->db->where(['timestamp' => $timestamp, 'status' => '3'])->count_all_results('attendance');
							echo $present_count + $late_count;
							?>
						</p>
						<p class="text-gray-600" style="font-size: 12px;">Present <?php echo ($timestamp == strtotime(date('d-m-Y'))) ? 'today' : date('M d', $timestamp); ?></p>
					</div>
				</div>
			</div>
		</a>
	</div>

	<!-- Financial Overview (Super Admin, Accountant, and Admin Level 2) -->
	<?php if (($account_type == 'admin' && ($admin_level <= 3))): ?>
	<div class="grid grid-cols-1 lg:grid-cols-4 gap-4 md:gap-6 mb-6">
		<!-- Daily Revenue Card - Clickable -->
		<a href="javascript:;" onclick="showDailyRevenueModal(<?php echo $timestamp; ?>)" class="block cursor-pointer" style="text-decoration: none;">
			<div class="dashboard-card hover:shadow-lg transition-shadow duration-300" style="border-left: 5px solid #43e97b; padding: 20px; min-height: 180px; display: flex; align-items: center;">
				<div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3" style="width: 100%;">
					<div class="flex flex-col items-start gap-2">
						<div class="stat-icon" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); width: 56px; height: 56px; font-size: 26px;">
							<i class="entypo-chart-line"></i>
						</div>
						<h3 class="text-gray-700 font-medium" style="font-size: 13px;">Daily Revenue</h3>
					</div>
					<div class="text-right ml-auto">
						<p class="text-4xl font-extrabold text-gray-900 leading-none mb-1"><span class="currency-symbol"><?php echo $currency; ?></span><?php echo number_format($daily_revenue, 2); ?></p>
						<p class="text-gray-600" style="font-size: 12px;"><?php echo ($timestamp == strtotime(date('d-m-Y'))) ? 'Today' : date('M d, Y', $timestamp); ?></p>
					</div>
				</div>
			</div>
		</a>

		<div class="dashboard-card" style="border-left: 5px solid #667eea; padding: 20px; min-height: 180px; display: flex; align-items: center;">
			<div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3" style="width: 100%;">
				<div class="flex flex-col items-start gap-2">
					<div class="stat-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); width: 56px; height: 56px; font-size: 26px;">
						<i class="entypo-gauge"></i>
					</div>
					<h3 class="text-gray-700 font-medium" style="font-size: 13px;">Collection Rate</h3>
				</div>
				<div class="text-right ml-auto">
					<p class="text-4xl font-extrabold text-gray-900 leading-none mb-1"><?php echo $collection_rate; ?>%</p>
					<p class="text-gray-700" style="font-size: 12px;">Collected: <span class="currency-symbol"><?php echo $currency; ?></span><?php echo number_format($total_revenue, 0); ?></p>
					<span class="text-xs font-bold px-2 py-1 rounded-full <?php echo $collection_rate >= 80 ? 'bg-green-100 text-green-700' : ($collection_rate >= 60 ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-700'); ?> mt-1 inline-block">
						<?php echo $collection_rate >= 80 ? 'Excellent' : ($collection_rate >= 60 ? 'Good' : 'Needs Attention'); ?>
					</span>
				</div>
			</div>
		</div>
		
		<!-- Student Credits Card - NEW -->
		<a href="<?php echo site_url('admin/student_credits'); ?>" class="block cursor-pointer" style="text-decoration: none;">
			<div class="dashboard-card hover:shadow-lg transition-shadow duration-300" style="border-left: 5px solid #10b981; padding: 20px; min-height: 180px; display: flex; align-items: center;">
				<div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3" style="width: 100%;">
					<div class="flex flex-col items-start gap-2">
						<div class="stat-icon" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); width: 56px; height: 56px; font-size: 26px;">
							<i class="fa fa-gift"></i>
						</div>
						<h3 class="text-gray-700 font-medium" style="font-size: 13px;">Student Credits</h3>
					</div>
					<div class="text-right ml-auto">
						<p class="text-4xl font-extrabold text-gray-900 leading-none mb-1"><span class="currency-symbol"><?php echo $currency; ?></span><?php echo number_format($total_active_credits, 0); ?></p>
						<p class="text-gray-700" style="font-size: 12px;"><?php echo $students_with_credits; ?> students</p>
						<?php if ($students_with_credits > 0): ?>
						<span class="text-xs font-bold px-2 py-1 rounded-full bg-green-100 text-green-700 mt-1 inline-block">
							<i class="fa fa-check-circle mr-1"></i>Prepaid Balances
						</span>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</a>

		<div class="dashboard-card" style="border-left: 5px solid #fa709a; padding: 20px; min-height: 180px; display: flex; align-items: center;">
			<div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3" style="width: 100%;">
				<div class="flex flex-col items-start gap-2">
					<div class="stat-icon" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); width: 56px; height: 56px; font-size: 26px;">
						<i class="entypo-credit-card"></i>
					</div>
					<h3 class="text-gray-700 font-medium" style="font-size: 13px;">Outstanding Debt</h3>
				</div>
				<div class="text-right ml-auto">
					<!-- Outstanding Debt - Clickable -->
					<a href="javascript:;" onclick="showAjaxModal('<?php echo site_url('modal/popup/modal_outstanding_debt/'.$un_term .'/'. $un_year);?>', 'modal_outstanding_debt');" style="text-decoration: none; color: inherit;">
						<p class="text-4xl font-extrabold text-gray-900 leading-none mb-1 hover:text-orange-600 transition-colors" style="cursor: pointer;"><?php echo $active_pending_payments; ?></p>
						<p class="text-orange-700 hover:text-orange-800 transition-colors" style="font-size: 12px; cursor: pointer;">Active students with unpaid bills</p>
						<?php if ($active_pending_payments > 0): ?>
						<span class="text-xs font-bold px-2 py-1 rounded-full bg-orange-100 text-orange-700 metric-badge mt-1 inline-block">
							Action Needed
						</span>
						<?php endif; ?>
					</a>
					
					<!-- Bad Debt - Clickable -->
					<?php if ($bad_debt_count > 0): ?>
					<a href="javascript:;" onclick="showAjaxModal('<?php echo site_url('modal/popup/modal_bad_debt/'.$un_term .'/'. $un_year);?>', 'modal_bad_debt');" style="text-decoration: none; color: inherit;">
						<div class="mt-2 pt-2 border-t border-gray-200 hover:bg-red-50 transition-colors" style="cursor: pointer; padding: 4px; border-radius: 4px;">
							<p class="text-2xl font-bold text-red-600 hover:text-red-700 transition-colors"><?php echo $bad_debt_count; ?></p>
							<p class="text-red-600 hover:text-red-700 transition-colors" style="font-size: 11px;">Bad Debt (muted students)</p>
						</div>
					</a>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</div>
	<?php endif; ?>

	<!-- Charts Section -->
	<div class="grid grid-cols-1 lg:grid-cols-2 gap-4 md:gap-6 mb-6">
		<!-- Student Distribution by Class -->
		<div class="dashboard-card">
			<h3 class="text-xl font-semibold text-gray-800 mb-4">Student Distribution by Class</h3>
			<div class="chart-container">
				<canvas id="studentDistributionChart"></canvas>
			</div>
		</div>

		<!-- Attendance Trend Chart -->
		<div class="dashboard-card">
			<h3 class="text-xl font-semibold text-gray-800 mb-4">Attendance Trend (Last 7 Days)</h3>
			<div class="chart-container">
				<canvas id="attendanceTrendChart"></canvas>
			</div>
		</div>
	</div>

	<!-- Gender and Residential Distribution by Class -->
	<div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6 mb-6">
		<!-- Gender Distribution by Class -->
		<div class="dashboard-card">
			<h3 class="text-xl font-semibold text-gray-800 mb-4">Gender Distribution by Class</h3>
			<div class="chart-container">
				<canvas id="genderDistributionChart"></canvas>
			</div>
		</div>

		<!-- Residential Distribution by Class -->
		<div class="dashboard-card">
			<h3 class="text-xl font-semibold text-gray-800 mb-4">Residential Distribution by Class</h3>
			<div class="chart-container">
				<canvas id="residentialDistributionChart"></canvas>
			</div>
		</div>
	</div>

	<!-- Quick Actions -->
	<?php if ($account_type == 'admin' && $admin_level != 4): // Hide for cashiers ?>
	<div class="dashboard-card mb-6">
		<h3 class="text-2xl font-semibold text-gray-800 mb-4">Quick Actions</h3>
		<div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 md:gap-4">
			<div class="quick-action-btn" onclick="navigation('<?php echo site_url('admin/student_add'); ?>')">
				<i class="entypo-user-add text-4xl text-blue-600 mb-2"></i>
				<p class="text-base font-bold text-gray-800">Add Student</p>
			</div>
			<div class="quick-action-btn" onclick="navigation('<?php echo site_url('admin/manage_attendance'); ?>')">
				<i class="entypo-check text-4xl text-green-600 mb-2"></i>
				<p class="text-base font-bold text-gray-800">Attendance</p>
			</div>
			<div class="quick-action-btn" onclick="navigation('<?php echo site_url('admin/student_invoice'); ?>')">
				<i class="entypo-credit-card text-4xl text-purple-600 mb-2"></i>
				<p class="text-base font-bold text-gray-800">Billing</p>
			</div>
			<div class="quick-action-btn" onclick="showAjaxModal('<?php echo site_url('modal/popup/modal_take_payment/0'); ?>', 'take_payment')">
				<i class="entypo-credit-card text-4xl text-red-600 mb-2"></i>
				<p class="text-base font-bold text-gray-800">Take Payment</p>
			</div>
			<div class="quick-action-btn" onclick="navigation('<?php echo site_url('admin/message'); ?>')">
				<i class="entypo-mail text-4xl text-indigo-600 mb-2"></i>
				<p class="text-base font-bold text-gray-800">Messages</p>
			</div>
			<div class="quick-action-btn" onclick="navigation('<?php echo site_url('admin/send_bill_reminder'); ?>')">
				<i class="entypo-paper-plane text-4xl text-pink-600 mb-2"></i>
				<p class="text-base font-bold text-gray-800">Bill Reminders</p>
			</div>
		</div>
	</div>
	<?php endif; ?>



	<!-- Financial Summary (Super Admin Only) -->
	<?php if ($account_type == 'admin' && $admin_level == 1): ?>
	<?php
	// Fetch financial data
	$currency = $this->db->get_where('settings', array('type' => 'currency'))->row()->description;
	$fmt = new NumberFormatter('en_US', NumberFormatter::CURRENCY);

	// Unpaid Invoices - Query directly from invoice table where mute = '0'
	$unpaid_count_query = "
		SELECT COUNT(DISTINCT invoice_code) as total
		FROM invoice
		WHERE due > 0
			AND mute = '0'
			AND can_delete != 'trash'
	";
	$unpaid_invoices_count = $this->db->query($unpaid_count_query)->row()->total ?? 0;

	$unpaid_amount_query = "
		SELECT SUM(due) as total_due
		FROM invoice
		WHERE due > 0
			AND mute = '0'
			AND can_delete != 'trash'
	";
	$unpaid_amount = $this->db->query($unpaid_amount_query)->row()->total_due ?? 0;
	$unpaid_amount = floatval($unpaid_amount); // Ensure numeric value

	// Total Invoices Due (All invoices with any due amount - active students only)
	$total_invoices_due_query = "
		SELECT SUM(due) as total_due
		FROM invoice
		WHERE due > 0
			AND mute = '0'
			AND year = ?
			AND term = ?
			AND can_delete != 'trash'
	";
	$total_invoices_due = $this->db->query($total_invoices_due_query, [$un_year, $un_term])->row()->total_due ?? 0;
	$total_invoices_due = floatval($total_invoices_due); // Ensure numeric value

	// Total Income
	$this->db->select('receipt_code');
	$this->db->distinct();
	$this->db->where('year', $un_year);
	$this->db->where('term', $un_term);
	$this->db->where('payment_type', 'income');
	$this->db->where('can_delete !=', 'trash');
	$receipt_count = $this->db->get('payment')->num_rows();

	$this->db->select_sum('amount');
	$this->db->where('year', $un_year);
	$this->db->where('term', $un_term);
	$this->db->where('payment_type', 'income');
	$this->db->where('can_delete !=', 'trash');
	$total_income_raw = $this->db->get('payment')->row()->amount ?? 0;
	$total_income_raw = floatval($total_income_raw); // Ensure numeric value

	// Calculate "Total Invoices Due" = Unpaid Invoices + Total Income (moved after $total_income_raw is defined)
	$total_invoices_due_combined = $unpaid_amount + $total_income_raw;
	// Total Expenses
	$this->db->select_sum('amount');
	$this->db->where('year', $un_year);
	$this->db->where('term', $un_term);
	$this->db->where('payment_type', 'expense');
	$this->db->where('can_delete !=', 'trash');
	$total_expense_raw = $this->db->get('payment')->row()->amount ?? 0;
	$total_expense_raw = floatval($total_expense_raw); // Ensure numeric value

	// Use new daily_fee_transactions and daily_fee_wallet tables
	if ($this->db->table_exists('daily_fee_transactions')) {
		// Get total payments from transactions
		$this->db->select_sum('feeding_amount');
		$this->db->where('year', $un_year);
		$this->db->where('term', $un_term);
		$total_feeding_paid = $this->db->get('daily_fee_transactions')->row()->feeding_amount ?? 0;

		$this->db->select_sum('classes_amount');
		$this->db->where('year', $un_year);
		$this->db->where('term', $un_term);
		$total_classes_paid = $this->db->get('daily_fee_transactions')->row()->classes_amount ?? 0;

		$this->db->select_sum('transport_amount');
		$this->db->where('year', $un_year);
		$this->db->where('term', $un_term);
		$total_fare_paid = $this->db->get('daily_fee_transactions')->row()->transport_amount ?? 0;

		$this->db->select_sum('water_amount');
		$this->db->where('year', $un_year);
		$this->db->where('term', $un_term);
		$total_water_paid = $this->db->get('daily_fee_transactions')->row()->water_amount ?? 0;

		$this->db->select_sum('breakfast_amount');
		$this->db->where('year', $un_year);
		$this->db->where('term', $un_term);
		$total_breakfast_paid = $this->db->get('daily_fee_transactions')->row()->breakfast_amount ?? 0;

		// Get total arrears from wallet - filter by active students (mute = '0') across all terms/years
		$this->db->select_sum('w.feeding_arrears');
		$this->db->from('daily_fee_wallet w');
		$this->db->join('enroll e', 'w.student_id = e.student_id AND w.year = e.year AND w.term = e.term', 'inner');
		$this->db->where('e.mute', '0');
		$this->db->where('w.feeding_arrears >', 0);
		$total_feeding_owe = $this->db->get()->row()->feeding_arrears ?? 0;

		$this->db->select_sum('w.classes_arrears');
		$this->db->from('daily_fee_wallet w');
		$this->db->join('enroll e', 'w.student_id = e.student_id AND w.year = e.year AND w.term = e.term', 'inner');
		$this->db->where('e.mute', '0');
		$this->db->where('w.classes_arrears >', 0);
		$total_classes_owe = $this->db->get()->row()->classes_arrears ?? 0;

		$this->db->select_sum('w.transport_arrears');
		$this->db->from('daily_fee_wallet w');
		$this->db->join('enroll e', 'w.student_id = e.student_id AND w.year = e.year AND w.term = e.term', 'inner');
		$this->db->where('e.mute', '0');
		$this->db->where('w.transport_arrears >', 0);
		$total_fare_owe = $this->db->get()->row()->transport_arrears ?? 0;
	} else {
		// Use old feeding_fee table
		$this->db->select('timestamp');
		$this->db->distinct();
		$this->db->where('year', $un_year);
		$this->db->where('term', $un_term);
		$this->db->order_by('timestamp', 'desc');
		$this->db->limit(1);
		$feeding_timestamp_result = $this->db->get('feeding_fee');
		$feeding_timestamp = $feeding_timestamp_result->num_rows() > 0 ? $feeding_timestamp_result->row()->timestamp : strtotime(date('d-m-Y'));

		$this->db->select_sum('feeding_paid');
		$this->db->where('year', $un_year);
		$this->db->where('term', $un_term);
		$this->db->where('mute', '0');
		$total_feeding_paid = $this->db->get('feeding_fee')->row()->feeding_paid ?? 0;

		$this->db->select_sum('due');
		$this->db->where('timestamp', $feeding_timestamp);
		$this->db->where('year', $un_year);
		$this->db->where('term', $un_term);
		$this->db->where('mute', '0');
		$this->db->where('due >', 0);
		$total_feeding_owe = $this->db->get('feeding_fee')->row()->due ?? 0;

		$this->db->select_sum('classes_paid');
		$this->db->where('year', $un_year);
		$this->db->where('term', $un_term);
		$this->db->where('mute', '0');
		$total_classes_paid = $this->db->get('feeding_fee')->row()->classes_paid ?? 0;

		$this->db->select_sum('cdue');
		$this->db->where('timestamp', $feeding_timestamp);
		$this->db->where('year', $un_year);
		$this->db->where('term', $un_term);
		$this->db->where('mute', '0');
		$this->db->where('cdue >', 0);
		$total_classes_owe = $this->db->get('feeding_fee')->row()->cdue ?? 0;

		$this->db->select_sum('amount_paid');
		$this->db->where('year', $un_year);
		$this->db->where('term', $un_term);
		$total_fare_paid = $this->db->get('transport_fare')->row()->amount_paid ?? 0;
		$total_fare_owe = 0;
	}
	?>

	<div class="mb-6">
		<h2 class="text-2xl font-bold text-gray-800 mb-4">Financial Summary</h2>
		
		<!-- Main Financial Cards -->
		<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6 mb-6">
			<!-- Total Invoices Due (FIRST) - No Link -->
			<div class="dashboard-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; padding: 20px; height: 100%; display: flex; flex-direction: column;">
				<div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3" style="flex: 1;">
					<div class="flex flex-col items-start gap-2">
						<div class="stat-icon" style="background: rgba(255,255,255,0.2); width: 56px; height: 56px; font-size: 26px;">
							<i class="fa fa-file-invoice-dollar" style="color: white;"></i>
						</div>
						<h3 class="font-medium" style="color: white; font-size: 13px;">Total Due Invoices</h3>
					</div>
					<div class="text-right ml-auto">
						<p class="text-4xl font-extrabold leading-none mb-1" style="color: white;"><span class="currency-symbol"><?php echo $currency; ?></span><?php echo number_format($total_invoices_due_combined, 2); ?></p>
						<p style="color: white; font-size: 14px;">Outstanding as of this term</p>
					</div>
				</div>
			</div>

			<!-- Total Income (SECOND) -->
			<a href="<?php echo site_url('admin/financial_reports/payments'); ?>" class="block" target="_blank" style="height: 100%;">
				<div class="dashboard-card" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); color: white; padding: 20px; height: 100%; display: flex; flex-direction: column;">
					<div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3" style="flex: 1;">
						<div class="flex flex-col items-start gap-2">
							<div class="stat-icon" style="background: rgba(255,255,255,0.2); width: 56px; height: 56px; font-size: 26px;">
								<i class="fa fa-wallet" style="color: white;"></i>
							</div>
							<h3 class="font-medium" style="color: white; font-size: 13px;">Total Income</h3>
						</div>
						<div class="text-right ml-auto">
							<p class="text-4xl font-extrabold leading-none mb-1" style="color: white;"><span class="currency-symbol"><?php echo $currency; ?></span><?php echo number_format($total_income_raw, 2); ?></p>
							<p style="color: white; font-size: 12px;">Received this term</p>
							<span class="text-xs font-semibold bg-white bg-opacity-20 px-2 py-1 rounded-full mt-1 inline-block" style="color: white;">Receipts: <?php echo $receipt_count; ?></span>
						</div>
					</div>
				</div>
			</a>

			<!-- Unpaid Invoices (THIRD) -->
			<a href="#" onclick="showAjaxModal('<?php echo site_url('modal/popup/modal_unpaid_invoices/'.$un_term .'/'. $un_year.'/'. $un_sem);?>', 'modal_unpaid_invoices');" class="block" style="height: 100%;">
				<div class="dashboard-card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; height: 100%; display: flex; flex-direction: column;">
					<div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3" style="flex: 1;">
						<div class="flex flex-col items-start gap-2">
							<div class="stat-icon" style="background: rgba(255,255,255,0.2); width: 56px; height: 56px; font-size: 26px;">
								<i class="fa fa-credit-card" style="color: white;"></i>
							</div>
							<h3 class="font-medium" style="color: white; font-size: 13px;">Unpaid Invoices</h3>
						</div>
						<div class="text-right ml-auto">
							<p class="text-4xl font-extrabold leading-none mb-1" style="color: white;"><span class="currency-symbol"><?php echo $currency; ?></span><?php echo number_format($unpaid_amount, 2); ?></p>
							<p style="color: white; font-size: 12px;">Outstanding payments</p>
							<span class="text-xs font-semibold bg-white bg-opacity-20 px-2 py-1 rounded-full mt-1 inline-block" style="color: white;">Qty: <?php echo $unpaid_invoices_count; ?></span>
						</div>
					</div>
				</div>
			</a>

			<!-- Total Expenses (FOURTH) -->
			<a href="<?php echo site_url('admin/expense'); ?>" class="block" target="_blank" style="height: 100%;">
				<div class="dashboard-card" style="background: linear-gradient(135deg, #ee0979 0%, #ff6a00 100%); color: white; padding: 20px; height: 100%; display: flex; flex-direction: column;">
					<div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3" style="flex: 1;">
						<div class="flex flex-col items-start gap-2">
							<div class="stat-icon" style="background: rgba(255,255,255,0.2); width: 56px; height: 56px; font-size: 26px;">
								<i class="fa fa-tags" style="color: white;"></i>
							</div>
							<h3 class="font-medium" style="color: white; font-size: 13px;">Total Termly Expenses</h3>
						</div>
						<div class="text-right ml-auto">
							<p class="text-4xl font-extrabold leading-none mb-1" style="color: white;"><span class="currency-symbol"><?php echo $currency; ?></span><?php echo number_format($total_expense_raw, 2); ?></p>
							<p style="color: white; font-size: 12px;">Spent this term</p>
						</div>
					</div>
				</div>
			</a>
		</div>

		<!-- Total Daily Fees Summary Card - Enterprise Grade -->
		<?php
		$total_daily_fees_collected = 0;
		$enabled_fee_modules = [];
		$module_breakdown = [];
		
		if (is_fee_module_enabled('feeding')) {
			$total_daily_fees_collected += $total_feeding_paid;
			$enabled_fee_modules[] = 'Feeding';
			$module_breakdown[] = ['name' => 'Feeding', 'amount' => $total_feeding_paid, 'icon' => 'fa-cutlery', 'color' => '#4facfe'];
		}
		if (is_fee_module_enabled('classes')) {
			$total_daily_fees_collected += $total_classes_paid;
			$enabled_fee_modules[] = 'Classes';
			$module_breakdown[] = ['name' => 'Classes', 'amount' => $total_classes_paid, 'icon' => 'fa-book', 'color' => '#fa709a'];
		}
		if (is_fee_module_enabled('transport')) {
			$total_daily_fees_collected += $total_fare_paid;
			$enabled_fee_modules[] = 'Transport';
			$module_breakdown[] = ['name' => 'Transport', 'amount' => $total_fare_paid, 'icon' => 'fa-bus', 'color' => '#30cfd0'];
		}
		if (is_fee_module_enabled('water')) {
			$this->db->select_sum('water_amount');
			$this->db->where('year', $un_year);
			$this->db->where('term', $un_term);
			$water_paid = $this->db->get('daily_fee_transactions')->row()->water_amount ?? 0;
			$total_daily_fees_collected += $water_paid;
			$enabled_fee_modules[] = 'Water';
			$module_breakdown[] = ['name' => 'Water', 'amount' => $water_paid, 'icon' => 'fa-tint', 'color' => '#00c6ff'];
		}
		if (is_fee_module_enabled('breakfast')) {
			$this->db->select_sum('breakfast_amount');
			$this->db->where('year', $un_year);
			$this->db->where('term', $un_term);
			$breakfast_paid = $this->db->get('daily_fee_transactions')->row()->breakfast_amount ?? 0;
			$total_daily_fees_collected += $breakfast_paid;
			$enabled_fee_modules[] = 'Breakfast';
			$module_breakdown[] = ['name' => 'Breakfast', 'amount' => $breakfast_paid, 'icon' => 'fa-coffee', 'color' => '#f093fb'];
		}
		?>
		
		<?php if (!empty($enabled_fee_modules)): ?>
		<div class="mb-6">
			<div class="dashboard-card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; overflow: hidden; position: relative;">
				<!-- Decorative Background Pattern -->
				<div style="position: absolute; top: -50px; right: -50px; width: 200px; height: 200px; background: rgba(255,255,255,0.05); border-radius: 50%;"></div>
				<div style="position: absolute; bottom: -30px; left: -30px; width: 150px; height: 150px; background: rgba(255,255,255,0.05); border-radius: 50%;"></div>
				
				<div style="position: relative; z-index: 1;">
					<!-- Header Section -->
					<div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
						<div class="flex items-center gap-4">
							<div style="width: 80px; height: 80px; background: rgba(255,255,255,0.15); backdrop-filter: blur(10px); border-radius: 20px; display: flex; align-items: center; justify-content: center; box-shadow: 0 8px 32px rgba(0,0,0,0.1);">
								<i class="fa fa-chart-line" style="color: white; font-size: 40px;"></i>
							</div>
							<div>
								<p class="text-base font-bold" style="color: rgba(255,255,255,0.95); text-transform: uppercase; letter-spacing: 1.2px; margin-bottom: 6px;">Termly Fees Collection</p>
								<h2 class="text-5xl md:text-6xl font-extrabold" style="color: white; margin: 0; line-height: 1.2;"><span class="currency-symbol"><?php echo $currency; ?></span><?php echo number_format($total_daily_fees_collected, 2); ?></h2>
							</div>
						</div>
						<div class="flex flex-col items-end gap-2">
							<div style="background: rgba(255,255,255,0.15); backdrop-filter: blur(10px); padding: 12px 24px; border-radius: 12px;">
								<p class="text-sm font-bold" style="color: rgba(255,255,255,0.95); margin-bottom: 6px;">Academic Period</p>
								<p class="text-xl font-extrabold" style="color: white; margin: 0;">Year <?php echo $un_year; ?> • Term <?php echo $un_term; ?></p>
							</div>
							<div style="background: rgba(16, 185, 129, 0.2); backdrop-filter: blur(10px); padding: 8px 16px; border-radius: 8px; border: 1px solid rgba(16, 185, 129, 0.3);">
								<p class="text-ms font-bold" style="color: #00ffab; margin: 0;"><?php echo count($enabled_fee_modules); ?> Active Modules</p>
							</div>
						</div>
					</div>
					
					<!-- Module Breakdown Grid -->
					<div class="grid grid-cols-2 md:grid-cols-<?php echo min(count($module_breakdown), 5); ?> gap-3">
						<?php foreach ($module_breakdown as $module): ?>
						<div style="background: rgba(255,255,255,0.1); backdrop-filter: blur(10px); border-radius: 12px; padding: 16px; border: 1px solid rgba(255,255,255,0.1); transition: all 0.3s;" onmouseover="this.style.background='rgba(255,255,255,0.15)'; this.style.transform='translateY(-4px)';" onmouseout="this.style.background='rgba(255,255,255,0.1)'; this.style.transform='translateY(0)';">
							<div class="flex items-center gap-3 mb-2">
								<div style="width: 36px; height: 36px; background: <?php echo $module['color']; ?>; border-radius: 8px; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">
									<i class="fa <?php echo $module['icon']; ?>" style="color: white; font-size: 16px;"></i>
								</div>
								<p class="text-base font-bold" style="color: white; margin: 0;"><?php echo $module['name']; ?></p>
							</div>
							<p class="text-3xl font-extrabold" style="color: white; margin: 0;"><span class="currency-symbol"><?php echo $currency; ?></span><?php echo number_format($module['amount'], 0); ?></p>
							<?php if ($total_daily_fees_collected > 0): ?>
							<div style="margin-top: 8px; height: 4px; background: rgba(255,255,255,0.2); border-radius: 2px; overflow: hidden;">
								<div style="height: 100%; background: <?php echo $module['color']; ?>; width: <?php echo round(($module['amount'] / $total_daily_fees_collected) * 100); ?>%; transition: width 0.5s;"></div>
							</div>
							<p class="text-sm font-semibold" style="color: rgba(255,255,255,0.9); margin-top: 4px;"><?php echo round(($module['amount'] / $total_daily_fees_collected) * 100, 1); ?>% of total</p>
							<?php endif; ?>
						</div>
						<?php endforeach; ?>
					</div>
				</div>
			</div>
		</div>
		<?php endif; ?>
		
		<!-- Fee Collection Cards -->
		<?php
		$daily_fees = [];
		
		// Get previous term for comparison
		$prev_term = $un_term > 1 ? $un_term - 1 : 3;
		// Handle year format "2025-2026" - if term is 1, we need previous year "2024-2025"
		if ($un_term > 1) {
			$prev_year = $un_year;
		} else {
			// Extract start and end years from format "2025-2026"
			$year_parts = explode('-', $un_year);
			if (count($year_parts) == 2) {
				$start_year = intval($year_parts[0]) - 1;
				$end_year = intval($year_parts[1]) - 1;
				$prev_year = $start_year . '-' . $end_year;
			} else {
				// Fallback if format is different
				$prev_year = $un_year;
			}
		}
		$today_today = strtotime('today');
		$yesterday_yesterday = strtotime('yesterday');

		if (is_fee_module_enabled('feeding')) {
			$this->db->select_sum('feeding_amount');
			$this->db->where('payment_date', $yesterday_yesterday);
			//$this->db->where('term', $prev_term);
			$prev_feeding = $this->db->get('daily_fee_transactions')->row()->feeding_amount ?? 0;
			$prev_feeding = floatval($prev_feeding); // Ensure numeric value

			$this->db->select_sum('feeding_amount');
			$this->db->where('payment_date', $today_today);
			//$this->db->where('term', $prev_term);
			$total_feeding_paid_today = $this->db->get('daily_fee_transactions')->row()->feeding_amount ?? 0;
			$total_feeding_paid_today = floatval($total_feeding_paid_today); // Ensure numeric value

			$feeding_change = $prev_feeding > 0 ? round((($total_feeding_paid_today - $prev_feeding) / $prev_feeding) * 100, 1) : 0;
			
			$daily_fees[] = [
				'name' => 'Feeding Fee',
				'icon' => 'fa-cutlery',
				'gradient' => 'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)',
				'amount' => $total_feeding_paid_today,
				'change' => $feeding_change,
				'modal_url' => site_url('modal/popup/modal_feeding_payments/'.$un_term.'/'.$un_year),
				'modal_id' => 'modal_feeding_payments'
			];
		}
		
		if (is_fee_module_enabled('classes')) {
			$this->db->select_sum('classes_amount');
			$this->db->where('payment_date', $yesterday_yesterday);
			$prev_classes = $this->db->get('daily_fee_transactions')->row()->classes_amount ?? 0;
			$prev_classes = floatval($prev_classes); // Ensure numeric value

			$this->db->select_sum('classes_amount');
			$this->db->where('payment_date', $today_today);
			$total_classes_paid_today = $this->db->get('daily_fee_transactions')->row()->classes_amount ?? 0;
			$total_classes_paid_today = floatval($total_classes_paid_today); // Ensure numeric value

			$classes_change = $prev_classes > 0 ? round((($total_classes_paid_today - $prev_classes) / $prev_classes) * 100, 1) : 0;
			
			$daily_fees[] = [
				'name' => 'Classes Fee',
				'icon' => 'fa-book',
				'gradient' => 'linear-gradient(135deg, #fa709a 0%, #fee140 100%)',
				'amount' => $total_classes_paid_today,
				'change' => $classes_change,
				'modal_url' => site_url('modal/popup/modal_classes_payments/'.$un_term.'/'.$un_year),
				'modal_id' => 'modal_classes_payments'
			];
		}
		
		if (is_fee_module_enabled('transport')) {
			$this->db->select_sum('transport_amount');
			$this->db->where('payment_date', $yesterday_yesterday);
			$prev_transport = $this->db->get('daily_fee_transactions')->row()->transport_amount ?? 0;
			$prev_transport = floatval($prev_transport); // Ensure numeric value

			$this->db->select_sum('transport_amount');
			$this->db->where('payment_date', $today_today);
			$total_fare_paid_today = $this->db->get('daily_fee_transactions')->row()->transport_amount ?? 0;
			$total_fare_paid_today = floatval($total_fare_paid_today); // Ensure numeric value

			$transport_change = $prev_transport > 0 ? round((($total_fare_paid_today - $prev_transport) / $prev_transport) * 100, 1) : 0;
			
			$daily_fees[] = [
				'name' => 'Transport Fare',
				'icon' => 'fa-bus',
				'gradient' => 'linear-gradient(135deg, #30cfd0 0%, #330867 100%)',
				'amount' => $total_fare_paid_today,
				'change' => $transport_change,
				'modal_url' => site_url('modal/popup/modal_transport_payments/'.$un_term.'/'.$un_year),
				'modal_id' => 'modal_transport_payments'
			];
		}
		
		if (is_fee_module_enabled('water')) {
			$this->db->select_sum('water_amount');
			$this->db->where('payment_date', $yesterday_yesterday);
			$prev_water = $this->db->get('daily_fee_transactions')->row()->water_amount ?? 0;
			$prev_water = floatval($prev_water); // Ensure numeric value

			$this->db->select_sum('water_amount');
			$this->db->where('payment_date', $today_today);
			$total_water_paid_today = $this->db->get('daily_fee_transactions')->row()->water_amount ?? 0;
			$total_water_paid_today = floatval($total_water_paid_today); // Ensure numeric value

			$water_change = $prev_water > 0 ? round((($total_water_paid_today - $prev_water) / $prev_water) * 100, 1) : 0;
			
			$daily_fees[] = [
				'name' => 'Water Fee',
				'icon' => 'fa-tint',
				'gradient' => 'linear-gradient(135deg, #00c6ff 0%, #0072ff 100%)',
				'change' => $water_change,
				'amount' => $total_water_paid_today,
				'modal_url' => site_url('modal/popup/modal_water_payments/'.$un_term.'/'.$un_year),
				'modal_id' => 'modal_water_payments'
			];
		}
		
		if (is_fee_module_enabled('breakfast')) {
			$this->db->select_sum('breakfast_amount');
			$this->db->where('payment_date', $yesterday_yesterday);
			$prev_breakfast = $this->db->get('daily_fee_transactions')->row()->breakfast_amount ?? 0;
			$prev_breakfast = floatval($prev_breakfast); // Ensure numeric value

			$this->db->select_sum('breakfast_amount');
			$this->db->where('payment_date', $today_today);
			$total_breakfast_paid_today = $this->db->get('daily_fee_transactions')->row()->breakfast_amount ?? 0;
			$total_breakfast_paid_today = floatval($total_breakfast_paid_today); // Ensure numeric value

			$breakfast_change = $prev_breakfast > 0 ? round((($total_breakfast_paid_today - $prev_breakfast) / $prev_breakfast) * 100, 1) : 0;
			
			$daily_fees[] = [
				'name' => 'Breakfast Fee',
				'icon' => 'fa-coffee',
				'gradient' => 'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)',
				'change' => $breakfast_change,
				'amount' => $total_breakfast_paid_today,
				'modal_url' => site_url('modal/popup/modal_breakfast_payments/'.$un_term.'/'.$un_year),
				'modal_id' => 'modal_breakfast_payments'
			];
		}
		
		$fee_count = count($daily_fees);
		$grid_class = $fee_count == 1 ? 'grid-cols-1' : ($fee_count == 2 ? 'grid-cols-1 sm:grid-cols-2' : ($fee_count == 3 ? 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3' : ($fee_count == 4 ? 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-4' : 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-5')));
		?>
		
		<?php if (!empty($daily_fees)): ?>
		<div class="grid <?php echo $grid_class; ?> gap-4 md:gap-6 mb-6">
			<?php foreach ($daily_fees as $fee): ?>
			<a href="<?php echo site_url('admin/cashier_dashboard_admin'); ?>" target="_blank" class="block h-full">
				<div class="dashboard-card h-full flex flex-col" style="background: <?php echo $fee['gradient']; ?>; color: white; padding: 20px;">
					<div class="flex items-center justify-between mb-3">
						<div class="stat-icon" style="background: rgba(255,255,255,0.2); width: 56px; height: 56px; font-size: 26px;">
							<i class="fa <?php echo $fee['icon']; ?>" style="color: white;"></i>
						</div>
						<div class="text-right">
							<p class="text-4xl font-extrabold leading-none" style="color: white;"><span class="currency-symbol"><?php echo $currency; ?></span><?php echo number_format($fee['amount'], 2); ?></p>
							<?php if (isset($fee['change']) && $fee['change'] != 0): ?>
							<span class="text-xs font-bold px-2 py-1 rounded-full <?php echo $fee['change'] > 0 ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?> mt-1 inline-block" data-toggle="tooltip" title="Compared to previous day">
								<?php echo $fee['change'] > 0 ? '↑' : '↓'; ?> <?php echo abs($fee['change']); ?>%
							</span>
							<?php endif; ?>
						</div>
					</div>
					<h3 class="font-medium mb-2" style="color: white; font-size: 13px;"><?php echo $fee['name']; ?></h3>
					<p style="color: white; font-size: 12px;">Received today <?= date('D M j, Y') ?></p>
				</div>
			</a>
			<?php endforeach; ?>
		</div>
		<?php endif; ?>
		
		<!-- Unpaid Balances -->
		<?php
		$unpaid_items = [];
		if (is_fee_module_enabled('feeding') && $total_feeding_owe > 0) {
			$unpaid_items[] = ['label' => 'Feeding', 'amount' => $total_feeding_owe];
		}
		if (is_fee_module_enabled('classes') && $total_classes_owe > 0) {
			$unpaid_items[] = ['label' => 'Classes', 'amount' => $total_classes_owe];
		}
		if (is_fee_module_enabled('transport') && $total_fare_owe > 0) {
			$unpaid_items[] = ['label' => 'Transport', 'amount' => $total_fare_owe];
		}
		if (is_fee_module_enabled('water')) {
			$this->db->select_sum('w.water_arrears');
			$this->db->from('daily_fee_wallet w');
			$this->db->join('enroll e', 'w.student_id = e.student_id AND w.year = e.year AND w.term = e.term', 'inner');
			$this->db->where('e.mute', '0');
			$this->db->where('w.water_arrears >', 0);
			$total_water_owe = $this->db->get()->row()->water_arrears ?? 0;
			if ($total_water_owe > 0) {
				$unpaid_items[] = ['label' => 'Water', 'amount' => $total_water_owe];
			}
		}
		if (is_fee_module_enabled('breakfast')) {
			$this->db->select_sum('w.breakfast_arrears');
			$this->db->from('daily_fee_wallet w');
			$this->db->join('enroll e', 'w.student_id = e.student_id AND w.year = e.year AND w.term = e.term', 'inner');
			$this->db->where('e.mute', '0');
			$this->db->where('w.breakfast_arrears >', 0);
			$total_breakfast_owe = $this->db->get()->row()->breakfast_arrears ?? 0;
			if ($total_breakfast_owe > 0) {
				$unpaid_items[] = ['label' => 'Breakfast', 'amount' => $total_breakfast_owe];
			}
		}
		?>
		
		<?php if (!empty($unpaid_items)): ?>
		<div class="grid grid-cols-1 gap-4 md:gap-6">
			<a href="<?php echo site_url('admin/cashier_dashboard_admin'); ?>" class="block" title="Debtors List">
				<div class="dashboard-card" style="background: linear-gradient(135deg, #f85032 0%, #e73827 100%); color: white;">
					<div class="flex items-center justify-between mb-4">
						<h3 class="text-xl font-semibold" style="color: white;">Daily Fees - Unpaid Balances</h3>
						<div class="stat-icon" style="background: rgba(255,255,255,0.2); width: 56px; height: 56px;">
							<i class="fa fa-exclamation-triangle" style="color: white; font-size: 24px;"></i>
						</div>
					</div>
					<div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-<?php echo min(count($unpaid_items), 5); ?> gap-4">
						<?php foreach ($unpaid_items as $item): ?>
						<div class="text-center p-4 bg-white bg-opacity-10 rounded-lg">
							<p class="text-lg font-bold mb-2" style="color: white;"><?php echo $item['label']; ?></p>
							<p class="text-3xl md:text-4xl font-extrabold" style="color: white;"><span class="currency-symbol"><?php echo $currency; ?></span><?php echo number_format($item['amount'], 2); ?></p>
						</div>
						<?php endforeach; ?>
					</div>
				</div>
			</a>
		</div>
		<?php endif; ?>
	</div>
	<?php endif; ?>
</div>

<!-- Chart.js Library -->
<script src="<?php echo base_url(); ?>assets/cdn/js/chart-3.9.1.min.js"></script>

<script>
// Student Distribution Chart
const studentDistCtx = document.getElementById('studentDistributionChart');
if (studentDistCtx) {
	<?php
	$class_labels = array();
	$class_counts = array();
	foreach ($classes as $class) {
		$class_name = $class['name'] . ' ' . $class['name_numeric'];
		$this->db->where('class_id', $class['class_id']);
		$this->db->where('year', $un_year);
		$this->db->where('term', $un_term);
		$this->db->where('mute', '0');
		$count = $this->db->get('enroll')->num_rows();
		if ($count > 0) {
			$class_labels[] = $class_name;
			$class_counts[] = $count;
		}
	}
	?>
	createOrUpdateChart('studentDistributionChart', {
		type: 'bar',
		data: {
			labels: <?php echo json_encode($class_labels); ?>,
			datasets: [{
				label: 'Students',
				data: <?php echo json_encode($class_counts); ?>,
				backgroundColor: 'rgba(102, 126, 234, 0.8)',
				borderColor: 'rgba(102, 126, 234, 1)',
				borderWidth: 2,
				borderRadius: 8
			}]
		},
		options: {
			responsive: true,
			maintainAspectRatio: false,
			plugins: {
				legend: { display: false }
			},
			scales: {
				y: { beginAtZero: true, ticks: { stepSize: 1 } }
			}
		}
	});
}

// Attendance Trend Chart
const attendanceCtx = document.getElementById('attendanceTrendChart');
if (attendanceCtx) {
	<?php
	$dates = array();
	$attendance_counts = array();
	for ($i = 6; $i >= 0; $i--) {
		$date_timestamp = strtotime(date('d-m-Y', strtotime("-$i days")));
		$dates[] = date('M d', $date_timestamp);
		$this->db->where('timestamp', $date_timestamp);
		$this->db->where_in('status', ['1', '3']);
		$attendance_counts[] = $this->db->get('attendance')->num_rows();
	}
	?>
	createOrUpdateChart('attendanceTrendChart', {
		type: 'line',
		data: {
			labels: <?php echo json_encode($dates); ?>,
			datasets: [{
				label: 'Present',
				data: <?php echo json_encode($attendance_counts); ?>,
				borderColor: 'rgba(250, 112, 154, 1)',
				backgroundColor: 'rgba(250, 112, 154, 0.1)',
				borderWidth: 3,
				fill: true,
				tension: 0.4,
				pointRadius: 5,
				pointBackgroundColor: 'rgba(250, 112, 154, 1)'
			}]
		},
		options: {
			responsive: true,
			maintainAspectRatio: false,
			plugins: {
				legend: { display: false }
			},
			scales: {
				y: { beginAtZero: true, ticks: { stepSize: 10 } }
			}
		}
	});
}

// Gender Distribution by Class Chart
const genderCtx = document.getElementById('genderDistributionChart');
if (genderCtx) {
	<?php
	$gender_class_labels = array();
	$male_counts = array();
	$female_counts = array();
	$total_male = 0;
	$total_female = 0;
	
	foreach ($classes as $class) {
		$class_name = $class['name'] . ' ' . $class['name_numeric'];
		$this->db->select('student_id');
		$this->db->where('class_id', $class['class_id']);
		$this->db->where('year', $un_year);
		$this->db->where('term', $un_term);
		$this->db->where('mute', '0');
		$class_students = $this->db->get('enroll')->result_array();
		$class_student_ids = array_column($class_students, 'student_id');
		
		if (!empty($class_student_ids)) {
			$this->db->where_in('student_id', $class_student_ids);
			$this->db->where('sex', 'male');
			$male = $this->db->get('student')->num_rows();
			
			$this->db->where_in('student_id', $class_student_ids);
			$this->db->where('sex', 'female');
			$female = $this->db->get('student')->num_rows();
			
			if ($male > 0 || $female > 0) {
				$gender_class_labels[] = $class_name;
				$male_counts[] = $male;
				$female_counts[] = $female;
				$total_male += $male;
				$total_female += $female;
			}
		}
	}
	$gender_class_labels[] = 'TOTAL';
	$male_counts[] = $total_male;
	$female_counts[] = $total_female;
	?>
	createOrUpdateChart('genderDistributionChart', {
		type: 'bar',
		data: {
			labels: <?php echo json_encode($gender_class_labels); ?>,
			datasets: [
				{
					label: 'Male',
					data: <?php echo json_encode($male_counts); ?>,
					backgroundColor: 'rgba(54, 162, 235, 0.8)',
					borderColor: 'rgba(54, 162, 235, 1)',
					borderWidth: 2,
					borderRadius: 6
				},
				{
					label: 'Female',
					data: <?php echo json_encode($female_counts); ?>,
					backgroundColor: 'rgba(255, 99, 132, 0.8)',
					borderColor: 'rgba(255, 99, 132, 1)',
					borderWidth: 2,
					borderRadius: 6
				}
			]
		},
		options: {
			responsive: true,
			maintainAspectRatio: false,
			plugins: {
				legend: {
					position: 'top',
					labels: { font: { size: 12 }, padding: 10 }
				}
			},
			scales: {
				y: { beginAtZero: true, ticks: { stepSize: 1 } },
				x: { ticks: { font: { size: 11 } } }
			}
		}
	});
}

// Residential Distribution by Class Chart
const residentialCtx = document.getElementById('residentialDistributionChart');
if (residentialCtx) {
	<?php
	// Get all unique residence types
	$this->db->select('residence_type');
	$this->db->distinct();
	$this->db->where('year', $un_year);
	$this->db->where('term', $un_term);
	$this->db->where('mute', '0');
	$res_types_result = $this->db->get('enroll')->result_array();
	$res_types = array_column($res_types_result, 'residence_type');
	
	$res_class_labels = array();
	$res_datasets = array();
	$res_totals = array();
	
	// Initialize totals
	foreach ($res_types as $type) {
		$res_totals[$type] = 0;
	}
	
	// Get data for each class
	foreach ($classes as $class) {
		$class_name = $class['name'] . ' ' . $class['name_numeric'];
		$this->db->where('class_id', $class['class_id']);
		$this->db->where('year', $un_year);
		$this->db->where('term', $un_term);
		$this->db->where('mute', '0');
		$count = $this->db->get('enroll')->num_rows();
		
		if ($count > 0) {
			$res_class_labels[] = $class_name;
			foreach ($res_types as $type) {
				$this->db->where('class_id', $class['class_id']);
				$this->db->where('year', $un_year);
				$this->db->where('term', $un_term);
				$this->db->where('mute', '0');
				$this->db->where('residence_type', $type);
				$type_count = $this->db->get('enroll')->num_rows();
				$res_datasets[$type][] = $type_count;
				$res_totals[$type] += $type_count;
			}
		}
	}
	
	// Add TOTAL column
	$res_class_labels[] = 'TOTAL';
	foreach ($res_types as $type) {
		$res_datasets[$type][] = $res_totals[$type];
	}
	
	$colors = [
		['rgba(102, 126, 234, 0.8)', 'rgba(102, 126, 234, 1)'],
		['rgba(118, 75, 162, 0.8)', 'rgba(118, 75, 162, 1)'],
		['rgba(255, 138, 0, 0.8)', 'rgba(255, 138, 0, 1)'],
		['rgba(0, 200, 83, 0.8)', 'rgba(0, 200, 83, 1)']
	];
	
	$chart_datasets = array();
	$color_index = 0;
	foreach ($res_types as $type) {
		$chart_datasets[] = array(
			'label' => ucfirst($type),
			'data' => $res_datasets[$type],
			'backgroundColor' => $colors[$color_index][0],
			'borderColor' => $colors[$color_index][1],
			'borderWidth' => 2,
			'borderRadius' => 6
		);
		$color_index = ($color_index + 1) % count($colors);
	}
	?>
	createOrUpdateChart('residentialDistributionChart', {
		type: 'bar',
		data: {
			labels: <?php echo json_encode($res_class_labels); ?>,
			datasets: <?php echo json_encode($chart_datasets); ?>
		},
		options: {
			responsive: true,
			maintainAspectRatio: false,
			plugins: {
				legend: {
					position: 'top',
					labels: { font: { size: 12 }, padding: 10 }
				}
			},
			scales: {
				y: { beginAtZero: true, ticks: { stepSize: 1 } },
				x: { ticks: { font: { size: 11 } } }
			}
		}
	});
}

// Initialize datepicker
$(document).ready(function() {
	$('.datepicker').datepicker({
		format: 'dd-mm-yyyy',
		autoclose: true,
		todayHighlight: true
	});
	
	// Initialize tooltips
	$('[data-toggle="tooltip"]').tooltip();
});

// Function to show daily revenue modal
function showDailyRevenueModal(timestamp) {
	showAjaxModal_alert('<center><div style="font-size: 20px; font-weight: bolder;">Loading...</div></center>', 'Daily Revenue Details');
	
	$.ajax({
		url: '<?php echo site_url("modal/popup_daily_revenue/"); ?>' + timestamp,
		success: function(response) {
			$('#modal_alert .modal-body').html(response);
			$('#modal_alert .modal-dialog').css({
				'max-width': '95%',
				'width': '1200px'
			});
		}
	});
}
</script>
