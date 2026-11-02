                        <!-- sample modal content -->
                        <div class="modal fade" id="EduModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel1">
                            <div class="modal-dialog" role="document">
                                <div class="modal-content ">
                                    <div class="modal-header">
                                        <h4 class="modal-title" id="exampleModalLabel1">Disciplinary Notice</h4>
                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                    </div>
                                    <form method="post" action="Add_Education" id="educationmodal" enctype="multipart/form-data">
                                    <div class="modal-body">
			                                    <div class="form-group">
			                                        <label>Degree Name</label>
			                                        <input type="text" name="name" class="form-control form-control-line" placeholder=" Degree Name" minlength="2" required> 
			                                    </div>
			                                    <div class="form-group">
			                                        <label>Institute name</label>
			                                        <input type="text" name="institute" class="form-control form-control-line" placeholder=" Institute name" minlength="7" required> 
			                                    </div>
			                                    <div class="form-group">
			                                        <label>Result</label>
			                                        <input type="text" name="result" class="form-control form-control-line" placeholder=" Result" minlength="2" required> 
			                                    </div>
			                                    <div class="form-group">
			                                        <label>Passing Year</label>
			                                        <input type="text" name="year" class="form-control form-control-line" placeholder="Passing Year"> 
			                                    </div>                                        
                                        
                                    </div>
                                    <div class="modal-footer">
                                        <input type="hidden" name="emid" value=""> 
                                        <input type="hidden" name="id" value=""> 
                                        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                                        <button type="submit" class="btn btn-primary">Submit</button>
                                    </div>
                                    </form>
                                </div>
                            </div>
                        </div>                        
                        <!-- sample modal content -->
                        <div class="modal fade" id="ExpModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel1">
                            <div class="modal-dialog" role="document">
                                <div class="modal-content ">
                                    <div class="modal-header">
                                        <h4 class="modal-title" id="exampleModalLabel1">Experience Modal</h4>
                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                    </div>
                                    <form method="post" action="Add_Experience" id="experiencemodal" enctype="multipart/form-data">
                                    <div class="modal-body">
			                                    	<div class="form-group">
			                                    	    <label> Company Name</label>
			                                    	    <input type="text" name="company_name" class="form-control form-control-line company_name" placeholder="Company Name" minlength="2" required> 
			                                    	</div>
			                                    	<div class="form-group">
			                                    	    <label>Position</label>
			                                    	    <input type="text" name="position_name" class="form-control form-control-line position_name" placeholder="Position" minlength="3" required> 
			                                    	</div>
			                                    	<div class="form-group">
			                                    	    <label>Address</label>
			                                    	    <textarea name="address" class="form-control form-control-line duty" placeholder=" Duty" minlength="7" required></textarea>
			                                    	</div>
			                                    	<div class="form-group">
			                                    	    <label>Work Duration</label>
			                                    	    <input type="text" name="work_duration" class="form-control form-control-line working_period" placeholder="Working Duration" required> 
			                                    	</div>                                      
                                        
                                    </div>
                                    <div class="modal-footer">
                                        <input type="hidden" name="emid" value=""> 
                                        <input type="hidden" name="id" value=""> 
                                        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                                        <button type="submit" class="btn btn-primary">Submit</button>
                                    </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- sample modal content -->
                        <div class="modal fade" id="Leave-Modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel1">
                            <div class="modal-dialog" role="document">
                                <div class="modal-content ">
                                    <div class="modal-header">
                                        <h4 class="modal-title" id="exampleModalLabel1">Assign Leave Modal</h4>
                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                    </div>
                                    <form action="Assign_leave" method="post" id="leavemodal" enctype="multipart/form-data">
                                    <div class="modal-body">
                                        <div class="form-group">
                                        <label class="">Leave Type</label>                                 
                                         <select name="typeid"  class="select2 form-control custom-select" style="width: 100%" id="typeid" required>
                                          <option value="">Select Here...</option>
                                          <?php 
                                            $leavetypes = $this->leave_model->GetleavetypeInfo();
                                          ?>
                                           <?php foreach($leavetypes as $value): ?>
                                            <option value="<?php echo $value->type_id ?>"><?php echo $value->name ?></option>
                                            <?php endforeach; ?>
                                        </select>          
                                        </div>
                                     <div class="form-group">
                                            <label>day</label>
                                            <input type="number" name="noday" class="form-control form-control-line" placeholder="Leave Day" required> 
                                     </div>

                                        <div class="form-group">
                                        <label class="">Year</label>                                 
                                        <select name="year" class="select2 form-control custom-select" style="width: 100%" id="year" required>
                                         <option value="">Select Here...</option>
                                          <?php 
                                           for ($x = 2021; $x < 3000; $x++){
                                            echo '<option value='.$x.'>'.$x.'</option>';            
                                           }
                                            ?>
                                        </select>          
                                        </div>
                                      
                                        
                                    </div>
                                    <div class="modal-footer">
                                        <input type="hidden" name="em_id" value=""> 
                                        <input type="hidden" name="id" value=""> 
                                        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                                        <button type="submit" class="btn btn-primary">Assign</button>
                                    </div>
                                    </form>
                                </div>
                            </div>
                        </div>