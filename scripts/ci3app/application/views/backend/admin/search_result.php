<?php
$name = $this->db->get_where($this->session->userdata('login_type'), array($this->session->userdata('login_type').'_id' => $this->session->userdata('login_user_id')))->row()->name;
$admin_level = $this->db->get_where('admin', array('name' => $name))->row()->level;
?>
<hr />
<div class="row">
    <div class="col-md-12">

      <table class="w-full text-xl text-left text-gray-500 dark:text-gray-400 datatable" id="table_export">
                  <thead class="text-xl text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                      <tr>
                          <th scope="col" class="px-4 py-3">ID No</th>
                          <th scope="col" class="px-4 py-3 text-center" style="text-align: center !important;">Photo</th>
                          <th scope="col" class="px-4 py-3">Name</th>
                          <th scope="col" class="px-4 py-3 text-left" style="text-align: left !important;">Address</th>
                          <th scope="col" class="px-4 py-3">Residence Type</th>
                          <th scope="col" class="px-4 py-3">Class</th>
                          <?php if($admin_level != 4): ?>
                          <th scope="col" class="px-4 py-3 text-center" style="text-align: center !important;">Account Status</th>
                          <th scope="col" class="px-4 py-3 text-center" style="text-align: center !important;">Option</th>
                          <?php else: ?>
                          <th scope="col" class="px-4 py-3">Guardian Name</th>
                          <th scope="col" class="px-4 py-3">Guardian Contact</th>
                          <th scope="col" class="px-4 py-3 text-center">View</th>
                          <?php endif; ?>
                      </tr>
                    </thead>
                    <tbody>
              <?php
										foreach ($student_information as $row):
											$enrollment_query = $this->crud_model->getStudentCurrentEnrollmentStatusRow($row['student_id']);

											$class_id = $enrollment_query->class_id;
											$section_id = $enrollment_query->section_id;
											$status = $enrollment_query->mute;
											$residence_type = $enrollment_query->residence_type;

											$class_name = $this->crud_model->get_class_name($class_id);
											$class_name_numeric = $this->crud_model->get_class_name_numeric($class_id);

											$section_name = $this->db->get_where('section', array('section_id' => $section_id, 'class_id' => $class_id))->row()->name;

											$studentOwes = $this->financial_report_model->getAllBillInvoicesOwingByStudentId($row['student_id']);

											$student_class = $class_name . ' ' . $class_name_numeric . ' ' . $section_name;

											$status_display = '';

											if ($status == 0) {
												$status_display = '<span class="btn btn-success btn-sm"><i class="fa fas fa-check"></i> Active</span>';
											} else {
												$status_display = '<span class="btn btn-default btn-sm"><i class="fa fas fa-times"></i> Muted</span>';
											}
											?>
														      <tr class="border-b dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700">
														          <td class="px-4 py-3"><?php echo $this->db->get_where('student', array(
												'student_id' => $row['student_id'],
											))->row()->student_code; ?></td>
														          <td class="px-4 py-3" align="center"><img src="<?php echo $this->crud_model->get_image_url('student', $row['student_id'], $row['sex']); ?>" class="img-circle" width="30" /></td>
														          <td class="px-4 py-3">
														              <?php
											echo $this->db->get_where('student', array(
												'student_id' => $row['student_id'],
											))->row()->name;
											?>
														          </td>
														          <td class="px-4 py-3">
														              <?php
											echo $this->db->get_where('student', array(
												'student_id' => $row['student_id'],
											))->row()->address;
											?>
														          </td>
														          <td class="px-4 py-3">
														              <?php
																				echo $residence_type;
																				?>
																			</td>
														          <td class="px-4 py-3">
											<?php echo $student_class; ?>
														          </td>

														          <?php if($admin_level != 4): ?>
														          <td class="px-4 py-3" align="center">
														              <?php
											echo $status_display;
											?>
														          </td>


				          <td class="px-4 py-3" align="center">

				              <div class="btn-group">
				                  <?php
				                  	echo get_action_button();
				                  ?>
				                  <ul class="dropdown-menu dropdown-default pull-right" role="menu">

				                      <!-- STUDENT PROFILE LINK -->
				                      <li>
				                          <a href="#" class="font-bold text-xl" onclick="navigation('<?php echo site_url('admin/student_profile/'.$row['student_id']);?>')" style='color: #0029ff;'>
                                  	<i class="entypo-user"></i>
                                      Profile
                                  </a>
				                      </li>
				                      <?php
				                      	if(count($studentOwes) > 0):
				                      ?>
				                      <li class="divider"></li>
				                      <li>
				                        <a href="#" class="font-bold text-xl" onclick="invoice_pay_modal('<?=$row['student_id'] ?>')" style='color: green;'>
				                          <i class="entypo-credit-card"></i>
				                            <?php echo get_phrase('Take Payment'); ?>
				                        </a>
				                      </li>
				                      <li class="divider"></li>
				                      <?php
				                      	endif;
				                      ?>

				                      <!-- <li><a href="#" onclick="showStudentBillReport(<?=$row['student_id'];?>)" style="color: black;"><i class="entypo-credit-card"></i>&nbsp; View Bill</a></li><li class="divider"></li> -->

				                      <!-- STUDENT EDITING LINK -->
				                      <!--<li>
				                          <a href="#" onclick="showAjaxModal('<?php //echo site_url('modal/popup/modal_student_edit/' . $row['student_id']); ?>');" style='color: #0029ff;'>
				                              <i class="entypo-pencil"></i>
				                                  <?php //echo get_phrase('edit'); ?>
				                              </a>
				                      </li>-->

				                      <li class="divider"></li>
				                      <li>
				                          <a href="#" class="font-bold text-xl" onclick="showAjaxModal('<?php echo site_url('modal/popup/student_id/' . $row['student_id']); ?>');"  style='color: #fd09ff;'>
				                              <i class="entypo-vcard"></i>
				                              <?php echo get_phrase('generate_id'); ?>
				                          </a>
				                      </li>

				                      <li class="divider"></li>
				                      <li>
				                          <a href="#" class="font-bold text-xl" onclick="navigation('<?php echo site_url('admin/apply_discount/' . $row['student_id']); ?>');" style='color: #ff8c00;'>
				                              <i class="entypo-tag"></i>
				                              <?php echo get_phrase('apply_discount'); ?>
				                          </a>
				                      </li>

				                      <!-- Delete functionality removed - use student_information page for deletions -->
				                  </ul>
				              </div>

				          </td>
				          <?php else: 
				              // Get guardian information for cashier
				              $parent_id = $this->db->get_where('student', array('student_id' => $row['student_id']))->row()->parent_id;
				              $guardian_name = 'N/A';
				              $guardian_phone = 'N/A';
				              if($parent_id) {
				                  $parent_info = $this->db->get_where('parent', array('parent_id' => $parent_id))->row();
				                  if($parent_info) {
				                      $guardian_name = $parent_info->name;
				                      $guardian_phone = $parent_info->phone;
				                  }
				              }
				          ?>
				          <td class="px-4 py-3"><?php echo $guardian_name; ?></td>
				          <td class="px-4 py-3"><?php echo $guardian_phone; ?></td>
				          <td class="px-4 py-3 text-center">
				              <button type="button" onclick="showCashierStudentView(<?php echo $row['student_id']; ?>)" class="btn btn-sm btn-info rounded-lg" style="font-weight: 600;">
				                  <i class="entypo-user"></i> View Details
				              </button>
				          </td>
				          <?php endif; ?>
				      </tr>
												              <?php endforeach;?>
          </tbody>
      </table>
    </div>
</div>



<!-----  DATA TABLE EXPORT CONFIGURATIONS ---->
<script type="text/javascript">

	function invoice_pay_modal(student_id, date = '', term = '') {
      if(date != '' && term == '') {
          showAjaxModal('<?php echo site_url('modal/popup/modal_take_payment/');?>' + student_id + '/' + date, 'take_payment');
      } else if(date != '' && term != '') {
          showAjaxModal('<?php echo site_url('modal/popup/modal_take_payment/');?>' + student_id + '/' + date + '/' + term, 'take_payment');
      }  else {
          showAjaxModal('<?php echo site_url('modal/popup/modal_take_payment/');?>' + student_id, 'take_payment');
      }
  }
/**
	jQuery(document).ready(function($)
	{


		var datatable = $("#table_export").dataTable({
			"sPaginationType": "bootstrap",
			"sDom": "<'row'<'col-xs-3 col-left'l><'col-xs-9 col-right'<'export-data'T>f>r>t<'row'<'col-xs-3 col-left'i><'col-xs-9 col-right'p>>",
			"oTableTools": {
        "sSwfPath": "<?php echo base_url(); ?>assets/js/datatables/copy_csv_xls_pdf.swf",
				"aButtons": [

					{
						"sExtends": "xls",
						"mColumns": [0, 2, 3, 4]
					},
					{
						"sExtends": "pdf",
						"mColumns": [0, 2, 3, 4]
					},
					{
						"sExtends": "print",
						"fnSetText"	   : "Press 'esc' to return",
						"fnClick": function (nButton, oConfig) {
							datatable.fnSetColumnVis(1, false);
							datatable.fnSetColumnVis(5, false);

							this.fnPrint( true, oConfig );

							window.print();

							$(window).keyup(function(e) {
								  if (e.which == 27) {
									  datatable.fnSetColumnVis(1, true);
									  datatable.fnSetColumnVis(5, true);
								  }
							});
						},

					},
				]
			},

		});

		$(".dataTables_wrapper select").select2({
			minimumResultsForSearch: -1
		});
	});
**/

  function bulk_invoice_view_modal(student_id) {
        showAjaxModal('<?php echo site_url('modal/popup/modal_view_bulk_invoice/'); ?>' + student_id, 'take_payment');
    }

    // Cashier student view modal
    function showCashierStudentView(student_id) {
        showAjaxModal('<?php echo site_url('modal/popup/modal_cashier_student_view/'); ?>' + student_id);
    }

    function showStudentBillReport(student_id) {
        showAjaxModal('<?php echo site_url('modal/popup/modal_student_bill_report/'); ?>' + student_id, 'large');
    }
</script>
