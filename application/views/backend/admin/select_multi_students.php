<?php 
$class_name = $this->crud_model->get_class_name($class_id);
$running_year = get_settings('running_year');
$running_term = get_settings('running_term');
$running_sem = get_settings('running_sem');

if($class_name == 'JHSS') {
	$students = $this->db->get_where('enroll' , array('class_id' => $class_id, 'year' => $running_year, 'mute' => '0', 'sem' => $running_sem))->result_array();
} else {
	$students = $this->db->get_where('enroll' , array('class_id' => $class_id, 'mute' => '0', 'year' => $running_year, 'term' => $running_term))->result_array();
}

if($students_ids != '') {
	$students_ids = explode('-', $students_ids);
}
?>

<div class="bg-white rounded-xl shadow-md border border-gray-200 p-8">
	<!-- Header Section -->
	<div id="student_header" class="bg-white rounded-xl shadow-md border border-gray-200 p-6 mb-6 transition-all duration-300">
	<div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
		<div>
			<h2 class="text-3xl font-bold text-gray-900"><?php echo get_phrase('select_students');?></h2>
			<p class="text-lg text-gray-700 mt-2">Total: <span id="student_count" class="font-bold text-blue-600"><?php echo count($students); ?></span> students | Selected: <span id="selected_count" class="font-bold text-green-600">0</span></p>
		</div>
		<div class="flex gap-3">
			<button type="button" id="select_all_btn" class="inline-flex items-center px-5 py-3 bg-green-600 hover:bg-green-700 text-white font-bold rounded-lg transition-colors duration-200 text-base">
				<svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
					<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
				</svg>
				Select All
			</button>
			<button type="button" id="deselect_all_btn" class="inline-flex items-center px-5 py-3 bg-red-600 hover:bg-red-700 text-white font-bold rounded-lg transition-colors duration-200 text-base">
				<svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
					<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
				</svg>
				Deselect All
			</button>
		</div>
	</div>

	<!-- Search Bar -->
	<div class="mb-6">
		<div class="relative">
			<div class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none">
				<svg class="w-6 h-6 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
					<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
				</svg>
			</div>
			<input type="text" id="student_search" class="bg-white border-2 border-gray-300 text-gray-900 text-base rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 block w-full pl-12 p-4" placeholder="Search student by name...">
		</div>
	</div>
	</div>

	<?php if(count($students) > 0): ?>
		<!-- Students Grid -->
		<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 max-h-[500px] overflow-y-auto pr-2">
			<?php 
			foreach($students as $row):
				$student_name = $this->db->get_where('student' , array('student_id' => $row['student_id']))->row()->name;
				$is_checked = false;
				
				if($students_ids != '') {
					for($i = 0; $i < count($students_ids); $i++) {
						if($row['student_id'] == $students_ids[$i]) {
							$is_checked = true;
							break;
						}
					}
				}
			?>
			<div class="student-card bg-white rounded-xl p-5 border-2 border-gray-300 hover:border-blue-500 hover:shadow-lg transition-all duration-200 cursor-pointer" data-student-name="<?php echo htmlspecialchars($student_name); ?>">
				<label class="flex items-center cursor-pointer">
					<input type="checkbox" class="check w-6 h-6 text-blue-600 bg-white border-2 border-gray-400 rounded focus:ring-blue-500 focus:ring-2" name="students_ids[]" value="<?=$row['student_id'] ?>" <?=$is_checked ? 'checked' : '' ?>>
					<div class="ml-4 flex-1">
						<div class="flex items-center gap-3">
							<svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
								<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
							</svg>
							<span class="text-base font-bold text-gray-900"><?=$student_name ?></span>
						</div>
					</div>
				</label>
			</div>
			<?php endforeach; ?>
		</div>
	<?php else: ?>
		<div class="bg-red-50 border-l-4 border-red-500 p-6 rounded-lg">
			<div class="flex items-center">
				<svg class="w-8 h-8 text-red-500 mr-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
					<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
				</svg>
				<p class="text-red-700 font-bold text-lg">No student found in this class!</p>
			</div>
		</div>
	<?php endif; ?>
</div>