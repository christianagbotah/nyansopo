    <script type="text/javascript">
    $('.modal-header .close').css({
            opacity: .8,

        });
    function showAjaxModal(url, $param = '')
    {
        //expand the size of the modal if the page name is take_payment
        if($param == 'take_payment' || $param == 'modal_unpaid_invoices' || $param == 'make_refund') {
            $('#modal_ajax .modal-content').css({
                'left' : '-325px',
                'width' : '1250px'
            });
        }
        // SHOWING AJAX PRELOADER IMAGE

        $('#modal_ajax .modal-body').html('<div style="text-align:center;margin-top:200px;"><img src="<?php echo base_url();?>assets/images/validate.gif" width="64px"/><br/><small>This might take a little time</small></div>');
        // LOADING THE AJAX MODAL
        $('#modal_ajax').modal('show', {backdrop: 'true'});

        // SHOW AJAX RESPONSE ON REQUEST SUCCESS
        $.ajax({
            url: url,
            cache: false,
            success: function(response)
            {
                $('#modal_ajax .modal-body').html(response);
            }
        });
    }

    function showAjaxModalDisplay(data = '')
    {        

        // LOADING THE AJAX MODAL
        $('#modal_display').modal('show', {backdrop: 'true'});
        $('#modal_display .modal-body').html(data);

    }

    function showAjaxModal_move_student(url, param = [])
    {

        // SHOWING AJAX PRELOADER IMAGE

        param = param.join('-');

        $('#modal_move_student .modal-body').html('<div style="text-align:center;margin-top:200px;"><img src="<?php echo base_url();?>assets/images/validate.gif" width="64px"/><br/><small>This might take a little time</small></div>');
        // LOADING THE AJAX MODAL
        $('#modal_move_student').modal('show', {backdrop: 'true'});

        // SHOW AJAX RESPONSE ON REQUEST SUCCESS
        $.ajax({
            url: url + param,
            cache: false,
            success: function(response)
            {
                $('#modal_move_student .modal-body').html(response);
            }
        });
    }

    function showAjaxModal_invoice(url, $param = '')
    {
        // SHOWING AJAX PRELOADER IMAGE
        $('#modal_ajax .modal-body').html('<div style="text-align:center;margin-top:200px;"><img src="assets/images/validate.gif" width="64px" /></div>');

        $('#modal_ajax .modal-body').html('<div style="text-align:center;margin-top:200px;"><img src="<?php echo base_url();?>assets/images/validate.gif" width="64px"/></div>');
        // LOADING THE AJAX MODAL
        $('#modal_ajax').modal('show', {backdrop: 'true'});

        // SHOW AJAX RESPONSE ON REQUEST SUCCESS
        $.ajax({
            url: url,
            success: function(response)
            {
                $('#modal_ajax .modal-body').html(response);
            }
        });
    }

    function showAjaxModal_receipt(url, $param = '')
    {
        //expand the size of the modal if the page name is take_payment
        if($param == 'take_payment') {
            $('#modal_ajax_receipt .modal-content').css({
                'left' : '-325px',
                'width' : '1250px'
            });
        } else {
            $('#modal_ajax_receipt .modal-content').removeAttr('style');
        }
        // SHOWING AJAX PRELOADER IMAGE
        $('#modal_ajax_receipt .modal-body').html('<div style="text-align:center;margin-top:200px;"><img src="assets/images/validate.gif" width="64px" /></div>');

        $('#modal_ajax_receipt .modal-body').html('<div style="text-align:center;margin-top:200px;"><img src="<?php echo base_url();?>assets/images/validate.gif" width="64px"/></div>');
        // LOADING THE AJAX MODAL
        $('#modal_ajax_receipt').modal('show', {backdrop: 'true'});

        // SHOW AJAX RESPONSE ON REQUEST SUCCESS
        $.ajax({
            url: url,
            success: function(response)
            {
                $('#modal_ajax_receipt .modal-body').html(response);
            }
        });
    }


    function showAjaxModal_selection(url, $param = '')
    {

        // LOADING THE AJAX MODAL
        $('#selection').modal('show', {backdrop: 'true'});

        // SHOW AJAX RESPONSE ON REQUEST SUCCESS
        $.ajax({
            url: url,
            success: function(response)
            {
                $('#selection .modal-body').html(response);
            }
        });
    }

    function showAjaxModal_confirm(url)
    {
        // SHOWING AJAX PRELOADER IMAGE
        $('#modal_confirm .modal-body').html('<div style="text-align:center;margin-top:100px;"><img src="assets/images/validate.gif" width="64px" /></div>');

        $('#modal_confirm .modal-body').html('<div style="text-align:center;margin-top:200px;"><img src="<?php echo base_url();?>assets/images/validate.gif" width="64px"/></div>');
        // LOADING THE AJAX MODAL
        $('#modal_confirm').modal({backdrop: 'static', keyboard: false});

        // SHOW AJAX RESPONSE ON REQUEST SUCCESS
        $.ajax({
            url: url,
            success: function(response)
            {
                $('#modal_confirm .modal-body').html(response);
            }
        });
    }

    //success-error
    function showAjaxModal_confirm2(message, type)
    {
        $('#modal_confirm2 .modal-content').css({
            

        });

        //change background of modal depending the confirmation type
        if(type == 'success' || type == 'Success') {
            $('#modal_confirm2 .modal-header').removeClass('bg-danger');
            $('#modal_confirm2 .modal-header').addClass('bg-success');
            $('#modal_confirm2 .modal-body').removeClass('bg-danger');
            $('#modal_confirm2 .modal-body').addClass('bg-success');

            // LOADING THE AJAX MODAL
            $('#modal_confirm2').modal({backdrop: 'static', keyboard: false});
            $('#modal_confirm2 .modal-body h3').html(message);
            $('#modal_confirm2 .modal-title').html(type);

        } else if(type == 'error' || type == 'Error') {
            $('#modal_confirm2 .modal-header').removeClass('bg-success');
            $('#modal_confirm2 .modal-header').addClass('bg-danger');
            $('#modal_confirm2 .modal-body').removeClass('bg-success');
            $('#modal_confirm2 .modal-body').addClass('bg-danger');

            // LOADING THE AJAX MODAL
            $('#modal_confirm2').modal({backdrop: 'static', keyboard: false});
            $('#modal_confirm2 .modal-body h3').html(message);
            $('#modal_confirm2 .modal-title').html(type);

        } else if(type == 'loading' || type == 'Loading') {
            $('#modal_confirm2 .modal-header').removeClass('bg-danger');
            $('#modal_confirm2 .modal-header').addClass('bg-success');
            $('#modal_confirm2 .modal-body').removeClass('bg-danger');
            $('#modal_confirm2 .modal-body').addClass('bg-success');

            // LOADING THE AJAX MODAL
            $('#modal_confirm2').modal({backdrop: 'static', keyboard: false});
            $('#modal_confirm2 .modal-body h3').html(message);
            $('#modal_confirm2 .modal-title').html(type);

            $('#modal_confirm2 .modal-body').append('<div style="text-align:center;margin-top:10px;"><img src="<?php echo base_url(); ?>assets/images/validate.gif" width="34px"/></div>');
        }

    } 


    //prompt
    function showAjaxModal_prompt(message)
    {
        // SHOWING AJAX PRELOADER IMAGE
        //$('#modal_prompt .modal-body').addClass('bg-success');

        // LOADING THE AJAX MODAL
        $('#modal_prompt').modal({backdrop: 'static', keyboard: false});
        $('#modal_prompt .modal-body h3').html(message);
        
    }

    function showAjaxModal_preloader()
    {
        // LOADING THE AJAX MODAL
        $('#modal_preloader').modal({backdrop: 'static', keyboard: false});
        
    }

    //success-error
    function showAjaxModal_alert(message, type)
    {
        // SHOWING AJAX PRELOADER IMAGE
        $('.modal-header .close').css({
            marginTop: '-82px',
            opacity: .8,

        });
        //change background of modal depending the confirmation type
        if(type == 'success' || type == 'Success' || type == 'Logged Out') {
            /*$('#modal_alert .modal-header').removeClass('bg-danger');
            $('#modal_alert .modal-header').removeClass('bg-warning');
            $('#modal_alert .modal-header').removeClass('bg-info');
            $('#modal_alert .modal-header').addClass('bg-success');
            $('#modal_alert .modal-body').removeClass('bg-danger');
            $('#modal_alert .modal-body').removeClass('bg-info');
            $('#modal_alert .modal-body').removeClass('bg-warning');
            $('#modal_alert .modal-body').addClass('bg-success');*/

            // LOADING THE AJAX MODAL
            $('#modal_alert .modal-header button').css('display','block');
            $('#modal_alert .modal-header center').html('<img src="<?= base_url('/assets/icons/icon-success.png') ?>" width="80px" height="80px"/>');
            $('#modal_alert').modal({backdrop: 'static', keyboard: false});
            $('#modal_alert .modal-body h3').html(message);
            //$('#modal_alert .modal-title').html(type);


        } else if(type == 'error' || type == 'Error') {
            /*$('#modal_alert .modal-header').removeClass('bg-success');
            $('#modal_alert .modal-header').removeClass('bg-warning');
            $('#modal_alert .modal-header').addClass('bg-danger');
            $('#modal_alert .modal-body').removeClass('bg-success');
            $('#modal_alert .modal-body').removeClass('bg-warning');
            $('#modal_alert .modal-body').addClass('bg-danger');*/

            // LOADING THE AJAX MODAL
            $('#modal_alert .modal-header button').css('display','block');
            $('#modal_alert .modal-header center').html('<img src="<?= base_url('/assets/icons/icon-cancel.png') ?>" width="80px" height="80px"/>');
            $('#modal_alert').modal({backdrop: 'static', keyboard: false});
            $('#modal_alert .modal-body h3').html(message);
            //$('#modal_alert .modal-title').html(type);

        } else if(type == 'warning' || type == 'Warning') {
            /*$('#modal_alert .modal-header').removeClass('bg-success');
            $('#modal_alert .modal-header').removeClass('bg-danger');
            $('#modal_alert .modal-header').addClass('bg-warning');
            $('#modal_alert .modal-body').removeClass('bg-success');
            $('#modal_alert .modal-body').removeClass('bg-danger');
            $('#modal_alert .modal-body').addClass('bg-warning');*/

            // LOADING THE AJAX MODAL
            $('#modal_alert .modal-header button').css('display','block');
            $('#modal_alert .modal-header center').html('<img src="<?= base_url('/assets/icons/icon-warning.png') ?>" width="80px" height="80px"/>');
            $('#modal_alert').modal({backdrop: 'static', keyboard: false});
            $('#modal_alert .modal-body h3').html(message);
           // $('#modal_alert .modal-title').html(type);

        } else if(type == 'loading' || type == 'Loading') {
            /*$('#modal_alert .modal-header').removeClass('bg-danger');
            $('#modal_alert .modal-header').removeClass('bg-success');
            $('#modal_alert .modal-header').removeClass('bg-warning');
            $('#modal_alert .modal-header').addClass('bg-info');
            $('#modal_alert .modal-body').removeClass('bg-danger');
            $('#modal_alert .modal-body').removeClass('bg-success');
            $('#modal_alert .modal-body').removeClass('bg-warning');
            $('#modal_alert .modal-body').addClass('bg-info');*/

            // LOADING THE AJAX MODAL
            $('#modal_alert .modal-header button').css('display','none');
            $('#modal_alert .modal-header center').html('<img src="<?= base_url('/assets/loaders/icons8-dots-loader.gif') ?>" width="80px" height="80px"/>');
            $('#modal_alert').modal({backdrop: 'static', keyboard: false});
            $('#modal_alert .modal-body h3').html(message);
            //$('#modal_alert .modal-title').html(type);
        }
    }

    function showAjaxModal_confirm_staff(url)
    {
        // SHOWING AJAX PRELOADER IMAGE
        $('#modal_confirm_staff .modal-body').html('<div style="text-align:center;margin-top:100px;"><img src="assets/images/validate.gif" width="64px" /></div>');

        $('#modal_confirm_staff .modal-body').html('<div style="text-align:center;margin-top:200px;"><img src="<?php echo base_url();?>assets/images/validate.gif" width="64px"/></div>');
        // LOADING THE AJAX MODAL
        $('#modal_confirm_staff').modal({backdrop: 'static', keyboard: false});

        // SHOW AJAX RESPONSE ON REQUEST SUCCESS
        $.ajax({
            url: url,
            success: function(response)
            {
                $('#modal_confirm_staff .modal-body').html(response);
            }
        });
    }

    function showAjaxModal_idle_user(url)
    {
        // SHOWING AJAX PRELOADER IMAGE
        $('#modal_idle_user .modal-body').html('<div style="text-align:center;margin-top:100px;"><img src="assets/images/validate.gif" width="64px" /></div>');

        $('#modal_idle_user .modal-body').html('<div style="text-align:center;margin-top:200px;"><img src="<?php echo base_url();?>assets/images/validate.gif" width="64px"/></div>');
        // LOADING THE AJAX MODAL
        $('#modal_idle_user').modal({backdrop: 'static', keyboard: false});

        // SHOW AJAX RESPONSE ON REQUEST SUCCESS
        $.ajax({
            url: url,
            success: function(response)
            {
                $('#modal_idle_user .modal-body').html(response);
            }
        });
    }

    function showAjaxModal_user_blocked(url)
    {
        // SHOWING AJAX PRELOADER IMAGE
        $('#modal_user_blocked .modal-body').html('<div style="text-align:center;margin-top:100px;"><img src="assets/images/validate.gif" width="64px" /></div>');

        $('#modal_user_blocked .modal-body').html('<div style="text-align:center;margin-top:200px;"><img src="<?php echo base_url();?>assets/images/validate.gif" width="64px"/></div>');
        // LOADING THE AJAX MODAL
        $('#modal_user_blocked').modal({backdrop: 'static', keyboard: false});

        // SHOW AJAX RESPONSE ON REQUEST SUCCESS
        $.ajax({
            url: url,
            success: function(response)
            {
                $('#modal_user_blocked .modal-body').html(response);
            }
        });
    }
    
    </script>

    <!-- (Confirm Ajax modal_move_student)-->
    <div class="modal fade" id="modal_move_student">
        <div class="modal-dialog">
            <div class="modal-content">

                <div class="modal-header" style="background-color: red">
                    <h4 class="modal-title" style="color: #ffffff; text-align: center;">Please Select The Class</h4>
                </div>

                <div class="modal-body"></div>

                <div class="modal-footer">
                    <div class="col-sm-6 col-md-6 col-xs-6">
                        <button type="button" id="modal_move_student_move" class="btn btn-success btn-lg">Move</button>
                    </div>    
                    <div class="col-sm-6 col-md-6 col-xs-6" style="text-align: left;">
                        <button type="button" class="btn btn-danger btn-lg" id="modal_move_student_cancel"  data-dismiss="modal">Cancel</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- (Ajax Modal)-->
    <div class="modal fade" id="modal_ajax">
        <div class="modal-dialog">
            <div class="modal-content">

                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                    <h4 class="modal-title"><?php echo $system_name;?></h4>
                </div>

                <div class="modal-body" style="height:500px; overflow:auto;">



                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modal_display">
        <div class="modal-dialog">
            <div class="modal-content">

                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                    
                </div>

                <div class="modal-body"></div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- (Ajax Modal Receipt)-->
    <div class="modal fade" id="modal_ajax_receipt">
        <div class="modal-dialog">
            <div class="modal-content">

                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                    <h4 class="modal-title"><?php echo $system_name;?></h4>
                </div>

                <div class="modal-body" style="height:500px; overflow:auto;">


                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>


    <div class="modal fade" id="selection">
        <div class="modal-dialog">
            <div class="modal-content" style="margin-top: 35vh;">

                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                    <h4 class="modal-title"><?php echo $system_name;?></h4>
                </div>

                <div class="modal-body"></div>

                <div class="modal-footer">
                    <!--<button type="button" class="btn btn-danger" data-dismiss="modal">Close</button>-->
                </div>
            </div>
        </div>
    </div>

    <!-- (Confirm Ajax Modal)-->
    <div class="modal fade" id="modal_confirm">
        <div class="modal-dialog">
            <div class="modal-content">

                <div class="modal-header" style="background-color: red">
                    <h4 class="modal-title" style="color: #ffffff; text-align: center;">Duplicates Found!</h4>
                </div>

                <div class="modal-body"></div>

                <div class="modal-footer">
                    <div class="col-sm-6 col-md-6 col-xs-6">
                        <button type="button" id="modal_yes" class="btn btn-success btn-lg">Yes</button>
                    </div>    
                    <div class="col-sm-6 col-md-6 col-xs-6" style="text-align: left;">
                        <button type="button" class="btn btn-danger btn-lg" id="modal_no"  data-dismiss="modal">No</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- (Confirm Ajax Modal)-->
    <div class="modal fade" id="modal_confirm_staff">
        <div class="modal-dialog">
            <div class="modal-content">

                <div class="modal-header" style="background-color: red">
                    <h4 class="modal-title" style="color: #ffffff; text-align: center;">Duplicates Found!</h4>
                </div>

                <div class="modal-body"></div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- (Confirm Ajax modal_idle_user)-->
    <div class="modal fade" id="modal_idle_user">
        <div class="modal-dialog">
            <div class="modal-content">

                <div class="modal-header" style="background-color: green">
                    <h4 class="modal-title" style="color: #ffffff; text-align: center;">You have been logged out!</h4>
                </div>

                <div class="modal-body"></div>

                <div class="modal-footer">   
                    <div class="col-sm-12 col-md-12 col-xs-12" style="text-align: cneter;">
                        <button type="button" class="btn btn-success btn-lg"  data-dismiss="modal">Login here</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- (Confirm Ajax modal_user_blocked)-->
    <div class="modal fade" id="modal_user_blocked">
        <div class="modal-dialog">
            <div class="modal-content">

                <div class="modal-header" style="background-color: red">
                    <h4 class="modal-title" style="color: #ffffff; text-align: center;">Your Account Has Been Blocked!</h4>
                </div>

                <div class="modal-body"></div>

                <div class="modal-footer">   
                    <div class="col-sm-12 col-md-12 col-xs-12" style="text-align: cneter;">
                        <button type="button" class="btn btn-danger btn-lg"  data-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    </div>




    <script type="text/javascript">
    function confirm_modal(delete_url, modal_type, type, class_id = '')
    {
        if (modal_type === 'generic_confirmation') {
            $('#modal-generic_confirmation').modal('show', {backdrop: 'static'});
            document.getElementById('update_link').setAttribute('href' , delete_url);
        } else if(modal_type === 'modal_block') {
            $('#modal_block').modal('show', {backdrop: 'static'});
            
            $('#block_link').click(function(e) {

                $('.close').click();
                showAjaxModal_alert('<center><div style="font-size: 20px; font-weight: bolder;">Processing Data...<br><i class="fa fa-3x fa-spinner fa-pulse"></i></div></center>', 'Loading');

                $.ajax({
                    url: delete_url,
                    type: 'POST',
                    dataType: 'html',
                })
                .done(function(response) {

                    //========start
                    showAjaxModal_alert('Account Blocked Successfully', 'Success');

                    

                    setTimeout(() => {
                        navigation('<?php echo site_url('admin/'); ?>' + type + '/' + class_id);
                        $('.modal').removeClass('modal-backdrop');
                        $('.modal').removeClass('fade');
                        $('.modal').removeClass('in');
                        $('.close').click();
                    }, 5000);
                    ///=======end
                })
                .fail(function(err) {
                    showAjaxModal_alert('Error: ' + err.responseText, 'Error');
                });
                
            })

            

        } else if(modal_type === 'modal_unblock') {
            $('#modal_unblock').modal('show', {backdrop: 'static'});
            
             $('#unblock_link').click(function(e) {

                $('.close').click();
                showAjaxModal_alert('<center><div style="font-size: 20px; font-weight: bolder;">Processing Data...<br><i class="fa fa-3x fa-spinner fa-pulse"></i></div></center>', 'Loading');

                $.ajax({
                    url: delete_url,
                    type: 'POST',
                    dataType: 'html',
                })
                .done(function(response) {

                    //========start
                    showAjaxModal_alert('Account Unblocked Successfully', 'Success');

                    

                    setTimeout(() => {
                        navigation('<?php echo site_url('admin/'); ?>' + type + '/' + class_id);
                        $('.modal').removeClass('modal-backdrop');
                        $('.modal').removeClass('fade');
                        $('.modal').removeClass('in');
                        $('.close').click();
                    }, 5000);
                    ///=======end
                })
                .fail(function(err) {
                    showAjaxModal_alert('Error: ' + err.responseText, 'Error');
                });
                
            })

        } else if(modal_type === 'modal_mute') {
            $('#modal_mute').modal('show', {backdrop: 'static'});

            $('#mute_link').click(function(e) {

                $('.close').click();
                showAjaxModal_alert('<center><div style="font-size: 20px; font-weight: bolder;">Processing Data...<br><i class="fa fa-3x fa-spinner fa-pulse"></i></div></center>', 'Loading');

                $.ajax({
                    url: delete_url,
                    type: 'POST',
                    dataType: 'html',
                })
                .done(function(response) {

                    //========start
                    showAjaxModal_alert('Student Muted Successfully', 'Success');

                    

                    setTimeout(() => {
                        navigation('<?php echo site_url('admin/'); ?>' + type + '/' + class_id);
                        $('.modal').removeClass('modal-backdrop');
                        $('.modal').removeClass('fade');
                        $('.modal').removeClass('in');
                        $('.close').click();
                    }, 5000);
                    ///=======end
                })
                .fail(function(err) {
                    showAjaxModal_alert('Error: ' + err.responseText, 'Error');
                });
                
            })

        } else if(modal_type === 'modal_unmute') {
            $('#modal_unmute').modal('show', {backdrop: 'static'});

            $('#unmute_link').click(function(e) {

                let class_id = $('#class_id_enrollment').val();

                if(class_id == '') {
                    showAjaxModal_alert('Please select a class.', 'Error');

                    setTimeout(() => {
                        $('#modal_alert .close').click();
                    }, 3000);

                    return false;
                }

                $('.close').click();
                showAjaxModal_alert('<center><div style="font-size: 20px; font-weight: bolder;">Processing Data...<br><i class="fa fa-3x fa-spinner fa-pulse"></i></div></center>', 'Loading');

                $.ajax({
                    url: delete_url + '/' + class_id,
                    type: 'POST',
                    dataType: 'html',
                })
                .done(function(response) {

                    //========start
                    showAjaxModal_alert('Student Unmuted Successfully', 'Success');

                    

                    setTimeout(() => {
                        navigation('<?php echo site_url('admin/'); ?>' + type + '/' + class_id);
                        $('.modal').removeClass('modal-backdrop');
                        $('.modal').removeClass('fade');
                        $('.modal').removeClass('in');
                        $('.close').click();
                    }, 5000);
                    ///=======end
                })
                .fail(function(err) {
                    showAjaxModal_alert('Error: ' + err.responseText, 'Error');
                });
                
            })

        } else if(modal_type === 'modal_exam_delete') {
            $('#modal_exam_delete').modal('show', {backdrop: 'static'});

            $('#delete_exam_link').click(function(event) {
                /* Act on the event */
                let final_conf = confirm('Please Are You Really Sure You Want To Do This?');
                if(final_conf == 1) {
                    $('.close').click();
                    $.ajax({
                          url: delete_url,
                          type: 'POST',
                          dataType: 'html',
                      })
                      .done(function() {
                          showAjaxModal_alert('Exam deleted successfully.', 'Success');
                          

                          setTimeout(() => {
                              $('.close').click();
                              navigation('<?php echo site_url('admin/exam'); ?>');
                              //window.location.reload();
                          }, 3000);
                      })
                      .fail(function(err) {
                          showAjaxModal_alert(err.responseText, 'Error');
                      });

                } else {
                    return false;
                }
            });

            
        } else if(modal_type === 'modal_invoice_delete') {
            $('#modal_invoice_delete').modal('show', {backdrop: 'static'});

            $('#delete_invoice_link').click(function(event) {
                /* Act on the event */
                let final_conf = confirm('Please Are You Really Sure You Want To Do This?');
                if(final_conf == 1) {
                    document.getElementById('delete_invoice_link').setAttribute('href' , delete_url);
                } else {
                    return false;
                }
            });

        } else if(modal_type === 'modal_invoice_warning') {
            $('#modal_invoice_warning').modal('show', {backdrop: 'static'});
            //document.getElementById('delete_invoice_link').setAttribute('href' , delete_url);
        } else{
            $('#modal-4').modal('show', {backdrop: 'static'});
            //document.getElementById('delete_link').setAttribute('href' , delete_url);

            $('#delete_link').click(function(event) {
                /* Act on the event */
                $('.close').click();
                showAjaxModal_alert('Please wait...<i class="fa fa-3x fa-spinner fa-pulse"></i>.', 'Loading');
                $.ajax({
                      url: delete_url,
                      type: 'POST',
                      dataType: 'text',
                  })
                  .done(function(response) {
                      if(response == 'done') {
                        
                        showAjaxModal_alert('Data deleted successfully.', 'Success');
                      

                          setTimeout(() => {
                              $('.close').click();
                              navigation('<?php echo site_url('admin/parent'); ?>');
                              //window.location.reload();
                          }, 3000); 
                      } else {
                        showAjaxModal_alert('Data not deleted!', 'Error');
                      }
                  })
                  .fail(function(err) {
                      showAjaxModal_alert(err.responseText, 'Error');
                  });
            });


        }
       
    }
    </script>

    <!-- (Normal Modal)-->
    <div class="modal fade" id="modal-4">
        <div class="modal-dialog">
            <div class="modal-content" style="margin-top:100px;">

                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                    <h4 class="modal-title" style="text-align:center;">Are you sure you want to delete this information ?</h4>
                </div>


                <div class="modal-footer" style="margin:0px; border-top:0px; text-align:center;">
                    <a href="#" class="btn btn-info" id="delete_link"><?php echo get_phrase('delete');?></a>
                    <button type="button" class="btn btn-danger" data-dismiss="modal"><?php echo get_phrase('cancel');?></button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- (Exam Delete Modal)-->
    <div class="modal fade" id="modal_exam_delete">
        <div class="modal-dialog">
            <div class="modal-content" style="margin-top: 35vh;">

                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                    <h3 class="modal-title" style="color: red;text-align:center;">EXAMINATION DELETION WARNING!!!</h3>
                    <p style="color: red; font-weight: bolder; font-size: 14px;">If you delete this examination, all records of each student related to this examination will be deleted completely and cannot be redone. We believe you know what you are doing, if not, please click CANCEL!</p>
                </div>


                <div class="modal-footer" style="margin:0px; border-top:0px; text-align:center;">
                    <a href="#" class="btn btn-info" id="delete_exam_link"><?php echo get_phrase('delete');?></a>
                    <button type="button" class="btn btn-danger" data-dismiss="modal"><?php echo get_phrase('cancel');?></button>
                </div>
            </div>
        </div>
    </div>

    <!-- (Invoice Delete Modal)-->
    <div class="modal fade" id="modal_invoice_delete">
        <div class="modal-dialog">
            <div class="modal-content" style="margin-top: 35vh;">

                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                    <h3 class="modal-title" style="color: red;text-align:center;">INVOICE DELETION WARNING!!!</h3>
                    <p style="color: red; font-weight: bolder; font-size: 14px;">If you delete this INVOICE, all records of this student related to this INVOICE and RECEIPTS will be deleted completely and cannot be redone. We believe you know what you are doing, if not, please click CANCEL!</p>
                </div>


                <div class="modal-footer" style="margin:0px; border-top:0px; text-align:center;">
                    <a href="#" class="btn btn-info" id="delete_invoice_link"><?php echo get_phrase('delete');?></a>
                    <button type="button" class="btn btn-danger" data-dismiss="modal"><?php echo get_phrase('cancel');?></button>
                </div>
            </div>
        </div>
    </div>

     <!-- (Invoice Delete Modal)-->
    <div class="modal fade" id="modal_invoice_warning">
        <div class="modal-dialog">
            <div class="modal-content" style="margin-top: 35vh;">

                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                    <h3 class="modal-title" style="text-align:center; color: red;">INVOICE DELETION PROHIBITED!!!</h3>
                    <p style="color: red; font-weight: bolder; font-size: 14px;">Sorry, you are not allowed to perform this action. Kindly contact the System Super Administrator for permission!</p>
                </div>


                <div class="modal-footer" style="margin:0px; border-top:0px; text-align:center;">
                    <button type="button" class="btn btn-danger" data-dismiss="modal"><?php echo get_phrase('cancel');?></button>
                </div>
            </div>
        </div>
    </div>

    <!-- (Block Modal)-->
    <div class="modal fade" id="modal_block">
        <div class="modal-dialog">
            <div class="modal-content" style="margin-top:35vh;">

                <div class="modal-header" style="background-color: red">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                    <h4 class="modal-title" style="text-align:center; color: #ffffff;"><i class="fa fa-lock"></i> Are you sure you want to BLOCK this student? </h4>
                </div>


                <div class="modal-footer" style="margin:0px; border-top:0px; text-align:center;">
                    <a href="#" class="btn btn-info" id="block_link"><?php echo get_phrase('Yes');?></a>
                    <button type="button" class="btn btn-danger" data-dismiss="modal"><?php echo get_phrase('No');?></button>
                </div>
            </div>
        </div>
    </div>

     <!-- (Unblock Modal)-->
    <div class="modal fade" id="modal_unblock">
        <div class="modal-dialog">
            <div class="modal-content" style="margin-top: 35vh;">

                <div class="modal-header" style="background-color: green">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                    <h4 class="modal-title" style="text-align:center; color: #ffffff;"><i class="fa fa-unlock"></i> Are you sure you want to UNBLOCK this student? </h4>
                </div>


                <div class="modal-footer" style="margin:0px; border-top:0px; text-align:center;">
                    <a href="#" class="btn btn-info" id="unblock_link"><?php echo get_phrase('Yes');?></a>
                    <button type="button" class="btn btn-danger" data-dismiss="modal"><?php echo get_phrase('No');?></button>
                </div>
            </div>
        </div>
    </div>

    <!-- (Mute Modal)-->
    <div class="modal fade" id="modal_mute">
        <div class="modal-dialog">
            <div class="modal-content" style="margin-top: 35vh;">

                <div class="modal-header" style="background-color: red">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                    <h4 class="modal-title" style="text-align:center; color: #ffffff;"><i class="fa fa-remove"></i> Are you sure you want to MUTE this student? </h4>
                </div>

                <div class="modal-body">
                    <p>If you perform this action, this student will be remove from your current administration. Records will not be deleted.</p>
                </div>

                <div class="modal-footer" style="margin:0px; border-top:0px; text-align:center;">
                    <a href="#" class="btn btn-info" id="mute_link"><?php echo get_phrase('Yes');?></a>
                    <button type="button" class="btn btn-danger" data-dismiss="modal"><?php echo get_phrase('No');?></button>
                </div>
            </div>
        </div>
    </div>

     <!-- (Unmute Modal)-->
    <div class="modal fade" id="modal_unmute">
        <div class="modal-dialog">
            <div class="modal-content" style="margin-top: 35vh;">

                <div class="modal-header" style="background-color: green">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                    <h4 class="modal-title" style="text-align:center; color: #ffffff;"><i class="fa fa-ok"></i> Are you sure you want to UNMUTE this student? </h4>
                </div>

                <div class="modal-body">
                    <p>If you perform this action, this student will be add to your current administration.</p>

                    <div class="form-group row">
                        <div class="col-md-6">
                            <div class="form-group">
                            <label class="control-label" style="margin-bottom: 5px;"><?php echo get_phrase('Select class for enrollment');?></label>
                                <select name="class_id_enrollment" id="class_id_enrollment" class="form-control select2">
                                    <option value=""><?php echo get_phrase('select_class');?></option>
                                    <?php

                    getFullClassList();
                ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer" style="margin:0px; border-top:0px; text-align:center;">
                    <a href="#" class="btn btn-info" id="unmute_link"><?php echo get_phrase('Yes');?></a>
                    <button type="button" class="btn btn-danger" data-dismiss="modal"><?php echo get_phrase('No');?></button>
                </div>
            </div>
        </div>
    </div>

    <!-- (generic_confirmation Modal)-->
    <div class="modal fade" id="modal-generic_confirmation">
        <div class="modal-dialog">
            <div class="modal-content" style="margin-top: 35vh;">

                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                    <h4 class="modal-title" style="text-align:center;"><?php echo get_phrase('are_you_sure_you_want_to_update_this_information'); ?> ?</h4>
                </div>


                <div class="modal-footer" style="margin:0px; border-top:0px; text-align:center;">
                    <a href="#" class="btn btn-info" id="update_link"><?php echo get_phrase('yes');?></a>
                    <button type="button" class="btn btn-danger" data-dismiss="modal"><?php echo get_phrase('no');?></button>
                </div>
            </div>
        </div>
    </div>

    <!-- (Modal)-->
    <div class="modal fade" id="modal_u">
        <div class="modal-dialog">
            <div class="modal-content" style="margin-top: 35vh;">

                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                    <h4 class="modal-title" style="text-align:center;"><?php echo 'The same parent\'s details exist. Are you sure you want to add this name?'; ?> ?</h4>
                </div>


                <div class="modal-footer" style="margin:0px; border-top:0px; text-align:center;">
                    <a href="#" class="btn btn-info" id="update_link"><?php echo get_phrase('yes');?></a>
                    <button type="button" class="btn btn-danger" data-dismiss="modal"><?php echo get_phrase('no');?></button>
                </div>
            </div>
        </div>
    </div>

     <!-- success or error Modal-->
  <div class="modal fade" id="modal_confirm2" tabindex="-1" role="dialog" aria-labelledby="calendar-modal" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h3 class="modal-title text-light" style="color: #fff"></h3>
          <button class="close btn-danger" type="button" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true"><i class="fa fa-fw fa-md fa-close"></i></span>
          </button>
          <hr>
        </div>
        <div class="modal-body"><h3 class="text-light" style="color: #fff"></h3></div>
      </div>
    </div>
  </div>


  <!-- success or error Modal-->
  <div class="modal fade" id="modal_alert" data-backdrop="static" data-keyboard="false" tabindex="-1" role="dialog" aria-labelledby="calendar-modal" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document" style="margin-top: 0px">
      <div class="modal-content" style="margin-top: 20vh">
        <div class="modal-header">
          <center></center>
          <button class="close btn-danger" type="button" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true"><i class="fa fa-fw fa-close"></i></span>
          </button>
        </div>
        <div class="modal-body">
            <h3 class="text-light" style="text-align: center; color: #545151"></h3>
            
        </div>
      </div>
    </div>
  </div>

  <div class="modal fade" id="modal_prompt" data-backdrop="static" data-keyboard="false" tabindex="-1" role="dialog" aria-labelledby="modal_prompt-modal" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document" style="margin-top: 0px">
      <div class="modal-content" style="margin-top: 20vh">
        <div class="modal-header">
            <center>
                <img src="<?= base_url('/assets/icons/icon-warning.png') ?>" width="80px" height="80px"/>
            </center>
        </div>
        <div class="modal-body">
            <h3 class="text-light" style="text-align: center; color: #545151"></h3>
            
        </div>
        <div class="modal-footer form-group row">
            <div class="col-lg-7 col-md-7 col-sm-7 col-xs-5"></div>
            <div class="col-lg-5 col-md-5 col-sm-5 col-xs-7">
                <div class="col-lg-6 col-md-6 col-sm-6 col-xs-6">
                    <button class="btn btn-success btn-lg" id="prompt_yes">YES</button>
                </div>
                <div class="col-lg-6 col-md-6 col-sm-6 col-xs-6">
                    <button class="btn btn-danger btn-lg" id="prompt_cancel" data-dismiss="modal" aria-label="Cancel">CANCEL</button>
                </div>
            </div>
            
        </div>
      </div>
    </div>
  </div>


  <div class="modal fade" data-backdrop="static" data-keyboard="false" tabindex="-1" id="modal_preloader" style="z-index: 999999">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="margin-top: 5vh">

                <div class="modal-header" style="background-color: #7a7b7b">
                    <h4 class="modal-title" style="color: #ffffff; text-align: center;">Please Wait... <img src="<?php echo base_url(); ?>assets/images/validate.gif" width="20px"/></h4>
                </div>

                <div class="modal-body">
                    <center>
                    <div><img style="position: relative;" src="<?php echo base_url();?>assets/images/lightworldtech.png" width="100px"><img style="position: relative;" src="<?php echo base_url();?>assets/images/validate.gif" width="64px"><p style="position: relative;" style="padding-top: 15px; font-weight: bold;">Loading, please wait<span id="dot1">.</span><span id="dot2">.</span><span id="dot3">.</span></p>
                    </div></center>

                </div>

                <div class="modal-footer" align="center" style="background-color: #d0d0d0">
                    <center>
                        <div>
                            <small>Powered by: Lightworld Technologies Limited - Ghana</small>
                        </div>
                    </center>
                    
                </div>
            </div>
        </div>
    </div>

<div class="modal fade" id="loading" tabindex="-1" role="dialog" aria-labelledby="calendar-modal" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document" style="margin-top: 0px">
      <div class="modal-content" style="margin-top: 35vh">
        <div class="modal-header">
          <h2 class="modal-title" style="color: #fff"></h2>
          <button class="close btn-danger" type="button" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true"><i class="fa fa-fw fa-close"></i></span>
          </button>
        </div><hr>
        <div class="modal-body">
            <h3 class="text-light" style="color: #fff"></h3>
            <div style="text-align:center;margin-top:10px;"><img src="<?php echo base_url(); ?>assets/images/validate.gif" width="34px"/></div>
        </div>
      </div>
    </div>
  </div>
