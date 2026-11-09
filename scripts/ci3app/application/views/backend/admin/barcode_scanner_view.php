<?php 
//echo date('d-m-Y', 1608854400);
    $active_sms_service = $this->db->get_where('settings' , array('type' => 'active_sms_service'))->row()->description;
?>
<hr />


<div class="row">
    <div class="col-md-12">
        <center>
            <div class="form-group col-sm-3 col-md-3">
                <label  class="control-label col-sm-6 col-md-6"><?php echo get_phrase('Category');?></label>
                  <div class="col-sm-6 col-md-6">
                      <select name="term" class="form-control" id="category" onchange="scanned_view_updator($(this).val(), $('#date_sel').val(), 'selected'); title_changer()">
                          <option value="check_in" <?php echo $category == 'check_in' ? 'selected' : ''; ?>><?php echo get_phrase('Chech_in');?></option>
                          <option value="check_out" <?php echo $category == 'check_out' ? 'selected' : ''; ?>><?php echo get_phrase('Chech_out');?></option>
                          <option value="feeding" <?php echo $category == 'feeding' ? 'selected' : ''; ?>><?php echo get_phrase('Feeding');?></option>
                          <option value="classes" <?php echo $category == 'classes' ? 'selected' : ''; ?>><?php echo get_phrase('Classes');?></option>
                          <option value="transport" <?php echo $category == 'transport' ? 'selected' : ''; ?>><?php echo get_phrase('Transport');?></option>
                      </select>
                  </div>
            </div>

            <div class="form-group col-sm-5 col-md-5">
                <h3 id="title_holder"><?=strtoupper($modal_title); ?></h3>
            </div>

            <div class="form-group col-sm-4 col-md-">
                <label  class="control-label col-md-2 col-sm-2"><?php echo get_phrase('date');?></label>
                <div class="col-sm-4 col-md-4">
                  <input type="text" name="date_show" id="date_show" class="form-control datepicker" data-format="yyyy-mm-dd" value="<?php echo date('Y-m-d', $timestamp); ?>" readonly="readonly">
                </div>

                <div class="col-sm-6 col-md-6">
                  <input type="date" name="date_sel" id="date_sel" class="form-control" value="<?php echo date('d-m-Y'); ?>" onchange="scanned_view_updator($('#category').val(), $(this).val(), 'selected')">
                </div>
            </div>
        </center>
    </div>
    <div class="col-md-12" id="details_holder">
                

    </div>
</div>


<script type="text/javascript">

	jQuery(document).ready(function($) {
        //call this as soon as the pages loads for the first time
        let category = '<?php echo $category; ?>';
        let timestamp = '<?php echo $timestamp; ?>';

        scanned_view_updator(category, timestamp);

      //  $('.datatable').DataTable();
	});

    //update date in the readonly field
    $('#date_sel').change(function(event) {
        /* Act on the event */
        let date_val = $(this).val();
        $('#date_show').val(date_val);
    });

     function check_sms_status() {
        var active_sms_service = '<?php echo $active_sms_service; ?>';
        if(active_sms_service == '' || active_sms_service == 'disabled' || active_sms_service == null) {
            showAjaxModal_alert('No active SMS service found. Please go to System Settings and activate SMS service and try again.', 'Warning', false, true);
            toastr.error('No active SMS service found. Please go to System Settings and activate SMS service and try again');
            $('.pt_link').removeAttr('href');
            $('.pt_link').attr({href: '#'});
            return false;

        }
    }

    //this updates the view with the selected date
    function scanned_view_updator(category, timestamp, selected) {
        $.ajax({
            url: '<?php echo site_url('admin/scanned_view_updator/') ?>' + category + '/' + timestamp + '/' + selected,
            type: 'POST',
            dataType: 'html',
            //data: {param1: 'value1'},
        })
        .done(function(response) {
            $('#details_holder').html(response);
        })
        .fail(function(err) {
            showAjaxModal_alert('An error occurred while loading the scanned data.', 'Error', false, true);
        });
        
    }


    //change title
    function title_changer() {
        
        let category = $('#category').val();
        let text = '';

        if(category == 'check_in') {
            text = 'CHECKED-IN';

        } else if(category == 'check_out') {
            text = 'CHECKED-OUT';

        } else if(category == 'feeding') {
            text = 'FEEDING FEE';
            
        } else if(category == 'classes') {
            text = 'CLASSES FEE';
            
        } else if(category == 'transport') {
            text = 'TRANSPORT FARE';
            
        }

        //UPDATE NOW
        $('#title_holder').html('VIEW ALL STUDENTS: ' + text);
    }

</script>
