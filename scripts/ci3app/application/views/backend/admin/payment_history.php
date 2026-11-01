<hr />
<div class="row">
    <div class="col-md-12">
        <a href="#" onclick="navigation('<?php echo site_url('admin/invoices_show'); ?>')" class="btn btn-<?php if($page_name == 'invoices' || $page_name == 'invoices_loaded') { echo 'success'; } else { echo 'info'; }; ?>">
            <?php echo get_phrase('invoices');?>
        </a>
        <a href="#" onclick="navigation('<?php echo site_url('admin/income/payment_history');?>')" class="btn btn-<?php echo $page_name == 'payment_history' ? 'success' : 'info'; ?>">
            <?php echo get_phrase('payment_history');?>
        </a>
        <a href="#" onclick="navigation('<?php echo site_url('admin/income/student_specific_payment_history');?>')" class="btn btn-<?php echo $page_name == 'student_specific_payment_history' ? 'success' : 'info'; ?>">
            <?php echo get_phrase('student_specific_payment_history');?>
        </a>
    </div>  
</div>
<hr>

<?php 
    $invoice_code_f       = $this->db->get_where('settings', array('type'=>'invoice_number_format'))->row()->description;
    $inv_number_len = strlen($invoice_code_f);
?>
<div class="row">
	<div class="col-md-12">
		<table class="table table-bordered" id="histories">
			<thead>
                <tr>
                    <th><div><?php echo get_phrase('title');?></div></th>
                    <th><div><?php echo get_phrase('description');?></div></th>
                    <th><div><?php echo get_phrase('method');?></div></th>
                    <th><div><?php echo get_phrase('amount');?></div></th>
                    <th><div><?php echo get_phrase('date');?></div></th>
                    <th><div><?php echo get_phrase('options');?></div></th>
                </tr>
            </thead>
		</table>
	</div>
</div>

<script type="text/javascript">
    $(document).ready(function() {
        $.fn.dataTable.ext.errMode = 'throw';
        $('#histories').DataTable({
            "processing": true,
            "serverSide": true,
            "ajax":{
                "url": "<?php echo site_url('admin/get_payments'); ?>",
                "dataType": "json",
                "type": "POST",
            },
            "columns": [
               // { "data": "payment_id" },
                { "data": "title" },
                { "data": "description" },
                { "data": "method" },
                { "data": "amount" },
                { "data": "date" },
                { "data": "options" },
            ],
            "columnDefs": [
                {
                    "targets": [2,5],
                    "orderable": false
                },
            ]
        });
    });


    function invoice_view_modal(invoice_code) {

        invoice_code = invoice_code.toString();
        let invoice_original_len = '<?php echo $inv_number_len; ?>';
        let current_invoice_len = invoice_code.length;

        if(invoice_code.substring(0, 1) == '_') {
            invoice_code = invoice_code.substring(1);
        } else {
            invoice_code = invoice_code;
        }

        $.ajax({
            url: '<?php echo site_url('admin/get_invoice_term/'); ?>'+ invoice_code,
            success: function(response) {
              showAjaxModal('<?php echo site_url('modal/popup/modal_view_invoice/');?>' + invoice_code + '/' + response);  
            }
        });
    }

    function bulk_invoice_view_modal(student_id) {

        showAjaxModal('<?php echo site_url('modal/popup/modal_view_bulk_invoice/');?>' + student_id);  
        
    }
</script>
