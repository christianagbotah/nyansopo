<?php echo form_open(site_url('admin/change_sem') , array('id' => 'sem_change'));?>
<li>
	
	<div class="form-group">
		<select name="running_sem" class="form-control" onchange="submit()">
		  	<?php $running_sem = $this->db->get_where('settings' , array('type'=>'running_sem'))->row()->description;?>
		  	<option value="" disabled="disabled"><?php echo get_phrase('select_running_semester');?></option>
		  	<?php for($i = 1; $i <= 2; $i++):?>
		      	<option value="<?php echo $i;?>"
		        <?php if($running_sem == $i) echo 'selected';?>>
		          	<?php echo $i;?>
		      	</option>
		  <?php endfor;?>
		</select>
	</div>
	
	
</li>
<?php echo form_close();?>



<script type="text/javascript">

    function submit()
    {
    	showAjaxModal_alert('<center><div style="font-size: 20px; font-weight: bolder;">Academic Semester Is Being Changed. Please Wait..<br><i class="fa fa-3x fa-spinner fa-pulse"></i></div></center>', 'Loading');
    	
    	$('#sem_change').submit();
    }
	
</script>