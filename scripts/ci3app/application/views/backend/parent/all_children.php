<div class="container">
	<div class="row">
	<button class="btn btn-default btn-sm float-right" onclick="PrintElem('#children_print')">Print <i class="fa fa-print"></i></button>
</div>
</div>
<div id="children_print">
	<caption><center><h4>MY <?=count($allChildren) > 1 ? ' CHILDREN' : ' CHILD'; ?></h4></center></caption>
	<table class="table table-bordered table-striped table-hover table-active" style="width:100%; border-collapse:collapse;border: 1px solid #ccc; margin-top: 10px;" border="1" >
	  <thead>
	      <tr>
	          <th align="center" width="80"><div align="center"><?php echo get_phrase('iD_no');?></div></th>
	          <th width="80"><div align="center"><?php echo get_phrase('photo');?></div></th>
	          <th align="left"><div><?php echo get_phrase('name');?></div></th>
	          <th align="left"><div><?php echo get_phrase('date_of_birth');?></div></th>
	          <th align="left"><div><?php echo get_phrase('class');?></div></th>
	          <th align="center"><div><?php echo get_phrase('status');?></div></th>
	          
	      </tr>
	  </thead>
	  <tbody>
	<?php
		$sn = 1;
		foreach($allChildren as $row) {
			$array_data = [
				'student_id' => $row['student_id'],
			];
			$enrollment = $this->db->get_where('enroll', $array_data)->last_row();
			$class_id = $enrollment->class_id;
			$section_id = $enrollment->section_id;

			$class_name = $this->crud_model->get_class_name($class_id);
			$class_name_numeric = $this->crud_model->get_class_name_numeric($class_id);
			$mute = $enrollment->mute;
			$status = '';
			$button = '';
			if($mute == 1) {
				if($class_name == 'JHS' && $class_name_numeric == 3) {
					$status = 'COMPLETED';
				} else {
					$status = 'INACTIVE';
				}

				$button = 'btn btn-default btn-sm';
			} else {
				$status = 'ACTIVE';
				$button = 'btn btn-success btn-sm';
			}
			
			?>
			<tr>
	      <td align="center"><?php echo $row['student_code'];?></td>
	      <td align="center"><img src="<?php echo $this->crud_model->get_image_url('student',$row['student_id'], $row['sex']);?>" class="img-circle" width="40" /></td>
	      <td>
	          <?php
	              echo $row['name'];
	          ?>
	      </td>
	      <td>
	          <?php
	              echo $row['birthday'];
	          ?>
	      </td>

	      <td>
	          <?php
	              echo getFullClassName($class_id);
	          ?>
	      </td>

	      <td align="center">
	      	<span class="<?=$button;?>"><?=$status;?></span>
	      </td>

	      
	    </tr>
			<?php
			$sn++;
		}
	?>
		
		</tbody>
	</table>
</div>

<script type="text/javascript">
	
	function PrintElem(elem)
    {
        Popup($(elem).html());
    }

    function Popup(data)
    {
        var mywindow = window.open('', '', '');
        mywindow.document.write('<!doctype html><html><head><title></title>');
        //mywindow.document.write('<link rel="stylesheet" href="assets/css/chartjs/dist/Chart.min.css" type="text\/css" \/>');
        mywindow.document.write('<link rel="stylesheet" type="text/css" href="<?php echo base_url('assets/css/chartjs/dist/Chart.css');?>" />');
        mywindow.document.write('<script src="<?php echo base_url('assets/css/chartjs/dist/Chart.js');?>" type="text\/javascript"><\/script>');
        mywindow.document.write('<script type="text\/javascript" src="<?php echo base_url('assets/js/jquery-3.3.1.min.js'); ?>"><\/script>');

        mywindow.document.write('<\/head><body style="font-size: 13px">');
        mywindow.document.write(data);

        mywindow.document.write('<\/body><\/html>');
        mywindow.document.close();

        mywindow.onload=function(){
            mywindow.focus();
            mywindow.print();
            mywindow.close();
        }
        
    }

    function print_page() {
        //window.location.reload();
        print();
       }

       function page_reload() {
        window.location.reload();
       }


        /*$(function() {
            setTimeout(() => {
              print_page();
            }, 1000);
            
        });*/
</script>