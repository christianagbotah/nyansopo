
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
	  	$this->db->where_not_in('title', $bill_item);
	    $bill_items = $this->db->get('bill_item');

	    if($bill_items->num_rows() > 0) {?>

	      <select id="<?=$item_id;?>_title" name="<?=$item_id;?>_title" data-validate="required" data-message-required="<?php echo get_phrase('value_required');?>" class="select2 bg-gray-50 border border-gray-300 text-gray-900 rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full h-16 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white text-xl dark:focus:ring-primary-500 dark:focus:border-primary-500" required="true" onchange="getItemDetails($(this).attr('id'), $(this).val())">

	          <option value="">Select Billing Item</option>
	          <?php

	      foreach($bill_items->result_array() as $item):?>

	    
	        <option value="<?=$item['title'];?>"><?=$item['title'];?></option>
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

	<div class="w-full">
	<input type="text" id="<?=$item_id;?>_amount"  name="<?=$item_id;?>_amount" data-validate="required" data-message-required="<?php echo get_phrase('value_required');?>" class="bg-gray-50 border border-gray-300 text-gray-900 rounded-lg focus:ring-primary-600 total_amount focus:border-primary-600 block w-full h-16 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white text-xl dark:focus:ring-primary-500 dark:focus:border-primary-500" placeholder="Bill item amount" required="true">
	</div>

	<div class="flex gap-3 w-full">

		<?php
		if($type == 'single') {
			?>
			<a href="javascript:void(0);" onclick="add_invoice_item('<?=$item_id;?>', 'single')" id="<?=$item_id;?>_add_btn" class="btn btn-info btn-sm"><i class="fa fa-plus"></i></a>

			<a href="javascript:(void);" onclick="remove_invoice_item('<?= $item_id; ?>', 'single')" class="btn btn-danger btn-sm"><i class="fa fa-remove"></i></a>
			<?php
		} else {
			?>

			<a href="javascript:void(0);" disabled onclick="add_invoice_item('<?=$item_id;?>')" id="<?=$item_id;?>_add_btn" class="btn btn-info btn-sm"><i class="fa fa-plus"></i></a>

			<a href="javascript:(void);" onclick="remove_invoice_item('<?= $item_id; ?>')" class="btn btn-danger btn-sm"><i class="fa fa-remove"></i></a>
			<?php
		}
		?>
	</div>
</div>

<script type="text/javascript">
	$('select').select2();
</script>



