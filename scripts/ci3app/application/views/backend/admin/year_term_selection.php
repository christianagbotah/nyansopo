<?php 

  $un_year = $param2;
  $un_term = $param3;
  $class_id = $param4;
  $un_sem = $param5;

?>

    <?php echo form_open(site_url('admin/view_single_bulk_invoice/'.$class_id), array('class' => 'form-horizontal form-group-bordered', 'id' => 'view_single_bulk_invoice_form')); ?>
      <div class="grid grid-cols-3 gap-4" style="margin-top: 5px;">
        <div class="w-full">
          <div class="flex gap-5">
              <label  class="control-label"><?php echo get_phrase('term');?></label>
              <select name="term" class="select2 bg-gray-50 border border-gray-300 text-gray-900 rounded-lg text-xl focus:ring-primary-500 focus:border-primary-500 block w-full h-16 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500" id="term">
                  <option value="" disabled="true"><?php echo get_phrase('term');?></option>
                  <?php for($i = 1; $i <= 3; $i++):?>
                      <option value="<?php echo $i;?>"
                        <?php if($un_term == $i) echo 'selected';?>>
                          <?php echo $i;?>
                      </option>
                  <?php endfor;?>
              </select>
          </div>
        </div>

        <div class="w-full">
            <div class="flex gap-5">
              <label  class="control-label"><?php echo get_phrase('year');?></label>
              <select name="year" class="select2 bg-gray-50 border border-gray-300 text-gray-900 rounded-lg text-xl focus:ring-primary-500 focus:border-primary-500 block w-full h-16 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500" id="year">
              <option value="" disabled="true"><?php echo get_phrase('year');?></option>
              <?php
                      echo populate_academic_year('yes');
                    ?>
              </select>
            </div>
        </div>
        <div class="w-full self-end">
            <input type="submit" formTarget="_blank" name="search" value="<?=$param6 == 'bill' ? 'View Bills' : 'View Invoices';?>" class="btn btn-info btn-sm rounded-lg h-16 text-xl font-bold">
        </div>
      </div>

      <input type="hidden" name="bills" value="<?=$param6;?>">
    <?php echo form_close();?>


<script type="text/javascript">
  $('select').select2();
  /*$('#view_single_bulk_invoice_form').submit(function(e) {
    e.preventDefault();


    $('.close').click();
   
    
    showAjaxModal_alert('<center><div style="font-size: 16px; font-weight: bolder; margin-top: 0px; ">Generating Invoices...<br><i class="fa fa-3x fa-spinner fa-pulse"></i><br/><small style="color: #fff">This might take sometime</small></div></center>', 'Loading');
    $('#modal_alert .modal-dialog').css('marginTop', '20vh');

    $.ajax({
      url: '<?php //echo site_url('admin/view_single_bulk_invoice/'.$class_id); ?>',
      type: 'POST',
      dataType: 'html',
      cache: false,
      contentType: false,
      processData: false,
      data: new FormData(this),
    })
    .done(function(response) {
      $('.close').click();

      var w = window.open('about:blank');
      w.document.open();
      w.document.write(response);
      w.document.close();
      //showAjaxModal(response, 'take_payment'); 
    });
    
  });*/
</script>