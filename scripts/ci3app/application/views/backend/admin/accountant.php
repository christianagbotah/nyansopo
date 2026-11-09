<a href="javascript:;" onclick="showAjaxModal('<?php echo site_url('modal/popup/accountant_add');?>');"
    class="btn btn-primary pull-right">
        <i class="entypo-plus-circled"></i>
        <?php echo get_phrase('add_new_accountant');?>
</a>
<br><br>

<?php if(validation_errors()) :?>
<div class="alert alert-danger alert-dismissible" role="alert">
    <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span>
    </button>
   <strong> <?php echo validation_errors(); ?></strong>
</div>
<?php endif;?>

<table class="table table-bordered datatable" id="table_export">
    <thead>
        <tr>
            <th><div><?php echo get_phrase('name');?></div></th>
            <th><div><?php echo get_phrase('phone');?></div></th>
            <th><div><?php echo get_phrase('email');?></div></th>
            <th><div><?php echo get_phrase('auth_key');?></div></th>
            <th><div><?php echo get_phrase('account_status');?></div></th>
            <th><div><?php echo get_phrase('options');?></div></th>
        </tr>
    </thead>
    <tbody>
        <?php
        $count = 1;
        $accountants   =   $this->db->get('accountant')->result_array();
        foreach($accountants as $row): ?>

            <?php 
                if($row['block_limit'] == 3) {
                    $as_btn = 'Blocked';
                    $btn_style = 'danger';
                    $btn_cont = '<span class="caret"></span></button><ul class="dropdown-menu dropdown-default pull-right" role="menu"><li><a href="#" onclick="account_unblock('.$row['accountant_id'].')" style="color: green;"><i class="fa fa-unlock"></i>&nbsp;'.get_phrase('unblock').'</a></li></li></ul>';
                } else {
                    $as_btn = 'Active';
                    $btn_style = 'success';
                    $btn_cont = '<span class="caret"></span></button><ul class="dropdown-menu dropdown-default pull-right" role="menu"><li><a href="#" onclick="account_block('.$row['accountant_id'].')" style="color: red;"><i class="glyphicon glyphicon-lock"></i>&nbsp;'.get_phrase('block').'</a></li></li></ul>';
                }

            $account_status = '<div class="btn-group"><button type="button" class="btn btn-'.$btn_style.' btn-sm dropdown-toggle" data-toggle="dropdown">
                                '.$as_btn.' '.$btn_cont.'</div>';
            ?>

            <tr>
                <td><?php echo $row['name'];?></td>
                <td><?php echo $row['phone'];?></td>
                <td><?php echo $row['email'];?></td>
                <td style="font-weight: bolder; letter-spacing: 3px;"><?php echo $row['authentication_key'];?></td>
                <td><?php echo $account_status;?></td>
                <td>

                    <div class="btn-group">
                        <button type="button" class="btn btn-info btn-sm dropdown-toggle" data-toggle="dropdown">
                            Action <span class="caret"></span>
                        </button>
                        <ul class="dropdown-menu dropdown-default pull-right" role="menu">

                            <li>
                                <a href="#" onclick="showAjaxModal('<?php echo site_url('modal/popup/accountant_edit/' . $row['accountant_id']);?>')" style='color: green;'>
                                    <i class="entypo-pencil"></i>
                                    <?php echo get_phrase('edit');?>
                                </a>
                            </li>
                            <li class="divider"></li>

                            <li>
                                <a href="#" onclick="accountant_delete_confirm(<?php echo $row['accountant_id'];?>);" style='color: red;'>
                                    <i class="entypo-trash"></i>
                                    <?php echo get_phrase('delete');?>
                                </a>
                            </li>
                        </ul>
                    </div>

                </td>
            </tr>
        <?php endforeach;?>
    </tbody>
</table>



<!-----  DATA TABLE EXPORT CONFIGURATIONS ---->
<script type="text/javascript">

    jQuery(document).ready(function($)
    {
        $('#table_export').dataTable();
    });

    function accountant_delete_confirm(accountant_id) {
        showConfirmModal(
            'Confirm Delete Accountant',
            'Are you sure you want to delete this accountant? This action cannot be undone.',
            function() {
                $('.close').click();
                showAjaxModal_alert('Please wait...<i class="fa fa-3x fa-spinner fa-pulse"></i>.', 'Loading');
                $.ajax({
                    url: '<?php echo site_url('admin/accountant/delete/');?>' + accountant_id,
                    type: 'POST',
                    dataType: 'json',
                })
                .done(function(response) {
                    if(response.message == 'done') {
                        showAjaxModal_alert('Accountant deleted successfully.', 'Success');

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

    function account_block(accountant_id) {
        showConfirmModal(
            'Confirm Block Accountant',
            'Are you sure you want to block this accountant account? They will not be able to log in until unblocked.',
            function() {
                $('.close').click();
                showAjaxModal_alert('<center><div style="font-size: 20px; font-weight: bolder;">Processing Data...<br><i class="fa fa-3x fa-spinner fa-pulse"></i></div></center>', 'Loading');

                $.ajax({
                    url: '<?php echo site_url('admin/accountant/block/');?>' + accountant_id,
                    type: 'POST',
                    dataType: 'html',
                })
                .done(function(response) {
                    showAjaxModal_alert('Account Blocked Successfully', 'Success');

                    setTimeout(() => {
                        navigation('<?php echo site_url('admin/accountant');?>');
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

    function account_unblock(accountant_id) {
        showConfirmModal(
            'Confirm Unblock Accountant',
            'Are you sure you want to unblock this accountant account? They will be able to log in again.',
            function() {
                $('.close').click();
                showAjaxModal_alert('<center><div style="font-size: 20px; font-weight: bolder;">Processing Data...<br><i class="fa fa-3x fa-spinner fa-pulse"></i></div></center>', 'Loading');

                $.ajax({
                    url: '<?php echo site_url('admin/accountant/unblock/');?>' + accountant_id,
                    type: 'POST',
                    dataType: 'html',
                })
                .done(function(response) {
                    showAjaxModal_alert('Account Unblocked Successfully', 'Success');

                    setTimeout(() => {
                        navigation('<?php echo site_url('admin/accountant');?>');
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
