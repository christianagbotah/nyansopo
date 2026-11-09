<!-- Footer -->

<footer style="color: #f2f2f2; background-color: black; position: sticky; bottom: 0px; z-index: 99999999; padding-bottom: 15px; padding-top: 15px; text-align: center; background-position: center-top; margin-left: 280px;">
	&copy; <?php echo date('Y'); ?> <strong> <?php echo $this->db->get_where('settings' , array('type'=>'system_name'))->row()->description; ?>. All rights reserved. | </strong>
    Powered By
	<a href="https://www.lightworldtech.com"
    	target="_blank" style="color: gold;">Lightworldtech</a>
</footer>
