<hr />

<div class="row">


  <!-- Start of Permission for admin -->
    <?php
     echo form_open(site_url('admin/permission_settings/do_update') ,
      array('class' => 'form-horizontal form-groups-bordered','target'=>'_top', 'id' => 'permission_settings_form'));?>
        <div class="col-md-6">

            <div class="panel panel-primary" >

              <div class="panel-heading">
                    <div class="panel-title">
                        <?php echo get_phrase('Permission settings for Administrator');?>
                    </div>
              </div>

              <div class="panel-body">

                  <div>
                    <ul class="list-group">

                      <?php
                        foreach($admin_permissions as $ap):
                      ?>
                      <li class="list-group-item row">
                       
                        <label for="admin_permission_<?=$ap['permission_id'] ?>" id="admin_<?=$ap['permission_id'];?>" class="control-label  <?=$ap['permission_status'] == '0' ? 'text-muted' : '';?>"><?=get_phrase($ap['permission_title']) ?></label>
                   

                        <div class="switch-button pull-right showcase-switch-button">
                          <input type="checkbox"  value="<?=$ap['permission_status'] ?>" <?=$ap['permission_status'] == '0' ? '' : 'checked';?> name="admin_permission_<?=$ap['permission_id'] ?>" id="admin_permission_<?=$ap['permission_id'] ?>" onchange="updatePermission('admin', <?=$ap['permission_id'] ?>)">
                          <label for="admin_permission_<?=$ap['permission_id'] ?>"></label>
                        </div>
                      </li>
                      <?php
                        endforeach;
                      ?>
                    </ul>
                  </div>

              </div>
            </div>
          </div>

  <!-- End of Permission for admin -->

  <!-- Start of Permission for teacher -->
          <div class="col-md-6">

            <div class="panel panel-primary" >

              <div class="panel-heading">
                  <div class="panel-title">
                      <?php echo get_phrase('Permission settings for Teacher');?>
                  </div>
              </div>
              <div class="panel-body">

                <div>
                    <ul class="list-group">

                      <?php
                        foreach($teacher_permissions as $tp):
                      ?>
                      <li class="list-group-item">
                        <label for="teacher_permission_<?=$tp['permission_id'] ?>" id="teacher_<?=$tp['permission_id'];?>" class="control-label  <?=$tp['permission_status'] == '0' ? 'text-muted' : '';?>"><?=$tp['permission_title'] ?></label>  

                        <div class="switch-button pull-right showcase-switch-button">
                          <input type="checkbox"  value="<?=$tp['permission_status'] ?>" <?=$tp['permission_status'] == '0' ? '' : 'checked';?>  name="teacher_permission_<?=$tp['permission_id'] ?>" id="teacher_permission_<?=$tp['permission_id'] ?>" onchange="updatePermission('teacher', <?=$tp['permission_id'] ?>)">

                          <label for="teacher_permission_<?=$tp['permission_id'] ?>"></label>
                        </div>
                      </li>
                      <?php
                        endforeach;
                      ?>
                    </ul>
                  </div>
                  
              </div>
            </div>
          </div>
        <?php echo form_close();?>

  <!-- End of Permission for admin -->
    </div>


<script type="text/javascript">
  
    //updating the permission on a change event
    function updatePermission(user, id) {

      let formUrl = $('#permission_settings_form').attr('action');


        $.ajax({
          url: formUrl + '/' + id,
          type: 'post',
          dataType: 'json',
          cache: false
        })
        .done(function(response) {

          updateFontColor(user, id, response.updatedVal);

          //console.log('Success: ' + response.message);
        })
        .fail(function(err) {
          //console.warn('Failed: ' + err.responseText);
        })
    }
    

    //update font color
    function updateFontColor(user, id, val) {

      if(val == 1) {
        //active
        $('#' + user + '_' + id).removeClass('text-muted');

      } else {

        $('#' + user + '_' + id).addClass('text-muted');
      }
    }
  
</script>

