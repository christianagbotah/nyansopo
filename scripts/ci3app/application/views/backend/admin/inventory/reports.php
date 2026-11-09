<!-- Inventory Reports -->
<?php include('_readable_header.php'); ?>

<div class="inventory-content p-8 sm:p-10 lg:p-12" style="margin-top: 70px;">
    <div class="mb-12">
        <h1 style="font-size: 3.5rem !important;" class="font-bold text-gray-900 tracking-tight leading-tight">Inventory Reports</h1>
        <p style="font-size: 1.25rem !important;" class="mt-4 text-gray-600 leading-relaxed">Generate and export inventory reports</p>
    </div>

    <div class="grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-3 mb-12">
        <div class="bg-white border border-gray-200 rounded-xl p-8 shadow-sm hover:shadow-md transition-shadow">
            <h3 style="font-size: 1.5rem !important;" class="font-bold text-gray-900 mb-3">Stock Valuation Report</h3>
            <p style="font-size: 1.125rem !important;" class="text-gray-600 mb-6 leading-relaxed">Complete inventory with current stock values</p>
            <button onclick="generateReport('valuation')" style="font-size: 1.125rem !important; min-height: 3.5rem !important;" class="w-full px-5 py-3 border border-transparent font-semibold rounded-lg text-white bg-blue-600 hover:bg-blue-700 transition-colors">
                Generate Report
            </button>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl p-8 shadow-sm hover:shadow-md transition-shadow">
            <h3 style="font-size: 1.5rem !important;" class="font-bold text-gray-900 mb-3">Low Stock Report</h3>
            <p style="font-size: 1.125rem !important;" class="text-gray-600 mb-6 leading-relaxed">Items below reorder level</p>
            <button onclick="generateReport('low_stock')" style="font-size: 1.125rem !important; min-height: 3.5rem !important;" class="w-full px-5 py-3 border border-transparent font-semibold rounded-lg text-white bg-orange-600 hover:bg-orange-700 transition-colors">
                Generate Report
            </button>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl p-8 shadow-sm hover:shadow-md transition-shadow">
            <h3 style="font-size: 1.5rem !important;" class="font-bold text-gray-900 mb-3">Movement History</h3>
            <p style="font-size: 1.125rem !important;" class="text-gray-600 mb-6 leading-relaxed">Stock in/out transactions</p>
            <button onclick="generateReport('movements')" style="font-size: 1.125rem !important; min-height: 3.5rem !important;" class="w-full px-5 py-3 border border-transparent font-semibold rounded-lg text-white bg-green-600 hover:bg-green-700 transition-colors">
                Generate Report
            </button>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl p-8 shadow-sm hover:shadow-md transition-shadow">
            <h3 style="font-size: 1.5rem !important;" class="font-bold text-gray-900 mb-3">Sales Report</h3>
            <p style="font-size: 1.125rem !important;" class="text-gray-600 mb-6 leading-relaxed">Product sales summary</p>
            <button onclick="generateReport('sales')" style="font-size: 1.125rem !important; min-height: 3.5rem !important;" class="w-full px-5 py-3 border border-transparent font-semibold rounded-lg text-white bg-purple-600 hover:bg-purple-700 transition-colors">
                Generate Report
            </button>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl p-8 shadow-sm hover:shadow-md transition-shadow">
            <h3 style="font-size: 1.5rem !important;" class="font-bold text-gray-900 mb-3">Category Analysis</h3>
            <p style="font-size: 1.125rem !important;" class="text-gray-600 mb-6 leading-relaxed">Stock by category breakdown</p>
            <button onclick="generateReport('category')" style="font-size: 1.125rem !important; min-height: 3.5rem !important;" class="w-full px-5 py-3 border border-transparent font-semibold rounded-lg text-white bg-indigo-600 hover:bg-indigo-700 transition-colors">
                Generate Report
            </button>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl p-8 shadow-sm hover:shadow-md transition-shadow">
            <h3 style="font-size: 1.5rem !important;" class="font-bold text-gray-900 mb-3">Supplier Report</h3>
            <p style="font-size: 1.125rem !important;" class="text-gray-600 mb-6 leading-relaxed">Products by supplier</p>
            <button onclick="generateReport('supplier')" style="font-size: 1.125rem !important; min-height: 3.5rem !important;" class="w-full px-5 py-3 border border-transparent font-semibold rounded-lg text-white bg-teal-600 hover:bg-teal-700 transition-colors">
                Generate Report
            </button>
        </div>
    </div>

    <div id="report_container" class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden hidden">
        <div class="px-8 py-5 border-b border-gray-200 bg-gradient-to-r from-gray-50 to-gray-100 flex items-center justify-between">
            <h2 style="font-size: 1.75rem !important;" class="font-bold text-gray-900" id="report_title">Report</h2>
            <div class="flex space-x-3">
                <button onclick="exportReport('excel')" style="font-size: 1.125rem !important;" class="px-4 py-2 border border-gray-300 font-semibold rounded-lg text-gray-700 bg-white hover:bg-gray-50 transition-colors">
                    <i class="fa fa-file-excel"></i> Excel
                </button>
                <button onclick="exportReport('pdf')" style="font-size: 1.125rem !important;" class="px-4 py-2 border border-gray-300 font-semibold rounded-lg text-gray-700 bg-white hover:bg-gray-50 transition-colors">
                    <i class="fa fa-file-pdf"></i> PDF
                </button>
                <button onclick="window.print()" style="font-size: 1.125rem !important;" class="px-4 py-2 border border-gray-300 font-semibold rounded-lg text-gray-700 bg-white hover:bg-gray-50 transition-colors">
                    <i class="fa fa-print"></i> Print
                </button>
            </div>
        </div>
        <div class="p-8">
            <div id="report_content"></div>
        </div>
    </div>
</div>

<script>
let currentReportType = '';

function generateReport(type) {
    currentReportType = type;
    showAjaxModal_alert('Generating report...', 'loading');
    
    $.get('<?php echo site_url('inventory/generate_report/'); ?>' + type, function(response) {
        const data = JSON.parse(response);
        if(data.status === 'success') {
            $('#report_title').text(data.title);
            $('#report_content').html(data.html);
            $('#report_container').removeClass('hidden');
            $('.close').click();
            $('html, body').animate({ scrollTop: $('#report_container').offset().top - 100 }, 500);
        } else {
            showAjaxModal_alert(data.message || 'Failed to generate report', 'error');
        }
    }).fail(function() {
        showAjaxModal_alert('An error occurred', 'error');
    });
}

function exportReport(format) {
    if(!currentReportType) return;
    window.location.href = '<?php echo site_url('inventory/export_report'); ?>?type=' + currentReportType + '&format=' + format;
}
</script>

<style>
@media print {
    .no-print { display: none !important; }
    #report_container { border: none !important; }
}
</style>
