<?php
$theme_color_row = $this->db->get_where('settings', array('type' => 'theme_color'))->row();
$theme_color = $theme_color_row ? $theme_color_row->description : '667eea';
if(strpos($theme_color, '#') !== 0) {
    $theme_color = '#' . $theme_color;
}

function adjustBrightness($hex, $steps) {
    $hex = str_replace('#', '', $hex);
    if(strlen($hex) == 3) {
        $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
    }
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));
    $r = max(0, min(255, $r + $steps));
    $g = max(0, min(255, $g + $steps));
    $b = max(0, min(255, $b + $steps));
    return '#' . str_pad(dechex($r), 2, '0', STR_PAD_LEFT) . str_pad(dechex($g), 2, '0', STR_PAD_LEFT) . str_pad(dechex($b), 2, '0', STR_PAD_LEFT);
}
$gradient_light = adjustBrightness($theme_color, 20);
$gradient_dark = adjustBrightness($theme_color, -30);
?>
<style>
:root {
    --theme-primary: <?php echo $theme_color; ?>;
    --theme-light: <?php echo $gradient_light; ?>;
    --theme-dark: <?php echo $gradient_dark; ?>;
}
/* Modern Modal Animations */
@keyframes modalFadeIn {
    from {
        opacity: 0;
        transform: scale(0.9) translateY(-20px);
    }
    to {
        opacity: 1;
        transform: scale(1) translateY(0);
    }
}

@keyframes modalFadeOut {
    from {
        opacity: 1;
        transform: scale(1) translateY(0);
    }
    to {
        opacity: 0;
        transform: scale(0.9) translateY(-20px);
    }
}

@keyframes backdropFadeIn {
    from { opacity: 0; }
    to { opacity: 0.5; }
}

@keyframes spinLoader {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.modal.fade .modal-dialog {
    transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1), opacity 0.3s ease;
    transform: scale(0.9) translateY(-20px);
}

.modal.fade.in .modal-dialog {
    transform: scale(1) translateY(0);
}

.modal-backdrop.fade {
    transition: opacity 0.3s ease;
}

.modern-modal-content {
    border-radius: 16px;
    border: none;
    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
    overflow: hidden;
}

.modern-modal-header {
    background: linear-gradient(135deg, var(--theme-light) 0%, var(--theme-dark) 100%);
    border: none;
    padding: 24px 30px;
    position: relative;
}

.modern-modal-header-danger {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
}

.modern-modal-header-success {
    background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
}

.modern-modal-header-warning {
    background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%);
}

.modern-close {
    color: white !important;
    opacity: 0.9 !important;
    font-size: 32px !important;
    font-weight: 300 !important;
    text-shadow: none !important;
    transition: all 0.3s ease !important;
    position: absolute !important;
    right: 20px !important;
    top: 20px !important;
    z-index: 10 !important;
}

.modern-close:hover {
    opacity: 1 !important;
    transform: rotate(90deg) !important;
}

.modern-btn {
    padding: 12px 28px;
    border-radius: 10px;
    font-weight: 600;
    transition: all 0.3s ease;
    border: none;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.modern-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(0,0,0,0.2);
}

.modern-btn-primary {
    background: linear-gradient(135deg, var(--theme-light) 0%, var(--theme-dark) 100%);
    color: white;
}

.modern-btn-danger {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    color: white;
}

.modern-btn-success {
    background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
    color: white;
}

.modern-btn-cancel {
    background: white;
    color: #7f8c8d;
    border: 2px solid #bdc3c7;
}

.loader-spinner {
    border: 4px solid #f3f3f3;
    border-top: 4px solid var(--theme-primary);
    border-radius: 50%;
    width: 50px;
    height: 50px;
    animation: spinLoader 1s linear infinite;
    margin: 10px auto;
}

.modal-xl {
    width: 95%;
    max-width: 1400px;
}

@media (min-width: 1200px) {
    .modal-xl {
        width: 90%;
    }
}
</style>


<!-- patch -->
    <!-- (Confirm Ajax modal_move_student)-->

    <div class="modal fade" id="modal_display">
        <div class="modal-dialog">
            <div class="modal-content">

                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                    
                </div>

                <div class="modal-body"></div>

                <div class="modal-footer" style="border-top:1px solid #ecf0f1;padding:20px 30px;background:#f8f9fa;text-align:center;">
                    <button type="button" class="btn modern-btn modern-btn-danger" data-dismiss="modal" style="font-size:15px;font-weight:600;min-width:100px;">Close</button>
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

                <div class="modal-footer" style="border-top:1px solid #ecf0f1;padding:20px 30px;background:#f8f9fa;text-align:center;">
                    <button type="button" class="btn modern-btn modern-btn-danger" data-dismiss="modal" style="font-size:15px;font-weight:600;min-width:100px;">Close</button>
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

                <div class="modal-footer" style="border-top:1px solid #ecf0f1;padding:20px 30px;background:#f8f9fa;text-align:center;">   
                    <div class="col-sm-12 col-md-12 col-xs-12" style="text-align: center;">
                        <button type="button" class="btn modern-btn modern-btn-success btn-lg" data-dismiss="modal" style="font-size:15px;font-weight:600;">Login here</button>
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

                <div class="modal-footer" style="border-top:1px solid #ecf0f1;padding:20px 30px;background:#f8f9fa;text-align:center;">   
                    <div class="col-sm-12 col-md-12 col-xs-12" style="text-align: center;">
                        <button type="button" class="btn modern-btn modern-btn-danger btn-lg" data-dismiss="modal" style="font-size:15px;font-weight:600;">Close</button>
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

                $('.modal').removeClass('modal-backdrop');
                $('.modal').removeClass('fade');
                $('.modal').removeClass('in');

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

                $('.modal').removeClass('modal-backdrop');
                $('.modal').removeClass('fade');
                $('.modal').removeClass('in');

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

                $('.modal').removeClass('modal-backdrop');
                $('.modal').removeClass('fade');
                $('.modal').removeClass('in');

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

                $('.modal').removeClass('modal-backdrop');
                $('.modal').removeClass('fade');
                $('.modal').removeClass('in');

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
                showCustomConfirm(
                    'Please Are You Really Sure You Want To Do This?',
                    function() {
                        // On Yes - proceed with deletion
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
                    }
                    // On No - do nothing (modal closes automatically)
                );
            });

            
        } else if(modal_type === 'modal_invoice_delete') {
            $('#modal_invoice_delete').modal('show', {backdrop: 'static'});

            $('#delete_invoice_link').click(function(event) {
                /* Act on the event */
                showCustomConfirm(
                    'Please Are You Really Sure You Want To Do This?',
                    function() {
                        // On Yes - proceed with deletion
                        $('.close').click();
                        document.getElementById('delete_invoice_link').setAttribute('href' , delete_url);

                        showAjaxModal_alert('DELETING INVOICE(S). PLEASE WAIT...', 'Loading');
                    }
                    // On No - do nothing
                );
            });

        } else if(modal_type === 'modal_invoice_warning') {
          
            
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
                      dataType: 'json',
                  })
                  .done(function(response) {
                      if(response.message == 'done') {
                        
                        showAjaxModal_alert('Data deleted successfully.', 'Success');
                      

                          setTimeout(() => {
                              $('.close').click();
                              if(response.route == 'student') {
                                location.reload();
                              } else {
                                navigation('<?php echo site_url('admin/'); ?>' + response.route);
                              }
                              
                              //window.location.reload();
                          }, 3000); 
                      } else {
                        showAjaxModal_alert('Data not deleted!<br><br>' + response.hasRecords, 'Error');
                        $('.close').click(function(e) {
                            location.reload();
                        });
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


                <div class="modal-footer" style="margin:0px; border-top:1px solid #ecf0f1; text-align:center;padding:20px 30px;background:#f8f9fa;">
                    <a href="#" class="btn modern-btn modern-btn-primary" id="delete_link" style="font-size:15px;font-weight:600;min-width:100px;"><?php echo get_phrase('delete');?></a>
                    <button type="button" class="btn modern-btn modern-btn-cancel" data-dismiss="modal" style="font-size:15px;font-weight:600;min-width:100px;"><?php echo get_phrase('cancel');?></button>
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


                <div class="modal-footer" style="margin:0px; border-top:1px solid #ecf0f1; text-align:center;padding:20px 30px;background:#f8f9fa;">
                    <a href="#" class="btn modern-btn modern-btn-danger" id="delete_exam_link" style="font-size:15px;font-weight:600;min-width:100px;"><?php echo get_phrase('delete');?></a>
                    <button type="button" class="btn modern-btn modern-btn-cancel" data-dismiss="modal" style="font-size:15px;font-weight:600;min-width:100px;"><?php echo get_phrase('cancel');?></button>
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
                    <p style="color: red; font-weight: bolder; font-size: 14px;"><?=strtoupper('If you delete the INVOICE(s), all related records including RECEIPTS will be deleted permanently and cannot be undone. We believe you know what you are doing, if not, please click CANCEL!'); ?></p>
                </div>


                <div class="modal-footer" style="margin:0px; border-top:1px solid #ecf0f1; text-align:center;padding:20px 30px;background:#f8f9fa;">
                    <a href="#" class="btn modern-btn modern-btn-danger" id="delete_invoice_link" style="font-size:15px;font-weight:600;min-width:100px;"><?php echo get_phrase('delete');?></a>
                    <button type="button" class="btn modern-btn modern-btn-cancel" data-dismiss="modal" style="font-size:15px;font-weight:600;min-width:100px;"><?php echo get_phrase('cancel');?></button>
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


                <div class="modal-footer" style="margin:0px; border-top:1px solid #ecf0f1; text-align:center;padding:20px 30px;background:#f8f9fa;">
                    <button type="button" class="btn modern-btn modern-btn-cancel" data-dismiss="modal" style="font-size:15px;font-weight:600;min-width:100px;"><?php echo get_phrase('cancel');?></button>
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


                <div class="modal-footer" style="margin:0px; border-top:1px solid #ecf0f1; text-align:center;padding:20px 30px;background:#f8f9fa;">
                    <a href="#" class="btn modern-btn modern-btn-success" id="block_link" style="font-size:15px;font-weight:600;min-width:100px;"><?php echo get_phrase('Yes');?></a>
                    <button type="button" class="btn modern-btn modern-btn-cancel" data-dismiss="modal" style="font-size:15px;font-weight:600;min-width:100px;"><?php echo get_phrase('No');?></button>
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


                <div class="modal-footer" style="margin:0px; border-top:1px solid #ecf0f1; text-align:center;padding:20px 30px;background:#f8f9fa;">
                    <a href="#" class="btn modern-btn modern-btn-success" id="unblock_link" style="font-size:15px;font-weight:600;min-width:100px;"><?php echo get_phrase('Yes');?></a>
                    <button type="button" class="btn modern-btn modern-btn-cancel" data-dismiss="modal" style="font-size:15px;font-weight:600;min-width:100px;"><?php echo get_phrase('No');?></button>
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

                <div class="modal-footer" style="margin:0px; border-top:1px solid #ecf0f1; text-align:center;padding:20px 30px;background:#f8f9fa;">
                    <a href="#" class="btn modern-btn modern-btn-success" id="mute_link" style="font-size:15px;font-weight:600;min-width:100px;"><?php echo get_phrase('Yes');?></a>
                    <button type="button" class="btn modern-btn modern-btn-cancel" data-dismiss="modal" style="font-size:15px;font-weight:600;min-width:100px;"><?php echo get_phrase('No');?></button>
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

                <div class="modal-footer" style="margin:0px; border-top:1px solid #ecf0f1; text-align:center;padding:20px 30px;background:#f8f9fa;">
                    <a href="#" class="btn modern-btn modern-btn-success" id="unmute_link" style="font-size:15px;font-weight:600;min-width:100px;"><?php echo get_phrase('Yes');?></a>
                    <button type="button" class="btn modern-btn modern-btn-cancel" data-dismiss="modal" style="font-size:15px;font-weight:600;min-width:100px;"><?php echo get_phrase('No');?></button>
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


                <div class="modal-footer" style="margin:0px; border-top:1px solid #ecf0f1; text-align:center;padding:20px 30px;background:#f8f9fa;">
                    <a href="#" class="btn modern-btn modern-btn-primary" id="update_link" style="font-size:15px;font-weight:600;min-width:100px;"><?php echo get_phrase('yes');?></a>
                    <button type="button" class="btn modern-btn modern-btn-cancel" data-dismiss="modal" style="font-size:15px;font-weight:600;min-width:100px;"><?php echo get_phrase('no');?></button>
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


                <div class="modal-footer" style="margin:0px; border-top:1px solid #ecf0f1; text-align:center;padding:20px 30px;background:#f8f9fa;">
                    <a href="#" class="btn modern-btn modern-btn-primary" id="update_link" style="font-size:15px;font-weight:600;min-width:100px;"><?php echo get_phrase('yes');?></a>
                    <button type="button" class="btn modern-btn modern-btn-cancel" data-dismiss="modal" style="font-size:15px;font-weight:600;min-width:100px;"><?php echo get_phrase('no');?></button>
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
<!-- patch ends -->

<script type="text/javascript">
$('.modal-header .close').css({ opacity: .8 });

function showAjaxModal(url, $param = '') {
    // Redirect student edit URLs to dedicated modal
    if(url.includes('/modal_student_edit') || url.includes('modal_student_edit') || $param === 'student_edit') {
        return showAjaxModal_student_edit(url);
    }
    
    if($param == 'xlarge' || $param == 'modal_student_edit') {
        $('#modal_ajax .modal-dialog').removeClass('modal-lg').addClass('modal-xl');
    } else if($param == 'modal_unpaid_invoices' || $param == 'modal_outstanding_debt' || $param == 'modal_bad_debt' || $param == 'make_refund' || $param == 'big') {
        $('#modal_ajax .modal-dialog').removeClass('modal-xl').addClass('modal-lg');
    } else {
        $('#modal_ajax .modal-dialog').removeClass('modal-lg modal-xl');
    }
    
    $('#modal_ajax .modal-body').html('<div style="text-align:center;padding:40px;"><div class="loader-spinner"></div><p style="margin-top:20px;color:#667eea;font-weight:600;">Loading...</p></div>');
    $('#modal_ajax').modal('show', {backdrop: 'true'});
    
    $.ajax({
        url: url,
        cache: false,
        success: function(response) {
            $('#modal_ajax .modal-body').html(response);
        },
        error: function() {
            $('#modal_ajax .modal-body').html('<div style="text-align:center;padding:40px;color:#e74c3c;"><h4>Error Loading Data</h4><p>Unable to load the requested content.</p></div>');
        }
    });
}

function showAjaxModalLarge(url, $param = '') {
    $('#static-modal .modal-body').html('<div style="text-align:center;padding:40px;"><div class="loader-spinner"></div><p style="margin-top:20px;color:#667eea;font-weight:600;">Loading...</p></div>');
    $('#static-modal').modal('show', {backdrop: 'true'});
    
    $.ajax({
        url: url,
        cache: false,
        success: function(response) {
            $('#static-modal .modal-body').html(response);
        },
        error: function() {
            $('#static-modal .modal-body').html('<div style="text-align:center;padding:40px;color:#e74c3c;"><h4>Error Loading Data</h4><p>Unable to load the requested content.</p></div>');
        }
    });
}

function showAjaxModalDisplay(data = '') {
    $('#modal_display').modal('show', {backdrop: 'true'});
    $('#modal_display .modal-body').html(data);
}

function showAjaxModalDisplayLarge(url) {
    $('#modal_display .modal-dialog').addClass('modal-lg');
    $('#modal_display').modal('show', {backdrop: 'true'});
    
    $.ajax({
        url: url,
        cache: false,
        success: function(response) {
            $('#modal_display .modal-body').html(response);
        }
    });
}

function showAjaxModal_move_student(url, param = []) {
    $('.select2').select2('destroy');
    param = param.join('-');
    
    $('#modal_move_student .modal-body').html('<div style="text-align:center;padding:40px;"><div class="loader-spinner"></div><p style="margin-top:20px;color:#667eea;font-weight:600;">Loading...</p></div>');
    $('#modal_move_student').modal('show', {backdrop: 'true'});
    
    $.ajax({
        url: url + param,
        cache: false,
        success: function(response) {
            $('#modal_move_student .modal-body').html(response);
        }
    });
}

function showAjaxModal_residence_status(url, param = []) {
    $('.select2').select2('destroy');
    param = param.join('-');
    
    $('#modal_change_residence_status .modal-body').html('<div style="text-align:center;padding:40px;"><div class="loader-spinner"></div><p style="margin-top:20px;color:#667eea;font-weight:600;">Loading...</p></div>');
    $('#modal_change_residence_status').modal('show', {backdrop: 'true'});
    
    $.ajax({
        url: url + param,
        cache: false,
        success: function(response) {
            $('#modal_change_residence_status .modal-body').html(response);
        }
    });
}

function showAjaxModal_invoice(url, $param = '') {
    $('#modal_ajax .modal-body').html('<div style="text-align:center;padding:40px;"><div class="loader-spinner"></div><p style="margin-top:20px;color:#667eea;font-weight:600;">Loading...</p></div>');
    $('#modal_ajax').modal('show', {backdrop: 'true'});
    
    $.ajax({
        url: url,
        success: function(response) {
            $('#modal_ajax .modal-body').html(response);
        }
    });
}

function showAjaxModal_receipt(url, $param = '') {
    $('#modal_ajax_receipt .modal-body').html('<div style="text-align:center;padding:40px;"><div class="loader-spinner"></div><p style="margin-top:20px;color:#667eea;font-weight:600;">Loading...</p></div>');
    $('#modal_ajax_receipt').modal('show', {backdrop: 'true'});
    
    $.ajax({
        url: url,
        cache: false,
        success: function(response) {
            $('#modal_ajax_receipt .modal-body').html(response);
        },
        error: function() {
            $('#modal_ajax_receipt .modal-body').html('<div style="text-align:center;padding:40px;color:#e74c3c;"><h4>Error Loading Data</h4><p>Unable to load the requested content.</p></div>');
        }
    });
}

function showAjaxModal_student_edit(url) {
    $('#modal_student_edit .modal-body').html('<div style="text-align:center;padding:40px;"><div class="loader-spinner"></div><p style="margin-top:20px;color:#667eea;font-weight:600;">Loading...</p></div>');
    $('#modal_student_edit').modal('show', {backdrop: 'true'});
    
    $.ajax({
        url: url,
        cache: false,
        success: function(response) {
            $('#modal_student_edit .modal-body').html(response);
        },
        error: function() {
            $('#modal_student_edit .modal-body').html('<div style="text-align:center;padding:40px;color:#e74c3c;"><h4>Error Loading Data</h4><p>Unable to load the requested content.</p></div>');
        }
    });
}

function showAjaxModal_selection(url, $param = '') {
    $('#selection').modal('show', {backdrop: 'true'});
    
    $.ajax({
        url: url,
        success: function(response) {
            $('#selection .modal-body').html(response);
        }
    });
}

function showAjaxModal_confirm(url) {
    $('#modal_confirm .modal-body').html('<div style="text-align:center;padding:40px;"><div class="loader-spinner"></div><p style="margin-top:20px;color:#667eea;font-weight:600;">Loading...</p></div>');
    $('#modal_confirm').modal({backdrop: 'static', keyboard: false});
    
    $.ajax({
        url: url,
        success: function(response) {
            $('#modal_confirm .modal-body').html(response);
        }
    });
}

function showAjaxModal_confirm2(message, type) {
    if(type == 'success' || type == 'Success') {
        $('#modal_confirm2 .modal-header').removeClass('modern-modal-header-danger').addClass('modern-modal-header-success');
        $('#modal_confirm2 .modal-body').removeClass('modern-modal-header-danger').addClass('modern-modal-header-success');
    } else if(type == 'error' || type == 'Error') {
        $('#modal_confirm2 .modal-header').removeClass('modern-modal-header-success').addClass('modern-modal-header-danger');
        $('#modal_confirm2 .modal-body').removeClass('modern-modal-header-success').addClass('modern-modal-header-danger');
    }
    
    $('#modal_confirm2').modal({backdrop: 'static', keyboard: false});
    $('#modal_confirm2 .modal-body h3').html(message);
    $('#modal_confirm2 .modal-title').html(type);
}

function showAjaxModal_prompt(message) {
    $('#modal_prompt').modal({backdrop: 'static', keyboard: false});
    $('#modal_prompt .modal-body h3').html(message);
}

function showAjaxModal_preloader() {
    $('#modal_preloader').modal({backdrop: 'static', keyboard: false});
}

function showAjaxModal_alert(message, type, reloadOnSuccess, autoClose) {
    // Check if modal exists
    if($('#modal_alert').length === 0) {
        console.error('Modal #modal_alert not found');
        return;
    }
    
    // Default reloadOnSuccess to true if not specified
    if(reloadOnSuccess === undefined) reloadOnSuccess = true;
    
    // Default autoClose to true if not specified
    if(autoClose === undefined) autoClose = true;
    
    var iconHtml = '';
    var footerHtml = '';
    
    var baseUrl = '<?php echo base_url(); ?>';
    
    if(type == 'success' || type == 'Success' || type == 'Logged Out') {
        iconHtml = '<div style="width:80px;height:80px;margin:0 auto 20px;background:linear-gradient(135deg,#11998e15 0%,#38ef7d15 100%);border-radius:50%;display:flex;align-items:center;justify-content:center;"><img src="' + baseUrl + 'assets/icons/icon-success.png" width="50px" height="50px"/></div>';
        $('#modal_alert .modal-header button').css('display','block');
    } else if(type == 'error' || type == 'Error') {
        iconHtml = '<div style="width:80px;height:80px;margin:0 auto 20px;background:linear-gradient(135deg,#f093fb15 0%,#f5576c15 100%);border-radius:50%;display:flex;align-items:center;justify-content:center;"><img src="' + baseUrl + 'assets/icons/icon-cancel.png" width="50px" height="50px"/></div>';
        $('#modal_alert .modal-header button').css('display','none');
        footerHtml = '<div class="modal-footer" style="border-top:1px solid #ecf0f1;padding:15px 30px;background:#f8f9fa;text-align:center;"><button type="button" class="btn modern-btn modern-btn-danger" data-dismiss="modal" style="min-width:100px;font-size:15px;font-weight:600;">Close</button></div>';
    } else if(type == 'warning' || type == 'Warning') {
        iconHtml = '<div style="width:80px;height:80px;margin:0 auto 20px;background:linear-gradient(135deg,#f39c1215 0%,#e67e2215 100%);border-radius:50%;display:flex;align-items:center;justify-content:center;"><img src="' + baseUrl + 'assets/icons/icon-warning.png" width="50px" height="50px"/></div>';
        $('#modal_alert .modal-header button').css('display','none');
        footerHtml = '<div class="modal-footer" style="border-top:1px solid #ecf0f1;padding:15px 30px;background:#f8f9fa;text-align:center;"><button type="button" class="btn modern-btn modern-btn-cancel" data-dismiss="modal" style="min-width:100px;font-size:15px;font-weight:600;">Close</button></div>';
    } else if(type == 'info' || type == 'Info') {
        // Info type with blue theme and info icon (using Font Awesome)
        iconHtml = '<div style="width:80px;height:80px;margin:0 auto 20px;background:linear-gradient(135deg,#667eea15 0%,#764ba215 100%);border-radius:50%;display:flex;align-items:center;justify-content:center;"><i class="fa fa-info-circle" style="font-size:50px;color:#667eea;"></i></div>';
        $('#modal_alert .modal-header button').css('display','none');
        footerHtml = '<div class="modal-footer" style="border-top:1px solid #ecf0f1;padding:15px 30px;background:#f8f9fa;text-align:center;"><button type="button" class="btn modern-btn modern-btn-primary" data-dismiss="modal" style="min-width:100px;font-size:15px;font-weight:600;">OK</button></div>';
    } else if(type == 'loading' || type == 'Loading') {
        iconHtml = '<div class="loader-spinner" style="margin: 10px auto;"></div>';
        $('#modal_alert .modal-header button').css('display','none');
    }
    
    // Update modal content
    if($('#modal_alert .modal-body center').length > 0) {
        $('#modal_alert .modal-body center').html(iconHtml);
    }
    if($('#modal_alert .modal-body h3').length > 0) {
        $('#modal_alert .modal-body h3').html(message);
    }
    
    // Remove existing footer first
    $('#modal_alert .modal-footer').remove();
    
    // Add footer if needed
    if(footerHtml) {
        $('#modal_alert .modal-content').append(footerHtml);
    }
    
    // Show modal
    $('#modal_alert').modal({backdrop: 'static', keyboard: false});
    
    // Auto-close success messages after 2 seconds (only if autoClose is true)
    if((type == 'success' || type == 'Success' || type == 'Logged Out') && autoClose === true) {
        setTimeout(function() {
            $('#modal_alert').modal('hide');
            // Only reload if reloadOnSuccess is true
            if(reloadOnSuccess === true || reloadOnSuccess === 'yes') {
                setTimeout(function() {
                    location.reload();
                }, 300);
            }
        }, 2000);
    }
}

function showAjaxModal_confirm_staff(url) {
    $('#modal_confirm_staff .modal-body').html('<div style="text-align:center;padding:40px;"><div class="loader-spinner"></div><p style="margin-top:20px;color:#667eea;font-weight:600;">Loading...</p></div>');
    $('#modal_confirm_staff').modal({backdrop: 'static', keyboard: false});
    
    $.ajax({
        url: url,
        success: function(response) {
            $('#modal_confirm_staff .modal-body').html(response);
        }
    });
}

function showAjaxModal_idle_user(url) {
    $('#modal_idle_user .modal-body').html('<div style="text-align:center;padding:40px;"><div class="loader-spinner"></div><p style="margin-top:20px;color:#667eea;font-weight:600;">Loading...</p></div>');
    $('#modal_idle_user').modal({backdrop: 'static', keyboard: false});
    
    $.ajax({
        url: url,
        success: function(response) {
            $('#modal_idle_user .modal-body').html(response);
        }
    });
}

function showAjaxModal_user_blocked(url) {
    $('#modal_user_blocked .modal-body').html('<div style="text-align:center;padding:40px;"><div class="loader-spinner"></div><p style="margin-top:20px;color:#667eea;font-weight:600;">Loading...</p></div>');
    $('#modal_user_blocked').modal({backdrop: 'static', keyboard: false});
    
    $.ajax({
        url: url,
        success: function(response) {
            $('#modal_user_blocked .modal-body').html(response);
        }
    });
}

function showAjaxModal_payroll_edit(url) {
    $('#modal_payroll_edit .modal-body').html('<div style="text-align:center;padding:40px;"><div class="loader-spinner"></div><p style="margin-top:20px;color:#059669;font-weight:600;">Loading payroll data...</p></div>');
    $('#modal_payroll_edit').modal({backdrop: 'static', keyboard: false});
    
    $.ajax({
        url: url,
        cache: false,
        success: function(response) {
            $('#modal_payroll_edit .modal-body').html(response);
        },
        error: function() {
            $('#modal_payroll_edit .modal-body').html('<div style="text-align:center;padding:40px;color:#e74c3c;"><h4>Error Loading Data</h4><p>Unable to load the payroll data.</p></div>');
        }
    });
}

/**
 * Custom Confirm Dialog - Replacement for browser confirm()
 * Uses existing modal_confirm from modal.php (no inline modal creation)
 * @param {string} message - The confirmation message to display
 * @param {function} onConfirm - Callback function to execute when user clicks Yes
 * @param {function} onCancel - Optional callback function to execute when user clicks No
 */
function showCustomConfirm(message, onConfirm, onCancel) {
    // Update modal title and message
    $('#modal_confirm .modal-title').html('Confirm Action');
    $('#modal_confirm .modal-body').html('<p style="text-align:center;font-size:16px;color:#2c3e50;padding:20px 0;">' + message + '</p>');
    
    // Remove any existing click handlers to prevent multiple bindings
    $('#modal_yes').off('click');
    $('#modal_no').off('click');
    
    // Bind Yes button
    $('#modal_yes').on('click', function() {
        $('#modal_confirm').modal('hide');
        if (typeof onConfirm === 'function') {
            // Small delay to allow modal to close
            setTimeout(function() {
                onConfirm();
            }, 100);
        }
    });
    
    // Bind No button
    $('#modal_no').on('click', function() {
        $('#modal_confirm').modal('hide');
        if (typeof onCancel === 'function') {
            setTimeout(function() {
                onCancel();
            }, 100);
        }
    });
    
    // Show modal
    $('#modal_confirm').modal({backdrop: 'static', keyboard: false});
}

</script>






<!-- Move Student Modal -->
<div class="modal fade" id="modal_move_student">
    <div class="modal-dialog">
        <div class="modal-content modern-modal-content">
            <div class="modal-header modern-modal-header-success">
                <button type="button" class="close modern-close" data-dismiss="modal">&times;</button>
                <div style="display:flex;align-items:center;gap:12px;">
                    <div style="width:48px;height:48px;background:rgba(255,255,255,0.2);border-radius:12px;display:flex;align-items:center;justify-content:center;">
                        <i class="entypo-users" style="color:white;font-size:24px;"></i>
                    </div>
                    <div>
                        <h4 class="modal-title" style="color:white;font-weight:700;margin:0;">Select The Class</h4>
                        <p style="color:rgba(255,255,255,0.9);font-size:13px;margin:0;">Move students to a different class or graduate them</p>
                    </div>
                </div>
            </div>
            <div class="modal-body" style="padding:30px;"></div>
            <div class="modal-footer" style="border-top:1px solid #ecf0f1;padding:20px 30px;background:#f8f9fa;display:flex;gap:12px;justify-content:flex-end;">
                <button type="button" class="btn modern-btn modern-btn-cancel" data-dismiss="modal">Cancel</button>
                <button type="button" id="modal_move_student_move" class="btn modern-btn modern-btn-success">Move</button>
            </div>
        </div>
    </div>
</div>

<!-- Change Residence Status Modal -->
<div class="modal fade" id="modal_change_residence_status">
    <div class="modal-dialog">
        <div class="modal-content modern-modal-content">
            <div class="modal-header modern-modal-header-success">
                <button type="button" class="close modern-close" data-dismiss="modal">&times;</button>
                <div style="display:flex;align-items:center;gap:12px;">
                    <div style="width:48px;height:48px;background:rgba(255,255,255,0.2);border-radius:12px;display:flex;align-items:center;justify-content:center;">
                        <i class="entypo-home" style="color:white;font-size:24px;"></i>
                    </div>
                    <div>
                        <h4 class="modal-title" style="color:white;font-weight:700;margin:0;">Change Residential Status</h4>
                        <p style="color:rgba(255,255,255,0.9);font-size:13px;margin:0;">Select the academic period for this change</p>
                    </div>
                </div>
            </div>
            <div class="modal-body" style="padding:30px;"></div>
            <div class="modal-footer" style="border-top:1px solid #ecf0f1;padding:20px 30px;background:#f8f9fa;display:flex;gap:12px;justify-content:flex-end;">
                <button type="button" class="btn modern-btn modern-btn-cancel" data-dismiss="modal">Cancel</button>
                <button type="button" id="modal_change_residence_status_change" class="btn modern-btn modern-btn-success">Change</button>
            </div>
        </div>
    </div>
</div>

<!-- Student Edit Modal (Dedicated Wide Modal) -->
<div class="modal fade" id="modal_student_edit">
    <div class="modal-dialog modal-xl">
        <div class="modal-content modern-modal-content">
            <div class="modal-header modern-modal-header">
                <button type="button" class="close modern-close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title" style="color:white;font-weight:700;"><i class="fa fa-user-edit"></i> Edit Student</h4>
            </div>
            <div class="modal-body" style="max-height:80vh;overflow-y:auto;padding:30px;"></div>
        </div>
    </div>
</div>

<!-- Ajax Modal -->
<div class="modal fade" id="modal_ajax">
    <div class="modal-dialog">
        <div class="modal-content modern-modal-content">
            <div class="modal-header modern-modal-header">
                <button type="button" class="close modern-close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title" style="color:white;font-weight:700;"><?php echo isset($system_name) ? $system_name : 'School Manager';?></h4>
            </div>
            <div class="modal-body" style="max-height:500px;overflow:auto;padding:30px;"></div>
            <div class="modal-footer" style="border-top:1px solid #ecf0f1;padding:20px 30px;background:#f8f9fa;">
                <button type="button" class="btn modern-btn modern-btn-danger" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>


<!-- Ajax Receipt Modal -->
<div class="modal fade" id="modal_ajax_receipt">
    <div class="modal-dialog">
        <div class="modal-content modern-modal-content">
            <div class="modal-header modern-modal-header">
                <button type="button" class="close modern-close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title" style="color:white;font-weight:700;"><?php echo isset($system_name) ? $system_name : 'School Manager';?></h4>
            </div>
            <div class="modal-body" style="max-height:500px;overflow:auto;padding:30px;"></div>
            <div class="modal-footer" style="border-top:1px solid #ecf0f1;padding:20px 30px;background:#f8f9fa;">
                <button type="button" class="btn modern-btn modern-btn-danger" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>


<!-- Boarding House Modal -->
<div class="modal fade" id="modal_boarding_house" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content modern-modal-content">
            <div class="modal-header modern-modal-header">
                <button type="button" class="close modern-close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title" id="boarding_house_modal_title" style="color:white;font-weight:700;">Boarding House</h4>
            </div>
            <div class="modal-body" id="boarding_house_modal_body" style="padding:30px;max-height:70vh;overflow-y:auto;"></div>
        </div>
    </div>
</div>

<!-- Boarding Dormitory Modal -->
<div class="modal fade" id="modal_boarding_dormitory" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content modern-modal-content">
            <div class="modal-header modern-modal-header">
                <button type="button" class="close modern-close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title" id="boarding_dormitory_modal_title" style="color:white;font-weight:700;">Dormitory</h4>
            </div>
            <div class="modal-body" id="boarding_dormitory_modal_body" style="padding:30px;max-height:70vh;overflow-y:auto;"></div>
        </div>
    </div>
</div>

<!-- Saved Fee Transactions Modal -->
<div class="modal fade" id="modal_saved_transactions">
    <div class="modal-dialog modal-lg">
        <div class="modal-content modern-modal-content">
            <div class="modal-header modern-modal-header-warning">
                <button type="button" class="close modern-close" data-dismiss="modal">&times;</button>
                <div style="display:flex;align-items:center;gap:12px;">
                    <div style="width:48px;height:48px;background:rgba(255,255,255,0.2);border-radius:12px;display:flex;align-items:center;justify-content:center;">
                        <i class="fa fa-clock" style="color:white;font-size:24px;"></i>
                    </div>
                    <div>
                        <h4 class="modal-title" style="color:white;font-weight:700;margin:0;">Saved Transactions</h4>
                        <p style="color:rgba(255,255,255,0.9);font-size:13px;margin:0;">Resume incomplete fee collections</p>
                    </div>
                </div>
            </div>
            <div class="modal-body" id="saved_transactions_list" style="padding:30px;max-height:60vh;overflow-y:auto;"></div>
        </div>
    </div>
</div>

<!-- Fee Assignment Modal -->
<div class="modal fade" id="modal_fee_assignment">
    <div class="modal-dialog modal-lg">
        <div class="modal-content modern-modal-content">
            <div class="modal-header modern-modal-header">
                <button type="button" class="close modern-close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title" style="color:white;font-weight:700;"><i class="fa fa-user-lock"></i> Assign Teacher to Class</h4>
            </div>
            <?php echo form_open('admin/save_fee_assignment', array('id' => 'assignment_form')); ?>
                <div class="modal-body" style="padding:30px;">
                    <input type="hidden" name="assignment_id" id="assignment_id">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="font-bold">Teacher *</label>
                                <select name="teacher_id" id="teacher_id" class="form-control select2" required style="height:46px;">
                                    <option value="">Select Teacher</option>
                                    <?php
                                    $teachers = $this->db->order_by('name', 'ASC')->get('teacher')->result_array();
                                    foreach($teachers as $teacher):
                                    ?>
                                    <option value="<?php echo $teacher['teacher_id']; ?>">
                                        <?php echo $teacher['name'] . ' (' . $teacher['teacher_code'] . ')'; ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="font-bold">Class *</label>
                                <select name="class_id" id="class_id" class="form-control select2" required style="height:46px;">
                                    <option value="">Select Class</option>
                                    <?php getFullClassList(); ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="font-bold" style="font-size:15px;margin-bottom:15px;">Fee Collection Permissions</label>
                        <div style="display:flex;flex-direction:column;gap:12px;">
                            <label style="display:flex;align-items:center;padding:12px 16px;border:2px solid #e0e0e0;border-radius:8px;cursor:pointer;transition:all 0.2s;" class="checkbox-label" data-checkbox="can_collect_feeding">
                                <input type="checkbox" name="can_collect_feeding" id="can_collect_feeding" value="1" style="width:20px;height:20px;margin-right:12px;cursor:pointer;">
                                <span style="font-size:15px;font-weight:500;">Can Collect Feeding Fee</span>
                            </label>
                            <label style="display:flex;align-items:center;padding:12px 16px;border:2px solid #e0e0e0;border-radius:8px;cursor:pointer;transition:all 0.2s;" class="checkbox-label" data-checkbox="can_collect_classes">
                                <input type="checkbox" name="can_collect_classes" id="can_collect_classes" value="1" style="width:20px;height:20px;margin-right:12px;cursor:pointer;">
                                <span style="font-size:15px;font-weight:500;">Can Collect Classes Fee</span>
                            </label>
                            <label style="display:flex;align-items:center;padding:12px 16px;border:2px solid #e0e0e0;border-radius:8px;cursor:pointer;transition:all 0.2s;" class="checkbox-label" data-checkbox="can_collect_transport">
                                <input type="checkbox" name="can_collect_transport" id="can_collect_transport" value="1" style="width:20px;height:20px;margin-right:12px;cursor:pointer;">
                                <span style="font-size:15px;font-weight:500;">Can Collect Transport Fare</span>
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer" style="border-top:1px solid #ecf0f1;padding:20px 30px;background:#f8f9fa;">
                    <button type="button" class="btn modern-btn modern-btn-cancel" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn modern-btn modern-btn-primary">
                        <i class="fa fa-save"></i> Save Assignment
                    </button>
                </div>
            <?php echo form_close(); ?>
        </div>
    </div>
</div>


<style>
.checkbox-label:hover { background:#f8f9fa; border-color:var(--theme-primary); }
.checkbox-label input:checked { accent-color:var(--theme-primary); }
.checkbox-label:has(input:checked) { background:#f0f4ff; border-color:var(--theme-primary); }
</style>
<script>
$(document).ready(function() {
    $('#modal_fee_assignment').on('shown.bs.modal', function() {
        $('#teacher_id, #class_id').select2({ dropdownParent: $('#modal_fee_assignment'), width: '100%' });
    });
});
</script>

<?php include 'modal/fee_collection_modals.php'; ?>

<!-- ============================================ -->
<!-- CENTRALIZED MODAL REGISTRY -->
<!-- All application modals defined below -->
<!-- ============================================ -->

<!-- Bank Reconciliation Modal -->
<div class="modal fade" id="reconciliationModal">
    <div class="modal-dialog modal-lg">
        <div class="modal-content modern-modal-content">
            <div class="modal-header modern-modal-header">
                <button type="button" class="close modern-close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title" style="color:white;font-weight:700;"><i class="fa fa-balance-scale"></i> Bank Reconciliation</h4>
            </div>
            <div class="modal-body" style="padding:30px;"></div>
            <div class="modal-footer" style="border-top:1px solid #ecf0f1;padding:20px 30px;background:#f8f9fa;">
                <button type="button" class="btn modern-btn modern-btn-cancel" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn modern-btn modern-btn-success" id="saveReconciliation">Save Reconciliation</button>
            </div>
        </div>
    </div>
</div>

<!-- Details Modal (Generic) -->
<div class="modal fade" id="detailsModal">
    <div class="modal-dialog modal-lg">
        <div class="modal-content modern-modal-content">
            <div class="modal-header modern-modal-header">
                <button type="button" class="close modern-close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title" style="color:white;font-weight:700;"><i class="fa fa-info-circle"></i> Details</h4>
            </div>
            <div class="modal-body" style="padding:30px;max-height:70vh;overflow-y:auto;"></div>
            <div class="modal-footer" style="border-top:1px solid #ecf0f1;padding:20px 30px;background:#f8f9fa;">
                <button type="button" class="btn modern-btn modern-btn-primary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Budget Modal -->
<div class="modal fade" id="budgetModal">
    <div class="modal-dialog modal-lg">
        <div class="modal-content modern-modal-content">
            <div class="modal-header modern-modal-header">
                <button type="button" class="close modern-close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title" id="budgetModalTitle" style="color:white;font-weight:700;"><i class="fa fa-money"></i> Budget</h4>
            </div>
            <div class="modal-body" style="padding:30px;"></div>
        </div>
    </div>
</div>

<!-- Budget Details Modal -->
<div class="modal fade" id="budgetDetailsModal">
    <div class="modal-dialog modal-xl" style="width:95%;">
        <div class="modal-content modern-modal-content">
            <div class="modal-header modern-modal-header" style="position: relative;">
                <button type="button" class="close modern-close" data-dismiss="modal" style="position: absolute; top: 10px; right: 15px; background: #ef4444; color: white; border: none; width: 50px; height: 50px; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 24px; transition: all 0.3s; box-shadow: 0 4px 12px rgba(239, 68, 68, 0.4); opacity: 1; text-shadow: none; z-index: 1000;" onmouseover="this.style.background='#dc2626'; this.style.transform='scale(1.1) rotate(90deg)'; this.style.boxShadow='0 6px 16px rgba(239, 68, 68, 0.6)'" onmouseout="this.style.background='#ef4444'; this.style.transform='scale(1) rotate(0deg)'; this.style.boxShadow='0 4px 12px rgba(239, 68, 68, 0.4)'">&times;</button>
                <h4 class="modal-title" id="budgetDetailsTitle" style="color:white;font-weight:700;"></h4>
            </div>
            <div class="modal-body" id="budgetDetailsContent" style="padding:30px;"></div>
        </div>
    </div>
</div>

<!-- Budget Line Modal -->
<div class="modal fade" id="budgetLineModal">
    <div class="modal-dialog">
        <div class="modal-content modern-modal-content">
            <div class="modal-header modern-modal-header">
                <button type="button" class="close modern-close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title" style="color:white;font-weight:700;"><i class="fa fa-line-chart"></i> Budget Line</h4>
            </div>
            <div class="modal-body" style="padding:30px;"></div>
        </div>
    </div>
</div>

<!-- Expense Modal -->
<div class="modal fade" id="expenseModal">
    <div class="modal-dialog modal-lg">
        <div class="modal-content modern-modal-content">
            <div class="modal-header modern-modal-header">
                <button type="button" class="close modern-close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title" id="expenseModalTitle" style="color:white;font-weight:700;"><i class="fa fa-credit-card"></i> Expense</h4>
            </div>
            <div class="modal-body" style="padding:30px;"></div>
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal">
    <div class="modal-dialog">
        <div class="modal-content modern-modal-content">
            <div class="modal-header modern-modal-header-danger">
                <button type="button" class="close modern-close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title" style="color:white;font-weight:700;"><i class="fa fa-times-circle"></i> Reject Expense</h4>
            </div>
            <div class="modal-body" style="padding:30px;"></div>
        </div>
    </div>
</div>

<!-- Category Modal (Generic) -->
<div id="category_modal" class="modal fade">
    <div class="modal-dialog">
        <div class="modal-content modern-modal-content">
            <div class="modal-header modern-modal-header">
                <button type="button" class="close modern-close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title" style="color:white;font-weight:700;"><i class="fa fa-folder"></i> Category</h4>
            </div>
            <div class="modal-body" style="padding:30px;"></div>
        </div>
    </div>
</div>

<!-- Create Modal (Generic) -->
<div class="modal fade" id="createModal">
    <div class="modal-dialog modal-lg">
        <div class="modal-content modern-modal-content">
            <div class="modal-header modern-modal-header-success">
                <button type="button" class="close modern-close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title" id="createModalTitle" style="color:white;font-weight:700;"><i class="fa fa-plus-circle"></i> Create New</h4>
            </div>
            <div class="modal-body" style="padding:30px;"></div>
        </div>
    </div>
</div>

<!-- Edit Modal (Generic) -->
<div class="modal fade" id="editModal">
    <div class="modal-dialog modal-lg">
        <div class="modal-content modern-modal-content">
            <div class="modal-header modern-modal-header">
                <button type="button" class="close modern-close" data-dismiss="modal" aria-label="Close">&times;</button>
                <h4 class="modal-title" style="color:white;font-size:20px;font-weight:700;margin:0;"></h4>
            </div>
            <div class="modal-body" style="padding:30px;"></div>
        </div>
    </div>
</div>

<!-- Fee Modal -->
<div class="modal fade" id="feeModal">
    <div class="modal-dialog modal-lg">
        <div class="modal-content modern-modal-content">
            <div class="modal-header modern-modal-header">
                <button type="button" class="close modern-close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title" id="feeModalTitle" style="color:white;font-weight:700;"><i class="fa fa-money"></i> Fee Structure</h4>
            </div>
            <div class="modal-body" style="padding:30px;"></div>
        </div>
    </div>
</div>

<!-- Generate Modal -->
<div class="modal fade" id="generateModal">
    <div class="modal-dialog">
        <div class="modal-content modern-modal-content">
            <div class="modal-header modern-modal-header-success">
                <button type="button" class="close modern-close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title" style="color:white;font-weight:700;"><i class="fa fa-file-text"></i> Generate</h4>
            </div>
            <div class="modal-body" style="padding:30px;"></div>
        </div>
    </div>
</div>

<!-- Preview Modal -->
<div class="modal fade" id="previewModal">
    <div class="modal-dialog modal-lg">
        <div class="modal-content modern-modal-content">
            <div class="modal-header modern-modal-header">
                <button type="button" class="close modern-close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title" style="color:white;font-weight:700;"><i class="fa fa-eye"></i> Preview</h4>
            </div>
            <div class="modal-body" style="padding:30px;max-height:75vh;overflow-y:auto;"></div>
            <div class="modal-footer" style="border-top:1px solid #ecf0f1;padding:20px 30px;background:#f8f9fa;">
                <button type="button" class="btn modern-btn modern-btn-primary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Audit Details Modal -->
<div id="auditDetailsModal" class="modal fade">
    <div class="modal-dialog modal-lg">
        <div class="modal-content modern-modal-content">
            <div class="modal-header modern-modal-header">
                <button type="button" class="close modern-close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title" style="color:white;font-weight:700;"><i class="fa fa-history"></i> Audit Details</h4>
            </div>
            <div class="modal-body" style="padding:30px;max-height:70vh;overflow-y:auto;"></div>
            <div class="modal-footer" style="border-top:1px solid #ecf0f1;padding:20px 30px;background:#f8f9fa;">
                <button type="button" class="btn modern-btn modern-btn-primary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Add Bank Modal -->
<div id="addBankModal" class="modal fade">
    <div class="modal-dialog">
        <div class="modal-content modern-modal-content">
            <div class="modal-header modern-modal-header-success">
                <button type="button" class="close modern-close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title" style="color:white;font-weight:700;"><i class="fa fa-bank"></i> Add Bank Account</h4>
            </div>
            <div class="modal-body" style="padding:30px;"></div>
        </div>
    </div>
</div>

<!-- Add Budget Modal -->
<div id="addBudgetModal" class="modal fade">
    <div class="modal-dialog modal-lg">
        <div class="modal-content modern-modal-content">
            <div class="modal-header modern-modal-header-success">
                <button type="button" class="close modern-close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title" style="color:white;font-weight:700;"><i class="fa fa-plus-circle"></i> Create Budget</h4>
            </div>
            <div class="modal-body" style="padding:30px;"></div>
        </div>
    </div>
</div>

<!-- Add Account Modal -->
<div id="addAccountModal" class="modal fade">
    <div class="modal-dialog">
        <div class="modal-content modern-modal-content">
            <div class="modal-header modern-modal-header-success">
                <button type="button" class="close modern-close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title" style="color:white;font-weight:700;"><i class="fa fa-plus-circle"></i> Add Account</h4>
            </div>
            <div class="modal-body" style="padding:30px;"></div>
        </div>
    </div>
</div>

<!-- Add Expense Modal -->
<div id="addExpenseModal" class="modal fade">
    <div class="modal-dialog modal-lg">
        <div class="modal-content modern-modal-content">
            <div class="modal-header modern-modal-header">
                <button type="button" class="close modern-close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title" style="color:white;font-weight:700;"><i class="fa fa-credit-card"></i> Add Expense</h4>
            </div>
            <div class="modal-body" style="padding:30px;"></div>
        </div>
    </div>
</div>

<!-- Add Fiscal Year Modal -->
<div id="addFiscalYearModal" class="modal fade">
    <div class="modal-dialog">
        <div class="modal-content modern-modal-content">
            <div class="modal-header modern-modal-header-success">
                <button type="button" class="close modern-close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title" style="color:white;font-weight:700;"><i class="fa fa-calendar"></i> Add Fiscal Year</h4>
            </div>
            <div class="modal-body" style="padding:30px;"></div>
        </div>
    </div>
</div>

<!-- Add Journal Modal -->
<div id="addJournalModal" class="modal fade">
    <div class="modal-dialog modal-lg">
        <div class="modal-content modern-modal-content">
            <div class="modal-header modern-modal-header">
                <button type="button" class="close modern-close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title" style="color:white;font-weight:700;"><i class="fa fa-book"></i> Journal Entry</h4>
            </div>
            <div class="modal-body" style="padding:30px;"></div>
        </div>
    </div>
</div>

<!-- Bulk Payment Modal -->
<div id="bulk_payment_modal" class="modal fade">
    <div class="modal-dialog modal-lg">
        <div class="modal-content modern-modal-content">
            <div class="modal-header modern-modal-header-success">
                <button type="button" class="close modern-close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title" style="color:white;font-weight:700;"><i class="fa fa-users"></i> Bulk Payment</h4>
            </div>
            <div class="modal-body" style="padding:30px;"></div>
        </div>
    </div>
</div>

<!-- Drive List Wide Modal -->
<div id="driveListModal" class="modal fade">
    <div class="modal-dialog modal-xl">
        <div class="modal-content modern-modal-content">
            <div class="modal-header modern-modal-header-danger">
                <button type="button" class="close modern-close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title" style="color:white;font-weight:700;"><i class="fa fa-exclamation-triangle"></i> Drive List</h4>
            </div>
            <div class="modal-body" style="padding:30px;max-height:75vh;overflow-y:auto;"></div>
        </div>
    </div>
</div>

<!-- Payroll Edit Modal -->
<div class="modal fade" id="modal_payroll_edit">
    <div class="modal-dialog modal-lg">
        <div class="modal-content modern-modal-content">
            <div class="modal-header modern-modal-header-success">
                <button type="button" class="close modern-close" data-dismiss="modal">&times;</button>
                <div style="display:flex;align-items:center;gap:12px;">
                    <div style="width:48px;height:48px;background:rgba(255,255,255,0.2);border-radius:12px;display:flex;align-items:center;justify-content:center;">
                        <i class="fa fa-edit" style="color:white;font-size:24px;"></i>
                    </div>
                    <div>
                        <h4 class="modal-title" style="color:white;font-weight:700;margin:0;">Edit Payroll</h4>
                        <p style="color:rgba(255,255,255,0.9);font-size:13px;margin:0;">Modify salary details and deductions</p>
                    </div>
                </div>
            </div>
            <div class="modal-body" style="padding:30px;max-height:75vh;overflow-y:auto;"></div>
        </div>
    </div>
</div>

<!-- Notification Details Modal -->
<div id="notificationDetailsModal" class="modal fade">
    <div class="modal-dialog modal-lg">
        <div class="modal-content modern-modal-content">
            <div class="modal-header modern-modal-header">
                <button type="button" class="close modern-close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title" style="color:white;font-weight:700;"><i class="fa fa-bell"></i> Notification Details</h4>
            </div>
            <div class="modal-body" style="padding:0;max-height:70vh;overflow-y:auto;"></div>
            <div class="modal-footer" style="border-top:1px solid #ecf0f1;padding:20px 30px;background:#f8f9fa;display:flex;gap:12px;justify-content:flex-end;"></div>
        </div>
    </div>
</div>


<!-- Preloader Modal -->
<div class="modal fade" data-backdrop="static" data-keyboard="false" id="modal_preloader">
    <div class="modal-dialog modal-sm" style="margin-top:20vh;">
        <div class="modal-content modern-modal-content">
            <div class="modal-body" style="padding:40px;text-align:center;">
                <div class="loader-spinner"></div>
                <p style="margin-top:20px;color:#667eea;font-weight:600;">Please Wait...</p>
                <p style="color:#7f8c8d;font-size:14px;">Loading, please wait...</p>
            </div>
        </div>
    </div>
</div>

<!-- Selection Modal -->
<div class="modal fade" id="selection">
    <div class="modal-dialog">
        <div class="modal-content modern-modal-content" style="margin-top:15vh;">
            <div class="modal-header modern-modal-header">
                <button type="button" class="close modern-close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title" style="color:white;font-weight:700;"><?php echo isset($system_name) ? $system_name : 'School Manager';?></h4>
            </div>
            <div class="modal-body" style="padding:30px;"></div>
        </div>
    </div>
</div>

<!-- Confirm Modal -->
<div class="modal fade" id="modal_confirm">
    <div class="modal-dialog modal-lg">
        <div class="modal-content modern-modal-content">
            <div class="modal-header modern-modal-header-danger">
                <h4 class="modal-title" style="color:white;font-weight:700;text-align:center;">Duplicates Found!</h4>
            </div>
            <div class="modal-body" style="padding:30px;"></div>
            <div class="modal-footer" style="border-top:1px solid #ecf0f1;padding:20px 30px;background:#f8f9fa;display:flex;gap:12px;justify-content:center;">
                <button type="button" id="modal_no" class="btn modern-btn modern-btn-cancel" data-dismiss="modal">No</button>
                <button type="button" id="modal_yes" class="btn modern-btn modern-btn-success" data-dismiss="modal">Yes</button>
            </div>
        </div>
    </div>
</div>
<?php include 'modal/confirm_modal.php'; ?>

<!-- Prompt Modal -->
<div class="modal fade" id="modal_prompt" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-sm" style="margin-top:15vh;">
        <div class="modal-content modern-modal-content">
            <div class="modal-header" style="border:none;padding:30px 30px 0;text-align:center;">
                <img src="<?= base_url('/assets/icons/icon-warning.png') ?>" width="80px" height="80px" style="margin:0 auto;"/>
            </div>
            <div class="modal-body" style="padding:20px 30px;">
                <h3 style="text-align:center;color:#2c3e50;font-size:16px;"></h3>
            </div>
            <div class="modal-footer" style="border-top:1px solid #ecf0f1;padding:20px 30px;background:#f8f9fa;display:flex;gap:12px;justify-content:center;">
                <button class="btn modern-btn modern-btn-cancel" id="prompt_cancel" data-dismiss="modal">Cancel</button>
                <button class="btn modern-btn modern-btn-success" id="prompt_yes">Yes</button>
            </div>
        </div>
    </div>
</div>

<!-- Alert Modal -->
<div class="modal fade" id="modal_alert" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-sm" style="margin-top:15vh;">
        <div class="modal-content modern-modal-content">
            <div class="modal-header" style="border:none;padding:20px 20px 0;position:relative;">
                <button class="close modern-close" type="button" data-dismiss="modal" style="color:#666!important;font-size:28px!important;">&times;</button>
            </div>
            <div class="modal-body" style="padding:20px 30px 40px;text-align:center;">
                <center></center>
                <h3 style="color:#2c3e50;font-size:18px;font-weight:600;margin-top:0px;"></h3>
            </div>
        </div>
    </div>
</div>

<script>
// Centralized Modal Helper Functions
function showModalWithContent(modalId, title, content) {
    $('#' + modalId + ' .modal-title').html(title);
    $('#' + modalId + ' .modal-body').html(content);
    $('#' + modalId).modal('show');
}

function loadModalContent(modalId, url, title) {
    if(title) $('#' + modalId + ' .modal-title').html(title);
    $('#' + modalId + ' .modal-body').html('<div style="text-align:center;padding:40px;"><div class="loader-spinner"></div><p style="margin-top:20px;color:#667eea;font-weight:600;">Loading...</p></div>');
    
    // Safe modal show - check if Bootstrap modal is available
    var $modal = $('#' + modalId);
    if (typeof $modal.modal === 'function') {
        $modal.modal('show');
    } else {
        // Fallback: show modal using direct DOM manipulation
        $modal.addClass('in').css('display', 'block');
        $('body').addClass('modal-open');
        if ($('.modal-backdrop').length === 0) {
            $('body').append('<div class="modal-backdrop fade in"></div>');
        }
    }
    
    $.ajax({
        url: url,
        cache: false,
        success: function(response) {
            $('#' + modalId + ' .modal-body').html(response);
        },
        error: function() {
            $('#' + modalId + ' .modal-body').html('<div style="text-align:center;padding:40px;color:#e74c3c;"><h4>Error Loading Data</h4><p>Unable to load the requested content.</p></div>');
        }
    });
}
</script>



    </div>
</div>

<!-- ========================================
     GES LESSON NOTE SYSTEM MODALS
     ======================================== -->

<!-- Add/Edit Curriculum Strand Modal -->
<div class="modal fade modern-modal" id="strandModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="strandModalTitle"><?php echo get_phrase('add_strand'); ?></h4>
                <button type="button" class="close" data-dismiss="modal" style="color: #fff; opacity: 1;">
                    <span>&times;</span>
                </button>
            </div>
            <?php echo form_open('', array('id' => 'strandForm', 'method' => 'post')); ?>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="modern-form-group">
                                <label><?php echo get_phrase('class_level'); ?><span class="required">*</span></label>
                                <select name="class_level" id="class_level" class="modern-form-control" required>
                                    <option value=""><?php echo get_phrase('select_class'); ?></option>
                                    <?php 
                                    // Use getFullClassList helper to populate classes
                                    if (function_exists('getFullClassList')) {
                                        getFullClassList();
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="modern-form-group">
                                <label><?php echo get_phrase('subject'); ?><span class="required">*</span></label>
                                <select name="subject_id" id="subject_id" class="modern-form-control" required>
                                    <option value=""><?php echo get_phrase('select_class_first'); ?></option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="modern-form-group">
                        <label><?php echo get_phrase('strand_name'); ?><span class="required">*</span></label>
                        <input type="text" name="name" id="strand_name" class="modern-form-control" required placeholder="<?php echo get_phrase('enter_strand_name'); ?>">
                    </div>

                    <div class="modern-form-group">
                        <label><?php echo get_phrase('description'); ?><span class="required">*</span></label>
                        <textarea name="description" id="strand_description" class="modern-form-control" rows="5" required placeholder="<?php echo get_phrase('enter_strand_description'); ?>"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="modern-btn modern-btn-secondary" data-dismiss="modal">
                        <i class="entypo-cancel"></i>
                        <?php echo get_phrase('cancel'); ?>
                    </button>
                    <button type="submit" class="modern-btn modern-btn-primary">
                        <i class="entypo-floppy"></i>
                        <?php echo get_phrase('save_strand'); ?>
                    </button>
                </div>
            <?php echo form_close(); ?>
        </div>
    </div>
</div>

<script>
// Dynamic subject loading for curriculum strand modal
$(document).ready(function() {
    // When class is selected, load subjects for that class
    $(document).on('change', '#class_level', function() {
        var classId = $(this).val();
        var subjectDropdown = $('#subject_id');
        
        // Clear current options
        subjectDropdown.html('<option value=""><?php echo get_phrase('loading'); ?>...</option>');
        
        if (classId) {
            // Fetch subjects for selected class
            $.ajax({
                url: '<?php echo site_url('admin/get_subjects_by_class'); ?>',
                type: 'POST',
                data: { class_id: classId },
                dataType: 'json',
                success: function(response) {
                    subjectDropdown.html('<option value=""><?php echo get_phrase('select_subject'); ?></option>');
                    
                    if (response.success && response.subjects.length > 0) {
                        $.each(response.subjects, function(index, subject) {
                            subjectDropdown.append(
                                $('<option></option>')
                                    .attr('value', subject.subject_id)
                                    .text(subject.name)
                            );
                        });
                    } else {
                        subjectDropdown.html('<option value=""><?php echo get_phrase('no_subjects_for_class'); ?></option>');
                    }
                },
                error: function() {
                    subjectDropdown.html('<option value=""><?php echo get_phrase('error_loading_subjects'); ?></option>');
                }
            });
        } else {
            subjectDropdown.html('<option value=""><?php echo get_phrase('select_class_first'); ?></option>');
        }
    });
    
    // Handle strand form submission via AJAX
    $(document).on('submit', '#strandForm', function(e) {
        e.preventDefault();
        
        var form = $(this);
        var formData = form.serialize();
        var actionUrl = form.attr('action');
        var submitBtn = form.find('button[type="submit"]');
        var originalBtnText = submitBtn.html();
        
        // Disable submit button and show loading
        submitBtn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> <?php echo get_phrase('saving'); ?>...');
        
        // Show loading modal
        showAjaxModal_alert('<?php echo get_phrase('saving'); ?>...', 'Loading');
        
        $.ajax({
            url: actionUrl,
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Show success in both modal and toast
                    showAjaxModal_alert(response.message || '<?php echo get_phrase('strand_saved_successfully'); ?>', 'Success', true);
                    showNotification('success', response.message || '<?php echo get_phrase('strand_saved_successfully'); ?>');
                    
                    // Close strand modal
                    $('#strandModal').modal('hide');
                    
                    // Page will reload automatically from showAjaxModal_alert
                } else {
                    // Show error in both modal and toast
                    showAjaxModal_alert(response.message || '<?php echo get_phrase('failed_to_save_strand'); ?>', 'Error');
                    showNotification('error', response.message || '<?php echo get_phrase('failed_to_save_strand'); ?>');
                    
                    // Re-enable submit button
                    submitBtn.prop('disabled', false).html(originalBtnText);
                }
            },
            error: function(xhr, status, error) {
                // Show error in both modal and toast
                var errorMsg = '<?php echo get_phrase('error_occurred'); ?>: ' + error;
                showAjaxModal_alert(errorMsg, 'Error');
                showNotification('error', errorMsg);
                
                // Re-enable submit button
                submitBtn.prop('disabled', false).html(originalBtnText);
            }
        });
    });
});

// Notification function
function showNotification(type, message) {
    var bgColor = type === 'success' ? '#10b981' : '#ef4444';
    var icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';
    
    // Create notification element
    var notification = $('<div>')
        .css({
            'position': 'fixed',
            'top': '20px',
            'right': '20px',
            'background': bgColor,
            'color': '#fff',
            'padding': '16px 24px',
            'border-radius': '8px',
            'box-shadow': '0 4px 12px rgba(0,0,0,0.15)',
            'z-index': '99999',
            'display': 'flex',
            'align-items': 'center',
            'gap': '12px',
            'min-width': '300px',
            'animation': 'slideInRight 0.3s ease'
        })
        .html('<i class="fa ' + icon + '" style="font-size: 20px;"></i><span>' + message + '</span>');
    
    // Add to body
    $('body').append(notification);
    
    // Remove after 3 seconds
    setTimeout(function() {
        notification.fadeOut(300, function() {
            $(this).remove();
        });
    }, 3000);
}
</script>

<style>
/* Modern Form Styles for Lesson Note Modals */
@keyframes slideInRight {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

.modern-form-group {
    margin-bottom: 24px;
}

.modern-form-group label {
    display: block;
    font-size: 14px;
    font-weight: 600;
    color: #374151;
    margin-bottom: 8px;
}

.modern-form-group label .required {
    color: #dc2626;
    margin-left: 4px;
}

.modern-form-control {
    width: 100%;
    padding: 12px 16px;
    font-size: 15px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    transition: all 0.2s;
}

.modern-form-control:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.modern-modal .modal-content {
    border-radius: 16px;
    border: none;
    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
}

.modern-modal .modal-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: #fff;
    border-radius: 16px 16px 0 0;
    padding: 24px;
    border: none;
}

.modern-modal .modal-title {
    font-size: 20px;
    font-weight: 700;
    color: #fff !important;
}

.modern-modal .modal-body {
    padding: 32px;
}

.modern-modal .modal-footer {
    padding: 20px 32px;
    border-top: 1px solid #e5e7eb;
}

.modern-btn-secondary {
    background: #6b7280;
    color: #fff;
}

.modern-btn-secondary:hover {
    background: #4b5563;
}
</style>
