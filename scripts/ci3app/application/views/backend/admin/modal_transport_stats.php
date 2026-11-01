<?php
$type = $param1;
$running_year = $this->db->get_where('settings', array('type' => 'running_year'))->row()->description;
$running_term = $this->db->get_where('settings', array('type' => 'running_term'))->row()->description;

if ($type == 'routes') {
    $routes = $this->db->get('transport')->result_array();
    ?>
    <div class="modal-body p-6">
        <h4 class="text-2xl font-semibold text-gray-800 mb-4"><?php echo get_phrase('all_transport_routes'); ?></h4>
        <div class="overflow-x-auto">
            <table class="min-w-full bg-white border border-gray-200 rounded-lg shadow-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-4 text-left text-base font-semibold text-gray-700 uppercase tracking-wider border-b"><?php echo get_phrase('route_name'); ?></th>
                        <th class="px-6 py-4 text-left text-base font-semibold text-gray-700 uppercase tracking-wider border-b"><?php echo get_phrase('vehicle_number'); ?></th>
                        <th class="px-6 py-4 text-left text-base font-semibold text-gray-700 uppercase tracking-wider border-b"><?php echo get_phrase('fare'); ?></th>
                        <th class="px-6 py-4 text-left text-base font-semibold text-gray-700 uppercase tracking-wider border-b"><?php echo get_phrase('students'); ?></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php foreach ($routes as $route): 
                        $students = $this->db->where('enroll.transport_id', $route['transport_id'])
                            ->where('enroll.year', $running_year)
                            ->where('enroll.term', $running_term)
                            ->from('enroll')->count_all_results();
                    ?>
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4 text-lg text-gray-900"><?php echo $route['route_name']; ?></td>
                        <td class="px-6 py-4 text-lg text-gray-900"><?php echo $route['number_of_vehicle']; ?></td>
                        <td class="px-6 py-4 text-lg text-gray-900"><?php echo $route['route_fare']; ?></td>
                        <td class="px-6 py-4 text-lg text-gray-900"><?php echo $students; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php
} elseif ($type == 'students') {
    $students = $this->db->select('student.name, student.student_code, class.name as class_name, class.name_numeric, section.name as section_name, transport.route_name, transport.number_of_vehicle')
        ->join('student', 'enroll.student_id = student.student_id')
        ->join('class', 'enroll.class_id = class.class_id')
        ->join('section', 'enroll.section_id = section.section_id')
        ->join('transport', 'enroll.transport_id = transport.transport_id')
        ->where('enroll.transport_id IS NOT NULL', null, false)
        ->where('enroll.year', $running_year)
        ->where('enroll.term', $running_term)
        ->from('enroll')->get()->result_array();
    ?>
    <div class="modal-body p-6">
        <h4 class="text-2xl font-semibold text-gray-800 mb-4"><?php echo get_phrase('students_using_transport'); ?></h4>
        <div class="overflow-x-auto">
            <table class="min-w-full bg-white border border-gray-200 rounded-lg shadow-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-4 text-left text-base font-semibold text-gray-700 uppercase tracking-wider border-b"><?php echo get_phrase('student_code'); ?></th>
                        <th class="px-6 py-4 text-left text-base font-semibold text-gray-700 uppercase tracking-wider border-b"><?php echo get_phrase('name'); ?></th>
                        <th class="px-6 py-4 text-left text-base font-semibold text-gray-700 uppercase tracking-wider border-b"><?php echo get_phrase('class'); ?></th>
                        <th class="px-6 py-4 text-left text-base font-semibold text-gray-700 uppercase tracking-wider border-b"><?php echo get_phrase('route'); ?></th>
                        <th class="px-6 py-4 text-left text-base font-semibold text-gray-700 uppercase tracking-wider border-b"><?php echo get_phrase('vehicle'); ?></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php foreach ($students as $student): ?>
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4 text-lg text-gray-900"><?php echo $student['student_code']; ?></td>
                        <td class="px-6 py-4 text-lg text-gray-900"><?php echo $student['name']; ?></td>
                        <td class="px-6 py-4 text-lg text-gray-900"><?php echo $student['class_name'] . ' ' . $student['name_numeric'] . ' ' . $student['section_name']; ?></td>
                        <td class="px-6 py-4 text-lg text-gray-900"><?php echo $student['route_name']; ?></td>
                        <td class="px-6 py-4 text-lg text-gray-900"><?php echo $student['number_of_vehicle']; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php
} elseif ($type == 'assigned') {
    $routes = $this->db->query("SELECT DISTINCT transport.transport_id, transport.route_name, transport.number_of_vehicle, transport.route_fare FROM enroll JOIN transport ON enroll.transport_id = transport.transport_id WHERE enroll.transport_id IS NOT NULL AND enroll.year = '$running_year' AND enroll.term = '$running_term'")->result_array();
    ?>
    <div class="modal-body p-6">
        <h4 class="text-2xl font-semibold text-gray-800 mb-4"><?php echo get_phrase('assigned_routes'); ?></h4>
        <div class="overflow-x-auto">
            <table class="min-w-full bg-white border border-gray-200 rounded-lg shadow-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-4 text-left text-base font-semibold text-gray-700 uppercase tracking-wider border-b"><?php echo get_phrase('route_name'); ?></th>
                        <th class="px-6 py-4 text-left text-base font-semibold text-gray-700 uppercase tracking-wider border-b"><?php echo get_phrase('vehicle_number'); ?></th>
                        <th class="px-6 py-4 text-left text-base font-semibold text-gray-700 uppercase tracking-wider border-b"><?php echo get_phrase('fare'); ?></th>
                        <th class="px-6 py-4 text-left text-base font-semibold text-gray-700 uppercase tracking-wider border-b"><?php echo get_phrase('students'); ?></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php foreach ($routes as $route): 
                        $students = $this->db->where('enroll.transport_id', $route['transport_id'])
                            ->where('enroll.year', $running_year)
                            ->where('enroll.term', $running_term)
                            ->from('enroll')->count_all_results();
                    ?>
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4 text-lg text-gray-900"><?php echo $route['route_name']; ?></td>
                        <td class="px-6 py-4 text-lg text-gray-900"><?php echo $route['number_of_vehicle']; ?></td>
                        <td class="px-6 py-4 text-lg text-gray-900"><?php echo $route['route_fare']; ?></td>
                        <td class="px-6 py-4 text-lg text-gray-900"><?php echo $students; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php
} elseif ($type == 'collected') {
    $collections = $this->db->select('student.name, student.student_code, SUM(daily_fee_transactions.transport_amount) as total')
        ->join('student', 'daily_fee_transactions.student_id = student.student_id')
        ->where('daily_fee_transactions.payment_date >=', strtotime($running_year . '-01-01'))
        ->where('daily_fee_transactions.payment_date <=', strtotime($running_year . '-12-31'))
        ->where('daily_fee_transactions.transport_amount >', 0)
        ->group_by('daily_fee_transactions.student_id')
        ->from('daily_fee_transactions')->get()->result_array();
    $currency = $this->db->get_where('settings', array('type' => 'currency'))->row()->description;
    ?>
    <div class="modal-body p-6">
        <h4 class="text-2xl font-semibold text-gray-800 mb-4"><?php echo get_phrase('fare_collected_this_term'); ?></h4>
        <div class="overflow-x-auto">
            <table class="min-w-full bg-white border border-gray-200 rounded-lg shadow-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-4 text-left text-base font-semibold text-gray-700 uppercase tracking-wider border-b"><?php echo get_phrase('student_code'); ?></th>
                        <th class="px-6 py-4 text-left text-base font-semibold text-gray-700 uppercase tracking-wider border-b"><?php echo get_phrase('name'); ?></th>
                        <th class="px-6 py-4 text-left text-base font-semibold text-gray-700 uppercase tracking-wider border-b"><?php echo get_phrase('total_collected'); ?></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php foreach ($collections as $collection): ?>
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4 text-lg text-gray-900"><?php echo $collection['student_code']; ?></td>
                        <td class="px-6 py-4 text-lg text-gray-900"><?php echo $collection['name']; ?></td>
                        <td class="px-6 py-4 text-lg text-gray-900"><?php echo $currency . ' ' . number_format($collection['total'], 2); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php
}
?>
<div class="modal-footer">
    <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo get_phrase('close'); ?></button>
</div>
