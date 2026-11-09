<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 * Invoice Model - Now Sync-Aware
 * 
 * Extended from MY_Model to automatically track sync status.
 * All INSERT, UPDATE, DELETE operations will automatically set:
 * - sync_status = 'PENDING'
 * - last_modified_at = current timestamp
 * - device_id = configured device ID
 * - last_modified_by = current user ID
 */
class Invoice_model extends MY_Model {

    function __construct() {
        parent::__construct();
    }

    function clear_cache() {
        $this->output->set_header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
        $this->output->set_header('Pragma: no-cache');
    }

    //get invoice items for each class
    function get_invoice_title($class_id) {
        $running_year = get_settings('running_year');

        $get_title = $this->db
                            ->select('title')
                            ->from('invoice')
                            ->distinct()
                            ->where('can_delete !=', 'trash')
                            ->where('class_id', $class_id)
                            ->where('year', $running_year)
                            ->get();

        if($get_title->num_rows() < 1) {
            $p1 = explode('-', $running_year)[0] - 1;
            $p2 = explode('-', $running_year)[1] - 1;
            $last_year = $p1.'-'.$p2;

            $get_title = $this->db
                            ->select('title')
                            ->from('invoice')
                            ->where('can_delete !=', 'trash')
                            ->distinct()
                            ->where('class_id', $class_id)
                            ->where('year', $last_year)
                            ->get();
        }

        return $get_title;
    }

    //display the titles chosen already
    function display_titles($class_id, $type) {
        $get_title_query = $this->get_invoice_title($class_id);


        if($get_title_query->num_rows() > 0) {
            $get_title_array = $get_title_query->result_array();

            foreach($get_title_array as $row) {
                $data = array(
                    'title' => $row['title'],
                    'class_id' => $class_id
                );

                $this->db->where('can_delete !=', 'trash');
                $amount = $this->db->get_where('invoice', $data)->row()->amount;

                $this->db->where('can_delete !=', 'trash');
                $description = $this->db->get_where('invoice', $data)->row()->description;

                $item_id = rand().'_'.time();

                if($type == 'single') {

                    echo '<div class="row" id="'.$item_id.'">
                                  <div class="form-group col-sm-3 col-md-3">
                                      <label class="col-sm-3 control-label">Title</label>
                                      <div class="col-sm-9">
                                          <input type="text" class="form-control" id="'.$item_id.'_title" name="'.$item_id.'_title" value="'.$row['title'].'"
                                                data-validate="required" data-message-required="required"/>
                                      </div>
                                  </div>
                                  <div class="form-group col-sm-3 col-md-3">
                                      <label class="col-sm-3 control-label">Description</label>
                                      <div class="col-sm-9">
                                          <input type="text" class="form-control" value="'.$description.'" name="'.$item_id.'_description" readonly="readonly"/>
                                      </div>
                                  </div>


                                  <div class="form-group col-sm-3 col-md-3">
                                    <label class="col-sm-4 control-label">Amount</label>
                                    <div class="col-sm-8">
                                      <input type="text" class="form-control total_amount" id="'.$item_id.'_amount"  name="'.$item_id.'_amount"
                                          placeholder="Total amount" value="'.$amount.'"
                                              data-validate="required" data-message-required="required"/>
                                    </div>
                                </div>

                                <div class="col-sm-2 col-md-2">
                                  <div class="col-sm-3 col-md-3"><a href="javascript:(void);" onclick="add_invoice_item(\''.$item_id.'\', \'single\')" id="'.$item_id.'_add_btn" class="btn btn-info btn-sm"><i class="fa fa-plus"></i></a></div>
                                  <div class="col-sm-3 col-md-3"><a href="javascript:(void);" onclick="remove_invoice_item(\''.$item_id.'\', \'single\')" class="btn btn-danger btn-sm"><i class="fa fa-remove"></i></a></div>
                                  
                                </div><br>
                            </div>';

                } else if($type == 'mass') {
                    echo '
                                <div class="row" id="'.$item_id.'">
                                  <div class="form-group col-sm-4 col-md-4">
                                      <label class="col-sm-2 control-label">Title</label>
                                      <div class="col-sm-10">
                                          <input type="text" class="form-control" id="'.$item_id.'_title" name="'.$item_id.'_title" value="'.$row['title'].'"
                                                data-validate="required" data-message-required="required"/>
                                      </div>
                                  </div>
                                  <div class="form-group col-sm-4 col-md-4">
                                      <label class="col-sm-3 control-label">Description</label>
                                      <div class="col-sm-9">
                                          <input type="text" class="form-control" value="'.$description.'" name="'.$item_id.'_description"/>
                                      </div>
                                  </div>


                                  <div class="form-group col-sm-3 col-md-3">
                                    <label class="col-sm-4 control-label">Amount</label>
                                    <div class="col-sm-8">
                                      <input type="text" class="form-control total_amount" id="'.$item_id.'_amount"  name="'.$item_id.'_amount"
                                          placeholder="Total amount" value="'.$amount.'"
                                              data-validate="required" data-message-required="required"/>
                                    </div>
                                </div>

                                <div class="col-sm-2 col-md-2">
                                  <div class="col-sm-6 col-md-3"><a href="javascript:(void);" onclick="add_invoice_item2(\''.$item_id.'\', \'mass\')" id="'.$item_id.'_add_btn" class="btn btn-info btn-sm"><i class="fa fa-plus"></i></a></div>
                                  <div class="col-sm-6 col-md-3"><a href="javascript:(void);" onclick="remove_invoice_item2(\''.$item_id.'\', \'mass\')" class="btn btn-danger btn-sm"><i class="fa fa-remove"></i></a></div>
                                  
                                </div><br>
                                </div>';
                }
            }
        } else {
            echo 'none';
        }
    }

    //get all invoices
    function get_all_invoices_by_year($year) {
      $this->db->select('invoice_code');
      $this->db->distinct();
      $this->db->where('year', $year);
      $this->db->where('can_delete !=', 'trash');
      $invoices = $this->db->get('invoice')->result();
      return $invoices;
    }

    
 }
