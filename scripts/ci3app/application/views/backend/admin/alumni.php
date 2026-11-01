<?php
$name = $this->db->get_where($this->session->userdata('login_type'), array($this->session->userdata('login_type').'_id' => $this->session->userdata('login_user_id')))->row()->name;
    $admin_level = $this->db->get_where('admin', array('name' => $name))->row()->level;

    

//feedback
$display = 'none';
$alert_type = 'success';
if(isset($_GET['msg'])) {
    $msg_val = $_GET['msg'];
    if($msg_val == 1) {
        $display = 'block';
        $alert_type = 'danger';
        $feedback = 'Did You Change This Student\'s ID No? Duplicate Found. Please Maintain The Old ID No!';
    } else if($msg_val == 2) {
        $display = 'block';
        $alert_type = 'danger';
        $feedback = 'Could Not Update. Invalid Email Found!';
    } else if($msg_val == 3) {
        $display = 'block';
        $alert_type = 'success';
        $feedback = 'Student\'s Information Updated Successfully';
    } else if($msg_val == 4) {
        $display = 'block';
        $alert_type = 'danger';
        $feedback = 'Did You Change This Student\'s Email? Duplicate Found. Please Maintain The Old Email Address!';
    } else if($msg_val == 5) {
        $display = 'block';
        $alert_type = 'success';
        $feedback = 'Student\'s Information Updated Successfully';
    }

}

    $active_sms_service = $this->db->get_where('settings' , array('type' => 'active_sms_service'))->row()->description;
?>
<hr />


<div class="row">
    <div class="col-md-12">

        <ul class="nav nav-tabs bordered">
            <li class="active">
                <a href="#home" data-toggle="tab">
                    <span class="visible-xs"><i class="entypo-users"></i></span>
                    <span class="hidden-xs"><?php echo get_phrase('all_students');?></span>
                </a>
            </li>
        </ul>

        <div class="tab-content">
            <div style="display: <?= $display; ?>; margin-top: 10px" class="alert alert-<?= $alert_type; ?> alert-dismissible" role="alert">
                <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span>
                </button>
               <strong> <?php echo $feedback; ?></strong>
            </div>

                <table class="table table-responsive table-bordered datatable" id="old_students">
                    <thead>
                        <tr>
                            <th><div><?php echo get_phrase('iD_no');?></div></th>
                            <th><div><?php echo get_phrase('photo');?></div></th>
                            <th><div><?php echo get_phrase('name');?></div></th>
                            <th><div><?php echo get_phrase('year_batch');?></div></th>
                            <th class="span3"><div><?php echo get_phrase('address');?></div></th>
                            <th><div><?php echo get_phrase('email');?></div></th>
                            <th><div><?php echo get_phrase('auth_key');?></div></th>
                            <th><div><?php echo get_phrase('account_status');?></div></th>
                            <th><div><?php echo get_phrase('options');?></div></th>
                        </tr>
                    </thead>
                    <tbody>
                        
                    </tbody>
                </table>

        </div>


    </div>
</div>


<script type="text/javascript">

	jQuery(document).ready(function($) {

     
        $.fn.dataTable.ext.errMode = 'throw';
        $('#old_students').DataTable({
            "processing": true,
            "serverSide": true,
            "ajax":{
                "url": "<?php echo site_url('admin/load_alumni') ?>",
                "dataType": "json",
                "type": "POST",
            },
            "columns": [
                { "data": "code" },
                { "data": "photo" },
                { "data": "name" },
                { "data": "year_batch" },
                { "data": "address" },
                { "data": "email" },
                { "data": "auth" },
                { "data": "account_status" },
                { "data": "options" },
            ],
            "columnDefs": [
                {
                    "targets": [1,5,6,7],
                    "orderable": false
                },
            ]
        });

	});



    function account_block(student_id) {

        confirm_modal('<?php echo site_url('admin/student/block/');?>' + student_id, 'modal_block', 'old_students');   
    }

    function account_unblock(student_id) {
        confirm_modal('<?php echo site_url('admin/student/unblock/');?>' + student_id, 'modal_unblock', 'old_students');   
    }

    function account_mute(student_id) {
        confirm_modal('<?php echo site_url('admin/student/mute/');?>' + student_id, 'modal_mute', 'old_students', 'old_students');   
    }

    function account_unmute(student_id) {
        confirm_modal('<?php echo site_url('admin/student/unmute/');?>' + student_id, 'modal_unmute', 'old_students');   
    }


//adjustments
    //delete student
    function student_delete(student_id) {
        confirm_modal("<?=site_url('admin/delete_student/'); ?>" + student_id)
    }

    //student ID generation
    function student_id_card(student_id) {
        showAjaxModal("<?=site_url('modal/popup/student_id/'); ?>" + student_id)
    }

    //student edit
    function student_edit(student_id) {
        showAjaxModal("<?=site_url('modal/popup/modal_student_edit/'); ?>" + student_id)
    }

    //student profile
    function student_profile(student_id) {
        navigation("<?=site_url('admin/student_profile/'); ?>" + student_id)
    }

    //student sms
    function student_sms(student_id) {
        navigation("<?=site_url('admin/message/sms_send?si='); ?>" + student_id)
    }

    //student marksheet
    function student_marksheet(student_id) {
        navigation("<?=site_url('admin/student_marksheet/'); ?>" + student_id)
    }

    //student marksheet creche
    function student_marksheet_creche(student_id) {
        navigation("<?=site_url('admin/student_marksheet_creche/'); ?>" + student_id)
    }

</script>
