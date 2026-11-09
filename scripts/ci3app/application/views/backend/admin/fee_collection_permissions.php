<?php
$running_year = $this->db->get_where('settings', ['type' => 'running_year'])->row()->description;
$running_term = $this->db->get_where('settings', ['type' => 'running_term'])->row()->description;
$global_permission = $this->db->get_where('settings', ['type' => 'teacher_fee_collection_mode'])->row()->description ?? 'restricted';
?>

<div class="max-w-7xl mx-auto px-4 py-6">
    <div class="bg-white rounded-xl shadow-md border border-gray-200 p-6 mb-6">
        <h2 class="text-2xl font-bold text-gray-800 mb-4">
            <i class="fa fa-shield"></i> Fee Collection Permissions
        </h2>
        <p class="text-gray-600 mb-4">
            Manage which teachers can collect fees during attendance marking.
        </p>
    </div>

    <?php echo form_open('admin/update_fee_collection_mode', array('id' => 'global_permission_form')); ?>
        <div class="bg-white rounded-xl shadow-md border border-gray-200 p-6 mb-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4">
                <i class="fa fa-globe"></i> Global Permission Mode
            </h3>
            
            <div class="space-y-4">
                <label class="flex items-start space-x-3 p-4 border-2 rounded-lg cursor-pointer hover:bg-gray-50 <?php echo $global_permission == 'all_allowed' ? 'border-green-500 bg-green-50' : 'border-gray-300'; ?>">
                    <input type="radio" name="permission_mode" value="all_allowed" class="mt-1 w-5 h-5" <?php echo $global_permission == 'all_allowed' ? 'checked' : ''; ?>>
                    <div>
                        <p class="font-bold text-gray-800">Allow All Teachers</p>
                        <p class="text-sm text-gray-600">All teachers can collect fees when marking attendance in any class</p>
                    </div>
                </label>

                <label class="flex items-start space-x-3 p-4 border-2 rounded-lg cursor-pointer hover:bg-gray-50 <?php echo $global_permission == 'restricted' ? 'border-red-500 bg-red-50' : 'border-gray-300'; ?>">
                    <input type="radio" name="permission_mode" value="restricted" class="mt-1 w-5 h-5" <?php echo $global_permission == 'restricted' ? 'checked' : ''; ?>>
                    <div>
                        <p class="font-bold text-gray-800">Restrict All Teachers</p>
                        <p class="text-sm text-gray-600">No teacher can collect fees. Only admins and accountants can collect fees</p>
                    </div>
                </label>

                <label class="flex items-start space-x-3 p-4 border-2 rounded-lg cursor-pointer hover:bg-gray-50 <?php echo $global_permission == 'selective' ? 'border-blue-500 bg-blue-50' : 'border-gray-300'; ?>">
                    <input type="radio" name="permission_mode" value="selective" class="mt-1 w-5 h-5" <?php echo $global_permission == 'selective' ? 'checked' : ''; ?>>
                    <div>
                        <p class="font-bold text-gray-800">Selective Assignment</p>
                        <p class="text-sm text-gray-600">Only specifically assigned teachers can collect fees in their assigned classes</p>
                    </div>
                </label>
            </div>
        </div>

        <div id="selective_assignments" style="display: <?php echo $global_permission == 'selective' ? 'block' : 'none'; ?>;">
            <div class="bg-white rounded-xl shadow-md border border-gray-200 p-6 mb-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4">
                    <i class="fa fa-users"></i> Assign Teachers to Classes
                </h3>
                
                <button type="button" onclick="showAssignModal()" class="bg-green-600 hover:bg-green-700 text-white font-bold px-6 py-3 rounded-lg mb-4">
                    <i class="fa fa-plus"></i> Add Assignment
                </button>

                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="px-4 py-3 text-left font-bold text-gray-700">Teacher</th>
                                <th class="px-4 py-3 text-left font-bold text-gray-700">Class</th>
                                <th class="px-4 py-3 text-left font-bold text-gray-700">Can Collect</th>
                                <th class="px-4 py-3 text-left font-bold text-gray-700">Assigned Date</th>
                                <th class="px-4 py-3 text-center font-bold text-gray-700">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="assignments_table">
                            <?php
                            $assignments = $this->db->select('fc.*, t.name as teacher_name, c.name as class_name, c.name_numeric')
                                ->from('fee_collection_assignments fc')
                                ->join('teacher t', 't.teacher_id = fc.teacher_id')
                                ->join('class c', 'c.class_id = fc.class_id')
                                ->where('fc.year', $running_year)
                                ->where('fc.term', $running_term)
                                ->order_by('t.name', 'ASC')
                                ->get()->result_array();

                            if (empty($assignments)):
                            ?>
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-gray-500">
                                    <i class="fa fa-info-circle"></i> No assignments yet. Click "Add Assignment" to create one.
                                </td>
                            </tr>
                            <?php else: ?>
                                <?php foreach($assignments as $assignment): ?>
                                <tr class="border-b hover:bg-gray-50">
                                    <td class="px-4 py-3"><?php echo $assignment['teacher_name']; ?></td>
                                    <td class="px-4 py-3"><?php echo $assignment['class_name'] . ' ' . $assignment['name_numeric']; ?></td>
                                    <td class="px-4 py-3">
                                        <div class="space-y-1 text-sm">
                                            <?php if ($assignment['can_collect_feeding']): ?>
                                            <span class="inline-block bg-green-100 text-green-800 px-2 py-1 rounded">Feeding</span>
                                            <?php endif; ?>
                                            <?php if ($assignment['can_collect_classes']): ?>
                                            <span class="inline-block bg-blue-100 text-blue-800 px-2 py-1 rounded">Classes</span>
                                            <?php endif; ?>
                                            <?php if ($assignment['can_collect_transport']): ?>
                                            <span class="inline-block bg-purple-100 text-purple-800 px-2 py-1 rounded">Transport</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-600">
                                        <?php echo date('d M Y', $assignment['created_at']); ?>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <button type="button" onclick="editAssignment(<?php echo $assignment['id']; ?>)" class="text-blue-600 hover:text-blue-800 mx-1">
                                            <i class="fa fa-edit"></i>
                                        </button>
                                        <button type="button" onclick="deleteAssignment(<?php echo $assignment['id']; ?>)" class="text-red-600 hover:text-red-800 mx-1">
                                            <i class="fa fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-md border border-gray-200 p-6">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold px-6 py-3 rounded-lg">
                <i class="fa fa-save"></i> Save Global Setting
            </button>
        </div>
    <?php echo form_close(); ?>
</div>

<script>
$('input[name="permission_mode"]').change(function() {
    if ($(this).val() === 'selective') {
        $('#selective_assignments').slideDown();
    } else {
        $('#selective_assignments').slideUp();
    }
});

$('#global_permission_form').submit(function(e) {
    e.preventDefault();
    showAjaxModal_alert('Saving...', 'loading');
    
    $.ajax({
        url: '<?php echo site_url('admin/update_fee_collection_mode'); ?>',
        type: 'POST',
        data: $(this).serialize()
    }).done(function(response) {
        var data = typeof response === 'string' ? JSON.parse(response) : response;
        if (data.status === 'success') {
            showAjaxModal_alert(data.message, 'success');
            setTimeout(function() {
                location.reload();
            }, 1500);
        } else {
            showAjaxModal_alert(data.message || 'Operation failed', 'error');
        }
    }).fail(function(xhr) {
        showAjaxModal_alert('An error occurred. Please try again.', 'error');
    });
});

function showAssignModal() {
    $('#assignment_id').val('');
    $('#teacher_id').val('');
    $('#class_id').val('');
    $('#can_collect_feeding').prop('checked', false);
    $('#can_collect_classes').prop('checked', false);
    $('#can_collect_transport').prop('checked', false);
    $('#modal_fee_assignment').modal('show');
}

function editAssignment(id) {
    $.ajax({
        url: '<?php echo site_url('admin/get_fee_assignment/'); ?>' + id,
        dataType: 'json'
    }).done(function(data) {
        $('#assignment_id').val(data.id);
        $('#teacher_id').val(data.teacher_id);
        $('#class_id').val(data.class_id);
        $('#can_collect_feeding').prop('checked', data.can_collect_feeding == 1);
        $('#can_collect_classes').prop('checked', data.can_collect_classes == 1);
        $('#can_collect_transport').prop('checked', data.can_collect_transport == 1);
        $('#modal_fee_assignment').modal('show');
    }).fail(function() {
        showAjaxModal_alert('Failed to load assignment', 'error');
    });
}

function deleteAssignment(id) {
    showConfirmModal(
        'Delete Assignment',
        'Are you sure you want to remove this fee collection assignment?',
        function() {
            showAjaxModal_alert('Deleting...', 'loading');
            $.ajax({
                url: '<?php echo site_url('admin/delete_fee_assignment/'); ?>' + id,
                type: 'POST',
                dataType: 'json'
            }).done(function(response) {
                showAjaxModal_alert(response.message, response.status);
                if (response.status === 'success') {
                    setTimeout(() => location.reload(), 2000);
                }
            }).fail(function() {
                showAjaxModal_alert('An error occurred', 'error');
            });
        },
        'Delete',
        'danger'
    );
}

$(document).on('submit', '#assignment_form', function(e) {
    e.preventDefault();
    
    var formData = $(this).serialize();
    
    $('#modal_fee_assignment').modal('hide');
    showAjaxModal_alert('Saving assignment...', 'loading');
    
    $.ajax({
        url: '<?php echo site_url('admin/save_fee_assignment'); ?>',
        type: 'POST',
        data: formData
    }).done(function(response) {
        var data = typeof response === 'string' ? JSON.parse(response) : response;
        if (data.status === 'success') {
            showAjaxModal_alert(data.message, 'success');
            setTimeout(function() {
                location.reload();
            }, 1500);
        } else {
            showAjaxModal_alert(data.message || 'Operation failed', 'error');
        }
    }).fail(function(xhr) {
        var errorMsg = 'Failed to save assignment';
        try {
            var response = JSON.parse(xhr.responseText);
            errorMsg = response.message || errorMsg;
        } catch(e) {
            if (xhr.responseText) {
                errorMsg = xhr.responseText.substring(0, 100);
            }
        }
        showAjaxModal_alert(errorMsg, 'error');
    });
});
</script>
