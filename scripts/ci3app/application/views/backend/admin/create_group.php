  <?php echo form_open(site_url('admin/group_message/create_group'), array('id' => 'group_message_form')); ?>
  <div class="form-group">
    <label for="field-1" class="col-sm-3 control-label"><?php echo get_phrase('group_name');?></label>

    <div class="col-sm-8">
      <input type="text" class="form-control" name="group_name" data-validate="required" data-message-required="<?php echo get_phrase('value_required');?>"  autofocus
      data-validate="required" data-message-required="<?php echo get_phrase('value_required');?>" required>
    </div>
  </div><hr>

  <br><br><br>
  <div class="form-group">
    <label for="field-2" class="col-sm-3 control-label"><?php echo get_phrase('create_new_list');?></label>

      <label class="col-sm-3 radio-inline "><input type="radio" onclick="update_yes(this)" class="form-control" name="yes_no" id="yes" value="yes"><h3>Yes</h3></label>

      <label class="col-sm-3 radio-inline"><input type="radio" onclick="update_no(this)" class="form-control" name="yes_no" id="no" value="no" checked><h3>No.</h3></label>

  </div>

  <div class="col-md-12" style="margin-top: 10px; display: none" id="second_list">
    <br>
      <div class="form-group row">
        <label for="field-1" class="col-sm-4 control-label"><?php echo get_phrase('Member\'s Name:');?></label>

        <div class="col-sm-6">
          <input type="text" class="form-control" name="member_name" data-validate="required" data-message-required="<?php echo get_phrase('value_required');?>" 
          data-validate="required">
        </div>
      </div>

      <div class="form-group row">
        <label for="field-1" class="col-sm-4 control-label"><?php echo get_phrase('Member\'s Contact:');?></label>

        <div class="col-sm-6">
          <input type="text" class="form-control" name="member_contact" data-validate="required" data-message-required="<?php echo get_phrase('value_required');?>" 
          data-validate="required" >
        </div>
      </div>
    </div>



<?php
  $running_year = get_settings('running_year');
  $running_term = get_settings('running_term');
  $running_sem = get_settings('running_sem');

  $user_array = ['student', 'teacher', 'parent'];
  for ($i=0; $i < sizeof($user_array); $i++):

    if($user_array[$i] == 'parent') {
      $this->db->select('student_id');
      $this->db->from('enroll');
      $this->db->where('mute', '0');
      $this->db->where('year', $running_year);
      $this->db->where('term', $running_term);
      $this->db->or_where('sem', $running_sem);
      $st_ids =  $this->db->get()->result_array();

      $st_ids_array = array();
      $ij = 0;
      foreach($st_ids as $row) {
          $st_ids_array[$ij] = $row['student_id'];
          $ij++;
      }

      $this->db->select('parent_id');
      $this->db->from('student');
      $this->db->where_in('student_id', $st_ids_array);
      $pt_ids = $this->db->get()->result_array();

      $pt_ids_array = array();
      $j = 0;
      foreach($pt_ids as $row2) {
          $pt_ids_array[$j] = $row2['parent_id'];
          $j++;
      }
      $this->db->where_in('parent_id', $pt_ids_array);
      $user_list = $this->db->get($user_array[$i])->result_array();
    } else {
      $user_list = $this->db->get($user_array[$i])->result_array();
    }
    
    ?>
    <br/>
    
    <div class="col-md-12" style="margin-top: 10px;" id="<?=$user_array[$i]?>_holder">
    <table  class="table table-bordered table-striped" id="<?=$user_array[$i]?>">
      <span class="col-md-6" style="font-size: 13px; color: #616161; text-align: left; padding: 0; margin: 0;"><u><?php echo ucfirst($user_array[$i]) .' List'; ?></u></span>
      <span class="col-md-4 pull-right" style="text-align: right; color: #616161;">
        <input type="checkbox" id = "<?php echo $user_array[$i]; ?>" onchange="checkAllBoxes(this)">&nbsp;<?php echo get_phrase('check_all'); ?>
      </span>
      <thead>
        <tr>
          <th><?php echo get_phrase('select'); ?></th>
          <th><?php echo get_phrase('phone'); ?></th>
          <th><?php echo get_phrase('name'); ?></th>
        </tr>
      </thead>
      <?php foreach ($user_list as $user):?>
        <tr>
          <td width = "20%"><input type="checkbox" class="<?php echo $user_array[$i]; ?>" name="user[]" value="<?php echo $user_array[$i].'_'.$user[$user_array[$i].'_id']; ?>"></td>
          <td width = "25%"><?php echo $user['phone'] ?></td>
          <td width = "55%"><?php echo $user['name'] ?></td>
        </tr>
      <?php endforeach ?>
    </table>
  </div>
<?php endfor; ?>
<div class="col-md-4 col-md-offset-4" style="text-align: center;">
  <button type="submit" name="submit" class="btn btn-success btn-md"><?php echo get_phrase('done'); ?></button>
</div>
<?php echo form_close();?>
<script type="text/javascript">

  


  function checkAllBoxes(check){
    var checkboxes = document.getElementsByTagName('input');

    if (check.checked) {
          $('.'+check.id).prop("checked", true);

     } else {
        $('.'+check.id).prop("checked", false);
     }
  }


  function update_yes(val) {
    /* Act on the event */
    if(val.checked) {
      $('#student_holder').slideUp('slow');
      $('#teacher_holder').slideUp('slow');
      $('#parent_holder').slideUp('slow');
      $('#second_list').slideDown('slow');
    }
    
  };

  function update_no(val) {
    /* Act on the event */
    if(val.checked) {

      $('#second_list').slideUp('slow');
      $('#student_holder').slideDown('slow');
      $('#teacher_holder').slideDown('slow');
      $('#parent_holder').slideDown('slow');
    }
  };
</script>
