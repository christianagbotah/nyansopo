
<?php 

    $running_sem = $this->db->get_where('settings', array('type' => 'running_sem'))->row()->description;
    $active_sms_service = $this->db->get_where('settings' , array('type' => 'active_sms_service'))->row()->description;
    $user_id = $this->session->userdata('login_user_id');
    $admin_level = $this->db->get_where('admin', array('admin_id' => $user_id))->row()->level;


?>

<section class="bg-gray-50 dark:bg-gray-900 py-3 sm:py-5">
  <div class="px-2 mx-auto max-w-screen-2xl">
      <div class="relative  bg-white shadow-md dark:bg-gray-800 p-4 sm:rounded-lg">
          <div class="overflow-x-scroll">
            <?php echo form_open(site_url('admin/bulk_invoice_delete/invoices_show/true'), array('class' => 'form-horizontal form-groups-bordered validate', 'id'=>'all_students_invoices',  'enctype' => 'multipart/form-data'));?>
              <table class="w-full text-xl text-left text-gray-500 dark:text-gray-400 datatable" id="all_students_invoices">
                  <thead class="text-xl text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                      <tr>
                          <!-- <th scope="col" class="p-4">
                              
                          </th> -->
                          <th scope="col" class="px-4 py-3">ID No</th>
                          <th scope="col" class="px-4 py-3">Name</th>
                          <!-- <th scope="col" class="px-4 py-3">Address</th> -->
                          <th scope="col" class="px-4 py-3">Payment Status</th>
                          <!-- <th scope="col" class="px-4 py-3" align="left">Invoice Code</th> -->
                          <th scope="col" class="px-4 py-3">Year|Term</th>
                          <th scope="col" class="px-4 py-3" align="right">Amount</th>
                          <th scope="col" class="px-4 py-3">Option</th>
                      </tr>
                  </thead>
                  <tbody>
                    <?php
                                foreach($students as $row):

                                    $this->db->select_sum('due');
                                    if($year != '0') {
                                        $this->db->where('year', $year);
                                    }
                                    
                                    if($term != '0') {
                                        $this->db->where('term', $term);
                                    }
                                    
                                    $this->db->where('can_delete !=', 'trash');
                                    $is_owing_amount = $this->db->get_where('invoice', array('student_id' => $row['student_id']))->row()->due;

                                    //
                                    if($year != '0') {
                                        $this->db->where('year', $year);
                                    }
                                    
                                    if($term != '0') {
                                        $this->db->where('term', $term);
                                    }
                                    
                                    $this->db->where('can_delete !=', 'trash');
                                    $owing_query = $this->db->get_where('invoice', array('student_id' => $row['student_id']));

                                    if($owing_query->num_rows() < 1) {

                                        continue;
                                    }


                                    $owing_row = $owing_query->row();

                                    $invoice_code = $owing_row->invoice_code;

                                    $payment_status_button = 'bg-green-400';
                                    $payment_status_text = 'Cleared';

                                    if($is_owing_amount > 0) {
                                        $payment_status_button = 'bg-red-700';
                                        $payment_status_text = 'Owing';
                                    }

                                    if($year == '0') {

                                        $year = 'All';
                                    }

                                    if($term == '0') {

                                        $term = 'All';
                                    }

                                    if ($is_owing_amount == 0) {
                                        $status = '<span class="btn btn-success btn-xs">'.get_phrase('paid').'</span>';
                                        //$payment_text = 'View Receipts';

                                    } elseif ($is_owing_amount < 0) {
                                        $status = '<span class="btn btn-warning btn-xs">'.get_phrase('over_paid').'</span>';
                                        //$payment_text = 'View Receipts';

                                    
                                    } else if($is_owing_amount > 0) {
                                        $status = '<span class="btn btn-danger btn-xs">'.get_phrase('unpaid').'</span>';
                                        
                                        //$payment_text = 'Take Payment';
                                    }

                                    
                                        // Hide "Take Payment" if fully paid
                                        if ($is_owing_amount <= 0) {
                                            $payment_option = '<li><a href="#" onclick="view_receipts_modal('.$row['student_id'].')" style="color: #d803f8;"><i class="entypo-eye"></i>&nbsp;View Receipts</a></li><li class="divider"></li>';
                                        } else {
                                            $payment_option = '<li><a href="#" onclick="invoice_pay_modal('.$row['student_id'].')" style="color: #d803f8;"><i class="entypo-bookmarks"></i>&nbsp;Take Payment</a></li><li class="divider"></li><li><a href="#" onclick="view_receipts_modal('.$row['student_id'].')" style="color: #d803f8;"><i class="entypo-eye"></i>&nbsp;View Receipts</a></li><li class="divider"></li>';
                                        }

                                    $options = '<div class="btn-group">'.get_action_button().'
                                        <ul class="dropdown-menu dropdown-default pull-right" role="menu">'.$payment_option.'<li><a href="#" onclick="invoice_view_modal(\''.$invoice_code.'\')" style="color: blue;"><i class="entypo-credit-card"></i>&nbsp;'.get_phrase('view_invoice').'</a></li><li class="divider"></li>

                                        <li><a href="#" onclick="bulk_invoice_view_modal('.$row['student_id'].')" style="color: black;"><i class="entypo-credit-card"></i>&nbsp;'.get_phrase('view_bulk_invoice').'</a></li><li class="divider"></li>

                                        <!-- SMS LINK -->
                                        <li>
                                            <a href="#" class="pt_link" onclick="check_sms_status(); navigation('.site_url('admin/message/sms_send?si='.$row['student_id']).')" style="color: green;"><i class="glyphicon glyphicon-envelope"></i>&nbsp;'.get_phrase('send_sMS').' 
                                            </a>
                                        </li>
                                        <li class="divider"></li>

                                        <!-- STUDENT PROFILE LINK -->
                                        <li>
                                            <a href="#" style="color: #0029ff;" onclick="invoice_load('.$row['student_id'].')">
                                                <i class="entypo-credit-card"></i>&nbsp;'.get_phrase('view_student\'s_invoices').'
                                                </a>
                                        </li>
                                        <li class="divider"></li>

                                        </ul></div>';

                                        $student_info_row = $this->db->get_where('student', array('student_id' => $row['student_id']))->row();
                                        $gender = $student_info_row->sex;
                                        $student_name = $student_info_row->name;
                                        $student_address = $student_info_row->address;
 

                    ?>
                      <tr class="border-b dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700">
                          <!-- <td class="w-4 px-4 py-3">
                              <div class="flex items-center">
                                  <input type="checkbox" id="checkbox-<?=$invoice_code;?>" onclick="allStudentsBoxChecked()" class="w-5 h-5 bg-gray-100 border-gray-300 rounded text-primary-600 focus:ring-primary-500 dark:focus:ring-primary-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600 cursor-pointer"  name="invoices_sel[]" value="<?=$invoice_code;?>">
                                  <label for="checkbox-<?=$invoice_code;?>" class="sr-only">checkbox</label>
                              </div>
                          </td> -->

                          <th class="px-4 py-2 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                              <?php echo $this->db->get_where('student' , array('student_id' => $row['student_id']))->row()->student_code;?>
                          </th>
                          <td scope="row" class="flex items-center px-4 py-2 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                              <img src="<?php echo $this->crud_model->get_image_url('student',$row['student_id'], $gender);?>" alt="Student image" class="w-auto h-8 mr-3 img-circle" width="30">
                              <?php
                                    echo $student_name;
                                ?>
                          </td>
                          <!-- <td class="px-4 py-2 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                            <?//=$student_address;?>
                              
                          </td> -->
                          <td class="px-4 py-2 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                              <div class="flex items-center">
                                  <div class="inline-block w-4 h-4 mr-2 <?=$payment_status_button;?> rounded-full"></div>
                                  <?=$payment_status_text;?>
                              </div>
                          </td>
                          <!-- <td align="left" class="px-4 py-2 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                            <?=$invoice_code;?> 
                          </td> -->
                          <td class="px-4 py-2 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                              <?=$year.'|'.$term;?>
                          </td>
                          <td align="right" class="px-4 py-2 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                              <?=number_format($is_owing_amount, 2, '.', ',');?>
                          </td>

                          <td align="center" class="px-4 py-2 font-medium text-gray-900 whitespace-nowrap dark:text-white"><?=$options;?></td>
                      </tr>
                      <?php
                  endforeach;
                      ?>
                  </tbody>
                  <tfoot id="stfooter" style="display: none">
                        <tr>
                            <td colspan="10" align="center">
                                <?php
                                    if($admin_level == 1) {?>

                                         <input type="submit" name="submit_delete" id="submit_delete_all" class="btn btn-danger rounded-lg font-bold uppercase" value="Delete Selected Invoices">
                                         <?php     
                                    } else {

                                        ?>

                                         <input type="submit" name="submit_delete_request" id="submit_delete_request" class="btn btn-danger rounded-lg font-bold uppercase" value="Request to delete selected invoices">
                                         <?php  
                                    }
                                ?>
                              
                            </td>
                        </tr>
                    </tfoot>
              </table>
            </form>
          </div>
      </div>
  </div>
</section>

<a href="#selection" id="anchor_a"></a>

<script type="text/javascript">

    jQuery(document).ready(function($) {
        //$('.datatable').DataTable();

    });



    $('#all_students_invoices').submit(function(event) {
        event.preventDefault();

        let admin_level = <?php echo $admin_level; ?>;

        if(admin_level == 1) {

            $('html, body').animate({
              scrollTop: ($('#receipt_bydate_form').offset().top )
            }, 1000);

            confirm_modal('<?php echo site_url('admin/bulk_invoice_delete'); ?>', 'modal_delete_warning', 'all_students_invoices');

        } else {

            let ajax_url = $(this).attr('action');
            
            $.ajax({
                  url: ajax_url,
                  type: 'POST',
                  dataType: 'json',
                  data: new FormData(this),
                  cache: false,
                  processData: false,
                  contentType: false
              })
              .done(function(data) {    
                if(data.status == 'success') {

                    showAjaxModal_alert(data.message, 'Success');
                } else {

                    showAjaxModal_alert(data.message, 'Error');
                }

                setTimeout(() => {
                    $('.close').click();
                }, 3000);
              })
              .fail(function(err) {
                 showAjaxModal_alert('ERROR: ' + err.responseText, 'Error');

              })
        }
        
    });

    

    $('.dropdown-menu li').click(function(event) {
        /* Act on the event */
        $('html, body').animate({
             scrollTop: ($('#class_holder').offset().top )
        }, 1000); 
    });

function check_sms_status() {
        var active_sms_service = '<?php echo $active_sms_service; ?>';
        if(active_sms_service == '' || active_sms_service == 'disabled' || active_sms_service == null) {
            alert('No active SMS service found. Please go to System Settings and activate SMS service and try again');
            toastr.error('No active SMS service found. Please go to System Settings and activate SMS service and try again');
            $('.pt_link').removeAttr('href');
            $('.pt_link').attr({href: '#'});
            return false;

        }
    }

    $('#bulk_invoice').click(function(event) {
        /* Act on the event */
       // alert('kkk');

       $('html, body').animate({
            scrollTop: ($('#top').offset().top),
       }, 1000);

        showAjaxModal_selection('<?php echo site_url('modal/popup/year_term_selection/'.$year.'/'.$term.'/'.$class_id.'/'.$running_sem);?>');
    });

    $('#bulk_bill').click(function(event) {
        /* Act on the event */
       // alert('kkk');

       $('html, body').animate({
            scrollTop: ($('#top').offset().top),
       }, 1000);

        showAjaxModal_selection('<?php echo site_url('modal/popup/year_term_selection/'.$year.'/'.$term.'/'.$class_id.'/'.$running_sem.'/bill');?>');
    });

    $('#general_payment').click(function(event) {
        //Scroll to the top
          $('html, body').animate({
              scrollTop: ($('#top').offset().top )
          }, 1000);

        general_payament_modal('<?php echo $class_id; ?>');
    });


    
    
</script>

