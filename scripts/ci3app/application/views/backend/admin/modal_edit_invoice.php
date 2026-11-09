<?php
$readonly = 'readonly';
$edit_data      =   $this->db->get_where('invoice' , array('invoice_id' => $param2) )->result_array();
?>

<div class="tab-pane box active" id="edit" style="padding: 5px">
    <div class="box-content">
        <?php foreach($edit_data as $row):?>
        <?php echo form_open(site_url('admin/invoice/do_update/'.$row['invoice_id']), array('class' => 'form-horizontal form-groups-bordered validate','target'=>'_top'));?>
                <div class="form-group">
                    <label class="col-sm-3 control-label"><?php echo get_phrase('student');?></label>
                       <div class="col-sm-5">
                        <?php $student_name = $this->db->get_where('student', array('student_id' => $row['student_id']))->row()->name; ?>
                        <h4><?php echo $student_name;?></h4>
                        <input type="hidden" name="student_id" class="form-control" value="<?php echo $row['student_id'];?>">
                    </div>
                </div>
                <div class="form-group">
                        <label class="col-sm-3 control-label"><?php echo get_phrase('invoice_title');?></label>
                        <div class="col-sm-5">
                            <select name="sel" onchange="invoice_edit_update(this.value)" class="form-control" required>
                                <?php 
                                    $inv_list  =   $this->db->get_where('invoice' , array('invoice_code' => $row['invoice_code']) )->result_array();
                                ?>

                                <?php foreach($inv_list as $list) : ?>
                                <option value="<?= $list['invoice_id'] ?>" <?php if($param2 == $list['invoice_id']) echo 'selected'; ?>><?php echo get_phrase($list['title']);?></option>
                            <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                    <label class="col-sm-3 control-label">Edit Title here</label>
                    <div class="col-sm-5">
                        <input type="text" class="form-control" placeholder="Edit title here" name="title" value="<?= $row['title'] ?>"/>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-sm-3 control-label"><?php echo get_phrase('description');?></label>
                    <div class="col-sm-5">
                        <input type="text" class="form-control" name="description" value="<?php echo $row['description'];?>"/>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-sm-3 control-label"><?php echo get_phrase('total_amount');?></label>
                    <div class="col-sm-5">
                        <input type="text" class="form-control" id="total_amount" name="amount" onkeyup="status_check()" value="<?php echo $row['amount'];?>" required/>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-sm-3 control-label"><?php echo get_phrase('amount_paid');?></label>
                    <div class="col-sm-5">
                        <input type="text" class="form-control" id="amount_paid" name="amount_paid" value="<?php echo $row['amount_paid'];?>" required <?php echo $readonly; ?>/>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-sm-3 control-label"><?php echo get_phrase('status');?></label>
                    <div class="col-sm-3">
                        <input type="text" class="form-control" id="status" name="status" value="<?php echo ucwords($row['status']); ?>" <?php echo $readonly; ?>>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-sm-3 control-label"><?php echo get_phrase('date');?></label>
                    <div class="col-sm-5">
                        <input type="text" class="datepicker form-control" name="date"
                            value="<?php echo date('m/d/Y', $row['creation_timestamp']);?>"/>
                    </div>

                </div>
                <div class="form-group">
                  <div class="col-sm-offset-3 col-sm-5">
                      <button type="submit" class="btn btn-success"><?php echo get_phrase('edit_invoice');?></button>
                  </div>
                </div>
        </form>
        <?php endforeach;?>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function() {

            var total_amount = Number($('#total_amount').val());
            var amount_paid  = Number($('#amount_paid').val());

            if(total_amount == amount_paid) {
                $('#status').val('Paid');
                $('#status').css({'background-color': 'green', 'color': '#ffffff'});
            }else if(total_amount > amount_paid) {
                $('#status').val('Unpaid');
                $('#status').css({'background-color': 'red', 'color': '#ffffff'});
            }else if(total_amount < amount_paid) {
               $('#status').val('Over paid');
               $('#status').css({'background-color': '#fbd41f', 'color': '#ffffff'});
            }
    });

        function status_check() {
            var total_amount = Number($('#total_amount').val());
            var amount_paid  = Number($('#amount_paid').val());

            if(total_amount == amount_paid) {
                $('#status').val('Paid');
                $('#status').css({'background-color': 'green', 'color': '#ffffff'});
            }else if(total_amount > amount_paid) {
                $('#status').val('Unpaid');
                $('#status').css({'background-color': 'red', 'color': '#ffffff'});
            }else if(total_amount < amount_paid) {
               $('#status').val('Over paid');
               $('#status').css({'background-color': '#fbd41f', 'color': '#ffffff'});
            }
         }
     
      //for updating the take edit modal when invoice title changes
    function invoice_edit_update(invoice_id) {
        showAjaxModal('<?php echo site_url('modal/popup/modal_edit_invoice/');?>' + invoice_id);
    }
</script>