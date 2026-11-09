<?php
$edit_data = $this->db->get_where('noticeboard', array('notice_id' => $param2))->result_array();

//get id of the user who accessed this page and marked this message as read
$account_type       =   $this->session->userdata('login_type');

if($account_type == 'admin') {
    $user_id = $this->db->get_where('admin', array('admin_id' => $this->session->userdata('admin_id')))->row()->admin_id;
}elseif($account_type == 'accountant') {
    $user_id = $this->db->get_where('accountant', array('accountant_id' => $this->session->userdata('accountant_id')))->row()->accountant_id;
}elseif($account_type == 'parent') {
    $user_id = $this->db->get_where('parent', array('parent_id' => $this->session->userdata('parent_id')))->row()->parent_id;
}elseif($account_type == 'teacher') {
    $user_id = $this->db->get_where('teacher', array('teacher_id' => $this->session->userdata('teacher_id')))->row()->teacher_id;
}elseif($account_type == 'student') {
    $user_id = $this->db->get_where('student', array('student_id' => $this->session->userdata('student_id')))->row()->student_id;
}elseif($account_type == 'librarian') {
    $user_id = $this->db->get_where('librarian', array('librarian_id' => $this->session->userdata('librarian_id')))->row()->librarian_id;
}//end

foreach ($edit_data as $row):
?>
<center>
    <a onClick="PrintElem('#notice_print')" class="btn btn-info btn-icon icon-left hidden-print pull-right">
        Print Notice
        <i class="entypo-print"></i>
    </a>
    
   
</center>

    <br><br>

    <div class="row" id="notice_print">
            <div class="col-md-12">

                <div class="panel panel-primary" data-collapsed="0">
                        
                    <div class="panel-body">
                        <p><b>Title: </b><?php echo $row['notice_title']; ?></p>
                        <hr>
                        <div><img src="<?php echo base_url(); ?>uploads/frontend/noticeboard/<?php echo $row['image'];?>" height="150"></div>
                        <hr>
                        <b>Notice:</b>
                        <p><?php echo $row['notice'] ?></p>
                        <hr>
                        <div class="col-sm-4 col-xs-4">
                           <p><b>Date: </b><?php echo date('d M Y',$row['create_timestamp']) ?></p> 
                        </div>
                        <div class="col-sm-8 col-xs-8 pull-right" style="margin-top: -5px;">
                            
                            <?php echo form_open('',array('class' => 'form-horizontal')); ?>
                            <div class="form-group">
                                <label class="control-label col-sm-5 col-xs-5">Mark As Read </label>  

                                <div class="switch-button  showcase-switch-button">
                                <input id="marked_read"  type="checkbox"  value="<?php echo $param2; ?>" name="marked_read">
                                <label for="marked_read" ></label>
                                </div>    

                                <input type="hidden" name="user_id" id="user_id" value="<?php echo $user_id; ?>">                 
                            </div>

                            

                        </form>
                        </div>
                        

                        
                        
                    </div>
                </div>
            </div>
    </div>
<?php endforeach; ?>


<script type="text/javascript">

    // print invoice function
    function PrintElem(elem)
    {
        Popup($(elem).html());
    }

    function Popup(data)
    {
        var mywindow = window.open('', 'notice', 'height=400,width=600');
        mywindow.document.write('<html><head><title>Notice</title>');
        mywindow.document.write('<link rel="stylesheet" href="assets/css/neon-theme.css" type="text/css" />');
        mywindow.document.write('<link rel="stylesheet" href="assets/js/datatables/responsive/css/datatables.responsive.css" type="text/css" />');
        mywindow.document.write('</head><body >');
        mywindow.document.write(data);
        mywindow.document.write('</body></html>');

        var is_chrome = Boolean(mywindow.chrome);
        if (is_chrome) {
            setTimeout(function() {
                mywindow.print();
                mywindow.close();

                return true;
            }, 250);
        }
        else {
            mywindow.print();
            mywindow.close();

            return true;
        }

        return true;
    }

</script>

