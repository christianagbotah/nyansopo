<script src="<?php echo base_url(); ?>assets/cdn/js/tailwindcss.js"></script>

<style>
    /* Enhanced readability and responsive layout */
    .study-materials-admin-container {
        width: 100%;
        max-width: 100%;
        margin: 0 auto;
        padding: 0 1rem;
    }
    
    /* Better table readability */
    #table-2 {
        font-size: 1rem;
        line-height: 1.7;
        width: 100% !important;
    }
    
    #table-2 thead th {
        font-weight: 600;
        letter-spacing: 0.05em;
        padding: 1rem;
        white-space: nowrap;
        font-size: 0.875rem;
    }
    
    #table-2 tbody td {
        padding: 1.25rem 1rem;
        vertical-align: middle;
        font-size: 1rem;
    }
    
    /* Responsive table wrapper */
    .table-responsive-wrapper {
        width: 100%;
        overflow-x: auto;
    }
    
    /* DataTable wrapper adjustments */
    .dataTables_wrapper {
        width: 100% !important;
        padding: 1.5rem;
    }
    
    .dataTables_wrapper .dataTables_length,
    .dataTables_wrapper .dataTables_filter {
        margin-bottom: 1rem;
    }
    
    .dataTables_wrapper .dataTables_length select,
    .dataTables_wrapper .dataTables_filter input {
        padding: 0.625rem 0.875rem !important;
        border: 2px solid #e5e7eb !important;
        border-radius: 0.5rem !important;
        font-size: 0.875rem !important;
        height: 42px !important;
        background: white !important;
        transition: all 0.2s !important;
    }
    
    .dataTables_wrapper .dataTables_length select:focus,
    .dataTables_wrapper .dataTables_filter input:focus {
        border-color: #3b82f6 !important;
        outline: none !important;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1) !important;
    }
    
    .dataTables_wrapper .dataTables_length label,
    .dataTables_wrapper .dataTables_filter label {
        display: flex !important;
        align-items: center !important;
        gap: 0.5rem !important;
        font-weight: 500 !important;
        color: #374151 !important;
    }
    
    /* Consistent input heights */
    .filter-input,
    .filter-select {
        height: 42px !important;
        padding: 0.625rem 0.875rem !important;
        border: 2px solid #e5e7eb !important;
        border-radius: 0.5rem !important;
        font-size: 1.0625rem !important;
        transition: all 0.2s !important;
    }
    
    .filter-input:focus,
    .filter-select:focus {
        border-color: #3b82f6 !important;
        outline: none !important;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1) !important;
    }
    
    /* Filter labels */
    .filter-input + label,
    .filter-select + label,
    label {
        font-size: 1rem !important;
        font-weight: 500 !important;
    }
    
    /* Bulk actions bar */
    .bulk-actions-bar {
        position: fixed;
        bottom: 20px;
        left: 50%;
        transform: translateX(-50%) translateY(150%);
        background: white;
        padding: 1.5rem 2.5rem;
        border-radius: 16px;
        box-shadow: 0 -6px 30px rgba(0,0,0,0.2), 0 4px 20px rgba(0,0,0,0.15);
        display: none;
        gap: 1.25rem;
        align-items: center;
        z-index: 999999;
        transition: transform 0.3s ease-in-out, opacity 0.3s ease-in-out;
        min-width: 700px;
        border: 3px solid #e5e7eb;
        opacity: 0;
    }
    
    .bulk-actions-bar.show {
        display: flex;
        transform: translateX(-50%) translateY(0);
        opacity: 1;
    }
    
    .bulk-checkbox {
        width: 20px;
        height: 20px;
        cursor: pointer;
        accent-color: #3b82f6;
    }
    
    @media (max-width: 768px) {
        .bulk-actions-bar {
            min-width: 95%;
            flex-wrap: wrap;
            justify-content: center;
            padding: 1.25rem 1rem;
            gap: 0.75rem;
            bottom: 10px;
        }
    }
</style>

<div class="study-materials-admin-container">
    <div class="bg-gradient-to-br from-white to-gray-50 rounded-xl shadow-lg p-8 mb-6">
        <div class="flex flex-col md:flex-row md:justify-between md:items-center gap-4">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 mb-2 flex items-center gap-3">
                    <span class="bg-blue-100 text-blue-600 p-3 rounded-lg">
                        <i class="entypo-book-open text-2xl"></i>
                    </span>
                    <?php echo get_phrase('study_materials');?>
                </h1>
                <p class="text-gray-600 text-lg"><?php echo get_phrase('review_and_approve_study_materials');?></p>
            </div>
        </div>
    </div>

    <!-- Filters Section -->
    <div class="bg-white rounded-xl shadow-lg p-6 mb-6">
        <div class="flex items-center gap-3 mb-4">
            <i class="entypo-filter text-2xl text-blue-600"></i>
            <h2 class="text-xl font-bold text-gray-900">Filters</h2>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Class Filter -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2" style="font-size: 1rem !important;">
                    <i class="entypo-users text-gray-500"></i> Class
                </label>
                <select id="filter_class" class="filter-select w-full bg-white text-gray-900">
                    <option value="">All Classes</option>
                    <?php
                    $classes = $this->db->get('class')->result_array();
                    foreach($classes as $class):
                        $section = $this->db->get_where('section', array('class_id' => $class['class_id']))->row();
                        $section_name = $section ? ' - ' . $section->name : '';
                    ?>
                        <option value="<?php echo $class['class_id']; ?>">
                            <?php echo $class['name'].' '.$class['name_numeric'].$section_name; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Teacher Filter -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2" style="font-size: 1rem !important;">
                    <i class="entypo-user text-gray-500"></i> Teacher
                </label>
                <select id="filter_teacher" class="filter-select w-full bg-white text-gray-900">
                    <option value="">All Teachers</option>
                    <?php
                    $teachers = $this->db->get('teacher')->result_array();
                    foreach($teachers as $teacher):
                    ?>
                        <option value="<?php echo $teacher['teacher_id']; ?>">
                            <?php echo $teacher['name']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Start Date Filter -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2" style="font-size: 1rem !important;">
                    <i class="entypo-calendar text-gray-500"></i> Start Date From
                </label>
                <input type="date" id="filter_start_date" class="filter-input w-full bg-white text-gray-900">
            </div>

            <!-- End Date Filter -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2" style="font-size: 1rem !important;">
                    <i class="entypo-calendar text-gray-500"></i> End Date To
                </label>
                <input type="date" id="filter_end_date" class="filter-input w-full bg-white text-gray-900">
            </div>
        </div>

        <div class="flex gap-3 mt-4">
            <button onclick="applyFilters()" class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-semibold shadow-sm transition" style="font-size: 1rem !important;">
                <i class="entypo-search"></i>
                <span>Apply Filters</span>
            </button>
            <button onclick="clearFilters()" class="inline-flex items-center gap-2 bg-gray-200 hover:bg-gray-300 text-gray-700 px-6 py-3 rounded-lg font-semibold transition" style="font-size: 1rem !important;">
                <i class="entypo-ccw"></i>
                <span>Clear Filters</span>
            </button>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
        <div class="table-responsive-wrapper" id="table_holder">
            <div class="flex items-center justify-center p-12">
                <p class="text-lg text-gray-600">Loading data, please wait... <i class="fa-solid fa-spinner fa-pulse ml-2"></i></p>
            </div>
        </div>
    </div>
</div>

<!-- Bulk Actions Bar -->
<div class="bulk-actions-bar" id="bulk-actions-bar">
    <span class="font-bold text-gray-800" id="selected-count" style="font-size: 1.125rem;">0 selected</span>
    <button onclick="bulkApprove()" class="inline-flex items-center gap-2 bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg font-bold transition shadow-md hover:shadow-lg" style="font-size: 1.0625rem !important;">
        <i class="entypo-check" style="font-size: 1.25rem;"></i>
        <span>Approve Selected</span>
    </button>
    <button onclick="bulkDecline()" class="inline-flex items-center gap-2 bg-red-600 hover:bg-red-700 text-white px-6 py-3 rounded-lg font-bold transition shadow-md hover:shadow-lg" style="font-size: 1.0625rem !important;">
        <i class="entypo-cancel" style="font-size: 1.25rem;"></i>
        <span>Decline Selected</span>
    </button>
    <button onclick="bulkPending()" class="inline-flex items-center gap-2 bg-yellow-600 hover:bg-yellow-700 text-white px-6 py-3 rounded-lg font-bold transition shadow-md hover:shadow-lg" style="font-size: 1.0625rem !important;">
        <i class="entypo-clock" style="font-size: 1.25rem;"></i>
        <span>Mark Pending</span>
    </button>
    <button onclick="clearSelection()" class="inline-flex items-center gap-2 bg-gray-300 hover:bg-gray-400 text-gray-800 px-6 py-3 rounded-lg font-bold transition shadow-md hover:shadow-lg" style="font-size: 1.0625rem !important;">
        <i class="entypo-cancel-circled" style="font-size: 1.25rem;"></i>
        <span>Clear</span>
    </button>
</div>

<script type="text/javascript">

    $(function(ev) {
        loadTable(); /*load automatically*/
    })
    
    function loadTable(filters = {}) {
        $.ajax({
            url: '<?= site_url('admin/study_material/load');?>',
            type: 'post',
            dataType: 'html',
            cache: false,
            data: filters
        })
        .done(function(response) {
            $('#table_holder').html(response);
        })
        .fail(function(err) {
            $('#table_holder').html('<div class="p-12 text-center"><p class="text-red-600 text-lg">Could not load table, please try again!</p></div>');
        })
    }

    function applyFilters() {
        var filters = {
            class_id: $('#filter_class').val(),
            teacher_id: $('#filter_teacher').val(),
            start_date: $('#filter_start_date').val(),
            end_date: $('#filter_end_date').val()
        };
        loadTable(filters);
    }

    function clearFilters() {
        $('#filter_class').val('');
        $('#filter_teacher').val('');
        $('#filter_start_date').val('');
        $('#filter_end_date').val('');
        loadTable();
    }

    function updateStatus(status, id) {
        if(status == '') return;

        $.ajax({
            url: '<?= site_url('admin/study_material/update_status');?>',
            type: 'post',
            dataType: 'text',
            cache: false,
            data: {id: id, status: status},
        })
        .done(function(response) {
            $('#table_holder').html('<div class="flex items-center justify-center p-12"><p class="text-lg text-gray-600">Loading data, please wait... <i class="fa-solid fa-spinner fa-pulse ml-2"></i></p></div>');
            // Reapply current filters
            applyFilters();
        })
        .fail(function(err) {
           alert(err.responseText);
        })
    }
    
    // Bulk actions functions
    function updateBulkActions() {
        const selectedCheckboxes = $('.material-checkbox:checked');
        const count = selectedCheckboxes.length;
        
        $('#selected-count').text(count + ' selected');
        
        if (count > 0) {
            $('#bulk-actions-bar').addClass('show');
        } else {
            $('#bulk-actions-bar').removeClass('show');
        }
    }
    
    function getSelectedIds() {
        const ids = [];
        $('.material-checkbox:checked').each(function() {
            ids.push($(this).data('id'));
        });
        return ids;
    }
    
    function bulkApprove() {
        const ids = getSelectedIds();
        if (ids.length === 0) return;
        
        showConfirmModal(
            'Approve Materials',
            `Are you sure you want to approve ${ids.length} selected material(s)?`,
            function() {
                bulkUpdateStatus(ids, 'Approved');
            },
            'Approve',
            'success'
        );
    }
    
    function bulkDecline() {
        const ids = getSelectedIds();
        if (ids.length === 0) return;
        
        showConfirmModal(
            'Decline Materials',
            `Are you sure you want to decline ${ids.length} selected material(s)?`,
            function() {
                bulkUpdateStatus(ids, 'Declined');
            },
            'Decline',
            'danger'
        );
    }
    
    function bulkPending() {
        const ids = getSelectedIds();
        if (ids.length === 0) return;
        
        showConfirmModal(
            'Mark as Pending',
            `Are you sure you want to mark ${ids.length} selected material(s) as pending?`,
            function() {
                bulkUpdateStatus(ids, 'Pending');
            },
            'Mark Pending',
            'warning'
        );
    }
    
    function bulkUpdateStatus(ids, status) {
        $.ajax({
            url: '<?= site_url('admin/study_material/bulk_update_status');?>',
            type: 'post',
            dataType: 'json',
            data: {ids: ids, status: status},
        })
        .done(function(response) {
            if (response.status === 'success') {
                showAjaxModal_alert(response.message || 'Status updated successfully', 'Success', false);
                clearSelection();
                applyFilters();
            } else {
                showAjaxModal_alert(response.message || 'Failed to update status', 'Error', true);
            }
        })
        .fail(function(err) {
            showAjaxModal_alert('Error updating status: ' + (err.responseText || 'Unknown error'), 'Error', true);
        })
    }
    
    function clearSelection() {
        $('.material-checkbox').prop('checked', false);
        $('#select-all').prop('checked', false);
        updateBulkActions();
    }
    
    function toggleSelectAll(checked) {
        $('.material-checkbox').prop('checked', checked);
        updateBulkActions();
    }
</script>