<html>
<head>

    <link rel="stylesheet" href="<?php echo base_url('assets/js/jquery-ui/css/no-theme/jquery-ui-1.10.3.custom.min.css'); ?>"/>
    <link rel="stylesheet" href="<?php echo base_url('assets/css/font-icons/entypo/css/entypo.css.min.css'); ?>"/>
    <link rel="stylesheet" href="<?php echo base_url(); ?>assets/cdn/fonts/noto-sans.css"/>
    <link rel="stylesheet" href="<?php echo base_url('assets/css/bootstrap.min.css'); ?>"/>
    <link rel="stylesheet" href="<?php echo base_url('assets/css/neon-core.css'); ?>"/>
    <link rel="stylesheet" href="<?php echo base_url('assets/css/neon-theme.css'); ?>"/>
    <link rel="stylesheet" href="<?php echo base_url('assets/css/neon-forms.css'); ?>"/>


    <style>

        .id-card-holder {
            width: 225px;
            padding: 4px;
            margin: 0 auto;

            border-radius: 5px;
            position: relative;
        }

        .id-card-holder:after {
            content: '';
            width: 7px;
            display: block;
            height: 100px;
            position: absolute;
            top: 105px;
            border-radius: 0 5px 5px 0;
        }

        .id-card-holder:before {
            content: '';
            width: 7px;
            display: block;
            height: 100px;
            position: absolute;
            top: 105px;
            left: 222px;
            border-radius: 5px 0 0 5px;
        }

        .id-card {

            background-color: #fff;
            padding: 10px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 0 1.5px 0px #b9b9b9;
        }

        .id-card img {
            margin: 0 auto;
        }

        .header img {
            width: 100px;
            margin-top: 15px;
        }

        .cBody {
          background-color: #bb1186e3 !important;
          margin: -60px -10px -15px -10px;
          padding-top: 50px;
          padding-bottom: 5px;
          border-top-right-radius: 65%;
          border-top-left-radius: 65%;
          z-index: 999;

        }

        .cBody h2, .cBody td, .cBody td b {
          color: #ffffff !important;
        }

        .bDesign {
          background-color: #000000 !important;
          margin: -40px -10px -15px -10px;
          padding-top: 50px;
          padding-bottom: 5px;
          border-top-right-radius: 2000%;
          border-top-left-radius: 2000%;
        }

        .photo img {
            width: 90px;
            margin-top: 15px;
            border-width: 6px;
          border-style: groove;
          border-right-color: #38c1ea;
          border-bottom-color: #38c1ea;
          border-left-color: #f360b8;
          border-top-color: #f360b8;
        }

        .id-card h2 {
            font-size: 15px;
            margin: 5px 0;
        }

        .id-card h3 {
            font-size: 12px;
            margin: 2.5px 0;
            font-weight: 300;
        }

        .qr-code img {
            width: 50px;
        }

        .id-card p {
            font-size: 5px;
            margin: 2px;
        }

        .id-card-hook {
            background-color: #000;
            width: 70px;
            margin: 0 auto;
            height: 15px;
            border-radius: 5px 5px 0 0;
        }

        .id-card-hook:after {
            content: '';
            background-color: #d7d6d3;
            width: 47px;
            height: 6px;
            display: block;
            margin: 0px auto;
            position: relative;
            top: 6px;
            border-radius: 4px;
        }

        .id-card-tag-strip {
            display: none;
            width: 45px;
            height: 40px;
            background-color: #0950ef;
            margin: 0 auto;
            border-radius: 5px;
            position: relative;
            top: 9px;
            z-index: 1;
            border: 1px solid #0041ad;
        }

        .id-card-tag-strip:after {
            content: '';
            display: block;
            width: 100%;
            height: 1px;
            background-color: #c1c1c1;
            position: relative;
            top: 10px;
        }

        .id-card-tag {
            width: 0;
            height: 0;
            border-left: 100px solid transparent;
            border-right: 100px solid transparent;
            border-top: 100px solid #0958db;
            margin: -10px auto -30px auto;
        }

        .id-card-tag:after {
            content: '';
            display: block;
            width: 0;
            height: 0;
            border-left: 50px solid transparent;
            border-right: 50px solid transparent;
            border-top: 100px solid #d7d6d3;
            margin: -10px auto -30px auto;
            position: relative;
            top: -130px;
            left: -50px;
        }

        #details td {
            font-size: 11px;
            display: ;
        }


        #id_cards_table tr{
          
        }

        #id_cards_table td {
          width: 200px !important;

        }

        .id-card-holder{
          margin-left: 60px;
        }


    </style>
</head>
<body>

<div class="container-fluid">
  <div class="row">
    <div class="col-sm-6">
      <a href="<?php echo site_url('admin/bulk_student_id');?>" class="btn btn-default btn-icon icon-left hidden-print pull-left" style="margin-left: 20px; margin-top: 20px;">
                       Back
                        <i class="glyphicon glyphicon-circle-arrow-left"></i>
                </a>
      
    </div>
    <div class="col-sm-6">
      <a onClick="window.print()" class="btn btn-default btn-icon icon-left hidden-print pull-right" style="margin-right: 20px; margin-top: 20px;">
                       Print ID Card
                        <i class="glyphicon glyphicon-print"></i>
                </a>
    </div>
  </div>
    
</div>


<div class="row">
  <table cellpadding="0" cellspacing="0" width="100%" id="id_cards_table">
      <tr>

        <?php
          $running_year = $this->db->get_where('settings' , array('type'=>'running_year'))->row()->description;
          $student = $this->db->get_where('student', array('student_id' => $ids_selected[0]))->row();
          $class_id = $this->db->get_where('enroll', array('student_id' => $ids_selected[0],  'year' => $running_year))->row()->class_id;
          $gender  = $this->db->get_where('student', array('student_id' => $ids_selected[0]))->row()->sex;

        ?>
        <!--Display1 with 0 index-->
          <td style="">
              <div class="id-card-tag-strip"></div>
              <div class="row" style="padding-top: 15px; margin-right: 20px;">
                        
                <div class="id-card-holder">
                  <div class="id-card" style="border: 1px solid gray;">
                    <div class="header" style="text-align: center; margin-left: 0px; margin-right: 0px; margin-top: -25px;">
                       <div class="row"><img src="<?php echo base_url(); ?>uploads/school_logo.png"  style="max-height:72px;max-width: 72px"/></div>
                       <div class="row-fluid">
                           <h5 style="display: inline;margin-left: 15px; padding-top: 2px;"><?php echo $this->db->get_where('settings' , array('type'=>'system_name'))->row()->description;?></h5>
                       </div>   
                    </div>

                    <div class="photo">
                        <img class="img-circle" src="<?php echo $this->crud_model->get_image_url('student',$student->student_id, $gender);?>" class="img-circle" width="30" />
                    </div>
                    <div class="bDesign"></div>
                    <div class="cBody">
                    <h2><?php echo 'ID#: '. $student->student_code;?></h2>
                    <hr>
                    <div style="text-align: justify; margin-left: 10px;">
                      <center>
                       <table class="" id="details">
                           <tr>
                               <td><b>Name</b></td>
                               <td><?php echo ucwords(strtolower($student->name)); ?></td>
                           </tr>
                           <tr>
                               <td><b>Parent</b></td>
                               <td><?php echo ucwords(strtolower($this->db->get_where('parent',array('parent_id'=>$student->parent_id))->row()->name)); ?></td>
                           </tr>
                           <tr>
                               <td><b>Class</b></td>
                               <?php
                                    //add section A or B if the class has more than one section
                                    $section_name = $this->db->get_where('section', array('class_id' => $class_id))->row()->name;
                                    $class_has_more_sections = $this->db->get_where('class', array('name' => $this->db->get_where('class',array('class_id'=>$class_id))->row()->name, 'name_numeric' => $this->db->get_where('class',array('class_id'=>$class_id))->row()->name_numeric))->num_rows();
                                    $sec_name = '';
                                    if($class_has_more_sections > 1) {
                                        $sec_name = $section_name;
                                    }

                                  
                                  $class_name = $this->db->get_where('class',array('class_id'=>$class_id))->row()->name;
                                  if($class_name == 'JHSS' || $class_name == 'KG') {
                                    $class_name = strtoupper($class_name);

                                  } else {
                                    $class_name = ucwords(strtolower($class_name));
                                  }
                               
                                ?>
                                 <td><?php echo $class_name.' '.$this->db->get_where('class',array('class_id'=>$class_id))->row()->name_numeric.$sec_name; ?></td>
                           </tr>
                           <!--<tr>
                               <td><b>Blood Group</b> &nbsp;</td>
                               <td><?php echo ($student->blood_group) ? $student->blood_group : 'N/A';?></td>
                           </tr> -->
                           <tr>
                               <td><b>Contact</b></td>
                               <td> <?php echo $this->db->get_where('parent',array('parent_id'=>$student->parent_id))->row()->phone; ?></td>
                           </tr>
                           <tr>
                               <td>
                                   
                               </td>
                           </tr>

                       </table>
                       </center>
                    </div>
                  </div>
              
                    <hr>
                    <img style="-webkit-user-select: none; margin-left: -3px; max-width:200px;background-position: 0px 0px, 10px 10px;background-size: 20px 20px;background-image:linear-gradient(45deg, #eee 25%, transparent 25%, transparent 75%, #eee 75%, #eee 100%),linear-gradient(45deg, #eee 25%, white 25%, white 75%, #eee 75%, #eee 100%);"
                         src="<?php echo site_url('admin/create_barcode/'.$student->student_code); ?>">

                </div>
              </div>
            </div>
        </td><!--//Display1 ends-->

        <?php

          $student = $this->db->get_where('student', array('student_id' => $ids_selected[1]))->row();
          $class_id = $this->db->get_where('enroll', array('student_id' => $ids_selected[1], 'year' => $running_year))->row()->class_id;
          $gender  = $this->db->get_where('student', array('student_id' => $ids_selected[1]))->row()->sex;

        ?>

        <td style="">
              <div class="id-card-tag-strip"></div>
              <div class="row" style="padding-top: 15px; margin-right: 20px;">
                        
                <div class="id-card-holder">
                  <div class="id-card" style="border: 1px solid gray;">
                    <div class="header" style="text-align: center; margin-left: 0px; margin-right: 0px; margin-top: -25px;">
                       <div class="row"><img src="<?php echo base_url(); ?>uploads/school_logo.png"  style="max-height:72px;max-width: 72px"/></div>
                       <div class="row-fluid">
                           <h5 style="display: inline;margin-left: 15px; padding-top: 2px;"><?php echo $this->db->get_where('settings' , array('type'=>'system_name'))->row()->description;?></h5>
                       </div>   
                    </div>

                    <div class="photo">
                        <img class="img-circle" src="<?php echo $this->crud_model->get_image_url('student',$student->student_id, $gender);?>" class="img-circle" width="30" />
                    </div>
                    <div class="bDesign"></div>
                    <div class="cBody">
                    <h2><?php echo 'ID#: '. $student->student_code;?></h2>
                    <hr>
                    <div style="text-align: justify; margin-left: 10px;">
                      <center>
                       <table class="" id="details">
                           <tr>
                               <td><b>Name</b></td>
                               <td><?php echo ucwords(strtolower($student->name)); ?></td>
                           </tr>
                           <tr>
                               <td><b>Parent</b></td>
                               <td><?php echo ucwords(strtolower($this->db->get_where('parent',array('parent_id'=>$student->parent_id))->row()->name)); ?></td>
                           </tr>
                           <tr>
                               <td><b>Class</b></td>
                               <?php
                                    //add section A or B if the class has more than one section
                                    $section_name = $this->db->get_where('section', array('class_id' => $class_id))->row()->name;
                                    $class_has_more_sections = $this->db->get_where('class', array('name' => $this->db->get_where('class',array('class_id'=>$class_id))->row()->name, 'name_numeric' => $this->db->get_where('class',array('class_id'=>$class_id))->row()->name_numeric))->num_rows();
                                    $sec_name = '';
                                    if($class_has_more_sections > 1) {
                                        $sec_name = $section_name;
                                    }

                                  
                                  $class_name = $this->db->get_where('class',array('class_id'=>$class_id))->row()->name;
                                  if($class_name == 'JHSS' || $class_name == 'KG') {
                                    $class_name = strtoupper($class_name);

                                  } else {
                                    $class_name = ucwords(strtolower($class_name));
                                  }
                               
                                ?>
                                 <td><?php echo $class_name.' '.$this->db->get_where('class',array('class_id'=>$class_id))->row()->name_numeric.$sec_name; ?></td>
                           </tr>
                           <!--<tr>
                               <td><b>Blood Group</b> &nbsp;</td>
                               <td><?php echo ($student->blood_group) ? $student->blood_group : 'N/A';?></td>
                           </tr> -->
                           <tr>
                           <tr>
                               <td><b>Contact</b></td>
                               <td> <?php echo $this->db->get_where('parent',array('parent_id'=>$student->parent_id))->row()->phone; ?></td>
                           </tr>
                           <tr>
                               <td>
                                   
                               </td>
                           </tr>

                       </table>
                       </center>
                    </div>
                  </div>

                    <hr>
                    <img style="-webkit-user-select: none; margin-left: -3px; max-width:200px;background-position: 0px 0px, 10px 10px;background-size: 20px 20px;background-image:linear-gradient(45deg, #eee 25%, transparent 25%, transparent 75%, #eee 75%, #eee 100%),linear-gradient(45deg, #eee 25%, white 25%, white 75%, #eee 75%, #eee 100%);"
                         src="<?php echo site_url('admin/create_barcode/'.$student->student_code); ?>">

                </div>
              </div>
            </div>
        </td><!--//Display2 ends-->

        <?php

          $student = $this->db->get_where('student', array('student_id' => $ids_selected[2]))->row();
          $class_id = $this->db->get_where('enroll', array('student_id' => $ids_selected[2], 'year' => $running_year))->row()->class_id;
          $gender  = $this->db->get_where('student', array('student_id' => $ids_selected[2]))->row()->sex;

        ?>

        <td style="">
              <div class="id-card-tag-strip"></div>
              <div class="row" style="padding-top: 15px; margin-right: 20px;">
                        
                <div class="id-card-holder">
                  <div class="id-card" style="border: 1px solid gray;">
                    <div class="header" style="text-align: center; margin-left: 0px; margin-right: 0px; margin-top: -25px;">
                       <div class="row"><img src="<?php echo base_url(); ?>uploads/school_logo.png"  style="max-height:72px;max-width: 72px"/></div>
                       <div class="row-fluid">
                           <h5 style="display: inline;margin-left: 15px; padding-top: 2px;"><?php echo $this->db->get_where('settings' , array('type'=>'system_name'))->row()->description;?></h5>
                       </div>   
                    </div>

                    <div class="photo">
                        <img class="img-circle" src="<?php echo $this->crud_model->get_image_url('student',$student->student_id, $gender);?>" class="img-circle" width="30" />
                    </div>
                    <div class="bDesign"></div>
                    <div class="cBody">
                    <h2><?php echo 'ID#: '. $student->student_code;?></h2>
                    <hr>
                    <div style="text-align: justify; margin-left: 10px;">
                      <center>
                       <table class="" id="details">
                           <tr>
                               <td><b>Name</b></td>
                               <td><?php echo ucwords(strtolower($student->name)); ?></td>
                           </tr>
                           <tr>
                               <td><b>Parent</b></td>
                               <td><?php echo ucwords(strtolower($this->db->get_where('parent',array('parent_id'=>$student->parent_id))->row()->name)); ?></td>
                           </tr>
                           <tr>
                               <td><b>Class</b></td>
                               <?php
                                    //add section A or B if the class has more than one section
                                    $section_name = $this->db->get_where('section', array('class_id' => $class_id))->row()->name;
                                    $class_has_more_sections = $this->db->get_where('class', array('name' => $this->db->get_where('class',array('class_id'=>$class_id))->row()->name, 'name_numeric' => $this->db->get_where('class',array('class_id'=>$class_id))->row()->name_numeric))->num_rows();
                                    $sec_name = '';
                                    if($class_has_more_sections > 1) {
                                        $sec_name = $section_name;
                                    }

                                  
                                  $class_name = $this->db->get_where('class',array('class_id'=>$class_id))->row()->name;
                                  if($class_name == 'JHSS' || $class_name == 'KG') {
                                    $class_name = strtoupper($class_name);

                                  } else {
                                    $class_name = ucwords(strtolower($class_name));
                                  }
                               
                                ?>
                                 <td><?php echo $class_name.' '.$this->db->get_where('class',array('class_id'=>$class_id))->row()->name_numeric.$sec_name; ?></td>
                           </tr>
                           <!--<tr>
                               <td><b>Blood Group</b> &nbsp;</td>
                               <td><?php echo ($student->blood_group) ? $student->blood_group : 'N/A';?></td>
                           </tr> -->
                           <tr>
                           <tr>
                               <td><b>Contact</b></td>
                               <td> <?php echo $this->db->get_where('parent',array('parent_id'=>$student->parent_id))->row()->phone; ?></td>
                           </tr>
                           <tr>
                               <td>
                                   
                               </td>
                           </tr>

                       </table>
                       </center>
                    </div>
                  </div>

                    <hr>
                    <img style="-webkit-user-select: none; margin-left: -3px; max-width:200px;background-position: 0px 0px, 10px 10px;background-size: 20px 20px;background-image:linear-gradient(45deg, #eee 25%, transparent 25%, transparent 75%, #eee 75%, #eee 100%),linear-gradient(45deg, #eee 25%, white 25%, white 75%, #eee 75%, #eee 100%);"
                         src="<?php echo site_url('admin/create_barcode/'.$student->student_code); ?>">

                </div>
              </div>
            </div>
        </td><!--//Display3 ends-->

<!--Row 2 Begins-->
      </tr>
        <?php

          $student = $this->db->get_where('student', array('student_id' => $ids_selected[3]))->row();
          $class_id = $this->db->get_where('enroll', array('student_id' => $ids_selected[3], 'year' => $running_year))->row()->class_id;
          $gender  = $this->db->get_where('student', array('student_id' => $ids_selected[3]))->row()->sex;

        ?>
          <td style="">
              <div class="id-card-tag-strip"></div>
              <div class="row" style="padding-top: 15px; margin-right: 20px;">
                        
                <div class="id-card-holder">
                  <div class="id-card" style="border: 1px solid gray;">
                    <div class="header" style="text-align: center; margin-left: 0px; margin-right: 0px; margin-top: -25px;">
                       <div class="row"><img src="<?php echo base_url(); ?>uploads/school_logo.png"  style="max-height:72px;max-width: 72px"/></div>
                       <div class="row-fluid">
                           <h5 style="display: inline;margin-left: 15px; padding-top: 2px;"><?php echo $this->db->get_where('settings' , array('type'=>'system_name'))->row()->description;?></h5>
                       </div>   
                    </div>

                    <div class="photo">
                        <img class="img-circle" src="<?php echo $this->crud_model->get_image_url('student',$student->student_id, $gender);?>" class="img-circle" width="30" />
                    </div>
                    <div class="bDesign"></div>
                    <div class="cBody">
                    <h2><?php echo 'ID#: '. $student->student_code;?></h2>
                    <hr>
                    <div style="text-align: justify; margin-left: 10px;">
                      <center>
                       <table class="" id="details">
                           <tr>
                               <td><b>Name</b></td>
                               <td><?php echo ucwords(strtolower($student->name)); ?></td>
                           </tr>
                           <tr>
                               <td><b>Parent</b></td>
                               <td><?php echo ucwords(strtolower($this->db->get_where('parent',array('parent_id'=>$student->parent_id))->row()->name)); ?></td>
                           </tr>
                           <tr>
                               <td><b>Class</b></td>
                               <?php
                                    //add section A or B if the class has more than one section
                                    $section_name = $this->db->get_where('section', array('class_id' => $class_id))->row()->name;
                                    $class_has_more_sections = $this->db->get_where('class', array('name' => $this->db->get_where('class',array('class_id'=>$class_id))->row()->name, 'name_numeric' => $this->db->get_where('class',array('class_id'=>$class_id))->row()->name_numeric))->num_rows();
                                    $sec_name = '';
                                    if($class_has_more_sections > 1) {
                                        $sec_name = $section_name;
                                    }

                                  
                                  $class_name = $this->db->get_where('class',array('class_id'=>$class_id))->row()->name;
                                  if($class_name == 'JHSS' || $class_name == 'KG') {
                                    $class_name = strtoupper($class_name);

                                  } else {
                                    $class_name = ucwords(strtolower($class_name));
                                  }
                               
                                ?>
                                 <td><?php echo $class_name.' '.$this->db->get_where('class',array('class_id'=>$class_id))->row()->name_numeric.$sec_name; ?></td>
                           </tr>
                           <!--<tr>
                               <td><b>Blood Group</b> &nbsp;</td>
                               <td><?php echo ($student->blood_group) ? $student->blood_group : 'N/A';?></td>
                           </tr> -->
                           <tr>
                           <tr>
                               <td><b>Contact</b></td>
                               <td> <?php echo $this->db->get_where('parent',array('parent_id'=>$student->parent_id))->row()->phone; ?></td>
                           </tr>
                           <tr>
                               <td>
                                   
                               </td>
                           </tr>

                       </table>
                       </center>
                    </div>
                  </div>

                    <hr>
                    <img style="-webkit-user-select: none; margin-left: -3px; max-width:200px;background-position: 0px 0px, 10px 10px;background-size: 20px 20px;background-image:linear-gradient(45deg, #eee 25%, transparent 25%, transparent 75%, #eee 75%, #eee 100%),linear-gradient(45deg, #eee 25%, white 25%, white 75%, #eee 75%, #eee 100%);"
                         src="<?php echo site_url('admin/create_barcode/'.$student->student_code); ?>">

                </div>
              </div>
            </div>
        </td><!--//Display4 ends-->

        <?php

          $student = $this->db->get_where('student', array('student_id' => $ids_selected[4]))->row();
          $class_id = $this->db->get_where('enroll', array('student_id' => $ids_selected[4], 'year' => $running_year))->row()->class_id;
          $gender  = $this->db->get_where('student', array('student_id' => $ids_selected[4]))->row()->sex;

        ?>

        <td style="">
              <div class="id-card-tag-strip"></div>
              <div class="row" style="padding-top: 15px; margin-right: 20px;">
                        
                <div class="id-card-holder">
                  <div class="id-card" style="border: 1px solid gray;">
                    <div class="header" style="text-align: center; margin-left: 0px; margin-right: 0px; margin-top: -25px;">
                       <div class="row"><img src="<?php echo base_url(); ?>uploads/school_logo.png"  style="max-height:72px;max-width: 72px"/></div>
                       <div class="row-fluid">
                           <h5 style="display: inline;margin-left: 15px; padding-top: 2px;"><?php echo $this->db->get_where('settings' , array('type'=>'system_name'))->row()->description;?></h5>
                       </div>   
                    </div>

                    <div class="photo">
                        <img class="img-circle" src="<?php echo $this->crud_model->get_image_url('student',$student->student_id, $gender);?>" class="img-circle" width="30" />
                    </div>
                    <div class="bDesign"></div>
                    <div class="cBody">
                    <h2><?php echo 'ID#: '. $student->student_code;?></h2>
                    <hr>
                    <div style="text-align: justify; margin-left: 10px;">
                      <center>
                       <table class="" id="details">
                           <tr>
                               <td><b>Name</b></td>
                               <td><?php echo ucwords(strtolower($student->name)); ?></td>
                           </tr>
                           <tr>
                               <td><b>Parent</b></td>
                               <td><?php echo ucwords(strtolower($this->db->get_where('parent',array('parent_id'=>$student->parent_id))->row()->name)); ?></td>
                           </tr>
                           <tr>
                               <td><b>Class</b></td>
                               <?php
                                    //add section A or B if the class has more than one section
                                    $section_name = $this->db->get_where('section', array('class_id' => $class_id))->row()->name;
                                    $class_has_more_sections = $this->db->get_where('class', array('name' => $this->db->get_where('class',array('class_id'=>$class_id))->row()->name, 'name_numeric' => $this->db->get_where('class',array('class_id'=>$class_id))->row()->name_numeric))->num_rows();
                                    $sec_name = '';
                                    if($class_has_more_sections > 1) {
                                        $sec_name = $section_name;
                                    }

                                  
                                  $class_name = $this->db->get_where('class',array('class_id'=>$class_id))->row()->name;
                                  if($class_name == 'JHSS' || $class_name == 'KG') {
                                    $class_name = strtoupper($class_name);

                                  } else {
                                    $class_name = ucwords(strtolower($class_name));
                                  }
                               
                                ?>
                                 <td><?php echo $class_name.' '.$this->db->get_where('class',array('class_id'=>$class_id))->row()->name_numeric.$sec_name; ?></td>
                           </tr>
                           <!--<tr>
                               <td><b>Blood Group</b> &nbsp;</td>
                               <td><?php echo ($student->blood_group) ? $student->blood_group : 'N/A';?></td>
                           </tr> -->
                           <tr>
                           <tr>
                               <td><b>Contact</b></td>
                               <td> <?php echo $this->db->get_where('parent',array('parent_id'=>$student->parent_id))->row()->phone; ?></td>
                           </tr>
                           <tr>
                               <td>
                                   
                               </td>
                           </tr>

                       </table>
                       </center>
                    </div>
                  </div>

                    <hr>
                    <img style="-webkit-user-select: none; margin-left: -3px; max-width:200px;background-position: 0px 0px, 10px 10px;background-size: 20px 20px;background-image:linear-gradient(45deg, #eee 25%, transparent 25%, transparent 75%, #eee 75%, #eee 100%),linear-gradient(45deg, #eee 25%, white 25%, white 75%, #eee 75%, #eee 100%);"
                         src="<?php echo site_url('admin/create_barcode/'.$student->student_code); ?>">

                </div>
              </div>
            </div>
        </td><!--//Display5 ends-->

        <?php

          $student = $this->db->get_where('student', array('student_id' => $ids_selected[5]))->row();
          $class_id = $this->db->get_where('enroll', array('student_id' => $ids_selected[5], 'year' => $running_year))->row()->class_id;
          $gender  = $this->db->get_where('student', array('student_id' => $ids_selected[5]))->row()->sex;

        ?>

        <td style="">
              <div class="id-card-tag-strip"></div>
              <div class="row" style="padding-top: 15px; margin-right: 20px;">
                        
                <div class="id-card-holder">
                  <div class="id-card" style="border: 1px solid gray;">
                    <div class="header" style="text-align: center; margin-left: 0px; margin-right: 0px; margin-top: -25px;">
                       <div class="row"><img src="<?php echo base_url(); ?>uploads/school_logo.png"  style="max-height:72px;max-width: 72px"/></div>
                       <div class="row-fluid">
                           <h5 style="display: inline;margin-left: 15px; padding-top: 2px;"><?php echo $this->db->get_where('settings' , array('type'=>'system_name'))->row()->description;?></h5>
                       </div>   
                    </div>

                    <div class="photo">
                        <img class="img-circle" src="<?php echo $this->crud_model->get_image_url('student',$student->student_id, $gender);?>" class="img-circle" width="30" />
                    </div>
                    <div class="bDesign"></div>
                    <div class="cBody">
                    <h2><?php echo 'ID#: '. $student->student_code;?></h2>
                    <hr>
                    <div style="text-align: justify; margin-left: 10px;">
                      <center>
                       <table class="" id="details" >
                           <tr>
                               <td><b>Name</b></td>
                               <td><?php echo ucwords(strtolower($student->name)); ?></td>
                           </tr>
                           <tr>
                               <td><b>Parent</b></td>
                               <td><?php echo ucwords(strtolower($this->db->get_where('parent',array('parent_id'=>$student->parent_id))->row()->name)); ?></td>
                           </tr>
                           <tr>
                               <td><b>Class</b></td>
                               <?php
                                    //add section A or B if the class has more than one section
                                    $section_name = $this->db->get_where('section', array('class_id' => $class_id))->row()->name;
                                    $class_has_more_sections = $this->db->get_where('class', array('name' => $this->db->get_where('class',array('class_id'=>$class_id))->row()->name, 'name_numeric' => $this->db->get_where('class',array('class_id'=>$class_id))->row()->name_numeric))->num_rows();
                                    $sec_name = '';
                                    if($class_has_more_sections > 1) {
                                        $sec_name = $section_name;
                                    }

                                  
                                  $class_name = $this->db->get_where('class',array('class_id'=>$class_id))->row()->name;
                                  if($class_name == 'JHSS' || $class_name == 'KG') {
                                    $class_name = strtoupper($class_name);

                                  } else {
                                    $class_name = ucwords(strtolower($class_name));
                                  }
                               
                                ?>
                                 <td><?php echo $class_name.' '.$this->db->get_where('class',array('class_id'=>$class_id))->row()->name_numeric.$sec_name; ?></td>
                           </tr>
                           <!--<tr>
                               <td><b>Blood Group</b> &nbsp;</td>
                               <td><?php echo ($student->blood_group) ? $student->blood_group : 'N/A';?></td>
                           </tr> -->
                           <tr>
                           <tr>
                               <td><b>Contact</b></td>
                               <td> <?php echo $this->db->get_where('parent',array('parent_id'=>$student->parent_id))->row()->phone; ?></td>
                           </tr>
                           <tr>
                               <td>
                                   
                               </td>
                           </tr>

                       </table>
                       </center>
                    </div>
                  </div>

                    <hr>
                    <img style="-webkit-user-select: none; margin-left: -3px; max-width:200px;background-position: 0px 0px, 10px 10px;background-size: 20px 20px;background-image:linear-gradient(45deg, #eee 25%, transparent 25%, transparent 75%, #eee 75%, #eee 100%),linear-gradient(45deg, #eee 25%, white 25%, white 75%, #eee 75%, #eee 100%);"
                         src="<?php echo site_url('admin/create_barcode/'.$student->student_code); ?>">

                </div>
              </div>
            </div>
        </td><!--//Display6 ends-->
      <tr>
        
      </tr>
    </table>
  </div>



    <script type="text/javascript" src="<?php echo base_url('assets/js/jquery-3.3.1.min.js'); ?>"></script>
    <script type="text/javascript" src="<?php echo base_url('assets/js/bootstrap.js'); ?>"></script>
</body>
</html>

