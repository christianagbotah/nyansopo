
<?php 
    $invoice_code_f       = $this->db->get_where('settings', array('type'=>'invoice_number_format'))->row()->description;
    $inv_number_len = strlen($invoice_code_f);

    $admin_level = $this->db->get_where('admin', array('name' => $name))->row()->level;
    $current_user     = $this->session->userdata('login_type') . '-' . $this->session->userdata('login_user_id');

    
    //currency
    $currency = $this->db->get_where('settings', array('type' => 'currency'))->row()->description;
    $fmt = new NumberFormatter('en_US', NumberFormatter::CURRENCY);


?>
<div class="row">
		<div class="form-group row">
			<div class="col-sm-9 col-lg-9 col-sm-9 col-md-9"></div>
			<div class="col-sm-3 col-lg-3 col-sm-3 col-md-3">
          <select name="year" onchange="getAllInvoicesByYear($(this).val())" class="form-control select2">
          <?php $running_year = $this->db->get_where('settings' , array('type'=>'running_year'))->row()->description;?>
          <option value="" disabled="true"><?php echo get_phrase('select_year');?></option>
          <?php
						echo populate_academic_year('yes', $year);
						?>
          </select>
      </div>
		</div>
    <div class="col-md-12" id="table_holder">
        

                    
                       
                    
        
    </div>

</div>
<script type="text/javascript">
		$(window).on('load', function(e) {
			getAllInvoicesByYear('<?=$running_year;?>');
			$('.modal').css({
				backgroundColor: 'rgba(2, 2, 2, 0.54)',
			});
		})

    $(document).ready(function($) {

    		boxChecked();
    		

    		/*$.fn.dataTable.ext.errMode = 'throw';
        $('#tinvoices').DataTable({
        	//"processing": true,
    			//"serverSide": true,
        	"columnDefs": [
        	{
        		"targets": [0],
        		"orderable": false
        	}]
        });*/
    });

    function getAllInvoicesByYear(year) {

    	showAjaxModal_alert('LOADING INVOICES. PLEASE WAIT...', 'Loading');

    	$.ajax({
    		url: '<?= site_url('admin/all_invoices/');?>' + year + '/yes',
    		type: 'post',
    		dataType: 'html',

    	})
    	.done(function(data) {
    		$('.close').click();
    		$('#table_holder').html(data);
    	})
    	.fail(function(err) {
    		$('#table_holder').html('<div style="text-align="center">' + err.responseText + '</div>');
    	})
    }


    //toggle check all
    function boxCheckedAll(value) {
    		
        let checkboxes = $('#checkboxes_form tbody td input[type="checkbox"]');

       	let thisVal = $('input[name="all_invoices_sel[]"]').filter(':checked').length;
       	
       	if(checkboxes.length)
        $(function(e) {
        	$.each(checkboxes, (index, val) => {

        		if(thisVal > 0) { //check all
        			val.checked = true;
        			$('#tfooter').removeAttr('style');
        		} else { //uncheck all
        			val.checked = false;
        			$('#tfooter').css('display', 'none');
        		}
        		
        	})
        })
    }

    //is any box checked
    function boxChecked() {
        let checkboxes = $('#checkboxes_form tbody input[type="checkbox"]');
        let count_checked_buttons = checkboxes.filter(':checked').length;

        if(count_checked_buttons < 1) {
            $('#tfooter').css('display', 'none');
        } else {
            $('#tfooter').removeAttr('style');
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
            showAjaxModal('<?php echo site_url('modal/popup/modal_take_payment2/'); ?>' + student_id + '/' + date, 'take_payment');
        } else if(date != '' && term != '') {
            showAjaxModal('<?php echo site_url('modal/popup/modal_take_payment2/'); ?>' + student_id + '/' + date + '/' + term, 'take_payment');
        }  else {
            showAjaxModal('<?php echo site_url('modal/popup/modal_take_payment2/'); ?>' + student_id, 'take_payment');
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

        $.ajax({
            url: '<?php echo site_url('admin/get_invoice_term/'); ?>'+ invoice_code,
            cache: false,
            success: function(response) {
              showAjaxModal('<?php echo site_url('modal/popup/modal_view_invoice/'); ?>' + invoice_code + '/' + response, 'take_payment');
            }
        });

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



</script>