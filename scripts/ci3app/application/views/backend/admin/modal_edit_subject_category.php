<?php 
$edit_data		=	$this->db->get_where('subject_category_creche' , array('category_id' => $param2) )->result_array();
foreach ( $edit_data as $row):
?>

<div class="row">
	<div class="col-md-12">
		<div class="panel panel-success" data-collapsed="0">
        	<div class="panel-heading">
            	<div class="panel-title" >
            		<i class="entypo-plus-circled"></i>
					<?php echo get_phrase('edit_subject_category');?>
            	</div>
            </div>
			<div class="panel-body">
                <?php echo form_open(site_url('admin/subject_category/do_update/'.$row['category_id']) , array('id' => 'edit_category_form', 'class' => 'form-horizontal form-groups-bordered validate'));?>
                <div class="form-group">
                    <label class="col-sm-4 control-label"><?php echo get_phrase('category_name');?></label>
                    <div class="col-sm-5 controls">
                        <input type="text" class="form-control" id="edit_category_name" name="name" value="<?php echo $row['name'];?>" required/>
                        <input type="hidden" name="cat_id" value="<?= $row['category_id']; ?>" id="cat_id">
                    </div>
                    <div class="col-sm-3 ">
                        <a href="javascript:void(0);" class="btn btn-success" onclick="edit_subject_cat()" id="edit_category_btn"><?php echo get_phrase('edit_category');?></a>
                    </div>
                </div>
                
        		</form>
            </div>
        </div>
    </div>
</div>

<?php
endforeach;
?>

<script type="text/javascript">
    //edit category
    function edit_subject_cat() {
        $('#modal_ajax').css('display', 'none');        
         const cat_name = $('#edit_category_name').val();
         const cat_id = $('#cat_id').val();
	    $.ajax({
	       url: '<?php echo site_url('admin/subject_category/do_update/') ?>' + cat_name + '/' + cat_id, 
	        beforeSend: function(){
	           //show the preloader
	           $('#loader_holder_subj').css('display', 'block');
	           $('#preloader2').css({display: 'block', top: '10px'});
	       },
	       success: function(response) {
	          //show success message
	            $('#preloader2').css({display: 'block', top: '10px'});
    			$('#preloader2').html(
    				'<div class="alert alert-success alert-dismissible" role="alert" aria-label="alert"><button class="close" data-dismiss="alert">&times;</button><h3>Category Successfully Updated.</h3></div>'
    			);
    			
    			setTimeout(() => {
	    	     window.location.reload();
	    	    }, 2000); 
	       }
	    });
    }

</script>




