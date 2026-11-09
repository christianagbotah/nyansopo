<div class="row">
    <div class="col-md-12">
        <div class="panel panel-primary" data-collapsed="0">
            <div class="panel-heading">
                <div class="panel-title" >
                    <i class="entypo-plus-circled"></i>
                    <?php echo get_phrase('request_new_book');?>

                    <h4 id="error_alert" style="text-align: center; color: red; position: relative; top: -10px; background-color: #ad0d0d; padding: 10px;"></h4>
                </div>
            </div>

            <div class="panel-body">
                
                <?php echo form_open(site_url('student/book_request/create') , array('class' => 'form-horizontal form-groups-bordered validate', 'enctype' => 'multipart/form-data'));?>
    
                    <div class="form-group">
                        <label class="col-sm-3 control-label"><?php echo get_phrase('book'); ?></label>
                        <div class="col-sm-6">
                            <select name="book_id" id = "book_id" class="form-control select2" onchange="verify_book(this.value)">
                                <option value="" disabled="disabled"><?php echo get_phrase('select_a_book'); ?></option>
                                <?php 
                                $books = $this->db->get('book')->result_array();
                                foreach ($books as $row) { ?>
                                    <option value="<?php echo $row['book_id']; ?>">
                                        <?php echo $row['name'];?>
                                    </option>
                                <?php } ?>
                            </select> 
                        </div>
                        <div class="col-sm-2" id="mark_it"></div>
                    </div>

                    <div class="form-group">
                        <label class="col-sm-3 control-label"><?php echo get_phrase('issue_starting_date');?></label>
                        <div class="col-sm-6">
                            <input type="text" class="datepicker form-control" name="issue_start_date"
                                data-validate="required" data-message-required="<?php echo get_phrase('value_required');?>" value="" />
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-sm-3 control-label"><?php echo get_phrase('issue_ending_date');?></label>
                        <div class="col-sm-6">
                            <input type="text" class="datepicker form-control" name="issue_end_date"
                                data-validate="required" data-message-required="<?php echo get_phrase('value_required');?>" value="" />
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <div class="col-sm-offset-3 col-sm-5">
                            <button type="submit" id = 'submit' class="btn btn-info"><?php echo get_phrase('submit');?></button>
                        </div>
                    </div>

                <?php echo form_close();?>

            </div>
        </div>
    </div>
</div>
<script type = 'text/javascript'>
                var book_id = '';
                jQuery(document).ready(function($) {
                    $("#submit").attr('disabled', 'disabled');

                    $("#error_alert").css('display', 'none');
                });

                function check_validation(){
                    if(book_id !== ''){
                        $('#submit').removeAttr('disabled');
                    }
                    else{
                        $("#submit").attr('disabled', 'disabled');
                    }
                }
                $('#book_id').change(function(){
                    book_id = $('#book_id').val();
                    check_validation();
                });

                //check if the selected book is available
                function verify_book(book_id) {
                    var book_id = $("#book_id").val();

                    $.ajax({
                        url: '<?php echo site_url('student/verify_book/') ?>' + book_id,
                        success: function(response) {
                            if(response == 'Unavailable') {
                                $("#error_alert").text('The Book You Have Selected Is Not Available. Please Choose Another One!')
                                $("#error_alert").css('display', 'block');
                                $("#submit").attr('disabled', 'disabled');
                                $("#mark_it").html('<span class="glyphicon glyphicon-remove control-label" style="font-size: 18px; color: red;"></span>');

                            }else {
                                 $("#error_alert").css('display', 'none');
                                 $('#submit').removeAttr('disabled');
                                 $("#mark_it").html('<span class="glyphicon glyphicon-ok control-label" style="font-size: 18px; color: green;"></span>');
                            }
                        }

                    });
                }
            </script>















