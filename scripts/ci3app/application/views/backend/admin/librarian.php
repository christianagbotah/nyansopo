<a href="javascript:;" onclick="showAjaxModal('<?php echo site_url('modal/popup/librarian_add');?>');"
    class="btn btn-primary pull-right">
        <i class="entypo-plus-circled"></i>
        <?php echo get_phrase('add_new_librarian');?>
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
            <th><div><?php echo get_phrase('email');?></div></th>
            <th><div><?php echo get_phrase('auth_key');?></div></th>
            <th><div><?php echo get_phrase('phone');?></div></th>
            <th><div><?php echo get_phrase('account_status');?></div></th>
            <th><div><?php echo get_phrase('options');?></div></th>
        </tr>
    </thead>
    <tbody>
        <?php
        $count = 1;
        $librarians   =   $this->db->get('librarian')->result_array();
        foreach($librarians as $row): ?>

            <?php 
                if($row['block_limit'] == 3) {
                    $as_btn = 'Blocked';
                    $btn_style = 'danger';
                    $btn_cont = '<span class="caret"></span></button><ul class="dropdown-menu dropdown-default pull-right" role="menu"><li><a href="#" onclick="account_unblock('.$row['librarian_id'].')" style="color: green;"><i class="fa fa-unlock"></i>&nbsp;'.get_phrase('unblock').'</a></li></li></ul>';
                } else {
                    $as_btn = 'Active';
                    $btn_style = 'success';
                    $btn_cont = '<span class="caret"></span></button><ul class="dropdown-menu dropdown-default pull-right" role="menu"><li><a href="#" onclick="account_block('.$row['librarian_id'].')" style="color: red;"><i class="glyphicon glyphicon-lock"></i>&nbsp;'.get_phrase('block').'</a></li></li></ul>';
                }

            $account_status = '<div class="btn-group"><button type="button" class="btn btn-'.$btn_style.' btn-sm dropdown-toggle" data-toggle="dropdown">
                                '.$as_btn.' '.$btn_cont.'</div>';
            ?>

            <tr>
                <td><?php echo $row['name'];?></td>
                <td><?php echo $row['email'];?></td>
                <td style="font-weight: bolder; letter-spacing: 3px;"><?php echo $row['authentication_key'];?></td>
                <td><?php echo $row['phone'];?></td>
                <td><?php echo $account_status;?></td>
                <td>

                    <div class="btn-group">
                        <button type="button" class="btn btn-info btn-sm dropdown-toggle" data-toggle="dropdown">
                            Action <span class="caret"></span>
                        </button>
                        <ul class="dropdown-menu dropdown-default pull-right" role="menu">

                            <li>
                                <a href="#" onclick="showAjaxModal('<?php echo site_url('modal/popup/librarian_edit/'.$row['librarian_id']);?>');" style='color: green;'>
                                    <i class="entypo-pencil"></i>
                                    <?php echo get_phrase('edit');?>
                                </a>
                            </li>
                            <li class="divider"></li>

                            <li>
                                <a href="#" onclick="confirm_modal('<?php echo site_url('admin/librarian/delete/'.$row['librarian_id']);?>');" style='color: red;'>
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

    function account_block(librarian_id) {
        confirm_modal('<?php echo site_url('admin/librarian/block/');?>' + librarian_id, 'modal_block', 'librarian');   
    }

    function account_unblock(librarian_id) {
        confirm_modal('<?php echo site_url('admin/librarian/unblock/');?>' + librarian_id, 'modal_unblock', 'librarian');   
    }

</script>
