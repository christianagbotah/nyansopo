<style>

    .id-card-holder {
        width: 225px;
        padding: 4px;
        margin: 0 auto;
        background-color: #1f1f1f;
        border-radius: 5px;
        position: relative;
    }
    .id-card-holder:after {
        content: '';
        width: 7px;
        display: block;
        background-color: #0a0a0a;
        height: 100px;
        position: absolute;
        top: 105px;
        border-radius: 0 5px 5px 0;
    }
    .id-card-holder:before {
        content: '';
        width: 7px;
        display: block;
        background-color: #0a0a0a;
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

    .id-card  h2 {
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
    .id-card  p {
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
</style>

<?php
    $running_year = $this->db->get_where('settings' , array('type'=>'running_year'))->row()->description;
    $id = $param2;
    $student = $this->db->get_where('student',array('student_id'=>$id))->row();
    $class_id = $this->db->get_where('enroll',array('student_id'=>$id, 'year' => $running_year))->row()->class_id;
    $gender  = $this->db->get_where('student', array('student_id' => $id))->row()->sex;

?>

<div class="id-card-tag-strip"></div>
<div class="id-card-hook"></div>
<div class="id-card-holder">
    <div class="id-card">
        <div class="header" style="text-align: center; margin-left: 0px; margin-top: -25px;">
           <div class="row"><img src="<?php echo base_url(); ?>uploads/school_logo.png"  style="max-height:72px;max-width: 72px"/></div>
           <div class="row-fluid">
               <h5 style="display: inline;margin-left: 15px;"><?php echo $this->db->get_where('settings' , array('type'=>'system_name'))->row()->description;?></h5>
           </div>   
        </div>

        <div class="photo">
            <img class="img-circle" src="<?php echo $this->crud_model->get_image_url('student',$student->student_id, $gender);?>" class="img-circle" width="30" />
        </div>
        <div class="bDesign"></div>
        <div class="cBody">
        <h2><?php echo'ID#: '. $student->student_code;?></h2>
        <hr>
        <div style="text-align: justify;margin-left: 7px">
          <center>
           <table class="">
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
                   <td> <?php echo $student->phone; ?></td>
               </tr>

           </table>
           </center>
        </div>
    </div>

        <hr>
            <img style="-webkit-user-select: none;max-width:200px;background-position: 0px 0px, 10px 10px;background-size: 20px 20px;background-image:linear-gradient(45deg, #eee 25%, transparent 25%, transparent 75%, #eee 75%, #eee 100%),linear-gradient(45deg, #eee 25%, white 25%, white 75%, #eee 75%, #eee 100%);" src="<?php echo site_url('admin/create_barcode/'.$student->student_code);?>">



    </div>
</div>

<a  target="_blank" href="<?php echo site_url('admin/print_id/'.$student->student_id);?>" class="btn btn-primary"><i class="entypo-print"></i> Print</a>
