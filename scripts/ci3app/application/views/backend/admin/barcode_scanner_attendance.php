<style type="text/css">
  #input_area {
    border-right: 10px solid green;
    border-left: 10px solid green;
  }

  input[type='radio'] {
        height: 20px;
        width: 20px;
        vertical-align: middle;
    }

  #passport {
    border: 1px solid #000;
    width: 202px;
    height: 202px;
  }
</style>

<hr />
<div class="row">
	<div class="col-md-12">
    <center>
        <!--visible to only super admin-->
    <?php
      $name = $this->db->get_where($this->session->userdata('login_type'), array($this->session->userdata('login_type').'_id' => $this->session->userdata('login_user_id')))->row()->name;
      $admin_level = $this->db->get_where('admin', array('name' => $name))->row()->level;
    ?>
    
    <div class="row">
    <!--<button class="btn btn-info btn-lg" id="barcodes_gen">Generate Mass Barcodes</button>-->
    </div><hr>
    <div class="panel panel-info">
      <div class="panel-heading">
        <div class="row">
          <div class="col-sm-12 col-md-12"><h2>BARCODE SCANNER</h2> 
            <div class="switch-button  showcase-switch-button">
              <input id="autoscan"  type="checkbox"  value="1" checked name="autoscan" onchange="value_change(this.value)">
              <label for="autoscan" ></label>   <strong style="font-size: 22px;" id="yes_no">AUTO</strong>                               
            </div> 
          </div>
        </div>
      </div>

      <div class="panel-body">
            <!--ONLY SUPER ADMIN IS ALLOWED TO VIEW THIS-->
          <?php if ($account_type == 'admin' && $admin_level == 1):?>
        <div class="col-sm-12 col-md-12">
                <?php echo form_open(site_url('admin/barcode_scanner/attendance'), array('class' => 'form-horizontal form-group-bordered', 'id' => 'barcode_form')); ?>
                  <div class="form-group">
                    <div class="col-sm-4 col-md-4" style="text-align: left">
                      <table class="table table-secondary table-striped table-hover" style="border: 0px">
                        <thead>
                        </thead>
                        <tbody>
                          <tr>
                            <td><input type="radio" class="radio" name="check_in_out" id="check_in" value="check_in" checked="checked"></td>
                            <td><label for="check_in" class="form-control-label">STUDENT CHECKING IN</label></td>
                            <td><button type="button" class="btn btn-info btn-sm" id="view_check_in" onclick="view_all('check_in')">View</button></td>
                          </tr>

                          <tr>
                            <td><input type="radio" class="radio" name="check_in_out" id="check_out" value="check_out"></td>
                            <td><label for="check_out" class="form-control-label">STUDENT CHECKING OUT </label></td>
                            <td><button type="button" class="btn btn-info btn-sm" id="view_check_out" onclick="view_all('check_out')">View</button></td>
                          </tr>

                          <tr>
                            <td><input type="radio" class="radio" name="check_in_out" id="feeding" value="feeding"></td>
                            <td><label for="feeding" class="form-control-label">STUDENT GOING FOR LUNCH </label></td>
                            <td><button type="button" class="btn btn-info btn-sm" id="view_feeding" onclick="view_all('feeding')">View</button></td>
                          </tr>

                          <tr>
                            <td><input type="radio" class="radio" name="check_in_out" id="classes" value="classes"></td>
                            <td><label for="classes" class="form-control-label">STUDENT GOING FOR CLASSES </label></td>
                            <td><button type="button" class="btn btn-info btn-sm" id="view_classes" onclick="view_all('classes')">View</button></td>
                          </tr>

                          <tr>
                            <td><input type="radio" class="radio" name="check_in_out" id="transport" value="transport"></td>
                            <td><label for="transport" class="form-control-label">STUDENT'S TRANSPORT </label></td>
                            <td><button type="button" class="btn btn-info btn-sm" id="view_transport" onclick="view_all('transport')">View</button></td>
                          </tr>

                        </tbody>
                      </table>

                    </div>
                    <div class="col-sm-4 col-md-4" id="input_area">
                      <input type="text" name="barcode_scanner" class="form-control" id="barcode_scanner" autofocus="true" onmouseenter="this.focus()">

                      <br/>
                      <div id="passport">
                        <img src="<?php echo base_url().'uploads/user.jpg'; ?>" width="200" height="200" />
                        <strong><h4 id="student_name"></h4></strong><hr><br/>
                      </div>
                    </div>

                  <div class="col-sm-4 col-md-4" style="display: none" id="st_approve">
                      <input type="checkbox" class="form-control checkbox" name="approve_student" id="approve_student" value="approve"> <label class="form-control-label">APPROVE THIS STUDENT</label>
                  </div>

                  <div class="col-sm-12 col-md-12" id="submit_holder" style="display: none; margin-top: 30px">
                      <input type="submit" name="submit" value="SUBMIT" class="btn btn-success btn-lg">
                  </div>
                </div>
                
                <?php echo form_close();?>
        </div>

      <?php endif; ?>
      </div>
    </div>
  </center>
  </div>
</div>



    <script>

    //view all
    function view_all(category) {
      //page scrolls to top
      $('html, body').animate({
          scrollTop: ($('#top').offset().top )
      }, 1000); 

      //processing
      showAjaxModal('<?php echo site_url('admin/barcode_scanner_view/') ?>' + category, 'take_payment');
    }

  $('#barcodes_gen').click(function(event) {
    /* Act on the event */
    //using ajax
    $.ajax({
      url: '<?php echo site_url('admin/mass_barcode_generator') ?>',
      type: 'POST',
      dataType: 'html',
    })
    .done(function() {
      alert('Barcodes Successfully Generated. Please check the folder path: "uploads/barcodes/students".');
    })
    .fail(function() {
      alert("Error generating the Barcodes. Please try again later");
    })
    
  });


  function value_change() {
      $('#barcode_scanner').focus();
       let autoscan = $('#autoscan').filter(':checked').length;
       if(autoscan > 0) {
            $('#autoscan').val(1);
            $('#yes_no').text('AUTO');

            $('#barcode_scanner').removeAttr('placeholder');
            $('#submit_holder').slideUp('slow');
            $('#st_approve').slideUp('slow');
            $('#yes_no').css('color', '#000000');

       } else {
            $('#autoscan').val(0);
            $('#yes_no').text('MANNUAL');

            $('#barcode_scanner').attr('placeholder', 'Enter Student\'s ID No. Here');
            $('#submit_holder').slideDown('slow');
            $('#st_approve').slideDown('slow');
            $('#yes_no').css('color', '#b3afaf');
       }
    }

    //HANDLING THE SCANNED CODE DATA HERE
    $('#barcode_form').submit(function(event) {
      /* Act on the event */
      event.preventDefault();

      let autoscan = $('#autoscan').filter(':checked').length;
      if(autoscan > 0) {
        get_student_image();
      }

      //Scroll to the top
      $('html, body').animate({
          scrollTop: ($('#top').offset().top )
      }, 1000);  


      let barcode_data = $('#barcode_scanner').val();//main data collected (Student's ID Number)
      let check_in_out = $('input[name="check_in_out"]').filter(':checked').val(); //either checking in or out
      let approve_student = $('input[name="approve_student"]').filter(':checked').val(); //either checking in or out
      let currency = '<?php echo $this->db->get_where('settings', array('type' => 'currency'))->row()->description; ?>';

      if(approve_student == undefined) {
        approve_student = 0;
      }


      $.ajax({
        url: '<?php echo site_url('admin/barcode_scanner/attendance/') ?>' + barcode_data + '/' + check_in_out + '/' + approve_student,
        type: 'POST',
        dataType: 'json',
      })
      .done(function(response) {

        if(response.invalid_barcode == 'invalid') {
          //invalid barcode scanned
          showAjaxModal_alert('Invalid Barcode. The system cannot recognize this barcode at the moment. Please try again.', 'Error');

        } else {

          //valid barcode scanned
          //FOR CHECKING IN AND OUT
          if(response.already_checked_in == 1) { //access denied
            showAjaxModal_alert('SORRY, ' + response.student_name + ' HAS ALREADY CLOCKED-IN TODAY.', 'Error');

          } else if(response.already_checked_out == 1) { //access denied
            showAjaxModal_alert('SORRY, ' + response.student_name + ' HAS ALREADY CLOCKED-OUT TODAY.', 'Error');

          } else if(response.check_out_error == 1) { //access denied
            showAjaxModal_alert('SORRY, STUDENT MUST BE CHECKED-IN FIRST BEFORE CHECKING OUT.', 'Error');

          } else {

            if(response.school_fees_access == 'granted') { //access granted
              showAjaxModal_alert('WELCOME BACK TO SCHOOL, ' + response.student_name, 'Success');

              //REFRESH PAGE IF IT WAS MANNUALLY SUBMITTED
              if(autoscan < 1) {
                //mannually submited
                setTimeout(() => {
                  window.location.reload();
                }, 3000);
              }

            } else if(response.school_fees_access == 'denied') { //access denied
              showAjaxModal_alert('Access Denied To ' + response.student_name +'. Outstanding balance: ' + currency + ' ' + number_format(response.amount, 2, '.', ','), 'Error');

            } else if(response.student_checked_out == 'checked_out') {
              showAjaxModal_alert('GOODBYE, ' + response.student_name, 'Success');

              //REFRESH PAGE IF IT WAS MANNUALLY SUBMITTED
              if(autoscan < 1) {
                //mannually submited
                setTimeout(() => {
                  window.location.reload();
                }, 3000);
              }
            }
          } //END OF CHECKING IN AND OUT

          //FOR FEEDING, CLASSES & TRANSPORT 
          if(response.att_status == 'absent') {//student is absent
            showAjaxModal_alert('Sorry, it seems ' + response.student_name + ' is not in school today. Please confirm with the ' + response.class_name + ' teacher if class attendance was taken today.', 'Error');
          } else {
            //student is present
            if(response.fees_status == 'granted') { //access granted
              showAjaxModal_alert(response.fees_type + ' access granted to ' + response.student_name, 'Success');

              //REFRESH PAGE IF IT WAS MANNUALLY SUBMITTED
              
              if(autoscan < 1) {
                //mannually submited
                setTimeout(() => {
                  window.location.reload();
                }, 3000);
              }

            } else if(response.fees_status == 'denied') { //access denied
              showAjaxModal_alert(response.fees_type + ' access denied to ' + response.student_name +'. Outstanding balance: ' + currency + ' ' + number_format(response.amount, 2, '.', ','), 'Error');

            } else if(response.duplicate == 1) { //student
              showAjaxModal_alert('This operation has already been done for ' + response.student_name + '.', 'Error');

            }
          }
          
        }
        
      })
      .fail(function(err) {
       alert(err.responseText);// showAjaxModal_alert(err.responseText, 'Error');
       // showAjaxModal_alert('Unknown error occured, please try again later.', 'Error');
      });
      
    });

    //show student image here
    $('#barcode_scanner').keyup(function(event) {
      /* Act on the event */
      let autoscan = $('#autoscan').filter(':checked').length;
      if(autoscan < 1) {
        get_student_image();
      }
      
    });

    function get_student_image() {

      
      let student_code = $('#barcode_scanner').val();

      $.ajax({
        url: '<?php echo site_url('admin/get_student_image/') ?>' + student_code,
        type: 'POST',
        dataType: 'json',
      })
      .done(function(response) {

        $('#passport img').attr('src', response.image);
        $('#student_name').html(response.student_name);
      });
          

    }
  

    $(document).on('hidden.bs.modal', function () {
      $('#barcode_scanner').focus();
    });
  </script>
