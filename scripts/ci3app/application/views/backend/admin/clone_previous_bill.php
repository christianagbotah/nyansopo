
<?php 

  $array = $this->financial_report_model->getPreviousBillsHistory($class_ids_array);
?>

<style type="text/css">
  .select2 {
    visibility: visible !important;
  }

</style>

<?php
  if(count($array) < 1) { ?>
    <div class="flex gap-5 mb-5 justify-center">

      <div class="w-fit p-4 border border-red-500 bg-red-200 text-red-700 rounded-lg font-bold text-xl">No data found for this class!</div>
    </div>
    <?php

  }

  $counter = 0;
  $previous_bill_items_array = array();

  foreach($array as $a):
    $counter++;
    $secondCode = time() + $counter;
    $item_id = rand().'_'.$secondCode;

    $bill_item_row = $this->crud_model->getBillItemRowById($a['bill_item_id']);
    $bill_item_title = $bill_item_row->title;
    $bill_item_description = $bill_item_row->description;

    $bill_item_cat_id = $bill_item_row->bill_category_id;
    $bill_item_cat_name = $this->crud_model->getBillCategoryNameById($bill_item_cat_id);

     $bill_item_amount = $this->financial_report_model->getPreviousBillAmountHistory($a['bill_item_id']);

     $previous_bill_items_array[] = $bill_item_title;
?>

<div class="flex gap-5 mb-5 row" id="<?= $item_id; ?>">

  <div class="w-full">

    <?php
      //$bill_items = $this->db->get('bill_item');

      //if($bill_items->num_rows() > 0) {?>

        <select id="<?=$item_id;?>_title" name="<?=$item_id;?>_title" data-validate="required" data-message-required="<?php echo get_phrase('value_required');?>" class="select2 bg-gray-50 border border-gray-300 text-gray-900 rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full max-w-full h-16 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white text-xl dark:focus:ring-primary-500 dark:focus:border-primary-500" required="true" onchange="getItemDetails($(this).attr('id'), $(this).val())">
            <?php

        //foreach($bill_items->result_array() as $item):?>

      
          <option value="<?=$bill_item_title;?>"><?=$bill_item_title?></option>
            <?php
             // endforeach;
           // }
            ?>
            </select>

    </div>
    <div class="w-full hidden-xs">
    <input type="text" name="<?=$item_id;?>_category" id="<?=$item_id;?>_category" class="bg-gray-200 border border-gray-300 text-gray-500 rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full h-16 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white text-xl dark:focus:ring-primary-500 dark:focus:border-primary-500 cursor-not-allowed" placeholder="Bill item category"  value="<?=$bill_item_cat_name; ?>" readonly="readonly">
    </div>
    <div class="w-full hidden-xs">
    <input type="text" name="<?=$item_id;?>_description" id="<?=$item_id;?>_description" class="bg-gray-200 border border-gray-300 text-gray-500 rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full h-16 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white text-xl dark:focus:ring-primary-500 dark:focus:border-primary-500 cursor-not-allowed" placeholder="Bill item description" value="<?=$bill_item_description; ?>" readonly="readonly">
    </div>

    <div class="w-96 max-w-96">
    <input type="text" id="<?=$item_id;?>_amount"  name="<?=$item_id;?>_amount" data-validate="required" data-message-required="<?php echo get_phrase('value_required');?>" class="bg-gray-50 border border-gray-300 text-gray-900 rounded-lg focus:ring-primary-600 total_amount focus:border-primary-600 block w-full h-16 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white text-xl dark:focus:ring-primary-500 dark:focus:border-primary-500" placeholder="Amount" value="<?=$bill_item_amount; ?>" required="true">
    </div>

    <div class="flex gap-3 w-fit max-w-fit">

      <?php
      if($type == 'mass') {
        ?>
        <a href="javascript:void(0);" <?=count($array) == $counter ? '' : 'disabled'; ?> onclick="add_invoice_item2('<?=$item_id;?>', 'mass')" id="<?=$item_id;?>_add_btn" class="btn btn-info btn-sm"><i class="fa fa-plus"></i></a>

        <a href="javascript:(void);" disabled onclick="return false;/*remove_invoice_item2('<?= $item_id; ?>', 'mass')*/" class="btn btn-danger btn-sm"><i class="fa fa-remove"></i></a>
        <?php
      } else {
        ?>

        <a href="javascript:void(0);" <?=count($array) == $counter ? '' : 'disabled'; ?> onclick="add_invoice_item2('<?=$item_id;?>')" id="<?=$item_id;?>_add_btn" class="btn btn-info btn-sm"><i class="fa fa-plus"></i></a>

        <a href="javascript:(void);" disabled onclick="return false;/*remove_invoice_item2('<?= $item_id; ?>')*/" class="btn btn-danger btn-sm"><i class="fa fa-remove"></i></a>
        <?php
      }
      ?>
    </div>
  </div>
<?php
  endforeach;

  $previous_bill_items_array = json_encode($previous_bill_items_array);
?>

<script type="text/javascript">
  $(function(ev) {

    $('select').select2();
    mass_bill_items_array = <?=$previous_bill_items_array?>;

  })

  
</script>

