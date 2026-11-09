<!-- Bottom Scripts -->
<script src="<?php echo base_url('assets/js/bootstrap-tagsinput.js');?>"></script>
<script src="<?php echo base_url('assets/js/gsap/main-gsap.js');?>"></script>
<!-- Bootstrap MUST load before jQuery-UI to prevent modal() method conflict -->
<script src="<?php echo base_url('assets/js/bootstrap-debug.js');?>"></script>

<!-- Fix Bootstrap modal TypeError: data[option] is not a function -->
<script>
(function($) {
	// Store original modal method
	var originalModal = $.fn.modal;
	
	// Override modal method with error handling
	$.fn.modal = function(option) {
		try {
			// Check if element exists
			if (this.length === 0) {
				console.warn('Modal: No elements found');
				return this;
			}
			
			// Check if option is valid
			if (option && typeof option === 'string') {
				// Valid string options: 'show', 'hide', 'toggle', 'handleUpdate'
				var validOptions = ['show', 'hide', 'toggle', 'handleUpdate'];
				if (validOptions.indexOf(option) === -1) {
					console.warn('Modal: Invalid option "' + option + '"');
					return this;
				}
			}
			
			// Call original modal method
			return originalModal.call(this, option);
		} catch (e) {
			console.error('Modal error:', e.message);
			return this;
		}
	};
	
	// Copy static properties
	$.fn.modal.Constructor = originalModal.Constructor;
	$.fn.modal.noConflict = originalModal.noConflict;
})(jQuery);
</script>
<!-- TEMPORARILY DISABLED JQUERY-UI TO FIX BOOTSTRAP MODAL CONFLICT -->
<!-- <script src="<?php echo base_url('assets/js/jquery-ui/js/jquery-ui-1.10.3.minimal.min.js');?>"></script> -->

<!-- TEMPORARILY DISABLED - requires jQuery-UI widget -->
<!-- <script src="<?php echo base_url('assets/js/joinable.js');?>"></script> -->

<script src="<?php echo base_url('assets/js/resizeable.js');?>"></script>
<!-- jQuery Transit - Required for sidebar animations in neon-api.js -->
<script src="<?php echo base_url('assets/js/jquery.transit.min.js');?>"></script>
<script src="<?php echo base_url('assets/js/neon-api.js');?>"></script>
<script src="<?php echo base_url('assets/js/toastr.js');?>"></script>
<script src="<?php echo base_url('assets/js/jquery.validate.min.js');?>"></script>
<script src="<?php echo base_url('assets/js/fullcalendar/fullcalendar.min.js');?>"></script>
<script src="<?php echo base_url('assets/js/bootstrap-datepicker.js');?>"></script>
<script src="<?php echo base_url('assets/js/bootstrap-timepicker.min.js');?>"></script>
<script src="<?php echo base_url('assets/js/fileinput.js');?>"></script>

<!-- TEMPORARILY DISABLED FLOWBITE TO FIX BOOTSTRAP MODAL CONFLICT -->
<!-- <script src="<?=base_url('node_modules/flowbite/dist/flowbite.min.js')?>"></script>
<script src="<?=base_url('node_modules/flowbite-datepicker/dist/js/datepicker.min.js')?>"></script> -->

<!-- <script type="text/javascript" src="<?php //echo base_url('assets/datatable/dataTables/js/jquery.dataTables.js');?>"></script>
<script type="text/javascript" src="<?php //echo base_url('assets/datatable/dataTables/js/dataTables.bootstrap.js');?>"></script>
<script type="text/javascript" src="<?php //echo base_url('assets/datatable/buttons/js/dataTables.buttons.js');?>"></script>
<script type="text/javascript" src="<?php //echo base_url('assets/datatable/buttons/js/buttons.bootstrap.js');?>"></script> -->


<script src="<?php echo base_url('assets/js/neon-calendar.js');?>"></script>
<script src="<?php echo base_url('assets/js/neon-chat.js');?>"></script>
<script src="<?php echo base_url('assets/js/neon-custom.js');?>"></script>
<script src="<?php echo base_url('assets/js/neon-demo.js');?>"></script>

<script src="<?php echo base_url('assets/js/wysihtml5/wysihtml5-0.4.0pre.min.js');?>"></script>
<script src="<?php echo base_url('assets/js/wysihtml5/bootstrap-wysihtml5.js');?>"></script>

<!--FOR GENERATING CSV FILE-->
<script type="text/javascript" src="<?php echo base_url('assets/sheetjs-master/xlsx.full.min.js')?>"></script>

<?php if (!isset($skip_datatables) || $skip_datatables !== TRUE): ?>
<!--DataTables Plugins - CDN versions only -->
<!-- Removed duplicate: assets/datatables/datatables.min.js -->

<!-- DataTables Export Dependencies (must load BEFORE buttons) -->
<script src="<?php echo base_url('assets/cdn/js/jszip.min.js');?>" crossorigin="anonymous"></script>
<script src="<?php echo base_url('assets/cdn/js/pdfmake.min.js');?>" crossorigin="anonymous"></script>
<script src="<?php echo base_url('assets/cdn/js/vfs_fonts.js');?>" crossorigin="anonymous"></script>

<!-- DataTables Core -->
<script src="<?php echo base_url('assets/cdn/js/dataTables.js');?>" crossorigin="anonymous"></script>

<!-- DataTables Buttons Extension (must load AFTER core) -->
<script src="<?php echo base_url('assets/cdn/js/dataTables.buttons-3.0.2.js');?>" crossorigin="anonymous"></script>
<script src="<?php echo base_url('assets/cdn/js/buttons.dataTables-3.2.4.js');?>" crossorigin="anonymous"></script>

<!-- DataTables Button Types (must load AFTER buttons extension) -->
<script src="<?php echo base_url('assets/cdn/js/buttons.html5.min.js');?>" crossorigin="anonymous"></script>
<script src="<?php echo base_url('assets/cdn/js/buttons.print.min.js');?>?v=<?php echo time(); ?>" crossorigin="anonymous"></script>
<script src="<?php echo base_url('assets/cdn/js/buttons.colVis.min.js');?>" crossorigin="anonymous"></script>
<?php endif; ?>
 

<!-- Select2 v3.5.2 (original version) -->
<script src="<?php echo base_url('assets/js/select2/select2.min.js');?>"></script>

<!-- DataTables Layout Force Fix - JavaScript Solution -->
<script src="<?php echo base_url('assets/js/datatables-layout-force-fix.js');?>?v=<?php echo time(); ?>"></script>
<!-- Select2 Global Initialization Fix (prevents query function errors) -->
<script src="<?php echo base_url('assets/js/select2-global-init.js');?>"></script>

<!-- TEMPORARILY DISABLED - requires jQuery-UI widget -->
<!-- <script src="<?php echo base_url('assets/js/selectboxit/jquery.selectBoxIt.min.js');?>"></script> -->

<!--End of DataTables Plugins -->

<!-- SweetAlert2 Library -->
<script src="<?php echo base_url('assets/cdn/js/sweetalert2.min.js');?>"></script>

<!-- Global Base URL for JavaScript -->
<script>
    // Make base_url available globally to all JavaScript files
    // This ensures compatibility across different school installations
    var base_url = '<?php echo base_url(); ?>';
</script>

<!-- Notification Polling Script -->
<script src="<?php echo base_url('assets/js/notification_polling.js');?>"></script>

<!-- SHOW TOASTR NOTIFICATION -->
<?php if ($this->session->flashdata('flash_message') != ""):?>

<script type="text/javascript">
	$(document).ready(function() {
		toastr.options = {
			"closeButton": true,
			"progressBar": true,
			"positionClass": "toast-top-right",
			"timeOut": "5000",
			"extendedTimeOut": "1000"
		};
		toastr.success('<?php echo $this->session->flashdata("flash_message");?>');
	});
</script>

<?php endif;?>

<?php if ($this->session->flashdata('error_message') != ""):?>

<script type="text/javascript">
	$(document).ready(function() {
		toastr.options = {
			"closeButton": true,
			"progressBar": true,
			"positionClass": "toast-top-right",
			"timeOut": "5000",
			"extendedTimeOut": "1000"
		};
		toastr.error('<?php echo $this->session->flashdata("error_message");?>');
	});
</script>

<?php endif;?>


<!---  DATA TABLE EXPORT CONFIGURATIONS -->
<script type="text/javascript">

	jQuery(document).ready(function($)
	{
		// Only initialize DataTable if the element exists and DataTable is loaded
		if (typeof $.fn.dataTable !== 'undefined' && $("#table_export").length > 0) {
			var datatable = $("#table_export").dataTable();
		}

		//update payment alert for admin
		/**setInterval(function() {
			 mobile_money_payment_alert_show();
		}, 800);

		function mobile_money_payment_alert_rows() {
			$.ajax({
				url: '<?php //echo site_url('admin/mobile_money_payment_alert_rows'); ?>',
				success: function(num_rows) {
					if(num_rows > 0) {
						$('#badge1').text(num_rows);
						$('#badge1').css('display', 'inline-flex');
						$('#bell').css({'color': '#ffffff', 'font-weight': 'bold'});
					}else{
						$('#badge1').css('display', 'none');
						$('#bell').css({'color': '#1325ab'});
					}
				}
			});
		} **/
	});






</script>


<!-- Global script to make HTML5 date inputs clickable anywhere -->
<script>
$(document).on('click', 'input[type="date"]', function() {
    try {
        this.showPicker();
    } catch(e) {
        this.focus();
    }
});
</script>


<!-- Fix for navigation menu dropdowns not opening -->
<script>
$(document).ready(function() {
    // Reinitialize menu click handlers to ensure dropdowns work
    $('#main-menu li.has-sub > a').off('click').on('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        var $li = $(this).parent('li');
        var $submenu = $li.children('ul');
        
        if ($li.hasClass('opened')) {
            // Close submenu
            $li.removeClass('opened');
            $submenu.slideUp(300);
        } else {
            // Close other open submenus at the same level
            $li.siblings('.opened').removeClass('opened').children('ul').slideUp(300);
            // Open this submenu
            $li.addClass('opened');
            $submenu.slideDown(300);
        }
        
        return false;
    });
    
    // Mark menu items with submenus
    $('#main-menu li').each(function() {
        if ($(this).children('ul').length > 0) {
            $(this).addClass('has-sub');
        }
    });
});
</script>
