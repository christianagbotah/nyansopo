
<hr />
<div class="row">
    <div class="col-md-12">
        <blockquote class="blockquote-blue">
            <p>
                <strong>Bulk Student ID Cards Printing Notes</strong>
            </p>
            <p>
                After selecting the class you want to print the ID cards for, select a maximum of six(6) students from the checkboxes to generate their ID Cards for printing. If you select more than the specified number, the layout on the sheet will be distorted since this software had been programmed to automatically arrange six(6) ID Cards on an A4 Sheet.
            </p>
        </blockquote>
    </div>
</div>
<?php echo form_open(site_url('admin/bulk_student_id/generate'),  array('id' => 'checkboxes_form'));?>
<div class="row">
<?php 
    
?>  <div class="col-sm-1"></div>
	<div class="form-group">
        <div class="col-sm-3" style="margin-top: 15px;">
        <label class="control-label" style="margin-bottom: 5px;"><?php echo get_phrase('year');?></label>
            <select name="year" class="form-control select2" id="year">
              <?php $running_year = $this->db->get_where('settings' , array('type'=>'running_year'))->row()->description;?>
              <option value="" disabled="true"><?php echo get_phrase('select_running_session');?></option>
              <?php
                          echo populate_academic_year();
                        ?>
            </select>
        </div>
    </div>

    <div class="form-group">
        <div class="col-sm-3">
        <label class="control-label" style="margin-bottom: 5px;"><?php echo get_phrase('class');?></label>
            <select name="class_id" class="form-control select2" data-validate="required" id="class_id"
                data-message-required="<?php echo get_phrase('value_required');?>"
                 required>
                    <option value=""><?php echo get_phrase('select_class');?></option>
                        <?php
                                  getFullClassList();
                              ?>
            </select>
        </div>
    </div>

    <center>
        <div class="col-sm-4">
        <button class="btn btn-info" type="button" style="margin:10px;" onclick="get_students_for_bulk_id()">
            <?php echo get_phrase('load_class_data');?></button>
        </div>
    </center>
    <div class="col-sm-1"></div>


</div>

<div id="bulk_student_holder"></div>

<?php echo form_close();?>

<script type="text/javascript">
    

    function get_students_for_bulk_id() {
        year = $('#year').val();
        class_id = $('#class_id').val();

        if(class_id == '') {
            toastr.error('<?php echo get_phrase("you_must_select_a_class") ?>')
            return false;
        }

        $.ajax({
            url: '<?php echo site_url('admin/get_students_for_bulk_id/') ?>'+ year+ '/'+ class_id,
            success: function(response) {
                $('#bulk_student_holder').html(response);
            }
        });
    }

</script>