<div class="overflow-x-auto w-full">
    <table class="w-full datatable" id="table-2">
        <thead class="bg-gradient-to-r from-gray-50 to-gray-100 border-b-2 border-gray-200">
            <tr>
                <th class="px-4 py-4 text-center text-xs font-bold text-gray-700 uppercase tracking-wider" style="width: 40px;">
                    <input type="checkbox" id="select-all" class="bulk-checkbox" onchange="toggleSelectAll(this.checked)" title="Select All">
                </th>
                <th class="px-4 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">
                    <div class="flex items-center gap-2">
                        <i class="entypo-calendar text-gray-500"></i>
                        Created
                    </div>
                </th>
                <th class="px-4 py-4 text-center text-xs font-bold text-gray-700 uppercase tracking-wider">
                    <div class="flex items-center justify-center gap-2">
                        <i class="entypo-clock text-gray-500"></i>
                        Period
                    </div>
                </th>
                <th class="px-4 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">
                    <div class="flex items-center gap-2">
                        <i class="entypo-doc-text text-gray-500"></i>
                        Title
                    </div>
                </th>
                <th class="px-4 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">
                    <div class="flex items-center gap-2">
                        <i class="entypo-align-left text-gray-500"></i>
                        Description
                    </div>
                </th>
                <th class="px-4 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">
                    <div class="flex items-center gap-2">
                        <i class="entypo-users text-gray-500"></i>
                        Class
                    </div>
                </th>
                <th class="px-4 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">
                    <div class="flex items-center gap-2">
                        <i class="entypo-book text-gray-500"></i>
                        Subject
                    </div>
                </th>
                <th class="px-4 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">
                    <div class="flex items-center gap-2">
                        <i class="entypo-user text-gray-500"></i>
                        Teacher
                    </div>
                </th>
                <th class="px-4 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">
                    <div class="flex items-center gap-2">
                        <i class="entypo-attach text-gray-500"></i>
                        File
                    </div>
                </th>
                <th class="px-4 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">
                    <div class="flex items-center gap-2">
                        <i class="entypo-info-circled text-gray-500"></i>
                        Status
                    </div>
                </th>
                <th class="px-4 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">
                    <div class="flex items-center gap-2">
                        <i class="entypo-cog text-gray-500"></i>
                        Action
                    </div>
                </th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            <?php
            if(empty($study_material_info)) {
                echo '<tr><td colspan="11" class="px-4 py-12 text-center text-gray-500">
                        <div class="flex flex-col items-center gap-3">
                            <i class="entypo-folder text-6xl text-gray-300"></i>
                            <p class="text-lg font-medium">No study materials found</p>
                            <p class="text-sm">Materials submitted by teachers will appear here</p>
                        </div>
                      </td></tr>';
            }
            
            foreach ($study_material_info as $row) { 
                $statusColour = $row['status'] == 'Pending' ? 'bg-yellow-400' : ($row['status'] == 'Approved' ? 'bg-green-500' : 'bg-red-500');
                $statusBg = $row['status'] == 'Pending' ? 'bg-yellow-50' : ($row['status'] == 'Approved' ? 'bg-green-50' : 'bg-red-50');
                $statusText = $row['status'] == 'Pending' ? 'text-yellow-700' : ($row['status'] == 'Approved' ? 'text-green-700' : 'text-red-700');
            ?>
            <tr class="hover:bg-blue-50 transition-colors">
                <td class="px-4 py-5 text-center">
                    <input type="checkbox" class="bulk-checkbox material-checkbox" data-id="<?php echo $row['document_id']; ?>" onchange="updateBulkActions()">
                </td>
                <td class="px-4 py-5">
                    <div class="flex items-center gap-2">
                        <i class="entypo-calendar text-gray-400 text-lg"></i>
                        <span class="text-base font-medium text-gray-700"><?php echo date("d M, Y", $row['timestamp']); ?></span>
                    </div>
                </td>
                <td class="px-4 py-5">
                    <div class="flex flex-col items-center gap-1">
                        <span class="font-semibold text-gray-700 text-base"><?php echo date("d M, Y", $row['start_date']); ?></span>
                        <span class="text-sm text-gray-400 font-medium">↓</span>
                        <span class="font-semibold text-gray-700 text-base"><?php echo date("d M, Y", $row['end_date']); ?></span>
                    </div>
                </td>
                <td class="px-4 py-5">
                    <div class="font-semibold text-gray-900 text-lg leading-relaxed">
                        <?php echo $row['title']?>
                    </div>
                </td>
                <td class="px-4 py-5">
                    <div class="text-base text-gray-600 leading-relaxed max-w-xs">
                        <?php echo substr($row['description'], 0, 80) . (strlen($row['description']) > 80 ? '...' : ''); ?>
                    </div>
                </td>
                <td class="px-4 py-5">
                    <div class="inline-flex items-center gap-2 bg-indigo-50 text-indigo-700 px-3 py-2 rounded-lg font-medium text-base">
                        <i class="entypo-users"></i>
                        <?php 
                        $class = $this->db->get_where('class', array('class_id' => $row['class_id']))->row();
                        $section = $this->db->get_where('section', array('class_id' => $row['class_id']))->row();
                        $section_name = $section ? ' - ' . $section->name : '';
                        echo $class->name.' '.$class->name_numeric.$section_name;
                        ?>
                    </div>
                </td>
                <td class="px-4 py-5">
                    <div class="inline-flex items-center gap-2 bg-purple-50 text-purple-700 px-3 py-2 rounded-lg font-medium text-base">
                        <i class="entypo-book"></i>
                        <?php 
                        $subject = $this->db->get_where('subject', array('subject_id' => $row['subject_id']))->row();
                        echo $subject ? $subject->name : 'N/A';
                        ?>
                    </div>
                </td>
                <td class="px-4 py-5">
                    <div class="text-base text-gray-700 font-medium">
                        <?php 
                        $teacher = $this->db->get_where('teacher', array('teacher_id' => $row['teacher_id']))->row();
                        echo $teacher ? $teacher->name : 'N/A';
                        ?>
                    </div>
                </td>
                <td class="px-4 py-5">
                    <a href="<?php echo base_url().$row['file_path']; ?>" 
                       class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2.5 rounded-lg font-semibold shadow-sm text-base transition hover:shadow-md">
                        <i class="entypo-download"></i>
                        <span>Download</span>
                    </a>
                </td>
                <td class="px-4 py-5">
                    <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full <?= $statusText;?> <?= $statusBg;?> border border-current border-opacity-20 text-sm font-semibold">
                        <span class="w-2 h-2 rounded-full <?= $statusColour;?> animate-pulse"></span>
                        <?php echo $row['status'];?>
                    </span>
                </td>
                <td class="px-4 py-5">
                    <?php echo form_open('/admin/study_material/update_status', ['class' => 'w-full', 'id' => 'update_status_form']);?>
                    <select class="w-full px-3 py-2.5 bg-gray-50 border border-gray-300 text-gray-900 text-base rounded-lg focus:ring-blue-500 focus:border-blue-500 font-medium cursor-pointer hover:bg-gray-100 transition" 
                            onchange="updateStatus($(this).val(), <?= $row['document_id'];?>)">
                        <option value="">Change Status...</option>
                        <option value="Pending" <?= $row['status'] == 'Pending' ? 'selected' : '';?>>⏳ Pending</option>
                        <option value="Approved" <?= $row['status'] == 'Approved' ? 'selected' : '';?>>✓ Approve</option>
                        <option value="Declined" <?= $row['status'] == 'Declined' ? 'selected' : '';?>>✗ Decline</option>
                    </select>
                    </form>
                </td>
            </tr>
            <?php } ?>
        </tbody>
    </table>
</div>

<script type="text/javascript">
    jQuery(document).ready(function ($) {
        $("#table-2").dataTable({
            "pageLength": 10,
            "lengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
            "order": [[1, "desc"]], // Sort by created date descending (column index 1 now because of checkbox)
            "responsive": true,
            "autoWidth": false,
            "dom": '<"flex flex-col md:flex-row md:justify-between md:items-center gap-4 mb-4"lf>rtip',
            "language": {
                "search": "Search materials:",
                "lengthMenu": "Show _MENU_ materials",
                "info": "Showing _START_ to _END_ of _TOTAL_ materials",
                "infoEmpty": "No materials available",
                "infoFiltered": "(filtered from _MAX_ total materials)",
                "zeroRecords": "No matching materials found",
                "paginate": {
                    "first": "First",
                    "last": "Last",
                    "next": "Next →",
                    "previous": "← Previous"
                }
            },
            "columnDefs": [
                { "orderable": false, "targets": 0 } // Disable sorting on checkbox column
            ]
        });
    });
</script>