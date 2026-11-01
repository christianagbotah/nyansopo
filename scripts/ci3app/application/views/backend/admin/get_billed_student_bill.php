<?php
      if(empty($invoice_code) || $invoice_code == '') return;

      $codeData = $this->db->get_where('invoice', ['invoice_code' => $invoice_code])->result_array();
      $ids = array();

        foreach($codeData as $cd) {

           $item_id = rand().'_'.time();
           $cat_id = $this->crud_model->getBillCategoryIdByItemTitle($cd['title']);
           $cat_name = $this->crud_model->getBillCategoryNameById($cat_id);

           $ids[] = $item_id;
          ?>

          <div class="flex gap-5 mb-5 row" id="<?=$item_id;?>">
              <div class="w-full">

                  <select id="<?=$item_id;?>_title" name="<?=$item_id;?>_title" data-validate="required" data-message-required="<?php echo get_phrase('value_required');?>" class="select2 bg-gray-50 border border-gray-300 text-gray-900 rounded-lg focus:ring-primary-500 text-xl focus:border-primary-500 block w-full h-16 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark
                    :focus:border-primary-500" required="true">

                
                    <option value="<?=$cd['title'];?>"><?=$cd['title'];?></option>

                </select>

                </div>
                <div class="w-full hidden-xs">
                    <input type="text" name="<?=$item_id;?>_category" id="<?=$item_id;?>_category" class="bg-gray-200 border border-gray-300 text-gray-500 rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full h-16 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white text-xl dark:focus:ring-primary-500 dark:focus:border-primary-500 cursor-not-allowed" placeholder="Bill item category" value="<?=$cat_name;?>" readonly="readonly">
                </div>
                <div class="w-full hidden-xs">
                    <input type="text" name="<?=$item_id;?>_description" id="<?=$item_id;?>_description" class="bg-gray-200 border border-gray-300 text-gray-500 rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full h-16 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white text-xl dark:focus:ring-primary-500 dark:focus:border-primary-500 cursor-not-allowed" placeholder="Bill item description" value="<?=$cd['description'];?>"  readonly="readonly">
                </div>

                <div class="w-96 max-w-96">
                  <input type="text" id="<?=$item_id;?>_amount"  name="<?=$item_id;?>_amount" data-validate="required" data-message-required="<?php echo get_phrase('value_required');?>" class="bg-gray-50 border border-gray-300 text-gray-900 rounded-lg focus:ring-primary-600 total_amount focus:border-primary-600 block w-full h-16 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white text-xl dark:focus:ring-primary-500 dark:focus:border-primary-500" placeholder="Amount" value="<?=$cd['amount'];?>" required="true">
                </div>

                <div class="flex gap-3 w-fit max-w-fit">
                  <a href="javascript:void(0);" onclick="add_invoice_item_list('<?=$item_id;?>', '')" id="<?=$item_id;?>_add_btn" class="btn btn-info btn-sm"><i class="fa fa-plus"></i></a>

                  <a href="javascript:(void);" onclick="remove_invoice_item_list('<?= $item_id; ?>', ' ', $('#<?=$item_id;?>_title').val())" class="btn btn-danger btn-sm"><i class="fa fa-remove"></i></a>

                </div>
            </div> 

          <?php
        }
      ?>


       




<script type="text/javascript">
  $('select').select2();

  let ids = [];
  ids = <?=json_encode($ids);?>;


</script>