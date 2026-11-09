<?php
$currency = $this->db->get_where('settings', array('type' => 'currency'))->row()->description;
$fmt = new NumberFormatter('en_US', NumberFormatter::CURRENCY);

$user_id = $this->session->userdata('login_user_id');
$admin_level = $this->db->get_where('admin', array('admin_id' => $user_id))->row()->level;

//check if this is coming from a submitted payment page (take payment)
if (isset($_GET['id'])) {
    $imported_id = $_GET['id'];
}

$un_year = $running_year;
$forYear = 'yes';

if ($search == 'search') {

    $year;
    $term;
    //$sem;

    $un_year = $year;
    $un_term = $term;
    //$un_sem  = $sem;

    $date_timestamp = $date;
    $feeding_timestamp = $date;
    $transport_timestamp = $date;

} else {

    $un_year = $running_year;
    $un_term = $running_term;
    //$un_sem = $running_sem;

    $date_timestamp = strtotime(date('d-m-Y'));

}

$date_timestamp = date('l M d, Y', $date_timestamp);

?>

<style>
/* Modern Invoices Page Styling */
.invoices-container {
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    min-height: 100vh;
    padding: 1rem 0;
}

.modern-card {
    background: #ffffff;
    border-radius: 20px;
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    padding: 2rem;
    margin-bottom: 2rem;
    transition: all 0.3s ease;
    border: 1px solid rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(10px);
}

/* Table holder specific styling - reduced padding for better table display */
#table_holder.modern-card {
    padding: 1rem;
    overflow-x: auto;
}

/* Ensure tables display properly */
#table_holder table {
    width: 100%;
    min-width: 100%;
    table-layout: auto;
}

#table_holder .dataTables_wrapper {
    overflow-x: auto;
}

#table_holder .dataTables_scrollBody {
    overflow-x: auto !important;
}

.modern-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.15), 0 10px 20px -5px rgba(0, 0, 0, 0.1);
}

.search-card {
    background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
    color: white;
    border: none;
}

.search-card .form-input-modern {
    background: rgba(255, 255, 255, 0.95);
    border: 2px solid rgba(255, 255, 255, 0.3);
    color: #1e293b;
    backdrop-filter: blur(10px);
    font-size: 1.1rem;
    font-weight: 600;
}

.search-card .form-input-modern::placeholder {
    color: rgba(30, 41, 59, 0.6);
}

.search-card .form-input-modern:focus {
    background: rgba(255, 255, 255, 1);
    border-color: #3b82f6;
    box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.2);
    color: #1e293b;
}

.search-card select.form-input-modern {
    background: rgba(255, 255, 255, 0.95);
    color: #1e293b;
}

.search-card select.form-input-modern option {
    background: white;
    color: #1e293b;
    padding: 8px 12px;
    font-size: 1rem;
    font-weight: 500;
}

.search-card label {
    color: white;
    font-size: 1.1rem;
    font-weight: 700;
}

.stats-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.stats-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
    transition: left 0.5s;
}

.stats-card:hover::before {
    left: 100%;
}

.stats-card:hover {
    transform: translateY(-8px) scale(1.02);
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
}

.stats-card.blue {
    background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
}

.stats-card.green {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
}

.stats-card.purple {
    background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
}

.stats-icon {
    font-size: 3.5rem;
    margin-bottom: 1.2rem;
    opacity: 0.9;
}

.stats-amount {
    font-size: 3rem;
    font-weight: 800;
    margin: 0.5rem 0;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.stats-label {
    font-size: 1.3rem;
    font-weight: 600;
    opacity: 0.9;
    margin-bottom: 0.5rem;
}

.stats-subtitle {
    font-size: 1rem;
    opacity: 0.8;
    margin-top: 0.5rem;
}

.form-input-modern {
    padding: 1.2rem 1.5rem;
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    font-size: 1.1rem;
    font-weight: 600;
    transition: all 0.3s ease;
    background: #ffffff;
    color: #374151;
    width: 100%;
}

.form-input-modern:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
    transform: translateY(-2px);
}

.form-input-modern::placeholder {
    color: #9ca3af;
    font-weight: 400;
}

.btn-modern {
    padding: 1.2rem 2.5rem;
    border-radius: 12px;
    font-weight: 700;
    font-size: 1.1rem;
    transition: all 0.3s ease;
    border: none;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    text-decoration: none;
    min-height: 3.5rem;
}

.btn-modern-primary {
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
    color: white;
    box-shadow: 0 4px 14px 0 rgba(59, 130, 246, 0.25);
}

.btn-modern-primary:hover {
    background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
    transform: translateY(-2px);
    box-shadow: 0 8px 25px 0 rgba(59, 130, 246, 0.35);
}

.btn-modern-success {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
    box-shadow: 0 4px 14px 0 rgba(16, 185, 129, 0.25);
}

.btn-modern-success:hover {
    background: linear-gradient(135deg, #059669 0%, #047857 100%);
    transform: translateY(-2px);
    box-shadow: 0 8px 25px 0 rgba(16, 185, 129, 0.35);
}

.btn-modern-danger {
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    color: white;
    box-shadow: 0 4px 14px 0 rgba(239, 68, 68, 0.25);
}

.btn-modern-danger:hover {
    background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
    transform: translateY(-2px);
    box-shadow: 0 8px 25px 0 rgba(239, 68, 68, 0.35);
}

.btn-modern-info {
    background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%);
    color: white;
    box-shadow: 0 4px 14px 0 rgba(6, 182, 212, 0.25);
}

.btn-modern-info:hover {
    background: linear-gradient(135deg, #0891b2 0%, #0e7490 100%);
    transform: translateY(-2px);
    box-shadow: 0 8px 25px 0 rgba(6, 182, 212, 0.35);
}

.filter-section {
    background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
    border-radius: 20px;
    padding: 2rem;
    margin: 2rem 0;
    border: 1px solid #e2e8f0;
}

.class-selection-card {
    background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
    color: white;
    border-radius: 20px;
    padding: 2rem;
    text-align: center;
    margin: 2rem 0;
}

.class-selection-card .form-input-modern {
    background: rgba(255, 255, 255, 0.1);
    border: 2px solid rgba(255, 255, 255, 0.2);
    color: white;
    font-size: 1.2rem;
    padding: 1.25rem 1.5rem;
}

.class-selection-card .form-input-modern::placeholder {
    color: rgba(255, 255, 255, 0.7);
}

.class-selection-card .form-input-modern:focus {
    background: rgba(255, 255, 255, 0.2);
    border-color: rgba(255, 255, 255, 0.5);
}

.loading-spinner {
    display: inline-block;
    width: 2rem;
    height: 2rem;
    border: 3px solid rgba(255, 255, 255, 0.3);
    border-radius: 50%;
    border-top-color: #3b82f6;
    animation: spin 1s ease-in-out infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

.fade-in {
    animation: fadeIn 0.5s ease-in-out;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

.slide-up {
    animation: slideUp 0.3s ease-out;
}

@keyframes slideUp {
    from { transform: translateY(20px); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
}

/* Responsive Design */
@media (max-width: 768px) {
    .invoices-container {
        padding: 1rem 0;
    }
    
    .modern-card {
        padding: 1rem;
        margin-bottom: 1rem;
    }
    
    #table_holder.modern-card {
        padding: 0.5rem;
    }
    
    .stats-amount {
        font-size: 2rem;
    }
    
    .stats-icon {
        font-size: 2.5rem;
    }
    
    .btn-modern {
        padding: 1rem 1.5rem;
        font-size: 1rem;
        width: 100%;
    }
    
    .form-input-modern {
        padding: 1rem 1.2rem;
        font-size: 1rem;
    }
    
    #receipts-tab .row.mb-4 > div {
        margin-bottom: 1rem;
    }
    
    .tab-btn {
        font-size: 1.1rem;
        padding: 0.75rem 1rem;
    }
    
    .tab-navigation {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
}

@media (max-width: 640px) {
    .modern-card {
        padding: 0.75rem;
        border-radius: 12px;
    }
    
    .stats-card {
        margin-bottom: 1rem;
    }
    
    .stats-card .flex {
        flex-direction: column;
        gap: 1rem;
    }
    
    .filter-section {
        padding: 1rem;
    }
    
    .class-selection-card {
        padding: 1rem;
    }
    
    h1.display-4 {
        font-size: 1.75rem;
    }
    
    h2.text-3xl {
        font-size: 1.5rem;
    }
    
    .tab-btn {
        font-size: 1rem;
        padding: 0.5rem 0.75rem;
    }
}

/* Custom scrollbar */
.custom-scrollbar::-webkit-scrollbar {
    width: 8px;
}

.custom-scrollbar::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 4px;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 4px;
}

.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

/* Focus states for accessibility */
.form-input-modern:focus,
.btn-modern:focus {
    outline: 2px solid #3b82f6;
    outline-offset: 2px;
}

/* Loading states */
.loading {
    opacity: 0.6;
    pointer-events: none;
}

.loading::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 20px;
    height: 20px;
    margin: -10px 0 0 -10px;
    border: 2px solid #f3f3f3;
    border-top: 2px solid #3b82f6;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

/* Additional Modern Enhancements */
.glass-effect {
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.hover-lift {
    transition: all 0.3s ease;
}

.hover-lift:hover {
    transform: translateY(-5px);
    box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.15);
}

.gradient-text {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.pulse-animation {
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.05); }
    100% { transform: scale(1); }
}

/* Enhanced button states */
.btn-modern:active {
    transform: translateY(0);
    box-shadow: 0 2px 4px 0 rgba(0, 0, 0, 0.1);
}

.btn-modern:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none !important;
}

/* Card hover effects */
.modern-card:hover .stats-icon {
    transform: scale(1.1);
    transition: transform 0.3s ease;
}

/* Smooth transitions for all interactive elements */
* {
    transition: all 0.3s ease;
}

/* Custom selection styles */
::selection {
    background: rgba(59, 130, 246, 0.2);
    color: #1e40af;
}

/* Enhanced focus states */
.form-input-modern:focus,
.btn-modern:focus,
select:focus {
    outline: 2px solid #3b82f6;
    outline-offset: 2px;
    box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
}

/* Improved table styling for data tables */
.dataTables_wrapper {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.dataTables_wrapper .dataTables_length,
.dataTables_wrapper .dataTables_filter,
.dataTables_wrapper .dataTables_info,
.dataTables_wrapper .dataTables_paginate {
    margin: 1rem 0;
}

.dataTables_wrapper .dataTables_paginate .paginate_button {
    padding: 0.5rem 1rem;
    margin: 0 0.25rem;
    border-radius: 8px;
    border: 1px solid #e5e7eb;
    background: white;
    color: #374151;
    transition: all 0.3s ease;
}

.dataTables_wrapper .dataTables_paginate .paginate_button:hover {
    background: #3b82f6;
    color: white;
    border-color: #3b82f6;
    transform: translateY(-2px);
}

.dataTables_wrapper .dataTables_paginate .paginate_button.current {
    background: #3b82f6;
    color: white;
    border-color: #3b82f6;
}

/* Smooth scrolling enhancement */
html {
    scroll-behavior: smooth;
}

/* Enhanced scroll animation */
.scroll-to-content {
    transition: all 0.3s ease;
}

.scroll-to-content:hover {
    transform: translateY(-2px);
}

/* Content area highlight when scrolling */
#table_holder {
    transition: all 0.3s ease;
}

#table_holder.scroll-highlight {
    box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.2);
    transform: scale(1.02);
}

/* Tab Navigation Styles */
.tab-navigation {
    display: flex;
    gap: 1rem;
    margin-bottom: 2rem;
    border-bottom: 2px solid #e5e7eb;
}

.tab-btn {
    padding: 1rem 2rem;
    background: transparent;
    border: none;
    border-bottom: 3px solid transparent;
    color: #6b7280;
    font-size: 1.5rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}

.tab-btn:hover {
    color: #3b82f6;
    background: rgba(59, 130, 246, 0.05);
}

.tab-btn.active {
    color: #3b82f6;
    border-bottom-color: #3b82f6;
}

.tab-content {
    display: none;
}

.tab-content.active {
    display: block;
}

/* Fix select2 height to match input fields */
#receipts-tab .select2-container .select2-selection--single {

    padding: 0.6rem 1rem !important;
}

#receipts-tab .select2-container--default .select2-selection--single .select2-selection__rendered {
    line-height: 2.6rem !important;
}


#receipts-tab .select2 a {
    margin-top: -11px !important;
}

</style>


<div class="invoices-container">
    <div class="container-fluid">
        <!-- Page Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="modern-card text-center">
                    <h1 class="display-4 font-bold text-gray-900 mb-3">
                        <i class="fa-solid fa-receipt text-blue-600 mr-3"></i>
                        Invoice Management
                    </h1>
                    <p class="text-lg text-gray-600">Search and manage student invoices with advanced filtering options</p>
                    </div>
                </div>
            </div>

        <!-- Search Cards Row -->
        <div class="row">
            <!-- Date Search Card -->
            <div class="col-lg-6 col-md-12 mb-4">
                <div class="modern-card search-card">
                    <div class="text-center mb-4">
                        <i class="fa-solid fa-calendar-days stats-icon"></i>
                        <h3 class="text-2xl font-bold mb-2">Search by Date</h3>
                        <p class="opacity-90">Filter invoices by specific date</p>
        </div>

                    <?php echo form_open(site_url('admin/get_receipts_bydate/search'), array('class' => 'form-horizontal', 'id' => 'receipt_bydate_form')); ?>
                    <div class="space-y-4">
                        <div>
                            <label for="date_sel" class="block text-sm font-semibold mb-2">Select Date</label>
                        </div>
                        
                        <div class="flex gap-3 items-end">
                            <div class="flex-1">
                                <div class="relative">
                                    <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
                                        <i class="fa-solid fa-calendar text-white opacity-70"></i>
                                    </div>
                                    <input id="date_sel" data-format="dd-mm-yyyy" value="<?php echo date('d-m-Y', strtotime($date_timestamp)); ?>" 
                                           placeholder="Select date" name="date_sel" type="text" 
                                           class="form-input-modern ps-10 datepicker" placeholder="Select date">
                                </div>
                            </div>
                            
                            <div class="flex-shrink-0">
                                <button type="submit" class="btn-modern btn-modern-primary">
                                    <i class="fa-solid fa-search"></i>
                                    Search
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php echo form_close(); ?>
                </div>
            </div>

            <!-- Term/Year Search Card -->
            <div class="col-lg-6 col-md-12 mb-4">
                <div class="modern-card search-card">
                    <div class="text-center mb-4">
                        <i class="fa-solid fa-graduation-cap stats-icon"></i>
                        <h3 class="text-2xl font-bold mb-2">Search by Term/Year</h3>
                        <p class="opacity-90">Filter invoices by academic term and year</p>
                    </div>
                    
                    <?php echo form_open(site_url('admin/get_receipts_byterm/search'), array('class' => 'form-horizontal', 'id' => 'receipts_byterm_form')); ?>
                    <div class="space-y-4">
                        <div class="flex gap-3 items-end">
                            <div class="flex-1">
                                <label for="term" class="block text-sm font-semibold mb-2">Term</label>
                                <select id="term" name="term" class="form-input-modern">
                                    <option value="" disabled="true"><?php echo get_phrase('term'); ?></option>
                                    <option value="0">All</option>
                                    <?php for ($i = 1; $i <= 3; $i++): ?>
                                        <option value="<?php echo $i; ?>" <?php if ($un_term == $i) echo 'selected'; ?>>
                                            <?php echo $i; ?>
                                        </option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            
                            <div class="flex-1">
                                <label for="year" class="block text-sm font-semibold mb-2">Year</label>
                                <select id="year" name="year" class="form-input-modern">
                                    <option value="" disabled="true"><?php echo get_phrase('year'); ?></option>
                                    <option value="0">All</option>
                                    <?php echo populate_academic_year($forYear, $un_year); ?>
                                </select>
                            </div>
                            
                            <div class="flex-shrink-0">
                                <label class="block text-sm font-semibold mb-2">&nbsp;</label>
                                <button type="submit" class="btn-modern btn-modern-primary">
                                    <i class="fa-solid fa-search"></i>
                                    Search
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php echo form_close(); ?>
                </div>
            </div>
        </div>

        <!-- Statistics Cards Row -->
            <div class="row">
            <!-- Date Statistics Card -->
            <div class="col-lg-6 col-md-12 mb-4">
                <a href="javascript:;" onclick="receipt_issued_bydate_modal()" id="date_rec_link" class="text-decoration-none">
                    <div class="modern-card stats-card blue">
                        <div class="stats-icon">
                            <i class="fa-solid fa-dollar-sign"></i>
                        </div>
                        <div class="stats-amount" id="date_rec">0</div>
                        <div class="stats-label">Total Amount</div>
                        <div class="flex justify-between items-center mt-4">
                            <div class="text-center">
                                <div class="text-2xl font-bold" id="date_rec_qty">0</div>
                                <div class="text-sm opacity-80">Quantity</div>
                            </div>
                            <div class="text-right">
                                <div class="text-lg font-semibold"><?php echo get_phrase('total_receipts_for_issued_invoices'); ?></div>
                                <div class="text-sm opacity-80" id="date_rec_date">Select a date</div>
                            </div>
                        </div>
                    </div>
                </a>
                    </div>

            <!-- Term Statistics Card -->
            <div class="col-lg-6 col-md-12 mb-4">
                <a href="javascript:;" onclick="receipt_issued_byterm_modal()" id="term_rec_link" class="text-decoration-none">
                    <div class="modern-card stats-card green">
                        <div class="stats-icon">
                            <i class="fa-solid fa-chart-line"></i>
                        </div>
                        <div class="stats-amount" id="term_rec">0</div>
                        <div class="stats-label">Total Amount</div>
                        <div class="flex justify-between items-center mt-4">
                            <div class="text-center">
                                <div class="text-2xl font-bold" id="term_rec_qty">0</div>
                                <div class="text-sm opacity-80">Quantity</div>
                            </div>
                            <div class="text-right">
                                <div class="text-lg font-semibold"><?php echo get_phrase('total_receipts_for_issued_invoices'); ?></div>
                                <div class="text-sm opacity-80" id="term_rec_date">Select term/year</div>
                            </div>
                </div>
            </div>
            </a>
        </div>
    </div>
    </div>
</div>



    <!-- Tab Navigation -->
    <div class="container-fluid">
        <div class="tab-navigation">
            <button class="tab-btn active text-2xl" data-tab="invoices-tab">
                <i class="fa-solid fa-file-invoice mr-2"></i>
                Invoices
            </button>
            <button class="tab-btn text-2xl" data-tab="receipts-tab">
                <i class="fa-solid fa-receipt mr-2"></i>
                All Receipts
            </button>
        </div>
    </div>

    <!-- Invoices Tab Content -->
    <div id="invoices-tab" class="tab-content active">
    <div class="container-fluid">
        <div class="class-selection-card">
            <div class="text-center mb-6">
                <i class="fa-solid fa-users stats-icon"></i>
                <h2 class="text-3xl font-bold mb-2">Class Selection</h2>
                <p class="text-lg opacity-90">Select a class to view and manage student invoices</p>
            </div>
            
            <?php echo form_open(site_url('admin/invoices_show/invoice_search'), array('class' => 'w-full', 'id' => 'invoices_reload_form')); ?>
            <div class="max-w-2xl mx-auto">
                <label for="class_selection" class="block text-lg font-semibold mb-4">Choose Class</label>
                <select id="class_selection" name="class_id" class="form-input-modern select2">
            <option value=""><?php echo get_phrase('select_class'); ?></option>
            <?php
                $teacher_id = '';
                getFullClassList($teacher_id, $_GET['id']);
            ?>
            </select>
        </div>
        <?php echo form_close(); ?>
            
            <div class="text-center mt-6">
                <button class="btn-modern btn-modern-success" style="display: none" id="return_to_class_list">
                    <i class="fa-solid fa-arrow-left"></i>
                    Return to Class List
                </button>
        </div>
    </div>

        <!-- Class Selection Status -->
        <div id="class_holder" class="text-center mb-6"></div>

        <!-- Content Area -->
        <div id="table_holder" class="modern-card">
            <div id="class_list" style="display: none;"></div>
            <div id="loader" style="display: none; position: relative; top: 0px;" class="text-center py-8">
                <div class="loading-spinner mx-auto mb-4"></div>
                <p class="text-lg text-gray-600">Loading data, please wait...</p>
            </div>
            <div id="per_invoice_loaded" style="display: none; position: relative; top: 0px;"></div>
        </div>
        
    <div><a href="#table_holder" id="anchor_a"></a></div>
    </div>
    </div>
    </div>

    <!-- All Receipts Tab Content -->
    <div id="receipts-tab" class="tab-content">
    <div class="container-fluid">
        <div class="modern-card">
            <div class="text-center mb-6">
                <i class="fa-solid fa-receipt stats-icon text-blue-600"></i>
                <h2 class="text-3xl font-bold mb-2">All Receipts</h2>
                <p class="text-lg opacity-90">Search and filter all payment receipts</p>
            </div>
            
            <!-- Filters -->
            <div class="row mb-4 items-center">
                <div class="col-md-2">
                    <label for="filter_start_date" class="block text-sm font-semibold mb-2">Start Date</label>
                    <input type="text" id="filter_start_date" class="form-input-modern datepicker" placeholder="Start date">
                </div>
                <div class="col-md-2">
                    <label for="filter_end_date" class="block text-sm font-semibold mb-2">End Date</label>
                    <input type="text" id="filter_end_date" class="form-input-modern datepicker" placeholder="End date">
                </div>
                <div class="col-md-2">
                    <label for="filter_receipt_code" class="block text-sm font-semibold mb-2">Receipt Code</label>
                    <input type="text" id="filter_receipt_code" class="form-input-modern" placeholder="Receipt code">
                </div>
                <div class="col-md-2">
                    <label for="filter_class" class="block text-sm font-semibold mb-2">Class</label>
                    <select id="filter_class" class="form-input-modern select2 border-none h-16 max-h-16">
                        <option value="">All Classes</option>
                        <?php
                            $teacher_id = '';
                            getFullClassList($teacher_id, '');
                        ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="filter_student" class="block text-sm font-semibold mb-2">Student</label>
                    <select id="filter_student" class="form-input-modern select2 border-none h-16 max-h-16" disabled>
                        <option value="">Select class first</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="block text-sm font-semibold mb-2">&nbsp;</label>
                    <button class="btn-modern btn-modern-primary" id="load_receipts_btn">
                        <i class="fa-solid fa-search"></i>
                        Load
                    </button>
                </div>
            </div>
        </div>

        <!-- Receipts Table Area -->
        <div id="receipts_table_holder" class="modern-card" style="display: none;">
            <div id="receipts_list"></div>
            <div id="receipts_loader" style="display: none;" class="text-center py-8">
                <div class="loading-spinner mx-auto mb-4"></div>
                <p class="text-lg text-gray-600">Loading receipts, please wait...</p>
            </div>
        </div>
    </div>
    </div>
</div>


<script type="text/javascript">
    $(function() {

        $('#tinvoices').DataTable();
        boxChecked();
        get_receipts_by_term();
        get_receipts_by_date();
        
        // Check if returning from payment edit
        if(sessionStorage.getItem('return_to_receipts') === 'true') {
            sessionStorage.removeItem('return_to_receipts');
            
            // Switch to receipts tab
            $('.tab-btn').removeClass('active');
            $('.tab-btn[data-tab="receipts-tab"]').addClass('active');
            $('.tab-content').removeClass('active');
            $('#receipts-tab').addClass('active');
            
            // Restore filters
            const filters = JSON.parse(sessionStorage.getItem('receipt_filters') || '{}');
            if(filters.start_date) $('#filter_start_date').val(filters.start_date);
            if(filters.end_date) $('#filter_end_date').val(filters.end_date);
            if(filters.receipt_code) $('#filter_receipt_code').val(filters.receipt_code);
            if(filters.class_id) {
                $('#filter_class').val(filters.class_id).trigger('change');
                if(filters.student_id) {
                    setTimeout(function() {
                        $('#filter_student').val(filters.student_id);
                    }, 500);
                }
            }
            
            // Auto-load receipts
            setTimeout(function() {
                loadAllReceipts();
                setTimeout(function() {
                    $('html, body').animate({
                        scrollTop: $('#receipts_table_holder').offset().top - 100
                    }, 800);
                }, 1000);
            }, 600);
        }
        
        // Tab navigation
        $('.tab-btn').click(function() {
            const tabId = $(this).data('tab');
            $('.tab-btn').removeClass('active');
            $(this).addClass('active');
            $('.tab-content').removeClass('active');
            $('#' + tabId).addClass('active');
        });
        
        // Load receipts button
        $('#load_receipts_btn').click(function() {
            loadAllReceipts();
        });
        
        // Load students when class is selected
        $('#filter_class').change(function() {
            const class_id = $(this).val();
            if(class_id) {
                loadStudentsByClass(class_id);
            } else {
                $('#filter_student').html('<option value="">Select a class first</option>').prop('disabled', true);
            }
        });

        const isset = '<?php echo isset($_GET['id']); ?>';

        if(isset) {
            $('#class_selection').change();

            setTimeout(() => {
               $('html, body').animate({
                  scrollTop: ($('#table_holder').height())
                }, 1000);
             }, 1000)
        }

    });

    $('#reload_btn').click(function(event) {
        /* Act on the event */
        $('#reload_btn').val('Loading, please wait...');
        $('#reload_btn').removeClass('btn-success');
        $('#reload_btn').addClass('btn-danger');
    });

    ///Search by date form submitted
    $('#receipt_bydate_form').submit(function(event) {
        /* Act on the event */
        event.preventDefault();
        get_receipts_by_date();

    });

    ///Search by date form submitted
    $('#receipts_byterm_form').submit(function(event) {
        /* Act on the event */
        event.preventDefault();

        get_receipts_by_term();
        loadInvoices(); //load invoice list

    });

    function get_receipts_by_term() {
        let term = $('#term').val();
        //let sem = $('#sem').val();
        let year = $('#year').val();

        $.ajax({
            url: '<?php echo site_url('admin/get_receipt_byterm/search/'); ?>' + year + '/' + term,
            type: 'POST',
            dataType: 'json',
            success: function(response) {
                $('#term_rec').text(response[0].total_amount);
                $('#term_rec_date').text('Received In: ' + response[0].duration);
                $('#term_rec_qty').text(response[0].total_number);

                if(response[0].total_amount == 'No Data') {
                    $('#term_rec_link').removeAttr('onclick');
                        /* Act on the event */
                        $('#term_rec').css('color', '#f3d60d');
                } else {
                    $('#term_rec_link').attr('onclick', 'receipt_issued_byterm_modal()');
                        /* Act on the event */
                        $('#term_rec').css('color', '#ffffff');
                }
            }
        });
    }

    //call the get_receipts
    function get_receipts_by_date() {
        let selected_date = $('#date_sel').val();


        $.ajax({
            url: '<?php echo site_url('admin/get_receipt_bydate/search/'); ?>' + selected_date,
            type: 'POST',
            dataType: 'json',
            success: function(response) {
                $('#date_rec').text(response[0].total_amount);
                $('#date_rec_date').text('Received On: ' + response[0].date_chosen);
                $('#date_rec_qty').text(response[0].total_number);

                if(response[0].total_amount == 'No Data') {
                    $('#date_rec_link').removeAttr('onclick');
                        /* Act on the event */
                        $('#date_rec').css('color', '#f3d60d');
                } else {
                    $('#date_rec_link').attr('onclick', 'receipt_issued_bydate_modal()');
                        /* Act on the event */
                        $('#date_rec').css('color', '#ffffff');
                }
            }
        });
    }

     function receipt_issued_bydate_modal() {
        let selected_date = $('#date_sel').val();

        showAjaxModal('<?php echo site_url('modal/popup/receipts_issued_bydate/'); ?>' + selected_date, 'take_payment');

    }

    function receipt_issued_byterm_modal() {
        let term = $('#term').val();
        //let sem = $('#sem').val();
        let year = $('#year').val();

        showAjaxModal('<?php echo site_url('modal/popup/receipts_issued_byterm/'); ?>' + year + '/' + term, 'take_payment');
    }


    $('#class_selection').change(function(event) {
        /* Act on the event */
        loadInvoices();
    });

    function scrollToContentArea() {
        if ($('#table_holder').length) {
            $('html, body').animate({
                scrollTop: $('#table_holder').offset().top - 100
            }, 800);
        }
    }

    function loadInvoices() {

        //event.preventDefault();
        $('#anchor_a').click();

        let year = $('#year').val();
        let term = $('#term').val();
        // let sem = $('#sem').val();

        let class_id = $('#class_selection').val();
        let class_selected = $('#class_selection').children('option:selected').text();

        if(class_selected == '') {

            class_selected = 'No class selected';
        }

        $('#class_holder').html(`
            <div class="modern-card text-center">
                <div class="flex items-center justify-center mb-4">
                    <i class="fa-solid fa-check-circle text-green-500 text-2xl mr-3"></i>
                    <h3 class="text-2xl font-bold text-gray-900">Class Selected: ${class_selected}</h3>
                </div>
                <div class="flex flex-wrap gap-4 justify-center">
                    <button class="btn-modern btn-modern-info" id="bulk_invoice">
                        <i class="fa-solid fa-file-invoice"></i>
                        View Bulk Invoice
                    </button>
                    <button class="btn-modern btn-modern-danger" id="bulk_bill">
                        <i class="fa-solid fa-receipt"></i>
                        View Bulk Bill
                    </button>
                </div>
            </div>
        `);

        $.ajax({
        url: '<?php echo site_url('admin/invoices_show/invoice_search/'); ?>' + class_id + '/' + year + '/' + term,
        //cache: false,
        beforeSend: function() {
            $('#table_holder #loader').fadeIn('slow');
            $('#return_to_class_list').fadeOut('slow');
            $('#table_holder #per_invoice_loaded').fadeOut('slow');
            $('#table_holder #class_list').fadeOut('slow');
            $('#table_holder #loader').html(`
                <div class="text-center py-8">
                    <div class="loading-spinner mx-auto mb-4"></div>
                    <p class="text-lg text-gray-600">Loading data, please wait...</p>
                </div>
            `);

            setTimeout(() => {
               $('#table_holder #loader').html(`
                   <div class="text-center py-8">
                       <div class="loading-spinner mx-auto mb-4"></div>
                       <p class="text-lg text-gray-600">Thanks for your patience. I'm almost done.</p>
                   </div>
               `);
            }, 30000)
        },
        success: function(response) {
            $('#table_holder #loader').fadeOut('slow');
            $('#table_holder #per_invoice_loaded').fadeOut('slow');
            $('#table_holder #class_list').fadeIn('slow');
            $('#table_holder #class_list').html(response);
            
            // Initialize DataTable and then scroll
            $('.datatable').DataTable({
                "initComplete": function(settings, json) {
                    scrollToContentArea();
                }
            });
            
            // Fallback scroll in case DataTable doesn't initialize
            setTimeout(() => {
                scrollToContentArea();
            }, 1000);
        }

      });
    }


    //call class changed function
    function class_selected_load(class_id) {
        $('#anchor_a').click();

        let year = $('#year').val();
        let term = $('#term').val();
        //let sem = $('#sem').val();

        $('#class_selection').val(class_id).change();
        let class_selected = $('#class_selection').children('option:selected').text();

        if(class_selected == '') {

            class_selected = 'No class selected';
        }
        $('#class_holder').html(`
            <div class="modern-card text-center">
                <div class="flex items-center justify-center mb-4">
                    <i class="fa-solid fa-check-circle text-green-500 text-2xl mr-3"></i>
                    <h3 class="text-2xl font-bold text-gray-900">Class Selected: ${class_selected}</h3>
                </div>
                <div class="flex flex-wrap gap-4 justify-center">
                    <button class="btn-modern btn-modern-info" id="bulk_invoice">
                        <i class="fa-solid fa-file-invoice"></i>
                        View Bulk Invoice
                    </button>
                    <button class="btn-modern btn-modern-danger" id="bulk_bill">
                        <i class="fa-solid fa-receipt"></i>
                        View Bulk Bill
                    </button>
                </div>
            </div>
        `);

        $.ajax({
        url: '<?php echo site_url('admin/invoices_show/invoice_search/'); ?>' + class_id + '/' + year + '/' + term,
        //cache: false,
        beforeSend: function() {
            $('#table_holder #loader').fadeIn('slow');
            $('#return_to_class_list').fadeOut('slow');
            $('#table_holder #per_invoice_loaded').fadeOut('slow');
            $('#table_holder #class_list').fadeOut('slow');
            $('#table_holder #loader').html(`
                <div class="text-center py-8">
                    <div class="loading-spinner mx-auto mb-4"></div>
                    <p class="text-lg text-gray-600">Loading data, please wait...</p>
                </div>
            `);

            setTimeout(() => {
               $('#table_holder #loader').html(`
                   <div class="text-center py-8">
                       <div class="loading-spinner mx-auto mb-4"></div>
                       <p class="text-lg text-gray-600">Thanks for your patience. I'm almost done.</p>
                   </div>
               `);
            }, 30000)
        },
        success: function(response) {
            $('#table_holder #loader').fadeOut('slow');
            $('#table_holder #per_invoice_loaded').fadeOut('slow');
            $('#table_holder #class_list').fadeIn('slow');
            $('#table_holder #class_list').html(response);
            $('.datatable').DataTable();
        }

      });
    }


    function invoice_load(student_id) {
        let class_id = $('#class_selection').val();

        $.ajax({
        url: '<?php echo site_url('admin/invoices_show/invoice_load/'); ?>' + student_id,
        cache: false,
        beforeSend: function() {
            $('#table_holder #class_list').fadeOut('slow');
            $('#table_holder #loader').fadeIn('slow');
            $('#table_holder #loader').html(`
                <div class="text-center py-8">
                    <div class="loading-spinner mx-auto mb-4"></div>
                    <p class="text-lg text-gray-600">Loading invoice details, please wait...</p>
                </div>
            `);

            setTimeout(() => {
               $('#table_holder #loader').html(`
                   <div class="text-center py-8">
                       <div class="loading-spinner mx-auto mb-4"></div>
                       <p class="text-lg text-gray-600">Thanks for your patience. I'm almost done.</p>
                   </div>
               `);
            }, 5000)
        },
        success: function(response) {
            $('#table_holder #loader').fadeOut('slow');
            $('#table_holder #per_invoice_loaded').fadeIn('slow');
            $('#table_holder #per_invoice_loaded').html(response);
            $('#return_to_class_list').fadeIn('slow');


            //change load button text and color
            $('#reload_btn').val('Reload Invoices');
            $('#reload_btn').removeClass('btn-danger');
            $('#reload_btn').addClass('btn-success');
        }

      });
    }

    //toggle return to class list button
    $('#return_to_class_list').click(function(event) {
        /* Act on the event */
        /*$('html, body').animate({
          scrollTop: ($('#table_holder').offset().top )
        }, 1000);*/

        $('#per_invoice_loaded').fadeOut('slow');
        $('#class_list').fadeIn('slow');
        $('#return_to_class_list').fadeOut('slow');

    });

    $('button #take_payment').click(function(event) {
        /* Act on the event */
        $('#per_invoice_loaded').fadeOut('slow');
        $('#class_list').fadeIn('slow');
        $('#return_to_class_list').fadeOut('slow');

    });

     function invoice_pay_modal(student_id, date = '', term = '') {

       /** invoice_code = invoice_code.toString();
        let invoice_original_len = '<?php echo $inv_number_len; ?>';
        let current_invoice_len = invoice_code.length;

        if(invoice_code.substring(0, 1) == '_') {
            invoice_code = invoice_code.substring(1);
        } else {
            invoice_code = invoice_code;
        } **/
        if(date != '' && term == '') {
            showAjaxModal('<?php echo site_url('modal/popup/modal_take_payment/'); ?>' + student_id + '/' + date, 'take_payment');
        } else if(date != '' && term != '') {
            showAjaxModal('<?php echo site_url('modal/popup/modal_take_payment/'); ?>' + student_id + '/' + date + '/' + term, 'take_payment');
        }  else {
            showAjaxModal('<?php echo site_url('modal/popup/modal_take_payment/'); ?>' + student_id, 'take_payment');
        }
    }

    function invoice_pay_modal(student_id, date = '', term = '') {

       /** invoice_code = invoice_code.toString();
        let invoice_original_len = '<?php echo $inv_number_len; ?>';
        let current_invoice_len = invoice_code.length;

        if(invoice_code.substring(0, 1) == '_') {
            invoice_code = invoice_code.substring(1);
        } else {
            invoice_code = invoice_code;
        } **/
        if(date != '' && term == '') {
            showAjaxModal('<?php echo site_url('modal/popup/modal_take_payment/'); ?>' + student_id + '/' + date, 'take_payment');
        } else if(date != '' && term != '') {
            showAjaxModal('<?php echo site_url('modal/popup/modal_take_payment/'); ?>' + student_id + '/' + date + '/' + term, 'take_payment');
        }  else {
            showAjaxModal('<?php echo site_url('modal/popup/modal_take_payment/'); ?>' + student_id, 'take_payment');
        }
    }

    function view_receipts_modal(student_id, date = '', term = '') {

       /** invoice_code = invoice_code.toString();
        let invoice_original_len = '<?php echo $inv_number_len; ?>';
        let current_invoice_len = invoice_code.length;

        if(invoice_code.substring(0, 1) == '_') {
            invoice_code = invoice_code.substring(1);
        } else {
            invoice_code = invoice_code;
        } **/
        if(date != '' && term == '') {
            showAjaxModal('<?php echo site_url('modal/popup/modal_view_receipts/'); ?>' + student_id + '/' + date, 'take_payment');
        } else if(date != '' && term != '') {
            showAjaxModal('<?php echo site_url('modal/popup/modal_view_receipts/'); ?>' + student_id + '/' + date + '/' + term, 'take_payment');
        }  else {
            showAjaxModal('<?php echo site_url('modal/popup/modal_view_receipts/'); ?>' + student_id, 'take_payment');
        }
    }



    function invoice_view_modal(invoice_code) {

        invoice_code = invoice_code.toString();
        let invoice_original_len = '<?php echo $inv_number_len; ?>';
        let current_invoice_len = invoice_code.length;

        if(invoice_code.substring(0, 1) == '_') {
            invoice_code = invoice_code.substring(1);
        } else {
            invoice_code = invoice_code;
        }

        showAjaxModal('<?php echo site_url('modal/popup_professional/modal_view_invoice_professional/'); ?>' + invoice_code, 'large');

    }



    function bulk_invoice_view_modal(student_id) {

        showAjaxModal('<?php echo site_url('modal/popup/modal_view_bulk_invoice/'); ?>' + student_id, 'take_payment');

    }

    //general payment
    function general_payament_modal(class_id) {

        showAjaxModal('<?php echo site_url('modal/popup/modal_take_general_payment/'); ?>' + class_id, 'take_payment');

    }

    function invoice_edit_modal(invoice_code) {
        showAjaxModal_invoice('<?php echo site_url('modal/popup/modal_edit_invoice/'); ?>' + invoice_code);
    }

    function invoice_delete_confirm(invoice_code) {
        let admin_level = <?php echo $admin_level; ?>;

        invoice_code = invoice_code.toString();
        let invoice_original_len = '<?php echo $inv_number_len; ?>';
        let current_invoice_len = invoice_code.length;

        /*if(invoice_code.substring(0, 1) == '_') {
            invoice_code = invoice_code.substring(1);
        } else {*/
           // invoice_code = invoice_code;
        //}

        if(admin_level == 1) {
            confirm_modal('<?php echo site_url('admin/invoice/delete/'); ?>' + invoice_code, 'modal_invoice_delete', 'checkboxes_form');
        } else {
           confirm_modal('<?php echo site_url('admin/invoice/delete/'); ?>' + invoice_code, 'modal_delete_warning');
            return false;
        }
    }

   

    //is any box checked
    function boxChecked(approvedCounter=0, firstLoad=false) {
        console.log(approvedCounter);

        if(approvedCounter > 0 && !firstLoad) {
            showAjaxModal_alert('Please proceed to process the approved requests before selecting new ones.', 'Error');
            return;
        }
        let checkboxes = $('#checkboxes_form td input[type="checkbox"]');
        let count_checked_buttons = checkboxes.filter(':checked').length;


        if(count_checked_buttons < 1) {
            $('#tfooter').css('display', 'none');
        } else {
            $('#tfooter').removeAttr('style');
        }
    }



     function allStudentsBoxChecked() {
        let checkboxes = $('#all_students_invoices td input[type="checkbox"]');
        let count_checked_buttons = checkboxes.filter(':checked').length;

        if(count_checked_buttons < 1) {
            $('#stfooter').css('display', 'none');
        } else {
            $('#stfooter').removeAttr('style');
        }
    }

    function loadStudentsByClass(class_id) {
        $.ajax({
            url: '<?php echo site_url('admin/get_students_by_class/'); ?>' + class_id,
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                let options = '<option value="">All Students</option>';
                response.forEach(function(student) {
                    options += '<option value="' + student.student_id + '">' + student.name + '</option>';
                });
                $('#filter_student').html(options).prop('disabled', false);
            }
        });
    }

    function loadAllReceipts() {
        let start_date = $('#filter_start_date').val();
        let end_date = $('#filter_end_date').val();
        let receipt_code = $('#filter_receipt_code').val();
        let class_id = $('#filter_class').val();
        let student_id = $('#filter_student').val();

        $.ajax({
            url: '<?php echo site_url('admin/get_all_receipts/'); ?>',
            type: 'POST',
            data: {
                start_date: start_date,
                end_date: end_date,
                receipt_code: receipt_code,
                class_id: class_id,
                student_id: student_id
            },
            beforeSend: function() {
                $('#receipts_table_holder').show();
                $('#receipts_loader').show();
                $('#receipts_list').hide();
            },
            success: function(response) {
                $('#receipts_loader').hide();
                $('#receipts_list').show();
                $('#receipts_list').html(response);
                $('#receipts_list .datatable').DataTable();
                setTimeout(function() {
                    $('html, body').animate({
                        scrollTop: $('#receipts_table_holder').offset().top - 100
                    }, 800);
                }, 300);
            },
            error: function() {
                $('#receipts_loader').hide();
                $('#receipts_list').show();
                $('#receipts_list').html('<div class="alert alert-danger">Error loading receipts. Please try again.</div>');
            }
        });
    }

    function saveFiltersAndEdit(receipt_code) {
        const filters = {
            start_date: $('#filter_start_date').val(),
            end_date: $('#filter_end_date').val(),
            receipt_code: $('#filter_receipt_code').val(),
            class_id: $('#filter_class').val(),
            student_id: $('#filter_student').val()
        };
        sessionStorage.setItem('receipt_filters', JSON.stringify(filters));
        showAjaxModal('<?php echo site_url('modal/popup/modal_take_payment_edit/'); ?>/' + receipt_code, 'large');
    }

</script>




