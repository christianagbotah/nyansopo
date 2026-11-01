        <!--visible to only super admin-->
       <?php
      $name = $this->db->get_where($this->session->userdata('login_type'), array($this->session->userdata('login_type').'_id' => $this->session->userdata('login_user_id')))->row()->name;
      $admin_level = $this->db->get_where('admin', array('name' => $name))->row()->level;
    ?>
    <?php if ($account_type == 'admin' && $admin_level == 1):?>
                <!-- Action Buttons Row -->
                <div class="row mb-10 p-5" style="margin-top: 20px;">
                    <div class="col-md-12 col-sm-12">
                        <div class="py-5">
                            <a href="javascript:;" onclick="$.ajax({url: '<?php echo site_url('modal/popup/modal_admin_add/');?>', type: 'GET', success: function(response) { $('#modal_ajax').html(response); $('#modal_ajax').modal('show', {backdrop: 'static'}); }});"
                            class="btn btn-primary h-16 rounded-lg text-xl font-bold content-center p-3">
                                <i class="entypo-plus-circled"></i>
                                Add New Admin
                            </a>
                        </div>
                    </div>
                </div>
    <?php endif; ?>
        <?php if(validation_errors()) :?>
                <div class="alert alert-danger alert-dismissible" role="alert">
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span>
                    </button>
                   <strong> <?php echo validation_errors(); ?></strong>
                </div>
                <?php endif;?>

                    <table class="table table-hover table-striped table-active w-full text-xl text-left rtl:text-right text-gray-500 dark:text-gray-600 datatable" id="admins">
                      <thead class="text-lg font-bold text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400 uppercase">
                          <tr>
                            <th scope="col" class="px-6 py-3">Full Name</th>
                            <th scope="col" class="px-6 py-3">Email Address</th>
                            <th scope="col" class="px-6 py-3">Auth Key</th>
                            <th scope="col" class="px-6 py-3">Phone</th>
                            <th scope="col" class="px-6 py-3">Designation</th>
                            <th scope="col" class="px-6 py-3">Account Status</th>
                            <th scope="col" class="px-6 py-3">options</th>
                          </tr>
                      </thead>
                </table>



<!-----  DATA TABLE EXPORT CONFIGURATIONS ---->
<script type="text/javascript">

	jQuery(document).ready(function($) {
        $.fn.dataTable.ext.errMode = 'throw';
        $('#admins').DataTable({
            "processing": true,
            "serverSide": true,
            "ajax":{
                "url": "<?php echo site_url('admin/get_admin') ?>",
                "dataType": "json",
                "type": "POST",
            },
            "columns": [
                { "data": "name" },
                { "data": "email" },
                { "data": "auth_key" },
                { "data": "phone" },
                { "data": "designation" },
                { "data": "account_status" },
                { "data": "options" },
            ],
            "columnDefs": [
                {
                    "targets": [5],
                    "orderable": false
                },
            ]
        });
	});

    function admin_edit_modal(admin_id) {
        $.ajax({
            url: '<?php echo site_url('modal/popup/modal_admin_edit/');?>' + admin_id,
            type: 'GET',
            success: function(response) {
                $('#modal_ajax').html(response);
                $('#modal_ajax').modal('show', {backdrop: 'static'});
            }
        });
    }

    function admin_delete_confirm(admin_id) {
        showConfirmModal(
            'Confirm Delete Administrator',
            'Are you sure you want to delete this administrator? This action cannot be undone.',
            function() {
                $('.close').click();
                showAjaxModal_alert('Please wait...<i class="fa fa-3x fa-spinner fa-pulse"></i>.', 'Loading');
                $.ajax({
                    url: '<?php echo site_url('admin/admins/delete/');?>' + admin_id,
                    type: 'POST',
                    dataType: 'json',
                })
                .done(function(response) {
                    if(response.message == 'done') {
                        showAjaxModal_alert('Administrator deleted successfully.', 'Success');

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

    function account_block(admin_id) {
        showConfirmModal(
            'Confirm Block Administrator',
            'Are you sure you want to block this administrator account? They will not be able to log in until unblocked.',
            function() {
                $('.close').click();
                showAjaxModal_alert('<center><div style="font-size: 20px; font-weight: bolder;">Processing Data...<br><i class="fa fa-3x fa-spinner fa-pulse"></i></div></center>', 'Loading');

                $.ajax({
                    url: '<?php echo site_url('admin/admins/block/');?>' + admin_id,
                    type: 'POST',
                    dataType: 'html',
                })
                .done(function(response) {
                    showAjaxModal_alert('Account Blocked Successfully', 'Success');

                    setTimeout(() => {
                        navigation('<?php echo site_url('admin/admins');?>');
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

    function account_unblock(admin_id) {
        showConfirmModal(
            'Confirm Unblock Administrator',
            'Are you sure you want to unblock this administrator account? They will be able to log in again.',
            function() {
                $('.close').click();
                showAjaxModal_alert('<center><div style="font-size: 20px; font-weight: bolder;">Processing Data...<br><i class="fa fa-3x fa-spinner fa-pulse"></i></div></center>', 'Loading');

                $.ajax({
                    url: '<?php echo site_url('admin/admins/unblock/');?>' + admin_id,
                    type: 'POST',
                    dataType: 'html',
                })
                .done(function(response) {
                    showAjaxModal_alert('Account Unblocked Successfully', 'Success');

                    setTimeout(() => {
                        navigation('<?php echo site_url('admin/admins');?>');
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

</script>
