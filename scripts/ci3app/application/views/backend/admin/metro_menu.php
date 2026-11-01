<!-- Windows 8 Start Screen Style Mega Menu -->
<style>
/* Windows 8 Metro Start Screen */
.metro-start-container { position: relative; z-index: 10000; }

/* Launcher icon - hidden by default */
.metro-start-trigger { position: fixed; right: -70px; top: 30px; width: 60px; height: 60px; background: linear-gradient(135deg, rgba(255, 255, 255, 0.15), rgba(255, 255, 255, 0.05)); border: 2px solid rgba(255, 255, 255, 0.3); border-radius: 12px; cursor: pointer; display: flex; align-items: center; justify-content: center; color: white; font-size: 28px; transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); backdrop-filter: blur(15px); box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2), inset 0 1px 0 rgba(255, 255, 255, 0.2); overflow: hidden; z-index: 999997; }
.metro-start-trigger.visible { right: 30px; }
.metro-start-trigger:hover { background: linear-gradient(135deg, rgba(255, 255, 255, 0.25), rgba(255, 255, 255, 0.1)); border-color: rgba(255, 255, 255, 0.5); transform: scale(1.08) rotate(2deg); box-shadow: 0 12px 40px rgba(0, 0, 0, 0.4), inset 0 1px 0 rgba(255, 255, 255, 0.3); }
.metro-start-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.9); opacity: 0; visibility: hidden; transition: all 0.3s; z-index: 999998; }
.metro-start-overlay.active { opacity: 1; visibility: visible; }
.metro-start-screen { position: fixed; top: 0; right: -100%; width: 100%; height: 100%; background: var(--theme-secondary); transition: all 0.4s; z-index: 999999; overflow-y: auto; }
.metro-start-screen.active { right: 0; }
.metro-start-screen.sidebar-open { right: 0; width: calc(100% - 250px); }
.metro-start-header { padding: 40px 80px; display: flex; align-items: center; justify-content: space-between; position: sticky; top: -20px; background: var(--theme-secondary); z-index: 10; }

/* Metro Search Styles */
#metro-search:focus {
    background: rgba(255,255,255,0.15);
    border-color: rgba(59,130,246,0.5);
    box-shadow: 0 0 0 3px rgba(59,130,246,0.1);
}
.metro-tile-hidden {
    display: none !important;
}
.metro-group-hidden {
    display: none !important;
}
.metro-tile-highlight {
    animation: pulse 1s ease-in-out;
    box-shadow: 0 0 20px rgba(251,191,36,0.5) !important;
}
@keyframes pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.05); }
}

/* Mobile backdrop - must be lower than sidebar (99999) but higher than normal content */
/*.mobile-backdrop { z-index: 99998 !important; }*/

/* Navigation Sidebar Z-Index Control - sidebar should be on top */
/*.sidebar-menu { z-index: 99999 !important; }*/

/* When navigation is expanded, it should dominate metro menu */
body.sidebar-open .metro-start-container { z-index: 9000 !important; }
body.sidebar-open .metro-start-trigger { z-index: 899997 !important; }
body.sidebar-open .metro-start-overlay { z-index: 899998 !important; }
body.sidebar-open .metro-start-screen { z-index: 899999 !important; }

/* Ensure metro menu dominates when sidebar is NOT open */
body.sidebar-collapse .metro-start-container, body:not(.sidebar-open) .metro-start-container { z-index: 10000 !important; }
body.sidebar-collapse .metro-start-trigger, body:not(.sidebar-open) .metro-start-trigger { z-index: 999997 !important; }
body.sidebar-collapse .metro-start-overlay, body:not(.sidebar-open) .metro-start-overlay { z-index: 999998 !important; }
body.sidebar-collapse .metro-start-screen, body:not(.sidebar-open) .metro-start-screen { z-index: 999999 !important; }
.metro-start-header h1 { color: #fff; margin: 0; font-size: 42px; font-weight: 200; }
.metro-close-btn { width: 40px; height: 40px; background: transparent; border: none; color: #fff; cursor: pointer; font-size: 20px; }
.metro-start-content { padding: 0 80px 80px; display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 30px; column-gap: 30px; }
.metro-group { margin-bottom: 40px; display: block; width: 100%; }
.metro-group-title { color: rgba(255,255,255,0.6); font-size: 14px; font-weight: 300; margin: 0 0 20px 0; text-transform: uppercase; letter-spacing: 1px; }
.metro-tiles { display: grid; grid-template-columns: repeat(2, 150px); gap: 10px; }
.metro-tile { width: 150px; height: 150px; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 15px; color: white; text-decoration: none; transition: all 0.2s; position: relative; overflow: hidden; text-align: center; opacity: 0; transform: scale(0.8); }
.metro-tile.animate { opacity: 1; transform: scale(1); transition: all 0.6s ease; }
.metro-tile:hover { transform: scale(1.05); color: white; text-decoration: none; }
.metro-tile i { font-size: 60px !important; margin-bottom: 8px; opacity: 0.9; }
.metro-tiles .metro-tile i { font-size: 60px !important; }
.metro-tile .fa { font-size: 60px !important; }
.metro-tile span { font-size: 12px; font-weight: 600; z-index: 2; margin-top: 12px; }
.metro-tile.{ grid-column: span 2; width: 310px; }
.tile-blue { background: #0078d4; }
.tile-green { background: #107c10; }
.tile-orange { background: #d83b01; }
.tile-purple { background: #5c2d91; }
.tile-teal { background: #008272; }
.tile-amber { background: #ff8c00; }
.tile-cyan { background: #00bcf2; }
.tile-emerald { background: #00b294; }
.tile-red { background: #e81123; }
.tile-pink { background: #ec008c; }
.tile-violet { background: #881798; }
.tile-gray { background: #5d5a58; }
@media (min-width: 1800px) {
    .metro-start-content { grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); }
}
@media (max-width: 1024px) {
    .metro-start-content { padding: 0 40px 40px; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); }
}
@media (max-width: 768px) {
    .metro-start-content { padding: 0 20px 40px; grid-template-columns: 1fr; }
}
}
@media (max-width: 768px) {
    .metro-start-header { padding: 20px; }
    .metro-start-header h1 { font-size: 32px; }
    .metro-start-content { padding: 0 20px 40px; column-count: 1; }
    .metro-group { margin-bottom: 30px; }
    .metro-tiles { grid-template-columns: repeat(2, 120px); }
    .metro-tile { width: 120px; height: 120px; padding: 12px; }
    .metro-tile i { font-size: 45px !important; }
    .metro-tiles .metro-tile i { font-size: 45px !important; }
    .metro-tile .fa { font-size: 45px !important; }
    .metro-tile span { font-size: 10px; }
    .metro-tile.{ width: 250px; }
}
</style>

<?php if($account_type == 'admin' && $admin_level != 4):?>
<div class="metro-start-container">
    
    <div class="metro-start-overlay" id="mega_overlay"></div>
    
    <div class="metro-start-screen" id="mega_panel">
        <div class="metro-start-header">
            <h1>Start</h1>
            
            <!-- Menu Search Field -->
            <div style="flex: 1; max-width: 400px; margin-left: 2rem; position: relative;">
                <input type="text" id="metro-search" placeholder="Search menu... (Ctrl+K)" style="width: 100%; padding: 1rem 1rem 1rem 3rem; background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); border-radius: 0.5rem; color: #fff; font-size: 1.25rem; font-weight: 600; outline: none; transition: all 0.2s;">
                <i class="fa fa-search" style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: rgba(255,255,255,0.5); pointer-events: none; font-size: 1.25rem;"></i>
                <button id="clear-metro-search" style="position: absolute; right: 0.75rem; top: 50%; transform: translateY(-50%); background: none; border: none; color: rgba(255,255,255,0.5); cursor: pointer; display: none; padding: 0; font-size: 1.75rem;">&times;</button>
            </div>
            
            <button class="metro-close-btn" id="mega_close">
                <i class="fa fa-times fa-3x"></i>
            </button>
        </div>
        
        <div class="metro-start-content">
            <!-- GROUP 1: Dashboard & Quick Access -->
            <div class="metro-group">
                <h2 class="metro-group-title">Dashboard & Quick Access</h2>
                <div class="metro-tiles">
                    <a href="<?php echo site_url('admin/dashboard/ajax'); ?>" class="metro-tile tile-blue">
                        <i class="fa fa-tachometer-alt fa-5x"></i>
                        <span>Dashboard</span>
                    </a>
                    <a href="<?php echo site_url('admin/barcode_scanner/init'); ?>" class="metro-tile tile-cyan">
                        <i class="fa fa-qrcode fa-5x"></i>
                        <span>QR Scanner</span>
                    </a>
                    <a href="<?php echo site_url('admin/student_add'); ?>" class="metro-tile tile-green">
                        <i class="fa fa-user-plus fa-5x"></i>
                        <span>Admit Student</span>
                    </a>
                    <a href="<?php echo site_url('fee_collection'); ?>" class="metro-tile tile-emerald">
                        <i class="fa fa-hand-holding-usd fa-5x"></i>
                        <span>Fee Collection</span>
                    </a>
                    <a href="<?php echo site_url('admin/manage_attendance'); ?>" class="metro-tile tile-purple">
                        <i class="fa fa-check-square fa-5x"></i>
                        <span>Mark Attendance</span>
                    </a>
                    <a href="<?php echo site_url('admin/class_routine_view'); ?>" class="metro-tile tile-amber">
                        <i class="fa fa-calendar-week fa-5x"></i>
                        <span>Timetable</span>
                    </a>
                </div>
            </div>

            <!-- GROUP 2: Students & Users -->
            <div class="metro-group">
                <h2 class="metro-group-title">Students & Users</h2>
                <div class="metro-tiles">
                    <a href="<?php echo site_url('admin/student'); ?>" class="metro-tile tile-blue">
                        <i class="fa fa-graduation-cap fa-5x"></i>
                        <span>All Students</span>
                    </a>
                    <a href="<?php echo site_url('admin/student_promotion'); ?>" class="metro-tile tile-teal">
                        <i class="fa fa-level-up-alt fa-5x"></i>
                        <span>Promotion</span>
                    </a>
                    <a href="<?php echo site_url('admin/student_bulk_add/nav'); ?>" class="metro-tile tile-violet">
                        <i class="fa fa-users fa-5x"></i>
                        <span>Bulk Admit</span>
                    </a>
                    <a href="<?php echo site_url('admin/teacher'); ?>" class="metro-tile tile-orange">
                        <i class="fa fa-user-tie fa-5x"></i>
                        <span>Teachers</span>
                    </a>
                    <a href="<?php echo site_url('admin/parent'); ?>" class="metro-tile tile-purple">
                        <i class="fa fa-users fa-5x"></i>
                        <span>Parents</span>
                    </a>
                    <a href="<?php echo site_url('admin/admins'); ?>" class="metro-tile tile-red">
                        <i class="fa fa-user-secret fa-5x"></i>
                        <span>Admins</span>
                    </a>
                </div>
            </div>

            <!-- GROUP 3: Academic Setup -->
            <div class="metro-group">
                <h2 class="metro-group-title">Academic Setup</h2>
                <div class="metro-tiles">
                    <a href="<?php echo site_url('admin/classes'); ?>" class="metro-tile tile-purple">
                        <i class="fa fa-chalkboard fa-5x"></i>
                        <span>Classes</span>
                    </a>
                    <a href="<?php echo site_url('admin/section'); ?>" class="metro-tile tile-teal">
                        <i class="fa fa-th-large fa-5x"></i>
                        <span>Sections</span>
                    </a>
                    <a href="<?php echo site_url('admin/subject'); ?>" class="metro-tile tile-blue">
                        <i class="fa fa-book-open fa-5x"></i>
                        <span>Subjects</span>
                    </a>
                    <a href="<?php echo site_url('admin/academic_syllabus'); ?>" class="metro-tile tile-emerald">
                        <i class="fa fa-book fa-5x"></i>
                        <span>Syllabus</span>
                    </a>
                    <a href="<?php echo site_url('admin/study_material'); ?>" class="metro-tile tile-green">
                        <i class="fa fa-file-pdf fa-5x"></i>
                        <span>Study Material</span>
                    </a>
                    <a href="<?php echo site_url('admin/class_routine_view'); ?>" class="metro-tile tile-violet">
                        <i class="fa fa-calendar-week fa-5x"></i>
                        <span>Class Timetable</span>
                    </a>
                </div>
            </div>

            <!-- GROUP 4: Examinations -->
            <div class="metro-group">
                <h2 class="metro-group-title">Examinations</h2>
                <div class="metro-tiles">
                    <a href="<?php echo site_url('examination'); ?>" class="metro-tile tile-amber">
                        <i class="fa fa-clipboard-list fa-5x"></i>
                        <span>Exam Dashboard</span>
                    </a>
                    <a href="<?php echo site_url('admin/exam'); ?>" class="metro-tile tile-orange">
                        <i class="fa fa-file-alt fa-5x"></i>
                        <span>Exam List</span>
                    </a>
                    <a href="<?php echo site_url('admin/marks_manage'); ?>" class="metro-tile tile-cyan">
                        <i class="fa fa-edit fa-5x"></i>
                        <span>Manage Marks</span>
                    </a>
                    <a href="<?php echo site_url('admin/tabulation_sheet'); ?>" class="metro-tile tile-violet">
                        <i class="fa fa-table fa-5x"></i>
                        <span>Tabulation</span>
                    </a>
                    <a href="<?php echo site_url('admin/grade'); ?>" class="metro-tile tile-red">
                        <i class="fa fa-award fa-5x"></i>
                        <span>Grades</span>
                    </a>
                    <a href="<?php echo site_url('admin/student_marksheet_list'); ?>" class="metro-tile tile-pink">
                        <i class="fa fa-chart-bar fa-5x"></i>
                        <span>Student Marksheet</span>
                    </a>
                </div>
            </div>

            <!-- GROUP 5: Reports & Assessment -->
            <div class="metro-group">
                <h2 class="metro-group-title">Reports & Assessment</h2>
                <div class="metro-tiles">
                    <?php
                    // Check if conduct and interest items should be shown (hide for style_3)
                    $terminal_report_style = $this->db->get_where('settings', array('type' => 'terminal_report_style'))->row();
                    $show_conduct_interest = (!$terminal_report_style || $terminal_report_style->description != 'style_3');
                    
                    if ($show_conduct_interest):
                    ?>
                    <a href="<?php echo site_url('conduct_items'); ?>" class="metro-tile tile-teal">
                        <i class="fa fa-user-check fa-5x"></i>
                        <span>Conduct Items</span>
                    </a>
                    <a href="<?php echo site_url('interest_items'); ?>" class="metro-tile tile-emerald">
                        <i class="fa fa-heart fa-5x"></i>
                        <span>Interest Items</span>
                    </a>
                    <?php else: ?>
                    <a href="<?php echo site_url('admin/grade_creche'); ?>" class="metro-tile tile-pink">
                        <i class="fa fa-star fa-5x"></i>
                        <span>Creche Grades</span>
                    </a>
                    <a href="<?php echo site_url('admin/exam_reports'); ?>" class="metro-tile tile-blue">
                        <i class="fa fa-archive fa-5x"></i>
                        <span>Exam Archives</span>
                    </a>
                    <?php endif; ?>
                    <a href="<?php echo site_url('head_teacher_remarks'); ?>" class="metro-tile tile-violet">
                        <i class="fa fa-comment-dots fa-5x"></i>
                        <span>Principal Remarks</span>
                    </a>
                    <a href="<?php echo site_url('teacher_remarks_templates'); ?>" class="metro-tile tile-purple">
                        <i class="fa fa-comments fa-5x"></i>
                        <span>Teacher Remarks</span>
                    </a>
                    <a href="<?php echo site_url('admin/question_paper'); ?>" class="metro-tile tile-cyan">
                        <i class="fa fa-file-alt fa-5x"></i>
                        <span>Question Papers</span>
                    </a>
                    <a href="<?php echo site_url('admin/exam_marks_sms'); ?>" class="metro-tile tile-green">
                        <i class="fa fa-sms fa-5x"></i>
                        <span>SMS Exam Marks</span>
                    </a>
                </div>
            </div>

            <!-- GROUP 6: Online Learning -->
            <div class="metro-group">
                <h2 class="metro-group-title">Online Learning</h2>
                <div class="metro-tiles">
                    <a href="<?php echo site_url('admin/create_online_exam'); ?>" class="metro-tile tile-blue">
                        <i class="fa fa-laptop fa-5x"></i>
                        <span>Create Online Exam</span>
                    </a>
                    <a href="<?php echo site_url('admin/manage_online_exam'); ?>" class="metro-tile tile-purple">
                        <i class="fa fa-tasks fa-5x"></i>
                        <span>Manage Online</span>
                    </a>
                    <a href="<?php echo site_url('admin/question_paper'); ?>" class="metro-tile tile-teal">
                        <i class="fa fa-file-alt fa-5x"></i>
                        <span>Question Papers</span>
                    </a>
                    <a href="<?php echo site_url('admin/book'); ?>" class="metro-tile tile-orange">
                        <i class="fa fa-book-reader fa-5x"></i>
                        <span>Library</span>
                    </a>
                    <a href="<?php echo site_url('admin/study_material'); ?>" class="metro-tile tile-emerald">
                        <i class="fa fa-file-pdf fa-5x"></i>
                        <span>Study Materials</span>
                    </a>
                    <a href="<?php echo site_url('admin/noticeboard'); ?>" class="metro-tile tile-pink">
                        <i class="fa fa-bullhorn fa-5x"></i>
                        <span>Noticeboard</span>
                    </a>
                </div>
            </div>

            <!-- GROUP 7: Attendance & Operations -->
            <div class="metro-group">
                <h2 class="metro-group-title">Attendance & Operations</h2>
                <div class="metro-tiles">
                    <a href="<?php echo site_url('attendance/dashboard'); ?>" class="metro-tile tile-cyan">
                        <i class="fa fa-calendar-check fa-5x"></i>
                        <span>Attendance Dashboard</span>
                    </a>
                    <a href="<?php echo site_url('attendance/report'); ?>" class="metro-tile tile-purple">
                        <i class="fa fa-chart-bar fa-5x"></i>
                        <span>Attendance Report</span>
                    </a>
                    <?php if (is_fee_module_enabled('transport')): ?>
                    <a href="<?php echo site_url('admin/transportation'); ?>" class="metro-tile tile-amber">
                        <i class="fa fa-bus fa-5x"></i>
                        <span>Transport</span>
                    </a>
                    <a href="<?php echo site_url('admin/cft_student_receipt'); ?>" class="metro-tile tile-orange">
                        <i class="fa fa-hand-holding-usd fa-5x"></i>
                        <span>Daily Fees</span>
                    </a>
                    <?php else: ?>
                    <a href="<?php echo site_url('admin/payroll'); ?>" class="metro-tile tile-amber">
                        <i class="fa fa-dollar-sign fa-5x"></i>
                        <span>Payroll</span>
                    </a>
                    <a href="<?php echo site_url('admin/payslipList'); ?>" class="metro-tile tile-orange">
                        <i class="fa fa-file-invoice fa-5x"></i>
                        <span>Payslips</span>
                    </a>
                    <?php endif; ?>
                    <a href="<?php echo site_url('admin/book'); ?>" class="metro-tile tile-teal">
                        <i class="fa fa-book-reader fa-5x"></i>
                        <span>Library</span>
                    </a>
                    <?php if(get_settings('boarding_system') == 'yes'): ?>
                    <a href="<?php echo site_url('admin/manageBoardingHouse'); ?>" class="metro-tile tile-red">
                        <i class="fa fa-building fa-5x"></i>
                        <span>Boarding</span>
                    </a>
                    <?php else: ?>
                    <a href="<?php echo site_url('admin/expense'); ?>" class="metro-tile tile-red">
                        <i class="fa fa-receipt fa-5x"></i>
                        <span>Expenses</span>
                    </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- GROUP 8: Inventory & Assets -->
            <div class="metro-group">
                <h2 class="metro-group-title">Inventory & Assets</h2>
                <div class="metro-tiles">
                    <?php if ($admin_level <= 3): ?>
                    <a href="<?php echo site_url('inventory'); ?>" class="metro-tile tile-emerald">
                        <i class="fa fa-boxes fa-5x"></i>
                        <span>Inventory Dashboard</span>
                    </a>
                    <a href="<?php echo site_url('inventory/pos'); ?>" class="metro-tile tile-violet">
                        <i class="fa fa-shopping-cart fa-5x"></i>
                        <span>Point of Sale</span>
                    </a>
                    <a href="<?php echo site_url('inventory/products'); ?>" class="metro-tile tile-orange">
                        <i class="fa fa-box fa-5x"></i>
                        <span>Products</span>
                    </a>
                    <a href="<?php echo site_url('inventory/sales'); ?>" class="metro-tile tile-pink">
                        <i class="fa fa-receipt fa-5x"></i>
                        <span>Sales History</span>
                    </a>
                    <a href="<?php echo site_url('inventory/returns'); ?>" class="metro-tile tile-cyan">
                        <i class="fa fa-undo fa-5x"></i>
                        <span>Returns</span>
                    </a>
                    <a href="<?php echo site_url('inventory/suppliers'); ?>" class="metro-tile tile-blue">
                        <i class="fa fa-truck fa-5x"></i>
                        <span>Suppliers</span>
                    </a>
                    <?php else: ?>
                    <a href="<?php echo site_url('admin/dormitory'); ?>" class="metro-tile tile-emerald">
                        <i class="fa fa-bed fa-5x"></i>
                        <span>Dormitory</span>
                    </a>
                    <a href="<?php echo site_url('admin/hostel'); ?>" class="metro-tile tile-violet">
                        <i class="fa fa-hotel fa-5x"></i>
                        <span>Hostel</span>
                    </a>
                    <a href="<?php echo site_url('admin/canteen'); ?>" class="metro-tile tile-orange">
                        <i class="fa fa-utensils fa-5x"></i>
                        <span>Canteen</span>
                    </a>
                    <a href="<?php echo site_url('admin/vehicle'); ?>" class="metro-tile tile-pink">
                        <i class="fa fa-car fa-5x"></i>
                        <span>Vehicles</span>
                    </a>
                    <a href="<?php echo site_url('admin/certificate'); ?>" class="metro-tile tile-cyan">
                        <i class="fa fa-certificate fa-5x"></i>
                        <span>Certificates</span>
                    </a>
                    <a href="<?php echo site_url('admin/id_card'); ?>" class="metro-tile tile-blue">
                        <i class="fa fa-id-card fa-5x"></i>
                        <span>ID Cards</span>
                    </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- GROUP 9: Student Billing -->
            <div class="metro-group">
                <h2 class="metro-group-title">Student Billing</h2>
                <div class="metro-tiles">
                    <a href="<?php echo site_url('admin/student_invoice'); ?>" class="metro-tile tile-emerald">
                        <i class="fa fa-file-invoice-dollar fa-5x"></i>
                        <span>Student Billing</span>
                    </a>
                    <a href="<?php echo site_url('admin/discount_profiles'); ?>" class="metro-tile tile-pink">
                        <i class="fa fa-percent fa-5x"></i>
                        <span>Discounts</span>
                    </a>
                    <a href="<?php echo site_url('admin/apply_discount'); ?>" class="metro-tile tile-purple">
                        <i class="fa fa-tags fa-5x"></i>
                        <span>Apply Discount</span>
                    </a>
                    <a href="<?php echo site_url('admin/student_ledger'); ?>" class="metro-tile tile-blue">
                        <i class="fa fa-book fa-5x"></i>
                        <span>Student Ledger</span>
                    </a>
                    <a href="<?php echo site_url('admin/manage_discount_assignments'); ?>" class="metro-tile tile-cyan">
                        <i class="fa fa-users-cog fa-5x"></i>
                        <span>Discount Assignments</span>
                    </a>
                    <a href="<?php echo site_url('admin/discount_reports'); ?>" class="metro-tile tile-teal">
                        <i class="fa fa-chart-bar fa-5x"></i>
                        <span>Discount Reports</span>
                    </a>
                </div>
            </div>

            <!-- GROUP 10: Collections & Cashier -->
            <div class="metro-group">
                <h2 class="metro-group-title">Collections & Cashier</h2>
                <div class="metro-tiles">
                    <a href="<?php echo site_url('admin/cashier_dashboard_admin'); ?>" class="metro-tile tile-green">
                        <i class="fa fa-cash-register fa-5x"></i>
                        <span>Cashier Dashboard</span>
                    </a>
                    <a href="<?php echo site_url('admin/my_collections'); ?>" class="metro-tile tile-cyan">
                        <i class="fa fa-chart-line fa-5x"></i>
                        <span>My Collections</span>
                    </a>
                    <a href="<?php echo site_url('admin/cashier_daily_summary'); ?>" class="metro-tile tile-pink">
                        <i class="fa fa-chart-bar fa-5x"></i>
                        <span>Daily Summary</span>
                    </a>
                    <a href="<?php echo site_url('admin/cashier_handover_report'); ?>" class="metro-tile tile-red">
                        <i class="fa fa-hand-holding-usd fa-5x"></i>
                        <span>Handover Report</span>
                    </a>
                    <a href="<?php echo site_url('fee_collection/statistics'); ?>" class="metro-tile tile-violet">
                        <i class="fa fa-chart-bar fa-5x"></i>
                        <span>Fee Statistics</span>
                    </a>
                    <a href="<?php echo site_url('admin/print_receipts'); ?>" class="metro-tile tile-orange">
                        <i class="fa fa-print fa-5x"></i>
                        <span>Print Receipts</span>
                    </a>
                </div>
            </div>

            <!-- GROUP 11: Income & Expenses -->
            <div class="metro-group">
                <h2 class="metro-group-title">Income & Expenses</h2>
                <div class="metro-tiles">
                    <a href="<?php echo site_url('admin/income_dashboard'); ?>" class="metro-tile tile-teal">
                        <i class="fa fa-coins fa-5x"></i>
                        <span>Income</span>
                    </a>
                    <a href="<?php echo site_url('admin/expenditure_dashboard'); ?>" class="metro-tile tile-red">
                        <i class="fa fa-money-bill-wave fa-5x"></i>
                        <span>Expenditure</span>
                    </a>
                    <a href="<?php echo site_url('admin/expense'); ?>" class="metro-tile tile-orange">
                        <i class="fa fa-receipt fa-5x"></i>
                        <span>All Expenses</span>
                    </a>
                    <a href="<?php echo site_url('admin/expenditure_reports'); ?>" class="metro-tile tile-amber">
                        <i class="fa fa-file-alt fa-5x"></i>
                        <span>Expense Reports</span>
                    </a>
                    <a href="<?php echo site_url('admin/expense_category'); ?>" class="metro-tile tile-purple">
                        <i class="fa fa-list fa-5x"></i>
                        <span>Expense Categories</span>
                    </a>
                    <a href="<?php echo site_url('admin/student_credits'); ?>" class="metro-tile tile-blue">
                        <i class="fa fa-credit-card fa-5x"></i>
                        <span>Student Credits</span>
                    </a>
                </div>
            </div>

            <!-- GROUP 12: Financial Reports -->
            <div class="metro-group">
                <h2 class="metro-group-title">Financial Reports</h2>
                <div class="metro-tiles">
                    <a href="<?php echo site_url('admin/financial_reports/receivables'); ?>" class="metro-tile tile-violet">
                        <i class="fa fa-arrow-down fa-5x"></i>
                        <span>Receivables</span>
                    </a>
                    <a href="<?php echo site_url('admin/financial_reports/income-expenditure'); ?>" class="metro-tile tile-emerald">
                        <i class="fa fa-balance-scale fa-5x"></i>
                        <span>Income & Expense</span>
                    </a>
                    <a href="<?php echo site_url('admin/payroll'); ?>" class="metro-tile tile-green">
                        <i class="fa fa-dollar-sign fa-5x"></i>
                        <span>Payroll</span>
                    </a>
                    <a href="<?php echo site_url('admin/payslipList'); ?>" class="metro-tile tile-cyan">
                        <i class="fa fa-file-invoice fa-5x"></i>
                        <span>Payslips</span>
                    </a>
                    <a href="<?php echo site_url('admin/pension_providers'); ?>" class="metro-tile tile-blue">
                        <i class="fa fa-building fa-5x"></i>
                        <span>Pension Providers</span>
                    </a>
                    <a href="<?php echo site_url('admin/aging_report'); ?>" class="metro-tile tile-pink">
                        <i class="fa fa-clock fa-5x"></i>
                        <span>Aging Report</span>
                    </a>
                </div>
            </div>

            <!-- GROUP 13: Communication -->
            <div class="metro-group">
                <h2 class="metro-group-title">Communication</h2>
                <div class="metro-tiles">
                    <a href="<?php echo site_url('admin/noticeboard'); ?>" class="metro-tile tile-orange">
                        <i class="fa fa-bullhorn fa-5x"></i>
                        <span>Noticeboard</span>
                    </a>
                    <a href="<?php echo site_url('admin/message'); ?>" class="metro-tile tile-blue">
                        <i class="fa fa-envelope fa-5x"></i>
                        <span>Messages</span>
                    </a>
                    <a href="<?php echo site_url('admin/sms_automation'); ?>" class="metro-tile tile-violet">
                        <i class="fa fa-robot fa-5x"></i>
                        <span>SMS Automation</span>
                    </a>
                    <a href="<?php echo site_url('admin/exam_marks_sms'); ?>" class="metro-tile tile-green">
                        <i class="fa fa-sms fa-5x"></i>
                        <span>SMS Marks</span>
                    </a>
                    <a href="<?php echo site_url('admin/send_bill_reminder'); ?>" class="metro-tile tile-pink">
                        <i class="fa fa-paper-plane fa-5x"></i>
                        <span>Bill Reminders</span>
                    </a>
                    <a href="<?php echo site_url('admin/sms_log_report'); ?>" class="metro-tile tile-cyan">
                        <i class="fa fa-list-alt fa-5x"></i>
                        <span>SMS Log</span>
                    </a>
                </div>
            </div>

            <!-- GROUP 14: Student Management -->
            <div class="metro-group">
                <h2 class="metro-group-title">Student Management</h2>
                <div class="metro-tiles">
                    <a href="<?php echo site_url('admin/alumni'); ?>" class="metro-tile tile-blue">
                        <i class="fa fa-user-graduate fa-5x"></i>
                        <span>Alumni</span>
                    </a>
                    <a href="<?php echo site_url('admin/muted_students'); ?>" class="metro-tile tile-red">
                        <i class="fa fa-user-slash fa-5x"></i>
                        <span>Muted Students</span>
                    </a>
                    <a href="<?php echo site_url('admin/student_on_special_diet'); ?>" class="metro-tile tile-orange">
                        <i class="fa fa-utensils fa-5x"></i>
                        <span>Special Diet</span>
                    </a>
                    <a href="<?php echo site_url('admin/bulk_student_id'); ?>" class="metro-tile tile-cyan">
                        <i class="fa fa-id-card fa-5x"></i>
                        <span>Student ID Cards</span>
                    </a>
                    <a href="<?php echo site_url('admin/librarian'); ?>" class="metro-tile tile-teal">
                        <i class="fa fa-user-graduate fa-5x"></i>
                        <span>Librarians</span>
                    </a>
                    <a href="<?php echo site_url('admin/non_teaching_staff'); ?>" class="metro-tile tile-purple">
                        <i class="fa fa-user-tie fa-5x"></i>
                        <span>Non-Teaching Staff</span>
                    </a>
                </div>
            </div>

            <!-- GROUP 15: System Settings (Admin Only) -->
            <?php if ($admin_level == 1): ?>
            <div class="metro-group">
                <h2 class="metro-group-title">System Settings</h2>
                <div class="metro-tiles">
                    <a href="<?php echo site_url('admin/system_settings'); ?>" class="metro-tile tile-gray">
                        <i class="fa fa-cog fa-5x"></i>
                        <span>General Settings</span>
                    </a>
                    <a href="<?php echo site_url('admin/theme_settings'); ?>" class="metro-tile tile-purple">
                        <i class="fa fa-palette fa-5x"></i>
                        <span>Theme Settings</span>
                    </a>
                    <a href="<?php echo site_url('admin/sms_settings'); ?>" class="metro-tile tile-orange">
                        <i class="fa fa-sms fa-5x"></i>
                        <span>SMS Settings</span>
                    </a>
                    <a href="<?php echo site_url('user_permissions'); ?>" class="metro-tile tile-cyan">
                        <i class="fa fa-user-shield fa-5x"></i>
                        <span>User Permissions</span>
                    </a>
                    <a href="<?php echo site_url('admin/permission_settings'); ?>" class="metro-tile tile-red">
                        <i class="fa fa-lock fa-5x"></i>
                        <span>Role Permissions</span>
                    </a>
                    <a href="<?php echo site_url('admin/backup'); ?>" class="metro-tile tile-emerald">
                        <i class="fa fa-database fa-5x"></i>
                        <span>Backup & Restore</span>
                    </a>
                </div>
            </div>
            <?php else: ?>
            <div class="metro-group">
                <h2 class="metro-group-title">Reports & Analytics</h2>
                <div class="metro-tiles">
                    <a href="<?php echo site_url('admin/financial_reports/receivables'); ?>" class="metro-tile tile-blue">
                        <i class="fa fa-arrow-down fa-5x"></i>
                        <span>Receivables</span>
                    </a>
                    <a href="<?php echo site_url('admin/financial_reports/payments'); ?>" class="metro-tile tile-purple">
                        <i class="fa fa-credit-card fa-5x"></i>
                        <span>Payments</span>
                    </a>
                    <a href="<?php echo site_url('admin/discount_reports'); ?>" class="metro-tile tile-orange">
                        <i class="fa fa-chart-bar fa-5x"></i>
                        <span>Discount Reports</span>
                    </a>
                    <a href="<?php echo site_url('admin/sms_log_report'); ?>" class="metro-tile tile-cyan">
                        <i class="fa fa-list-alt fa-5x"></i>
                        <span>SMS Log</span>
                    </a>
                    <a href="<?php echo site_url('admin/student_ledger'); ?>" class="metro-tile tile-teal">
                        <i class="fa fa-book fa-5x"></i>
                        <span>Student Ledger</span>
                    </a>
                    <a href="<?php echo site_url('admin/aging_report'); ?>" class="metro-tile tile-green">
                        <i class="fa fa-clock fa-5x"></i>
                        <span>Aging Report</span>
                    </a>
                </div>
            </div>
            <?php endif; ?>

            <!-- GROUP 16: Approvals & Requests (Admin Only) -->
            <?php if ($admin_level == 1): ?>
            <div class="metro-group">
                <h2 class="metro-group-title">Approvals & Requests</h2>
                <div class="metro-tiles">
                    <a href="<?php echo site_url('admin/discount_approvals'); ?>" class="metro-tile tile-amber">
                        <i class="fa fa-clipboard-check fa-5x"></i>
                        <span>Discount Approvals</span>
                    </a>
                    <a href="<?php echo site_url('admin/modification_requests'); ?>" class="metro-tile tile-pink">
                        <i class="fa fa-file-invoice fa-5x"></i>
                        <span>Invoice Approvals</span>
                    </a>
                    <a href="<?php echo site_url('admin/payment_settings'); ?>" class="metro-tile tile-blue">
                        <i class="fa fa-credit-card fa-5x"></i>
                        <span>Payment Settings</span>
                    </a>
                    <a href="<?php echo site_url('admin/daily_fee_module_settings'); ?>" class="metro-tile tile-green">
                        <i class="fa fa-toggle-on fa-5x"></i>
                        <span>Fee Modules</span>
                    </a>
                    <a href="<?php echo site_url('admin/fee_structure'); ?>" class="metro-tile tile-violet">
                        <i class="fa fa-list-alt fa-5x"></i>
                        <span>Fee Structure</span>
                    </a>
                    <a href="<?php echo site_url('admin/daily_fee_rates'); ?>" class="metro-tile tile-purple">
                        <i class="fa fa-money-bill fa-5x"></i>
                        <span>Daily Fee Rates</span>
                    </a>
                </div>
            </div>
            <?php else: ?>
            <div class="metro-group">
                <h2 class="metro-group-title">My Tasks & Activities</h2>
                <div class="metro-tiles">
                    <a href="<?php echo site_url('admin/my_collections'); ?>" class="metro-tile tile-amber">
                        <i class="fa fa-chart-line fa-5x"></i>
                        <span>My Collections</span>
                    </a>
                    <a href="<?php echo site_url('admin/marks_manage'); ?>" class="metro-tile tile-pink">
                        <i class="fa fa-edit fa-5x"></i>
                        <span>Manage Marks</span>
                    </a>
                    <a href="<?php echo site_url('admin/student_information'); ?>" class="metro-tile tile-blue">
                        <i class="fa fa-user-graduate fa-5x"></i>
                        <span>My Students</span>
                    </a>
                    <a href="<?php echo site_url('admin/class_routine_view'); ?>" class="metro-tile tile-green">
                        <i class="fa fa-calendar-check fa-5x"></i>
                        <span>My Schedule</span>
                    </a>
                    <a href="<?php echo site_url('admin/cashier_daily_summary'); ?>" class="metro-tile tile-violet">
                        <i class="fa fa-chart-bar fa-5x"></i>
                        <span>Daily Summary</span>
                    </a>
                    <a href="<?php echo site_url('admin/message'); ?>" class="metro-tile tile-purple">
                        <i class="fa fa-envelope fa-5x"></i>
                        <span>Messages</span>
                    </a>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- GROUP 17: Staff Management -->
            <div class="metro-group">
                <h2 class="metro-group-title">Staff Management</h2>
                <div class="metro-tiles">
                    <?php if ($admin_level == 1): ?>
                    <a href="<?php echo site_url('admin/teacher_attendance_privileges'); ?>" class="metro-tile tile-purple">
                        <i class="fa fa-user-shield fa-5x"></i>
                        <span>Attendance Privileges</span>
                    </a>
                    <?php else: ?>
                    <a href="<?php echo site_url('admin/admins'); ?>" class="metro-tile tile-purple">
                        <i class="fa fa-user-secret fa-5x"></i>
                        <span>Administrators</span>
                    </a>
                    <?php endif; ?>
                    <a href="<?php echo site_url('admin/teacher'); ?>" class="metro-tile tile-blue">
                        <i class="fa fa-chalkboard-teacher fa-5x"></i>
                        <span>Teachers</span>
                    </a>
                    <a href="<?php echo site_url('admin/non_teaching_staff'); ?>" class="metro-tile tile-teal">
                        <i class="fa fa-users fa-5x"></i>
                        <span>Non-Teaching Staff</span>
                    </a>
                    <a href="<?php echo site_url('admin/parent'); ?>" class="metro-tile tile-amber">
                        <i class="fa fa-users fa-5x"></i>
                        <span>Parents</span>
                    </a>
                    <a href="<?php echo site_url('admin/librarian'); ?>" class="metro-tile tile-orange">
                        <i class="fa fa-book-reader fa-5x"></i>
                        <span>Librarians</span>
                    </a>
                    <a href="<?php echo site_url('admin/payroll'); ?>" class="metro-tile tile-green">
                        <i class="fa fa-money-bill-wave fa-5x"></i>
                        <span>Staff Payroll</span>
                    </a>
                </div>
            </div>

            <!-- GROUP 18: My Account -->
            <div class="metro-group">
                <h2 class="metro-group-title">My Account</h2>
                <div class="metro-tiles">
                    <a href="<?php 
                        $current_admin_id = $this->session->userdata('admin_id');
                        echo site_url('admin/admin_details/'.$current_admin_id); 
                    ?>" class="metro-tile tile-blue">
                        <i class="fa fa-user-circle fa-5x"></i>
                        <span>My Profile</span>
                    </a>
                    <a href="<?php echo site_url('admin/system_settings'); ?>" class="metro-tile tile-amber">
                        <i class="fa fa-cog fa-5x"></i>
                        <span>System Settings</span>
                    </a>
                    <a href="<?php echo site_url('admin/theme_settings'); ?>" class="metro-tile tile-cyan">
                        <i class="fa fa-palette fa-5x"></i>
                        <span>Theme Settings</span>
                    </a>
                    <a href="<?php echo site_url('admin/sms_settings'); ?>" class="metro-tile tile-purple">
                        <i class="fa fa-sms fa-5x"></i>
                        <span>SMS Settings</span>
                    </a>
                    <a href="<?php echo site_url('admin/noticeboard'); ?>" class="metro-tile tile-green">
                        <i class="fa fa-bullhorn fa-5x"></i>
                        <span>Noticeboard</span>
                    </a>
                    <a href="<?php echo site_url('login/logout'); ?>" class="metro-tile tile-red">
                        <i class="fa fa-sign-out-alt fa-5x"></i>
                        <span>Logout</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Animate tiles function based on vertical position
function animateTiles() {
    const tiles = Array.from(document.querySelectorAll('.metro-tile'));
    tiles.forEach(tile => tile.classList.remove('animate'));
    
    // Sort tiles by vertical position (top to bottom)
    const sortedTiles = tiles.sort((a, b) => {
        const rectA = a.getBoundingClientRect();
        const rectB = b.getBoundingClientRect();
        if (Math.abs(rectA.top - rectB.top) < 10) {
            return rectA.left - rectB.left;
        }
        return rectA.top - rectB.top;
    });
    
    setTimeout(function() {
        sortedTiles.forEach(function(tile, index) {
            setTimeout(function() {
                tile.classList.add('animate');
            }, index * 80);
        });
    }, 200);
}

// Metro UI Mega Menu Functions
function initMegaMenu() {
    const trigger = document.getElementById('mega_link');
    const overlay = document.getElementById('mega_overlay');
    const panel = document.getElementById('mega_panel');
    const closeBtn = document.getElementById('mega_close');
    const toggleBtn = document.getElementById('metro_toggle');
    
    if (!overlay || !panel || !closeBtn || !toggleBtn) return;
    
    // Function to open metro menu
    function openMetroMenu(e) {
        e.preventDefault();
        e.stopPropagation();
        
        // Check if sidebar is NOT collapsed and collapse it immediately
        const pageContainer = $('.page-container');
        if (!pageContainer.hasClass('sidebar-collapsed')) {
            // Call the Neon theme's built-in collapse function
            if (typeof hide_sidebar_menu === 'function') {
                hide_sidebar_menu(false); // false = no animation, immediate collapse
            } else {
                // Fallback: manually add the collapsed class
                pageContainer.addClass('sidebar-collapsed');
            }
        }
        
        // Clean up mobile sidebar if open
        $('.mobile-backdrop').remove();
        $('.sidebar-menu').removeClass('mobile-open');
        $('body').removeClass('sidebar-open');
        
        // Open metro menu
        overlay.classList.add('active');
        panel.classList.add('active');
        document.body.style.overflow = 'hidden';
        
        // Animate tiles
        animateTiles();
    }
    
    // Toggle button opens metro menu
    toggleBtn.addEventListener('click', openMetroMenu);
    
    // Original trigger also works if it exists
    if (trigger) {
        trigger.addEventListener('click', openMetroMenu);
    }
    
    // Function to close metro menu
    function closeMetroMenu() {
        overlay.classList.remove('active');
        panel.classList.remove('active');
        panel.classList.remove('sidebar-open');
        document.body.style.overflow = '';
        
        // Clean up any lingering mobile backdrop
        $('.mobile-backdrop').remove();
    }
    
    closeBtn.addEventListener('click', closeMetroMenu);
    overlay.addEventListener('click', closeMetroMenu);
    
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && panel.classList.contains('active')) {
            closeMetroMenu();
        }
    });
    
    panel.addEventListener('click', function(e) {
        e.stopPropagation();
    });
    
    // Metro Menu Search Functionality
    const metroSearch = document.getElementById('metro-search');
    const clearMetroSearch = document.getElementById('clear-metro-search');
    
    if (metroSearch) {
        let allMetroTiles = [];
        let allMetroGroups = [];
        
        // Collect all tiles and groups
        function initializeMetroItems() {
            allMetroTiles = Array.from(document.querySelectorAll('.metro-tile'));
            allMetroGroups = Array.from(document.querySelectorAll('.metro-group'));
        }
        
        // Filter tiles
        function filterMetroMenu(searchTerm) {
            searchTerm = searchTerm.toLowerCase().trim();
            
            if (searchTerm === '') {
                // Show all
                allMetroTiles.forEach(tile => {
                    tile.classList.remove('metro-tile-hidden', 'metro-tile-highlight');
                });
                allMetroGroups.forEach(group => {
                    group.classList.remove('metro-group-hidden');
                });
                clearMetroSearch.style.display = 'none';
                return;
            }
            
            clearMetroSearch.style.display = 'block';
            
            // Track which groups have visible tiles
            const visibleGroups = new Set();
            
            allMetroTiles.forEach(tile => {
                const text = tile.textContent.trim().toLowerCase();
                const matches = text.includes(searchTerm);
                
                if (matches) {
                    tile.classList.remove('metro-tile-hidden');
                    tile.classList.add('metro-tile-highlight');
                    
                    // Mark this tile's group as visible
                    const group = tile.closest('.metro-group');
                    if (group) {
                        visibleGroups.add(group);
                    }
                } else {
                    tile.classList.add('metro-tile-hidden');
                    tile.classList.remove('metro-tile-highlight');
                }
            });
            
            // Hide groups with no visible tiles
            allMetroGroups.forEach(group => {
                if (visibleGroups.has(group)) {
                    group.classList.remove('metro-group-hidden');
                } else {
                    group.classList.add('metro-group-hidden');
                }
            });
        }
        
        // Initialize when menu opens
        setTimeout(() => {
            initializeMetroItems();
        }, 100);
        
        // Search input handler
        metroSearch.addEventListener('input', function(e) {
            filterMetroMenu(e.target.value);
        });
        
        // Clear button
        clearMetroSearch.addEventListener('click', function() {
            metroSearch.value = '';
            filterMetroMenu('');
            metroSearch.focus();
        });
        
        // Focus search when menu opens
        overlay.addEventListener('transitionend', function() {
            if (panel.classList.contains('active')) {
                setTimeout(() => {
                    metroSearch.focus();
                }, 100);
            }
        });
        
        // Keyboard shortcut Ctrl+K
        document.addEventListener('keydown', function(e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 'k' && panel.classList.contains('active')) {
                e.preventDefault();
                metroSearch.focus();
                metroSearch.select();
            }
            
            // Escape to clear
            if (e.key === 'Escape' && document.activeElement === metroSearch) {
                metroSearch.value = '';
                filterMetroMenu('');
            }
        });
    }
}

// Initialize on page load
$(document).ready(function() {
    initMegaMenu();
    
    // Auto-open metro menu on dashboard page - DESKTOP ONLY (screens wider than 768px)
    var currentUrl = window.location.href;
    var isDesktop = window.matchMedia("(min-width: 769px)").matches;
    
    // Don't auto-open metro menu on staff details pages (both old code-based and new ID-based routes)
    var isStaffDetailsPage = currentUrl.includes('/admin/staffDetails') || 
                             currentUrl.includes('/admin/admin_details') || 
                             currentUrl.includes('/admin/teacher_details') || 
                             currentUrl.includes('/admin/non_teaching_staff_details');
    
    if (isDesktop && !isStaffDetailsPage && (currentUrl.includes('/admin/dashboard') || currentUrl.includes('/admin/index') || currentUrl.endsWith('/admin') || currentUrl.endsWith('/admin/'))) {
        // Wait for page to fully load, then slide in
        $(window).on('load', function() {
            setTimeout(function() {
                const overlay = document.getElementById('mega_overlay');
                const panel = document.getElementById('mega_panel');
                if (overlay && panel) {
                    // Force collapse sidebar for full metro experience
                    const body = document.body;
                    if (body.classList.contains('sidebar-open')) {
                        body.classList.remove('sidebar-open');
                        body.classList.add('sidebar-collapse');
                    } else if (!body.classList.contains('sidebar-collapse')) {
                        body.classList.add('sidebar-collapse');
                    }
                    
                    // Clean up any mobile sidebar backdrop
                    $('.mobile-backdrop').remove();
                    $('.sidebar-menu').removeClass('mobile-open');
                    
                    overlay.classList.add('active');
                    panel.classList.add('active');
                    document.body.style.overflow = 'hidden';
                    
                    // Animate tiles
                    animateTiles();
                }
            }, 1000);
        });
    }
});
</script>
<?php endif; ?>
