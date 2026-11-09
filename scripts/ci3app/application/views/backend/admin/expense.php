<style>
.expense-container { max-width:1400px; margin:0 auto; padding:20px; background:#fef2f2; }
.page-header { background:#fff; border-left:4px solid #dc2626; border-radius:8px; padding:24px; margin-bottom:30px; box-shadow:0 2px 8px rgba(220,38,38,0.1); }
.page-title { font-size:28px; font-weight:700; color:#dc2626; margin:0; display:flex; align-items:center; gap:12px; }
.page-subtitle { font-size:14px; color:#6b7280; margin-top:8px; }
/* For 14" laptops and smaller screens - hide table button text when sidebar is expanded */
@media (max-width: 1536px) {
    .page-container:not(.sidebar-collapsed) #expenses tbody td .btn-text { display: none; }
    .page-container:not(.sidebar-collapsed) #expenses tbody td .btn-sm { padding: 0 10px; min-width: 36px; }
}
@media (max-width: 768px) {
    .page-header { flex-direction: column !important; align-items: flex-start !important; gap: 20px; }
    .page-header > div:last-child { width: 100%; text-align: left !important; }
    .filter-row { flex-wrap: wrap !important; }
    .form-group { min-width: 100% !important; }
    /* Hide button text in table actions, show icons only on mobile */
    #expenses tbody td .btn-text { display: none; }
    #expenses tbody td .btn-sm { padding: 0 10px; min-width: 36px; }
}
.filter-card { background:#fff; border:1px solid #fecaca; border-radius:8px; padding:24px; margin-bottom:24px; box-shadow:0 2px 8px rgba(220,38,38,0.08); }
.filter-row { display:flex; gap:12px; align-items:flex-end; flex-wrap:wrap; }
.form-group { flex:1 1 auto; min-width:150px; }
/* When sidebar is collapsed on 14" screens, keep all filters in one row */
@media (min-width: 1200px) and (max-width: 1536px) {
    .page-container.sidebar-collapsed .filter-row { flex-wrap: nowrap; }
    .page-container.sidebar-collapsed .form-group { min-width: 140px; }
}
/* Large screens always single row */
@media (min-width: 1537px) {
    .filter-row { flex-wrap: nowrap; }
}
/* Medium screens when sidebar expanded */
@media (max-width: 1536px) {
    .form-group { min-width: 160px; }
}
@media (max-width: 991px) {
    .form-group { min-width: 200px; flex: 1 1 45%; }
}
.form-label { display:block; font-size:12px; font-weight:600; color:#991b1b; margin-bottom:6px; text-transform:uppercase; letter-spacing:0.5px; }
.form-input { width:100%; height:40px; padding:0 12px; border:1px solid #fecaca; border-radius:6px; font-size:14px; transition:all 0.2s; background:#fff; }
.form-input:focus { border-color:#dc2626; outline:none; box-shadow:0 0 0 3px rgba(220,38,38,0.1); }
.btn { height:40px; padding:0 20px; border:none; border-radius:6px; font-size:14px; font-weight:600; cursor:pointer; transition:all 0.2s; display:inline-flex; align-items:center; gap:8px; text-decoration:none; }
.btn-primary { background:#dc2626; color:#fff; }
.btn-primary:hover { background:#b91c1c; transform:translateY(-1px); box-shadow:0 4px 12px rgba(220,38,38,0.3); color:#fff; }
.btn-secondary { background:#3b82f6; color:#fff; }
.btn-secondary:hover { background:#2563eb; transform:translateY(-1px); box-shadow:0 4px 12px rgba(59,130,246,0.3); color:#fff; }
.btn-success { background:#10b981; color:#fff; }
.btn-success:hover { background:#059669; transform:translateY(-1px); box-shadow:0 4px 12px rgba(16,185,129,0.3); color:#fff; }
.btn-sm { height:32px; padding:0 12px; font-size:13px; }
.btn-danger { background:#dc2626; color:#fff; }
.btn-danger:hover { background:#b91c1c; transform:translateY(-1px); box-shadow:0 4px 12px rgba(220,38,38,0.3); color:#fff; }
.stats-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(280px, 1fr)); gap:20px; margin-bottom:24px; }
.stat-card { background:#fff; border:1px solid #fecaca; border-radius:8px; padding:24px; box-shadow:0 2px 8px rgba(220,38,38,0.08); position:relative; overflow:hidden; }
.stat-card::before { content:''; position:absolute; top:0; left:0; width:4px; height:100%; background:#dc2626; }
.stat-icon { width:48px; height:48px; background:#fee2e2; border-radius:8px; display:flex; align-items:center; justify-content:center; color:#dc2626; font-size:24px; margin-bottom:12px; }
.stat-label { font-size:12px; font-weight:600; color:#991b1b; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:8px; }
.stat-value { font-size:36px; font-weight:700; color:#dc2626; }
.table-card { background:#fff; border:1px solid #fecaca; border-radius:8px; padding:24px; box-shadow:0 2px 8px rgba(220,38,38,0.08); }
.table-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; padding-bottom:16px; border-bottom:2px solid #fecaca; }
.table-title { font-size:18px; font-weight:600; color:#991b1b; }
#expenses { width:100%; border-collapse:collapse; }
#expenses thead th { background:#fef2f2; color:#991b1b; font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:0.5px; padding:14px 16px; text-align:left; border-bottom:2px solid #fecaca; }
#expenses tbody td { padding:14px 16px; border-bottom:1px solid #fee2e2; font-size:14px; color:#111827; }
#expenses tbody tr:hover { background:#fef2f2; }
.amount-cell { font-weight:600; color:#dc2626; font-size:15px; }
/* DataTable Buttons Styling */
.dt-buttons { margin-bottom: 15px; }
.dt-buttons .btn { margin-right: 8px; height: 36px; padding: 0 16px; font-size: 13px; border-radius: 6px; }
.dt-buttons .btn i { margin-right: 6px; }
.dataTables_wrapper .row { margin-bottom: 15px; }
.dataTables_filter { text-align: right; }
.dataTables_filter input { border: 1px solid #fecaca; border-radius: 6px; padding: 6px 12px; margin-left: 8px; }
/* Fix colvis dropdown positioning */
.dt-button-collection { max-height: 400px !important; overflow-y: auto !important; }
.dt-button-collection .dt-button { text-align: left !important; }
</style>

<div class="expense-container">
    <div class="page-header" style="display: flex; justify-content: space-between; align-items: center;">
        <div>
            <h1 class="page-title">
                <i class="entypo-credit-card"></i> Expense Management
            </h1>
            <p class="page-subtitle">Track and manage all school expenses efficiently</p>
        </div>
        <div style="text-align: right;">
            <div style="font-size: 12px; font-weight: 600; color: #991b1b; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px;">Total Expenses</div>
            <div style="font-size: 36px; font-weight: 700; color: #dc2626;" id="totalExpenses">0.00</div>
        </div>
    </div>

    <div class="filter-card">
        <div class="filter-row">
            <div class="form-group">
                <label class="form-label">Start Date</label>
                <input id="datepicker-range-start" name="start" type="text" class="form-input datepicker" data-format="dd-mm-yyyy" placeholder="Select start date">
            </div>
            <div class="form-group">
                <label class="form-label">End Date</label>
                <input id="datepicker-range-end" name="end" type="text" class="form-input datepicker" data-format="dd-mm-yyyy" placeholder="Select end date">
            </div>
            <div class="form-group">
                <label class="form-label">Payment Method</label>
                <select id="payment-method-filter" class="form-input" style="height:40px;">
                    <option value="">All Methods</option>
                    <?php
                    $this->load->helper('payment_method');
                    $payment_methods = get_payment_methods();
                    foreach($payment_methods as $method):
                    ?>
                    <option value="<?php echo $method->id; ?>"><?php echo $method->name; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group" style="flex:0 0 auto; min-width:auto;">
                <label class="form-label" style="visibility:hidden;">Action</label>
                <button class="btn btn-secondary" onclick="filterToDate()" id="loadExpensesButton">
                    <i class="fa fa-filter"></i> <span>Filter</span>
                </button>
            </div>
            <div class="form-group" style="flex:0 0 auto; min-width:auto;">
                <label class="form-label" style="visibility:hidden;">Action</label>
                <a href="javascript:;" onclick="showAjaxModal('<?php echo site_url('modal/popup/expense_add/');?>');" class="btn btn-primary">
                    <i class="fa fa-plus"></i> Add Expense
                </a>
            </div>
            <div class="form-group" style="flex:0 0 auto; min-width:auto;">
                <label class="form-label" style="visibility:hidden;">Action</label>
                <a href="javascript:;" onclick="showAjaxModal('<?php echo site_url('admin/expense_bulk_add');?>', 'xlarge');" class="btn btn-success">
                    <i class="fa fa-list"></i> Bulk Add
                </a>
            </div>
            <div class="form-group" style="flex:0 0 auto; min-width:auto;">
                <label class="form-label" style="visibility:hidden;">Action</label>
                <a href="javascript:;" onclick="openBulkEdit()" class="btn btn-secondary">
                    <i class="fa fa-edit"></i> Bulk Edit
                </a>
            </div>
        </div>
    </div>

    <div class="table-card">
        <div class="table-header">
            <div class="table-title">Expense Records</div>
        </div>
        <table id="expenses">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Title</th>
                    <th>Category</th>
                    <th>Year | Term</th>
                    <th>Method</th>
                    <th>Amount (GHC)</th>
                    <th style="text-align:center;">Actions</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>



<!-----  DATA TABLE EXPORT CONFIGURATIONS ---->
<script type="text/javascript">

    jQuery(document).ready(function($) {
        // Initialize datepickers
        $('#datepicker-range-start, #datepicker-range-end').datepicker({
            format: 'dd-mm-yyyy',
            autoclose: true,
            todayHighlight: true
        });

        $.fn.dataTable.ext.errMode = 'throw';
        var expenseTable = $('#expenses').DataTable({
            "processing": true,
            "serverSide": true,
            "ajax":{
                "url": "<?php echo site_url('admin/get_expenses') ?>",
                "dataType": "json",
                "type": "POST",
                "dataSrc": function(json) {
                    // Update total expenses on every data load
                    if(json.totalExpenses) {
                        $('#totalExpenses').text(json.totalExpenses);
                    }
                    return json.data;
                }
            },
            
            "columns": [
                { "data": "date" },
                { "data": "title" },
                { "data": "category" },
                { "data": "year" },
                { "data": "payment_method" },
                { "data": "amount" },
                { "data": "options" },
            ],
            "columnDefs": [
                {
                    "targets": [2,5,6],
                    "orderable": false
                },
            ],
            "dom": '<"row"<"col-sm-6"B><"col-sm-6"f>>rtip',
            "buttons": [
                {
                    extend: 'colvis',
                    text: '<i class="fa fa-columns"></i> Columns',
                    className: 'btn btn-sm btn-secondary',
                    collectionLayout: 'fixed two-column',
                    popoverTitle: 'Column Visibility',
                    fade: 250
                },
                {
                    extend: 'excel',
                    text: '<i class="fa fa-file-excel-o"></i> Excel',
                    className: 'btn btn-sm btn-success',
                    title: 'Expense Report',
                    exportOptions: {
                        columns: ':visible', // Export all visible columns
                        format: {
                            body: function(data, row, column, node) {
                                // Strip HTML tags and get text content
                                return $('<div>').html(data).text();
                            }
                        }
                    },
                    footer: true,
                    customize: function(xlsx) {
                        // Add total row at the end
                        var sheet = xlsx.xl.worksheets['sheet1.xml'];
                        var lastRow = $('row', sheet).length;
                        var totalExpenses = $('#totalExpenses').text();
                        $('row:last c', sheet).attr('s', '2'); // Style last row
                    }
                },
                {
                    extend: 'pdf',
                    text: '<i class="fa fa-file-pdf-o"></i> PDF',
                    className: 'btn btn-sm btn-danger',
                    title: 'Expense Report',
                    orientation: 'landscape',
                    exportOptions: {
                        columns: ':visible', // Export all visible columns
                        format: {
                            body: function(data, row, column, node) {
                                // Strip HTML tags and get text content
                                return $('<div>').html(data).text();
                            }
                        }
                    },
                    footer: true,
                    customize: function(doc) {
                        // Add total expenses at the bottom
                        var totalExpenses = $('#totalExpenses').text();
                        doc.content[1].table.body.push([
                            { text: 'Total Expenses', bold: true, colSpan: 5, alignment: 'right' },
                            {},
                            {},
                            {},
                            {},
                            { text: totalExpenses, bold: true, alignment: 'right' },
                            { text: '', colSpan: 1 }
                        ]);
                    }
                },
                {
                    extend: 'print',
                    text: '<i class="fa fa-print"></i> Print',
                    className: 'btn btn-sm btn-primary',
                    title: 'Expense Report',
                    exportOptions: {
                        columns: ':visible', // Print all visible columns
                        format: {
                            body: function(data, row, column, node) {
                                // Strip HTML tags and get text content
                                return $('<div>').html(data).text();
                            }
                        }
                    },
                    footer: true,
                    customize: function(win) {
                        var totalExpenses = $('#totalExpenses').text();
                        
                        // Add custom styling for print view
                        $(win.document.body).find('table').addClass('display').css({
                            'font-size': '12pt',
                            'width': '100%'
                        });
                        
                        // Right-align amount column in print view
                        $(win.document.body).find('table tbody td:nth-child(6)').css('text-align', 'right');
                        
                        // Add total row
                        var totalRow = '<tr style="font-weight: bold; border-top: 2px solid #000; background-color: #f3f4f6;">' +
                            '<td colspan="5" style="text-align: right; padding: 10px;">Total Expenses (GHC):</td>' +
                            '<td style="text-align: right; padding: 10px;">' + totalExpenses + '</td>' +
                            '<td></td>' +
                            '</tr>';
                        $(win.document.body).find('table tbody').append(totalRow);
                    }
                }
            ]
        });
    });

    function expense_edit_modal(payment_id) {
        showAjaxModal('<?php echo site_url('modal/popup/expense_edit/');?>' + payment_id);
    }

    function expense_delete_confirm(payment_id) {
        showConfirmModal(
            'Delete Expense',
            'Are you sure you want to delete this expense? This action cannot be undone.',
            function() {
                // User confirmed, proceed with delete
                showAjaxModal_alert('Deleting expense...', 'loading');
                
                $.ajax({
                    url: '<?php echo site_url('admin/expense/delete/');?>' + payment_id,
                    type: 'POST',
                    dataType: 'json'
                }).done(function(response) {
                    if(response.message == 'done') {
                        showAjaxModal_alert('Expense deleted successfully.', 'success', false);
                        
                        // Reload DataTable
                        var table = $('#expenses').DataTable();
                        table.ajax.reload(null, false);
                        
                        // Update total expenses
                        setTimeout(function() {
                            table.on('xhr', function() {
                                var json = table.ajax.json();
                                if(json.totalExpenses) {
                                    $('#totalExpenses').text(json.totalExpenses);
                                }
                            });
                        }, 100);
                    } else {
                        showAjaxModal_alert('Error deleting expense.', 'error');
                    }
                }).fail(function(xhr) {
                    showAjaxModal_alert('An error occurred: ' + xhr.responseText, 'error');
                });
            },
            'Delete',
            'danger'
        );
    }

    function openBulkEdit() {
        let startDate = $('#datepicker-range-start').val();
        let endDate = $('#datepicker-range-end').val();
        
        let url = '<?php echo site_url('admin/expense_bulk_edit'); ?>';
        if(startDate && endDate) {
            startDate = startDate.replace(/\//g, '-');
            endDate = endDate.replace(/\//g, '-');
            url += '?start_date=' + startDate + '&end_date=' + endDate;
        }
        
        showAjaxModal(url, 'xlarge');
    }

    //reload by filtering
    function filterToDate() {

        let startDate = $('#datepicker-range-start').val();
        let endDate = $('#datepicker-range-end').val();
        let paymentMethod = $('#payment-method-filter').val();

        startDate = startDate.replace('/', '-');
        endDate = endDate.replace('/', '-');

        startDate = startDate.replace('/', '-');
        endDate = endDate.replace('/', '-');



        if(endDate == '' || startDate == '') {
            showAjaxModal_alert('Select a valid date range!', 'Error');
            return;
        }

        

        let buttonText = $('#loadExpensesButton span').text();

        if(buttonText == 'Filter') {
            $('#loadExpensesButton span').html('Loading...');
            $('#loadExpensesButton').prop('disabled', true);
        } else {
            $('#loadExpensesButton span').html('Filter');
            $('#loadExpensesButton').prop('disabled', false);
        }

        

        var table = $('#expenses').DataTable();

        // Build URL with payment method parameter
        let url = "<?php echo site_url("admin/get_expenses/") ?>" + 'ajax/' + startDate + "/"  + endDate;
        if(paymentMethod) {
            url += '/' + paymentMethod;
        }

        // Use draw event for reliable scroll after table redraw
        table.one('draw.dt', function() {
            let totalExpensesJson = table.ajax.json();
            $('#totalExpenses').text(totalExpensesJson.totalExpenses);
            $('#loadExpensesButton span').html('Filter');
            $('#loadExpensesButton').prop('disabled', false);
            
            // Scroll to table section smoothly
            setTimeout(function() {
                $('html, body').animate({
                    scrollTop: $('.table-card').offset().top - 20
                }, 600);
            }, 150);
        });

        table.ajax.url(url).load();

        
    }

</script>
