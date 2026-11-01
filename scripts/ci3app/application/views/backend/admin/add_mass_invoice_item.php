
<?php 

  $item_id = rand().'_'.time();
?>

<style type="text/css">
  .select2 {
    visibility: visible !important;
  }

</style>


<div class="flex gap-5 mb-5 row" id="<?= $item_id; ?>">

  <div class="w-full">

    <?php
      // Start with bill_item table selection
      $this->db->select('*');
      $this->db->from('bill_item');
      
      // Exclude already selected items
      if(!empty($bill_item) && is_array($bill_item)) {
          $this->db->where_not_in('bill_item.title', $bill_item);
      }
      
      // Filter by class categories if class_ids provided
      if(!empty($class_ids) && is_array($class_ids)) {
          // Get unique categories for the selected classes
          $this->db->distinct();
          $this->db->select('category');
          $this->db->from('class');
          $this->db->where_in('class_id', $class_ids);
          $categories_query = $this->db->get();
          
          $categories = array();
          foreach($categories_query->result() as $row) {
              $categories[] = $row->category;
          }
          
          // Rebuild query after categories query
          $this->db->select('*');
          $this->db->from('bill_item');
          
          // Re-apply exclusion filter
          if(!empty($bill_item) && is_array($bill_item)) {
              $this->db->where_not_in('bill_item.title', $bill_item);
          }
          
          if(!empty($categories)) {
              // Filter bill items by class categories or specific class IDs
              $this->db->group_start();
              
              // 1. Items with specific_class_ids matching selected classes
              foreach($class_ids as $class_id) {
                  $this->db->or_where("FIND_IN_SET('$class_id', bill_item.specific_class_ids) >", 0);
              }
              
              // 2. Items with class_category matching (but no specific_class_ids)
              $this->db->or_group_start();
              $this->db->where_in('bill_item.class_category', $categories);
              $this->db->group_start();
              $this->db->where('bill_item.specific_class_ids IS NULL');
              $this->db->or_where('bill_item.specific_class_ids', '');
              $this->db->group_end();
              $this->db->group_end();
              
              // 3. Global items (no specific_class_ids and no class_category)
              $this->db->or_group_start();
              $this->db->group_start();
              $this->db->where('bill_item.specific_class_ids IS NULL');
              $this->db->or_where('bill_item.specific_class_ids', '');
              $this->db->group_end();
              $this->db->group_start();
              $this->db->where('bill_item.class_category IS NULL');
              $this->db->or_where('bill_item.class_category', '');
              $this->db->group_end();
              $this->db->group_end();
              
              $this->db->group_end();
          }
      }
      
      $bill_items = $this->db->get();

      if($bill_items->num_rows() > 0) {?>

        <select id="<?=$item_id;?>_title" name="<?=$item_id;?>_title" data-validate="required" data-message-required="<?php echo get_phrase('value_required');?>" class="select2 bg-gray-50 border border-gray-300 text-gray-900 rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full h-16 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white text-xl dark:focus:ring-primary-500 dark:focus:border-primary-500" required="true" onchange="getItemDetails($(this).attr('id'), $(this).val())">

            <option value="">Select Billing Item</option>
            <?php

        foreach($bill_items->result_array() as $item):?>

      
          <option value="<?=$item['title'];?>"><?=$item['title']; ?></option>
            <?php
              endforeach;
            }
            ?>
            </select>

  </div>
  <div class="w-full hidden-xs">
  <input type="text" name="<?=$item_id;?>_category" id="<?=$item_id;?>_category" class="bg-gray-200 border border-gray-300 text-gray-500 rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full h-16 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white text-xl dark:focus:ring-primary-500 dark:focus:border-primary-500 cursor-not-allowed" placeholder="Bill item category"  readonly="readonly">
  </div>
  <div class="w-full hidden-xs">
  <input type="text" name="<?=$item_id;?>_description" id="<?=$item_id;?>_description" class="bg-gray-200 border border-gray-300 text-gray-500 rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full h-16 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white text-xl dark:focus:ring-primary-500 dark:focus:border-primary-500 cursor-not-allowed" placeholder="Bill item description"  readonly="readonly">
  </div>

  <div class="w-96 max-w-96">
  <input type="text" id="<?=$item_id;?>_amount"  name="<?=$item_id;?>_amount" data-validate="required" data-message-required="<?php echo get_phrase('value_required');?>" class="bg-gray-50 border border-gray-300 text-gray-900 rounded-lg focus:ring-primary-600 total_amount focus:border-primary-600 block w-full h-16 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white text-xl dark:focus:ring-primary-500 dark:focus:border-primary-500" placeholder="Amount" required="true">
  </div>

  <div class="flex gap-3 w-fit max-w-fit">

    <?php
    if($type == 'mass') {
      ?>
      <a href="javascript:void(0);" onclick="add_invoice_item2('<?=$item_id;?>', 'mass')" id="<?=$item_id;?>_add_btn" class="btn btn-info btn-sm"><i class="fa fa-plus"></i></a>

      <a href="javascript:(void);" onclick="remove_invoice_item2('<?= $item_id; ?>', 'mass', $('#<?=$item_id;?>_title').val())" class="btn btn-danger btn-sm"><i class="fa fa-remove"></i></a>
      <?php
    } else {
      ?>

      <a href="javascript:void(0);" disabled onclick="add_invoice_item2('<?=$item_id;?>')" id="<?=$item_id;?>_add_btn" class="btn btn-info btn-sm"><i class="fa fa-plus"></i></a>

      <a href="javascript:(void);" onclick="remove_invoice_item2('<?= $item_id; ?>', ' ', $('#<?=$item_id;?>_title').val())" class="btn btn-danger btn-sm"><i class="fa fa-remove"></i></a>
      <?php
    }
    ?>
  </div>
</div>

<script type="text/javascript">
  $('select').select2();
</script>



