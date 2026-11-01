<?php
    $paypal_details = json_decode($this->db->get_where('settings' , array('type'=>'paypal'))->row()->description, true);
    $stripe_details = json_decode($this->db->get_where('settings' , array('type'=>'stripe_keys'))->row()->description, true);

    $mo_button = get_phrase('mobile_money');
    $disabled = '';
    $btn_color = 'info';

    $currency = $this->db->get_where('settings', array('type' => 'currency'))->row()->description;
    $fmt = new NumberFormatter('en_US', NumberFormatter::CURRENCY);

    $paypal_activity = $paypal_details[0]['active'];
    $stripe_activity = $stripe_details[0]['active'];

    $child_of_parent = $this->db->get_where('student' , array(
        'student_id' => $student_id
    ))->result_array();
    foreach ($child_of_parent as $row):
?>
<hr />
<div class="label label-primary pull-right" style="font-size: 14px;">
    <i class="entypo-user"></i> <?php echo $row['name'];?>
</div>
<div class="row">
	<div class="col-md-12">

    	<!------CONTROL TABS START------>
		<ul class="nav nav-tabs bordered">
			<li class="active">
            	<a href="#list" data-toggle="tab"><i class="entypo-menu"></i>
					<?php echo get_phrase('invoice/payment_list');?>
                    	</a></li>
		</ul>
    	<!------CONTROL TABS END------>
		<div class="tab-content">
            <!----TABLE LISTING STARTS-->
            <div class="tab-pane box active" id="list">

                <table  class="table table-bordered datatable" id="table_export">
                	<thead>
                		<tr>
                    		<th><div><?php echo get_phrase('student');?></div></th>
                            <th><div><?php echo get_phrase('invoice#');?></div></th>
                    		<th><div><?php echo get_phrase('title');?></div></th>
                    		<th><div><?php echo get_phrase('description');?></div></th>
                    		<th><div><?php echo get_phrase('amount');?></div></th>
                            <th><div><?php echo get_phrase('amount_paid');?></div></th>
                    		<th><div><?php echo get_phrase('status');?></div></th>
                    		<th><div><?php echo get_phrase('date');?></div></th>
                    		<th><div><?php echo get_phrase('payment_options');?></th>
                            <th><div><?php echo get_phrase('view_invoice');?></div></th>
						</tr>
					</thead>
                    <tbody>
                    	<?php
                        $this->db->where('can_delete !=', 'trash');
                            $invoices = $this->db->get_where('invoice' , array(
                                'student_id' => $row['student_id']
                            ))->result_array();
                            foreach($invoices as $row2):
                        ?>
                        <tr>
							<td><?php echo $this->crud_model->get_type_name_by_id('student',$row2['student_id']);?></td>
                            <td><?php echo $row2['invoice_code'];?></td>
							<td><?php echo $row2['title'];?></td>
							<td><?php echo $row2['description'];?></td>
							<td><?php echo numfmt_format_currency($fmt, $row2['amount'], $currency);?></td>
                            <td><?php echo numfmt_format_currency($fmt, $row2['amount_paid'], $currency);?></td>

                            <?php if($row2['due'] == 0):?>
                                <td>
                                    <button class="btn btn-success btn-xs"><?php echo get_phrase('paid');?></button>
                                </td>
                            <?php endif;?>
                            <?php if($row2['due'] > 0):?>
                                <td>
                                    <button class="btn btn-danger btn-xs"><?php echo get_phrase('unpaid');?></button>
                                </td>
                            <?php endif;?>
                            <?php if($row2['due'] < 0):?>
                                <td>
                                    <button class="btn btn-warning btn-xs"><?php echo get_phrase('over_paid');?></button>
                                </td>
                            <?php endif;?>
							<td><?php echo date('d M, Y', $row2['creation_timestamp']);?></td>
							<td class="col-md-3">
            

                                
                                   <?php if(intval($row2['due']) <= 0):
                                         $mo_button = 'No payment required!';
                                         $btn_color = 'success';
                                         $disabled = 'disabled';

                                     else:
                                        $mo_button = get_phrase('mobile_money');
                                        $disabled = '';
                                        $btn_color = 'info';

                                        endif;
                                    ?>

                                <a href="#" class="btn btn-<?php echo $btn_color; ?>"  onclick="showAjaxModal('<?php echo site_url('modal/popup/modal_mobile_money_payment/'.$row2['invoice_code'].'/'.$row2['student_id']); ?>')" <?php echo $disabled; ?>>
                                    <span data-toggle="tooltip" title="Mobile Money"><i class="fa fa-money" id="cont1" aria-hidden="true" style="color: #fff;" ></i> <?php echo $mo_button; ?></span>
                                </a>
              
                              </td>
                              <td><a href="#" class="btn btn-primary btn-block" onclick="invoice_view_modal('<?php echo $row2['invoice_code'];?>')"><i class="entypo-credit-card"></i>&nbsp; <?php echo get_phrase('view_invoice');?></a></td>

                        </tr>
                        <?php 
                        $row2['due'] = '';
                    endforeach;?>
                    </tbody>
                </table>
			</div>
            <!----TABLE LISTING ENDS-->


		</div>
	</div>
</div>
<?php endforeach;?>


<script type="text/javascript">

    function invoice_view_modal(invoice_code) {
        $.ajax({
            url: '<?php echo site_url('admin/get_invoice_term/'); ?>'+ invoice_code,
            success: function(response) {
              showAjaxModal('<?php echo site_url('modal/popup/modal_view_invoice/');?>' + invoice_code + '/' + response);  
            }
        });
    }

</script>
