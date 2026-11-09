<?php
	$currency = $this->db->get_where('settings', array('type' => 'currency'))->row()->description;
	$currency_len = strlen($currency);
	$fmt = new NumberFormatter('en_US', NumberFormatter::CURRENCY);
?>

<div class="col-md-2 col-sm-2"></div>
        <div class="col-md-8 col-sm-8">
            <div class="panel panel-info panel-shadow" data-collapsed="0">
                <div class="panel-heading" style="background-color: #6cb7d8">
                    <div class="panel-title" style="color: #fff;"><?php echo get_phrase('payments_|_receipts_history'); ?></div>
                </div>
                <div class="panel-body">
                    <div class="row" style="margin-bottom: 15px;">
                        <div class="col-md-3">
                            <input type="text" id="filter_receipt" class="form-control" placeholder="Filter by Receipt Code">
                        </div>
                        <div class="col-md-3">
                            <input type="text" id="filter_date" class="form-control" placeholder="Filter by Date">
                        </div>
                        <div class="col-md-3">
                            <select id="filter_method" class="form-control">
                                <option value="">All Methods</option>
                                <option value="1">Cash</option>
                                <option value="2">Cheque</option>
                                <option value="3">Card</option>
                                <option value="4">Mobile Money</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <input type="number" id="filter_amount" class="form-control" placeholder="Filter by Amount" step="0.01">
                        </div>
                    </div>

                    <table class="table table-bordered" id="exp_table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th><?php echo get_phrase('receipt_no.'); ?></th>
                                <th style="text-align: right"><?php echo get_phrase('amount_received'); ?></th>
                                <th><?php echo get_phrase('method'); ?></th>
                                <th><?php echo get_phrase('date_paid'); ?></th>
                                <th><?php echo get_phrase('action'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
							$count = 1;

							$this->db->select('receipt_code, SUM(amount) as total_amount, timestamp, year, term, method');
							$this->db->from('payment');
							$this->db->where('invoice_code !=', null);
							$this->db->where('invoice_id !=', null);
							$this->db->where('student_id', $student_id);
							$this->db->group_by('receipt_code');
							$this->db->order_by('receipt_code', 'desc');
							$payments = $this->db->get()->result_array();

							foreach ($payments as $row):

							$receipt_code = $row['receipt_code'];
							$total_amount_received = $row['total_amount'];
							$timestamp = $row['timestamp'];
							$year = $row['year'];
							$term = $row['term'];
							$method = $row['method'];
							?>
               <tr>
                   <td><?php echo $count++; ?></td>
                   <td><?php echo $receipt_code; ?></td>
                   <td style="font-weight: bolder; text-align: right"><?php echo numfmt_format_currency($fmt, $total_amount_received, $currency); ?></td>
                   <td>
                       <?php
				if ($method == 1) {
				echo get_phrase('cash');
				}

				if ($method == 2) {
				echo get_phrase('cheque');
				}

				if ($method == 3) {
				echo get_phrase('card');
				}

				if ($method == 4) {
				echo 'Mobile Money';
				}

				?>
			</td>
              <td><?php echo date('d M, Y H:i:s', $timestamp); ?></td>

             <td>
              <button class="btn btn-info" onclick="modal_view_receipt('<?php echo $receipt_code ?>', <?=$student_id . ',' . $total_amount_received . ',' . $timestamp;?>)"><i class="entypo-eye"></i><em> View Receipt</em>
              </button>
              <button class="btn btn-warning" onclick="requestModification('<?php echo $receipt_code; ?>')"><i class="fa fa-edit"></i> Modify
              </button>
             </td>
        </tr>
                         <?php

						endforeach;
						?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-sm-2"></div>

<script>
$(document).ready(function() {
    $('#filter_receipt, #filter_date, #filter_method, #filter_amount').on('keyup change', function() {
        var receiptFilter = $('#filter_receipt').val().toLowerCase();
        var dateFilter = $('#filter_date').val().toLowerCase();
        var methodFilter = $('#filter_method').val();
        var amountFilter = $('#filter_amount').val();
        
        $('#exp_table tbody tr').each(function() {
            var receipt = $(this).find('td:eq(1)').text().toLowerCase();
            var amount = $(this).find('td:eq(2)').text().replace(/[^0-9.]/g, '');
            var method = $(this).find('td:eq(3)').text().toLowerCase();
            var date = $(this).find('td:eq(4)').text().toLowerCase();
            
            var showRow = true;
            
            if(receiptFilter && receipt.indexOf(receiptFilter) === -1) showRow = false;
            if(dateFilter && date.indexOf(dateFilter) === -1) showRow = false;
            if(amountFilter && parseFloat(amount) !== parseFloat(amountFilter)) showRow = false;
            
            if(methodFilter) {
                var methodMatch = false;
                if(methodFilter === '1' && method.indexOf('cash') !== -1) methodMatch = true;
                if(methodFilter === '2' && method.indexOf('cheque') !== -1) methodMatch = true;
                if(methodFilter === '3' && method.indexOf('card') !== -1) methodMatch = true;
                if(methodFilter === '4' && method.indexOf('mobile money') !== -1) methodMatch = true;
                if(!methodMatch) showRow = false;
            }
            
            $(this).toggle(showRow);
        });
    });
});

function requestModification(receiptCode) {
    $.get('<?php echo site_url("admin/get_payment_id_by_receipt/"); ?>' + receiptCode, function(response) {
        var data = JSON.parse(response);
        if(data.status === 'success') {
            loadModalContent('createModal', 
                '<?php echo site_url("admin/receipt_modification_modal/"); ?>' + data.payment_id, 
                '<i class="fa fa-edit"></i> Request Receipt Modification');
        } else {
            showAjaxModal_alert('Receipt not found', 'error');
        }
    });
}
</script>