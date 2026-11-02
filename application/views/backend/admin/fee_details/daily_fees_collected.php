<?php
$system_name = $this->db->get_where('settings', array('type' => 'system_name'))->row()->description;
$school_logo = base_url() . 'uploads/school_logo.png';
?>
<style>
.filter-card { background: white; border-radius: 12px; padding: 20px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); margin-bottom: 20px; }
.form-label { font-weight: 600; color: #374151; font-size: 13px; margin-bottom: 8px; display: block; }
.form-control { border: 2px solid #e5e7eb; border-radius: 10px; padding: 10px 14px; font-size: 14px; transition: all 0.3s; height: 42px; }
.form-control:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1); outline: none; }
.btn-modern { padding: 8px 16px; border: none; border-radius: 10px; font-weight: 600; font-size: 13px; cursor: pointer; transition: all 0.3s; display: inline-flex; align-items: center; gap: 6px; height: 38px; }
.btn-primary-modern { background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: white; box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3); }
.btn-primary-modern:hover { background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%); box-shadow: 0 6px 16px rgba(59, 130, 246, 0.4); transform: translateY(-2px); }
.btn-secondary-modern { background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%); color: white; box-shadow: 0 4px 12px rgba(107, 114, 128, 0.3); }
.btn-secondary-modern:hover { background: linear-gradient(135deg, #4b5563 0%, #374151 100%); box-shadow: 0 6px 16px rgba(107, 114, 128, 0.4); transform: translateY(-2px); }
.btn-success-modern { background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3); }
.btn-success-modern:hover { background: linear-gradient(135deg, #059669 0%, #047857 100%); transform: translateY(-2px); }
.btn-danger-modern { background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); color: white; box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3); }
.btn-danger-modern:hover { background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%); transform: translateY(-2px); }
.btn-indigo-modern { background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%); color: white; box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3); }
.btn-indigo-modern:hover { background: linear-gradient(135deg, #4f46e5 0%, #4338ca 100%); transform: translateY(-2px); }
.btn-purple-modern { background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%); color: white; box-shadow: 0 4px 12px rgba(139, 92, 246, 0.3); }
.btn-purple-modern:hover { background: linear-gradient(135deg, #7c3aed 0%, #6d28d9 100%); transform: translateY(-2px); }
</style>

<div style="padding: 10px;">
    <!-- Filter Section -->
    <div class="filter-card" style="padding: 20px;">
        <h2 style="margin-bottom: 20px; color: #1f2937; font-size: 18px; font-weight: 700;">
            <i class="fa fa-filter" style="color: #3b82f6;"></i> Filter Collections
        </h2>
        
        <div class="row">
            <div class="col-md-3">
                <label class="form-label"><i class="fa fa-calendar"></i> From Date</label>
                <input type="text" id="filter_date_from" class="form-control air-datepicker" placeholder="Select start date" data-position="bottom left">
            </div>
            
            <div class="col-md-3">
                <label class="form-label"><i class="fa fa-calendar"></i> To Date</label>
                <input type="text" id="filter_date_to" class="form-control air-datepicker" placeholder="Select end date" data-position="bottom left">
            </div>
            
            <div class="col-md-3">
                <label class="form-label"><i class="fa fa-graduation-cap"></i> Class</label>
                <select id="filter_class" class="form-control">
                    <option value="">All Classes</option>
                    <?php getFullClassList(); ?>
                </select>
            </div>
            
            <div class="col-md-3">
                <label class="form-label">&nbsp;</label>
                <div style="display: flex; gap: 10px;">
                    <button onclick="applyFilters()" class="btn-modern btn-primary-modern">
                        <i class="fa fa-filter"></i> Apply
                    </button>
                    <button onclick="resetFilters()" class="btn-modern btn-secondary-modern">
                        <i class="fa fa-undo"></i> Reset
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div style="margin-bottom: 15px; display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
        <button onclick="selectAllReceipts()" class="btn-modern btn-success-modern">
            <i class="fa fa-check-square"></i> Select All
        </button>
        <button onclick="deselectAllReceipts()" class="btn-modern btn-danger-modern">
            <i class="fa fa-square"></i> Deselect All
        </button>
        <input type="text" id="filter_student" placeholder="Quick filter by name..." class="form-control" style="flex: 1; min-width: 200px; height: 38px; padding: 8px 12px; font-size: 13px;" onkeyup="filterTable()">
        <button onclick="printSelected()" class="btn-modern btn-indigo-modern">
            <i class="fa fa-print"></i> Print Receipts (<span id="selected_count">0</span>)
        </button>
        <button onclick="printList()" class="btn-modern btn-purple-modern">
            <i class="fa fa-list"></i> Print List
        </button>
        <button onclick="exportToExcel()" class="btn-modern btn-success-modern">
            <i class="fa fa-file-excel"></i> Export Excel
        </button>
    </div>
    
    <div id="content_area">
        <?php if (empty($data)): ?>
            <p style="text-align: center; color: #a0aec0; padding: 40px 0;">
                <i class="fa fa-info-circle"></i> No collections found
            </p>
        <?php else: ?>
            <table class="table table-striped" id="details_table" style="margin-bottom: 20px;">
                <thead>
                    <tr class="cursor-pointer">
                        <th style="width: 50px;">
                            <input type="checkbox" id="select_all_checkbox" onchange="toggleAllFromHeader()" style="width: 18px; height: 18px; cursor: pointer;">
                        </th>
                        <th>Date/Time</th>
                        <th>Student</th>
                        <th>Class</th>
                        <th>Receipt</th>
                        <th>Payment Method</th>
                        <th style="text-align: right;">Amount (<?= $currency ?>)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $total = 0;
                    foreach ($data as $row): 
                        $amount = $row[$fee_type . '_amount'];
                        $total += $amount;
                    ?>
                        <tr data-student="<?= strtolower($row['name']) ?>" onclick="toggleRowCheckbox(this)" style="cursor: pointer;">
                            <td onclick="event.stopPropagation();">
                                <input type="checkbox" class="receipt_checkbox" 
                                       data-student-id="<?= $row['student_id'] ?>" 
                                       data-timestamp="<?= $row['payment_date'] ?>" 
                                       onchange="updateSelectedCount()" 
                                       style="width: 18px; height: 18px; cursor: pointer;">
                            </td>
                            <td>
                                <div><?= date('M j, Y', (int)$row['created_at']) ?></div>
                                <small style="color: #718096;"><?= date('h:i A', (int)$row['created_at']) ?></small>
                            </td>
                            <td>
                                <strong><?= $row['name'] ?></strong><br>
                                <small><?= $row['student_code'] ?></small>
                            </td>
                            <td><?= $row['class_display'] ?></td>
                            <td><code><?= $row['receipt_number'] ?></code></td>
                            <td>
                                <?php
                                $payment_method = $this->db->get_where('payment_methods', ['id' => $row['payment_method']])->row();
                                if($payment_method) {
                                    echo '<i class="fa fa-' . $payment_method->icon . ' payment-icon" style="color: #718096; margin-right: 5px;"></i>';
                                    echo '<span style="color: #4a5568;">' . $payment_method->name . '</span>';
                                } else {
                                    echo '<span style="color: #a0aec0;">N/A</span>';
                                }
                                ?>
                            </td>
                            <td style="text-align: right; font-weight: 700;">
                                <span class="currency-symbol" style="font-size: 11px; color: #718096;"><?= $currency ?></span> <?= number_format($amount, 2) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="6" style="text-align: right;">
                            Total: <span class="currency-symbol" style="font-size: 12px; color: #718096;"><?= $currency ?></span> <?= number_format($total, 2) ?>
                        </th>
                    </tr>
                </tfoot>
            </table>
            <p style="color: #718096; font-size: 14px;">
                <i class="fa fa-info-circle"></i> Total transactions: <?= count($data) ?>
            </p>
        <?php endif; ?>
    </div>
</div>

<script>
var currentFeeType = '<?= $fee_type ?>';
var currentFeeName = '<?= $fee_name ?>';
var currentCashierId = '<?= $cashier_id ?>';

$(document).ready(function() {
    $('.air-datepicker').datepicker({
        dateFormat: 'yyyy-mm-dd',
        autoClose: true
    });
});

function applyFilters() {
    showAjaxModal_alert('Loading...', 'loading');
    
    // Store current filter values before reload
    var filterDateFrom = $('#filter_date_from').val();
    var filterDateTo = $('#filter_date_to').val();
    var filterClass = $('#filter_class').val();
    
    $.ajax({
        url: '<?= site_url('admin/get_fee_details') ?>',
        type: 'POST',
        data: {
            fee_type: currentFeeType,
            category: 'collected',
            cashier_id: currentCashierId,
            date_from: filterDateFrom,
            date_to: filterDateTo,
            class_id: filterClass
        },
        dataType: 'json'
    }).done(function(response) {
        $('#modal_alert').modal('hide');
        if(response.status === 'success') {
            $('#content_area').parent().html(response.html);
            
            // Restore filter values after content reload
            setTimeout(function() {
                $('#filter_date_from').val(filterDateFrom);
                $('#filter_date_to').val(filterDateTo);
                $('#filter_class').val(filterClass);
                
                // Reinitialize datepicker on the new elements
                $('.air-datepicker').datepicker({
                    dateFormat: 'yyyy-mm-dd',
                    autoClose: true
                });
            }, 100);
        } else {
            showAjaxModal_alert(response.message || 'Failed to load data', 'error');
        }
    }).fail(function() {
        showAjaxModal_alert('An error occurred', 'error');
    });
}

function resetFilters() {
    $('#filter_date_from').val('');
    $('#filter_date_to').val('');
    $('#filter_class').val('');
    $('#filter_student').val('');
    
    // Simply reload the table by filtering with empty values
    // This will show the original data that was loaded when modal opened
    filterTable();
}

function printList() {
    var table = document.getElementById("details_table");
    if (!table) {
        showAjaxModal_alert('No data to print', 'warning');
        return;
    }
    
    // Clone the table to modify for printing
    var tableClone = table.cloneNode(true);
    
    // Remove currency symbols and payment method icons from cloned table
    var currencySymbols = tableClone.querySelectorAll('.currency-symbol');
    currencySymbols.forEach(function(el) {
        el.style.display = 'none';
    });
    
    var paymentIcons = tableClone.querySelectorAll('.payment-icon');
    paymentIcons.forEach(function(el) {
        el.style.display = 'none';
    });
    
    var tfoot = tableClone.querySelector("tfoot");
    var tfootHTML = tfoot ? tfoot.outerHTML : "";
    
    var printWindow = window.open("", "", "width=800,height=600");
    printWindow.document.write("<html><head><title>Payment List - " + currentFeeName + "</title>");
    printWindow.document.write("<style>");
    printWindow.document.write("@page{margin:20mm;}");
    printWindow.document.write("body{font-family:Arial,sans-serif;}");
    printWindow.document.write(".print-header{display:flex;align-items:center;gap:15px;margin-bottom:20px;border-bottom:2px solid #333;padding-bottom:15px;}");
    printWindow.document.write(".school-logo{max-height:60px;}");
    printWindow.document.write(".school-info{flex:1;}");
    printWindow.document.write(".school-name{font-size:24px;font-weight:bold;margin:0;color:#2d3748;}");
    printWindow.document.write(".report-title{font-size:18px;color:#718096;margin:5px 0 0 0;}");
    printWindow.document.write("table{width:100%;border-collapse:collapse;page-break-inside:auto;}");
    printWindow.document.write("tr{page-break-inside:avoid;page-break-after:auto;}");
    printWindow.document.write("thead{display:table-header-group;}");
    printWindow.document.write("th,td{border:1px solid #ddd;padding:8px;text-align:left;}");
    printWindow.document.write("th{background:#f3f4f6;font-weight:bold;}");
    printWindow.document.write(".print-footer{margin-top:20px;border-top:2px solid #333;padding-top:10px;}");
    printWindow.document.write("th:first-child,td:first-child{display:none;}");
    printWindow.document.write(".currency-symbol{display:none;}");
    printWindow.document.write(".payment-icon{display:none;}");
    printWindow.document.write("</style>");
    printWindow.document.write("</head><body>");
    printWindow.document.write("<div class='print-header'>");
    printWindow.document.write("<img src='<?= $school_logo ?>' class='school-logo' alt='School Logo'>");
    printWindow.document.write("<div class='school-info'>");
    printWindow.document.write("<h1 class='school-name'><?= $system_name ?></h1>");
    printWindow.document.write("<p class='report-title'>Payment Collection List - " + currentFeeName + "</p>");
    printWindow.document.write("</div></div>");
    printWindow.document.write(tableClone.outerHTML.replace(/<tfoot[^>]*>.*?<\/tfoot>/is, ""));
    printWindow.document.write("<div class='print-footer'>" + tfootHTML.replace(/<\/?tfoot>/g, "").replace(/<tr>/g, "<div style='text-align:right;font-weight:bold;font-size:18px;'>").replace(/<\/tr>/g, "</div>").replace(/<th[^>]*>/g, "<span>").replace(/<\/th>/g, "</span>") + "</div>");
    printWindow.document.write("</body></html>");
    printWindow.document.close();
    
    printWindow.print();
}

function exportToExcel() {
    var table = document.getElementById("details_table");
    if (!table) {
        showAjaxModal_alert('No data to export', 'warning');
        return;
    }
    
    // Simple CSV export - exclude tfoot (total row) and checkbox column
    var csv = [];
    
    // Add header row (skip first column - checkbox)
    var headerRow = table.querySelector("thead tr");
    if (headerRow) {
        var row = [], cols = headerRow.querySelectorAll("th");
        for (var j = 1; j < cols.length; j++) { // Start from 1 to skip checkbox column
            var text = cols[j].innerText.replace(/"/g, '""');
            row.push('"' + text + '"');
        }
        csv.push(row.join(","));
    }
    
    // Add body rows (excluding tfoot and checkbox column)
    var bodyRows = table.querySelectorAll("tbody tr");
    for (var i = 0; i < bodyRows.length; i++) {
        var row = [], cols = bodyRows[i].querySelectorAll("td");
        
        for (var j = 1; j < cols.length; j++) { // Start from 1 to skip checkbox column
            var text = cols[j].innerText.replace(/"/g, '""').replace(/\n/g, ' ');
            row.push('"' + text + '"');
        }
        
        csv.push(row.join(","));
    }
    
    var csvFile = new Blob([csv.join("\n")], {type: "text/csv"});
    var downloadLink = document.createElement("a");
    downloadLink.download = "Payment_Collections_" + currentFeeName + "_" + new Date().toISOString().slice(0,10) + ".csv";
    downloadLink.href = window.URL.createObjectURL(csvFile);
    downloadLink.style.display = "none";
    document.body.appendChild(downloadLink);
    downloadLink.click();
    document.body.removeChild(downloadLink);
}
</script>

<?php $this->load->view('backend/admin/fee_details/daily_fees_scripts'); ?>
