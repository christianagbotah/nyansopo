<div class="col-md-8 col-sm-8">
           <div class="form-group col-sm-2 col-md-2">
              <label class="col-sm-3 col-md-3 control-label"><?php echo get_phrase('term');?></label>
              <div class="col-sm-9 col-md-9">
                <select name="term" class="form-control select2" style="width:100%;" required>
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
            </div>

            <div class="form-group col-sm-3 col-md-3">
              <label  class="col-sm-3 col-md-3 control-label"><?php echo get_phrase('year');?></label>
              <div class="col-sm-9 col-md-9">
                  <select name="year" class="form-control select2">
                  <?php $running_year = $this->db->get_where('settings' , array('type'=>'running_year'))->row()->description;?>
                  <option value="" disabled="true"><?php echo get_phrase('select_year');?></option>
                  <?php
                          echo populate_academic_year();
                        ?>
                  </select>
              </div>
          </div>         

          <div class="form-group col-sm-3 col-md-3">
		         <div class="form-group">
                  <label class="col-sm-3 control-label"><?php echo get_phrase('class');?></label>
                  <div class="col-sm-9">
                      <select name="class_id" class="form-control select2 class_id2"
                      	onchange="return get_class_students_mass(this.value)" required="">
                      	<option value=""><?php echo get_phrase('select_class');?></option>
                      	<?php
                                  getFullClassList();
                              ?>
                          
                      </select>
                  </div>
              </div>
            </div>
          </div>

          <div class="col-md-4">
            <div id="student_selection_holder_mass"></div>
          </div>
        </div>

        <div id="invoice_items_holder">
        <div class="row">
          <div class="col-md-8 col-sm-8">
             <div class="form-group col-sm-4 col-md-4">
                    <label class="col-sm-3 control-label"><?php echo get_phrase('title');?></label>
                    <div class="col-sm-9">
                        <input type="text" class="form-control" name="177_1565053816_title"
                              data-validate="required" data-message-required="<?php echo get_phrase('value_required');?>"/>
                    </div>
                </div>
                <div class="form-group col-sm-4 col-md-4">
                    <label class="col-sm-3 control-label"><?php echo get_phrase('description');?></label>
                    <div class="col-sm-9">
                        <input type="text" class="form-control" name="177_1565053816_description"/>
                    </div>
                </div>
           </div>
         </div>

         <div class="row">
           <div class="col-sm-8 col-md-8">
             <div class="form-group col-sm-3 col-md-3">
                    <label class="col-sm-3 control-label"><?php echo get_phrase('date');?></label>
                    <div class="col-sm-9">
                        <input type="date" class="form-control" name="177_1565053816_date"
                              data-validate="required" data-message-required="<?php echo get_phrase('value_required');?>"/>
                    </div>
                </div>

                <div class="form-group col-sm-3 col-md-3">
                  <label class="col-sm-3 control-label"><?php echo get_phrase('total');?></label>
                  <div class="col-sm-9">
                    <input type="text" class="form-control total_amount" name="177_1565053816_amount"
                        placeholder="<?php echo get_phrase('total_amount');?>"
                            data-validate="required" data-message-required="<?php echo get_phrase('value_required');?>"/>
                  </div>
              </div>

              <div class="col-sm-2 col-md-2">
                <div class="col-sm-3 col-md-3"><a href="javascript:(void);" onclick="add_invoice_item2('16484_1565043896')" id="177_1565053816_add_btn" class="btn btn-info btn-sm"><i class="fa fa-plus"></i></a></div>
                
              </div><br>
           </div>
         </div>
        </div>

        
        <div class="row">
          <div class="col-sm-8 col-md-8" align="right"><h4 class="btn btn-lg btn-success" id="success_note2" style="display: none">Mass Bills/Invoices were created successfully!</h4></div>
        </div>
      </div>