<script src="<?php echo base_url(); ?>assets/cdn/js/tailwindcss.js"></script>

<div class="p-6">
    <div class="bg-white rounded-lg shadow-lg">
        <div class="bg-gradient-to-r from-indigo-500 to-purple-500 px-8 py-6 rounded-t-lg shadow-md">
            <h2 class="text-3xl font-bold text-gray-50 drop-shadow-lg"><?php echo get_phrase('transport_fare_report'); ?></h2>
        </div>
        
        <div class="p-8">
            <!-- Summary Statistics -->
            <div class="mb-6 grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="bg-blue-100 p-4 rounded-lg border-2 border-blue-300">
                    <div class="text-sm text-gray-600"><?php echo get_phrase('total_payments'); ?></div>
                    <div id="stat_total_payments" class="text-2xl font-bold text-blue-700">0</div>
                </div>
                <div class="bg-green-100 p-4 rounded-lg border-2 border-green-300">
                    <div class="text-sm text-gray-600"><?php echo get_phrase('total_amount'); ?></div>
                    <div id="stat_total_amount" class="text-2xl font-bold text-green-700">GHS 0.00</div>
                </div>
                <div class="bg-purple-100 p-4 rounded-lg border-2 border-purple-300">
                    <div class="text-sm text-gray-600"><?php echo get_phrase('by_cash'); ?></div>
                    <div id="stat_cash" class="text-2xl font-bold text-purple-700">GHS 0.00</div>
                </div>
                <div class="bg-orange-100 p-4 rounded-lg border-2 border-orange-300">
                    <div class="text-sm text-gray-600"><?php echo get_phrase('by_mobile_money'); ?></div>
                    <div id="stat_momo" class="text-2xl font-bold text-orange-700">GHS 0.00</div>
                </div>
            </div>
            
            <div class="mb-6 grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2"><?php echo get_phrase('route'); ?></label>
                    <select id="filter_transport" class="shadow border rounded w-full h-12 px-3 text-gray-700">
                        <option value=""><?php echo get_phrase('all_routes'); ?></option>
                        <?php
                        $transports = $this->db->get('transport')->result_array();
                        foreach ($transports as $transport):
                        ?>
                            <option value="<?php echo $transport['transport_id']; ?>"><?php echo $transport['route_name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2"><?php echo get_phrase('payment_method'); ?></label>
                    <select id="filter_payment_method" class="shadow border rounded w-full h-12 px-3 text-gray-700">
                        <option value=""><?php echo get_phrase('all_methods'); ?></option>
                        <option value="cash"><?php echo get_phrase('cash'); ?></option>
                        <option value="bank_transfer"><?php echo get_phrase('bank_transfer'); ?></option>
                        <option value="mobile_money"><?php echo get_phrase('mobile_money'); ?></option>
                        <option value="cheque"><?php echo get_phrase('cheque'); ?></option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2"><?php echo get_phrase('from_date'); ?></label>
                    <input type="date" id="filter_from_date" class="shadow border rounded w-full h-12 px-3 text-gray-700 cursor-pointer" onclick="this.showPicker()">
                </div>
                
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2"><?php echo get_phrase('to_date'); ?></label>
                    <input type="date" id="filter_to_date" class="shadow border rounded w-full h-12 px-3 text-gray-700 cursor-pointer" value="<?php echo date('Y-m-d'); ?>" onclick="this.showPicker()">
                </div>
            </div>
            
            <div class="mb-4 flex gap-2">
                <button onclick="filterReport()" class="bg-blue-500 hover:bg-blue-700 text-white font-bold h-12 px-4 rounded">
                    <?php echo get_phrase('filter'); ?>
                </button>
                <button onclick="resetFilters()" class="bg-gray-500 hover:bg-gray-700 text-white font-bold h-12 px-4 rounded">
                    <?php echo get_phrase('reset'); ?>
                </button>
                <button onclick="exportReport()" class="bg-green-500 hover:bg-green-700 text-white font-bold h-12 px-4 rounded">
                    <?php echo get_phrase('export_excel'); ?>
                </button>
            </div>
            
            <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
                <table id="fare_report_table" class="w-full text-left text-gray-700">
                    <thead class="text-sm text-gray-700 uppercase bg-gray-100">
                        <tr>
                            <th scope="col" class="px-6 py-4 text-base"><?php echo get_phrase('receipt_no'); ?></th>
                            <th scope="col" class="px-6 py-4 text-base"><?php echo get_phrase('student'); ?></th>
                            <th scope="col" class="px-6 py-4 text-base"><?php echo get_phrase('class'); ?></th>
                            <th scope="col" class="px-6 py-4 text-base"><?php echo get_phrase('route'); ?></th>
                            <th scope="col" class="px-6 py-4 text-base"><?php echo get_phrase('amount'); ?></th>
                            <th scope="col" class="px-6 py-4 text-base"><?php echo get_phrase('payment_date'); ?></th>
                            <th scope="col" class="px-6 py-4 text-base"><?php echo get_phrase('payment_method'); ?></th>
                            <th scope="col" class="px-6 py-4 text-base"><?php echo get_phrase('details'); ?></th>
                            <th scope="col" class="px-6 py-4 text-base"><?php echo get_phrase('action'); ?></th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                    <tfoot>
                        <tr class="bg-gray-200 font-bold">
                            <td colspan="4" class="px-6 py-4 text-right"><?php echo get_phrase('total'); ?>:</td>
                            <td id="total_amount" class="px-6 py-4"></td>
                            <td colspan="4"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
var table;

$(document).ready(function() {
    loadReport();
});

function loadReport() {
    if (table) {
        table.destroy();
    }
    
    var transport_id = $('#filter_transport').val() || '';
    var payment_method = $('#filter_payment_method').val() || '';
    var from_date = $('#filter_from_date').val() || '';
    var to_date = $('#filter_to_date').val() || '';
    
    table = $('#fare_report_table').DataTable({
        processing: true,
        serverSide: false,
        ajax: {
            url: '<?php echo site_url("admin/get_transport_fare_report"); ?>',
            type: 'POST',
            data: {
                transport_id: transport_id,
                payment_method: payment_method,
                from_date: from_date,
                to_date: to_date
            },
            dataSrc: function(json) {
                if (json && json.data) {
                    $('#total_amount').text(json.total_amount || 'GHS 0.00');
                    $('#stat_total_payments').text(json.total_count || 0);
                    $('#stat_total_amount').text(json.total_amount || 'GHS 0.00');
                    $('#stat_cash').text(json.cash_total || 'GHS 0.00');
                    $('#stat_momo').text(json.momo_total || 'GHS 0.00');
                    return json.data;
                }
                return [];
            },
            error: function(xhr, error, thrown) {
                alert('Error loading data: ' + error);
            }
        },
        columns: [
            { 
                data: 'receipt_number', 
                className: 'px-6 py-4 font-semibold text-gray-900 text-base',
                render: function(data, type, row) {
                    return data ? '<a href="javascript:void(0)" onclick="printReceiptByNumber(\'' + data + '\')" class="text-blue-600 hover:text-blue-800 underline">' + data + '</a>' : '-';
                }
            },
            { data: 'student_name', className: 'px-6 py-4 text-base', defaultContent: '-' },
            { data: 'class_name', className: 'px-6 py-4 text-base', defaultContent: '-' },
            { data: 'route_name', className: 'px-6 py-4 text-base', defaultContent: '-' },
            { data: 'amount_paid', className: 'px-6 py-4 text-base', defaultContent: 'GHS 0.00' },
            { data: 'payment_date', className: 'px-6 py-4 text-base', defaultContent: '-' },
            { data: 'payment_method', className: 'px-6 py-4 text-base', defaultContent: '-' },
            { 
                data: 'details', 
                className: 'px-6 py-4 text-base',
                render: function(data, type, row) {
                    return data ? data : '-';
                },
                defaultContent: '-'
            },
            {
                data: null,
                className: 'px-6 py-4',
                orderable: false,
                render: function(data, type, row) {
                    if (data && data.receipt_number) {
                        return '<button onclick="printReceiptByNumber(\'' + data.receipt_number + '\')" class="bg-purple-500 hover:bg-purple-700 text-white font-bold py-2 px-4 rounded text-sm"><?php echo get_phrase('print'); ?></button>';
                    }
                    return '-';
                }
            }
        ],
        pageLength: 25,
        language: {
            emptyTable: "No transport fare records found",
            loadingRecords: "Loading...",
            processing: "Processing..."
        }
    });
}

function filterReport() {
    loadReport();
}

function resetFilters() {
    $('#filter_transport').val('');
    $('#filter_payment_method').val('');
    $('#filter_from_date').val('');
    $('#filter_to_date').val('<?php echo date('Y-m-d'); ?>');
    loadReport();
}

function exportReport() {
    var transport_id = $('#filter_transport').val();
    var payment_method = $('#filter_payment_method').val();
    var from_date = $('#filter_from_date').val();
    var to_date = $('#filter_to_date').val();
    
    window.location.href = '<?php echo site_url("admin/export_transport_fare_report"); ?>?transport_id=' + transport_id + 
                           '&payment_method=' + payment_method + '&from_date=' + from_date + '&to_date=' + to_date;
}

function printReceiptByNumber(receiptNumber) {
    window.open('<?php echo site_url("admin/transport_fare/transport_fare_receipt"); ?>/' + receiptNumber, '_blank');
}
</script>
