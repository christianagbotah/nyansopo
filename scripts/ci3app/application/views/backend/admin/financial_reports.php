<hr>
<div class="row">
    <div class="col-md-4" id="tile1">
    
        <div class="tile-stats tile-blue">
            <div class="icon" style="margin-bottom: 20px;"><i class="fa fa-dollar" style="padding-right: 10px;"></i></div>
            <sub style="font-weight: bold; color: #ffffff; font-size: 15px;"><?php echo 'ANNUAL'; ?></sub> <div class="num" data-start="0"
                    data-postfix="" data-duration="500" data-delay="0">REPORT</div>

                    <p style="font-weight: bold; position: absolute; right: 20px; bottom: 10px; display: none" id="alert1"><em>Click to generate</em></p>
        </div>
        
    </div>

    <div class="col-md-4" id="tile2">
    
        <div class="tile-stats tile-green">
            <div class="icon" style="margin-bottom: 20px;"><i class="fa fa-money" style="padding-right: 10px;"></i></div>
            <sub style="font-weight: bold; color: #ffffff; font-size: 15px;"><?php echo 'TERMLY'; ?></sub> <div class="num" data-start="0"
                    data-postfix="" data-duration="500" data-delay="0">REPORT</div>      

                    <p style="font-weight: bold; position: absolute; right: 20px; bottom: 10px; display: none" id="alert2"><em>Click to generate</em></p> 
        </div>
        
    </div>

    <div class="col-md-4" id="tile3">
    
        <div class="tile-stats tile-black">
            <div class="icon" style="margin-bottom: 20px;"><i class="fa fa-bus" style="padding-right: 10px;"></i></div>
            <sub style="font-weight: bold; color: #ffffff; font-size: 15px;"><?php echo 'WEEKLY'; ?></sub> <div class="num" data-start="0"
                    data-postfix="" data-duration="500" data-delay="0">REPORT</div>

                    <p style="font-weight: bold; position: absolute; right: 20px; bottom: 10px; display: none" id="alert3"><em>Click to generate</em></p>
        </div>
        
    </div>
</div>


<div class="row">
            <div class="col-md-4">
                <div class="tile-stats tile-red" id="tile_show1" style="display: none">
                <div class="icon" style="margin-bottom: 20px;"><i class="fa fa-dollar" style="padding-right: 10px;"></i></div>
                <sub style="font-weight: bold; color: #ffffff; font-size: 15px;"><?php echo 'SELECT YEAR'; ?></sub><br>

                    <?php echo form_open(site_url('admin/financial_reports/annual'), array('class' => 'form-horizontal form-group-bordered', 'id' => 'annual_reports')); ?>
                        <div class="col-sm-12">
                        <div class="form-group">
                          <label  class="control-label col-sm-4"><?php echo get_phrase('yEAR');?></label>
                          <div class="col-sm-8">  
                              <select name="year" class="form-control select2" id="year" required="required">
                              <?php
                          echo populate_academic_year();
                        ?>
                              </select>
                            </div>
                        </div>     
                    </div>
                    <div class="col-sm-3"></div>
                    <div class="col-sm-3"></div>
                    <div class="col-sm-6 form-group">
                        <button class="btn btn-info btn-lg">GENERATE</button>
                    </div>
                    
                    <?php echo form_close();?>
                </div>
            </div>       


        <div class="col-md-4">
            <div class="tile-stats tile-red" id="tile_show2" style="display: none">
                <div class="icon" style="margin-bottom: 20px;"><i class="fa fa-dollar" style="padding-right: 10px;"></i></div>
                <sub style="font-weight: bold; color: #ffffff; font-size: 15px;"><?php echo 'SELECT TERM'; ?></sub><br>

                <?php echo form_open(site_url('admin/financial_reports/termly'), array('class' => 'form-horizontal form-group-bordered', 'id' => 'termly_reports')); ?>
                    <div class="col-sm-12">
                            <div class="col-sm-5">
                            <div class="form-group">
                                <label  class="control-label col-sm-12"><?php echo get_phrase('term');?></label>
                                  <div class="col-sm-12">
                                      <input type="number" name="term" min="1" max="3" id="term" placeholder="Enter" class="form-control" value="" size="20" required="required">
                                  </div>
                            </div> 
                            </div>
                            <div class="col-sm-7">
                                <div class="form-group">
                                  <label  class="control-label col-sm-12"><?php echo get_phrase('year');?></label>
                                  <div class="col-sm-12">  
                                      <select name="year" class="form-control select2" id="year" required="required">
                                      <?php
                          echo populate_academic_year();
                        ?>
                                      </select>
                                    </div>
                                </div> 
                                
                            </div> 
                    </div>
                    <div class="col-sm-3"></div>
                    <div class="col-sm-3"></div>
                    <div class="col-sm-6 form-group">
                        <button class="btn btn-info btn-lg">GENERATE</button>
                    </div>
                    
                    <?php echo form_close();?>
            </div>     
        </div>


        <div class="col-md-4">
        
            <div class="tile-stats tile-red" id="tile_show3" style="display: none">
                <div class="icon" style="margin-bottom: 20px;"><i class="fa fa-dollar" style="padding-right: 10px;"></i></div>
                <sub style="font-weight: bold; color: #ffffff; font-size: 15px;"><?php echo 'SELECT DATE INTERVAL'; ?></sub> <br><br>

                <?php echo form_open(site_url('admin/financial_reports/weekly'), array('class' => 'form-horizontal form-group-bordered', 'id' => 'weekly_reports')); ?>
                    <div class="row">
                        <div class="form-group">
                            <div class="col-sm-12">
                            <div class="col-sm-8">
                                <div class="input-group">
                                    <input type="text" class="form-control datepicker" name="date_start" id="date_start" data-template="dropdown" value="" data-format="dd-mm-yyyy" placeholder="Click to choose"data-validate="required" data-message-required="<?php echo get_phrase('value_required');?>" />
                                    
                                    <div class="input-group-addon">
                                        <a href="#"><i class="entypo-calendar"></i></a>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-4"></div>
                            </div>

                            <div class="col-12">
                            <div class="col-sm-5"></div>
                            <div class="col-sm-2"><p><strong><?php echo get_phrase('to');?></strong></p></div>
                            <div class="col-sm-5"></div>
                            </div>

                            <div class="col-sm-12">
                            <div class="col-sm-4"></div>
                            <div class="col-sm-8">
                                <div class="input-group">
                                    <input type="text" class="form-control datepicker" name="date_end" id="date_end" data-template="dropdown" value="" placeholder="Click to choose" data-format="dd-mm-yyyy" data-validate="required" data-message-required="<?php echo get_phrase('value_required');?>" />
                                    
                                    <div class="input-group-addon">
                                        <a href="#"><i class="entypo-calendar"></i></a>
                                    </div>
                                </div>
                            </div>
                            </div>
                        </div>       
                    </div>
                    <div class="col-sm-3"></div>
                    <div class="col-sm-3"></div>
                    <div class="col-sm-6 form-group">
                        <button class="btn btn-info btn-lg">GENERATE</button>
                    </div>
                    
                    <?php echo form_close();?>
            </div>        
        </div> 

</div>

<div id="reports_holder"><div></div></div>



<?php echo form_close();?>

<script type="text/javascript">

    //show the click to generate text and change the cursor to hand
    $('#tile1').mouseenter(function(event) {
        /* Act on the event */
        $('#tile1').css({
            cursor: 'pointer'
        });

        $('#alert1').fadeIn('500');
    });

    $('#tile1').mouseleave(function(event) {
        /* Act on the event */
        $('#alert1').fadeOut('500');
    });

    //show the selection tile when a click event occurs
    $('#tile1').click(function(event) {
        /* Act on the event */
        $('#tile_show2').fadeOut('500');
        $('#tile_show3').fadeOut('500');
        $('#reports_holder div').fadeOut('500');
        $('#tile_show1').fadeIn('500');
    });


    $('#tile2').mouseenter(function(event) {
        /* Act on the event */
        $('#tile2').css({
            cursor: 'pointer'
        });

        $('#alert2').fadeIn('500');
    });

    $('#tile2').mouseleave(function(event) {
        /* Act on the event */
        $('#alert2').fadeOut('500');
    });

    //show the selection tile when a click event occurs
    $('#tile2').click(function(event) {
        /* Act on the event */
        $('#tile_show1').fadeOut('500');
        $('#tile_show3').fadeOut('500');
        $('#reports_holder div').fadeOut('500');
        $('#tile_show2').fadeIn('500');
    });


    $('#tile3').mouseenter(function(event) {
        /* Act on the event */
        $('#tile3').css({
            cursor: 'pointer'
        });

        $('#alert3').fadeIn('500');
    });

    $('#tile3').mouseleave(function(event) {
        /* Act on the event */
        $('#alert3').fadeOut('500');
    });

    //show the selection tile when a click event occurs
    $('#tile3').click(function(event) {
        /* Act on the event */
        $('#tile_show2').fadeOut('500');
        $('#tile_show1').fadeOut('500');
        $('#reports_holder div').fadeOut('500');
        $('#tile_show3').fadeIn('500');
    });

    //time to send and receive data via ajax
    //ANNUAL REPORT
    $('#annual_reports').submit(function(event) {
        /* Act on the event */
        event.preventDefault();
        let year = $('#year').val();
        if(year == '' || year == null) {
            return false;
        } else {
            $.ajax({
                url: '<?php echo site_url('admin/financial_reports/annual/'); ?>' + year,
                type: 'POST',
                success: function(response) {
                    $('#reports_holder div').fadeIn('500');
                    $('#reports_holder div').html(response);
                }

            });
            
        }
    });

    //TERMLY REPORT
    $('#termly_reports').submit(function(event) {
        /* Act on the event */
        event.preventDefault();
        let year = $('#year').val();
        let term = $('#term').val();
        if(year == '' || year == null || term == '' || term == null) {
            return false;
        } else {
            $.ajax({
                url: '<?php echo site_url('admin/financial_reports/termly/'); ?>' + year + '/' + term,
                type: 'POST',
                success: function(response) {
                    $('#reports_holder div').fadeIn('500');
                    $('#reports_holder div').html(response);
                }

            });
            
        }
    });

    //WEEKLY REPORT
    $('#weekly_reports').submit(function(event) {
        /* Act on the event */
        event.preventDefault();
        let date_start = $('#date_start').val();
        let date_end = $('#date_end').val();
        if(date_start == '' || date_start == null || date_end == '' || date_end == null) {
            return false;
        } else {
            $.ajax({
                url: '<?php echo site_url('admin/financial_reports/weekly/'); ?>' + date_start + '/' + date_end,
                type: 'POST',
                success: function(response) {
                    $('#reports_holder div').fadeIn('500');
                    $('#reports_holder div').html(response);
                }

            });
            
        }
    });

</script>