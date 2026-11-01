<?php 
    $raw_score =  $this->db->get_where('settings' , array('type' => 'raw_score'))->row()->description;
?>
<?php 
$checked = 'checked';
$edit_data		=	$this->db->get_where('subject' , array('subject_id' => $param2) )->result_array();
foreach ( $edit_data as $row):
?>

<div class="row">
	<div class="col-md-12">
		<div class="panel panel-success" data-collapsed="0">
        	<div class="panel-heading">
            	<div class="panel-title" >
            		<i class="entypo-plus-circled"></i>
					<?php echo get_phrase('edit_subject');?>
            	</div>
            </div>
			<div class="panel-body">
                <?php echo form_open(site_url('admin/subject/do_update/'.$row['subject_id']) , array('class' => 'form-horizontal form-groups-bordered validate', 'id' => 'subject_edit_form'));?>
                <div class="form-group">
                    <label class="col-sm-4 control-label"><?php echo get_phrase('name');?></label>
                    <div class="col-sm-5 controls">
                        <input type="text" class="form-control" name="name" value="<?php echo $row['name'];?>" required/>
                    </div>
                </div>
                <div class="form-group" id="status_edit" <?php if($raw_score != 'Yes' || $param3 != 'FORM') { echo 'style="display: none;"';} ?>>
                    <label class="col-sm-4 control-label"><?php echo get_phrase('it_is_a_core_subject');?></label>
                    <div class="col-sm-2">
                        <input type="checkbox" class="form-control" name="status" <?php if($row['status'] == 1) echo $checked; ?> <?php if($checked) {
                                echo 'value="1"';
                            }else{
                                echo 'value="0"';
                            }; ?> >
                    </div>
                </div>
                <input type="hidden" name="class_id" value="<?php echo $row['class_id']; ?>">
                <div class="form-group">
                    <label class="col-sm-4 control-label"><?php echo get_phrase('teacher');?></label>
                    <div class="col-sm-5 controls">
                        <select name="teacher_id" class="form-control">
                            <?php 
                            $teachers = $this->db->get('teacher')->result_array();
                            foreach($teachers as $row2):
                            ?>
                                <option value="<?php echo $row2['teacher_id'];?>"
                                    <?php if($row['teacher_id'] == $row2['teacher_id'])echo 'selected';?>>
                                        <?php echo $row2['name'];?>
                                            </option>
                            <?php
                            endforeach;
                            ?>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <div class="col-sm-6 col-sm-offset-3 ">
                        <button type="submit" class="btn btn-success"><?php echo get_phrase('edit_subject');?></button>
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




