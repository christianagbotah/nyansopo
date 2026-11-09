<div class="bg-white min-h-screen p-6">
	<div class="max-w-7xl mx-auto">
		<!-- Header Card -->
		<div id="filter_card" class="bg-white rounded-xl shadow-md border border-gray-200 p-8 mb-6 transition-all duration-300">
			<div class="flex items-center gap-4 mb-8">
				<div class="bg-blue-600 p-4 rounded-xl">
					<svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
						<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
					</svg>
				</div>
				<div>
					<h1 class="text-4xl font-bold text-gray-900"><?php echo get_phrase('manage_attendance');?></h1>
					<p class="text-lg text-gray-600 mt-2">Select class and mark student attendance</p>
				</div>
			</div>

			<?php echo form_open(site_url('admin/attendance_selector/'), array('id' => 'att_selector_form'));?>
			<!-- Filter Section -->
			<div class="grid grid-cols-1 md:grid-cols-4 gap-5">
				<div>
					<label class="block text-base font-bold text-gray-800 mb-3"><?php echo get_phrase('class');?></label>
					<select name="class_id" class="bg-white border-2 border-gray-300 text-gray-900 text-base rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 block w-full p-3.5" onchange="select_section(this.value); select_students(this.value)" id="class_selection">
						<option value=""><?php echo get_phrase('select_class');?></option>
						<?php getFullClassList(); ?>
					</select>
				</div>

				<div id="section_holder">
					<label class="block text-base font-bold text-gray-800 mb-3"><?php echo get_phrase('section');?></label>
					<select class="bg-white border-2 border-gray-300 text-gray-900 text-base rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 block w-full p-3.5" name="section_id">
						<option value=""><?php echo get_phrase('select_class_first') ?></option>
					</select>
				</div>

				<div>
					<label class="block text-base font-bold text-gray-800 mb-3"><?php echo get_phrase('date');?></label>
					<input type="text" class="bg-white border-2 border-gray-300 text-gray-900 text-base rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 block w-full p-3.5 datepicker h-14 max-h-14" name="timestamp"  data-end-date="<?= date('d-m-Y');?>"  data-format="dd-mm-yyyy"
					value="<?php echo date("d-m-Y");?>"/>
				</div>

				<div class="flex items-end">
					<button type="submit" id="submit" class="w-full text-white bg-blue-600 hover:bg-blue-700 focus:ring-4 focus:ring-blue-300 font-bold rounded-lg text-base px-6 py-3.5 transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed" disabled>
						<svg class="w-6 h-6 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
							<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
						</svg>
						<?php echo get_phrase('manage_attendance');?>
					</button>
				</div>
			</div>

			<input type="hidden" name="year" value="<?php echo $running_year;?>">
			<input type="hidden" name="term" value="<?php echo $running_term;?>">
			<input type="hidden" name="sem" value="<?php echo $running_sem;?>">
		</div>

		<!-- Students Section -->
		<div id="students_holder" style="display: none;">
			<div id="sticky_spacer" style="height: 0px;"></div>
		</div>
		<?php echo form_close();?>
	</div>
</div>

<script type="text/javascript">
var class_selection = "";

jQuery(document).ready(function($) {
	$('#submit').attr('disabled', 'disabled');
});

function select_section(class_id) {
	if(class_id !== ''){
		$.ajax({
			url: '<?php echo site_url('admin/get_section/'); ?>' + class_id,
			success:function (response) {
				jQuery('#section_holder').html(response);
			}
		});
	}
}

function select_students(class_id) {
	if(class_id !== ''){
		$('#students_holder').html(`
			<div class="bg-white rounded-2xl shadow-lg p-8">
				<div class="flex justify-center items-center">
					<div class="text-center">
						<svg class="animate-spin h-12 w-12 text-blue-600 mx-auto mb-4" fill="none" viewBox="0 0 24 24">
							<circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
							<path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
						</svg>
						<p class="text-lg font-semibold text-gray-700">Loading students...</p>
					</div>
				</div>
			</div>
		`).show();

		$.ajax({
			url: '<?php echo site_url('admin/get_multi_select_students/'); ?>' + class_id,
			success:function (response) {
				jQuery('#students_holder').html(response).slideDown('slow');
				initSearchFilter();
			}
		});
	} else {
		jQuery('#students_holder').slideUp('slow');
	}
}

function initSearchFilter() {
	const searchInput = document.getElementById('student_search');
	const selectAllBtn = document.getElementById('select_all_btn');
	const deselectAllBtn = document.getElementById('deselect_all_btn');
	const studentHeader = document.getElementById('student_header');
	let headerOffset = 0;
	let headerSticky = false;
	
	// Sticky student header
	const mainHeaderHeight = 60;
	window.addEventListener('scroll', function() {
		if(!studentHeader) return;
		
		if(headerOffset === 0) {
			headerOffset = studentHeader.offsetTop;
		}
		
		const filterCardHeight = document.getElementById('filter_card').offsetHeight;
		const stickyTop = mainHeaderHeight + filterCardHeight + 10;
		
		if(window.pageYOffset > headerOffset - stickyTop) {
			if(!headerSticky) {
				studentHeader.style.position = 'fixed';
				studentHeader.style.top = stickyTop + 'px';
				studentHeader.style.left = '50%';
				studentHeader.style.transform = 'translateX(-50%)';
				studentHeader.style.width = 'calc(100% - 3rem)';
				studentHeader.style.maxWidth = '80rem';
				studentHeader.style.zIndex = '99';
				headerSticky = true;
			}
		} else {
			if(headerSticky) {
				studentHeader.style.position = 'relative';
				studentHeader.style.top = 'auto';
				studentHeader.style.left = 'auto';
				studentHeader.style.transform = 'none';
				studentHeader.style.width = 'auto';
				headerSticky = false;
			}
		}
	});
	
	
	if(searchInput) {
		searchInput.addEventListener('input', function(e) {
			const searchTerm = e.target.value.toLowerCase();
			const studentCards = document.querySelectorAll('.student-card');
			let visibleCount = 0;
			
			studentCards.forEach(card => {
				const studentName = card.dataset.studentName.toLowerCase();
				if(studentName.includes(searchTerm)) {
					card.style.display = '';
					visibleCount++;
				} else {
					card.style.display = 'none';
				}
			});
			
			document.getElementById('student_count').textContent = visibleCount;
		});
	}
	
	if(selectAllBtn) {
		selectAllBtn.addEventListener('click', function() {
			document.querySelectorAll('.student-card:not([style*="display: none"]) .check').forEach(cb => cb.checked = true);
			updateSelectedCount();
		});
	}
	
	if(deselectAllBtn) {
		deselectAllBtn.addEventListener('click', function() {
			document.querySelectorAll('.check').forEach(cb => cb.checked = false);
			updateSelectedCount();
		});
	}
	
	document.querySelectorAll('.check').forEach(checkbox => {
		checkbox.addEventListener('change', updateSelectedCount);
	});
}

function updateSelectedCount() {
	const selectedCount = document.querySelectorAll('.check:checked').length;
	document.getElementById('selected_count').textContent = selectedCount;
}

function check_validation(){
	if(class_selection !== ''){
		$('#submit').removeAttr('disabled');
	} else {
		$('#submit').attr('disabled', 'disabled');
	}
}

$('#class_selection').change(function(){
	class_selection = $('#class_selection').val();
	check_validation();
});

// Sticky header functionality
let filterCard = document.getElementById('filter_card');
let filterCardOffset = 0;
let isSticky = false;
const mainHeaderHeight = 60; // Main page header height

window.addEventListener('scroll', function() {
	if(!filterCard) return;
	
	if(filterCardOffset === 0) {
		filterCardOffset = filterCard.offsetTop;
	}
	
	if(window.pageYOffset > filterCardOffset - mainHeaderHeight) {
		if(!isSticky) {
			filterCard.style.position = 'fixed';
			filterCard.style.top = mainHeaderHeight + 'px';
			filterCard.style.left = '50%';
			filterCard.style.transform = 'translateX(-50%)';
			filterCard.style.width = 'calc(100% - 3rem)';
			filterCard.style.maxWidth = '80rem';
			filterCard.style.zIndex = '100';
			document.getElementById('sticky_spacer').style.height = filterCard.offsetHeight + 'px';
			isSticky = true;
		}
	} else {
		if(isSticky) {
			filterCard.style.position = 'relative';
			filterCard.style.top = 'auto';
			filterCard.style.left = 'auto';
			filterCard.style.transform = 'none';
			filterCard.style.width = 'auto';
			document.getElementById('sticky_spacer').style.height = '0px';
			isSticky = false;
		}
	}
});

$('#att_selector_form').submit(function(event) {
	event.preventDefault();

	let item_checked = $('.check').filter(':checked').length;
	if(item_checked < 1) {
		showAjaxModal_alert('No student was selected!', 'Error');
		return false;
	}

	let student_ids = [];
	$('.check:checked').each(function() {
		student_ids.push($(this).val());
	});

	$('#main_page').html(`
		<div class="flex justify-center items-center h-screen">
			<div class="text-center">
				<svg class="animate-spin h-16 w-16 text-blue-600 mx-auto mb-4" fill="none" viewBox="0 0 24 24">
					<circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
					<path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
				</svg>
				<p class="text-xl font-semibold text-gray-700">Fetching Data...</p>
			</div>
		</div>
	`);

	let formData = new FormData(this);
	student_ids.forEach(id => formData.append('students_ids[]', id));

	$.ajax({
		url: '<?php echo site_url('admin/attendance_selector/'); ?>',
		type: 'POST',
		dataType: 'html',
		data: formData,
		cache: false,
		contentType: false,
		processData: false
	})
	.done(function(data) {
		
		if(data == 'promotion error term') {
			showAjaxModal_confirm('Make sure students were promoted during the previous term. For further assistance, kindly contact the system administrator.', 'Error');
			navigation('<?php echo site_url('admin/manage_attendance'); ?>');
		} else if(data == 'promotion error sem') {
			showAjaxModal_confirm('Make sure students were promoted during the previous semester. For further assistance, kindly contact the system administrator.', 'Error');
			navigation('<?php echo site_url('admin/manage_attendance'); ?>');
		} else {
			$('#main_page').empty();
			navigation(data);
			$('#pre_notice').fadeOut('400', function() {
				$('#pre_notice').remove();
			}); 
		}
	});
});

$(document).ready(function() {
	$('.datepicker').datepicker({
		format: 'dd-mm-yyyy',
		autoclose: true,
		todayHighlight: true,
		orientation: 'bottom',
		endDate: new Date()
	});
});
</script>

