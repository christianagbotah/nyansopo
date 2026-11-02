

<?php
  $allBillCategory = $this->crud_model->getAllBillCategory();
  $currency = $this->db->get_where('settings', array('type' => 'currency'))->row()->description;
?>

<style type="text/css">
  .table-striped > tbody > tr:nth-child(odd) > td, .table-striped > tbody > tr:nth-child(odd) > th {
    background-color: #ffffff !important;
  }

  .validate-has-error {

    color: #ef5350 !important;
  }

  /* Student name word wrap fix */
  #bulk_invoices_datatable td {
    word-wrap: break-word;
    word-break: break-word;
    white-space: normal !important;
    max-width: 250px;
  }

  #bulk_invoices_datatable th {
    white-space: nowrap;
  }

  /* Prevent number wrapping in amount columns (6th, 7th, 8th columns) */
  #bulk_invoices_datatable td:nth-child(6),
  #bulk_invoices_datatable td:nth-child(7),
  #bulk_invoices_datatable td:nth-child(8) {
    white-space: nowrap !important;
    word-break: keep-all !important;
  }

  /* Specifically target the student name column (3rd column) */
  #bulk_invoices_datatable td:nth-child(3) {
    max-width: 200px;
    min-width: 150px;
  }

  /* Extend action column to accommodate full button text (3 buttons) */
  #bulk_invoices_datatable td:nth-child(10) {
    min-width: 140px;
    max-width: 160px;
  }
  
  /* Align specific classes multi-select with other fields - add margin-top to align */
  td [id^="input_specific_classes_"],
  td [id^="display_specific_classes_"] {
    display: block;
    padding-top: 0 !important;
    margin-top: 0 !important;
  }
  [id^="input_specific_classes_"] .select2-container {
    margin-top: 10px !important;
    vertical-align: top !important;
  }
  [id^="input_specific_classes_"] .select2-container .select2-selection--multiple {
    min-height: 80px !important;
    max-height: 80px !important;
    height: 80px !important;
    overflow-y: auto !important;
    display: flex !important;
    align-items: flex-start !important;
  }
  [id^="input_specific_classes_"] .select2-container .select2-selection__rendered {
    max-height: 68px !important;
    overflow-y: auto !important;
    padding-bottom: 4px !important;
  }
  
  /* Increase amount input field width for better display */
  [id^="edit_bill_amount_"] {
    min-width: 150px !important;
  }
</style>


<hr />
<div class="row">
  <div class="col-md-12">
      <!-- Arrears Tab Toggle -->
      <div class="flex justify-end mb-3">
        <label class="flex items-center gap-2 cursor-pointer">
          <span class="text-lg font-semibold text-gray-700">Enable Arrears Upload Tab</span>
          <div class="switch-button">
            <input type="checkbox" id="toggle_arrears_tab" onchange="toggleArrearsTab()">
            <label for="toggle_arrears_tab"></label>
          </div>
        </label>
      </div>
      
      <ul class="nav nav-tabs bordered">
        <li class="active">

          <a href="#bill_item" class="font-bold text-xl" data-toggle="tab">
            <i class="fa-solid fa-plus-circle fa-2x"></i> 
            <span class="hidden-xs uppercase"><?php echo get_phrase('add_billing_item');?></span>
            <span class="visible-xs uppercase"><?php echo get_phrase('bill_item');?></span>
          </a>
        </li>

        <!-- <li class="active" onclick="resetInvoice()">
          <a href="#unpaid" class="font-bold text-xl" data-toggle="tab">
            <span class="hidden-xs"><?php //echo get_phrase('create_single_invoice');?></span>
            <span class="visible-xs"><?php //echo get_phrase('single');?></span>
          </a>
        </li> -->

        <li onclick="resetInvoice()">
          <a href="#paid" class="font-bold text-xl" data-toggle="tab">
            <i class="fa-solid fa-credit-card fa-2x"></i> 
            <span class="hidden-xs uppercase"><?php echo get_phrase('create_invoice');?></span>
            <span class="visible-xs uppercase"><?php echo get_phrase('create_invoice');?></span>
          </a>
        </li>

        <!-- <li>
          <a href="#view_invoices" class="font-bold text-xl" data-toggle="tab">
            <i class="fa-solid fa-list fa-2x"></i> 
            <span class="hidden-xs uppercase"><?php echo get_phrase('view_invoices');?></span>
            <span class="visible-xs uppercase"><?php echo get_phrase('view_invoices');?></span>
          </a>
        </li> -->

        <li>
          <a href="#manage_bulk_invoices" class="font-bold text-xl" data-toggle="tab">
            <i class="fa-solid fa-edit fa-2x"></i> 
            <span class="hidden-xs uppercase"><?php echo get_phrase('manage_bulk_invoices');?></span>
            <span class="visible-xs uppercase"><?php echo get_phrase('bulk_manage');?></span>
          </a>
        </li>

        <li id="arrears_tab_li" style="display:<?php echo $this->db->get_where('settings', array('type' => 'enable_arrears_tab'))->row()->description == 'yes' ? 'block' : 'none'; ?>;">
          <a href="#bulk_arrears_import" class="font-bold text-xl" data-toggle="tab">
            <i class="fa-solid fa-file-import fa-2x"></i> 
            <span class="hidden-xs uppercase"><?php echo get_phrase('bulk_arrears_import');?></span>
            <span class="visible-xs uppercase"><?php echo get_phrase('arrears');?></span>
          </a>
        </li>

        <li>
          <a href="#receipt_modifications" class="font-bold text-xl" data-toggle="tab">
            <i class="fa-solid fa-shield-alt fa-2x"></i> 
            <span class="hidden-xs uppercase"><?php echo get_phrase('modification_requests');?></span>
            <span class="visible-xs uppercase"><?php echo get_phrase('modifications');?></span>
            <?php 
            $receipt_pending = $this->db->where('status', 'pending')->count_all_results('receipt_modification_requests');
            $invoice_pending = $this->db->where('status', 'pending')->count_all_results('invoice_modification_requests');
            $total_pending = $receipt_pending + $invoice_pending;
            if($total_pending > 0): 
            ?>
            <span class="badge badge-warning" style="font-size: 14px; padding: 6px 12px; border-radius: 20px; background: #f59e0b; color: white; margin-left: 8px;"><?php echo $total_pending; ?></span>
            <?php endif; ?>
          </a>
        </li>

        <!-- <li>
          <a href="#upload_invoices" class="font-bold text-xl" data-toggle="tab">
            <span class="hidden-xs"><?php //echo get_phrase('upload_invoices');?></span>
            <span class="visible-xs"><?php //echo get_phrase('upload');?></span>
          </a>
        </li> -->
      </ul>
      
      <div class="tab-content">
            <br>
        <!-- create  bill item -->    
        <div class="tab-pane active" id="bill_item">

          <!-- creation of single invoice -->    
          <?php echo form_open(site_url('admin/invoice/add_bill_item/') , array('class' => 'form-horizontal form-groups-bordered validate','target'=>'_top', 'id' => 'add_bill_item_form'));?>
              <div class="row">
                <div class="col-md-12 col-sm-12">
                  <div class="panel panel-info panel-shadow p-5" data-collapsed="0">
                    <div class="panel-heading">
                        <div class="panel-title"><?php echo get_phrase('ADD INVOICE ITEM');?></div>
                          <div class="grid grid-cols-1 md:grid-cols-7 gap-3 mt-5 w-full">
                            <div class="w-full">
                                <input type="text" name="bill_title" id="bill_title"
                                    data-validate="required" data-message-required="<?php echo get_phrase('value_required');?>" class="bg-gray-50 border border-gray-300 text-gray-900 rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full h-20 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white text-xl dark:focus:ring-primary-500 dark:focus:border-primary-500" placeholder="Bill item title" autofocus required="true" autofocus="true">
                            </div>
                            <div class="w-full">
                              <select id="bill_category" name="bill_category" data-validate="required" data-message-required="<?php echo get_phrase('value_required');?>" class="select2 bg-gray-50 border border-gray-300 text-gray-900 rounded-lg text-xl focus:ring-primary-500 focus:border-primary-500 block w-full h-20 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500" required="true">    
                            
                                    <option value="">Select Bill Category</option>

                                    <?php
                                        foreach($allBillCategory as $bCat) {
                                            ?>
                                            <option value="<?=$bCat['bill_category_id'];?>"><?=$bCat['bill_category_name'];?></option>
                                            <?php
                                        }
                                    ?>
                                  
                                </select>
                            </div>

                            <div class="w-full">
                              <select id="bill_class_category" name="class_category" class="select2 bg-gray-50 border border-gray-300 text-gray-900 rounded-lg text-xl focus:ring-primary-500 focus:border-primary-500 block w-full h-20 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500">    
                                    <option value="">All Classes (Optional)</option>
                                    <?php
                                        // Get distinct class categories from class table
                                        $this->db->distinct();
                                        $this->db->select('category');
                                        $this->db->from('class');
                                        $this->db->where('category IS NOT NULL', NULL, FALSE);
                                        $this->db->where('category !=', '');
                                        $categories = $this->db->get()->result_array();
                                        
                                        // Custom order: Pre-School, Lower Primary, Upper Primary, JHS
                                        $order_map = array(
                                            'Pre-School' => 1,
                                            'Lower Primary' => 2,
                                            'Upper Primary' => 3,
                                            'JHS' => 4
                                        );
                                        
                                        // Sort categories based on custom order
                                        usort($categories, function($a, $b) use ($order_map) {
                                            $order_a = isset($order_map[$a['category']]) ? $order_map[$a['category']] : 999;
                                            $order_b = isset($order_map[$b['category']]) ? $order_map[$b['category']] : 999;
                                            return $order_a - $order_b;
                                        });
                                        
                                        foreach($categories as $cat) {
                                            if(!empty($cat['category'])) {
                                                echo '<option value="'.$cat['category'].'">'.$cat['category'].'</option>';
                                            }
                                        }
                                    ?>
                                </select>
                            </div>

                            <div class="w-full">
                              <select id="bill_specific_class_ids" name="specific_class_ids[]" multiple class="select2 bg-gray-50 border border-gray-300 text-gray-900 rounded-lg text-xl focus:ring-primary-500 focus:border-primary-500 block w-full h-20 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500">    
                                    <option value="">Specific Classes (Optional - overrides category)</option>
                                    <?php
                                        // Get all classes with sections
                                        $this->db->select('class.class_id, class.name, class.name_numeric, class.category, section.name as section_name');
                                        $this->db->from('class');
                                        $this->db->join('section', 'section.class_id = class.class_id', 'left');
                                        $all_classes = $this->db->get()->result_array();
                                        
                                        // Debug: log what we got
                                        error_log('Total classes found: ' . count($all_classes));
                                        if(!empty($all_classes)) {
                                            error_log('Sample class: ' . json_encode($all_classes[0]));
                                            $unique_categories = array_unique(array_column($all_classes, 'category'));
                                            error_log('Unique categories: ' . json_encode($unique_categories));
                                        }
                                        
                                        // Use the same ordering logic as getFullClassList
                                        $class_order = ['CRECHE', 'NURSERY', 'KG', 'BASIC', 'JHS'];
                                        
                                        // Custom category order
                                        $category_order_map = array(
                                            'Pre-School' => 1,
                                            'Lower Primary' => 2,
                                            'Upper Primary' => 3,
                                            'JHS' => 4
                                        );
                                        
                                        // Sort by custom order
                                        usort($all_classes, function($a, $b) use ($class_order, $category_order_map) {
                                            // First sort by category using custom order
                                            $cat_a = isset($category_order_map[$a['category']]) ? $category_order_map[$a['category']] : 999;
                                            $cat_b = isset($category_order_map[$b['category']]) ? $category_order_map[$b['category']] : 999;
                                            
                                            if($cat_a != $cat_b) {
                                                return $cat_a - $cat_b;
                                            }
                                            
                                            // Then by name order
                                            $name_a = array_search($a['name'], $class_order);
                                            $name_b = array_search($b['name'], $class_order);
                                            
                                            if($name_a === false) $name_a = 999;
                                            if($name_b === false) $name_b = 999;
                                            
                                            if($name_a != $name_b) {
                                                return $name_a - $name_b;
                                            }
                                            
                                            // Then by numeric
                                            if($a['name_numeric'] != $b['name_numeric']) {
                                                return (int)$a['name_numeric'] - (int)$b['name_numeric'];
                                            }
                                            
                                            // Finally by section
                                            return strcmp($a['section_name'], $b['section_name']);
                                        });
                                        
                                        // Output grouped by category
                                        $current_category = '';
                                        foreach($all_classes as $cls) {
                                            // Add optgroup for category
                                            if($cls['category'] != $current_category) {
                                                if($current_category != '') {
                                                    echo '</optgroup>';
                                                }
                                                echo '<optgroup label="'.(empty($cls['category']) ? 'Other' : $cls['category']).'">';
                                                $current_category = $cls['category'];
                                            }
                                            
                                            // Output each class with full name (name + numeric + section)
                                            $full_name = $cls['name'] . ' ' . $cls['name_numeric'] . ' ' . $cls['section_name'];
                                            echo '<option value="'.$cls['class_id'].'">'.$full_name.'</option>';
                                        }
                                        
                                        if($current_category != '') {
                                            echo '</optgroup>';
                                        }
                                    ?>
                                </select>
                            </div>

                            <div class="w-full">
                                <input type="number" step="0.01" name="bill_amount" id="bill_amount"
                                data-validate="required" data-message-required="<?php echo get_phrase('value_required');?>" class="bg-gray-50 border border-gray-300 text-gray-900 rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full h-20 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white text-xl dark:focus:ring-primary-500 dark:focus:border-primary-500" placeholder="Bill item amount" required="true">
                            </div>
                            <div class="w-full">
                                <textarea rows="2" id="bill_description" name="bill_description" class="block p-2.5 w-full h-20 text-gray-900 bg-gray-50 rounded-lg border border-gray-300 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white text-xl dark:focus:ring-primary-500 dark:focus:border-primary-500 resize-none" placeholder="Bill item description"></textarea>
                            </div>
                          <div class="w-full flex items-end h-20">
                            <?=get_button('submit', 'Add Bill Item');?>
                            </div>
                            </div>
                        </div>
                    <div class="panel-body p-5">
                      <div class="row">
                        <div class="form-group col-sm-12 col-md-12">
                          
                          <!-- Bulk Actions Toolbar -->
                          <div id="bulk_actions_toolbar" class="bg-blue-50 border-2 border-blue-300 rounded-lg p-4 mb-4" style="display: none; position: sticky; top: 0; z-index: 100; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                            <div class="flex items-center gap-4 flex-wrap">
                              <div class="flex items-center gap-2">
                                <span class="font-bold text-blue-900"><span id="selected_count">0</span> item(s) selected</span>
                              </div>
                              <div class="flex gap-2 flex-wrap">
                                <button type="button" id="bulk_edit_btn" onclick="enableBulkEdit()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-semibold">
                                  <i class="fa fa-edit"></i> Edit Selected
                                </button>
                                <button type="button" id="bulk_save_btn" onclick="saveBulkEdit()" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 font-semibold" style="display: none;">
                                  <i class="fa fa-save"></i> Save All Changes
                                </button>
                                <button type="button" id="bulk_cancel_btn" onclick="cancelBulkEdit()" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 font-semibold" style="display: none;">
                                  <i class="fa fa-times"></i> Cancel
                                </button>
                                <button type="button" id="bulk_delete_btn" onclick="bulkDelete()" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 font-semibold">
                                  <i class="fa fa-trash"></i> Delete Selected
                                </button>
                                <button type="button" onclick="clearSelection()" class="px-4 py-2 bg-gray-400 text-white rounded-lg hover:bg-gray-500">
                                  Clear Selection
                                </button>
                              </div>
                            </div>
                          </div>

                          <div class="relative overflow-x-auto shadow-md sm:rounded-lg mt-10">
                              <table class="responsive w-full text-2xl text-left rtl:text-right text-gray-500 dark:text-gray-400" id="bill_items_table">
                                  <thead class="text-xl font-bold text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                      <tr>
                                        <th scope="col" class="px-6 py-3">
                                          <input type="checkbox" id="select_all_bill_items" class="w-5 h-5 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500" style="cursor: pointer;">
                                        </th>
                                        <th scope="col" class="px-6 py-3">S/N</th>
                                        <th scope="col" class="px-6 py-3">TITLE</th>
                                        <th scope="col" class="px-6 py-3">CATEGORY</th>
                                        <th scope="col" class="px-6 py-3">CLASS CATEGORY</th>
                                        <th scope="col" class="px-6 py-3">SPECIFIC CLASSES</th>
                                        <th scope="col" class="px-6 py-3">AMOUNT</th>
                                        <th scope="col" class="px-6 py-3">DESCRIPTION</th>
                                        <th scope="col" class="px-6 py-3">ACTION</th>
                                          </tr>
                                      </thead>
                                  <tbody>

                                    <?php
                                $items = $this->db->get('bill_item');

                                if($items->num_rows() > 0) {
                                  $sn = 1;
                                  $items_ids = [];

                                  foreach($items->result_array() as $item): 
                                    array_push($items_ids, $item['id']);
                                    ?>
                                    <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 border-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600">
                                      <td class="px-6 py-2">
                                        <input type="checkbox" class="bill_item_checkbox w-5 h-5 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500" value="<?=$item['id'];?>" style="cursor: pointer;">
                                      </td>
                                      <td class="px-6 py-2"><?=$sn;?></td>
                                      <td width="300" class="px-6 py-2">
                                        <div id="display_title_<?=$item['id'];?>">
                                          <?=$item['title'];?>
                                        </div>
                                        <div style="display: none" id="input_title_<?=$item['id'];?>">
                                          <input type="text" class="bg-gray-50 border border-gray-300 text-gray-900 rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full h-20 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white text-xl dark:focus:ring-primary-500 dark:focus:border-primary-500" value="<?=$item['title'];?>" name="edit_bill_title_<?=$item['id'];?>" id="edit_bill_title_<?=$item['id'];?>" title="<?=$item['title'];?>"
                                  data-validate="required"data-message-required="<?php echo get_phrase('value_required');?>"/>

                                          <input type="hidden" value="<?=$item['title'];?>" id="hidden_title_<?=$item['id'];?>"/>
                                        </div>
                                    
                                    </td>
                                    <td width="300" class="px-6 py-2">
                                        <div id="display_category_<?=$item['id'];?>">
                                          <?=$this->crud_model->getBillCategoryNameById($item['bill_category_id']);?>
                                        </div>
                                        <div style="display: none" id="input_category_<?=$item['id'];?>">

                                          <select id="edit_bill_category_<?=$item['id'];?>" name="edit_bill_category_<?=$item['id'];?>" class="select2 bg-gray-50 border border-gray-300 text-gray-900 rounded-lg text-xl focus:ring-primary-500 focus:border-primary-500 block w-full h-20 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500" required="true" data-validate="required"data-message-required="<?php echo get_phrase('value_required');?>">                
                                              
                                              <?php
                                                
                                                foreach($allBillCategory as $cat) {
                                                  ?>
                                                  <option value="<?=$cat['bill_category_id'];?>" <?=$item['bill_category_id'] == $cat['bill_category_id'] ? 'selected' : '';?>><?=$cat['bill_category_name'];?></option>
                                                  <?php
                                                }
                                              ?>
                                          </select>

                                          <input type="hidden" value="<?=$item['bill_category_id'];?>" id="hidden_category_<?=$item['id'];?>"/>
                                        </div>
                                    
                                    </td>
                                    <td width="200" class="px-6 py-2">
                                        <div id="display_class_category_<?=$item['id'];?>">
                                          <?=!empty($item['class_category']) ? $item['class_category'] : '<span style="color: #999;">All Classes</span>';?>
                                        </div>
                                        <div style="display: none" id="input_class_category_<?=$item['id'];?>">
                                          <select id="edit_bill_class_category_<?=$item['id'];?>" name="edit_bill_class_category_<?=$item['id'];?>" class="select2 bg-gray-50 border border-gray-300 text-gray-900 rounded-lg text-xl focus:ring-primary-500 focus:border-primary-500 block w-full h-20 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500">                
                                              <option value="">All Classes</option>
                                              <?php
                                                // Get distinct class categories from class table
                                                $this->db->distinct();
                                                $this->db->select('category');
                                                $this->db->from('class');
                                                $this->db->where('category IS NOT NULL', NULL, FALSE);
                                                $this->db->where('category !=', '');
                                                $edit_categories = $this->db->get()->result_array();
                                                
                                                // Custom order: Pre-School, Lower Primary, Upper Primary, JHS
                                                $edit_order_map = array(
                                                    'Pre-School' => 1,
                                                    'Lower Primary' => 2,
                                                    'Upper Primary' => 3,
                                                    'JHS' => 4
                                                );
                                                
                                                // Sort categories based on custom order
                                                usort($edit_categories, function($a, $b) use ($edit_order_map) {
                                                    $order_a = isset($edit_order_map[$a['category']]) ? $edit_order_map[$a['category']] : 999;
                                                    $order_b = isset($edit_order_map[$b['category']]) ? $edit_order_map[$b['category']] : 999;
                                                    return $order_a - $order_b;
                                                });
                                                
                                                foreach($edit_categories as $ecat) {
                                                  if(!empty($ecat['category'])) {
                                                    $selected = ($item['class_category'] == $ecat['category']) ? 'selected' : '';
                                                    echo '<option value="'.$ecat['category'].'" '.$selected.'>'.$ecat['category'].'</option>';
                                                  }
                                                }
                                              ?>
                                          </select>

                                          <input type="hidden" value="<?=$item['class_category'];?>" id="hidden_class_category_<?=$item['id'];?>"/>
                                        </div>
                                    
                                    </td>
                                    <td width="250" class="px-6 py-2">
                                        <div id="display_specific_classes_<?=$item['id'];?>">
                                          <?php 
                                          if(!empty($item['specific_class_ids'])) {
                                            $class_ids = explode(',', $item['specific_class_ids']);
                                            $class_names = [];
                                            foreach($class_ids as $cid) {
                                              $this->db->select('class.name, class.name_numeric, section.name as section_name');
                                              $this->db->from('class');
                                              $this->db->join('section', 'section.class_id = class.class_id', 'left');
                                              $this->db->where('class.class_id', $cid);
                                              $class_row = $this->db->get()->row_array();
                                              if($class_row) {
                                                $class_names[] = $class_row['name'] . ' ' . $class_row['name_numeric'] . ($class_row['section_name'] ? ' ' . $class_row['section_name'] : '');
                                              }
                                            }
                                            echo implode(', ', $class_names);
                                          } else {
                                            echo '<span style="color: #999;">-</span>';
                                          }
                                          ?>
                                        </div>
                                        <div style="display: none" id="input_specific_classes_<?=$item['id'];?>">
                                          <select id="edit_bill_specific_class_ids_<?=$item['id'];?>" name="edit_bill_specific_class_ids_<?=$item['id'];?>[]" multiple class="select2 bg-gray-50 border border-gray-300 text-gray-900 rounded-lg text-xl focus:ring-primary-500 focus:border-primary-500 block w-full h-20 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500" >                
                                              <?php
                                                // Get all classes with full names
                                                $this->db->select('class.class_id, class.name, class.name_numeric, section.name as section_name');
                                                $this->db->from('class');
                                                $this->db->join('section', 'section.class_id = class.class_id', 'left');
                                                $edit_all_classes = $this->db->get()->result_array();
                                                
                                                // Order by CRECHE, NURSERY, KG, BASIC, JHS
                                                $edit_class_order = array('CRECHE' => 1, 'NURSERY' => 2, 'KG' => 3, 'BASIC' => 4, 'JHS' => 5);
                                                usort($edit_all_classes, function($a, $b) use ($edit_class_order) {
                                                    $order_a = isset($edit_class_order[$a['name']]) ? $edit_class_order[$a['name']] : 999;
                                                    $order_b = isset($edit_class_order[$b['name']]) ? $edit_class_order[$b['name']] : 999;
                                                    if ($order_a === $order_b) {
                                                        return (int)$a['name_numeric'] - (int)$b['name_numeric'];
                                                    }
                                                    return $order_a - $order_b;
                                                });
                                                
                                                $current_specific_ids = !empty($item['specific_class_ids']) ? explode(',', $item['specific_class_ids']) : [];
                                                
                                                foreach($edit_all_classes as $ec) {
                                                  $full_name = $ec['name'] . ' ' . $ec['name_numeric'] . ($ec['section_name'] ? ' ' . $ec['section_name'] : '');
                                                  $selected = in_array($ec['class_id'], $current_specific_ids) ? 'selected' : '';
                                                  echo '<option value="'.$ec['class_id'].'" '.$selected.'>'.$full_name.'</option>';
                                                }
                                              ?>
                                          </select>

                                          <input type="hidden" value="<?=$item['specific_class_ids'];?>" id="hidden_specific_class_ids_<?=$item['id'];?>"/>
                                        </div>
                                    
                                    </td>
                                    <td width="300" class="px-6 py-2">
                                        <div id="display_amount_<?=$item['id'];?>">
                                          <?=number_format($item['amount'], 2, '.', ',');?>
                                        </div>
                                        <div style="display: none" id="input_amount_<?=$item['id'];?>">
                                          <input type="number" class="bg-gray-50 border border-gray-300 text-gray-900 rounded-lg focus:ring-primary-600 focus:border-primary-600 block flex-1 w-full h-20 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white text-xl dark:focus:ring-primary-500 dark:focus:border-primary-500" value="<?=$item['amount'];?>" name="edit_bill_amount_<?=$item['id'];?>" id="edit_bill_amount_<?=$item['id'];?>"
                                  data-validate="required"data-message-required="<?php echo get_phrase('value_required');?>"/>

                                          <input type="hidden" value="<?=$item['amount'];?>" id="hidden_amount_<?=$item['id'];?>"/>
                                        </div>
                                    
                                    </td>
                                    <td width="300" class="px-6 py-2">
                                        <div id="display_desc_<?=$item['id'];?>">
                                          <?=$item['description'];?>
                                        </div>
                                        <div style="display: none" id="input_desc_<?=$item['id'];?>">
                                          <textarea rows="2" class="block p-2.5 w-full h-20 text-gray-900 bg-gray-50 rounded-lg border border-gray-300 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white text-xl dark:focus:ring-primary-500 dark:focus:border-primary-500" value="<?=$item['description'];?>" name="edit_bill_description_<?=$item['id'];?>" id="edit_bill_description_<?=$item['id'];?>"data-validate="required"data-message-required="<?php echo get_phrase('value_required');?>"><?=$item['description'];?></textarea>

                                          <input type="hidden" value="<?=$item['description'];?>" id="hidden_desc_<?=$item['id'];?>"/>
                                        </div>
                                    </td>
                                      <td class="px-6 py-2">
                                        <div class="flex gap-2">
                                          <a href="javascript:void(0);" id="edit_button_<?=$item['id'];?>" onclick="editItem('<?=$item['id'];?>');" class="btn btn-success rounded-lg" title="Edit">
                                            <i class="entypo-pencil"></i>
                                          </a>

                                            <?php
                                                if($item['id'] == 8 || $item['id'] == 9):
                                            ?>
                                          <a href="javascript:void(0);" title="System generated, can't be deleted!" id="delete_button_<?=$item['id'];?>" disabled class="btn btn-danger rounded-lg" style="opacity: 0.5; cursor: not-allowed;">
                                            <i class="entypo-trash"></i>
                                          </a>

                                            <?php 
                                            else: ?>
                                          <a href="javascript:void(0);" id="delete_button_<?=$item['id'];?>" onclick="deleteItem('<?=$item['id'];?>');" class="btn btn-danger rounded-lg" title="Delete">
                                            <i class="entypo-trash"></i>
                                          </a>
                                            <?php

                                            endif;
                                            ?>
                                        </div>
                                      </td>

                                    </tr>
                                    <tr>
                                      <td class="px-6 py-2"></td>
                                      <td colspan="8" id="alert_<?=$item['id'];?>" class="px-6 py-2">
                                        
                                      </td>
                                      <td class="px-6 py-2"></td>
                                      <td class="px-6 py-2"></td>
                                    </tr>
                                    <?php
                                    $sn++;
                                  endforeach;
                                } else {
                                  echo '<tr><td align="center" colspan="5">No item found yet! Add one.</td></tr>';
                                }
                              ?>
                                    
                                  </tbody>
                              </table>
                          </div><!-- end -->
                              
                        </div>

                        </div>
                    </div>
                  </div>
                </div>
              </div>
          </form>
        </div><!-- add bill items end -->


            <!-- <div class="tab-pane active" id="unpaid">-->

            <!-- creation of single invoice -->
            <!--<?php //echo form_open(site_url('admin/invoice/create/') , array('class' => 'form-horizontal form-groups-bordered validate','target'=>'_top', 'id' => 'invoice_form'));?>
            <div class="row">
              <div class="col-md-12 col-sm-12">
                  <div class="panel panel-info panel-shadow p-5" data-collapsed="0">
                      <div class="panel-heading">
                        <div class="panel-title"><?php //echo get_phrase('invoice_information_-_Single');?></div>
                        <div class="flex justify-center">
                            <div class="alert alert-danger alert-dismissible" id="error" style="display: none;" role="alert" align="center"> 
                              <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span>
                              </button>
                              <strong>Title, Date and Amount must not be empty!</strong>
                            </div>
                        </div>
                      </div>

                      <div class="panel-body">
                        <div class="flex justify-center items-center">
                          <div class="flex items-center gap-3 alert alert-success alert-dismissible max-w-fit p-4 align-middle" id="success_note" style="display: none" role="alert" align="center"> 
                                <strong>invoice created successfully!</strong>
                                <button type="button" class="close ring-2 ring-green-700 rounded-full w-16 h-16" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span>
                                </button>
                                
                          </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                            <div class="flex flex-col">
                                <label for="house_capacity" class="block mb-2 font-bold text-2xl text-gray-900 dark:text-white hidden-xs">Date</label>
            
                                <div class="relative max-w-full">
                                  <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
                                     <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M20 4a2 2 0 0 0-2-2h-2V1a1 1 0 0 0-2 0v1h-3V1a1 1 0 0 0-2 0v1H6V1a1 1 0 0 0-2 0v1H2a2 2 0 0 0-2 2v2h20V4ZM0 18a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V8H0v10Zm5-8h10a1 1 0 0 1 0 2H5a1 1 0 0 1 0-2Z"/>
                                      </svg>
                                  </div>
                                  <input id="date" id="date" data-format="dd-mm-yyyy" value="<?//=date('d-m-Y')?>" data-message-required="<?php //echo get_phrase('value_required');?>" placeholder="Date" name="date" type="text" class="datepicker bg-gray-50 border border-gray-300 text-gray-900 text-xl rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full max-w-full h-20 ps-10 p-2.5  dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500 font-bold" placeholder="Select date commissioned">
                                </div>
                            </div>
                          
                            <div class="flex flex-col" id="term_holder">
                                <label for="term" class="block mb-2 font-bold text-2xl text-gray-900 dark:text-white hidden-xs">Term</label>
                                <select id="term" name="term" data-message-required="<?php //echo get_phrase('value_required');?>" class="bg-gray-50 border border-gray-300 text-gray-900 rounded-lg focus:ring-primary-500 focus:border-primary-500 text-xl font-bold block max-w-full h-20 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500">
                                    
                                    <?php //$running_term = $this->db->get_where('settings' , array('type'=>'running_term'))->row()->description;?>
                                      <option value="" disabled="true"><?php //echo get_phrase('select_term');?></option>
                                      <?php //for($i = 1; $i <= 3; $i++):?>
                                          <option value="<?php //echo $i;?>"
                                            <?php //if($running_term == $i) echo 'selected';?>>
                                              <?php //echo $i;?>
                                          </option>
                                      <?php //endfor;?>
                                </select>
                            </div>

                            <div class="flex flex-col">
                              <label for="year" class="block mb-2 font-bold text-2xl text-gray-900 dark:text-white hidden-xs">Year</label>
                              <select id="year" name="year" data-message-required="<?php echo get_phrase('value_required');?>" class="select2 bg-gray-50 border text-xl border-gray-300 text-gray-900 rounded-lg focus:ring-primary-500  text-xl font-bold focus:border-primary-500 block max-w-full h-20 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500">
                                  <?php $running_year = $this->db->get_where('settings' , array('type'=>'running_year'))->row()->description;?>
                                  <option value="" disabled="true"><?php echo get_phrase('select_year');?></option>
                                  <?php
                                      echo populate_academic_year('yes');
                                    ?>
                              </select>
                            </div>


                            <div class="flex flex-col">
                              <label for="year" class="block mb-2 font-bold text-2xl text-gray-900 dark:text-white hidden-xs">Class</label>
                              <select name="class_id" data-message-required="<?php echo get_phrase('value_required');?>" class="select2 bg-gray-50 border text-xl border-gray-300 text-gray-900 rounded-lg focus:ring-primary-500  text-xl font-bold focus:border-primary-500 block max-w-full h-20 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500 class_id" onchange="get_class_students(this.value);">
                                      <option value=""><?php echo get_phrase('select_class');?></option>
                                      <?php

                                          getFullClassList();
                                      ?>
                                        
                                    </select>
                              </div>

                              <div class="flex flex-col">
                                <label for="year" class="block mb-2 font-bold text-2xl text-gray-900 dark:text-white">Student</label>
                                <select id="student_selection_holder" name="student_id" data-message-required="<?php echo get_phrase('value_required');?>" class="select2 bg-gray-50 border text-xl border-gray-300 text-gray-900 rounded-lg focus:ring-primary-500  text-xl font-bold focus:border-primary-500 block max-w-full h-20 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500">
                                    <option value=""><?php echo get_phrase('select_class_first');?></option>
                                </select>
                                </div>
                              </div>
                            </div>

                            <hr>-->

                            <!-- Pre holder if sample is present-->

                            <!-- <div id="invoice_items_holder_generated" class="p-5"></div> --><!--End-->


                            <!-- <div id="invoice_items_holder" style="display: none;" class="p-5">
                              <div class="flex gap-5 mb-5 row" id="16484_1565043896">
                                <div class="w-full">

                                      <?php
                                        $bill_items = $this->db->get('bill_item');

                                        if($bill_items->num_rows() > 0) {?>

                                          <select id="16484_1565043896_title" name="16484_1565043896_title" data-validate="required" data-message-required="<?php echo get_phrase('value_required');?>" class="select2 bg-gray-50 border border-gray-300 text-gray-900 rounded-lg focus:ring-primary-500 text-xl focus:border-primary-500 block w-full h-16 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500" required="true" onchange="getItemDetails($(this).attr('id'), $(this).val())">

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
                                    <input type="text" name="16484_1565043896_category" id="16484_1565043896_category" class="bg-gray-200 border border-gray-300 text-gray-500 rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full h-16 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white text-xl dark:focus:ring-primary-500 dark:focus:border-primary-500 cursor-not-allowed" placeholder="Bill item category"  readonly="readonly">
                                </div>
                                <div class="w-full hidden-xs">
                                    <input type="text" name="16484_1565043896_description" id="16484_1565043896_description" class="bg-gray-200 border border-gray-300 text-gray-500 rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full h-16 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white text-xl dark:focus:ring-primary-500 dark:focus:border-primary-500 cursor-not-allowed" placeholder="Bill item description"  readonly="readonly">
                                </div>

                                <div class="w-full">
                                  <input type="text" id="16484_1565043896_amount"  name="16484_1565043896_amount" data-validate="required" data-message-required="<?php echo get_phrase('value_required');?>" class="bg-gray-50 border border-gray-300 text-gray-900 rounded-lg focus:ring-primary-600 total_amount focus:border-primary-600 block w-full h-16 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white text-xl dark:focus:ring-primary-500 dark:focus:border-primary-500" placeholder="Bill item amount">
                                </div>

                                <div class="flex gap-3 w-full">
                                  <a href="javascript:void(0);" disabled onclick="add_invoice_item('16484_1565043896', '')" id="16484_1565043896_add_btn" class="btn btn-info btn-sm"><i class="fa fa-plus"></i></a>
                                </div>
                            </div>                          
                        </div>
                  -->
                        <!-- submit button -->
                            <!-- <div class="flex justify-end">
                                <?php

                                  //echo get_button('submit', 'CREATE INVOICE', 'submit2 font-semibold');
                                ?>
                            </div> 
                      </div>
              </div>
            </div> -->

              <?php //echo form_close();?>

            <!-- creation of single invoice -->
            <!-- </div> -->

         <!-- create mass invoice -->
        <div class="tab-pane" id="paid">

          <!-- creation of mass invoice -->
          <?php echo form_open(site_url('admin/mass_invoice_create') , array('class' => 'form-horizontal form-groups-bordered validate', 'id'=> 'mass_invoice_form' ,'target'=>'_top'));?>
          <div class="row">
          <div class="col-md-12 col-sm-12">
            <div class="panel panel-info panel-shadow p-5" data-collapsed="0">
              <div class="panel-heading">
                <div class="flex panel-title uppercase justify-between items-center">
                  <span>Invoice Information - SINGLE/BULK</span>
                  <a href="<?php echo site_url('admin/fee_structure'); ?>" target="_blank" 
                     class="inline-flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 font-semibold rounded-lg shadow-lg hover:shadow-xl transition-all duration-300 transform hover:scale-105"
                     style="text-decoration: none; text-transform: none; color: white !important;">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: white;">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <span style="color: white;">View Fee Structure</span>
                  </a>
                </div>
                <div class="flex justify-center">
                  <div class="alert alert-danger alert-dismissible" id="error2" style="display: none;" role="alert" align="center"> 
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span>
                    </button>
                    <strong>Title, Date and Amount must not be empty!</strong>
                  </div>
                </div>
            </div>
            <div class="panel-body">
                <div class="flex justify-center items-center">
                  <div class="flex items-center gap-3 alert alert-success alert-dismissible max-w-fit p-4 align-middle" id="success_note2" style="display: none;" role="alert" align="center"> 
                        <strong>Mass invoices were created successfully!</strong>
                        <button type="button" class="close ring-2 ring-green-700 rounded-full w-16 h-16" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span>
                        </button>
                        
                  </div>
                </div>

                <div class="flex flex-col justify-center p-5 bg-yellow-100 rounded-lg shadow-md mb-10">
                  <div>
                    <h2 class="mb-2 text-2xl font-semibold text-gray-900 dark:text-white"><i class="fa-solid fa-info-circle"></i> Here, you can do the following:</h2>
                  </div>
                  <div>
                    <ul class="max-w-full space-y-1 text-gray-500 list-inside dark:text-gray-400">
                      <li class="flex items-center text-xl font-semibold">
                          <svg class="w-6 h-6 me-2 text-green-500 dark:text-green-400 shrink-0" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                              <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5Zm3.707 8.207-4 4a1 1 0 0 1-1.414 0l-2-2a1 1 0 0 1 1.414-1.414L9 10.586l3.293-3.293a1 1 0 0 1 1.414 1.414Z"/>
                           </svg>
                          Create New Bulk Invoices

                          
                      </li>
                      <li class="flex items-center text-xl font-semibold">
                          <svg class="w-6 h-6 me-2 text-green-500 dark:text-green-400 shrink-0" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                              <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5Zm3.707 8.207-4 4a1 1 0 0 1-1.414 0l-2-2a1 1 0 0 1 1.414-1.414L9 10.586l3.293-3.293a1 1 0 0 1 1.414 1.414Z"/>
                           </svg>

                           <span class="flex items-center text-xl text-gray-500 dark:text-gray-400">Update Bulk Invoices: <button data-popover-target="popover-description" data-popover-placement="bottom-end" type="button"> <span class="ml-5">See how</span> <i class="fa-solid fa-question-circle text-2xl"></i></button></span>
                          

                          
                      </li>

                    </ul>

                    <div data-popover id="popover-description" role="tooltip" class="absolute z-10 invisible inline-block text-lg text-gray-500 transition-opacity duration-300 bg-white border border-gray-400 rounded-lg shadow-md opacity-0 w-72 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-400">
                            <div class="p-3 space-y-2">
                                <h5 class="font-semibold text-gray-900 dark:text-white">Update Bill Item</h5>
                                <p>Just select the classes and choose the bill items you want to edit and input the correct amount.</p>
                                <br>
                                 <h5 class="font-semibold text-gray-900 dark:text-white">Add New Bill Item</h5>
                                <p>You can also add new bill items and the system will automate the updates.</p>
                                
                            </div>
                            <div data-popper-arrow></div>
                        </div>
                  </div>

                </div>
                <div class="grid grid-cols-1 md:grid-cols-5 gap-8 md:max-h-64 md:overflow-y-scroll">
                  <div class="flex flex-col">
                      <label for="house_capacity" class="block mb-2 font-bold text-2xl text-gray-900 dark:text-white hidden-xs">Date</label>

                      <div class="relative max-w-full">
                        <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
                           <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                              <path d="M20 4a2 2 0 0 0-2-2h-2V1a1 1 0 0 0-2 0v1h-3V1a1 1 0 0 0-2 0v1H6V1a1 1 0 0 0-2 0v1H2a2 2 0 0 0-2 2v2h20V4ZM0 18a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V8H0v10Zm5-8h10a1 1 0 0 1 0 2H5a1 1 0 0 1 0-2Z"/>
                            </svg>
                        </div>
                        <input id="date_mass" data-format="dd-mm-yyyy" value="<?=date('d-m-Y')?>" data-message-required="<?php echo get_phrase('value_required');?>" placeholder="Date" name="date" type="text" class="datepicker bg-gray-50 border border-gray-300 text-gray-900 text-xl rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full h-20 ps-10 p-2.5  dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500 font-bold" placeholder="Select date commissioned">
                      </div>
                  </div>
                
                  <div class="flex flex-col" id="term_holder">
                      <label for="term" class="block mb-2 font-bold text-2xl text-gray-900 dark:text-white hidden-xs">Term</label>
                      <select id="term_mass" name="term" data-validate="required" data-message-required="Please select term before proceeding" class="bg-gray-50 border border-gray-300 text-gray-900 rounded-lg focus:ring-primary-500 focus:border-primary-500 text-xl font-bold block max-w-full h-20 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500" onchange="toggle_students_category()" required="required">
                          
                          <?php $running_term = $this->db->get_where('settings' , array('type'=>'running_term'))->row()->description;?>
                            <option value=""><?php echo get_phrase('select_term');?></option>
                            <?php for($i = 1; $i <= 3; $i++):?>
                                <option value="<?php echo $i;?>"
                                  <?php //if($running_term == $i) echo 'selected';?>>
                                    <?php echo $i;?>
                                </option>
                            <?php endfor;?>
                      </select>
                  </div>

                  <div class="flex flex-col">
                    <label for="year" class="block mb-2 font-bold text-2xl text-gray-900 dark:text-white hidden-xs">Year</label>
                    <select id="year_mass" name="year" data-message-required="<?php echo get_phrase('value_required');?>" class="select2 bg-gray-50 border text-xl border-gray-300 text-gray-900 rounded-lg focus:ring-primary-500  text-xl font-bold focus:border-primary-500 block max-w-full h-20 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500" onchange="toggle_students_category()" required="required">
                        <?php $running_year = $this->db->get_where('settings' , array('type'=>'running_year'))->row()->description;?>
                        <option value="" disabled="true"><?php echo get_phrase('select_year');?></option>
                        <?php
                            echo populate_academic_year('yes');
                          ?>
                    </select>
                  </div>

                  <div class="flex flex-col">
                    <label for="year" class="block mb-2 font-bold text-2xl text-gray-900 dark:text-white hidden-xs">Students Category</label>
                    <select id="students_category" name="students_category" data-message-required="<?php echo get_phrase('value_required');?>" class="select2 bg-gray-50 border text-xl border-gray-300 text-gray-900 rounded-lg focus:ring-primary-500  text-xl font-bold focus:border-primary-500 block max-w-full h-20 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500" onchange="toggle_students_category()">
                        <option value="all"><?php echo get_phrase('All Students');?></option>
                        <?php
                          if($boarding_system == 'yes'):
                        ?>
                        <option value="boarding"><?php echo get_phrase('All Boarding Students');?></option>
                        <option value="day"><?php echo get_phrase('All Day Students');?></option>
                        <?php
                          endif;
                        ?>
                        <option value="class" selected><?php echo get_phrase('By Class');?></option>
                       
                    </select>
                  </div>


                  <div class="flex flex-col" id="mass_class_holder">
                    <label for="year" class="block mb-2 font-bold text-2xl text-gray-900 dark:text-white hidden-xs">Class</label>
                    <select name="class_id[]" id="class_id2" multiple data-message-required="<?php echo get_phrase('value_required');?>" class="select2 bg-gray-50 border text-xl border-gray-300 text-gray-900 rounded-lg focus:ring-primary-500  text-xl font-bold focus:border-primary-500 block max-w-full h-20 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500 class_id2" onchange="get_class_students_mass($(this).val());  toggle_clone_button($(this).val());" placeholder="Select Classes" required="required">
                        <?php

                            getFullClassList();
                        ?>
                          
                      </select>
                  </div>
                </div>

                  <hr>
                  <!-- View Fees Structure Button - Hidden: Requires bill_item_history data -->
                  <!--
                  <div class="flex gap-5 justify-end items-center mt-5 mb-3">
                    <button type="button" onclick="viewFeesStructure()" class="bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white font-bold text-xl rounded-lg px-8 py-4 shadow-lg hover:shadow-xl transform hover:scale-105 transition-all duration-200 flex items-center gap-3">
                      <i class="fa fa-list-alt"></i> VIEW FEES STRUCTURE
                    </button>
                  </div>
                  -->

                  <!-- finding out if this bill should be added to the selected class category -->
                  <div class="flex gap-5 justify-end items-center mt-5 mb-5 p-5">
                    
                    <label for="add_to_class_bill" id="add_to_class_bill_label" class="control-label hidden-xs text-lg md:text-2xl cursor-pointer">Add this to selected class category bills?</label>  

                    <div class="switch-button pull-right showcase-switch-button text-lg md:text-xl">
                      <input type="checkbox" checked value="1"  name="add_to_class_bill" id="add_to_class_bill" onchange="updateBillAddition()">

                      <label for="add_to_class_bill"></label>
                    </div>
                    
                  </div>

                  <!-- Pre holder if sample is present ignore-->

                  <div id="invoice_items_holder_generated" class="p-5"></div><!--End-->
   

                  <div id="invoice_items_holder" style="display: none;" class="p-5">
                    <div class="flex gap-5 mb-5" id="16484_1565043896">
                      <div class="w-full">

                            <?php
                              $bill_items = $this->db->get('bill_item');

                              if($bill_items->num_rows() > 0) {?>

                                <select id="16484_1565043896_title" name="16484_1565043896_title" data-validate="required" data-message-required="<?php echo get_phrase('value_required');?>" class="select2 bg-gray-50 border border-gray-300 text-gray-900 rounded-lg text-xl focus:ring-primary-500 focus:border-primary-500 block w-full h-16 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500" onchange="getItemDetails($(this).attr('id'), $(this).val())">

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
                      <div class="w-full">
                          <input type="text" name="16484_1565043896_category" id="16484_1565043896_category" class="bg-gray-200 border border-gray-300 text-gray-500 rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full h-16 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white text-xl dark:focus:ring-primary-500 dark:focus:border-primary-500 cursor-not-allowed" placeholder="Bill item category"  readonly="readonly">
                      </div>
                      <div class="w-full">
                          <input type="text" name="16484_1565043896_description" id="16484_1565043896_description" class="bg-gray-200 border border-gray-300 text-gray-500 rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full h-16 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white text-xl dark:focus:ring-primary-500 dark:focus:border-primary-500 cursor-not-allowed" placeholder="Bill item description"  readonly="readonly">
                      </div>

                      <div class="w-full">
                        <input type="text" id="16484_1565043896_amount"  name="16484_1565043896_amount" data-validate="required" data-message-required="<?php echo get_phrase('value_required');?>" class="bg-gray-50 border border-gray-300 text-gray-900 rounded-lg focus:ring-primary-600 total_amount focus:border-primary-600 block w-full h-16 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white text-xl dark:focus:ring-primary-500 dark:focus:border-primary-500" placeholder="Bill item amount">
                      </div>

                      <div class="flex gap-3 w-full">
                        <a href="javascript:void(0);" disabled onclick="add_invoice_item('16484_1565043896', '')" id="16484_1565043896_add_btn" class="btn btn-info btn-sm"><i class="fa fa-plus"></i></a>
                      </div>
                    </div> 

                    <!-- submit button -->
                    <div class="flex justify-end">
                        <?php

                          echo get_button('submit', 'CREATE INVOICE', 'submit2 font-semibold');
                        ?>
                    </div>
                  </div>
                  <!-- Ignore ends here -->
              

                  <!-- Pre holder if sample is present-->

                  <div id="invoice_items_holder_generated2" style="display: none;" class="p-5 overflow-y-auto max-h-[40vh]"></div><!--End-->

                  <div class="grid grid-cols-1 md:grid-cols-3 gap-12 md:gap-4">

                    <!-- Students holder -->
                    <div class="flex flex-col border-2 shadow-xl border-l-green-300 rounded-lg p-4">
                      <div class="flex gap-2 items-center mb-10 p-3">
                        <div class="p-4 font-bold text-xl w-fit max-w-fit hover:ring-1 hover:ring-green-400 shadow-xl text-gray-500 rounded-md">
                          STUDENTS LIST
                        </div>
                        <div class="justify-self-end">
                          <button type="button" onClick="select()" class="text-green-700 hover:text-white border border-green-700 hover:bg-green-800 focus:ring-4 focus:outline-none focus:ring-green-300 font-medium rounded-lg text-lg px-5 py-2.5 text-center me-2 mb-2 dark:border-green-500 dark:text-green-500 dark:hover:text-white dark:hover:bg-green-600 dark:focus:ring-green-800">Select All</button>
                        </div>
                        <div class="place-self-end">
                          <button type="button" onClick="unselect()" class="text-red-700 hover:text-white border border-red-700 hover:bg-red-800 focus:ring-4 focus:outline-none focus:ring-red-300 font-medium rounded-lg text-lg px-5 py-2.5 text-center me-2 mb-2 dark:border-red-500 dark:text-red-500 dark:hover:text-white dark:hover:bg-red-600 dark:focus:ring-red-900">Deselect All</button>
                        </div>
                      </div>
                      <div class="flex justify-center w-full">
                        <input type="text" class="bg-gray-50 border border-gray-300 text-gray-900 rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white text-xl dark:focus:ring-primary-500 dark:focus:border-primary-500 h-16" name="search-student" id="search-student" placeholder="Search students" onkeyup="filterStudents()">
                      </div>
                      <div id="student_selection_holder_mass" class="max-h-[40vh] p-5 overflow-y-auto"></div>
                    </div>

                    <!-- Bill items holder -->
                    <div class="flex flex-col gap-20 md:gap-8 md:col-span-2 border-2 p-3 shadow-xl border-l-green-300 rounded-lg">
                      <div class="flex gap-5 justify-between">
                        <div class="p-4 font-bold text-xl text-gray-500 w-fit max-w-fit hover:ring-1 hover:ring-green-400 shadow-xl rounded-md mb-10">INVOICE ITEMS</div>
                        <div class="p-4"><a href="#" class="p-4 bg-blue-500 hover:bg-blue-600 cursor-pointer font-bold rounded-lg text-white transition-colors duration-200" onclick="toggle_clone_previous_bill($(this).text())" id="clone_button">CLONE PREVIOUS</a></div>
                      </div>

                      <!-- clone or unclone holder start -->
                
                      <div class="flex flex-col gap-5 w-full" id="clone_unclone_holder" style="display: none;">
                        <div class="bg-red-100 p-3 border-2 border-red-400 rounded-lg mb-3 w-full">
                          <h2 class="mb-2 text-2xl font-semibold text-gray-500 dark:text-white italic text-justify"><i class="fa-solid fa-info-circle"></i> Please select the period's bill you want to clone. System will automatically recreate the invoices of the selected periods below for the term and year currently selected on the first row above. Please make sure your selection above is correct before proceeding.</h2>
                        </div>
                        <div class="flex gap-3 justify-end items-center md:mt-5 mb-8 md:mb-4 w-full">

                          <label for="term_clone" class="block mb-2 font-bold text-xl md:text-2xl text-gray-900 dark:text-white hidden-xs">Term</label>
                          <select id="term_clone" name="term_clone" data-message-required="<?php echo get_phrase('value_required');?>" class="bg-gray-50 border border-gray-300 text-gray-900 rounded-lg focus:ring-primary-500 focus:border-primary-500 text-xl font-bold block w-full max-w-full h-20 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500">
                              
                              <?php $running_term = $this->db->get_where('settings' , array('type'=>'running_term'))->row()->description;?>
                                <option value="" disabled="true"><?php echo get_phrase('select_term');?></option>
                                <?php for($i = 1; $i <= 3; $i++):?>
                                    <option value="<?php echo $i;?>"
                                      <?php if($running_term == $i) echo 'selected';?>>
                                        <?php echo $i;?>
                                    </option>
                                <?php endfor;?>
                          </select>

                          <label for="year_clone" class="block mb-2 font-bold text-xl md:text-2xl text-gray-900 dark:text-white hidden-xs">Year</label>
                          <select id="year_clone" name="year_clone" data-message-required="<?php echo get_phrase('value_required');?>" class="select2 bg-gray-50 border text-xl border-gray-300 text-gray-900 rounded-lg focus:ring-primary-500  text-xl font-bold focus:border-primary-500 block w-full max-w-full h-20 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500">
                              <?php $running_year = $this->db->get_where('settings' , array('type'=>'running_year'))->row()->description;?>
                              <option value="" disabled="true"><?php echo get_phrase('select_year');?></option>
                              <?php
                                  echo populate_academic_year('yes');
                                ?>
                          </select>

                          <!-- button -->
                          <div class="flex justify-end w-full max-w-full">
                              <?php

                                echo get_button('button', 'CLONE INVOICES', 'submit2 font-semibold', 'actual_clone_button');
                              ?>
                          </div>
                        </div>
                      </div>
                      <!-- clone or unclone holder ends -->

                      <div id="invoice_items_holder2" class="p-5 overflow-x-auto overflow-y-auto max-h-[40vh]">
                          <div class="flex gap-5 mb-5 row" id="177_1565053816">
                            <div class="w-full">

                                        <select id="177_1565053816_title" name="177_1565053816_title" data-validate="required" data-message-required="<?php echo get_phrase('value_required');?>" class="select2 bg-gray-50 border border-gray-300 text-gray-900 rounded-lg focus:ring-primary-500 text-xl focus:border-primary-500 block w-full h-16 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500" required="true" onchange="getItemDetails($(this).attr('id'), $(this).val())">

                                            <option value="">Select class(es) first</option>

                                            </select>

                              </div>
                              <div class="w-full hidden-xs">
                                  <input type="text" name="177_1565053816_category" id="177_1565053816_category" class="bg-gray-200 border border-gray-300 text-gray-500 rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full h-16 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white text-xl dark:focus:ring-primary-500 dark:focus:border-primary-500 cursor-not-allowed" placeholder="Bill item category"  readonly="readonly">
                              </div>
                              <div class="w-full hidden-xs">
                                  <input type="text" name="177_1565053816_description" id="177_1565053816_description" class="bg-gray-200 border border-gray-300 text-gray-500 rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full h-16 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white text-xl dark:focus:ring-primary-500 dark:focus:border-primary-500 cursor-not-allowed" placeholder="Bill item description"  readonly="readonly">
                              </div>

                              <div class="w-96 max-w-96">
                                <input type="text" id="177_1565053816_amount"  name="177_1565053816_amount" data-validate="required" data-message-required="<?php echo get_phrase('value_required');?>" class="bg-gray-50 border border-gray-300 text-gray-900 rounded-lg focus:ring-primary-600 total_amount focus:border-primary-600 block w-full h-16 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white text-xl dark:focus:ring-primary-500 dark:focus:border-primary-500" placeholder="Amount" required="true">
                              </div>

                              <div class="flex gap-3 w-fit max-w-fit">
                                <a href="javascript:void(0);" disabled onclick="add_invoice_item2('177_1565053816', '')" id="177_1565053816_add_btn" class="btn btn-info btn-sm"><i class="fa fa-plus"></i></a>

                                <a href="javascript:(void);" disabled onclick="return" class="btn btn-danger btn-sm"><i class="fa fa-remove"></i></a>

                              </div>
                          </div> 
                        
                      </div> <!-- End of holder-->

                      <!-- submit button -->
                      <div class="flex justify-end mt-7" id="mass_create_button_holder">
                          <?php

                            echo get_button('submit', 'CREATE INVOICES', 'submit2 font-semibold');
                          ?>
                      </div>
                    </div>
                  </div>

            </div>     
          </div>
          </div>
            <?php echo form_close();?>
          </div>    
        </div><!-- create mass invoice ends -->

        <!-- View invoices -->
        <!-- <div class="tab-pane" id="view_invoices">

        
          <div class="row">
          <div class="col-md-12 col-sm-12">
            <div class="panel panel-info panel-shadow p-5" data-collapsed="0">
              <div class="panel-heading">
                <div class="flex panel-title">VIEW STUDENTS INVOICES</div>
            </div>
            <div class="panel-body">
                <div class="flex justify-center items-center">
                  <div class="flex items-center gap-3 alert alert-success alert-dismissible max-w-fit p-4 align-middle" id="view_invoice_success_note" style="display: none;" role="alert" align="center"> 
                        <strong>Updated successfully!</strong>
                        <button type="button" class="close ring-2 ring-green-700 rounded-full w-16 h-16" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span>
                        </button>
                        
                  </div>

                  <div class="flex items-center gap-3 alert alert-danger alert-dismissible max-w-fit p-4 align-middle" id="view_invoice_error_note" style="display: none;" role="alert" align="center"> 
                        <strong>Update failed</strong>
                        <button type="button" class="close ring-2 ring-red-700 rounded-full w-16 h-16" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span>
                        </button>
                        
                  </div>
                </div>

                <div class="flex flex-col justify-center p-5 bg-yellow-100 rounded-lg shadow-md mb-10">
                  <div>
                    <h2 class="mb-2 text-2xl font-semibold text-gray-900 dark:text-white"><i class="fa-solid fa-info-circle"></i> Here, you can do the following:</h2>
                  </div>
                  <div>
                    <ul class="max-w-full space-y-1 text-gray-500 list-inside dark:text-gray-400">

                      <li class="flex items-center text-xl font-semibold">
                          <svg class="w-6 h-6 me-2 text-green-500 dark:text-green-400 shrink-0" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                              <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5Zm3.707 8.207-4 4a1 1 0 0 1-1.414 0l-2-2a1 1 0 0 1 1.414-1.414L9 10.586l3.293-3.293a1 1 0 0 1 1.414 1.414Z"/>
                           </svg>

                           <span class="flex items-center text-xl text-gray-500 dark:text-gray-400">Update Individual Invoices: <button data-popover-target="popover-description-update" data-popover-placement="bottom-end" type="button"> <span class="ml-5">See how</span> <i class="fa-solid fa-question-circle text-2xl"></i></button></span>
                          

                          
                      </li>

                    </ul>

                    <div data-popover id="popover-description-update" role="tooltip" class="absolute z-10 invisible inline-block text-lg text-gray-500 transition-opacity duration-300 bg-white border border-gray-400 rounded-lg shadow-md opacity-0 w-72 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-400">
                            <div class="p-3 space-y-2">
                                <h5 class="font-semibold text-gray-900 dark:text-white">Update Bill Item</h5>
                                <p>Set the desired term and year invoice you want to edit. Then search by student's name and choose the invoice code you want. Input the correct amount for each item.</p>
                                <br>
                                 <h5 class="font-semibold text-gray-900 dark:text-white">Add New Bill Item</h5>
                                <p>You can also add new bill items and the system will automate the updates.</p><br>
                                <h5 class="font-semibold text-gray-900 dark:text-white">Remove Bill Item</h5>
                                <p>You can remove bill items from the previous list.</p>
                                
                            </div>
                            <div data-popper-arrow></div>
                        </div>
                  </div>

                </div>
                <div class="grid grid-cols-1 md:grid-cols-5 gap-8">
                  <div class="flex flex-col" id="term_holder">
                      <label for="term_view" class="block mb-2 font-bold text-2xl text-gray-900 dark:text-white hidden-xs uppercase">Invoice Term</label>
                      <select id="term_view" name="term" data-message-required="<?php echo get_phrase('value_required');?>" class="select2 bg-gray-50 border text-xl border-gray-300 text-gray-900 rounded-lg focus:ring-primary-500  text-xl font-bold focus:border-primary-500 block max-w-full h-20 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500">
                          
                          <?php $running_term = $this->db->get_where('settings' , array('type'=>'running_term'))->row()->description;?>
                            <option value="" disabled="true"><?php echo get_phrase('select_term');?></option>
                            <?php for($i = 1; $i <= 3; $i++):?>
                                <option value="<?php echo $i;?>"
                                  <?php if($running_term == $i) echo 'selected';?>>
                                    <?php echo $i;?>
                                </option>
                            <?php endfor;?>
                      </select>
                  </div>

                  <div class="flex flex-col">
                    <label for="year_view" class="block mb-2 font-bold text-2xl text-gray-900 dark:text-white hidden-xs uppercase">Invoice Year</label>
                    <select id="year_view" name="year" data-message-required="<?php echo get_phrase('value_required');?>" class="select2 bg-gray-50 border text-xl border-gray-300 text-gray-900 rounded-lg focus:ring-primary-500  text-xl font-bold focus:border-primary-500 block max-w-full h-20 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500">
                        <?php $running_year = $this->db->get_where('settings' , array('type'=>'running_year'))->row()->description;?>
                        <option value="" disabled="true"><?php echo get_phrase('select_year');?></option>
                        <?php
                            echo populate_academic_year('yes');
                          ?>
                    </select>
                  </div>

                  <div class="mt-10">
                      <?php

                        echo get_button('button', 'LOAD STUDENTS', 'font-semibold h-20 p-2.5', 'load_students', 'bg-green-600', 'bg-green-800', 'loadBilledStudents()');
                      ?>
                  </div>

                  <div class="flex flex-col">
                    <label for="student_id" class="block mb-2 font-bold text-2xl text-gray-900 dark:text-white hidden-xs uppercase">Search By Student Name</label>
                    <select name="student_id" id="student_id" data-message-required="<?php echo get_phrase('value_required');?>" class="select2 bg-gray-50 border text-2xl border-gray-300 text-gray-900 rounded-lg focus:ring-primary-500  text-xl font-bold focus:border-primary-500 block max-w-full h-20 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500" onchange="get_student_invoice_codes($(this).val());" placeholder="Search student by name" required="required">
                        
                      </select>
                  </div>

                  <div class="flex flex-col">
                    <label for="student_invoice_codes" class="block mb-2 font-bold text-2xl text-gray-900 dark:text-white hidden-xs uppercase">Invoice Codes</label>
                    <select name="student_invoice_codes" id="student_invoice_codes" data-message-required="<?php echo get_phrase('value_required');?>" class="select2 bg-gray-50 border text-2xl border-gray-300 text-gray-900 rounded-lg focus:ring-primary-500  text-xl font-bold focus:border-primary-500 block max-w-full h-20 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500" onchange="get_student_invoice_bill();" placeholder="Search student invoice code" required="required">
                        
                      </select>
                  </div>
                  <div class="mt-10 flex gap-3">
                      <button type="button" onclick="viewSelectedInvoice()" class="inline-flex items-center px-5 py-2.5 text-lg font-bold text-center text-white bg-blue-600 rounded-lg hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300" id="view_invoice_button" disabled>
                          <i class="fa fa-file-invoice mr-2"></i> <?php echo get_phrase('view_invoice'); ?>
                      </button>
                      <button type="button" onclick="emailSelectedInvoice()" class="inline-flex items-center px-5 py-2.5 text-lg font-bold text-center text-white bg-green-600 rounded-lg hover:bg-green-800 focus:ring-4 focus:outline-none focus:ring-green-300" id="email_invoice_button" disabled>
                          <i class="fa fa-envelope mr-2"></i> <?php echo get_phrase('email_invoice'); ?>
                      </button>
                  </div>
                </div>

                  <hr> -->
              

                  <!-- Pre holder if sample is present-->
                  <!-- <div class="p-15 overflow-y-auto"> -->
                    
                    <?php
                      //echo form_open(site_url('admin/mass_invoice_create/update/') , array('class' => 'form-horizontal form-groups-bordered validate', 'id'=> 'bill_list_form' ,'target'=>'_top'));?>


                      <!-- <div id="billed_student_invoices_holder" class="p-10"></div> Holder -->
                      
                      <!-- Discount Display -->
                      <!-- <div id="invoice_discount_display" class="p-5 mb-5" style="display:none;"></div> -->
                      
                      <!-- submit button -->
                      <!-- <div class="flex justify-end gap-3 mt-7" id="">
                          <button type="button" onclick="applyDiscount()" class="inline-flex items-center px-5 py-2.5 text-lg font-bold text-center text-white bg-purple-600 rounded-lg hover:bg-purple-800 focus:ring-4 focus:outline-none focus:ring-purple-300 dark:bg-purple-600 dark:hover:bg-purple-700 dark:focus:ring-purple-800" id="apply_discount_button">
                              <i class="fa fa-tag mr-2"></i> <?php //echo get_phrase('apply_discount'); ?>
                          </button>
                          <?php
                            //echo get_button('submit', 'UPDATE INVOICES', 'submit2 font-semibold', 'update_invoice_button');
                          ?>
                      </div>

                    </form>
                  </div>

            </div>     
          </div>
          </div>
          </div> 

        </div> -->
        <!-- view invoices ends -->

        <!-- Manage Bulk Invoices -->
        <div class="tab-pane" id="manage_bulk_invoices">
          <div class="row">
            <div class="col-md-12 col-sm-12">
              <div class="panel panel-info panel-shadow p-5" data-collapsed="0">
                <div class="panel-heading">
                  <div class="panel-title uppercase flex items-center justify-between">
                    <div class="flex items-center gap-3">
                      <i class="fa-solid fa-file-invoice-dollar text-3xl text-blue-600"></i>
                      <span class="text-2xl font-bold">BULK INVOICE MANAGEMENT</span>
                    </div>
                    <div class="flex gap-3">
                      <span class="px-4 py-2 bg-blue-100 text-blue-800 rounded-lg font-semibold text-lg">
                        <i class="fa fa-info-circle"></i> Professional Financial Control
                      </span>
                    </div>
                  </div>
                </div>
                <div class="panel-body">
                  <!-- Info Banner -->
                  <div class="bg-gradient-to-r from-blue-50 to-indigo-50 border-l-4 border-blue-500 p-6 mb-8 rounded-lg shadow-sm">
                    <div class="flex items-start">
                      <i class="fa fa-lightbulb text-3xl text-blue-600 mr-4 mt-1"></i>
                      <div>
                        <h3 class="text-xl font-bold text-gray-800 mb-2">Advanced Invoice Management</h3>
                        <ul class="text-lg text-gray-700 space-y-1">
                          <li><i class="fa fa-check-circle text-green-600"></i> View bulk invoices by class, boarding status, or entire school</li>
                          <li><i class="fa fa-check-circle text-green-600"></i> Edit invoice amounts with approval workflow</li>
                          <li><i class="fa fa-check-circle text-green-600"></i> Delete invoices with proper authorization</li>
                          <li><i class="fa fa-check-circle text-green-600"></i> Real-time financial analytics and reporting</li>
                        </ul>
                      </div>
                    </div>
                  </div>

                  <!-- Advanced Filters -->
                  <div class="bg-white rounded-lg shadow-md p-6 mb-8">
                    <h4 class="text-xl font-bold text-gray-800 mb-6 flex items-center">
                      <i class="fa fa-filter text-blue-600 mr-3"></i> Smart Filters
                    </h4>
                    <div class="grid grid-cols-1 md:grid-cols-5 gap-6">
                      <div class="flex flex-col">
                        <label class="block mb-2 font-bold text-lg text-gray-700 uppercase">Academic Term</label>
                        <select id="bulk_term" class="select2 bg-gray-50 border-2 border-gray-300 text-gray-900 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-xl font-semibold block w-full h-16 p-3 transition-all">
                          <?php $running_term = $this->db->get_where('settings', array('type'=>'running_term'))->row()->description;?>
                          <option value="">All Terms</option>
                          <?php for($i = 1; $i <= 3; $i++):?>
                            <option value="<?php echo $i;?>" <?php if($running_term == $i) echo 'selected';?>>Term <?php echo $i;?></option>
                          <?php endfor;?>
                        </select>
                      </div>
                      <div class="flex flex-col">
                        <label class="block mb-2 font-bold text-lg text-gray-700 uppercase">Academic Year</label>
                        <select id="bulk_year" class="select2 bg-gray-50 border-2 border-gray-300 text-gray-900 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-xl font-semibold block w-full h-16 p-3 transition-all">
                          <option value="">All Years</option>
                          <?php echo populate_academic_year('yes');?>
                        </select>
                      </div>
                      <div class="flex flex-col">
                        <label class="block mb-2 font-bold text-lg text-gray-700 uppercase">Invoice Status</label>
                        <select id="bulk_status" class="select2 bg-gray-50 border-2 border-gray-300 text-gray-900 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-xl font-semibold block w-full h-16 p-3 transition-all">
                          <option value="">All Statuses</option>
                          <option value="paid"><i class="fa fa-check-circle"></i> Paid</option>
                          <option value="partial"><i class="fa fa-clock"></i> Partial</option>
                          <option value="unpaid"><i class="fa fa-exclamation-circle"></i> Unpaid</option>
                        </select>
                      </div>
                      <div class="flex flex-col">
                        <label class="block mb-2 font-bold text-lg text-gray-700 uppercase">Student Category</label>
                        <select id="bulk_filter" onchange="toggleBulkClassSelector()" class="select2 bg-gray-50 border-2 border-gray-300 text-gray-900 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-xl font-semibold block w-full h-16 p-3 transition-all">
                          <option value="all"><i class="fa fa-users"></i> All Students</option>
                          <?php if($boarding_system == 'yes'):?>
                          <option value="boarding"><i class="fa fa-bed"></i> Boarding Students</option>
                          <option value="day"><i class="fa fa-sun"></i> Day Students</option>
                          <?php endif;?>
                          <option value="class"><i class="fa fa-school"></i> By Class</option>
                        </select>
                      </div>
                      <div class="flex flex-col" id="bulk_class_selector" style="display:none;">
                        <label class="block mb-2 font-bold text-lg text-gray-700 uppercase">Select Class</label>
                        <select id="bulk_class" class="select2 bg-gray-50 border-2 border-gray-300 text-gray-900 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-xl font-semibold block w-full h-16 p-3 transition-all">
                          <option value="">Select Class</option>
                          <?php getFullClassList();?>
                        </select>
                      </div>
                    </div>
                    <div class="flex justify-end mt-4">
                      <button type="button" onclick="loadBulkInvoices()" id="load_bulk_invoices" class="bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white font-bold text-xl rounded-lg h-16 px-8 shadow-lg hover:shadow-xl transform hover:scale-105 transition-all duration-200 flex items-center justify-center gap-3">
                        <i class="fa fa-sync-alt"></i> LOAD INVOICES
                      </button>
                    </div>
                  </div>

                  <!-- Statistics Cards -->
                  <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8" id="bulk_stats" style="display:none;">
                    <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg shadow-lg p-6 text-white">
                      <div class="flex items-center justify-between">
                        <div>
                          <p class="text-sm font-semibold opacity-90 uppercase">Invoice Count</p>
                          <p class="text-4xl font-bold mt-2" id="stat_total">0</p>
                          <p class="text-xs opacity-75 mt-1">Unique Invoice Codes</p>
                        </div>
                        <i class="fa fa-file-invoice text-5xl opacity-30"></i>
                      </div>
                    </div>
                    <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-lg shadow-lg p-6 text-white">
                      <div class="flex items-center justify-between">
                        <div>
                          <p class="text-sm font-semibold opacity-90 uppercase">Total Bill</p>
                          <p class="text-4xl font-bold mt-2" id="stat_amount"><?=$currency;?>0</p>
                          <p class="text-xs opacity-75 mt-1">Total Amount Invoiced</p>
                        </div>
                        <i class="fa fa-money-bill-wave text-5xl opacity-30"></i>
                      </div>
                    </div>
                    <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-lg shadow-lg p-6 text-white">
                      <div class="flex items-center justify-between">
                        <div>
                          <p class="text-sm font-semibold opacity-90 uppercase">Students</p>
                          <p class="text-4xl font-bold mt-2" id="stat_students">0</p>
                        </div>
                        <i class="fa fa-users text-5xl opacity-30"></i>
                      </div>
                    </div>
                    <div class="bg-gradient-to-br from-red-500 to-red-600 rounded-lg shadow-lg p-6 text-white">
                      <div class="flex items-center justify-between">
                        <div>
                          <p class="text-sm font-semibold opacity-90 uppercase">Total Receivables</p>
                          <p class="text-4xl font-bold mt-2" id="stat_receivables"><?=$currency;?>0</p>
                          <p class="text-xs opacity-75 mt-1">Outstanding Balance</p>
                        </div>
                        <i class="fa fa-hand-holding-usd text-5xl opacity-30"></i>
                      </div>
                    </div>
                  </div>

                  <!-- Quick Search with Action Buttons -->
                  <div class="bg-gradient-to-r from-purple-50 to-blue-50 rounded-lg shadow-md p-6 mb-6">
                    <label class="block mb-3 font-bold text-lg text-gray-700 uppercase">Quick Search</label>
                    <div class="flex flex-wrap gap-4 items-center">
                      <!-- Action Buttons on the Left (First Row) -->
                      <div class="flex gap-3 flex-wrap">
                        <button type="button" onclick="printBulkInvoices()" class="bg-gradient-to-r from-purple-600 to-purple-700 hover:from-purple-700 hover:to-purple-800 text-white font-bold text-base rounded-lg px-6 py-3.5 shadow-lg hover:shadow-xl transform hover:scale-105 transition-all duration-200 flex items-center gap-2 whitespace-nowrap">
                          <i class="fa fa-print"></i> PRINT BULK BILLS
                        </button>
                        <button type="button" onclick="openRecentReceiptsModal()" class="bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white font-bold text-base rounded-lg px-6 py-3.5 shadow-lg hover:shadow-xl transform hover:scale-105 transition-all duration-200 flex items-center gap-2 whitespace-nowrap">
                          <i class="fa fa-receipt"></i> RECENT RECEIPTS
                        </button>
                      </div>
                      
                      <!-- Search Field and Button (Second Row on Mobile, Same Row on Desktop) -->
                      <div class="flex gap-3 w-full lg:flex-1 lg:w-auto">
                        <div class="flex-1" style="position: relative;">
                          <input type="text" id="global_search" placeholder="Search by Student Name, Student Code, or Invoice Code..." class="bg-white border-2 border-gray-300 text-gray-900 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 text-lg font-semibold block w-full h-14 p-4 transition-all" autocomplete="off">
                          <div id="search_suggestions" style="display: none; position: absolute; top: 100%; left: 0; right: 0; background: white; border: 2px solid #667eea; border-top: none; border-radius: 0 0 12px 12px; box-shadow: 0 8px 24px rgba(102, 126, 234, 0.2); max-height: 400px; overflow-y: auto; z-index: 1000; margin-top: -8px;"></div>
                        </div>
                        
                        <!-- Search Button -->
                        <button type="button" onclick="globalSearch()" class="bg-gradient-to-r from-purple-600 to-purple-700 hover:from-purple-700 hover:to-purple-800 text-white font-bold text-lg rounded-lg h-14 px-8 shadow-lg hover:shadow-xl transform hover:scale-105 transition-all duration-200 flex items-center gap-2 whitespace-nowrap">
                          <i class="fa fa-search"></i> SEARCH
                        </button>
                      </div>
                    </div>
                  </div>

                  <!-- DataTable Container -->
                  <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="overflow-x-auto" id="bulk_invoices_table_wrapper">
                      <table id="bulk_invoices_datatable" class="display nowrap" style="width:100%">
                        <thead class="bg-gradient-to-r from-gray-700 to-gray-800 text-white">
                          <tr>
                            <th class="text-white text-left p-4"><input type="checkbox" id="select_all_bulk" class="w-5 h-5"></th>
                            <th class="text-white text-left p-4">Invoice #</th>
                            <th class="text-white text-left p-4">Student</th>
                            <th class="text-white text-left p-4">Class</th>
                            <th class="text-white text-left p-4">Status</th>
                            <th class="text-white text-right p-4">Amount</th>
                            <th class="text-white text-right p-4">Paid</th>
                            <th class="text-white text-right p-4">Due</th>
                            <th class="text-white text-left p-4">Created</th>
                            <th class="text-white text-center p-4">Actions</th>
                          </tr>
                        </thead>
                        <tbody></tbody>
                      </table>
                    </div>
                  </div>

                  <!-- Bulk Actions Bar - Sticky -->
                  <div class="bg-white border-t-4 border-blue-600 shadow-2xl p-6" id="bulk_actions_bar" style="display:none; position:fixed; bottom:0; left:0; right:0; z-index:1000;">
                    <div class="container mx-auto">
                      <div class="flex items-center justify-between">
                        <div class="flex items-center gap-4">
                          <span class="text-xl font-bold text-gray-800">
                            <i class="fa fa-check-square text-blue-600"></i> <span id="selected_count">0</span> invoice(s) selected
                          </span>
                        </div>
                        <div class="flex gap-3">
                          <button type="button" onclick="printSelectedInvoices()" class="bg-purple-600 hover:bg-purple-700 text-white font-bold text-lg rounded-lg px-6 py-3 shadow-md hover:shadow-lg transition-all flex items-center gap-2">
                            <i class="fa fa-print"></i> Print Selected
                          </button>
                          <button type="button" onclick="bulkEmailInvoices()" class="bg-green-600 hover:bg-green-700 text-white font-bold text-lg rounded-lg px-6 py-3 shadow-md hover:shadow-lg transition-all flex items-center gap-2">
                            <i class="fa fa-envelope"></i> Email
                          </button>
                          <button type="button" onclick="bulkSmsInvoices()" class="bg-orange-600 hover:bg-orange-700 text-white font-bold text-lg rounded-lg px-6 py-3 shadow-md hover:shadow-lg transition-all flex items-center gap-2">
                            <i class="fa fa-mobile"></i> SMS
                          </button>
                          <button type="button" onclick="bulkModifyInvoices()" class="bg-orange-600 hover:bg-orange-700 text-white font-bold text-lg rounded-lg px-6 py-3 shadow-md hover:shadow-lg transition-all flex items-center gap-2" style="display: none;">
                            <i class="fa fa-edit"></i> Bulk Modify
                          </button>
                          <button type="button" onclick="exportSelectedInvoices()" class="bg-green-600 hover:bg-green-700 text-white font-bold text-lg rounded-lg px-6 py-3 shadow-md hover:shadow-lg transition-all flex items-center gap-2">
                            <i class="fa fa-file-excel"></i> Export
                          </button>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div><!-- manage bulk invoices ends -->

        <!-- Bulk Arrears Import -->
        <div class="tab-pane" id="bulk_arrears_import">
          <div class="row">
            <div class="col-md-12 col-sm-12">
              <div class="panel panel-info panel-shadow p-5" data-collapsed="0">
                <div class="panel-heading">
                  <div class="panel-title uppercase flex items-center justify-between">
                    <div class="flex items-center gap-3">
                      <i class="fa-solid fa-file-import text-3xl text-purple-600"></i>
                      <span class="text-2xl font-bold">BULK ARREARS IMPORT</span>
                    </div>
                    <div class="flex gap-3">
                      <span class="px-4 py-2 bg-purple-100 text-purple-800 rounded-lg font-semibold text-lg">
                        <i class="fa fa-magic"></i> Automated Invoice Creation
                      </span>
                    </div>
                  </div>
                </div>
                <div class="panel-body">
                  <div id="bulk_arrears_content"></div>
                </div>
              </div>
            </div>
          </div>
        </div><!-- bulk arrears import ends -->

        <!-- Modification Requests (Receipt & Invoice) -->
        <div class="tab-pane" id="receipt_modifications">
          <div class="row">
            <div class="col-md-12 col-sm-12">
              <div class="panel panel-info panel-shadow p-5" data-collapsed="0">
                <div class="panel-heading">
                  <div class="panel-title uppercase flex items-center justify-between">
                    <div class="flex items-center gap-3">
                      <i class="fa-solid fa-shield-alt text-3xl text-orange-600"></i>
                      <span class="text-2xl font-bold">MODIFICATION REQUESTS</span>
                    </div>
                    <div class="flex gap-3">
                      <span class="px-4 py-2 bg-orange-100 text-orange-800 rounded-lg font-semibold text-lg">
                        <i class="fa fa-lock"></i> Approval Required
                      </span>
                    </div>
                  </div>
                </div>
                <div class="panel-body">
                  <?php 
                  $requests = $this->db->get('receipt_modification_requests')->result_array();
                  $invoice_requests = $this->db->get('invoice_modification_requests')->result_array();
                  $page_data['requests'] = $requests;
                  $page_data['invoice_requests'] = $invoice_requests;
                  $this->load->view('backend/admin/receipt_invoice_modification_requests', $page_data); 
                  ?>
                </div>
              </div>
            </div>
          </div>
        </div><!-- modifications ends -->

        <!-- upload invoices -->
        <div class="tab-pane" id="upload_invoices">

          <!-- creation of single invoice -->
          <?php echo form_open(site_url('admin/uploadBillInvoices/import/') , array('class' => 'form-horizontal form-groups-bordered validate','target'=>'_top', 'id' => 'upload_invoices_form'));?>
            <div class="row">
              <div class="col-md-12 col-sm-12">
                  <div class="panel panel-info panel-shadow p-5" data-collapsed="0">
                    <div class="panel-heading">
                      <div class="panel-title"><?php echo get_phrase('upload bulk invoices');?></div>
                    </div>
                    <div class="panel-body">
                      
                        <div class="col-md-3 col-sm-3 col-xs-2"></div>
                          <div class="col-md-3 col-sm-3 col-xs-5">
                            <div class="form_group">
                              <label class="control-label" style="margin-bottom: 5px;"><?php echo get_phrase('class');?></label>
                              <select name="class_id" id="class_id" class="form-control select2" required
                                onchange="get_sections(this.value)"  data-validate="required"  data-message-required="<?php echo get_phrase('value_required');?>">
                                <option value=""><?php echo get_phrase('select_class');?></option>
                                <?php
                                    getFullClassList();
                                ?>
                              </select>
                            </div>
                          </div>
                          <div id="section_holder" class="col-md-3 col-sm-3 col-xs-3">
                            <label class="control-label" style="margin-bottom: 5px;"><?php echo get_phrase('section');?></label>
                            <select name="section_id" id="section_id" class="form-control select2">
                              <option value=""><?php echo get_phrase('select_class_first');?></option>
                            </select>
                          </div>
                          <div class="col-md-3 col-sm-3 col-xs-2"></div>
                        </div>
                        <div class="row">
                          <div class="col-md-3 col-sm-3 col-xs-2"></div>
                          <div class="col-md-6 col-sm-6 col-xs-6" style="padding: 5px 0 10px 0;">
                            <div class="col-md-4 col-sm-4 col-xs-4">
                              <button type="button" class="btn btn-primary pop-over" data-content="Click to generate the file. Fill it with the amount each student is owing and upload it back." data-placement="left" data-title="How to download and upload your csv file" name="generate_csv" id="generate_csv"><?php echo get_phrase('generate_').'CSV '.get_phrase('file'); ?></button>
                            </div>
                            <div class="col-md-4 col-sm-4 col-xs-4">
                            <input type="file" name="userfile" id="userfile" onchange="check_loaded_csvfile()" class="form-control inline btn btn-info" data-validate="required" data-message-required="<?php echo get_phrase('required'); ?>" accept="text/csv, .csv">
                            </div>

                            <div class="col-md-4 col-sm-4 col-xs-4">
                              <button type="submit" class="btn btn-success" name="import_csv" id="import_csv"><?php echo get_phrase('upload'); ?></button>
                            </div>
                          </div>
                          <div class="col-md-3 col-sm-3 col-xs-2"></div>
                    </div>
                  </div>
                </div>
            </div>
          <?php echo form_close();?>


          <!-- upload invoice-->
          <a href="" style="display: none;" id="bulk">Download</a>
        </div><!-- upload ends -->
      </div>
    </div>
  </div>

<!-- SheetJS Library for Excel Export -->
<script src="<?php echo base_url(); ?>assets/sheetjs-master/xlsx.full.min.js"></script>

<script type="text/javascript">

  let selected = []; //global
  let current_code = 0;
  let class_id = '';
  let item_ids = [];
  let single_bill_items_array = [];
  let mass_bill_items_array = [];
  let list_bill_items_array = [];
  let bill_list_item_ids = [];

  var class_selection = '';
  $(document).ready(function($) {
    /*disable update button*/
    $('#update_invoice_button').attr('disabled', 'disabled');
    $('#update_invoice_button').addClass('cursor-not-allowed');

    $('#apply_discount_button').attr('disabled', 'disabled');
    $('#apply_discount_button').addClass('cursor-not-allowed');

    


    //loadBilledStudents();//load billed students at the view invoices tab

    $('#submit_button').attr('disabled', 'disabled');
    $('#import_csv').attr('disabled', 'disabled');

    $('#generate_csv').mouseover(function() {
      $('#generate_csv').popover('show');
    });

    $('#generate_csv').mouseout(function() {
      $('#generate_csv').popover('hide');
    });

    $('.submit').attr('disabled', 'disabled');
    
    // Listen for invoice modification completion event from modal
    $(document).on('invoiceModificationComplete', function(event, data) {
      event.preventDefault(); // Prevent the fallback reload in modal
      
      // Refresh the invoice table without full page reload
      if(typeof refreshBulkInvoices === 'function') {
        refreshBulkInvoices();
      } else if(typeof loadBulkInvoices === 'function') {
        loadBulkInvoices();
      }
    });
  });

  function filterStudents() {

    var input, filter, ul, li, a, i, txtValue;
    input = document.getElementById("search-student");

    filter = input.value.toUpperCase();
    ul = document.getElementById("studentUrl");
    li = ul.getElementsByTagName("li");

    for (i = 0; i < li.length; i++) {
        a = li[i].getElementsByTagName("label")[0];
        txtValue = a.textContent || a.innerText;
        if (txtValue.toUpperCase().indexOf(filter) > -1) {
            li[i].style.display = "";
        } else {
            li[i].style.display = "none";
        }
    }
  }

  function select() {
    var chk = $('.check');
      for (i = 0; i < chk.length; i++) {
        chk[i].checked = true ;
      }

    //alert('asasas');
  }

  //reset invoice selections
  function resetInvoice() {

    selected = []; //reset the array
    item_ids = [];
    single_bill_items_array = [];
    mass_bill_items_array = [];

    //enable add btn and make it clickable

    //single
    $('#16484_1565043896_add_btn').attr('onclick', "add_invoice_item('16484_1565043896')");
    //$('#16484_1565043896_add_btn').removeAttr('disabled');
    //mass
    $('#177_1565053816_add_btn').attr('onclick', "add_invoice_item2('177_1565053816')");
   // $('#177_1565053816_add_btn').removeAttr('disabled');

    $('select[name="class_id"]').prop('value', '').change();
    $('select[name="student_id"]').html('<option value="">Select class first</option>');
    //reset for mass
    $('#177_1565053816_title').val('');
    $('#177_1565053816_description').val('');
    $('#177_1565053816_amount').val('');
    //$('#177_1565053816_add_btn').removeAttr('disabled');//enable add button
    //delete all other rows added
    $('#invoice_items_holder2 .row').not('#177_1565053816').remove();

    //reset for single
    $('#16484_1565043896_title').val('');
    $('#16484_1565043896_description').val('');
    $('#16484_1565043896_amount').val('');
    //$('#16484_1565043896_add_btn').removeAttr('disabled');//enable add button
    //delete all other rows added
    $('#invoice_items_holder .row').not('#16484_1565043896').remove();
    
    //get_class_students('');
    //get_class_students_mass('');
  }


  function unselect() {
    var chk = $('.check');
      for (i = 0; i < chk.length; i++) {
        chk[i].checked = false ;
      }
  }


    function get_class_students(class_id) {
        if (class_id !== '') {

          $('.submit').removeAttr('disabled');
          $('#invoice_items_holder').slideDown('slow');

          $.ajax({
            url: '<?php echo site_url('admin/get_class_name/');?>' + class_id,
            type: 'POST',
            dataType: 'text',

          })
          .done(function(class_name) {

            if(class_name == 'JHSS') {
              $('#term').removeAttr('required');
              $('#term_holder').slideUp('slow');
              $('#sem_holder').slideDown('slow');
              $('#sem').attr('required', 'required');

            } else {
              $('#sem').removeAttr('required');
              $('#sem_holder').slideUp('slow');
              $('#term_holder').slideDown('slow');
              $('#term').attr('required', 'required');
            }
          });//

          let year = $('#year').val();
          let term = $('#term').val();


          $.ajax({
            url: '<?php echo site_url('admin/get_class_students/');?>' + class_id,
            type: 'POST',
            data: {year_selected: year, term_selected: term},
            dataType: 'html',

          })
          .done(function(response) {

              jQuery('#student_selection_holder').html(response);
          })
          .fail(function() {
            alert('error');
          });
        
      } else {
        $('.submit').attr('disabled', 'disabled');
        $('#invoice_items_holder').slideUp('slow');
      }
    }


    //for displaying already entered invoice items
    function toggle_clone_button(class_id) {
      
        if (class_id.length > 0) {

          $('#clone_button').removeClass('bg-gray-300 cursor-not-allowed text-gray-600');
          $('#clone_button').addClass('bg-blue-900  text-gray-200 hover:text-gray-300');
          $('#clone_button').removeAttr('disabled');

        } else {

          $('#clone_button').removeClass('bg-blue-900  text-gray-200');
          $('#clone_button').addClass('bg-gray-300 cursor-not-allowed text-gray-600');
          $('#clone_button').attr('disabled', 'disabled');
        }

    }


    // Reload bill items dropdown based on selected classes
    function reload_bill_items_for_classes(class_ids) {
        if(!class_ids || class_ids.length === 0) {
            // No classes selected - show message in first row only
            $('#177_1565053816_title').html('<option value="">Select class(es) first</option>');
            $('#177_1565053816_title').val('');
            
            // Clear the selected array since no classes are selected
            selected = [];
            return;
        }

        $.ajax({
            url: '<?php echo site_url('admin/get_bill_items_json'); ?>',
            type: 'POST',
            data: { class_ids: class_ids },
            dataType: 'json',
            success: function(billItems) {
                let options = '<option value="">Select Billing Item</option>';
                
                if(billItems && billItems.length > 0) {
                    billItems.forEach(function(item) {
                        options += '<option value="' + item.title + '">' + item.title + '</option>';
                    });
                } else {
                    options = '<option value="">No bill items found for selected class(es)</option>';
                }
                
                // Update ALL bill item dropdowns (first row and any added rows)
                $('#invoice_items_holder2').find('select[id$="_title"]').each(function() {
                    let $dropdown = $(this);
                    let currentValue = $dropdown.val();
                    let currentText = $dropdown.find('option:selected').text();
                    
                    // Update dropdown options
                    $dropdown.html(options);
                    
                    // If there was a selected value, check if it still exists in the new list
                    if(currentValue && currentValue !== '') {
                        let stillExists = billItems.some(function(item) {
                            return item.title === currentValue;
                        });
                        
                        if(stillExists) {
                            // Item still exists in new filtered list - keep it selected
                            $dropdown.val(currentValue);
                        } else {
                            // Item no longer exists (class was removed) - clear it
                            $dropdown.val('');
                            
                            // Remove from mass_bill_items_array
                            let index = mass_bill_items_array.indexOf(currentValue);
                            if(index > -1) {
                                mass_bill_items_array.splice(index, 1);
                            }
                            
                            // Remove from selected array
                            let selectedIndex = selected.indexOf(currentValue);
                            if(selectedIndex > -1) {
                                selected.splice(selectedIndex, 1);
                            }
                        }
                    }
                });
            },
            error: function() {
                $('#177_1565053816_title').html('<option value="">Error loading bill items</option>');
            }
        });
    }




    function get_class_students_mass(class_ids_array) {

        jQuery('#student_selection_holder_mass').slideDown('slow');
        $('#invoice_items_holder2').slideDown('slow');

        // Reload bill items based on selected classes
        reload_bill_items_for_classes(class_ids_array);

        /*$.ajax({
            url: '<?php echo site_url('admin/get_class_name/');?>' + class_ids_array,
            type: 'POST',
            dataType: 'text',

          })
          .done(function(class_name) {

            if(class_name == 'JHSS') {
              $('#term_mass').removeAttr('required');
              $('#term_holder_mass').slideUp('slow');
              $('#sem_holder_mass').slideDown('slow');
              $('#sem_mass').attr('required', 'required');

            } else {
              $('#sem_mass').removeAttr('required');
              $('#sem_holder_mass').slideUp('slow');
              $('#term_holder_mass').slideDown('slow');
              $('#term_mass').attr('required', 'required');
            }
          });//*/

        $('#sem_mass').removeAttr('required');
        $('#sem_holder_mass').slideUp('slow');
        $('#term_holder_mass').slideDown('slow');
        $('#term_mass').attr('required', 'required');

        let year = $('#year_mass').val();
        let term = $('#term_mass').val();
        let cat = $('#students_category').val();

        $.ajax({
            url: '<?php echo site_url('admin/get_class_students_mass/');?>' + cat,
            type: 'GET',
            data: {class_data: class_ids_array, year_selected: year, term_selected: term},
            success: function(response)
            {
                jQuery('#student_selection_holder_mass').html(response);

                // Check if students were found
                var hasStudents = jQuery('#student_selection_holder_mass').find('.no-students-indicator').data('has-students');
                
                if (hasStudents === false) {
                    // No students found - disable bill items and create button
                    $('#invoice_items_holder2').slideUp('slow');
                    $('.submit2').prop('disabled', true).addClass('opacity-50 cursor-not-allowed');
                    
                    // Show a notice
                    if ($('#no-students-notice').length === 0) {
                        $('#invoice_items_holder2').before('<div id="no-students-notice" class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-4"><p class="text-yellow-700 font-semibold">⚠️ Bill item selection and invoice creation are disabled until valid students are available.</p></div>');
                    }
                } else {
                    // Students found - enable bill items and create button
                    $('#invoice_items_holder2').slideDown('slow');
                    $('.submit2').prop('disabled', false).removeClass('opacity-50 cursor-not-allowed');
                    $('#no-students-notice').remove();
                }

                select();/*select all students*/
            },

            error: function(error)
            {
                jQuery('#student_selection_holder_mass').html(error.responseText);
                
                // On error, also disable
                $('#invoice_items_holder2').slideUp('slow');
                $('.submit2').prop('disabled', true).addClass('opacity-50 cursor-not-allowed');
            }
        });
      
    }


    function toggle_students_category() {

      let cat = $('#students_category').val();

      if(cat != 'all') {

        $('#mass_class_holder').slideDown('slow');
        $('#class_id2').attr('required', 'required');

        jQuery('#student_selection_holder_mass').slideUp('slow');
        jQuery('#student_selection_holder_mass').html();
        

       /* if(cat == 'class') {
          $('#invoice_items_holder2').slideUp('slow');
          return; //end here if by class is selected

        } else {
*/
          $('#invoice_items_holder2').slideDown('slow');
        //}
 
      } else {

        toggle_clone_button([]);

        jQuery('#student_selection_holder_mass').slideDown('slow');
        $('#invoice_items_holder2').slideDown('slow');

        $('#class_id2').select2('val', '');

        $('#mass_class_holder').slideUp('slow');
        $('#class_id2').removeAttr('required');

      }

      jQuery('#student_selection_holder_mass').html('<center class="mt-5;"><i class="fa fa-spinner fa-spin" style="font-size: 20px;"></i></center>');

        let class_ids_array = $('#class_id2').val();
        let year = $('#year_mass').val();
        let term = $('#term_mass').val();


        $.ajax({
          url: '<?php echo site_url('admin/get_class_students_mass/');?>' + cat,
          type: 'GET',
          data: {class_data: class_ids_array, year_selected: year, term_selected: term},
          success: function(response)
          {

              jQuery('#student_selection_holder_mass').slideDown('slow');
              jQuery('#student_selection_holder_mass').html(response);

              // Check if students were found
              var hasStudents = jQuery('#student_selection_holder_mass').find('.no-students-indicator').data('has-students');
              
              if (hasStudents === false) {
                  // No students found - disable bill items and create button
                  $('#invoice_items_holder2').slideUp('slow');
                  $('.submit2').prop('disabled', true).addClass('opacity-50 cursor-not-allowed');
                  
                  // Show a notice
                  if ($('#no-students-notice').length === 0) {
                      $('#invoice_items_holder2').before('<div id="no-students-notice" class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-4"><p class="text-yellow-700 font-semibold">⚠️ Bill item selection and invoice creation are disabled until valid students are available.</p></div>');
                  }
              } else {
                  // Students found - enable bill items and create button
                  $('#invoice_items_holder2').slideDown('slow');
                  $('.submit2').prop('disabled', false).removeClass('opacity-50 cursor-not-allowed');
                  $('#no-students-notice').remove();
              }

              select();/*select all students*/
          },

          error: function(error)
          {
              jQuery('#student_selection_holder_mass').slideDown('slow');
              jQuery('#student_selection_holder_mass').html(error.responseText);
              
              // On error, also disable
              $('#invoice_items_holder2').slideUp('slow');
              $('.submit2').prop('disabled', true).addClass('opacity-50 cursor-not-allowed');
          }
        });
     
    }

    function check_validation(){
        if (class_id !== '') {
            $('.submit').removeAttr('disabled');
            $('#invoice_items_holder').css('display', 'block');
        }
        else{
            $('.submit').attr('disabled', 'disabled');
            $('#invoice_items_holder').removeAttr('style');
        }
    }
    $('.class_id').change(function(){
        class_id = $('.class_id').val();
        check_validation();
    });

      
    //function to add the invoice items
      function add_invoice_item(id, type) {

      //  $('.select2').select2('destroy'); /*destroy*/
        
        let title_value = $('#' + id + '_title').val();
        single_bill_items_array.push(title_value);

        item_ids.push(id);
        $.ajax({
          url: '<?php echo site_url('admin/add_invoice_item/'); ?>' + type,
          data: {bill_items_array: single_bill_items_array},
          success: function(response) {

            if(type == 'single') {
              $('#invoice_items_holder_generated').append(response);

            } else {
              $('#invoice_items_holder').append(response);
            }

            //disable the add button of this row and remove the onclick event listener
            $('#' + id +  '_add_btn').removeAttr('onclick');
            $('#' + id +  '_add_btn').attr('disabled', 'disabled');


          }
        });

      }

      //function to add the invoice items for mass invoice
      function add_invoice_item2(id, type) {

        let title_value = $('#' + id + '_title').val();
        
        if(!title_value || title_value === '') {
          showAjaxModal_alert('Please select a billing item first', 'error');
          return;
        }
        
        mass_bill_items_array.push(title_value);

        item_ids.push(id);
        
        // Get current selected classes
        let class_ids = $('#class_id2').val() || [];
        
        $.ajax({
          url: '<?php echo site_url('admin/add_mass_invoice_item/'); ?>' + type,
          data: {
            bill_items_array: mass_bill_items_array,
            class_ids: class_ids
          },
          success: function(response) {
            if(type == 'mass') {
              $('#invoice_items_holder_generated2').append(response);/*pre generated*/

            } else {
              $('#invoice_items_holder2').append(response);
            }

            //disable the add button of this row and remove the onclick event listener
            $('#' + id +  '_add_btn').removeAttr('onclick');
            $('#' + id +  '_add_btn').attr('disabled', 'disabled');
            
            // Re-initialize select2 for new dropdowns
            $('.select2').select2();
          }
        });

      }


      //function to add the invoice items to the student invoice list
      function add_invoice_item_list(id, type) {

        let title_value = $('#' + id + '_title').val();
        list_bill_items_array.push(title_value);

        bill_list_item_ids.push(id);
        $.ajax({
          url: '<?php echo site_url('admin/add_list_invoice_item/'); ?>' + type,
          data: {bill_items_array: list_bill_items_array},
          success: function(response) {
            $('#billed_student_invoices_holder').append(response);

            //disable the add button of this row and remove the onclick event listener
            /*$('#' + id +  '_add_btn').removeAttr('onclick');
            $('#' + id +  '_add_btn').attr('disabled', 'disabled');*/
          }
        });
      }

      /*Clone previous billing*/
      function toggle_clone_previous_bill(text) {

        if(text.toLowerCase() == 'clone previous') {

          /*show the term and year to clone row and hide the invoice items holder row*/
          $('#clone_unclone_holder').slideDown('slow');
          $('#invoice_items_holder2, #mass_create_button_holder').slideUp('slow');

          $('#clone_button').text('GO BACK');
          
        } else {

          /*show the term and year to clone row and hide the invoice items holder row*/
          $('#clone_unclone_holder').slideUp('slow');
          $('#invoice_items_holder2, #mass_create_button_holder').slideDown('slow');
          $('#clone_button').text('CLONE PREVIOUS');

        }
      }

      function clone_previous_bill() {

        var class_ids_array = $('#class_id2').val();
        
        if(class_ids_array.length < 1) {

          showAjaxModal_alert('No class selected! Select at least one class.', 'Error');
          return;
        }

        if(class_ids_array.length > 1) {
          showAjaxModal_alert('<span class="text-xl font-semibold">You can clone for one class at a time. Make sure you select a single class before using this feature!</span>', 'Error');

          return;
        }

        /*$('#invoice_items_holder2').html('<center><i class="fa-solid fa-spinner fa-pulse fa-2x"></i> <span class="font-bold text-xl">Loading Bill Items</span></center>');*/


        $.ajax({
          url: '<?php echo site_url('admin/clone_previous_bill_dummy/mass'); ?>',
          data: {class_data: class_ids_array},
          success: function(response) {

            $('#invoice_items_holder2').html(response);
          }
        });

      }

    //function to delete the invoice item
    function remove_invoice_item(id, type) {
      
      let last_row_id = 0;

      if(type == 'single') {
         last_row_id = $('#invoice_items_holder_generated .row:last').attr('id');


      } else {
        last_row_id = $('#invoice_items_holder .row:last').attr('id');
      }

      //check if the selected id is the same as the id of the last row
      //push the last row id into the array
      if(id == last_row_id) {
        id = last_row_id;
        
        //before we push this id inside the array, let's find out if it already exists in the array
        let id_counter = 0;
        for(i = 0; i < item_ids.length; i++) {
          if(id == item_ids[i]) {
            id_counter++;
          }
        }

        if(id_counter == 0) {//same id not found in the array so let's push it inside the array
          item_ids.push(id);
        }

        //get the previous button and enable it
        let previous_id_position = item_ids.length - 2;
        let previous_id = item_ids[previous_id_position];

        //enable the add button of the previous row and add the onclick event listener
        $('#' + previous_id +  '_add_btn').attr('onclick', "add_invoice_item('" + previous_id + "')");
        $('#' + previous_id +  '_add_btn').removeAttr('disabled');
      } 

      //remove the row with this id
      $('#'+id).remove();

      //remove this id from the array
      item_ids = item_ids.filter(function(index) {
        return index != id;
      });

      /**if the length of the array is less than or equal 1, then it is only the default row that is left
      * thus, reset it to its default state: add onclick event listener and also remove the disabled attr
      **/

      if(item_ids.length <= 1) {
        $('#16484_1565043896_add_btn').attr('onclick', "add_invoice_item('16484_1565043896')");
        $('#16484_1565043896_add_btn').removeAttr('disabled');

        //reset the array to null
        item_ids = [];
      }
    }

     //function to delete the invoice item for mass invoice
     function remove_invoice_item2(id, type, val) {

      //console.log('before ' + mass_bill_items_array);
      /*remove the item deleted from the arrays*/
      selected = selected.filter(function(item) {
        return item !== val;
      });
      mass_bill_items_array = mass_bill_items_array.filter(function(item) {
        return item !== val;
      });

      //console.log('after ' + mass_bill_items_array);

      let last_row_id = 0;
      
      if(type == 'mass') {
        last_row_id = $('#invoice_items_holder_generated2 .row:last').attr('id');

      } else {
        last_row_id = $('#invoice_items_holder2 .row:last').attr('id');
      }
      //check if the selected id is the same as the id of the last row
      //push the last row id into the array
      if(id == last_row_id) {
        id = last_row_id;
        
        //before we push this id inside the array, let's find out if it already exists in the array
        let id_counter = 0;
        for(i = 0; i < item_ids.length; i++) {
          if(id == item_ids[i]) {
            id_counter++;
          }
        }

        if(id_counter == 0) {//same id not found in the array so let's push it inside the array
          item_ids.push(id);
        }

        //get the previous button and enable it
        let previous_id_position = item_ids.length - 2;
        let previous_id = item_ids[previous_id_position];

        //enable the add button of the previous row and add the onclick event listener
        $('#' + previous_id +  '_add_btn').attr('onclick', "add_invoice_item2('" + previous_id + "')");
        $('#' + previous_id +  '_add_btn').removeAttr('disabled');
      } 

      //remove the row with this id
      $('#'+id).remove();

      //remove this id from the array
      item_ids = item_ids.filter(function(index) {
        return index != id;
      });

      /**if the length of the array is less than or equal 1, then it is only the default row that is left
      * thus, reset it to its default state: add onclick event listener and also remove the disabled attr
      **/
      if(item_ids.length <= 1) {
        $('#177_1565053816_add_btn').attr('onclick', "add_invoice_item2('177_1565053816')");
        $('#177_1565053816_add_btn').removeAttr('disabled');

        //reset the array to null
        item_ids = [];
      }
    }

     //function to delete the invoice item for mass invoice

    function remove_invoice_item_list(id, type, val) {

      /*remove the item deleted from the arrays*/
      selected = selected.filter(function(item) {
        return item !== val;
      });
      list_bill_items_array = list_bill_items_array.filter(function(item) {
        return item !== val;
      });

      //console.log('after ' + mass_bill_items_array);

      let last_row_id = 0;

      last_row_id = $('#billed_student_invoices_holder .row:last').attr('id');
      
      //check if the selected id is the same as the id of the last row
      //push the last row id into the array
      if(id == last_row_id) {
        id = last_row_id;
        
        //before we push this id inside the array, let's find out if it already exists in the array
        let id_counter = 0;
        for(i = 0; i < bill_list_item_ids.length; i++) {
          if(id == bill_list_item_ids[i]) {
            id_counter++;
          }
        }

        if(id_counter == 0) {//same id not found in the array so let's push it inside the array
          bill_list_item_ids.push(id);
        }

      } 

      //remove the row with this id
      $('#'+id).remove();

      //remove this id from the array
      bill_list_item_ids = bill_list_item_ids.filter(function(index) {
        return index != id;
      });



    }

    //form submitted for single invoice
    $('#invoice_form').submit(function(event) {
      event.preventDefault();

      showAjaxModal_alert('Please wait...', 'Loading');



      $('html, body').animate({
           scrollTop: ($('#top').offset().top )
      }, 1000); 

      let data_ids = [];

      if(item_ids.length == 0) {
        item_ids = ['16484_1565043896']
      }

      let form_data = $('#invoice_form').serialize();

      let last_row_id = 0;

      if($('#invoice_form').hasClass('single')) {
        //last_row_id = $('#invoice_items_holder_generated .row:last').attr('id');
        //reset the array to null
        item_ids = [];

        $('#invoice_items_holder_generated .row').each(function() {
          item_ids.push($(this).attr('id'));
        });

        //item_ids = item_ids.join('-');


      } else {
        last_row_id = $('#invoice_items_holder .row:last').attr('id');

        //before we push this id inside the array, let's find out if it already exists in the array
        let id_counter = 0;
        for(i = 0; i < item_ids.length; i++) {
          if(last_row_id == item_ids[i]) {
            id_counter++;
          }
        }

        if(id_counter == 0) {//some id not found in the array so let's push it inside the array
          item_ids.push(last_row_id);
        }

      }


      
        //Validate Title, Date and Total input to make sure they are not empty
        let validate_counter = 0;
        for(ji = 0; ji < item_ids.length; ji++) {
          let title_inp = $('#' + item_ids[ji] + '_title').val();
          let date_inp = $('#date').val();
          let total_inp = $('#' + item_ids[ji] + '_amount').val();


          if((title_inp == '' || title_inp == null) || (date_inp == '' || date_inp == null) || (total_inp == '' || total_inp == null)) {
            validate_counter++;
          } 
        }

        if(validate_counter > 0) { //some fields have not been filled
          showAjaxModal_alert('Title, Date and Amount fields must not be empty!', 'Error');
          toastr.error('Title, Date and Amount fields must not be empty!');
          $('#error').removeAttr('style');
          return false;
        }
      data_ids = item_ids.join('-');


      $.ajax({
        url: '<?php echo site_url('admin/invoice_create/create/'); ?>' + data_ids,
        data: form_data,
        success: function(response) {
          if(response == 'Success') {
            showAjaxModal_alert('Invoice was created successfully!', 'success', false);
            setTimeout(() => {
              $('.close').click();
              $('#invoice_form')[0].reset();
              $('#student_list').empty();
              $('#invoice_items_holder').empty();
              $('#invoice_items_holder_generated').empty();
              $('#class_id, #section_id, #date').val('').trigger('change');
              $('input[type="checkbox"]').prop('checked', false);
              item_ids = [];
            }, 1500);
          } else {
              showAjaxModal_alert(response, 'error');
          }
        }
      })
    });

    //form submitted for mass invoice
    $('#mass_invoice_form').submit(function(event) {
      event.preventDefault();

      // Validate term selection
      let term = $('#term_mass').val();
      if(!term || term === '') {
        showAjaxModal_alert('Please select term before proceeding', 'error');
        $('#term_mass').focus();
        $('html, body').animate({
          scrollTop: $('#term_mass').offset().top - 100
        }, 500);
        return false;
      }

      if($(':checkbox:checked').length < 1) {
        showAjaxModal_alert('No student selected.', 'error');
        return false;
      }

      let data_ids = [];

      if(item_ids.length == 0) {
        item_ids = ['177_1565053816']
      }

      let form_data = $('#mass_invoice_form').serialize();
      
      let last_row_id = 0;
      
      if($('#mass_invoice_form').hasClass('mass')) {
        item_ids = [];
        $('#invoice_items_holder_generated2 .row').each(function() {
          item_ids.push($(this).attr('id'));
        });
      } else {
        last_row_id = $('#invoice_items_holder2 .row:last').attr('id');
        let id_counter = 0;
        for(i = 0; i < item_ids.length; i++) {
          if(last_row_id == item_ids[i]) {
            id_counter++;
          }
        }
        if(id_counter == 0) {
          item_ids.push(last_row_id);
        }
      }

      //Validate Title, Date and Total input to make sure they are not empty
      let validate_counter = 0;
      for(ji = 0; ji < item_ids.length; ji++) {
        let title_inp = $('#' + item_ids[ji] + '_title').val();
        let date_inp = $('#date_mass').val();
        let total_inp = $('#' + item_ids[ji] + '_amount').val();

        if((title_inp == '' || title_inp == null) || (date_inp == '' || date_inp == null) || (total_inp == '' || total_inp == null)) {
          validate_counter++;
        } 
      }

      if(validate_counter > 0) {
        showAjaxModal_alert('Title, Date and Amount fields must not be empty!', 'Error');
        toastr.error('Title, Date and Amount fields must not be empty!');
        $('#error2').removeAttr('style');
        return false;
      }

      // Show confirmation modal before creating invoices
      showInvoiceConfirmationModal(item_ids, function() {
        // User confirmed, proceed with invoice creation
        data_ids = item_ids.join('-');
        
        showAjaxModal_alert('Please wait...', 'loading');
        
        $('html, body').animate({
             scrollTop: ($('#top').offset().top )
        }, 1000); 

        $.ajax({
          url: '<?php echo site_url('admin/mass_invoice_create/create/'); ?>' + data_ids,
          type: 'POST',
          data: form_data,
          processData: false, // Don't convert to query string
          contentType: 'application/x-www-form-urlencoded', // Keep form encoding
          dataType: 'json',
          success: function(response) {
            showAjaxModal_alert(response.message, 'success', false, false);
            // Reload after 3 seconds to allow user to see success message
            setTimeout(function() {
              location.reload();
            }, 3000);
          },
          error: function(err) {
             showAjaxModal_alert('An error occurred: ' + err.responseText, 'error');
          }
        });
      });

      return false; // Prevent default form submission
    });

    //form submitted to upate student bill
    $('#bill_list_form').submit(function(event) {
      event.preventDefault();
      

      showAjaxModal_alert('Please wait...', 'Loading');
      
      $('html, body').animate({
           scrollTop: ($('#top').offset().top )
      }, 1000); 

      let data_ids = [];

      if(bill_list_item_ids.length == 0) {
        bill_list_item_ids = ['177_1565053816']
      }

      let form_data = $('#bill_list_form').serialize();
      
      let last_row_id = 0;
      
    
        last_row_id = $('#billed_student_invoices_holder .row:last').attr('id');

        //before we push this id inside the array, let's find out if it already exists in the array
        let id_counter = 0;
        for(i = 0; i < bill_list_item_ids.length; i++) {
          if(last_row_id == bill_list_item_ids[i]) {
            id_counter++;
          }
        }

        if(id_counter == 0) {//same id not found in the array so let's push it inside the array
          bill_list_item_ids.push(last_row_id);
        }
      

        //Validate Title, Date and Total input to make sure they are not empty
        let validate_counter = 0;
        for(ji = 0; ji < bill_list_item_ids.length; ji++) {
          let title_inp = $('#' + bill_list_item_ids[ji] + '_title').val();
          let total_inp = $('#' + bill_list_item_ids[ji] + '_amount').val();


          if((title_inp == '' || title_inp == null) || (total_inp == '' || total_inp == null)) {
            validate_counter++;
          } 
        }

        if(validate_counter > 0) { //some fields have not been filled
          showAjaxModal_alert('Title and Amount fields must not be empty!', 'Error');
          toastr.error('Title and Amount fields must not be empty!');
          $('#error2').removeAttr('style');
          return false;
        }

      data_ids = bill_list_item_ids.join('-');
      let student_id = $('#student_id').val();
      let invoice_code = $('#student_invoice_codes').val();
      let term = $('#term_view').val();
      let year = $('#year_view').val();

      $.ajax({
        url: '<?php echo site_url('admin/mass_invoice_create/update/'); ?>' + data_ids + '/' + student_id + '/' + invoice_code + '/' + year + '/' + term,
        data: form_data,
        success: function(response) {
          showAjaxModal_alert(response.message, 'success');
          setTimeout(() => {
            get_student_invoice_bill();
          }, 1500);
        },
        error: function(err) {
           showAjaxModal_alert('An error occurred', 'error');
        }
      })
    });


    $('#add_bill_item_form').submit(function(ev) {
      ev.preventDefault();

      let title = $('#bill_title').val();
      let category = $('#bill_category').val();
      let desc = $('#bill_description').val();
      let amount = $('#bill_amount').val();
      let class_category = $('#bill_class_category').val();
      let specific_class_ids = $('#bill_specific_class_ids').val(); // Array of class IDs

      if(title == '' && category == '' && amount == '') {
        showAjaxModal_alert('Bill item title, category and amount are required!', 'error');
        return false;
      }

      if(title == '') {
        showAjaxModal_alert('Bill item title is required!', 'error');
        return false;
      }

      if(category == '') {
        showAjaxModal_alert('Bill item category is required!', 'error');
        return false;
      }

      if(amount == '') {
        showAjaxModal_alert('Bill item amount is required!', 'error');
        return false;
      }

      showAjaxModal_alert('Adding bill item, please wait...', 'loading');
      $.ajax({
        url: '<?=site_url('admin/invoice/add_bill_item/'); ?>',
        type: 'post',
        dataType: 'json',
        data: {
          'title': title,
          'desc': desc,
          'category': category,
          'amount': amount,
          'class_category': class_category,
          'specific_class_ids': specific_class_ids ? specific_class_ids.join(',') : '', // Convert array to comma-separated string
        }
      })
      .done(function(response) {
        if(response.success == 1) {
          showAjaxModal_alert('Item added successfully.', 'success');
          // setTimeout(() => {
          //   $('.close').click();
          //   $('#bill_title').val('');
          //   $('#bill_category').val('').trigger('change');
          //   $('#bill_description').val('');
          //   $('#bill_amount').val('');
          //   //refreshBillItemsTable();
          // }, 100);
        } else if(response.success == 2) {
          showAjaxModal_alert('This title already exists. Try another title.', 'error');
          $('#bill_title').val('');
          $('#bill_description').val('');
          $('#bill_title').focus();
        } else if(response.success == 0) {
          showAjaxModal_alert('Failed to add item! Try again later.', 'error');
        } 
      })
      .fail(function(err) {
        showAjaxModal_alert('An error occurred', 'error');
      })
    });

    function refreshBillItemsTable() {
      $.ajax({
        url: '<?php echo site_url('admin/get_bill_item'); ?>',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
          if(response.status === 'success') {
            updateBillItemsTable(response.data);
            $('#bill_items_table').trigger('update');
          }

        },
        error: function(err) {
          console.log(err.responseText)
        }
      });
    }

    function updateBillItemsTable(items) {
      var tbody = $('#bill_items_table tbody');
      tbody.empty();
      
      if(items.length > 0) {
        items.forEach(function(item, index) {
          var deleteBtn = (item.id == 8 || item.id == 9) ? 
            '<a href="javascript:void(0);" title="System generated, can\'t be deleted!" disabled class="btn btn-danger rounded-lg" style="opacity: 0.5; cursor: not-allowed;"><i class="entypo-trash"></i></a>' :
            '<a href="javascript:void(0);" onclick="deleteItem(\''+item.id+'\');" class="btn btn-danger rounded-lg" title="Delete"><i class="entypo-trash"></i></a>';
          
          var row = `
            <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 border-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600">
              <td class="px-6 py-2">
                <input type="checkbox" class="bill_item_checkbox w-5 h-5 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500" value="${item.id}" style="cursor: pointer;">
              </td>
              <td class="px-6 py-2">${index + 1}</td>
              <td width="300" class="px-6 py-2">
                <div id="display_title_${item.id}">${item.title}</div>
                <div style="display: none" id="input_title_${item.id}">
                  <input type="text" class="bg-gray-50 border border-gray-300 text-gray-900 rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full h-20 p-2.5" value="${item.title}" id="edit_bill_title_${item.id}" title="${item.title}"/>
                  <input type="hidden" value="${item.title}" id="hidden_title_${item.id}"/>
                </div>
              </td>
              <td width="300" class="px-6 py-2">
                <div id="display_category_${item.id}">${item.category_name}</div>
                <div style="display: none" id="input_category_${item.id}">
                  <select id="edit_bill_category_${item.id}" class="select2 bg-gray-50 border border-gray-300 text-gray-900 rounded-lg text-xl focus:ring-primary-500 focus:border-primary-500 block w-full h-20 p-2.5">
                    ${item.category_options}
                  </select>
                  <input type="hidden" value="${item.bill_category_id}" id="hidden_category_${item.id}"/>
                </div>
              </td>
              <td width="200" class="px-6 py-2">
                <div id="display_class_category_${item.id}">${item.class_category_display || '<span style="color: #999;">All Classes</span>'}</div>
                <div style="display: none" id="input_class_category_${item.id}">
                  <select id="edit_bill_class_category_${item.id}" class="select2 bg-gray-50 border border-gray-300 text-gray-900 rounded-lg text-xl focus:ring-primary-500 focus:border-primary-500 block w-full h-20 p-2.5">
                    ${item.class_category_options}
                  </select>
                  <input type="hidden" value="${item.class_category || ''}" id="hidden_class_category_${item.id}"/>
                </div>
              </td>
              <td width="250" class="px-6 py-2">
                <div id="display_specific_classes_${item.id}">${item.specific_classes_display || '<span style="color: #999;">-</span>'}</div>
                <div style="display: none" id="input_specific_classes_${item.id}">
                  <select id="edit_bill_specific_class_ids_${item.id}" name="edit_bill_specific_class_ids_${item.id}[]" multiple class="select2 bg-gray-50 border border-gray-300 text-gray-900 rounded-lg text-xl focus:ring-primary-500 focus:border-primary-500 block w-full h-20 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500" style="margin-top: 0 !important;">
                    ${item.specific_classes_options}
                  </select>
                  <input type="hidden" value="${item.specific_class_ids || ''}" id="hidden_specific_class_ids_${item.id}"/>
                </div>
              </td>
              <td width="300" class="px-6 py-2">
                <div id="display_amount_${item.id}">${parseFloat(item.amount).toLocaleString('en-US', {minimumFractionDigits: 2})}</div>
                <div style="display: none" id="input_amount_${item.id}">
                  <input type="number" class="bg-gray-50 border border-gray-300 text-gray-900 rounded-lg focus:ring-primary-600 focus:border-primary-600 block flex-1 w-full h-20 p-2.5" value="${item.amount}" id="edit_bill_amount_${item.id}"/>
                  <input type="hidden" value="${item.amount}" id="hidden_amount_${item.id}"/>
                </div>
              </td>
              <td width="300" class="px-6 py-2">
                <div id="display_desc_${item.id}">${item.description || ''}</div>
                <div style="display: none" id="input_desc_${item.id}">
                  <textarea rows="2" class="block p-2.5 w-full h-20 text-gray-900 bg-gray-50 rounded-lg border border-gray-300" id="edit_bill_description_${item.id}">${item.description || ''}</textarea>
                  <input type="hidden" value="${item.description || ''}" id="hidden_desc_${item.id}"/>
                </div>
              </td>
              <td class="px-6 py-2">
                <div class="flex gap-2">
                  <a href="javascript:void(0);" id="edit_button_${item.id}" onclick="editItem('${item.id}');" class="btn btn-success rounded-lg" title="Edit"><i class="entypo-pencil"></i></a>
                  ${deleteBtn}
                </div>
              </td>
            </tr>
            <tr>
              <td class="px-6 py-2"></td>
              <td colspan="7" id="alert_${item.id}" class="px-6 py-2"></td>
            </tr>
          `;
          tbody.append(row);
        });
      } else {
        tbody.html('<tr><td align="center" colspan="7">No item found yet! Add one.</td></tr>');
      }
    }
    //edit
    function editItem(id) {

      let ids = [];
      ids = <?=json_encode($items_ids); ?>;

      let editButtonText = $('#edit_button_'+id).text();
      if(editButtonText == 'UPDATE') {
        //trying to run update
        //we check if user has really made any changes. If not, we don't submit the form
        let bill_title = $('#edit_bill_title_'+id).val();
        let bill_desc = $('#edit_bill_description_'+id).val();
        let bill_category = $('#edit_bill_category_'+id).val();
        let bill_class_category = $('#edit_bill_class_category_'+id).val();
        let bill_specific_class_ids = $('#edit_bill_specific_class_ids_'+id).val();
        let bill_amount = $('#edit_bill_amount_'+id).val();

        let hidden_title = $('#hidden_title_'+id).val();
        let hidden_desc = $('#hidden_desc_'+id).val();
        let hidden_category = $('#hidden_category_'+id).val();
        let hidden_class_category = $('#hidden_class_category_'+id).val();
        let hidden_specific_class_ids = $('#hidden_specific_class_ids_'+id).val();
        let hidden_amount = $('#hidden_amount_'+id).val();

        // Convert arrays to strings for comparison
        let bill_specific_ids_str = bill_specific_class_ids ? bill_specific_class_ids.join(',') : '';
        
        if(bill_title.toLowerCase() == hidden_title.toLowerCase() && bill_desc.toLowerCase() == hidden_desc.toLowerCase() && bill_category.toLowerCase() == hidden_category.toLowerCase() && bill_class_category == hidden_class_category && bill_specific_ids_str == hidden_specific_class_ids && Number(bill_amount) == Number(hidden_amount)) {
          //no changes detected. return false
          $('#alert_'+id).html('<div class="alert alert-info"><button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button><strong><i class="entypo-info-circled"></i></strong> No changes was detected!</div>');
          $('#edit_button_'+id).html('<i class="entypo-pencil"></i>');

          $('#input_title_'+id).slideUp('fast');
          $('#display_title_'+id).slideDown('fast');

          $('#input_category_'+id).slideUp('fast');
          $('#display_category_'+id).slideDown('fast');

          $('#input_class_category_'+id).slideUp('fast');
          $('#display_class_category_'+id).slideDown('fast');

          $('#input_specific_classes_'+id).slideUp('fast');
          $('#display_specific_classes_'+id).slideDown('fast');

          $('#input_amount_'+id).slideUp('fast');
          $('#display_amount_'+id).slideDown('fast');

          $('#input_desc_'+id).slideUp('fast');
          $('#display_desc_'+id).slideDown('fast');

        } else {
          //sure something has changed so we can make update now

          showAjaxModal_alert('Updating bill item, please wait...', 'Loading');
          $.ajax({
            url: '<?=site_url('admin/invoice/update_bill_item/'); ?>',
            type: 'post',
            dataType: 'json',
            data: {
              'title': bill_title,
              'desc': bill_desc,
              'category': bill_category,
              'class_category': bill_class_category,
              'specific_class_ids': bill_specific_ids_str,
              'amount': bill_amount,
              'id': id,
            }
          })
          .done(function(response) {
            if(response.success == 0) {
              showAjaxModal_alert('Failed to update item! Try again later.', 'error');
            } else if(response.success == 1) {
              showAjaxModal_alert('Bill item updated successfully.', 'success');
              // setTimeout(() => {
              //   $('.close').click();
              //   refreshBillItemsTable();
              // }, 1000);
            } else {
              showAjaxModal_alert('Duplicate!. Try a different title.', 'error');
              $('#bill_title').val('');
              $('#bill_description').val('');
              $('#bill_category').val('');
              $('#bill_amount').val('');
              $('#bill_title').focus();
            }
           })
          .fail(function(err) {
            showAjaxModal_alert('An error occurred', 'error');
          })
        }

      } else {

        //check and close any other that is opened
        for(let i=0; i <= ids.length; i++) {
          if(ids[i] != id) {
            if($('#edit_button_'+ids[i]).text() == 'UPDATE') {
              $('#edit_button_'+ids[i]).html('<i class="entypo-pencil"></i>');
              $('#input_title_'+ids[i]).slideUp('fast');
              $('#display_title_'+ids[i]).slideDown('fast');

              $('#input_category_'+ids[i]).slideUp('fast');
              $('#display_category_'+ids[i]).slideDown('fast');

              $('#input_class_category_'+ids[i]).slideUp('fast');
              $('#display_class_category_'+ids[i]).slideDown('fast');

              $('#input_specific_classes_'+ids[i]).slideUp('fast');
              $('#display_specific_classes_'+ids[i]).slideDown('fast');

              $('#input_amount_'+ids[i]).slideUp('fast');
              $('#display_amount_'+ids[i]).slideDown('fast');

              $('#input_desc_'+ids[i]).slideUp('fast');
              $('#display_desc_'+ids[i]).slideDown('fast');
            }

             $('#alert_'+ids[i]+' .close').click(); //close alert if it is opened
          }
        } //end of closing any other opened edit field

        $('#alert_'+id+' .close').click(); //close alert if it is opened
        $('#edit_button_'+id).text('UPDATE');
        $('#input_title_'+id).slideDown('fast');
        $('#display_title_'+id).slideUp('fast');

        $('#input_category_'+id).slideDown('fast');
        $('#display_category_'+id).slideUp('fast');

        $('#input_class_category_'+id).slideDown('fast');
        $('#display_class_category_'+id).slideUp('fast');

        $('#input_specific_classes_'+id).slideDown('fast', function() {
          // Reinitialize select2 for specific classes after showing
          $('#edit_bill_specific_class_ids_'+id).select2({
            width: '100%',
            placeholder: 'Select specific classes',
            allowClear: true
          });
        });
        $('#display_specific_classes_'+id).slideUp('fast');

        $('#input_amount_'+id).slideDown('fast');
        $('#display_amount_'+id).slideUp('fast');

        $('#input_desc_'+id).slideDown('fast');
        $('#display_desc_'+id).slideUp('fast');
      }
    }

    let isDeleting = false;
    
    function deleteItem(id) {
      if(isDeleting) return;
      
      showConfirmModal(
        'Confirm Delete',
        'Are you sure you want to delete this bill item?',
        function() {
          if(isDeleting) return;
          isDeleting = true;
          
          showAjaxModal_alert('Deleting...', 'loading');
          $.ajax({
            url: '<?=site_url('admin/invoice/delete_bill_item/'); ?>' + id,
            type: 'GET',
            dataType: 'json',
            cache: false
          }).done(function(response) {
            if(response.status === 'success') {
              showAjaxModal_alert(response.message, 'success');
              // setTimeout(() => {
              //   $('.close').click();
              //   refreshBillItemsTable();
              //   isDeleting = false;
              // }, 100);
            } else {
              showAjaxModal_alert(response.message || 'Failed to delete', 'error');
              isDeleting = false;
            }
          }).fail(function(xhr) {
            console.error('Delete error:', xhr.responseText);
            showAjaxModal_alert('An error occurred while deleting', 'error');
            isDeleting = false;
          });
        },
        'Delete',
        'danger'
      );
    }

    
    function getItemDetails(row_id, val) {


      let pos = find2ndOccurence(row_id, '_');
      let code = row_id.substr(0, pos + 1);

      if(val != '') {
        $('#' + code +  'add_btn').removeAttr('disabled');
      } else {
        $('#' + code +  'add_btn').attr('disabled', 'disabled');
      }


      let exists = existsInArray(selected, val, code);
      
      if(exists) {
        showAjaxModal_alert('The selected item is already chosen. Select a different item!', 'Error');

        $('#' + code +  'title').select2('val', '');

        return;
      }

      $.ajax({
          url: '<?=site_url('admin/invoice/get_bill_item_details/'); ?>',
          type: 'post',
          dataType: 'json',
          data: {
            'title': val,
          }
        })
        .done(function(response) {
          $('#'+code+'description').val(response.description);
          $('#'+code+'category').val(response.category);
          $('#'+code+'amount').val(response.amount);
        })
    }

    //check if an element already exists in an array
    let counter2 = 0;
    function existsInArray(array, element, code) {
      let counter = 0;
      
      for(let val in array) {
        if(array[val] == element) {
          counter++;
        }
      }
      if(counter == 0) {
        if(counter2 > 0 && current_code == code) {
          //one has been pushed earlier
          let last_index = Number(selected.length);
          selected.pop(last_index);
        }
        selected.push(element);
        current_code = code;
        counter2++;
        return false;
      } else {
        return true;
      }
    }


    function find2ndOccurence(text, character) {
      let counter = 0;

      for(let i = 0; i <= text.length; i++) {
        if(text[i] == character) {
          counter++;
        }

        if(counter == 2) {
          return i;
        }
      }
    }



    function get_sections(class_id) {
    if (class_id != "") {
      $('#err_alert').css('display', 'none');

      $.ajax({
              url: '<?php echo site_url('admin/get_sections/');?>' + class_id ,
              success: function(response)
              {
                  jQuery('#section_holder').html(response);
                  jQuery('#bulk_add_form').show();
              }
          });
    }
  }


  $("#generate_csv").click(function(){

    //will build this late

    var class_id  = $('#class_id').val();
    var section_id  = $('#section_id').val();

    if(class_id == '' || section_id == '') {
      toastr.error("<?php echo get_phrase('please_make_sure_class_and_section_are_selected'); ?>");
      $('#err_alert').css('display', 'block');

      return false;
    }


      $.ajax({
          url: '<?php echo site_url('admin/generate_bulk_student_csv/');?>' + class_id + '/' + section_id,
          dataType: 'json',
          success: function(response) {
            toastr.success("<?php echo get_phrase('file_generated_and_downloaded_successfully._please_check_your_download_folder.'); ?>");
            $("#bulk").attr('href', response.file_path);
            $("#bulk").attr('download', response.file_name);
            jQuery('#bulk')[0].click();
            //document.location = response;
          }
      });
   
  });

  var loader = '<i class="fa fa-spinner fa-pulse"></i>';
  var checked_icon = '<i class="fa fa-check-square-o"></i>';
  
  //check uploaded file
  function check_loaded_csvfile() {
    //send file path to php to upload the file and validate
    
    var class_id = $('#class_id').val();
    var section_id = $('#section_id').val();


    if(class_id == '' && section_id == '' || class_id == null && section_id == null) {
      toastr.error('Make sure Class and Section fields are selected before you proceed!');

      //location.reload();
      return false;
    } else{

      $('#import_csv').removeAttr('disabled');
      $('#import_csv').click(); //submit the form
      
      //first disable the upload button
      var import_btn = $('#import_csv').attr('disabled', 'disabled');
      //change the btn text to 'please wait. Validating file...'
      import_btn.html(loader + ' Please wait...');

    }
  }

  $('#upload_invoices_form').submit(function(e) {
    e.preventDefault();

    showAjaxModal_alert(loader + ' Please be patient, upload ongoing...', 'Loading');
    let formUrl = $(this).attr('action');


    //let's send the file path to php now for upload and validation
      $.ajax({
        url: formUrl,
        type: 'post',
        data: new FormData(this),
        dataType: 'text',
        cache: false,
        contentType: false,
        processData: false
      })
      .done(function(response) {
        if(response == 'success') {
          showAjaxModal_alert('Invoices uploaded successfully', 'success');
          setTimeout(() => {
            $('.close').click();
            //location.reload();
          }, 100);
        } else if(response == 'failed') {
          showAjaxModal_alert('Sorry, we could not upload the invoices. Please try again!', 'error');
        } else if(response == 'no data') {
          showAjaxModal_alert('No data found for upload. Check your Excel/CSV file carefully.', 'error');
        } else {
          showAjaxModal_alert('An error occurred', 'error');
        }
        $('#import_csv').text('Upload');
      })
      .fail(function(err) {
        showAjaxModal_alert('An error occurred', 'error');
        $('#import_csv').text('Upload');
      })
        

  })

  /*load billed students function*/
  function loadBilledStudents() {

    let term = $('#term_view').val();
    let year = $('#year_view').val();

    $('#load_students').text('LOADING...');
    $('#load_students').attr('disabled', 'disabled');

    $.ajax({
      url: '<?php echo site_url('admin/getBilledStudents') ?>',
      dataType: 'html',
      type: 'post',
      data: {year: year, term: term},
      cache: false,
    })
    .done(function(response) {

      $('#student_id').html(response);
      $('#student_id option:first').prop('selected', true);
      $('#student_id').change();

      $('#load_students').text('LOAD STUDENTS');
      $('#load_students').removeAttr('disabled');
    })
    .fail(function(err) {
      showAjaxModal_alert('An error occurred', 'error');
      $('#load_students').text('LOAD STUDENTS');
      $('#load_students').removeAttr('disabled');
    })
  }

  /*get student's invoice codes when selected*/
  function get_student_invoice_codes(student_id) {

    let term = $('#term_view').val();
    let year = $('#year_view').val();

    if(student_id == '') {
      $('#student_invoice_codes').html('<option value="">Select Student First</option>').val('').trigger('change');
      $('#billed_student_invoices_holder').html('<div class="text-center py-20"><i class="fa fa-user-circle fa-5x text-gray-300 mb-4"></i><p class="text-2xl text-gray-500 font-semibold">Please select a student to view invoice codes</p></div>');
      $('#update_invoice_button').attr('disabled', 'disabled');
      $('#update_invoice_button').addClass('cursor-not-allowed');

      $('#apply_discount_button').attr('disabled', 'disabled');
      $('#apply_discount_button').addClass('cursor-not-allowed');

      $('#view_invoice_button').attr('disabled', 'disabled');
      $('#view_invoice_button').addClass('cursor-not-allowed');

      $('#email_invoice_button').attr('disabled', 'disabled');
      $('#email_invoice_button').addClass('cursor-not-allowed');
      

      return;

    } else {

      $('#update_invoice_button').removeAttr('disabled');
      $('#update_invoice_button').removeClass('cursor-not-allowed');

      $('#apply_discount_button').removeAttr('disabled');
      $('#apply_discount_button').removeClass('cursor-not-allowed');

      $('#view_invoice_button').removeAttr('disabled');
      $('#view_invoice_button').removeClass('cursor-not-allowed');

      $('#email_invoice_button').removeAttr('disabled');
      $('#email_invoice_button').removeClass('cursor-not-allowed');

    }

    $.ajax({
      url: '<?php echo site_url('admin/getStudentInvoiceCodesByTermYear') ?>',
      dataType: 'html',
      type: 'post',
      data: {year: year, term: term, student_id: student_id},
      cache: false,
    })
    .done(function(response) {

      $('#student_invoice_codes').html(response).change();
      
      if(!response || response.trim() === '' || response.includes('option value=""')) {
        $('#billed_student_invoices_holder').html('<div class="text-center py-20"><i class="fa fa-file-invoice fa-5x text-gray-300 mb-4"></i><p class="text-2xl text-gray-500 font-semibold">No invoice codes found for this student</p></div>');
      }

    })
    .fail(function(err) {
      showAjaxModal_alert('An error occurred', 'error');
    })

  }

  /*get student's invoice items when code is selected*/
  function get_student_invoice_bill() {

    let invoice_code = $('#student_invoice_codes').val();

    if(!invoice_code || invoice_code == '') {
      $('#billed_student_invoices_holder').html('<div class="text-center py-20"><i class="fa fa-file-invoice fa-5x text-gray-300 mb-4"></i><p class="text-2xl text-gray-500 font-semibold">Please select an invoice code to display invoice items</p></div>');
      $('#update_invoice_button').attr('disabled', 'disabled');
      $('#update_invoice_button').addClass('cursor-not-allowed');
      return;
    }

    $.ajax({
      url: '<?php echo site_url('admin/getStudentBillByInvoiceCode') ?>',
      dataType: 'json',
      type: 'post',
      data: {invoice_code: invoice_code},
      cache: false,
    })
    .done(function(response) {

      list_bill_items_array = response.items;

      $('#billed_student_invoices_holder').html(response.list);
      
      // Display discount details if available
      if(response.has_discount) {
        $('#invoice_discount_display').html(response.discount_html).show();
      } else {
        $('#invoice_discount_display').html('').hide();
      }

      bill_list_item_ids = ids;

      if(invoice_code != '') {

        $('#update_invoice_button').removeAttr('disabled');
        $('#update_invoice_button').removeClass('cursor-not-allowed');

      } else {

        $('#update_invoice_button').attr('disabled', 'disabled');
        $('#update_invoice_button').addClass('cursor-not-allowed');

      }

    })
    .fail(function(err) {
      $('#billed_student_invoices_holder').html(err.responseText);
    })

  }
  

  /*update the switch button value*/
  function updateBillAddition() {

    let selected_length = $('#add_to_class_bill').filter(':checked').length;

     if(selected_length > 0) {
        $('#add_to_class_bill').val(1);
        /*Yes*/
        $('#add_to_class_bill_label').removeClass('text-gray-400');

     } else {
        $('#add_to_class_bill').val(0);
        /*No*/
        $('#add_to_class_bill_label').addClass('text-gray-400');
     }


  }

  /*Clone button clicked for the process to begin*/
  $('#actual_clone_button').click(function(ev) {

    ev.preventDefault(); // Prevent default button behavior
    ev.stopPropagation(); // Stop event bubbling

    // STRONG VALIDATION: Validate TARGET term and year (where we're cloning TO)
    const termMass = $('#term_mass').val();
    const yearMass = $('#year_mass').val();
    
    if(!termMass || termMass === '') {
      showAjaxModal_alert('<strong>Target Term Not Selected!</strong><br><br>Please select the <strong>Term</strong> (at the top of the form) where you want to create the invoices.<br><br>This is the destination term for the cloned invoices.', 'error');
      $('#term_mass').focus();
      return false;
    }
    
    if(!yearMass || yearMass === '') {
      showAjaxModal_alert('<strong>Target Year Not Selected!</strong><br><br>Please select the <strong>Year</strong> (at the top of the form) where you want to create the invoices.<br><br>This is the destination year for the cloned invoices.', 'error');
      $('#year_mass').focus();
      return false;
    }

    // STRONG VALIDATION: Validate SOURCE term and year (what we're cloning FROM)
    const termClone = $('#term_clone').val();
    const yearClone = $('#year_clone').val();
    
    if(!termClone || termClone === '') {
      showAjaxModal_alert('<strong>Source Term Not Selected!</strong><br><br>Please select the <strong>Term</strong> you want to clone from (in the clone section below).<br><br>This is the source term where the original invoices exist.', 'error');
      $('#term_clone').focus();
      return false;
    }
    
    if(!yearClone || yearClone === '') {
      showAjaxModal_alert('<strong>Source Year Not Selected!</strong><br><br>Please select the <strong>Year</strong> you want to clone from (in the clone section below).<br><br>This is the source year where the original invoices exist.', 'error');
      $('#year_clone').focus();
      return false;
    }
    
    // Validate that source and target are different
    if(termClone === termMass && yearClone === yearMass) {
      showAjaxModal_alert('<strong>Source and Target Cannot Be the Same!</strong><br><br>You cannot clone invoices from Term ' + termClone + ', Year ' + yearClone + ' to the same period.<br><br>Please select a different target term/year at the top of the form.', 'error');
      return false;
    }

    // Validate that at least one student is selected
    const selectedStudents = $('input[name="student_id[]"]:checked');
    if(selectedStudents.length === 0) {
      showAjaxModal_alert('<strong>No Students Selected!</strong><br><br>Please select at least one student to create invoices for.', 'error');
      return false;
    }

    // Get selected class(es)
    const selectedClasses = $('#class_id2').val();
    if(!selectedClasses || selectedClasses.length === 0) {
      showAjaxModal_alert('<strong>No Class Selected!</strong><br><br>Please select at least one class!', 'error');
      return false;
    }

    if(selectedClasses.length > 1) {
      showAjaxModal_alert('<strong>Multiple Classes Selected!</strong><br><br>You can only clone for one class at a time. Please select a single class.', 'error');
      return false;
    }

    // Show loading indicator
    const $btn = $(this);
    const originalText = $btn.html();
    $btn.html('<i class="fa fa-spinner fa-spin"></i> Loading Preview...').prop('disabled', true);

    const formElement = document.getElementById('mass_invoice_form');
    const formData = new FormData(formElement);

    // First, get preview of bills to be cloned
    $.ajax({
      url: '<?php echo site_url('admin/preview_clone_bills') ?>',
      type: 'post',
      dataType: 'json',
      data: formData,
      cache: false,
      contentType: false,
      processData: false,
      timeout: 60000
    })
    .done(function(response) {
      // Re-enable button
      $btn.html(originalText).prop('disabled', false);
      
      if(!response.success) {
        showAjaxModal_alert(response.message || 'Failed to load bill preview', 'error');
        return;
      }
      
      if(!response.data || response.data.length === 0) {
        showAjaxModal_alert('No bills found to clone for the selected term/year.<br><br><strong>Note:</strong> Previous bills are fetched from the Bill History. Make sure invoices were created for the selected term/year and class.', 'warning');
        return;
      }
      
      // Show preview modal
      showCloneBillsPreview(response);
    })
    .fail(function(xhr, status, error) {
      // Re-enable button
      $btn.html(originalText).prop('disabled', false);
      
      let errorMessage = 'An error occurred while loading bill preview.';
      
      if(status === 'timeout') {
        errorMessage = 'Request timeout. Please try again.';
      } else if(xhr.responseText) {
        errorMessage += '<br><br>Error: ' + xhr.responseText;
      }
      
      showAjaxModal_alert(errorMessage, 'error');
      console.error('Preview error:', {status, error, response: xhr.responseText});
    });
    
    return false;
  });

  // Show clone bills preview modal with modern UI
  function showCloneBillsPreview(response) {
    const data = response.data;
    const selectedTerm = response.selected_term;
    const selectedYear = response.selected_year;
    
    // First, check if bills already exist for the selected period
    $.ajax({
      url: '<?php echo site_url('admin/check_existing_bills_for_clone'); ?>',
      type: 'POST',
      dataType: 'json',
      data: {
        student_id: data.map(s => s.student_id),
        term: selectedTerm,
        year: selectedYear
      },
      success: function(existingBillsResponse) {
        // Render the modal with existing bills data
        renderCloneBillsModal(response, existingBillsResponse);
      },
      error: function() {
        // If check fails, continue without warning
        renderCloneBillsModal(response, { exists: false, bills: [] });
      }
    });
  }

  function renderCloneBillsModal(response, existingBillsData) {
    const data = response.data;
    
    // Extract unique bill items
    let uniqueBillItems = [];
    let billItemsMap = {};
    
    if(data.length > 0) {
      data[0].bill_items.forEach((item, index) => {
        const key = item.bill_item_id;
        if(!billItemsMap[key]) {
          billItemsMap[key] = { ...item, index: index };
          uniqueBillItems.push(item);
        }
      });
    }
    
    const totalAmount = uniqueBillItems.reduce((sum, item) => sum + parseFloat(item.amount), 0);
    
    // Build modern modal HTML with existing bills warning
    let modalHTML = buildModernModalHTML(data, uniqueBillItems, totalAmount, response, existingBillsData);
    
    $('#cloneBillsPreviewModal').remove();
    $('body').append(modalHTML);
    $('#cloneBillsPreviewModal').modal('show');
    
    window.cloneBillsData = response;
    
    // Event handlers
    $(document).on('input', '.bill-amount', updateGrandTotal);
    $(document).on('click', '.delete-bill-item', handleDeleteItem);
    $(document).on('click', '#addNewBillItem', showAddBillItemDialog);
    $(document).on('click', '#confirmCloneBills', createClonedInvoices);
  }

  function buildModernModalHTML(data, uniqueBillItems, totalAmount, response, existingBillsData) {
    return `
      ${getModernModalStyles()}
      <div class="modal fade" id="cloneBillsPreviewModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
          <div class="modal-content modern-modal">
            ${getModalHeader()}
            ${getModalBody(data, uniqueBillItems, totalAmount, response, existingBillsData)}
            ${getModalFooter(data.length)}
          </div>
        </div>
      </div>`;
  }

  function getModernModalStyles() {
    return `<style>
      #cloneBillsPreviewModal .modal-dialog { max-width: 1600px !important; width: 95% !important; margin: 1.75rem auto; }
      #cloneBillsPreviewModal .modern-modal { border: none; border-radius: 16px; overflow: hidden; box-shadow: 0 20px 60px rgba(0,0,0,0.3); }
      #cloneBillsPreviewModal .modern-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 18px 24px; }
      #cloneBillsPreviewModal .modern-body { padding: 20px; background: #f8f9fa; max-height: 75vh; overflow-y: auto; }
      #cloneBillsPreviewModal .info-card { background: white; border-radius: 10px; padding: 16px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); margin-bottom: 16px; border-left: 4px solid #3498db; }
      #cloneBillsPreviewModal .badge-modern { padding: 2px 8px; border-radius: 10px; font-size: 10px; font-weight: 600; }
      #cloneBillsPreviewModal .badge-info { background: #e3f2fd; color: #1976d2; }
      #cloneBillsPreviewModal .badge-secondary { background: #f5f5f5; color: #616161; }
      #cloneBillsPreviewModal .badge-primary { background: #e8eaf6; color: #5e35b1; }
      #cloneBillsPreviewModal .bills-card { background: white; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); overflow: hidden; }
      #cloneBillsPreviewModal .bills-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 14px 18px; display: flex; justify-content: space-between; align-items: center; }
      #cloneBillsPreviewModal .total-badge { background: rgba(255,255,255,0.2); padding: 5px 12px; border-radius: 18px; font-size: 14px; font-weight: 700; backdrop-filter: blur(10px); }
      #cloneBillsPreviewModal .modern-table { width: 100%; border-collapse: separate; border-spacing: 0; }
      #cloneBillsPreviewModal .modern-table thead th { background: #f8f9fa; padding: 10px; font-weight: 600; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; color: #495057; border-bottom: 2px solid #dee2e6; }
      #cloneBillsPreviewModal .modern-table tbody tr { transition: all 0.2s; }
      #cloneBillsPreviewModal .modern-table tbody tr:hover { background: #f8f9fa; }
      #cloneBillsPreviewModal .modern-table tbody td { padding: 10px; border-bottom: 1px solid #f0f0f0; vertical-align: middle; font-size: 11px; }
      #cloneBillsPreviewModal .row-number { width: 32px; height: 32px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 12px; }
      #cloneBillsPreviewModal .amount-input { border: 2px solid #e9ecef; border-radius: 6px; padding: 6px 10px; font-size: 12px; font-weight: 600; color: #2c3e50; transition: all 0.3s; width: 100%; }
      #cloneBillsPreviewModal .amount-input:focus { border-color: #667eea; box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1); outline: none; }
      #cloneBillsPreviewModal .btn-delete { background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%); color: white; border: none; padding: 6px 10px; border-radius: 6px; font-weight: 600; transition: all 0.3s; cursor: pointer; font-size: 11px; }
      #cloneBillsPreviewModal .btn-delete:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(238, 90, 111, 0.4); }
      #cloneBillsPreviewModal .btn-add-item { background: linear-gradient(135deg, #51cf66 0%, #40c057 100%); color: white; border: none; padding: 8px 16px; border-radius: 8px; font-weight: 600; font-size: 12px; transition: all 0.3s; cursor: pointer; margin-top: 10px; }
      #cloneBillsPreviewModal .btn-add-item:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(64, 192, 87, 0.4); }
      #cloneBillsPreviewModal .modern-footer { background: white; padding: 16px 20px; display: flex; justify-content: space-between; align-items: center; border-top: 1px solid #e9ecef; }
      #cloneBillsPreviewModal .btn-modern { padding: 10px 24px; border-radius: 8px; font-weight: 600; font-size: 13px; border: none; transition: all 0.3s; cursor: pointer; }
      #cloneBillsPreviewModal .btn-cancel { background: #e9ecef; color: #495057; }
      #cloneBillsPreviewModal .btn-cancel:hover { background: #dee2e6; transform: translateY(-2px); }
      #cloneBillsPreviewModal .btn-confirm { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
      #cloneBillsPreviewModal .btn-confirm:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4); }
      #cloneBillsPreviewModal .select2-container { width: 100% !important; }
      #cloneBillsPreviewModal .select2-selection { height: 36px !important; border: 2px solid #e9ecef !important; border-radius: 6px !important; }
      #cloneBillsPreviewModal .select2-selection__rendered { line-height: 32px !important; font-size: 11px !important; }
    </style>`;
  }

  function getModalHeader() {
    return `<div class="modern-header">
      <div style="display: flex; justify-content: space-between; align-items: center;">
        <h3 style="margin: 0; font-weight: 700; font-size: 18px; color: white;">
          <i class="fa fa-eye" style="margin-right: 8px;"></i>Review Bills Before Creating
        </h3>
        <button type="button" class="close" data-dismiss="modal" style="color: white; opacity: 1; font-size: 26px; font-weight: 300;">
          <span>&times;</span>
        </button>
      </div>
    </div>`;
  }

  function getModalBody(data, uniqueBillItems, totalAmount, response, existingBillsData) {
    return `<div class="modern-body">
      ${getInfoCard(data.length, response)}
      ${existingBillsData && existingBillsData.exists ? getExistingBillsWarning(existingBillsData, response) : ''}
      ${getBillsCard(uniqueBillItems, totalAmount)}
    </div>`;
  }

  function getExistingBillsWarning(existingBillsData, response) {
    const term = $('#term_mass option[value="' + response.selected_term + '"]').text();
    const year = response.selected_year;
    
    let billsTableRows = '';
    existingBillsData.bills.forEach((bill, index) => {
      const statusBadge = bill.has_payment 
        ? '<span style="color: #e74c3c; font-weight: 600; font-size: 10px;"><i class="fa fa-exclamation-circle"></i> Has Payments</span>'
        : '<span style="color: #95a5a6; font-size: 10px;"><i class="fa fa-check-circle"></i> No Payments</span>';
      
      billsTableRows += `
        <tr style="background: #fffbf0;">
          <td style="text-align: center; font-weight: 600;">${index + 1}</td>
          <td><strong style="color: #2c3e50;">${bill.title}</strong></td>
          <td style="text-align: right; font-weight: 600; color: #2c3e50;">${parseFloat(bill.amount).toFixed(2)}</td>
          <td style="text-align: center;">${statusBadge}</td>
        </tr>
      `;
    });
    
    return `
      <div style="background: #fff3cd; border-left: 4px solid #ff9800; border-radius: 10px; padding: 16px; margin-bottom: 16px; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
        <div style="display: flex; align-items: flex-start; gap: 12px; margin-bottom: 12px;">
          <i class="fa fa-exclamation-triangle" style="color: #ff9800; font-size: 24px; margin-top: 2px;"></i>
          <div style="flex: 1;">
            <h5 style="margin: 0 0 8px 0; font-weight: 700; color: #856404; font-size: 14px;">
              Existing Bills Found for ${term} - ${year}
            </h5>
            <p style="margin: 0 0 12px 0; color: #856404; font-size: 12px; line-height: 1.6;">
              <strong>Warning:</strong> Bills already exist for the selected students in this term/year. 
              The new bill will <strong>replace</strong> the existing one.
            </p>
            <p style="margin: 0 0 8px 0; color: #856404; font-size: 11px; font-weight: 600;">
              Current Existing Items:
            </p>
            <table style="width: 100%; border-collapse: collapse; background: white; border-radius: 6px; overflow: hidden;">
              <thead>
                <tr style="background: #ffeaa7;">
                  <th style="padding: 8px; text-align: center; font-size: 11px; font-weight: 700; color: #2c3e50; width: 50px;">#</th>
                  <th style="padding: 8px; text-align: left; font-size: 11px; font-weight: 700; color: #2c3e50;">Item Title</th>
                  <th style="padding: 8px; text-align: right; font-size: 11px; font-weight: 700; color: #2c3e50; width: 120px;">Amount (GHC)</th>
                  <th style="padding: 8px; text-align: center; font-size: 11px; font-weight: 700; color: #2c3e50; width: 120px;">Status</th>
                </tr>
              </thead>
              <tbody>
                ${billsTableRows}
              </tbody>
            </table>
            <p style="margin: 12px 0 0 0; font-size: 11px; color: #d35400; line-height: 1.5;">
              <i class="fa fa-info-circle"></i> 
              <strong>Note:</strong> Items with payments will be updated. Items without payments that are not in the new bill will be deleted.
            </p>
          </div>
        </div>
      </div>
    `;
  }

  function getInfoCard(studentCount, response) {
    return `<div class="info-card">
      <div style="display: flex; gap: 12px;">
        <div style="width: 36px; height: 36px; background: #e3f2fd; border-radius: 10px; display: flex; align-items: center; justify-content: center;">
          <i class="fa fa-info-circle" style="color: #1976d2; font-size: 18px;"></i>
        </div>
        <div>
          <h5 style="margin: 0 0 5px 0; font-weight: 600; color: #2c3e50; font-size: 13px;">Review and Edit Invoice Items</h5>
          <p style="margin: 0; color: #6c757d; line-height: 1.4; font-size: 11px;">
            These bill items will be applied to <strong style="color: #667eea;">${studentCount} student(s)</strong>. 
            Edit amounts, delete items, or add new ones before creating invoices.
          </p>
          <div style="margin-top: 8px; padding-top: 8px; border-top: 1px solid #e9ecef; font-size: 11px;">
            <span style="color: #6c757d;">Cloning from:</span> 
            <strong style="color: #495057;">Term ${response.source_term}, ${response.source_year}</strong>
            <span style="color: #6c757d; margin: 0 5px;">→</span>
            <strong style="color: #667eea;">Term ${response.selected_term}, ${response.selected_year}</strong>
          </div>
        </div>
      </div>
    </div>`;
  }

  // Student list section removed as requested

  function getBillsCard(uniqueBillItems, totalAmount) {
    return `<div class="bills-card">
      <div class="bills-header">
        <div style="display: flex; align-items: center; gap: 8px;">
          <i class="fa fa-file-invoice" style="font-size: 18px;"></i>
          <h5 style="margin: 0; font-weight: 600; font-size: 15px; color: white;">Bill Items</h5>
        </div>
        <div class="total-badge">Total: GHC <span id="grand-total">${totalAmount.toFixed(2)}</span></div>
      </div>
      <table class="modern-table">
        <thead><tr>
          <th style="width: 50px; text-align: center;">#</th>
          <th style="width: 30%;">Bill Item</th>
          <th style="width: 18%;">Amount (GHC)</th>
          <th style="width: 32%;">Description</th>
          <th style="width: 90px; text-align: center;">Action</th>
        </tr></thead>
        <tbody id="billItemsBody">
          ${uniqueBillItems.map((item, index) => `<tr class="bill-item-row" data-item-id="${item.bill_item_id}" data-index="${index}">
            <td style="text-align: center;"><div class="row-number">${index + 1}</div></td>
            <td><strong style="color: #2c3e50; font-size: 12px;">${item.title}</strong></td>
            <td><input type="number" step="0.01" min="0" class="amount-input bill-amount" value="${item.amount}" data-index="${index}"></td>
            <td><span style="color: #6c757d; font-size: 11px;">${item.description || '-'}</span></td>
            <td style="text-align: center;"><button type="button" class="btn-delete delete-bill-item" data-index="${index}"><i class="fa fa-trash"></i></button></td>
          </tr>`).join('')}
        </tbody>
      </table>
      <div style="padding: 20px;">
        <button type="button" class="btn-add-item" id="addNewBillItem">
          <i class="fa fa-plus" style="margin-right: 6px;"></i>Add Bill Item
        </button>
      </div>
    </div>`;
  }

  function getModalFooter(studentCount) {
    return `<div class="modern-footer">
      <button type="button" class="btn-modern btn-cancel text-right" data-dismiss="modal">
        <i class="fa fa-times" style="margin-right: 6px;"></i>Cancel
      </button>
      <button type="button" class="btn-modern btn-confirm" id="confirmCloneBills">
        <i class="fa fa-check" style="margin-right: 6px;"></i>Create Invoices for ${studentCount} Student(s)
      </button>
    </div>`;
  }

  function handleDeleteItem() {
    const $row = $(this).closest('tr');
    const hasSelect = $row.find('.bill-item-select').length > 0;
    
    showConfirmModal(
      'Confirm Delete',
      'Are you sure you want to remove this bill item?',
      function() {
        $row.remove();
        updateGrandTotal();
        renumberRows();
        
        // If the deleted row had an unselected dropdown, re-enable add button
        if(hasSelect) {
          $('#addNewBillItem').prop('disabled', false).css('opacity', '1').css('cursor', 'pointer');
        }
      },
      '<?php echo get_phrase('delete'); ?>',
      'danger'
    );
  }

  function createClonedInvoices() {
    const $btn = $('#confirmCloneBills');
    const originalText = $btn.html();
    $btn.html('<i class="fa fa-spinner fa-spin"></i> Creating...').prop('disabled', true);
    
    // Get form data
    const data = window.cloneBillsData.data;
    const selectedYear = window.cloneBillsData.selected_year;
    const selectedTerm = window.cloneBillsData.selected_term;
    
    const formElement = document.getElementById('mass_invoice_form');
    const formData = new FormData(formElement);
    
    // IMPORTANT: Only include student IDs that are CHECKED in the form
    // Clear any existing student_id[] entries
    formData.delete('student_id[]');
    
    // Get only checked students from the actual form checkboxes
    const checkedStudents = document.querySelectorAll('input[name="student_id[]"]:checked');
    if(checkedStudents.length === 0) {
      $btn.html(originalText).prop('disabled', false);
      showAjaxModal_alert('No students selected! Please check at least one student.', 'error');
      return;
    }
    
    // Add only the checked students
    checkedStudents.forEach(function(checkbox) {
      formData.append('student_id[]', checkbox.value);
    });
    
    // Get selected class and other settings
    const selectedClass = $('#class_id2').val()[0]; // Get first selected class
    const addToBillHistory = $('#add_to_class_bill').is(':checked') ? 1 : 0;
    const studentsCategory = $('input[name="students_category"]:checked').val() || 'both';
    
    // Build bill items from the preview table (single list for all students)
    let billItemsParam = [];
    let billItemsData = {};
    
    $('#billItemsBody tr.bill-item-row').each(function(index) {
      const $row = $(this);
      const title = $row.find('td:eq(1)').text().trim();
      const amount = $row.find('.bill-amount').val();
      const description = $row.find('td:eq(3)').text().trim();
      
      // Generate unique ID for this item
      const uniqueId = ($row.data('item-id') || 'new') + '_' + Date.now() + '_' + index;
      billItemsParam.push(uniqueId);
      
      billItemsData[uniqueId + '_title'] = title;
      billItemsData[uniqueId + '_amount'] = parseFloat(amount);
      billItemsData[uniqueId + '_description'] = description !== '-' ? description : '';
    });
    
    // Validate that we have bill items
    if(billItemsParam.length === 0) {
      $btn.html(originalText).prop('disabled', false);
      showAjaxModal_alert('No bill items to create. Please add at least one bill item.', 'error');
      return;
    }
    
    // Build URL
    const billItemsString = billItemsParam.join('-');
    let url = '<?php echo site_url('admin/mass_invoice_create/create') ?>/' + billItemsString;
    url += '?year=' + encodeURIComponent(selectedYear);
    url += '&term=' + encodeURIComponent(selectedTerm);
    url += '&class_id[]=' + encodeURIComponent(selectedClass);
    url += '&add_to_class_bill=' + addToBillHistory;
    url += '&students_category=' + encodeURIComponent(studentsCategory);
    url += '&date=' + encodeURIComponent($('#date_mass').val() || '<?php echo date('m/d/Y'); ?>');
    
    // Add bill item data to formData
    for(let key in billItemsData) {
      formData.append(key, billItemsData[key]);
    }
    
    // Call mass_invoice_create method with discount support
    $.ajax({
      url: url,
      type: 'post',
      dataType: 'json',
      data: formData,
      cache: false,
      contentType: false,
      processData: false,
      timeout: 60000
    })
    .done(function(response) {
      $btn.html(originalText).prop('disabled', false);
      $('#cloneBillsPreviewModal').modal('hide');
      
      if(response.status == 'success') {
        showAjaxModal_alert(response.message, 'success', false, false);
        // Reload immediately without hiding the modal

        setInterval(() => {
          location.reload();
        }, 2000);
        
      } else {
        showAjaxModal_alert(response.message, 'info');
        setInterval(() => {
          location.reload();
        }, 2000);
      }
    })
    .fail(function(xhr, status, error) {
      $btn.html(originalText).prop('disabled', false);
      let errorMsg = 'An error occurred while creating invoices.';
      if(xhr.responseText) {
        errorMsg += '<br><br>Error: ' + xhr.responseText;
      }
      showAjaxModal_alert(errorMsg, 'error');
      console.error('Create error:', {status, error, response: xhr.responseText});
    });
  }

  function updateGrandTotal() {
    let total = 0;
    $('#billItemsBody .bill-amount').each(function() {
      total += parseFloat($(this).val()) || 0;
    });
    $('#grand-total').text(total.toFixed(2));
  }

  function renumberRows() {
    $('#billItemsBody tr').each(function(index) {
      $(this).find('.row-number').text(index + 1);
    });
  }

  function showAddBillItemDialog() {
    // Check if there's an unselected row
    let hasUnselectedRow = false;
    $('#billItemsBody tr.bill-item-row').each(function() {
      const $row = $(this);
      const hasSelect = $row.find('.bill-item-select').length > 0;
      if(hasSelect) {
        hasUnselectedRow = true;
        return false; // break
      }
    });
    
    if(hasUnselectedRow) {
      showAjaxModal_alert('Please select a billing item first before adding a new row', 'error');
      return;
    }
    
    const newIndex = $('#billItemsBody tr').length;
    const uniqueId = 'new_' + Date.now() + '_' + newIndex;
    
    // Get already selected bill item titles
    const selectedTitles = [];
    $('#billItemsBody tr.bill-item-row').each(function() {
      const title = $(this).find('td:eq(1)').text().trim();
      if(title && title !== '') {
        selectedTitles.push(title);
      }
    });
    
    // Get selected class IDs
    const classIds = $('#class_id2').val() || [];
    
    // Fetch bill items from server
    $.ajax({
      url: '<?php echo site_url('admin/get_bill_items_json'); ?>',
      type: 'POST',
      data: { class_ids: classIds },
      dataType: 'json',
      success: function(billItems) {
        // Filter out already selected items
        const availableItems = billItems.filter(item => !selectedTitles.includes(item.title));
        
        if(availableItems.length === 0) {
          showAjaxModal_alert('All available bill items have been added', 'warning');
          return;
        }
        
        const newRow = `
          <tr class="bill-item-row" data-item-id="${uniqueId}" data-index="${newIndex}">
            <td style="text-align: center;"><div class="row-number">${newIndex + 1}</div></td>
            <td>
              <select class="bill-item-select" data-index="${newIndex}" style="width: 100%; padding: 8px; border: 2px solid #e9ecef; border-radius: 6px; font-size: 12px;">
                <option value="">Select Bill Item</option>
                ${availableItems.map(item => `<option value="${item.id}" data-amount="${item.amount}" data-desc="${item.description || ''}" data-title="${item.title}">${item.title}</option>`).join('')}
              </select>
            </td>
            <td><input type="number" step="0.01" min="0" class="amount-input bill-amount" value="0" data-index="${newIndex}"></td>
            <td><span class="bill-desc" style="color: #6c757d; font-size: 11px;">-</span></td>
            <td style="text-align: center;"><button type="button" class="btn-delete delete-bill-item" data-index="${newIndex}"><i class="fa fa-trash"></i></button></td>
          </tr>`;
        
        $('#billItemsBody').append(newRow);
        
        // Disable add button until selection is made
        $('#addNewBillItem').prop('disabled', true).css('opacity', '0.5').css('cursor', 'not-allowed');
        
        // Handle bill item selection
        $(`.bill-item-select[data-index="${newIndex}"]`).on('change', function() {
          const $selected = $(this).find('option:selected');
          const amount = $selected.data('amount');
          const desc = $selected.data('desc');
          const title = $selected.data('title');
          
          if(!title || title === '') {
            return;
          }
          
          $(this).closest('tr').find('.bill-amount').val(amount);
          $(this).closest('tr').find('.bill-desc').text(desc || '-');
          
          // Replace select with title text
          $(this).replaceWith(`<strong style="color: #2c3e50; font-size: 12px;">${title}</strong>`);
          
          // Re-enable add button
          $('#addNewBillItem').prop('disabled', false).css('opacity', '1').css('cursor', 'pointer');
          
          updateGrandTotal();
        });
        
        updateGrandTotal();
      },
      error: function() {
        showAjaxModal_alert('Failed to load bill items. Please try again.', 'error');
      }
    });
  }

  function createClonedInvoices() {
    const $btn = $('#confirmCloneBills');
    const originalText = $btn.html();
    $btn.html('<i class="fa fa-spinner fa-spin"></i> Creating...').prop('disabled', true);
    
    // Get form data
    const data = window.cloneBillsData.data;
    const selectedYear = window.cloneBillsData.selected_year;
    const selectedTerm = window.cloneBillsData.selected_term;
    
    const formElement = document.getElementById('mass_invoice_form');
    const formData = new FormData(formElement);
    
    // IMPORTANT: Only include student IDs that are CHECKED in the form
    // Clear any existing student_id[] entries
    formData.delete('student_id[]');
    
    // Get only checked students from the actual form checkboxes
    const checkedStudents = document.querySelectorAll('input[name="student_id[]"]:checked');
    if(checkedStudents.length === 0) {
      $btn.html(originalText).prop('disabled', false);
      showAjaxModal_alert('No students selected! Please check at least one student.', 'error');
      return;
    }
    
    // Add only the checked students
    checkedStudents.forEach(function(checkbox) {
      formData.append('student_id[]', checkbox.value);
    });
    
    // Get selected class and other settings
    const selectedClass = $('#class_id2').val()[0]; // Get first selected class
    const addToBillHistory = $('#add_to_class_bill').is(':checked') ? 1 : 0;
    const studentsCategory = $('input[name="students_category"]:checked').val() || 'both';
    
    // Build bill items from the preview table (single list for all students)
    let billItemsParam = [];
    let billItemsData = {};
    
    $('#billItemsBody tr.bill-item-row').each(function(index) {
      const $row = $(this);
      const title = $row.find('td:eq(1)').text().trim();
      const amount = $row.find('.bill-amount').val();
      const description = $row.find('td:eq(3)').text().trim();
      
      // Generate unique ID for this item
      const uniqueId = ($row.data('item-id') || 'new') + '_' + Date.now() + '_' + index;
      billItemsParam.push(uniqueId);
      
      billItemsData[uniqueId + '_title'] = title;
      billItemsData[uniqueId + '_amount'] = parseFloat(amount);
      billItemsData[uniqueId + '_description'] = description !== '-' ? description : '';
    });
    
    // Validate that we have bill items
    if(billItemsParam.length === 0) {
      $btn.html(originalText).prop('disabled', false);
      showAjaxModal_alert('No bill items to create. Please add at least one bill item.', 'error');
      return;
    }
    
    // Build URL
    const billItemsString = billItemsParam.join('-');
    let url = '<?php echo site_url('admin/mass_invoice_create/create') ?>/' + billItemsString;
    url += '?year=' + encodeURIComponent(selectedYear);
    url += '&term=' + encodeURIComponent(selectedTerm);
    url += '&class_id[]=' + encodeURIComponent(selectedClass);
    url += '&add_to_class_bill=' + addToBillHistory;
    url += '&students_category=' + encodeURIComponent(studentsCategory);
    url += '&date=' + encodeURIComponent($('#date_mass').val() || '<?php echo date('m/d/Y'); ?>');
    
    // Add bill item data to formData
    for(let key in billItemsData) {
      formData.append(key, billItemsData[key]);
    }
    
    // Call mass_invoice_create method with discount support
    $.ajax({
      url: url,
      type: 'post',
      dataType: 'json',
      data: formData,
      cache: false,
      contentType: false,
      processData: false,
      timeout: 60000
    })
    .done(function(response) {
      $btn.html(originalText).prop('disabled', false);
      $('#cloneBillsPreviewModal').modal('hide');
      
      if(response.status == 'success') {
        showAjaxModal_alert(response.message, 'success', false, false);
        // Reload immediately without hiding the modal
        setInterval(() => {
          location.reload();
        }, 2000);
      } else {
        showAjaxModal_alert(response.message, 'info');
        setInterval(() => {
          location.reload();
        }, 2000);
      }
    })
    .fail(function(xhr, status, error) {
      $btn.html(originalText).prop('disabled', false);
      let errorMsg = 'An error occurred while creating invoices.';
      if(xhr.responseText) {
        errorMsg += '<br><br>Error: ' + xhr.responseText;
      }
      showAjaxModal_alert(errorMsg, 'error');
      console.error('Create error:', {status, error, response: xhr.responseText});
    });
  }

  // REPLACEMENT FOR showInvoiceConfirmationModal function in student_payment.php
// Replace the entire function starting from line ~4066

function showInvoiceConfirmationModal(item_ids, callback) {
  // Collect invoice details
  const term = $('#term_mass option:selected').text();
  const year = $('#year_mass').val();
  const date = $('#date_mass').val();
  const classes = $('#class_id2').val() || [];
  const filterType = $('#students_category').val(); // 'all', 'boarding', 'day', 'class'
  
  // Get student category properly
  let studentCategory = $('#students_category option:selected').text().trim();
  if(filterType === 'class' && classes.length > 0) {
    // If "By Class" is selected, show the selected class names
    let classNames = [];
    $('#class_id2 option:selected').each(function() {
      classNames.push($(this).text().trim());
    });
    studentCategory = 'By Class: ' + classNames.join(', ');
  }
  
  const addToBillHistory = $('#add_to_class_bill').is(':checked');
  
  // Count selected students
  const studentCount = $('input[name="student_id[]"]:checked').length;
  
  // Get bill item titles and amounts FROM THE FORM INPUTS (not database)
  let billItemTitles = [];
  let billItemsSimple = []; // For non-class view
  item_ids.forEach(function(id) {
    const title = $('#' + id + '_title').val();
    const amount = parseFloat($('#' + id + '_amount').val()) || 0;
    if(title) {
      billItemTitles.push({title: title, amount: amount}); // Send both title AND amount
      billItemsSimple.push({title: title, amount: amount});
    }
  });
  
  // If "By Class" is selected, get per-class breakdown via AJAX
  if(filterType === 'class' && classes.length > 0) {
    $.ajax({
      url: '<?php echo site_url('admin/get_invoice_preview_data'); ?>',
      type: 'POST',
      data: {
        class_ids: classes,
        bill_items: billItemTitles
      },
      dataType: 'json',
      success: function(response) {
        if(response.success) {
          displayConfirmationModal(response.classes, term, year, date, studentCategory, studentCount, addToBillHistory, callback, 'class');
        } else {
          showAjaxModal_alert('Failed to load invoice preview', 'error');
        }
      },
      error: function() {
        showAjaxModal_alert('Error loading invoice preview', 'error');
      }
    });
  } else {
    // For "All Students", "Boarding", "Day" - show simple list
    displayConfirmationModal(billItemsSimple, term, year, date, studentCategory, studentCount, addToBillHistory, callback, 'simple');
  }
}

function displayConfirmationModal(data, term, year, date, studentCategory, studentCount, addToBillHistory, callback, viewMode) {
  let billItemsHTML = '';
  
  if(viewMode === 'class') {
    // Per-class breakdown for "By Class" filter
    let classesData = data;
    classesData.forEach(function(classInfo) {
      let classTotal = 0;
      
      billItemsHTML += `
        <div class="info-section">
          <h4><i class="fa fa-graduation-cap" style="margin-right: 8px; color: #667eea;"></i>${classInfo.class_name}</h4>
          <table class="items-table">
            <thead>
              <tr>
                <th style="width: 50px;">#</th>
                <th>Item</th>
                <th style="text-align: right; width: 120px;">Amount (GHC)</th>
              </tr>
            </thead>
            <tbody>`;
      
      classInfo.items.forEach(function(item, index) {
        classTotal += item.amount;
        billItemsHTML += `
              <tr>
                <td>${index + 1}</td>
                <td><strong>${item.title}</strong></td>
                <td style="text-align: right; font-weight: 600;">${item.amount.toFixed(2)}</td>
              </tr>`;
      });
      
      billItemsHTML += `
              <tr class="total-row">
                <td colspan="2" style="text-align: right; padding: 14px 10px;">CLASS TOTAL:</td>
                <td style="text-align: right; padding: 14px 10px; color: #667eea;">GHC ${classTotal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
              </tr>
            </tbody>
          </table>
        </div>`;
    });
    
    billItemsHTML += `
      <div style="background: #e3f2fd; border-left: 4px solid #2196f3; padding: 12px; border-radius: 8px; margin-top: 15px;">
        <div style="display: flex; align-items: flex-start;">
          <i class="fa fa-info-circle" style="color: #2196f3; font-size: 18px; margin-right: 10px; margin-top: 2px;"></i>
          <div style="font-size: 12px; color: #1565c0; line-height: 1.6;">
            <strong>Note:</strong> Each student will be billed only the items applicable to their class. 
            Discounts and scholarships will be applied individually during invoice creation.
          </div>
        </div>
      </div>`;
      
  } else {
    // Simple list for "All Students", "Boarding", "Day"
    let billItems = data;
    let grandTotal = 0;
    
    billItemsHTML += `
      <div class="info-section">
        <h4><i class="fa fa-file-invoice-dollar" style="margin-right: 8px; color: #667eea;"></i>Selected Bill Items</h4>
        <table class="items-table">
          <thead>
            <tr>
              <th style="width: 50px;">#</th>
              <th>Item</th>
              <th style="text-align: right; width: 120px;">Amount (GHC)</th>
            </tr>
          </thead>
          <tbody>`;
    
    billItems.forEach(function(item, index) {
      grandTotal += item.amount;
      billItemsHTML += `
            <tr>
              <td>${index + 1}</td>
              <td><strong>${item.title}</strong></td>
              <td style="text-align: right; font-weight: 600;">${item.amount.toFixed(2)}</td>
            </tr>`;
    });
    
    billItemsHTML += `
            <tr class="total-row">
              <td colspan="2" style="text-align: right; padding: 14px 10px;">TOTAL (Per Student):</td>
              <td style="text-align: right; padding: 14px 10px; color: #667eea;">GHC ${grandTotal.toFixed(2)}</td>
            </tr>
          </tbody>
        </table>
      </div>`;
    
    billItemsHTML += `
      <div style="background: #fff3cd; border-left: 4px solid #ffc107; padding: 12px; border-radius: 8px; margin-top: 15px;">
        <div style="display: flex; align-items: flex-start;">
          <i class="fa fa-exclamation-triangle" style="color: #ff9800; font-size: 18px; margin-right: 10px; margin-top: 2px;"></i>
          <div style="font-size: 12px; color: #856404; line-height: 1.6;">
            <strong>Note:</strong> Some items may not apply to all students based on their class category or specific class assignments. 
            Discounts and scholarships will be applied individually during invoice creation.
          </div>
        </div>
      </div>`;
  }
  
  // Build modal HTML
  const modalHTML = `
    <style>
      #invoiceConfirmModal .modal-dialog { max-width: 900px; width: 90%; margin: 1.75rem auto; }
      #invoiceConfirmModal .modern-modal { border: none; border-radius: 16px; overflow: hidden; box-shadow: 0 20px 60px rgba(0,0,0,0.3); }
      #invoiceConfirmModal .modern-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 20px 28px; }
      #invoiceConfirmModal .modern-body { padding: 28px; background: #f8f9fa; max-height: 70vh; overflow-y: auto; }
      #invoiceConfirmModal .info-section { background: white; border-radius: 12px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
      #invoiceConfirmModal .info-section h4 { margin: 0 0 16px 0; font-size: 16px; font-weight: 700; color: #2c3e50; border-bottom: 2px solid #667eea; padding-bottom: 10px; }
      #invoiceConfirmModal .info-row { display: flex; padding: 10px 0; border-bottom: 1px solid #f0f0f0; }
      #invoiceConfirmModal .info-row:last-child { border-bottom: none; }
      #invoiceConfirmModal .info-label { font-weight: 600; color: #6c757d; min-width: 180px; font-size: 13px; }
      #invoiceConfirmModal .info-value { color: #2c3e50; font-weight: 600; font-size: 13px; }
      #invoiceConfirmModal .highlight { color: #667eea; font-weight: 700; font-size: 15px; }
      #invoiceConfirmModal .badge-custom { background: #e8eaf6; color: #5e35b1; padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: 600; margin: 2px; display: inline-block; }
      #invoiceConfirmModal .items-table { width: 100%; border-collapse: collapse; margin-top: 12px; }
      #invoiceConfirmModal .items-table th { background: #f8f9fa; padding: 10px; text-align: left; font-size: 12px; font-weight: 600; color: #495057; border-bottom: 2px solid #dee2e6; }
      #invoiceConfirmModal .items-table td { padding: 10px; font-size: 12px; border-bottom: 1px solid #f0f0f0; }
      #invoiceConfirmModal .items-table tr:hover { background: #f8f9fa; }
      #invoiceConfirmModal .total-row { background: linear-gradient(135deg, #667eea15 0%, #764ba215 100%); font-weight: 700; font-size: 14px; }
      #invoiceConfirmModal .warning-box { background: #fff3cd; border-left: 4px solid #ffc107; padding: 14px; border-radius: 8px; margin-bottom: 20px; }
      #invoiceConfirmModal .warning-box i { color: #ff9800; font-size: 20px; margin-right: 10px; }
      #invoiceConfirmModal .warning-text { color: #856404; font-size: 13px; line-height: 1.6; }
      #invoiceConfirmModal .modern-footer { background: white; padding: 20px 28px; display: flex; justify-content: space-between; align-items: center; border-top: 1px solid #e9ecef; }
      #invoiceConfirmModal .btn-modern { padding: 12px 32px; border-radius: 8px; font-weight: 600; font-size: 14px; border: none; transition: all 0.3s; cursor: pointer; }
      #invoiceConfirmModal .btn-cancel { background: #e9ecef; color: #495057; }
      #invoiceConfirmModal .btn-cancel:hover { background: #dee2e6; transform: translateY(-2px); }
      #invoiceConfirmModal .btn-confirm { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
      #invoiceConfirmModal .btn-confirm:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4); }
    </style>
    <div class="modal fade" id="invoiceConfirmModal" tabindex="-1">
      <div class="modal-dialog">
        <div class="modal-content modern-modal">
          <div class="modern-header">
            <div style="display: flex; justify-content: space-between; align-items: center;">
              <h3 style="margin: 0; font-weight: 700; font-size: 20px; color: white;">
                <i class="fa fa-check-circle" style="margin-right: 10px;"></i>Confirm Invoice Creation
              </h3>
              <button type="button" class="close" data-dismiss="modal" style="color: white; opacity: 1; font-size: 28px; font-weight: 300;">
                <span>&times;</span>
              </button>
            </div>
          </div>
          <div class="modern-body">
            <div class="warning-box">
              <div style="display: flex; align-items: flex-start;">
                <i class="fa fa-exclamation-triangle"></i>
                <div class="warning-text">
                  <strong>Please review carefully!</strong> Once confirmed, ${studentCount} invoice(s) will be created. 
                  Make sure all details below are correct before proceeding.
                </div>
              </div>
            </div>
            
            <div class="info-section">
              <h4><i class="fa fa-calendar" style="margin-right: 8px; color: #667eea;"></i>Term & Year</h4>
              <div class="info-row">
                <div class="info-label">Term:</div>
                <div class="info-value highlight">${term}</div>
              </div>
              <div class="info-row">
                <div class="info-label">Academic Year:</div>
                <div class="info-value highlight">${year}</div>
              </div>
              <div class="info-row">
                <div class="info-label">Invoice Date:</div>
                <div class="info-value">${date}</div>
              </div>
            </div>
            
            <div class="info-section">
              <h4><i class="fa fa-users" style="margin-right: 8px; color: #667eea;"></i>Students & Classes</h4>
              <div class="info-row">
                <div class="info-label">Total Students:</div>
                <div class="info-value highlight">${studentCount} student(s)</div>
              </div>
              <div class="info-row">
                <div class="info-label">Student Category:</div>
                <div class="info-value">${studentCategory}</div>
              </div>
              <div class="info-row">
                <div class="info-label">Add to Bill History:</div>
                <div class="info-value">${addToBillHistory ? '<span style="color: #27ae60;">Yes</span>' : '<span style="color: #e74c3c;">No</span>'}</div>
              </div>
            </div>
            
            <h4 style="font-size: 16px; font-weight: 700; color: #2c3e50; margin-bottom: 15px;">
              <i class="fa fa-file-invoice-dollar" style="margin-right: 8px; color: #667eea;"></i>${viewMode === 'class' ? 'Bill Items Per Class' : 'Bill Items'}
            </h4>
            ${billItemsHTML}
            
          </div>
          <div class="modern-footer">
            <button type="button" class="btn-modern btn-cancel" data-dismiss="modal">
              <i class="fa fa-times" style="margin-right: 6px;"></i>Cancel
            </button>
            <button type="button" class="btn-modern btn-confirm" id="confirmInvoiceCreation">
              <i class="fa fa-check" style="margin-right: 6px;"></i>Create ${studentCount} Invoice(s)
            </button>
          </div>
        </div>
      </div>
    </div>
  `;
  
  // Remove existing modal and add new one
  $('#invoiceConfirmModal').remove();
  $('body').append(modalHTML);
  
  // Show modal
  $('#invoiceConfirmModal').modal({backdrop: 'static', keyboard: false});
  
  // Handle confirm button
  $('#confirmInvoiceCreation').on('click', function() {
    $('#invoiceConfirmModal').modal('hide');
    callback();
  });
}



  /*Apply discount function*/
  function applyDiscount() {
    let student_id = $('#student_id').val();
    let invoice_code = $('#student_invoice_codes').val();

    if(!student_id) {
      showAjaxModal_alert('Please select a student first!', 'Error');
      return;
    }

    if(!invoice_code) {
      showAjaxModal_alert('Please select an invoice code first!', 'Error');
      return;
    }

    navigation('<?php echo site_url('admin/apply_discount/'); ?>' + student_id + '/' + invoice_code);
  }

  /*View professional invoice*/
  function viewProfessionalInvoice(invoice_code) {
    showAjaxModal('<?php echo site_url('modal/popup_professional/modal_view_invoice_professional/'); ?>' + invoice_code, 'large');
  }

  /*View selected invoice*/
  function viewSelectedInvoice() {
    let invoice_code = $('#student_invoice_codes').val();
    if(!invoice_code) {
      showAjaxModal_alert('Please select an invoice code first!', 'error');
      return;
    }
    viewProfessionalInvoice(invoice_code);
  }

  /*Email selected invoice*/
  function emailSelectedInvoice() {
    let invoice_code = $('#student_invoice_codes').val();
    if(!invoice_code) {
      showAjaxModal_alert('Please select an invoice code first!', 'error');
      return;
    }

    const modalContent = `
      <div style="padding: 15px;">
        <h5 style="margin-bottom: 15px; font-weight: 600; color: #374151; font-size: 15px;"><i class="fa fa-envelope"></i> Select Recipients</h5>
        <div style="display: flex; flex-direction: column; gap: 8px;">
          <label style="display: flex; align-items: center; padding: 8px; border: 2px solid #e5e7eb; border-radius: 6px; cursor: pointer;" onmouseover="this.style.borderColor='#3b82f6'" onmouseout="this.style.borderColor='#e5e7eb'">
            <input type="checkbox" value="student" style="width: 16px; height: 16px; margin-right: 10px;" checked>
            <span style="font-weight: 500; color: #1f2937; font-size: 14px;"><i class="fa fa-user-graduate" style="color: #3b82f6; margin-right: 6px;"></i>Student</span>
          </label>
          <label style="display: flex; align-items: center; padding: 8px; border: 2px solid #e5e7eb; border-radius: 6px; cursor: pointer;" onmouseover="this.style.borderColor='#3b82f6'" onmouseout="this.style.borderColor='#e5e7eb'">
            <input type="checkbox" value="guardian" style="width: 16px; height: 16px; margin-right: 10px;">
            <span style="font-weight: 500; color: #1f2937; font-size: 14px;"><i class="fa fa-user-shield" style="color: #10b981; margin-right: 6px;"></i>Guardian</span>
          </label>
          <label style="display: flex; align-items: center; padding: 8px; border: 2px solid #e5e7eb; border-radius: 6px; cursor: pointer;" onmouseover="this.style.borderColor='#3b82f6'" onmouseout="this.style.borderColor='#e5e7eb'">
            <input type="checkbox" value="father" style="width: 16px; height: 16px; margin-right: 10px;">
            <span style="font-weight: 500; color: #1f2937; font-size: 14px;"><i class="fa fa-male" style="color: #6366f1; margin-right: 6px;"></i>Father</span>
          </label>
          <label style="display: flex; align-items: center; padding: 8px; border: 2px solid #e5e7eb; border-radius: 6px; cursor: pointer;" onmouseover="this.style.borderColor='#3b82f6'" onmouseout="this.style.borderColor='#e5e7eb'">
            <input type="checkbox" value="mother" style="width: 16px; height: 16px; margin-right: 10px;">
            <span style="font-weight: 500; color: #1f2937; font-size: 14px;"><i class="fa fa-female" style="color: #ec4899; margin-right: 6px;"></i>Mother</span>
          </label>
        </div>
        <div style="margin-top: 15px; display: flex; gap: 8px; justify-content: flex-end;">
          <button type="button" onclick="$('.close').click()" style="padding: 8px 16px; border: 2px solid #d1d5db; background: white; color: #374151; border-radius: 6px; font-weight: 500; cursor: pointer; font-size: 13px;">
            Cancel
          </button>
          <button type="button" onclick="sendInvoiceEmail('${invoice_code}')" style="padding: 8px 16px; border: none; background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; border-radius: 6px; font-weight: 500; cursor: pointer; font-size: 13px;">
            <i class="fa fa-paper-plane"></i> Send
          </button>
        </div>
      </div>
    `;
    showModalWithContent('modal_ajax', '<i class="fa fa-envelope"></i> Email Invoice', modalContent);
  }

  function sendInvoiceEmail(invoice_code) {
    const recipients = [];
    $('#createModal input[type="checkbox"]:checked').each(function() {
      recipients.push($(this).val());
    });

    if(recipients.length === 0) {
      showAjaxModal_alert('Please select at least one recipient', 'error');
      return;
    }

    $('.close').click();
    showAjaxModal_alert('Sending email...', 'loading');
    
    $.ajax({
      url: '<?php echo site_url('invoice_email/send/'); ?>' + invoice_code,
      type: 'POST',
      data: { recipients: recipients },
      dataType: 'json'
    }).done(function(response) {
      if(response.status === 'success') {
        showAjaxModal_alert(response.message, 'success');
      } else {
        showAjaxModal_alert(response.message, 'error');
      }
    }).fail(function() {
      showAjaxModal_alert('Failed to send email', 'error');
    });
  }

  // ============================================
  // BULK INVOICE PRINTING - ENTERPRISE IMPLEMENTATION
  // ============================================
  
  function printBulkInvoices() {
    const term = $('#bulk_term').val();
    const year = $('#bulk_year').val();
    const status = $('#bulk_status').val();
    const filter = $('#bulk_filter').val();
    const classId = $('#bulk_class').val();

    if(filter === 'class' && !classId) {
      showAjaxModal_alert('Please select a class', 'error');
      return;
    }

    // Check if specific invoices are selected
    let selectedInvoiceCodes = [];
    if(selectedInvoices.length > 0) {
      selectedInvoiceCodes = selectedInvoices.map(inv => inv.code);
      
      showConfirmModal(
        'Print Selected Invoices',
        `You are about to print <strong>${selectedInvoices.length}</strong> selected invoice(s). Continue?`,
        function() {
          executeBulkPrint(term, year, status, filter, classId, selectedInvoiceCodes);
        },
        'Print Selected',
        'primary'
      );
    } else {
      // Print all based on filters
      const statusText = status === '' ? 'ALL STATUSES' :
                        status === 'paid' ? 'PAID' :
                        status === 'unpaid' ? 'UNPAID' :
                        status === 'partial' ? 'PARTIAL' : status.toUpperCase();
      const filterText = filter === 'all' ? 'ALL STUDENTS' : 
                        filter === 'boarding' ? 'BOARDING STUDENTS' : 
                        filter === 'day' ? 'DAY STUDENTS' : 
                        filter === 'class' ? $('#bulk_class option:selected').text() : filter.toUpperCase();
      
      const confirmMessage = `You are about to print ALL invoices matching:\n\nTerm: ${term || 'All'}\nYear: ${year || 'All'}\nStatus: ${statusText}\nFilter: ${filterText}\n\nThis may take a moment. Continue?`;
      
      showConfirmModal(
        'Print All Filtered Invoices',
        confirmMessage,
        function() {
          executeBulkPrint(term, year, status, filter, classId, []);
        },
        'Print All',
        'primary'
      );
    }
  }

  let isPrinting = false;
  
  function executeBulkPrint(term, year, status, filter, classId, invoiceCodes) {
    if(isPrinting) return;
    isPrinting = true;
    
    showAjaxModal_alert('Preparing invoices for printing...', 'loading');

    // Create form and submit to open in new window
    const form = $('<form>', {
      'method': 'POST',
      'action': '<?php echo site_url('admin/print_bulk_invoices'); ?>',
      'target': '_blank'
    });
    
    // Add CSRF token
    form.append($('<input>', {'type': 'hidden', 'name': '<?php echo $this->security->get_csrf_token_name(); ?>', 'value': '<?php echo $this->security->get_csrf_hash(); ?>'}));

    form.append($('<input>', {'type': 'hidden', 'name': 'term', 'value': term}));
    form.append($('<input>', {'type': 'hidden', 'name': 'year', 'value': year}));
    form.append($('<input>', {'type': 'hidden', 'name': 'status', 'value': status}));
    form.append($('<input>', {'type': 'hidden', 'name': 'filter', 'value': filter}));
    
    if(classId) {
      form.append($('<input>', {'type': 'hidden', 'name': 'class_id', 'value': classId}));
    }

    // Add selected invoice codes if any
    if(invoiceCodes.length > 0) {
      invoiceCodes.forEach(function(code) {
        form.append($('<input>', {'type': 'hidden', 'name': 'invoice_codes[]', 'value': code}));
      });
    }

    $('body').append(form);
    form.submit();
    form.remove();

    // Close loading modal after a short delay
    setTimeout(function() {
      $('.close').click();
      toastr.success('Print window opened. Please check your browser for the print dialog.');
      isPrinting = false;
    }, 1000);
  }

  function printSelectedInvoices() {
    if(selectedInvoices.length === 0) {
      showAjaxModal_alert('Please select at least one invoice to print', 'error');
      return;
    }

    const term = $('#bulk_term').val();
    const year = $('#bulk_year').val();
    const status = $('#bulk_status').val();
    const filter = $('#bulk_filter').val();
    const classId = $('#bulk_class').val();

    const invoiceCodes = selectedInvoices.map(inv => inv.code);
    
    const confirmMessage = `You are about to print ${selectedInvoices.length} selected invoice(s).\n\nTerm: ${term || 'All'}\nYear: ${year || 'All'}\n\nA new window will open with all selected invoices ready to print.`;
    
    showConfirmModal(
      'Print Selected Invoices',
      confirmMessage,
      function() {
        executeBulkPrint(term, year, status, filter, classId, invoiceCodes);
      },
      'Print Selected',
      'primary'
    );
  }

  // ============================================
  // BULK INVOICE MANAGEMENT - PROFESSIONAL IMPLEMENTATION
  // ============================================
  let bulkInvoiceTable;
  let selectedInvoices = [];

  function toggleBulkClassSelector() {
    const filter = $('#bulk_filter').val();
    if(filter === 'class') {
      $('#bulk_class_selector').slideDown(300);
      $('#bulk_class').attr('required', true);
    } else {
      $('#bulk_class_selector').slideUp(300);
      $('#bulk_class').removeAttr('required');
      $('#bulk_class').val('').trigger('change');
    }
  }

  function loadBulkInvoices() {
    const term = $('#bulk_term').val();
    const year = $('#bulk_year').val();
    const status = $('#bulk_status').val();
    const filter = $('#bulk_filter').val();
    const classId = $('#bulk_class').val();

    if(filter === 'class' && !classId) {
      showAjaxModal_alert('Please select a class', 'error');
      return;
    }

    // Store the filter data for refresh function
    window.bulkInvoiceRefreshData = {
      term: term,
      year: year,
      status: status,
      filter: filter,
      class_id: classId
    };

    // Show loading modal using existing modal system
    showAjaxModal_alert('<center><div style="font-size: 20px; font-weight: bolder;">Loading Invoices...<br><i class="fa fa-3x fa-spinner fa-pulse"></i></div></center>', 'Loading');

    $('#load_bulk_invoices').html('<i class="fa fa-spinner fa-spin"></i> LOADING...').prop('disabled', true);

    $.ajax({
      url: '<?php echo site_url('admin/get_bulk_invoices'); ?>',
      type: 'POST',
      dataType: 'json',
      data: { term, year, status, filter, class_id: classId },
      success: function(response) {
        if(response.status === 'success') {
          initBulkInvoiceTable(response.data);
          updateBulkStats(response.stats);
          $('#bulk_stats').slideDown(300);
          toastr.success(response.message || 'Invoices loaded successfully');
        } else {
          showAjaxModal_alert(response.message || 'Failed to load invoices', 'error');
        }
      },
      error: function(xhr) {
        showAjaxModal_alert('Error loading invoices: ' + xhr.responseText, 'error');
      },
      complete: function() {
        // Hide loading modal using existing modal system
        $('#modal_alert').modal('hide');
        $('#load_bulk_invoices').html('<i class="fa fa-sync-alt"></i> LOAD INVOICES').prop('disabled', false);
      }
    });
  }

  function initBulkInvoiceTable(data) {
    if(bulkInvoiceTable) {
      bulkInvoiceTable.destroy();
    }

    bulkInvoiceTable = $('#bulk_invoices_datatable').DataTable({
      data: data,
      columns: [
        { 
          data: null,
          orderable: false,
          className: 'text-center',
          render: function(data, type, row) {
            return `<input type="checkbox" class="bulk-invoice-check w-5 h-5" data-invoice-id="${row.invoice_id}" data-invoice-code="${row.invoice_code}">`;
          }
        },
        { 
          data: 'invoice_code',
          render: function(data, type, row) {
            return `<a href="javascript:void(0)" onclick="viewInvoiceDetails('${data}')" class="font-bold text-blue-600 hover:text-blue-800 hover:underline">#${data}</a>`;
          }
        },
        { 
          data: 'student_name',
          render: function(data, type, row) {
            return `<div><a href="<?php echo site_url('admin/student_profile/'); ?>${row.student_id}" target="_blank" class="font-semibold text-blue-600 hover:text-blue-800 hover:underline">${data}</a><div class="text-xs text-gray-600">${row.student_code}</div></div>`;
          }
        },
        { 
          data: 'class_name',
          render: function(data, type, row) {
            const section = row.section_name ? `<div class="text-xs text-gray-600">${row.section_name}</div>` : '';
            return `<div><div class="font-semibold text-base">${data}</div>${section}</div>`;
          }
        },
        { 
          data: 'status',
          render: function(data) {
            const badges = {
              'paid': '<span class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm font-bold"><i class="fa fa-check-circle"></i> Paid</span>',
              'unpaid': '<span class="px-3 py-1 bg-red-100 text-red-800 rounded-full text-sm font-bold"><i class="fa fa-times-circle"></i> Unpaid</span>',
              'partial': '<span class="px-3 py-1 bg-yellow-100 text-yellow-800 rounded-full text-sm font-bold"><i class="fa fa-clock"></i> Partial</span>'
            };
            return badges[data] || data;
          }
        },
        { 
          data: 'total_amount',
          className: 'text-right whitespace-nowrap',
          render: function(data) {
            return `<span class="font-bold text-gray-800" style="white-space: nowrap;">${parseFloat(data || 0).toLocaleString('en-US', {minimumFractionDigits: 2})}</span>`;
          }
        },
        { 
          data: 'amount_paid',
          className: 'text-right whitespace-nowrap',
          render: function(data) {
            return `<span class="font-semibold text-green-600" style="white-space: nowrap;">${parseFloat(data || 0).toLocaleString('en-US', {minimumFractionDigits: 2})}</span>`;
          }
        },
        { 
          data: 'due',
          className: 'text-right whitespace-nowrap',
          render: function(data) {
            const color = parseFloat(data || 0) > 0 ? 'text-red-600' : 'text-green-600';
            return `<span class="font-semibold ${color}" style="white-space: nowrap;">${parseFloat(data || 0).toLocaleString('en-US', {minimumFractionDigits: 2})}</span>`;
          }
        },
        { 
          data: 'creation_timestamp',
          render: function(data) {
            return new Date(data * 1000).toLocaleDateString('en-US', {year: 'numeric', month: 'short', day: 'numeric'});
          }
        },
        { 
          data: null,
          orderable: false,
          className: 'text-center',
          render: function(data, type, row) {
            const payButton = parseFloat(row.due || 0) > 0 ? 
              `<button onclick="openBulkPaymentModal('${row.student_id}')" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2.5 rounded-lg text-base font-bold transition-all whitespace-nowrap" title="Pay">
                Pay
              </button>` : '';
            return `
              <div class="flex gap-2 justify-center">
                ${payButton}
                <button onclick="viewInvoiceDetails('${row.invoice_code}')" class="action-btn bg-blue-500 hover:bg-blue-600 hover:shadow-lg text-white px-4 py-2.5 rounded-lg text-base font-bold transition-all duration-300" title="View Invoice">
                  <i class="fa fa-file-invoice"></i>
                </button>
                <button onclick="view_receipts_modal('${row.student_id}')" class="action-btn bg-purple-500 hover:bg-purple-600 hover:shadow-lg text-white px-4 py-2.5 rounded-lg text-base font-bold transition-all duration-300" title="View Receipts">
                  <i class="fa fa-receipt"></i>
                </button>
                <button onclick="modifyInvoice('${row.invoice_code}')" class="action-btn bg-amber-600 hover:bg-amber-700 hover:shadow-lg text-white px-4 py-2.5 rounded-lg text-base font-bold transition-all duration-300" title="Modify">
                  <i class="fa fa-edit"></i>
                </button>
              </div>
            `;
          }
        }
      ],
      responsive: true,
      pageLength: 25,
      lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
      dom: '<"flex justify-between items-center mb-4"<"flex gap-2"l><"flex-1"f>>rtip',
      language: {
        search: "_INPUT_",
        searchPlaceholder: "Search invoices...",
        lengthMenu: "Show _MENU_ entries",
        info: "Showing _START_ to _END_ of _TOTAL_ invoices",
        infoEmpty: "No invoices found",
        infoFiltered: "(filtered from _MAX_ total invoices)"
      }
    });

    // Handle select all
    $('#select_all_bulk').on('change', function() {
      $('.bulk-invoice-check').prop('checked', $(this).is(':checked')).trigger('change');
    });

    // Handle individual checkbox
    $('#bulk_invoices_datatable').on('change', '.bulk-invoice-check', function() {
      updateSelectedInvoices();
    });
  }

  function updateSelectedInvoices() {
    selectedInvoices = [];
    $('.bulk-invoice-check:checked').each(function() {
      selectedInvoices.push({
        id: $(this).data('invoice-id'),
        code: $(this).data('invoice-code')
      });
    });

    $('#selected_count').text(selectedInvoices.length);
    
    if(selectedInvoices.length > 0) {
      $('#bulk_actions_bar').slideDown(300);
    } else {
      $('#bulk_actions_bar').slideUp(300);
    }
  }

  function updateBulkStats(stats) {
    $('#stat_total').text(stats.invoice_count || 0);
    $('#stat_amount').text('<?=$currency;?>' + parseFloat(stats.total_amount || 0).toLocaleString('en-US', {minimumFractionDigits: 2}));
    $('#stat_students').text(stats.unique_students || 0);
    $('#stat_receivables').text('<?=$currency;?>' + parseFloat(stats.total_receivables || 0).toLocaleString('en-US', {minimumFractionDigits: 2}));
  }

  function openBulkPaymentModal(studentId) {
    window.bulkInvoiceRefreshData = {
      term: $('#bulk_term').val(),
      year: $('#bulk_year').val(),
      filter: $('#bulk_filter').val(),
      class_id: $('#bulk_class').val()
    };
    showAjaxModal('<?php echo site_url('modal/popup/modal_take_payment/'); ?>' + studentId + '/bulk_mode', 'take_payment');
  }

  function refreshBulkInvoices() {
    if(!window.bulkInvoiceRefreshData) return;
    
    const data = window.bulkInvoiceRefreshData;
    $.ajax({
      url: '<?php echo site_url('admin/get_bulk_invoices'); ?>',
      type: 'POST',
      dataType: 'json',
      data: data,
      success: function(response) {
        if(response.status === 'success') {
          initBulkInvoiceTable(response.data);
          updateBulkStats(response.stats);
        }
      }
    });
  }

  function viewInvoiceDetails(invoiceCode) {
    showAjaxModal('<?php echo site_url('modal/popup_professional/modal_view_invoice_professional/'); ?>' + invoiceCode, 'large');
  }

  function modifyInvoice(invoiceCode) {
    // Check if there's already a pending request for this invoice
    $.ajax({
      url: '<?php echo site_url("admin/check_invoice_modification_request"); ?>',
      type: 'POST',
      data: { invoice_code: invoiceCode },
      dataType: 'json'
    }).done(function(response) {
      if(response.has_pending) {
        showAjaxModal_alert('A pending modification request already exists for this invoice. Please wait for approval or rejection before submitting a new request.', 'warning');
      } else if(response.has_pending_discount) {
        showAjaxModal_alert('A pending discount request already exists for this invoice. Please wait for approval or rejection before submitting a modification request.', 'warning');
      } else {
        loadModalContent('createModal', 
          '<?php echo site_url('admin/invoice_modification_modal/'); ?>' + invoiceCode, 
          '<i class="fa fa-edit"></i> Modify Invoice');
      }
    }).fail(function() {
      showAjaxModal_alert('Error checking request status', 'error');
    });
  }

  function bulkEditInvoices() {
    if(selectedInvoices.length === 0) {
      showAjaxModal_alert('Please select at least one invoice', 'error');
      return;
    }

    const confirmMessage = `You are about to edit ${selectedInvoices.length} invoice(s).\n\nThis action requires administrative approval.\n\nContinue?`;

    showConfirmModal(
      'Bulk Edit - Approval Required',
      confirmMessage,
      function() {
        const codes = selectedInvoices.map(inv => inv.code).join(',');
        navigation('<?php echo site_url('admin/bulk_edit_invoices/'); ?>' + codes);
      },
      'Proceed to Edit',
      'primary'
    );
  }

  function bulkDeleteInvoices() {
    if(selectedInvoices.length === 0) {
      showAjaxModal_alert('Please select at least one invoice', 'error');
      return;
    }

    const confirmMessage = `You are about to delete ${selectedInvoices.length} invoice(s).\n\nWARNING: This action requires senior management approval and cannot be undone!\n\nAre you sure you want to proceed?`;

    showConfirmModal(
      'Bulk Delete - Critical Action',
      confirmMessage,
      function() {
        showAjaxModal_alert('Processing bulk deletion...', 'loading');
        $.ajax({
          url: '<?php echo site_url('admin/bulk_delete_invoices'); ?>',
          type: 'POST',
          dataType: 'json',
          data: { invoices: selectedInvoices.map(inv => inv.code) },
          success: function(response) {
            if(response.status === 'success') {
              showAjaxModal_alert(response.message, 'success');
              // setTimeout(() => {
              //   $('.close').click();
              //   //loadBulkInvoices();
              // }, 2000);
            } else {
              showAjaxModal_alert(response.message, 'error');
            }
          },
          error: function(xhr) {
            showAjaxModal_alert('Error: ' + xhr.responseText, 'error');
          }
        });
      },
      'Delete',
      'danger'
    );
  }

  function bulkModifyInvoices() {
    if(selectedInvoices.length === 0) {
      showAjaxModal_alert('Please select at least one invoice', 'error');
      return;
    }

    let totalAmount = 0;
    let studentNames = new Set();
    
    // Get data from DataTable if available
    if(typeof bulkInvoiceTable !== 'undefined' && bulkInvoiceTable) {
      selectedInvoices.forEach(inv => {
        const rowData = bulkInvoiceTable.rows().data().toArray().find(r => r.invoice_code === inv.code);
        if(rowData) {
          totalAmount += parseFloat(rowData.total_amount || 0);
          if(rowData.student_name) studentNames.add(rowData.student_name);
        }
      });
    }
    
    const studentCount = studentNames.size;

    const modalContent = `
      <div style="padding: 20px; max-height: 80vh; overflow-y: auto;">
        <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 12px; margin: -20px -20px 20px -20px;">
          <div style="display: flex; align-items: center; gap: 15px;">
            <i class="fa fa-edit" style="font-size: 32px;"></i>
            <div>
              <h4 style="margin: 0; font-size: 20px; font-weight: 700;">Bulk Invoice Modification</h4>
              <p style="margin: 4px 0 0 0; opacity: 0.95; font-size: 13px; color: white;">${selectedInvoices.length} invoices selected${totalAmount > 0 ? ' | Total: <?=$currency;?>' + totalAmount.toFixed(2) : ''}${studentCount > 0 ? ' | ' + studentCount + ' student(s)' : ''}</p>
            </div>
          </div>
        </div>

        <div style="background: #f0f9ff; border: 2px solid #0ea5e9; border-radius: 10px; padding: 15px; margin-bottom: 20px;">
          <div style="font-size: 12px; color: #0c4a6e; margin-bottom: 8px;"><strong>Selected Invoices:</strong></div>
          <div style="display: flex; flex-wrap: wrap; gap: 6px;">
            ${selectedInvoices.slice(0, 10).map(inv => `<span style="background: white; color: #1e40af; padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 600;">#${inv.code}</span>`).join('')}
            ${selectedInvoices.length > 10 ? `<span style="color: #0c4a6e; padding: 4px 10px; font-size: 12px;">+${selectedInvoices.length - 10} more</span>` : ''}
          </div>
        </div>

        <div style="margin-bottom: 20px;">
          <label style="display: block; font-size: 14px; font-weight: 600; color: #1f2937; margin-bottom: 10px;">Select Action</label>
          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
            <div class="bulk-action-card" data-action="edit" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; padding: 20px; border-radius: 10px; cursor: pointer; border: 3px solid transparent; transition: all 0.3s;" onclick="selectBulkAction('edit', this)">
              <i class="fa fa-edit" style="font-size: 28px; display: block; margin-bottom: 8px;"></i>
              <div style="font-size: 16px; font-weight: 700;">Edit Invoices</div>
              <div style="font-size: 12px; opacity: 0.9; margin-top: 4px;">Modify amounts or details</div>
            </div>
            <div class="bulk-action-card" data-action="delete" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); color: white; padding: 20px; border-radius: 10px; cursor: pointer; border: 3px solid transparent; transition: all 0.3s;" onclick="selectBulkAction('delete', this)">
              <i class="fa fa-trash-alt" style="font-size: 28px; display: block; margin-bottom: 8px;"></i>
              <div style="font-size: 16px; font-weight: 700;">Delete Invoices</div>
              <div style="font-size: 12px; opacity: 0.9; margin-top: 4px;">Permanently remove</div>
            </div>
          </div>
        </div>

        <div id="bulk_delete_warning" style="display: none; background: #fee2e2; border: 2px solid #ef4444; border-radius: 10px; padding: 15px; margin-bottom: 20px;">
          <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
            <i class="fa fa-exclamation-triangle" style="color: #dc2626; font-size: 24px;"></i>
            <strong style="color: #991b1b; font-size: 15px;">⚠️ Permanent Deletion Warning</strong>
          </div>
          <div style="color: #7f1d1d; font-size: 13px; line-height: 1.6;">
            <ul style="margin: 8px 0; padding-left: 20px;">
              <li>Deleting <strong>${selectedInvoices.length} invoice(s)</strong>${totalAmount > 0 ? ' worth <strong><?=$currency;?>' + totalAmount.toFixed(2) + '</strong>' : ''}</li>
              ${studentCount > 0 ? '<li>Affecting <strong>' + studentCount + ' student(s)</strong></li>' : ''}
              <li><strong>This action cannot be undone once approved</strong></li>
            </ul>
          </div>
        </div>

        <div id="bulk_edit_notice" style="display: none; background: #dbeafe; border: 2px solid #3b82f6; border-radius: 10px; padding: 15px; margin-bottom: 20px;">
          <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
            <i class="fa fa-info-circle" style="color: #1e40af; font-size: 24px;"></i>
            <strong style="color: #1e3a8a; font-size: 15px;">Bulk Edit Request</strong>
          </div>
          <div style="color: #1e40af; font-size: 13px; line-height: 1.6;">
            <p style="margin: 0 0 10px 0;">You are requesting to edit <strong>${selectedInvoices.length} invoice(s)</strong>. This will:</p>
            <ul style="margin: 8px 0; padding-left: 20px;">
              <li>Create a modification request for each selected invoice</li>
              <li>Require super admin approval before changes take effect</li>
              <li>Allow you to specify the reason for bulk modification</li>
            </ul>
            <div style="background: #eff6ff; border-left: 3px solid #3b82f6; padding: 10px; margin-top: 10px; border-radius: 4px;">
              <strong><i class="fa fa-lightbulb"></i> Note:</strong> For detailed invoice item editing, please use the individual invoice modification feature from the Actions column.
            </div>
          </div>
        </div>

        <div style="margin-bottom: 20px;">
          <label style="display: block; font-size: 14px; font-weight: 600; color: #1f2937; margin-bottom: 8px;">Reason for Modification <span style="color: #ef4444;">*</span></label>
          <textarea id="bulk_mod_reason" rows="4" required placeholder="Provide detailed reason for this bulk modification..."
                    style="width: 100%; padding: 12px; border: 2px solid #e5e7eb; border-radius: 8px; font-size: 13px; resize: vertical;"
                    onfocus="this.style.borderColor='#667eea'" onblur="this.style.borderColor='#e5e7eb'"></textarea>
        </div>

        <div style="background: #fef3c7; border-left: 4px solid #f59e0b; border-radius: 8px; padding: 12px; margin-bottom: 20px;">
          <div style="display: flex; align-items: center; gap: 10px;">
            <i class="fa fa-user-shield" style="color: #92400e; font-size: 20px;"></i>
            <div style="color: #78350f; font-size: 12px; line-height: 1.5;">
              <strong>Approval Required:</strong> This request will be sent to super admin for review. You'll be notified of the decision.
            </div>
          </div>
        </div>

        <div style="display: flex; gap: 10px; justify-content: flex-end;">
          <button type="button" onclick="$('.close').click()" style="padding: 10px 24px; border: 2px solid #d1d5db; background: white; color: #374151; border-radius: 8px; font-weight: 600; cursor: pointer;">
            <i class="fa fa-times"></i> Cancel
          </button>
          <button type="button" onclick="submitBulkModification()" style="padding: 10px 24px; border: none; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 8px; font-weight: 600; cursor: pointer;">
            <i class="fa fa-paper-plane"></i> Submit Request
          </button>
        </div>
      </div>
    `;
    
    showModalWithContent('createModal', '', modalContent);
  }

  let selectedBulkAction = null;
  
  function selectBulkAction(action, element) {
    selectedBulkAction = action;
    document.querySelectorAll('.bulk-action-card').forEach(card => {
      card.style.borderColor = 'transparent';
      card.style.transform = 'scale(1)';
    });
    element.style.borderColor = 'white';
    element.style.transform = 'scale(1.05)';
    
    if(action === 'delete') {
      $('#bulk_delete_warning').slideDown(300);
      $('#bulk_edit_notice').slideUp(300);
    } else if(action === 'edit') {
      $('#bulk_edit_notice').slideDown(300);
      $('#bulk_delete_warning').slideUp(300);
    }
  }

  function submitBulkModification() {
    if(!selectedBulkAction) {
      showAjaxModal_alert('Please select an action (Edit or Delete)', 'warning');
      return;
    }
    const reason = $('#bulk_mod_reason').val().trim();
    if(!reason) {
      showAjaxModal_alert('Please enter a reason for this modification', 'error');
      $('#bulk_mod_reason').focus();
      return;
    }
    $('.close').click();
    showAjaxModal_alert('Processing bulk modification request...', 'loading');
    
    $.ajax({
      url: '<?php echo site_url('admin/bulkModifyInvoices'); ?>',
      type: 'POST',
      dataType: 'json',
      data: {
        invoice_codes: selectedInvoices.map(inv => inv.code),
        request_type: selectedBulkAction,
        reason: reason
      },
      success: function(response) {
        if(response.status === 'success') {
          showAjaxModal_alert(response.message, 'success');
          setTimeout(() => {
            $('.close').click();
            loadBulkInvoices();
            selectedInvoices = [];
            updateSelectedInvoices();
          }, 2000);
        } else {
          showAjaxModal_alert(response.message, 'error');
        }
      },
      error: function(xhr) {
        showAjaxModal_alert('Error: ' + xhr.responseText, 'error');
      }
    });
  }

  function exportSelectedInvoices() {
    if(selectedInvoices.length === 0) {
      showAjaxModal_alert('Please select at least one invoice', 'error');
      return;
    }

    showAjaxModal_alert('Preparing professional export...', 'loading');
    
    const codes = selectedInvoices.map(inv => inv.code);
    
    $.ajax({
      url: '<?php echo site_url('admin/export_invoices'); ?>',
      type: 'POST',
      dataType: 'json',
      data: { invoice_codes: codes },
      success: function(response) {
        if(response.status === 'success') {
          $('.close').click();
          
          const schoolInfo = response.school_info;
          
          // Create empty worksheet
          const ws = {};
          
          // School header (rows 1-4)
          XLSX.utils.sheet_add_aoa(ws, [
            [schoolInfo.name],
            [schoolInfo.address],
            [schoolInfo.phone + ' | ' + schoolInfo.email],
            [''],
            ['INVOICE EXPORT REPORT'],
            ['Generated: ' + new Date().toLocaleString()],
            ['Total Invoices: ' + selectedInvoices.length],
            ['']
          ], {origin: 'A1'});
          
          // Add data starting from row 9
          XLSX.utils.sheet_add_json(ws, response.data, {origin: 'A9', skipHeader: false});
          
          // Column widths
          ws['!cols'] = [
            {wch: 15}, {wch: 25}, {wch: 15}, {wch: 20}, {wch: 15},
            {wch: 15}, {wch: 15}, {wch: 15}, {wch: 12}, {wch: 12}
          ];
          
          // Merges
          if(!ws['!merges']) ws['!merges'] = [];
          ws['!merges'].push(
            {s: {r: 0, c: 0}, e: {r: 0, c: 9}}, // School name
            {s: {r: 1, c: 0}, e: {r: 1, c: 9}}, // Address
            {s: {r: 2, c: 0}, e: {r: 2, c: 9}}, // Contact
            {s: {r: 4, c: 0}, e: {r: 4, c: 9}}, // Title
            {s: {r: 5, c: 0}, e: {r: 5, c: 9}}, // Date
            {s: {r: 6, c: 0}, e: {r: 6, c: 9}}  // Count
          );
          
          // Style school header
          ws['A1'].s = {font: {bold: true, sz: 18, color: {rgb: "1E40AF"}}, alignment: {horizontal: 'center', vertical: 'center'}};
          ws['A2'].s = {font: {sz: 11, color: {rgb: "4B5563"}}, alignment: {horizontal: 'center'}};
          ws['A3'].s = {font: {sz: 10, color: {rgb: "6B7280"}}, alignment: {horizontal: 'center'}};
          ws['A5'].s = {font: {bold: true, sz: 14, color: {rgb: "FFFFFF"}}, fill: {fgColor: {rgb: "1E40AF"}}, alignment: {horizontal: 'center'}};
          ws['A6'].s = {font: {sz: 10, color: {rgb: "4B5563"}}, alignment: {horizontal: 'center'}};
          ws['A7'].s = {font: {bold: true, sz: 11, color: {rgb: "059669"}}, alignment: {horizontal: 'center'}};
          
          // Style data headers (row 9)
          const range = XLSX.utils.decode_range(ws['!ref']);
          for(let C = 0; C <= 9; ++C) {
            const addr = XLSX.utils.encode_col(C) + '9';
            if(!ws[addr]) continue;
            ws[addr].s = {
              font: {bold: true, sz: 11, color: {rgb: "FFFFFF"}},
              fill: {fgColor: {rgb: "3B82F6"}},
              alignment: {horizontal: 'center', vertical: 'center'},
              border: {top: {style: 'thin'}, bottom: {style: 'thin'}, left: {style: 'thin'}, right: {style: 'thin'}}
            };
          }
          
          // Style data rows
          for(let R = 9; R <= range.e.r; ++R) {
            const isEven = (R - 9) % 2 === 0;
            for(let C = 0; C <= 9; ++C) {
              const addr = XLSX.utils.encode_col(C) + (R + 1);
              if(!ws[addr]) continue;
              ws[addr].s = {
                font: {sz: 10},
                fill: {fgColor: {rgb: isEven ? "F3F4F6" : "FFFFFF"}},
                alignment: {horizontal: C >= 5 && C <= 7 ? 'right' : 'left', vertical: 'center'},
                border: {top: {style: 'thin', color: {rgb: "E5E7EB"}}, bottom: {style: 'thin', color: {rgb: "E5E7EB"}}, left: {style: 'thin', color: {rgb: "E5E7EB"}}, right: {style: 'thin', color: {rgb: "E5E7EB"}}}
              };
              
              if(C === 8 && ws[addr].v) {
                const status = ws[addr].v.toLowerCase();
                if(status === 'paid') {
                  ws[addr].s.fill = {fgColor: {rgb: "D1FAE5"}};
                  ws[addr].s.font = {sz: 10, bold: true, color: {rgb: "065F46"}};
                } else if(status === 'unpaid') {
                  ws[addr].s.fill = {fgColor: {rgb: "FEE2E2"}};
                  ws[addr].s.font = {sz: 10, bold: true, color: {rgb: "991B1B"}};
                } else if(status === 'partial') {
                  ws[addr].s.fill = {fgColor: {rgb: "FEF3C7"}};
                  ws[addr].s.font = {sz: 10, bold: true, color: {rgb: "92400E"}};
                }
              }
            }
          }
          
          ws['!autofilter'] = {ref: XLSX.utils.encode_range({s: {r: 8, c: 0}, e: {r: 8, c: 9}})};
          ws['!freeze'] = {xSplit: 0, ySplit: 9};
          
          const wb = XLSX.utils.book_new();
          XLSX.utils.book_append_sheet(wb, ws, 'Invoice Export');
          
          const filename = 'Invoice_Export_' + new Date().toISOString().slice(0,10).replace(/-/g, '') + '_' + new Date().getTime() + '.xlsx';
          XLSX.writeFile(wb, filename, {cellStyles: true});
          
          toastr.success(`Successfully exported ${selectedInvoices.length} invoice(s) to Excel`);
        } else {
          showAjaxModal_alert(response.message || 'Export failed', 'error');
        }
      },
      error: function(xhr) {
        showAjaxModal_alert('Export error: ' + xhr.responseText, 'error');
      }
    });
  }

  function bulkEmailInvoices() {
    if(selectedInvoices.length === 0) {
      showAjaxModal_alert('Please select at least one invoice', 'error');
      return;
    }
    const modalContent = `
      <div style="padding: 15px;">
        <p style="margin-bottom: 15px; color: #374151;">Send ${selectedInvoices.length} invoice(s) via email to:</p>
        <div style="display: flex; flex-direction: column; gap: 8px;">
          <label style="display: flex; align-items: center; padding: 8px; border: 2px solid #e5e7eb; border-radius: 6px; cursor: pointer;" onmouseover="this.style.borderColor='#3b82f6'" onmouseout="this.style.borderColor='#e5e7eb'">
            <input type="checkbox" value="student" class="bulk-email-recipient" style="width: 16px; height: 16px; margin-right: 10px;" checked>
            <span style="font-weight: 500; color: #1f2937; font-size: 14px;"><i class="fa fa-user-graduate" style="color: #3b82f6; margin-right: 6px;"></i>Students</span>
          </label>
          <label style="display: flex; align-items: center; padding: 8px; border: 2px solid #e5e7eb; border-radius: 6px; cursor: pointer;" onmouseover="this.style.borderColor='#3b82f6'" onmouseout="this.style.borderColor='#e5e7eb'">
            <input type="checkbox" value="guardian" class="bulk-email-recipient" style="width: 16px; height: 16px; margin-right: 10px;">
            <span style="font-weight: 500; color: #1f2937; font-size: 14px;"><i class="fa fa-user-shield" style="color: #10b981; margin-right: 6px;"></i>Guardians</span>
          </label>
          <label style="display: flex; align-items: center; padding: 8px; border: 2px solid #e5e7eb; border-radius: 6px; cursor: pointer;" onmouseover="this.style.borderColor='#3b82f6'" onmouseout="this.style.borderColor='#e5e7eb'">
            <input type="checkbox" value="father" class="bulk-email-recipient" style="width: 16px; height: 16px; margin-right: 10px;">
            <span style="font-weight: 500; color: #1f2937; font-size: 14px;"><i class="fa fa-male" style="color: #6366f1; margin-right: 6px;"></i>Fathers</span>
          </label>
          <label style="display: flex; align-items: center; padding: 8px; border: 2px solid #e5e7eb; border-radius: 6px; cursor: pointer;" onmouseover="this.style.borderColor='#3b82f6'" onmouseout="this.style.borderColor='#e5e7eb'">
            <input type="checkbox" value="mother" class="bulk-email-recipient" style="width: 16px; height: 16px; margin-right: 10px;">
            <span style="font-weight: 500; color: #1f2937; font-size: 14px;"><i class="fa fa-female" style="color: #ec4899; margin-right: 6px;"></i>Mothers</span>
          </label>
        </div>
        <div style="margin-top: 15px; display: flex; gap: 8px; justify-content: flex-end;">
          <button type="button" onclick="$('.close').click()" style="padding: 8px 16px; border: 2px solid #d1d5db; background: white; color: #374151; border-radius: 6px; font-weight: 500; cursor: pointer; font-size: 13px;">Cancel</button>
          <button type="button" onclick="sendBulkEmails()" style="padding: 8px 16px; border: none; background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; border-radius: 6px; font-weight: 500; cursor: pointer; font-size: 13px;"><i class="fa fa-paper-plane"></i> Send</button>
        </div>
      </div>
    `;
    showModalWithContent('modal_ajax', '<i class="fa fa-envelope"></i> Bulk Email Invoices', modalContent);
  }

  function sendBulkEmails() {
    const recipients = [];
    $('.bulk-email-recipient:checked').each(function() { recipients.push($(this).val()); });
    if(recipients.length === 0) { showAjaxModal_alert('Please select at least one recipient', 'error'); return; }
    $('.close').click();
    showAjaxModal_alert('Sending emails...', 'loading');
    const codes = selectedInvoices.map(inv => inv.code);
    $.ajax({
      url: '<?php echo site_url('invoice_email/bulk_send'); ?>',
      type: 'POST',
      data: { invoice_codes: codes, recipients: recipients },
      dataType: 'json'
    }).done(function(response) { showAjaxModal_alert(response.message, response.status); }).fail(function() { showAjaxModal_alert('Failed to send emails', 'error'); });
  }

  function bulkSmsInvoices() {
    if(selectedInvoices.length === 0) {
      showAjaxModal_alert('Please select at least one invoice', 'error');
      return;
    }
    const modalContent = `
      <div style="padding: 15px;">
        <p style="margin-bottom: 15px; color: #374151;">Send ${selectedInvoices.length} invoice(s) via SMS to:</p>
        <div style="display: flex; flex-direction: column; gap: 8px;">
          <label style="display: flex; align-items: center; padding: 8px; border: 2px solid #e5e7eb; border-radius: 6px; cursor: pointer;" onmouseover="this.style.borderColor='#f59e0b'" onmouseout="this.style.borderColor='#e5e7eb'">
            <input type="checkbox" value="student" class="bulk-sms-recipient" style="width: 16px; height: 16px; margin-right: 10px;" checked>
            <span style="font-weight: 500; color: #1f2937; font-size: 14px;"><i class="fa fa-user-graduate" style="color: #3b82f6; margin-right: 6px;"></i>Students</span>
          </label>
          <label style="display: flex; align-items: center; padding: 8px; border: 2px solid #e5e7eb; border-radius: 6px; cursor: pointer;" onmouseover="this.style.borderColor='#f59e0b'" onmouseout="this.style.borderColor='#e5e7eb'">
            <input type="checkbox" value="guardian" class="bulk-sms-recipient" style="width: 16px; height: 16px; margin-right: 10px;">
            <span style="font-weight: 500; color: #1f2937; font-size: 14px;"><i class="fa fa-user-shield" style="color: #10b981; margin-right: 6px;"></i>Guardians</span>
          </label>
          <label style="display: flex; align-items: center; padding: 8px; border: 2px solid #e5e7eb; border-radius: 6px; cursor: pointer;" onmouseover="this.style.borderColor='#f59e0b'" onmouseout="this.style.borderColor='#e5e7eb'">
            <input type="checkbox" value="father" class="bulk-sms-recipient" style="width: 16px; height: 16px; margin-right: 10px;">
            <span style="font-weight: 500; color: #1f2937; font-size: 14px;"><i class="fa fa-male" style="color: #6366f1; margin-right: 6px;"></i>Fathers</span>
          </label>
          <label style="display: flex; align-items: center; padding: 8px; border: 2px solid #e5e7eb; border-radius: 6px; cursor: pointer;" onmouseover="this.style.borderColor='#f59e0b'" onmouseout="this.style.borderColor='#e5e7eb'">
            <input type="checkbox" value="mother" class="bulk-sms-recipient" style="width: 16px; height: 16px; margin-right: 10px;">
            <span style="font-weight: 500; color: #1f2937; font-size: 14px;"><i class="fa fa-female" style="color: #ec4899; margin-right: 6px;"></i>Mothers</span>
          </label>
        </div>
        <div style="margin-top: 15px; display: flex; gap: 8px; justify-content: flex-end;">
          <button type="button" onclick="$('.close').click()" style="padding: 8px 16px; border: 2px solid #d1d5db; background: white; color: #374151; border-radius: 6px; font-weight: 500; cursor: pointer; font-size: 13px;">Cancel</button>
          <button type="button" onclick="sendBulkSms()" style="padding: 8px 16px; border: none; background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white; border-radius: 6px; font-weight: 500; cursor: pointer; font-size: 13px;"><i class="fa fa-paper-plane"></i> Send</button>
        </div>
      </div>
    `;
    showModalWithContent('modal_ajax', '<i class="fa fa-mobile"></i> Bulk SMS Invoices', modalContent);
  }

  function sendBulkSms() {
    const recipients = [];
    $('.bulk-sms-recipient:checked').each(function() { recipients.push($(this).val()); });
    if(recipients.length === 0) { showAjaxModal_alert('Please select at least one recipient', 'error'); return; }
    $('.close').click();
    showAjaxModal_alert('Sending SMS...', 'loading');
    const codes = selectedInvoices.map(inv => inv.code);
    $.ajax({
      url: '<?php echo site_url('invoice_sms/bulk_send'); ?>',
      type: 'POST',
      data: { invoice_codes: codes, recipients: recipients },
      dataType: 'json'
    }).done(function(response) { showAjaxModal_alert(response.message, response.status); }).fail(function() { showAjaxModal_alert('Failed to send SMS', 'error'); });
  }

  let selectedStudentData = null;
  let searchTimeout = null;

  // Autocomplete functionality
  $('#global_search').on('input', function() {
    const searchTerm = $(this).val().trim();
    
    clearTimeout(searchTimeout);
    
    if(searchTerm.length < 2) {
      $('#search_suggestions').hide().empty();
      return;
    }
    
    searchTimeout = setTimeout(() => {
      $.ajax({
        url: '<?php echo site_url('admin/search_suggestions'); ?>',
        type: 'POST',
        dataType: 'json',
        data: { search: searchTerm },
        success: function(response) {
          if(response.status === 'success' && response.data.length > 0) {
            displaySuggestions(response.data);
          } else {
            $('#search_suggestions').hide().empty();
          }
        }
      });
    }, 300);
  });

  function displaySuggestions(data) {
    let html = '';
    data.forEach((item, index) => {
      html += `
        <div class="suggestion-item" onclick="selectSuggestion('${item.student_id}', '${item.student_name}')" style="padding: 10px 15px; border-bottom: 1px solid #e2e8f0; cursor: pointer; transition: all 0.2s;" onmouseover="this.style.background='linear-gradient(135deg, #667eea 0%, #764ba2 100%)'; this.style.color='white'; this.querySelector('.suggestion-meta').style.color='rgba(255,255,255,0.9)';" onmouseout="this.style.background='white'; this.style.color='#2d3748'; this.querySelector('.suggestion-meta').style.color='#718096';">
          <div style="display: flex; justify-content: space-between; align-items: center;">
            <div>
              <div style="font-weight: 600; font-size: 14px; margin-bottom: 3px;">
                <i class="fa fa-user" style="margin-right: 6px; font-size: 12px;"></i>${item.student_name}
              </div>
              <div class="suggestion-meta" style="font-size: 11px; color: #718096;">
                <span style="margin-right: 12px;"><i class="fa fa-id-card" style="margin-right: 4px;"></i>${item.student_code}</span>
                <span style="margin-right: 12px;"><i class="fa fa-school" style="margin-right: 4px;"></i>${item.class_name}</span>
                ${item.invoice_count > 0 ? '<span><i class="fa fa-file-invoice" style="margin-right: 4px;"></i>' + item.invoice_count + ' invoice(s)</span>' : ''}
              </div>
            </div>
            <i class="fa fa-arrow-right" style="font-size: 14px; opacity: 0.5;"></i>
          </div>
        </div>
      `;
    });
    $('#search_suggestions').html(html).slideDown(200);
  }

  function selectSuggestion(studentId, studentName) {
    $('#search_suggestions').hide();
    $('#global_search').val(studentName);
    
    showAjaxModal_alert('Loading student invoices...', 'loading');
    
    $.ajax({
      url: '<?php echo site_url('admin/global_invoice_search'); ?>',
      type: 'POST',
      dataType: 'json',
      data: { search: studentName },
      success: function(response) {
        if(response.status === 'success') {
          initBulkInvoiceTable(response.data);
          updateBulkStats(response.stats);
          $('#bulk_stats').slideDown(300);
          selectedStudentData = response.student_data;
          $('.close').click();
          toastr.success(response.message);
          
          // Scroll to bulk invoice section
          $('html, body').animate({
            scrollTop: $('#bulk_invoice_management').offset().top - 100
          }, 500);
        } else {
          showAjaxModal_alert(response.message, 'error');
        }
      },
      error: function(xhr) {
        showAjaxModal_alert('Search error: ' + xhr.responseText, 'error');
      }
    });
  }

  $(document).on('click', function(e) {
    if(!$(e.target).closest('#global_search, #search_suggestions').length) {
      $('#search_suggestions').hide();
    }
  });

  function globalSearch() {
    const searchTerm = $('#global_search').val().trim();
    
    if(!searchTerm) {
      showAjaxModal_alert('Please enter a search term', 'error');
      return;
    }

    showAjaxModal_alert('Searching...', 'loading');

    $.ajax({
      url: '<?php echo site_url('admin/global_invoice_search'); ?>',
      type: 'POST',
      dataType: 'json',
      data: { search: searchTerm },
      success: function(response) {
        if(response.status === 'success') {
          initBulkInvoiceTable(response.data);
          updateBulkStats(response.stats);
          $('#bulk_stats').slideDown(300);
          selectedStudentData = response.student_data;
          $('.close').click();
          toastr.success(response.message);
        } else {
          showAjaxModal_alert(response.message, 'error');
        }
      },
      error: function(xhr) {
        showAjaxModal_alert('Search error: ' + xhr.responseText, 'error');
      }
    });
  }

  function openTakePaymentModal() {
    let studentId = null;
    let studentName = null;

    if(selectedInvoices.length === 1) {
      const invoice = bulkInvoiceTable.rows().data().toArray().find(r => r.invoice_code === selectedInvoices[0].code);
      if(invoice) {
        studentId = invoice.student_id;
        studentName = invoice.student_name;
      }
    } else if(selectedStudentData) {
      studentId = selectedStudentData.student_id;
      studentName = selectedStudentData.student_name;
    }

    if(studentId) {
      window.location.href = '<?php echo site_url('admin/student_payment/'); ?>' + studentId;
      return;
    }

    const term = $('#bulk_term').val();
    const year = $('#bulk_year').val();
    const filter = $('#bulk_filter').val();
    const classId = $('#bulk_class').val();

    if(!term || !year) {
      showAjaxModal_alert('Please select term and year first', 'error');
      return;
    }

    showAjaxModal_alert('Loading students with outstanding balances...', 'loading');

    $.ajax({
      url: '<?php echo site_url('admin/get_students_with_dues'); ?>',
      type: 'POST',
      dataType: 'json',
      data: { term, year, filter, class_id: classId },
      success: function(response) {
        if(response.status === 'success') {
          //showModalWithContent('createModal', '<i class="fa fa-money-bill-wave"></i> Take Payment', response.html);
        } else {
          showAjaxModal_alert(response.message || 'Failed to load students', 'error');
        }
      },
      error: function(xhr) {
        showAjaxModal_alert('Error: ' + xhr.responseText, 'error');
      }
    });
  }

  function openViewReceiptsModal() {
    let studentId = null;
    let studentName = null;

    if(selectedInvoices.length === 1) {
      const invoice = bulkInvoiceTable.rows().data().toArray().find(r => r.invoice_code === selectedInvoices[0].code);
      if(invoice) {
        studentId = invoice.student_id;
        studentName = invoice.student_name;
      }
    } else if(selectedStudentData) {
      studentId = selectedStudentData.student_id;
      studentName = selectedStudentData.student_name;
    }

    loadModalContent('detailsModal', '<?php echo site_url('admin/view_student_receipts/'); ?>' + (studentId || ''), '<i class="fa fa-receipt"></i> View Receipts' + (studentName ? ' - ' + studentName : ''));
  }

  // Recent Receipts Functionality
  let recentReceiptsTable;

  function openRecentReceiptsModal() {
    const modalContent = `
      <style>
        @media (max-width: 768px) {
          .receipt-header-content { flex-direction: column !important; gap: 15px !important; }
          .receipt-stats { flex-direction: column !important; width: 100% !important; }
          .receipt-stat-card { width: 100% !important; }
          .receipt-filter-buttons { flex-direction: column !important; }
          .receipt-filter-buttons button { width: 100% !important; margin-left: 0 !important; }
          .receipt-modal-padding { padding: 15px !important; }
          .receipt-header-title { font-size: 20px !important; }
        }
        
        /* Receipt Action Popup Menu Styles */
        .receipt-action-popup-menu {
          position: fixed;
          top: 0;
          left: 0;
          background: white;
          border-radius: 12px;
          box-shadow: 0 10px 40px rgba(0,0,0,0.2), 0 0 0 1px rgba(0,0,0,0.05);
          min-width: 180px;
          z-index: 99999;
          opacity: 0;
          visibility: hidden;
          transform: scale(0.95) translateY(-5px);
          transition: all 0.15s ease;
          overflow: hidden;
          padding: 8px 0;
          pointer-events: none;
        }
        
        .receipt-action-popup-menu.show {
          opacity: 1;
          visibility: visible;
          transform: scale(1) translateY(0);
          pointer-events: auto;
        }
        
        .receipt-action-popup-item {
          display: flex;
          align-items: center;
          gap: 12px;
          padding: 12px 16px;
          font-size: 14px;
          font-weight: 500;
          color: #374151;
          text-decoration: none;
          transition: all 0.15s;
          border: none;
          background: none;
          width: 100%;
          cursor: pointer;
          text-align: left;
        }
        
        .receipt-action-popup-item:hover {
          background: #f3f4f6;
        }
        
        .receipt-action-popup-item i {
          width: 18px;
          text-align: center;
          font-size: 14px;
        }
        
        .receipt-action-popup-item.primary i { color: #667eea; }
        .receipt-action-popup-item.primary:hover { background: #ede9fe; color: #5a67d8; }
        
        .receipt-action-popup-item.warning i { color: #f59e0b; }
        .receipt-action-popup-item.warning:hover { background: #fef3c7; color: #d97706; }
        
        .receipt-action-popup-divider {
          height: 1px;
          background: #e5e7eb;
          margin: 6px 0;
        }
      </style>
      <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 2rem; border-radius: 12px 12px 0 0; margin: -20px -20px 0 -20px;">
        <div class="receipt-header-content" style="display: flex; justify-content: space-between; align-items: center; color: white;">
          <div>
            <h2 class="receipt-header-title" style="margin: 0; font-size: 28px; font-weight: 700; color: white;"><i class="fa fa-receipt"></i> Payment Receipts Analytics</h2>
            <p style="margin: 5px 0 0 0; opacity: 0.9; font-size: 14px;">Real-time payment tracking and financial insights</p>
          </div>
          <div id="receipt_stats" class="receipt-stats" style="display: flex; gap: 20px;">
            <div class="receipt-stat-card" style="text-align: center; background: rgba(255,255,255,0.2); padding: 15px 20px; border-radius: 10px; backdrop-filter: blur(10px);">
              <div style="font-size: 24px; font-weight: 700;">0</div>
              <div style="font-size: 11px; opacity: 0.9; text-transform: uppercase;">Total Receipts</div>
            </div>
            <div class="receipt-stat-card" style="text-align: center; background: rgba(255,255,255,0.2); padding: 15px 20px; border-radius: 10px; backdrop-filter: blur(10px);">
              <div style="font-size: 24px; font-weight: 700;"><?=$currency;?>0.00</div>
              <div style="font-size: 11px; opacity: 0.9; text-transform: uppercase;">Total Amount</div>
            </div>
          </div>
        </div>
      </div>
      
      <div class="receipt-modal-padding" style="padding: 25px; background: #f8f9fa;">
        <!-- Advanced Filters -->
        <div style="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); margin-bottom: 20px;">
          <div style="display: flex; align-items: center; margin-bottom: 15px;">
            <i class="fa fa-filter" style="color: #667eea; font-size: 18px; margin-right: 10px;"></i>
            <h4 style="margin: 0; font-weight: 600; color: #2d3748;">Advanced Filters</h4>
          </div>
          <!-- Single row with filters and buttons -->
          <div style="display: flex; gap: 10px; align-items: flex-end; flex-wrap: wrap;">
            <div style="flex: 1; min-width: 150px;">
              <label style="display: block; margin-bottom: 6px; font-weight: 600; font-size: 13px; color: #4a5568;"><i class="fa fa-school"></i> Class</label>
              <select id="receipt_class" class="form-control" style="height: 42px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px;">
                <option value="">All Classes</option>
                <?php 
                  $classes = getAllClassList();
                  foreach($classes as $cid) {
                    $cname = getFullClassName($cid);
                    echo '<option value="'.$cid.'">'.$cname.'</option>';
                  }
                ?>
              </select>
            </div>
            <?php if($boarding_system == 'yes'): ?>
            <div style="flex: 1; min-width: 150px;">
              <label style="display: block; margin-bottom: 6px; font-weight: 600; font-size: 13px; color: #4a5568;"><i class="fa fa-home"></i> Residence</label>
              <select id="receipt_boarding" class="form-control" style="height: 42px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px;">
                <option value="">All Students</option>
                <option value="Boarding">Boarding Only</option>
                <option value="Day">Day Only</option>
              </select>
            </div>
            <?php endif; ?>
            <div style="flex: 1; min-width: 150px;">
              <label style="display: block; margin-bottom: 6px; font-weight: 600; font-size: 13px; color: #4a5568;"><i class="fa fa-calendar"></i> Date From</label>
              <input type="date" id="receipt_date_from" class="form-control" style="height: 42px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px;">
            </div>
            <div style="flex: 1; min-width: 150px;">
              <label style="display: block; margin-bottom: 6px; font-weight: 600; font-size: 13px; color: #4a5568;"><i class="fa fa-calendar-check"></i> Date To</label>
              <input type="date" id="receipt_date_to" class="form-control" style="height: 42px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px;">
            </div>
            <!-- Buttons aligned with inputs -->
            <button type="button" onclick="loadRecentReceipts()" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; height: 42px; padding: 0 24px; border-radius: 8px; font-weight: 600; font-size: 14px; cursor: pointer; box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4); transition: all 0.3s; white-space: nowrap;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 16px rgba(102, 126, 234, 0.5)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 12px rgba(102, 126, 234, 0.4)'">
              <i class="fa fa-sync-alt"></i> Apply
            </button>
            <button type="button" onclick="$('#receipt_class, #receipt_date_from, #receipt_date_to').val(''); <?php if($boarding_system == 'yes'): ?>$('#receipt_boarding').val('');<?php endif; ?> loadRecentReceipts();" style="background: white; color: #667eea; border: 2px solid #667eea; height: 42px; padding: 0 24px; border-radius: 8px; font-weight: 600; font-size: 14px; cursor: pointer; transition: all 0.3s; white-space: nowrap;" onmouseover="this.style.background='#667eea'; this.style.color='white'" onmouseout="this.style.background='white'; this.style.color='#667eea'">
              <i class="fa fa-redo"></i> Reset
            </button>
            <button type="button" onclick="exportReceiptsToExcel()" style="background: #10b981; color: white; border: none; height: 42px; padding: 0 24px; border-radius: 8px; font-weight: 600; font-size: 14px; cursor: pointer; white-space: nowrap;">
              <i class="fa fa-file-excel"></i> Export
            </button>
          </div>
        </div>

        <!-- Data Table -->
        <div style="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
          <div class="table-responsive">
            <table id="recent_receipts_table" class="table table-hover" style="width:100%; margin: 0;">
              <thead style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                <tr>
                  <th style="padding: 15px; font-weight: 600; border: none;">Receipt #</th>
                  <th style="padding: 15px; font-weight: 600; border: none;">Invoice #</th>
                  <th style="padding: 15px; font-weight: 600; border: none;">Student</th>
                  <th style="padding: 15px; font-weight: 600; border: none;">Class</th>
                  <th style="padding: 15px; font-weight: 600; border: none;">Payment Method</th>
                  <th style="padding: 15px; font-weight: 600; border: none; text-align: right;">Amount (<?=$currency;?>)</th>
                  <th style="padding: 15px; font-weight: 600; border: none;">Date</th>
                  <th style="padding: 15px; font-weight: 600; border: none; text-align: center;">Actions</th>
                </tr>
              </thead>
              <tbody></tbody>
            </table>
          </div>
        </div>
      </div>
      
      <!-- Floating Receipt Action Popup Menu -->
      <div id="receiptActionPopupMenu" class="receipt-action-popup-menu">
        <button id="receiptViewBtn" class="receipt-action-popup-item primary">
          <i class="fa fa-eye"></i> View Receipt
        </button>
        <div class="receipt-action-popup-divider"></div>
        <button id="receiptModifyBtn" class="receipt-action-popup-item warning">
          <i class="fa fa-edit"></i> Modify Receipt
        </button>
      </div>
    `;
    
    showModalWithContent('budgetDetailsModal', '', modalContent);
    setTimeout(function() {
      initReceiptActionPopup();
      loadRecentReceipts();
    }, 500);
  }

  function loadRecentReceipts() {
    const classId = $('#receipt_class').val() || '';
    const boarding = $('#receipt_boarding').val() || '';
    const dateFrom = $('#receipt_date_from').val() || '';
    const dateTo = $('#receipt_date_to').val() || '';

    $.ajax({
      url: '<?php echo site_url('admin/get_recent_receipts'); ?>',
      type: 'POST',
      dataType: 'json',
      data: { class_id: classId, boarding: boarding, date_from: dateFrom, date_to: dateTo },
      success: function(response) {
        if(response.status === 'success') {
          initRecentReceiptsTable(response.data);
          toastr.success(response.message || 'Receipts loaded');
        } else {
          showAjaxModal_alert(response.message, 'error');
        }
      },
      error: function(xhr) {
        showAjaxModal_alert('Error: ' + xhr.responseText, 'error');
      }
    });
  }

  function initRecentReceiptsTable(data) {
    if(recentReceiptsTable) {
      recentReceiptsTable.destroy();
    }

    // Update stats
    const totalAmount = data.reduce((sum, r) => sum + parseFloat(r.amount_paid || 0), 0);
    $('#receipt_stats').html(`
      <div class="receipt-stat-card" style="text-align: center; background: rgba(255,255,255,0.2); padding: 15px 20px; border-radius: 10px; backdrop-filter: blur(10px);">
        <div style="font-size: 24px; font-weight: 700;">${data.length}</div>
        <div style="font-size: 11px; opacity: 0.9; text-transform: uppercase;">Total Receipts</div>
      </div>
      <div class="receipt-stat-card" style="text-align: center; background: rgba(255,255,255,0.2); padding: 15px 20px; border-radius: 10px; backdrop-filter: blur(10px);">
        <div style="font-size: 24px; font-weight: 700;"><?=$currency;?>${totalAmount.toLocaleString('en-US', {minimumFractionDigits: 2})}</div>
        <div style="font-size: 11px; opacity: 0.9; text-transform: uppercase;">Total Amount</div>
      </div>
    `);

    const columns = [
      { 
        data: 'receipt_code', 
        render: (d) => `<span style="font-weight: 700; color: #667eea; font-size: 14px;">#${d}</span>` 
      },
      { 
        data: 'invoice_code', 
        render: (d) => `<span style="font-weight: 600; color: #4a5568;">#${d}</span>` 
      },
      { 
        data: 'student_name',
        render: (d) => `<div style="font-weight: 600; color: #2d3748;">${d}</div>`
      },
      { 
        data: 'class_name',
        render: (d) => `<span style="font-size: 13px; color: #718096;">${d}</span>`
      }
    ];

    // Payment Method column
    columns.push({ 
      data: 'payment_method',
      render: (d) => {
        const methods = {
          'cash': '<span style="background: #10b981; color: white; padding: 6px 12px; border-radius: 6px; font-size: 11px; font-weight: 600;"><i class="fa fa-money-bill-wave"></i> Cash</span>',
          'cheque': '<span style="background: #3b82f6; color: white; padding: 6px 12px; border-radius: 6px; font-size: 11px; font-weight: 600;"><i class="fa fa-money-check"></i> Cheque</span>',
          'card': '<span style="background: #8b5cf6; color: white; padding: 6px 12px; border-radius: 6px; font-size: 11px; font-weight: 600;"><i class="fa fa-credit-card"></i> Card</span>',
          'mobile_money': '<span style="background: #f59e0b; color: white; padding: 6px 12px; border-radius: 6px; font-size: 11px; font-weight: 600;"><i class="fa fa-mobile-alt"></i> Mobile Money</span>',
          'bank_transfer': '<span style="background: #06b6d4; color: white; padding: 6px 12px; border-radius: 6px; font-size: 11px; font-weight: 600;"><i class="fa fa-university"></i> Bank Transfer</span>'
        };
        return methods[d] || '<span style="color: #6b7280;">N/A</span>';
      }
    });

    columns.push(
      { 
        data: 'amount_paid', 
        className: 'text-right', 
        render: (d) => `<span style="font-weight: 700; color: #10b981; font-size: 15px;">${parseFloat(d || 0).toLocaleString('en-US', {minimumFractionDigits: 2})}</span>` 
      },
      { 
        data: 'date',
        width: '140px',
        render: (d) => {
          const date = new Date(d * 1000);
          return `<div style="font-size: 13px; color: #4a5568; white-space: nowrap;">
            <div style="font-weight: 600;">${date.toLocaleDateString('en-US', {month: 'short', day: 'numeric', year: 'numeric'})}</div>
            <div style="font-size: 11px; color: #a0aec0;">${date.toLocaleTimeString('en-US', {hour: '2-digit', minute: '2-digit'})}</div>
          </div>`;
        }
      },
      { 
        data: null,
        orderable: false,
        className: 'text-center',
        width: '180px',
        render: (d, t, row) => `
          <div style="display: flex; gap: 8px; justify-content: center; align-items: center;">
            <button onclick="viewReceiptDetails('${row.receipt_code}', '${row.student_id}', '${row.amount_paid}', '${row.date}')" 
                    style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; padding: 8px 16px; border-radius: 6px; font-weight: 600; font-size: 12px; cursor: pointer; transition: all 0.3s; box-shadow: 0 2px 6px rgba(102, 126, 234, 0.3); white-space: nowrap;"
                    onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 10px rgba(102, 126, 234, 0.5)'"
                    onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 6px rgba(102, 126, 234, 0.3)'">
              <i class="fa fa-eye"></i> View
            </button>
            <button onclick="requestModification(${row.payment_id})" 
                    style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white; border: none; padding: 8px 16px; border-radius: 6px; font-weight: 600; font-size: 12px; cursor: pointer; transition: all 0.3s; box-shadow: 0 2px 6px rgba(245, 158, 11, 0.3); white-space: nowrap;"
                    onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 10px rgba(245, 158, 11, 0.5)'"
                    onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 6px rgba(245, 158, 11, 0.3)'">
              <i class="fa fa-edit"></i> Modify
            </button>
          </div>
        `
      }
    );

    recentReceiptsTable = $('#recent_receipts_table').DataTable({
      data: data,
      columns: columns,
      order: [[5, 'desc']], // Order by Date column (6th column, 0-indexed = 5)
      pageLength: 25,
      dom: '<"row"<"col-sm-6"l><"col-sm-6"f>>rtip',
      scrollX: false,
      autoWidth: false,
      responsive: true,
      language: {
        search: "_INPUT_",
        searchPlaceholder: "Search receipts...",
        lengthMenu: "Show _MENU_ receipts",
        info: "Showing _START_ to _END_ of _TOTAL_ receipts",
        infoEmpty: "No receipts found",
        infoFiltered: "(filtered from _MAX_ total receipts)"
      },
      drawCallback: function() {
        $('.dataTables_paginate .pagination').addClass('pagination-sm');
      }
    });
  }

  // Receipt Action Popup Menu Variables
  var currentReceiptData = null;
  var $receiptActionPopupMenu = null;

  // Initialize popup menu on modal load
  function initReceiptActionPopup() {
    $receiptActionPopupMenu = $('#receiptActionPopupMenu');
    
    // Close popup when clicking outside
    $(document).off('click.receiptPopup').on('click.receiptPopup', function(e) {
      if (!$(e.target).closest('.btn-action-menu, .receipt-action-popup-menu').length) {
        closeReceiptActionPopup();
      }
    });
    
    // Attach event handlers to menu items
    $('#receiptViewBtn').off('click').on('click', function() {
      if (currentReceiptData) {
        viewReceipt(
          currentReceiptData.receiptCode, 
          currentReceiptData.studentId, 
          currentReceiptData.amount, 
          currentReceiptData.date
        );
      }
      closeReceiptActionPopup();
    });
    
    $('#receiptModifyBtn').off('click').on('click', function() {
      if (currentReceiptData) {
        requestModification(currentReceiptData.paymentId);
      }
      closeReceiptActionPopup();
    });
  }

  function closeReceiptActionPopup() {
    if ($receiptActionPopupMenu) {
      $receiptActionPopupMenu.removeClass('show');
      $('.btn-action-menu').removeClass('active');
    }
  }

  function toggleReceiptActionMenu(btn, event) {
    event.stopPropagation();
    event.preventDefault();
    
    // Ensure we have a valid DOM element
    var btnElement = btn instanceof jQuery ? btn[0] : btn;
    var $btn = $(btnElement);
    
    // Store receipt data from button attributes
    currentReceiptData = {
      receiptCode: $btn.data('receipt-code'),
      studentId: $btn.data('student-id'),
      amount: $btn.data('amount'),
      date: $btn.data('date'),
      paymentId: $btn.data('payment-id')
    };
    
    // If menu is already open, close it
    if ($receiptActionPopupMenu && $receiptActionPopupMenu.hasClass('show')) {
      closeReceiptActionPopup();
      return;
    }
    
    closeReceiptActionPopup();
    
    // Force menu to be rendered but hidden to get proper dimensions
    $receiptActionPopupMenu.css({
      left: '-9999px',
      top: '-9999px',
      display: 'block',
      visibility: 'hidden'
    }).addClass('show');
    
    // Get actual menu dimensions
    var menuWidth = $receiptActionPopupMenu.outerWidth();
    var menuHeight = $receiptActionPopupMenu.outerHeight();
    
    // Hide again to reposition
    $receiptActionPopupMenu.css({
      display: '',
      visibility: ''
    }).removeClass('show');
    
    // Get button position relative to viewport
    var btnRect = btnElement.getBoundingClientRect();
    
    // Calculate initial position (below button, aligned to right edge of button)
    var left = btnRect.right - menuWidth;
    var top = btnRect.bottom + 5;
    
    // Adjust if menu would go off left edge of viewport
    if (left < 10) {
      left = btnRect.left; // Align to left edge of button instead
    }
    
    // Adjust if menu would go off bottom edge of viewport
    if (top + menuHeight > window.innerHeight) {
      top = btnRect.top - menuHeight - 5; // Show above button
    }
    
    // Ensure coordinates are within viewport bounds
    left = Math.max(10, Math.min(left, window.innerWidth - menuWidth - 10));
    top = Math.max(10, Math.min(top, window.innerHeight - menuHeight - 10));
    
    // Debug logging
    console.log('Button rect:', btnRect);
    console.log('Menu dimensions:', {width: menuWidth, height: menuHeight});
    console.log('Calculated position:', {left: left, top: top});
    console.log('Window dimensions:', {width: window.innerWidth, height: window.innerHeight});
    
    // Apply position and show menu
    $receiptActionPopupMenu.css({
      left: left + 'px',
      top: top + 'px'
    }).addClass('show');
    
    $btn.addClass('active');
  }

  function exportReceiptsToExcel() {
    if(!recentReceiptsTable || recentReceiptsTable.rows().count() === 0) {
      showAjaxModal_alert('No receipts to export', 'error');
      return;
    }

    const data = recentReceiptsTable.rows().data().toArray();
    const boardingEnabled = <?php echo $boarding_system == 'yes' ? 'true' : 'false'; ?>;

    // Prepare data for Excel
    const excelData = [];
    
    // Header row with styling
    const headers = ['Receipt #', 'Invoice #', 'Student Name', 'Class'];
    if(boardingEnabled) headers.push('Type');
    headers.push('Amount Paid', 'Date', 'Time');
    excelData.push(headers);

    // Data rows
    data.forEach(row => {
      const rowData = [
        row.receipt_code,
        row.invoice_code,
        row.student_name,
        row.class_name
      ];
      if(boardingEnabled) rowData.push(row.boarding_status);
      rowData.push(
        parseFloat(row.amount_paid || 0).toFixed(2),
        new Date(row.date * 1000).toLocaleDateString('en-US', {year: 'numeric', month: 'short', day: 'numeric'}),
        new Date(row.date * 1000).toLocaleTimeString('en-US', {hour: '2-digit', minute: '2-digit'})
      );
      excelData.push(rowData);
    });

    // Create workbook and worksheet
    const wb = XLSX.utils.book_new();
    const ws = XLSX.utils.aoa_to_sheet(excelData);

    // Set column widths
    const colWidths = [{wch: 15}, {wch: 15}, {wch: 25}, {wch: 20}];
    if(boardingEnabled) colWidths.push({wch: 12});
    colWidths.push({wch: 15}, {wch: 15}, {wch: 12});
    ws['!cols'] = colWidths;

    // Style header row
    const range = XLSX.utils.decode_range(ws['!ref']);
    for(let C = range.s.c; C <= range.e.c; ++C) {
      const address = XLSX.utils.encode_col(C) + '1';
      if(!ws[address]) continue;
      ws[address].s = {
        font: { bold: true, sz: 12, color: { rgb: 'FFFFFF' } },
        fill: { fgColor: { rgb: '667eea' } },
        alignment: { horizontal: 'center', vertical: 'center' }
      };
    }

    // Add worksheet to workbook
    XLSX.utils.book_append_sheet(wb, ws, 'Payment Receipts');

    // Generate filename
    const filename = 'Payment_Receipts_' + new Date().toISOString().split('T')[0] + '.xlsx';

    // Download file
    XLSX.writeFile(wb, filename);
    
    toastr.success('Excel file downloaded successfully');
  }

  function viewReceipt(receiptCode, studentId, amountPaid, timestamp) {
    // Call the receipt method with proper parameters: receipt_code, student_id, total_amount_paid, date_time
    window.open('<?php echo site_url('admin/receipt/'); ?>' + receiptCode + '/' + studentId + '/' + (amountPaid || 0) + '/' + (timestamp || Date.now()/1000), '_blank');
  }

  function requestModification(paymentId) {
    // First get the receipt code for this payment
    $.ajax({
      url: '<?php echo site_url("admin/get_payment_receipt_code"); ?>',
      type: 'POST',
      data: { payment_id: paymentId },
      dataType: 'json'
    }).done(function(data) {
      if(data.receipt_code) {
        // Check if there's already a pending request for this receipt
        $.ajax({
          url: '<?php echo site_url("admin/check_receipt_modification_request"); ?>',
          type: 'POST',
          data: { receipt_code: data.receipt_code },
          dataType: 'json'
        }).done(function(response) {
          if(response.has_pending) {
            showAjaxModal_alert('A pending modification request already exists for this receipt. Please wait for approval or rejection before submitting a new request.', 'warning');
          } else {
            loadModalContent('createModal', 
              '<?php echo site_url("admin/receipt_modification_modal/"); ?>' + paymentId, 
              '<i class="fa fa-edit"></i> Request Receipt Modification');
            
            // Fix: When nested modal closes, restore body scroll for parent modal
            $('#createModal').on('hidden.bs.modal', function() {
              if($('#budgetDetailsModal').hasClass('show') || $('#budgetDetailsModal').is(':visible')) {
                $('body').addClass('modal-open');
              }
            });
          }
        }).fail(function() {
          showAjaxModal_alert('Error checking request status', 'error');
        });
      }
    }).fail(function() {
      showAjaxModal_alert('Error retrieving payment information', 'error');
    });
  }

  function viewStudentReceipts(studentId, studentName) {
    loadModalContent('detailsModal', '<?php echo site_url('admin/view_student_receipts/'); ?>' + studentId, '<i class="fa fa-receipt"></i> Receipts - ' + studentName);
  }

  function view_receipts_modal(student_id, date = '', term = '') {
    if(date != '' && term == '') {
      showAjaxModal('<?php echo site_url('modal/popup/modal_view_receipts/'); ?>' + student_id + '/' + date, 'xlarge');
    } else if(date != '' && term != '') {
      showAjaxModal('<?php echo site_url('modal/popup/modal_view_receipts/'); ?>' + student_id + '/' + date + '/' + term, 'xlarge');
    } else {
      showAjaxModal('<?php echo site_url('modal/popup/modal_view_receipts/'); ?>' + student_id, 'xlarge');
    }
  }

  // Toggle Arrears Tab
  function toggleArrearsTab() {
    const isChecked = $('#toggle_arrears_tab').is(':checked');
    const value = isChecked ? 'yes' : 'no';
    
    $.ajax({
      url: '<?php echo site_url('admin/update_arrears_tab_setting'); ?>',
      type: 'POST',
      dataType: 'json',
      data: { enable: value },
      success: function(response) {
        if(response.status === 'success') {
          if(isChecked) {
            $('#arrears_tab_li').slideDown(300);
          } else {
            $('#arrears_tab_li').slideUp(300);
            if($('#bulk_arrears_import').hasClass('active')) {
              $('.nav-tabs a:first').tab('show');
            }
          }
        }
      }
    });
  }



  // Set initial toggle state on page load
  $(document).ready(function() {
    <?php if($this->db->get_where('settings', array('type' => 'enable_arrears_tab'))->row()->description == 'yes'): ?>
    $('#toggle_arrears_tab').prop('checked', true);
    <?php endif; ?>
  });

  // Load bulk arrears import modal when tab is shown
  $('a[href="#bulk_arrears_import"]').on('shown.bs.tab', function() {
    if($('#bulk_arrears_content').is(':empty')) {
      $('#bulk_arrears_content').html('<div class="text-center py-5"><i class="fa fa-spinner fa-spin fa-3x text-purple-600"></i><p class="mt-3 text-xl font-semibold">Loading...</p></div>');
      $.ajax({
        url: '<?php echo site_url('admin/bulk_arrears_import_modal'); ?>',
        type: 'GET',
        success: function(response) {
          $('#bulk_arrears_content').html(response);
        },
        error: function() {
          $('#bulk_arrears_content').html('<div class="alert alert-danger">Failed to load content</div>');
        }
      });
    }
  });

  // ============================================
  // AJAX PAGE REFRESH WITH TAB PERSISTENCE
  // ============================================
  let activeTab = 'bill_item';

  $('.nav-tabs a[data-toggle="tab"]').on('shown.bs.tab', function(e) {
      activeTab = $(e.target).attr('href').substring(1);
  });

  function refreshPageWithTab() {
      $.ajax({
          url: window.location.href,
          type: 'GET',
          dataType: 'html',
          success: function(response) {
              const $newContent = $(response).find('.row').first();
              $('.row').first().replaceWith($newContent);
              setTimeout(() => {
                  $('.nav-tabs a[href="#' + activeTab + '"').tab('show');
                  $('.select2').select2();
              }, 100);
          },
          error: function() {
              location.reload();
          }
      });
  }

  // Override showAjaxModal_alert for success to auto-refresh
  const originalShowAjaxModal = window.showAjaxModal_alert;
  window.showAjaxModal_alert = function(message, type, reloadOnSuccess) {
      originalShowAjaxModal(message, type, false);
      if(type === 'success') {
          setTimeout(() => {
              $('.close').click();
              // Reset mass invoice form before refresh
              if(activeTab === 'paid') {
                  $('#students_category').val('class').trigger('change');
                  $('#class_id2').val(null).trigger('change');
                  $('#student_selection_holder_mass').empty();
                  $('#invoice_items_holder2 .row').not('#177_1565053816').remove();
                  $('#177_1565053816_title').val('').trigger('change');
                  $('#177_1565053816_category').val('');
                  $('#177_1565053816_description').val('');
                  $('#177_1565053816_amount').val('');
                  item_ids = [];
                  mass_bill_items_array = [];
                  selected = [];
              }
              refreshPageWithTab();
          }, 2000);
      }
  };

  // View Fees Structure Function
  function viewFeesStructure() {
    const term = $('#term_mass').val();
    const year = $('#year_mass').val();
    const classIds = $('#class_id2').val();

    if(!term || !year) {
      showAjaxModal_alert('Please select term and year first', 'error');
      return;
    }

    showAjaxModal_alert('Loading fees structure...', 'loading');

    $.ajax({
      url: '<?php echo site_url('finance/get_fees_structure'); ?>',
      type: 'POST',
      dataType: 'json',
      data: { term, year, class_ids: classIds },
      success: function(response) {
        if(response.status === 'success') {
          // Store data in session via AJAX POST
          $.ajax({
            url: '<?php echo site_url('finance/store_fees_structure_data'); ?>',
            type: 'POST',
            dataType: 'json',
            data: {
              term: term,
              year: year,
              class_ids: classIds,
              data: response.data
            },
            success: function(storeResponse) {
              if (storeResponse.success) {
                $('.close').click();
                // Load modal with session token instead of full data
                const url = '<?php echo site_url('finance/fees_structure_view'); ?>?token=' + storeResponse.token;
                loadModalContent('createModal', url, '<i class="fa fa-list-alt"></i> Fees Structure');
                $('#createModal .modal-dialog').css({
                  'max-width': '1200px',
                  'width': '95%'
                });
                // Ensure modal body can scroll naturally with increased height
                $('#createModal .modal-body').css({
                  'max-height': 'calc(95vh - 100px)',
                  'overflow-y': 'auto',
                  'padding': '0'
                });
              } else {
                showAjaxModal_alert('Error: Failed to store data', 'error');
              }
            },
            error: function() {
              showAjaxModal_alert('Error storing data. Please try again.', 'error');
            }
          });
        } else {
          showAjaxModal_alert(response.message || 'Failed to load fees structure', 'error');
        }
      },
      error: function(xhr) {
        showAjaxModal_alert('Error loading fees structure', 'error');
      }
    });
  }

  function filterFeesTable() {
    const classFilter = $('#fees_filter_class').val().toLowerCase();
    const itemFilter = $('#fees_filter_item').val().toLowerCase();
    const searchTerm = $('#fees_search').val().toLowerCase();
    $('.fees-class-card').each(function() {
      const $card = $(this);
      const className = $card.data('class').toLowerCase();
      let showCard = !classFilter || className === classFilter;
      if(showCard && searchTerm) showCard = className.includes(searchTerm);
      if(showCard && itemFilter) {
        showCard = $card.find('.fees-item-row').filter(function() {
          return $(this).data('item').toLowerCase() === itemFilter;
        }).length > 0;
      }
      $card.toggle(showCard);
      if(showCard && itemFilter) {
        $card.find('.fees-item-row').each(function() {
          $(this).toggle($(this).data('item').toLowerCase() === itemFilter);
        });
      } else if(showCard) {
        $card.find('.fees-item-row').show();
      }
    });
  }
  


  function exportFeesStructure() {
    showAjaxModal_alert('Preparing professional export...', 'loading');
    const {term, year, classIds} = window.feesExportData;
    $.ajax({
      url: '<?php echo site_url('finance/export_fees_structure'); ?>',
      type: 'POST',
      dataType: 'json',
      data: { term, year, class_ids: classIds },
      success: function(response) {
        if(response.status === 'success') {
          $('.close').click();
          const ws = XLSX.utils.json_to_sheet(response.data);
          ws['!cols'] = [{wch: 20}, {wch: 35}, {wch: 45}, {wch: 15}, {wch: 10}, {wch: 15}];
          for(let cell in ws) {
            if(cell[0] === '!') continue;
            ws[cell].s = {
              font: {sz: 11, name: 'Calibri'},
              border: {top: {style: 'thin'}, bottom: {style: 'thin'}, left: {style: 'thin'}, right: {style: 'thin'}},
              alignment: {vertical: 'center', wrapText: true}
            };
            if(cell.match(/^[A-Z]1$/)) {
              ws[cell].s.font = {bold: true, sz: 12, color: {rgb: 'FFFFFF'}};
              ws[cell].s.fill = {fgColor: {rgb: '667eea'}};
              ws[cell].s.alignment = {horizontal: 'center', vertical: 'center'};
            }
          }
          const wb = XLSX.utils.book_new();
          XLSX.utils.book_append_sheet(wb, ws, 'Fees Structure');
          const filename = `Fees_Structure_Term${term}_${year}_${new Date().getTime()}.xlsx`;
          XLSX.writeFile(wb, filename);
          toastr.success('Fees structure exported successfully');
        } else {
          showAjaxModal_alert(response.message, 'error');
        }
      },
      error: function() { showAjaxModal_alert('Export failed', 'error'); }
    });
  }

  function viewBulkBillReport() {
    const term = $('#bulk_term').val();
    const year = $('#bulk_year').val();
    const filter = $('#bulk_filter').val();
    const classId = $('#bulk_class').val();

    if(!term || !year) {
      showAjaxModal_alert('Please select both term and year first', 'error');
      return;
    }

    if(filter === 'class' && !classId) {
      showAjaxModal_alert('Please select a class', 'error');
      return;
    }

    const sectionId = $('#bulk_class option:selected').data('section-id') || 1;
    const url = '<?php echo site_url('admin/print_terminal_bills/'); ?>' + classId + '/' + sectionId;
    window.open(url, '_blank');
  }

  // Initialize datepicker for all date fields
  $(document).ready(function() {
    $('.datepicker').datepicker({
      format: 'dd-mm-yyyy',
      autoclose: true,
      todayHighlight: true
    });
  });

</script>











<script>
// Bulk Bill Items Operations - Inline Editing
$(document).ready(function() {
  // Select all checkbox
  $('#select_all_bill_items').on('change', function() {
    $('.bill_item_checkbox').prop('checked', $(this).prop('checked'));
    updateBulkToolbar();
  });

  // Individual checkbox
  $(document).on('change', '.bill_item_checkbox', function() {
    updateBulkToolbar();
    // Uncheck select all if any checkbox is unchecked
    if(!$(this).prop('checked')) {
      $('#select_all_bill_items').prop('checked', false);
    }
  });

  updateBulkToolbar();
});

function updateBulkToolbar() {
  const selected = $('.bill_item_checkbox:checked').length;
  $('#selected_count').text(selected);
  
  if(selected > 0) {
    $('#bulk_actions_toolbar').slideDown();
  } else {
    $('#bulk_actions_toolbar').slideUp();
  }
}

function getSelectedBillItems() {
  const selected = [];
  $('.bill_item_checkbox:checked').each(function() {
    selected.push($(this).val());
  });
  return selected;
}

function clearSelection() {
  $('.bill_item_checkbox').prop('checked', false);
  $('#select_all_bill_items').prop('checked', false);
  updateBulkToolbar();
}

// Enable bulk edit mode - make all selected rows editable
function enableBulkEdit() {
  const selected = getSelectedBillItems();
  if(selected.length === 0) {
    showAjaxModal_alert('No items selected', 'error');
    return;
  }

  // Make all selected rows editable
  selected.forEach(function(id) {
    // Hide display, show input fields
    $('#display_title_'+id).slideUp('fast');
    $('#input_title_'+id).slideDown('fast');

    $('#display_category_'+id).slideUp('fast');
    $('#input_category_'+id).slideDown('fast');

    $('#display_class_category_'+id).slideUp('fast');
    $('#input_class_category_'+id).slideDown('fast');

    $('#display_specific_classes_'+id).slideUp('fast');
    $('#input_specific_classes_'+id).slideDown('fast', function() {
      // Reinitialize select2 for specific classes after showing
      $('#edit_bill_specific_class_ids_'+id).select2({
        width: '100%',
        placeholder: 'Select specific classes',
        allowClear: true
      });
    });

    $('#display_amount_'+id).slideUp('fast');
    $('#input_amount_'+id).slideDown('fast');

    $('#display_desc_'+id).slideUp('fast');
    $('#input_desc_'+id).slideDown('fast');

    // Disable individual edit button
    $('#edit_button_'+id).prop('disabled', true).css('opacity', '0.5');
  });

  // Show save/cancel, hide edit/delete buttons
  $('#bulk_edit_btn').hide();
  $('#bulk_delete_btn').hide();
  $('#bulk_save_btn').show();
  $('#bulk_cancel_btn').show();
}

// Cancel bulk edit - revert to display mode
function cancelBulkEdit() {
  const selected = getSelectedBillItems();

  selected.forEach(function(id) {
    // Show display, hide input fields
    $('#input_title_'+id).slideUp('fast');
    $('#display_title_'+id).slideDown('fast');

    $('#input_category_'+id).slideUp('fast');
    $('#display_category_'+id).slideDown('fast');

    $('#input_class_category_'+id).slideUp('fast');
    $('#display_class_category_'+id).slideDown('fast');

    $('#input_specific_classes_'+id).slideUp('fast');
    $('#display_specific_classes_'+id).slideDown('fast');

    $('#input_amount_'+id).slideUp('fast');
    $('#display_amount_'+id).slideDown('fast');

    $('#input_desc_'+id).slideUp('fast');
    $('#display_desc_'+id).slideDown('fast');

    // Re-enable individual edit button
    $('#edit_button_'+id).prop('disabled', false).css('opacity', '1');
  });

  // Show edit/delete, hide save/cancel buttons
  $('#bulk_edit_btn').show();
  $('#bulk_delete_btn').show();
  $('#bulk_save_btn').hide();
  $('#bulk_cancel_btn').hide();
}

// Save all bulk edits
function saveBulkEdit() {
  const selected = getSelectedBillItems();
  if(selected.length === 0) {
    showAjaxModal_alert('No items selected', 'error');
    return;
  }

  // Collect all changes
  const updates = [];
  selected.forEach(function(id) {
    const data = {
      id: id,
      title: $('#edit_bill_title_'+id).val(),
      desc: $('#edit_bill_description_'+id).val(),
      category: $('#edit_bill_category_'+id).val(),
      class_category: $('#edit_bill_class_category_'+id).val(),
      specific_class_ids: $('#edit_bill_specific_class_ids_'+id).val() ? $('#edit_bill_specific_class_ids_'+id).val().join(',') : '',
      amount: $('#edit_bill_amount_'+id).val()
    };
    updates.push(data);
  });

  showAjaxModal_alert('Saving ' + selected.length + ' item(s)...', 'loading');

  $.ajax({
    url: '<?=site_url('admin/invoice/bulk_save_bill_items');?>',
    type: 'POST',
    data: { updates: updates },
    dataType: 'json',
    success: function(response) {
      if(response.success) {
        showAjaxModal_alert(response.message, 'success');
        setTimeout(() => {location.reload();}, 1500);
      } else {
        showAjaxModal_alert(response.message, 'error');
      }
    },
    error: function() {
      showAjaxModal_alert('An error occurred', 'error');
    }
  });
}

function bulkDelete() {
  const selected = getSelectedBillItems();
  if(selected.length === 0) {
    showAjaxModal_alert('No items selected', 'error');
    return;
  }

  showConfirmModal(
    'Confirm Bulk Delete',
    'Are you sure you want to delete ' + selected.length + ' item(s)? This action cannot be undone!',
    function() {
      showAjaxModal_alert('Deleting items...', 'loading');
      
      $.ajax({
        url: '<?=site_url('admin/invoice/bulk_delete_bill_items');?>',
        type: 'POST',
        data: { ids: selected },
        dataType: 'json',
        success: function(response) {
          if(response.success) {
            showAjaxModal_alert(response.message, 'success');
            setTimeout(() => {location.reload();}, 1500);
          } else {
            showAjaxModal_alert(response.message, 'error');
          }
        },
        error: function() {
          showAjaxModal_alert('An error occurred', 'error');
        }
      });
    },
    'Delete',
    'danger'
  );
}

// View receipt details - opens receipt in new window
function viewReceiptDetails(receipt_code, student_id, amount, timestamp) {
  if (!receipt_code || !student_id) {
    console.error('Missing receipt_code or student_id');
    return;
  }
  window.open('<?php echo site_url('admin/receipt/'); ?>' + receipt_code + '/' + student_id + '/' + amount + '/' + timestamp, '_blank');
}
</script>
