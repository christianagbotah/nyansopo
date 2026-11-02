<?php 
    $active_sms_service = $this->db->get_where('settings' , array('type' => 'active_sms_service'))->row()->description;
    $account_type = $this->session->userdata('login_type');
    $admin_level = 1; // Default to allow access
    
    // Check admin level if admin user
    if ($account_type == 'admin') {
        $name = $this->db->get_where($this->session->userdata('login_type'), array($this->session->userdata('login_type').'_id' => $this->session->userdata('login_user_id')))->row()->name;
        $admin_level = $this->db->get_where('admin', array('name' => $name))->row()->level;
    }

    // Feedback messages
    $display = 'none';
    $alert_type = 'success';
    if(isset($_GET['msg'])) {
        $msg_val = $_GET['msg'];
        if($msg_val == 1) {
            $display = 'block';
            $alert_type = 'danger';
            $feedback = 'Invalid Staff Email Address';
        } else if($msg_val == 2) {
            $display = 'block';
            $alert_type = 'success';
            $feedback = 'Non-Teaching Staff Data Added Successfully';
        } else if($msg_val == 3) {
            $display = 'block';
            $alert_type = 'danger';
            $feedback = get_phrase('this_email_address_is_not_available');
        } else if($msg_val == 4) {
            $display = 'block';
            $alert_type = 'success';
            $feedback = 'Non-Teaching Staff Data Updated Successfully';
        } else if($msg_val == 5) {
            $display = 'block';
            $alert_type = 'danger';
            $feedback = 'Could Not Update Your Data. Invalid Email Found!';
        } else if($msg_val == 6) {
            $display = 'block';
            $alert_type = 'success';
            $feedback = 'Selected Non-Teaching Staff Deleted Successfully!';
        } else if($msg_val == 7) {
            $display = 'block';
            $alert_type = 'success';
            $feedback = get_phrase('user_account_blocked_successfully');
        } else if($msg_val == 8) {
            $display = 'block';
            $alert_type = 'success';
            $feedback = get_phrase('user_account_unblocked_successfully');
        } else if($msg_val == 9) {
            $display = 'block';
            $alert_type = 'danger';
            $feedback = 'SMS service error - Could not send welcome message';
        } else {
            $feedback = '';
        }
    } else {
        $feedback = '';
    }
?>

<?php if(validation_errors()) :?>
<div class="alert alert-danger alert-dismissible" role="alert">
    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
        <span aria-hidden="true">&times;</span>
    </button>
    <strong><?php echo validation_errors(); ?></strong>
</div>
<?php endif;?>

<div style="display: <?= $display; ?>; margin-top: 10px" class="alert alert-<?= $alert_type; ?> alert-dismissible" role="alert">
    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
        <span aria-hidden="true">&times;</span>
    </button>
    <strong><?php echo $feedback; ?></strong>
</div>

<!-- Action Buttons Row -->
<div class="row mb-10 p-5" style="margin-top: 20px;">
    <div class="col-md-12 col-sm-12">
        <div class="py-5 flex gap-3">
            <a href="<?= base_url().'admin/non_teaching_staff_gender_report/';?>" target="_blank" class="p-3 bg-purple-400 text-white font-bold text-2xl rounded-lg">
                <i class="fa fa-print"></i> Print Gender Report
            </a>
            <a href="javascript:;" onclick="showAjaxModal('<?php echo site_url('modal/popup/modal_non_teaching_staff_add/');?>');"
            class="btn btn-primary h-16 rounded-lg text-xl font-bold content-center p-3">
                <i class="fa-solid fa-user-plus"></i>
                Add New Non-Teaching Staff
            </a>
        </div>
    </div>
</div>

<caption></caption>

<!-- Non-Teaching Staff DataTable -->
<table class="table table-hover table-striped table-active w-full text-xl text-left rtl:text-right text-gray-500 dark:text-gray-600 datatable" id="non_teaching_staff">
    <thead class="text-lg font-bold text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400 uppercase">
        <tr>
            <th colspan="9">
                <div class="grid grid-cols-3 gap-2 py-5">
                    <div>MALES: <strong id="males" class="p-3 bg-purple-100 rounded-full"></strong></div>
                    <div>FEMALES: <strong id="females" class="p-3 bg-purple-100 rounded-full"></strong></div>
                    <div>TOTAL: <strong id="total" class="p-3 bg-purple-100 rounded-full"></strong></div>
                </div>
            </th>
        </tr>
        <tr>
            <th scope="col" class="px-6 py-3">Photo</th>
            <th scope="col" class="px-6 py-3">Staff ID</th>
            <th scope="col" class="px-6 py-3">Name</th>
            <th scope="col" class="px-6 py-3">Gender</th>
            <th scope="col" class="px-6 py-3">Position</th>
            <th scope="col" class="px-6 py-3">Email</th>
            <th scope="col" class="px-6 py-3">Phone</th>
            <th scope="col" class="px-6 py-3">Account Status</th>
            <th scope="col" class="px-6 py-3">Options</th>
        </tr>
    </thead>
</table>

<!----- DATA TABLE EXPORT CONFIGURATIONS ---->
<script type="text/javascript">

jQuery(document).ready(function($) {
    $.fn.dataTable.ext.errMode = 'throw';
    let allNonTeachingStaff = $('#non_teaching_staff').DataTable({
        "processing": true,
        "serverSide": true,
        "ajax":{
            "url": "<?php echo site_url('admin/get_non_teaching_staff') ?>",
            "dataType": "json",
            "type": "POST",
        },
        "columns": [
            { "data": "photo" },
            { "data": "staff_id" },
            { "data": "name" },
            { "data": "gender" },
            { "data": "position" },
            { "data": "email" },
            { "data": "phone" },
            { "data": "account_status" },
            { "data": "options" },
        ],
        "columnDefs": [
            {
                "targets": [0, 6, 7, 8],
                "orderable": false
            },
        ]
    });

    allNonTeachingStaff.on('xhr', function(json) {
        var json = allNonTeachingStaff.ajax.json();
        $('#males').text(json.totalMale || 0);
        $('#females').text(json.totalFemale || 0);
        $('#total').text(json.grandTotalGender || 0);
    });

    $(document).on('hidden.bs.modal', '#modal_confirm_staff', function() {
        toastr.info('Modal closed');
        location.reload();
    });
});

function non_teaching_staff_edit_modal(staff_id) {
    showAjaxModal('<?php echo site_url('modal/popup/modal_non_teaching_staff_edit/');?>' + staff_id);
}

function non_teaching_staff_delete_confirm(staff_id) {
    showConfirmModal(
        'Confirm Delete Non-Teaching Staff',
        'Are you sure you want to delete this non-teaching staff member? This action cannot be undone.',
        function() {
            $('.close').click();
            showAjaxModal_alert('Please wait...<i class="fa fa-3x fa-spinner fa-pulse"></i>.', 'Loading');
            $.ajax({
                url: '<?php echo site_url('admin/non_teaching_staff/delete/');?>' + staff_id,
                type: 'POST',
                dataType: 'json',
            })
            .done(function(response) {
                if(response.message == 'done') {
                    showAjaxModal_alert('Non-teaching staff deleted successfully.', 'Success');

                    setTimeout(() => {
                        $('.close').click();
                        navigation('<?php echo site_url('admin/'); ?>' + response.route);
                    }, 3000);
                } else {
                    showAjaxModal_alert('Deletion failed!', 'Error');
                }
            })
            .fail(function(err) {
                showAjaxModal_alert(err.responseText, 'Error');
            });
        },
        '<?php echo get_phrase('delete'); ?>',
        'danger'
    );
}

function account_block(staff_id) {
    showConfirmModal(
        'Confirm Block Non-Teaching Staff',
        'Are you sure you want to block this non-teaching staff account? They will not be able to log in until unblocked.',
        function() {
            $('.close').click();
            showAjaxModal_alert('<center><div style="font-size: 20px; font-weight: bolder;">Processing Data...<br><i class="fa fa-3x fa-spinner fa-pulse"></i></div></center>', 'Loading');

            $.ajax({
                url: '<?php echo site_url('admin/non_teaching_staff/block/');?>' + staff_id,
                type: 'POST',
                dataType: 'html',
            })
            .done(function(response) {
                showAjaxModal_alert('Account Blocked Successfully', 'Success');

                setTimeout(() => {
                    navigation('<?php echo site_url('admin/non_teaching_staff');?>');
                    $('.modal').removeClass('modal-backdrop');
                    $('.modal').removeClass('fade');
                    $('.modal').removeClass('in');
                    $('.close').click();
                }, 3000);
            })
            .fail(function(err) {
                showAjaxModal_alert('Error: ' + err.responseText, 'Error');
            });
        },
        '<?php echo get_phrase('block'); ?>',
        'danger'
    );
}

function account_unblock(staff_id) {
    showConfirmModal(
        'Confirm Unblock Non-Teaching Staff',
        'Are you sure you want to unblock this non-teaching staff account? They will be able to log in again.',
        function() {
            $('.close').click();
            showAjaxModal_alert('<center><div style="font-size: 20px; font-weight: bolder;">Processing Data...<br><i class="fa fa-3x fa-spinner fa-pulse"></i></div></center>', 'Loading');

            $.ajax({
                url: '<?php echo site_url('admin/non_teaching_staff/unblock/');?>' + staff_id,
                type: 'POST',
                dataType: 'html',
            })
            .done(function(response) {
                showAjaxModal_alert('Account Unblocked Successfully', 'Success');

                setTimeout(() => {
                    navigation('<?php echo site_url('admin/non_teaching_staff');?>');
                    $('.modal').removeClass('modal-backdrop');
                    $('.modal').removeClass('fade');
                    $('.modal').removeClass('in');
                    $('.close').click();
                }, 3000);
            })
            .fail(function(err) {
                showAjaxModal_alert('Error: ' + err.responseText, 'Error');
            });
        },
        '<?php echo get_phrase('unblock'); ?>',
        'success'
    );
}

function check_sms_status() {
    var active_sms_service = '<?php echo $active_sms_service; ?>';
    if(active_sms_service == '' || active_sms_service == 'disabled' || active_sms_service == null) {
        alert('No active SMS service found. Please go to System Settings and activate SMS service and try again');
        toastr.error('No active SMS service found. Please go to System Settings and activate SMS service and try again');
        return false;
    }
}

</script>
