<?php 
$edit_data		=	$this->db->get_where('exam' , array('exam_id' => $param2) )->result_array();
foreach ( $edit_data as $row):
?>
<div class="row">
	<div class="col-md-12">
		<div class="panel panel-success" data-collapsed="0">
        	<div class="panel-heading">
            	<div class="panel-title" >
            		<i class="entypo-plus-circled"></i>
					<?php echo get_phrase('edit_examination');?>
            	</div>
            </div>
			<div class="panel-body">
				
                <?php echo form_open(site_url('admin/exam/edit/do_update/'.$row['exam_id'] ), array('class' => 'form-horizontal form-groups-bordered validate','target'=>'_top', 'id' => 'edit_exam_form'));?>
            <div class="padded">
                <div class="form-group">
                    <label class="col-sm-3 control-label"><?php echo get_phrase('name');?></label>
                    <div class="col-sm-8">
                        <input type="text" class="form-control" name="name" value="<?php echo $row['name'];?>" data-validate="required" data-message-required="<?php echo get_phrase('value_required');?>"/>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-sm-3 control-label"><?php echo get_phrase('date');?></label>
                    <div class="col-sm-8">
                        <input type="text" class="datepicker form-control" name="date" value="<?php echo $row['date'];?>" data-validate="required" data-message-required="<?php echo get_phrase('value_required');?>"/>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-sm-3 control-label"><?php echo get_phrase('category');?></label>
                    <div class="col-sm-8">
                        <select name="category_id" class="form-control" required="required">
                        	<?php
                        		$exam_cat = $this->db->get('exam_category')->result_array();
                        		foreach($exam_cat as $rowc): ?>
                        			<option value="<?=$rowc['category_id']?>" <?php if($rowc['category_id'] == $row['category_id'])echo 'selected';?>><?php echo $this->crud_model->get_exam_category($rowc['category_id']);?></option>

                        			<?php
                        				endforeach;
                        			?>
                        	?>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <div class="col-sm-offset-3 col-sm-8">
                      <button type="submit" class="btn btn-success"><?php echo get_phrase('edit_exam');?></button>
                    </div>
                </div>
            </form>
            </div>
        </div>
    </div>
</div>

<?php
endforeach;
?>



<script type="text/javascript">
    //ajax add exam
    $('#edit_exam_form').submit(function(event) {
        /* Act on the event */
        event.preventDefault();

        $.ajax({
          url: '<?php echo site_url('admin/exam/edit/do_update/'.$row['exam_id']); ?>',
          type: 'POST',
          dataType: 'html',
          data: new FormData(this),
          cache: false,
          contentType: false,
          processData: false
      })
      .done(function() {
          $('.close').click();        
          showAjaxModal_alert('Exam updated successfully.', 'Success');
          navigation('<?php echo site_url('admin/exam'); ?>');

          setTimeout(() => {
              $('.close').click();
              //window.location.reload();
          }, 3000);
      })
      .fail(function(err) {
          showAjaxModal_alert(err.responseText, 'Error');
      });
            
    });
</script>

