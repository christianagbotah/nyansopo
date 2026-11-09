
<hr/>
<div class="row">
	<div class="col-md-12">


        <table class="table table-hover table-striped table-active w-full text-xl text-left rtl:text-right text-gray-500 dark:text-gray-600 datatable" id="table_export">
          <thead class="text-lg font-bold text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400 uppercase">
              <tr>
                <th scope="col" class="px-6 py-3">class</th>
                <th scope="col" class="px-6 py-3">subject category</th>
                <th scope="col" class="px-6 py-3">subject name</th>
                <th scope="col" class="px-6 py-3">teacher</th>
              </tr>
          </thead>
            <tbody>
            	<?php $count = 1;
									foreach($subjects as $row):
                        $class = $this->db->get_where('class', array('class_id' => $row['class_id']))->result_array();
                                        foreach ($class as $c):
                                            
                                        
                                        ?>
                        <?php
                    //add section A or B if the class has more than one section
                    $section_name = $this->db->get_where('section', array('class_id' => $c['class_id']))->row()->name;
                    $class_has_more_sections = $this->db->get_where('class', array('name' => $c['name'], 'name_numeric' => $c['name_numeric']))->num_rows();
                    $sec_name = '';
                    if($class_has_more_sections > 1) {
                        $sec_name = $section_name;
                    }
                ?>
                <tr>
					<td><?php echo $this->crud_model->get_type_name_by_id('class',$row['class_id']).' '.$c['name_numeric'].$sec_name;?></td>
					<td><?php echo $this->db->get_where('subject_category_creche', array('category_id' => $row['category_id']))->row()->name;?></td>
					<td><?php echo $row['name'];?></td>
					<td><a href="<?php echo site_url($account_type.'/teacher'); ?>"><?php echo $this->crud_model->get_type_name_by_id('teacher',$row['teacher_id']);?></a></td>
					
                </tr>
                <?php endforeach;
            endforeach?>
            </tbody>
        </table>
    </div>
</div>

                
