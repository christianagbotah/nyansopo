<link rel="stylesheet" type="text/css" href="<?php echo base_url('assets/apexchart/apexcharts.css');?>"/>


<style type="text/css">
	#assessment_graph .bg-primary {
		background-color:  #737373b3 !important;
	}

	#assessment_graph .bg-primary strong {
		padding:  0 5px !important;
	}

	.mt-3 {
		margin-top: 6rem !important;
	}
</style>

 <script src="<?php echo base_url('assets/apexchart/apexcharts.min.js');?>" type="text/javascript"></script>

<div class="row">
    <div class="col-sm-12 col-xs-12 col-md-12 col-lg-12">
        <?php echo form_open(site_url('admin/assessment_graph/generate') , array('class' => 'form-horizontal form-groups-bordered validate','target'=>'_top', 'id' => 'assessment_graph'));?>

            <div class="form-group row">

                <div class="col-sm-2 col-md-2 col-lg-2 col-xs-6">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <div class="input-group-text bg-primary text-light"><strong>Data Source:</strong> </div>
                        </div>
                        <select class="form-control" name="search_by_data_source" id="search_by_data_source">
                            <option value="1" selected>Portfolio Assessment</option>
                            <!-- <option value="2">Terminal Examination</option> -->
                        </select>
                    </div>
                   
                </div>
                <!-- Show dates if portfolio assessment is selected ==Toggle== -->
                <!-- Start date -->
                <div class="col-sm-2 col-md-2 col-xs-6 date_filter">
                    <div class="input-group date">
                        <div class="input-group-prepend">
                            <div class="input-group-text bg-primary text-light"><strong>Start Week:</strong></div>
                        </div>
                        <input type="week" name="start_week" id="start_week" class="form-control" value="<?php echo date('Y') .'-W'. (date('W') - 1); ?>">
                    </div>
                </div>

                <div class="row mt-3 visible-xs dd"></div>
                 <!-- End date -->
                <div class="col-sm-2 col-md-2 col-xs-6 date_filter">
                    <div class="input-group date">
                        <div class="input-group-prepend">
                            <div class="input-group-text bg-primary text-light"><strong>End Week:</strong></div>
                        </div>
                        <input type="week" name="end_week" id="end_week" class="form-control" max="<?php echo date('Y') .'-W'. date('W'); ?>" value="<?php echo date('Y') .'-W'. date('W'); ?>">
                    </div>
                    <div id="desc"></div>
                </div>


                <!-- end of dates selections -->

                <!-- Show term and year if terminal examination is selected ==Toggle== -->
                <!-- terms -->
                <div class="col-sm-2 col-md-2 col-xs-6 term_filter" style="display: none">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <div class="input-group-text bg-primary text-light"><strong>Term:</strong></div>
                        </div> 
                        <select class="form-control" name="search_by_term" id="search_by_term">
                            <option value="1" selected>1</option>
                            <option value="2">2</option>
                            <option value="3">3</option>
                    
                        </select>  
                    </div>
                </div>

                <div class="row mt-3 visible-xs tt"></div>
                 <!-- End date -->
                <div class="col-sm-2 col-md-2 col-xs-6 term_filter" style="display: none">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <div class="input-group-text bg-primary text-light"><strong>Year:</strong></div>
                        </div>
                        <select class="form-control" name="search_by_year" id="search_by_year">
                            <?php
                                echo populate_academic_year();
                            ?>
                    
                        </select>
                        
                    </div>
                </div>

                <!-- End of term and year selection -->

                <?php
                if ($account_type == 'teacher') {
                ?>
                            <div class="col-sm-2 col-md-2 col-lg-2 col-xs-6">
                              <!-- Select a class -->
                              <div class="input-group">
                                <div class="input-group-prepend">
                                    <div class="input-group-text bg-primary text-light"><strong>Filter By Class:</strong> </div>
                                </div>
                                <select class="form-control" name="search_by_class" id="search_by_class" required="required">
                                    <option value="" selected>Select a class</option>

                            
                            <?php
                foreach ($class_ids_creche as $subj):
                    $classes = $this->db->get_where('class', array('class_id' => $subj['class_id']))->result_array();
                    foreach ($classes as $row):

                        //add section A or B if the class has more than one section
                        $section_name = $this->db->get_where('section', array('class_id' => $row['class_id']))->row()->name;
                        $class_has_more_sections = $this->db->get_where('class', array('name' => $row['name'], 'name_numeric' => $row['name_numeric']))->num_rows();
                        $sec_name = '';
                        if ($class_has_more_sections > 1) {
                            $sec_name = $section_name;
                        }
                        ?>
                                                <option value="<?php echo $row['class_id']; ?>"><?php echo $row['name'] . ' ' . $row['name_numeric'] . $sec_name; ?></option>
                                                <?php endforeach;endforeach; //FOR CRECHE ?>

                                        <?php
                foreach ($class_ids_c as $cs):
                    $classes_c = $this->db->get_where('class', array('class_id' => $cs['class_id']))->result_array();
                    foreach ($classes_ as $rowc):

                        //add section A or B if the class has more than one section
                        $section_name = $this->db->get_where('section', array('class_id' => $rowc['class_id']))->row()->name;
                        $class_has_more_sections = $this->db->get_where('class', array('name' => $rowc['name'], 'name_numeric' => $rowc['name_numeric']))->num_rows();
                        $sec_name = '';
                        if ($class_has_more_sections > 1) {
                            $sec_name = $section_name;
                        }
                        ?>
                                                <option value="<?php echo $rowc['class_id']; ?>"><?php echo $rowc['name'] . ' ' . $rowc['name_numeric'] . $sec_name; ?></option>
                                                <?php endforeach;endforeach;?>

                                        <?php
                foreach ($class_ids as $subj):
                    $classes = $this->db->get_where('class', array('class_id' => $subj['class_id']))->result_array();
                    foreach ($classes as $row):

                        //add section A or B if the class has more than one section
                        $section_name = $this->db->get_where('section', array('class_id' => $row['class_id']))->row()->name;
                        $class_has_more_sections = $this->db->get_where('class', array('name' => $row['name'], 'name_numeric' => $row['name_numeric']))->num_rows();
                        $sec_name = '';
                        if ($class_has_more_sections > 1) {
                            $sec_name = $section_name;
                        }
                        ?>
                                                <option value="<?php echo $row['class_id']; ?>"><?php echo $row['name'] . ' ' . $row['name_numeric'] . $sec_name; ?></option>
                                                <?php endforeach;endforeach;?>
                                    </select>
                                </div>
                            </div>
                        <?php
                } else {
                ?>

                <div class="col-sm-2 col-md-2 col-lg-2 col-xs-6">
                  <!-- Select a class -->
                  <div class="input-group">
                    <div class="input-group-prepend">
                        <div class="input-group-text bg-primary text-light"><strong>Filter By Class:</strong> </div>
                    </div>
                    <select class="form-control" name="search_by_class" id="search_by_class" required="required">
                        <option value="" selected>Select a class</option>
                        <?php
                            getFullClassList();
                        ?>
                
                    </select>
                	</div> 
                </div>

                <?php } ?>

                <div class="row mt-3 visible-xs"></div>

                <div class="col-sm-3 col-md-3 col-lg-3 col-xs-8">
                <!-- For all students in the selected class -->
                  <div class="input-group">
                      <div class="input-group-prepend">
                          <div class="input-group-text bg-primary text-light"><strong>Students In Selected Class:</strong> </div>
                      </div>
                      <select class="form-control boxit" name="search_by_student" id="search_by_student" required="required">
                          
                          <option value="">Please select class first</option>
                      </select>
                  </div> 
                </div>

                

            </div>


        <div class="form-group row lower-row">

            <div class="col-sm-2 col-md-2 col-lg-2 col-xs-6" id="subject_holder">
                <div class="input-group">
                    <div class="input-group-prepend">
                        <div class="input-group-text bg-primary text-light"><strong>Subject:</strong> </div>
                    </div>
                    <select name="search_by_subject" id="search_by_subject" class="form-control" required="required">
                        <option value=""><?php echo get_phrase('select_class_first'); ?></option>
                    </select>
                </div>
            </div>

            <div class="col-sm-2 col-md-2 col-lg-2 col-xs-6">
                <div class="input-group">
                    <div class="input-group-prepend">
                        <div class="input-group-text bg-primary text-light"><strong>Graph Type:</strong> </div>
                    </div>
                    <select class="form-control" name="search_by_type" id="search_by_type">
                        <option value="line">Line Chart</option>
                        <option value="bar">Bar Chart</option>
                        <option value="area">Area Chart</option>
                    </select>
                </div>    
            </div>

            <div class="col-sm-2 col-md-2 col-lg-2 col-xs-6">
                <div class="input-group">
                    <div class="input-group-prepend">
                        <div class="input-group-text bg-primary text-light"><strong>Data View:</strong> </div>
                    </div>
                    <select name="search_by_view" id="search_by_view" class="form-control">
                        <option value="1"><?php echo get_phrase('Graph View'); ?></option>
                        <!-- <option value="2"><?php //echo get_phrase('Table View'); ?></option> -->
                    </select>
                </div>
            </div>

            <div class="col-sm-2 col-md-2 col-lg-2 col-xs-6" style="margin-top: 10px; float: right">
                <input type="submit" value="Generate" class="btn btn-default" />
            </div>
        </div>

        </form>
    </div>   

    <hr>

</div>
<hr>
<hr>


<!-- 
//////////////////////////////////////////////////////////////
/////////////////////MAIN BODY HERE /////////////////////////
//////////////////////////////////////////////////////////////
 -->
<div class="row">
    <div class="col-lg-12">
        <div id="print">
            <div id="graph_view"></div>
            <div id="table_view"></div>
        </div>
     </div>
</div>


<script type="text/javascript">
	$(function(e) {
        let generateClickCounter = 0;
        toggleDates();

		/*Toggle Stucents selection when Class changes*/
      $('#search_by_class').change(function(ev) {
        get_class_subject($(this).val()); //get subjects for this class

        if(generateClickCounter > 0) { //meaning we had at least one successful submission already
            callSubmission(); //form submission
        }

        let student = $('#search_by_student').val();
        let start_week = $('#start_week').val();

        /*show or hide the toggle row*/
        $.ajax({
            url: '<?php echo site_url('admin/getAllPortfolioAssessmentStudentsData/') ?>' + $(this).val() + '/' + start_week,
            type: 'post',
            dataType: 'html'
        })
        .done(function(data) {
            $('#search_by_student').html(data);

        })
        .fail(function(err) {

            $(this).html('Unable to load data!');
        })  
    
        
      });

    function get_class_subject(class_id) {
        if (class_id !== '') {
            let start_week = $('#start_week').val();
        $.ajax({
            url: '<?php echo site_url('admin/marks_get_subject/'); ?>' + class_id + '/graph/' + start_week,
            success: function(response)
            {
                jQuery('#subject_holder').html(response);
            }
        });
       // $('#submit').removeAttr('disabled');
      }
      else{
       // $('#submit').attr('disabled', 'disabled');
      }
    }

      //toggle date_filter and term_filter
      $('#search_by_data_source').change(function(e) {
        toggleDates();
      })

      function toggleDates() {
        let data = $('#search_by_data_source').val();
        if(data == 1) {
            //portfolio assessment selected
            $('.date_filter').slideDown('slow'); //disappear
            $('.term_filter').slideUp('slow'); //appear
            $('.tt').css({
                display: 'none',
                position: 'absolute'
            });
            $('.dd').removeAttr('style');
        } else {
            //terminal examination  selected
            $('.term_filter').slideDown('slow'); //disappear
            $('.date_filter').slideUp('slow'); //appear
            $('.dd').css({
                display: 'none',
                position: 'absolute'
            });
            $('.tt').removeAttr('style');
        }
      }

      //change effect: no need to click on the generate button to refresh the data. Once a field is changed, update the view immediately
      $('#search_by_student, #search_by_type, #search_by_view, #search_by_term, #search_by_year, #start_week, #end_week').change(function(e) {
        if(generateClickCounter > 0) { //meaning we had at least one successful submission already
            callSubmission(); //form submission
        }
      });

      //call submission
      function callSubmission() {
        $('#assessment_graph').submit();
      }

      //form submitted
      $('#assessment_graph').submit(function(ev) {
        ev.preventDefault();

        
        //validate few things here
        //if all students is selected, only a single subject can be analyzed at a time
        //and the vice versa
        let student = $('#search_by_student').val();
        let subject = $('#search_by_subject').val();

        /*if(student == 0 && subject == 0) {
            showAjaxModal_alert('<div title="If you select All Students, make sure you select a single subject. Likewise if All subjects is selected, make sure you select a single student!">You can only analyze a single subject for all students or a single student for all subjects.</div>', 'Error');
            return;
        }*/

        showAjaxModal_alert('<center><div style="font-size: 20px; font-weight: bolder; margin-top: 0px; ">Please wait... Data is being processed<br><i class="fa fa-3x fa-spinner fa-pulse"></i></div></center>', 'Loading');

        let url = $(this).attr('action');
        $.ajax({
            url: url,
            type: 'post',
            dataType: 'json',
            data: new FormData(this),
            cache: false,
            contentType: false,
            processData: false
        })
        .done(function(response) {

            if(response.message == 'success') {
                generateClickCounter++;
                //all is well
                $('.modal-content').removeAttr('style');
                $('.modal-content').css({
                    marginTop: "20vh",
                    transform: "matrix(1, 0, 0, 1, 0, 0)"
                });
                
                //showAjaxModal_alert(response.data, 'Success');
                $('#graph_view').html(response.data);
                $('.close').click();
            } else {
                //error
                $(function() {
                    $.each(response, function(index, val) {
                        $('#error_message').append('<li>' + val + '</li>');
                    });
                });

                showAjaxModal_alert('<ul id="error_message" style="text-align: left;"></ul>', 'Error');
            }
            

        })
        .fail(function(err) {

            showAjaxModal_alert('Error occured: ' + err.responseText, 'Error');
        })
        
      });

	});
</script>