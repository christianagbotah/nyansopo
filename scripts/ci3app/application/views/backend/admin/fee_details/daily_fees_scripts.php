<?php
// Ensure variables are defined (they should come from parent view)
if (!isset($school_logo)) {
    $school_logo = base_url() . 'uploads/school_logo.png';
}
if (!isset($system_name)) {
    $system_name = $this->db->get_where('settings', array('type' => 'system_name'))->row()->description;
}
?>
<script>
// Define JavaScript variables from PHP
var schoolLogo = '<?= $school_logo ?>';
var systemName = '<?= addslashes($system_name) ?>';

function toggleRowCheckbox(row) {
    var checkbox = row.querySelector(".receipt_checkbox");
    checkbox.checked = !checkbox.checked;
    updateSelectedCount();
}

function filterTable() {
    var input = document.getElementById("filter_student");
    var filter = input.value.toLowerCase();
    var table = document.getElementById("details_table");
    var tr = table.getElementsByTagName("tr");
    
    for (var i = 1; i < tr.length - 1; i++) {
        var studentName = tr[i].getAttribute("data-student");
        if (studentName && studentName.indexOf(filter) > -1) {
            tr[i].style.display = "";
        } else {
            tr[i].style.display = "none";
        }
    }
}

function selectAllReceipts() {
    var checkboxes = document.getElementsByClassName("receipt_checkbox");
    for (var i = 0; i < checkboxes.length; i++) {
        if (checkboxes[i].closest("tr").style.display !== "none") {
            checkboxes[i].checked = true;
        }
    }
    document.getElementById("select_all_checkbox").checked = true;
    updateSelectedCount();
}

function deselectAllReceipts() {
    var checkboxes = document.getElementsByClassName("receipt_checkbox");
    for (var i = 0; i < checkboxes.length; i++) {
        checkboxes[i].checked = false;
    }
    document.getElementById("select_all_checkbox").checked = false;
    updateSelectedCount();
}

function toggleAllFromHeader() {
    var checked = document.getElementById("select_all_checkbox").checked;
    var checkboxes = document.getElementsByClassName("receipt_checkbox");
    
    for (var i = 0; i < checkboxes.length; i++) {
        if (checkboxes[i].closest("tr").style.display !== "none") {
            checkboxes[i].checked = checked;
        }
    }
    updateSelectedCount();
}

function updateSelectedCount() {
    var checkboxes = document.getElementsByClassName("receipt_checkbox");
    var count = 0;
    for (var i = 0; i < checkboxes.length; i++) {
        if (checkboxes[i].checked) count++;
    }
    document.getElementById("selected_count").textContent = count;
}

function printSelected() {
    var checkboxes = document.getElementsByClassName("receipt_checkbox");
    var selected = [];
    
    for (var i = 0; i < checkboxes.length; i++) {
        if (checkboxes[i].checked) {
            selected.push({
                student_id: checkboxes[i].getAttribute("data-student-id"),
                timestamp: checkboxes[i].getAttribute("data-timestamp")
            });
        }
    }
    
    if (selected.length === 0) {
        showAjaxModal_alert('Please select at least one receipt to print', 'warning');
        return;
    }
    
    selected.forEach(function(item, index) {
        setTimeout(function() {
            window.open("<?= site_url('admin/print_fee_receipt') ?>/" + item.student_id + "/" + item.timestamp, "_blank");
        }, index * 500);
    });
}

function printTable() {
    var table = document.getElementById("details_table");
    var tfoot = table.querySelector("tfoot");
    var tfootHTML = tfoot ? tfoot.outerHTML : "";
    
    if (tfoot) tfoot.style.display = "none";
    
    var printWindow = window.open("", "", "width=800,height=600");
    var currentDate = new Date().toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
    var currentTime = new Date().toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
    
    printWindow.document.write("<html><head><title>Outstanding Arrears Report</title>");
    printWindow.document.write("<style>");
    printWindow.document.write("@page{margin:20mm;}");
    printWindow.document.write("body{font-family:Arial,sans-serif;}");
    printWindow.document.write(".print-header{display:flex;align-items:center;gap:15px;margin-bottom:20px;border-bottom:2px solid #333;padding-bottom:15px;}");
    printWindow.document.write(".school-logo{max-height:60px;}");
    printWindow.document.write(".school-info{flex:1;}");
    printWindow.document.write(".school-name{font-size:24px;font-weight:bold;margin:0;color:#2d3748;}");
    printWindow.document.write(".report-title{font-size:18px;color:#718096;margin:5px 0 0 0;}");
    printWindow.document.write(".print-date{text-align:center;color:#718096;font-size:14px;margin-bottom:20px;}");
    printWindow.document.write("table{width:100%;border-collapse:collapse;page-break-inside:auto;}");
    printWindow.document.write("tr{page-break-inside:avoid;page-break-after:auto;}");
    printWindow.document.write("thead{display:table-header-group;}");
    printWindow.document.write("th,td{border:1px solid #ddd;padding:8px;text-align:left;font-size:11px;}");
    printWindow.document.write("td:nth-child(3){font-size:10px;}");
    printWindow.document.write("th{background:#f3f4f6;font-weight:bold;}");
    printWindow.document.write(".print-footer{margin-top:20px;border-top:2px solid #333;padding-top:10px;text-align:right;font-weight:bold;font-size:18px;}");
    printWindow.document.write("</style>");
    printWindow.document.write("</head><body>");
    printWindow.document.write("<div class='print-header'>");
    printWindow.document.write("<img src='" + schoolLogo + "' class='school-logo' alt='School Logo'>");
    printWindow.document.write("<div class='school-info'>");
    printWindow.document.write("<h1 class='school-name'>" + systemName + "</h1>");
    printWindow.document.write("<p class='report-title'>Outstanding Arrears Report</p>");
    printWindow.document.write("</div></div>");
    printWindow.document.write("<div class='print-date'>Printed on " + currentDate + " at " + currentTime + "</div>");
    printWindow.document.write(table.outerHTML.replace(/<tfoot[^>]*>.*?<\/tfoot>/is, ""));
    printWindow.document.write("<div class='print-footer'>" + tfootHTML.replace(/<\/?tfoot>/g, "").replace(/<tr>/g, "<div>").replace(/<\/tr>/g, "</div>").replace(/<th[^>]*>/g, "<span>").replace(/<\/th>/g, "</span>") + "</div>");
    printWindow.document.write("</body></html>");
    printWindow.document.close();
    
    if (tfoot) tfoot.style.display = "";
    
    printWindow.print();
}
</script>
