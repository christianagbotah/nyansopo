<style>
.stat-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 20px;
    border-radius: 10px;
    margin-bottom: 20px;
}
.stat-card h3 { margin: 0; font-size: 36px; }
.stat-card p { margin: 5px 0 0 0; opacity: 0.9; }
</style>

<div class="row">
    <div class="col-md-3">
        <div class="stat-card">
            <h3 id="totalProfiles">0</h3>
            <p><i class="fa fa-tags"></i> <?php echo get_phrase('active_profiles'); ?></p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card" style="background: linear-gradient(135deg, #10c469 0%, #0e9d57 100%);">
            <h3 id="totalAssignments">0</h3>
            <p><i class="fa fa-users"></i> <?php echo get_phrase('total_assignments'); ?></p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card" style="background: linear-gradient(135deg, #f9c851 0%, #f77e53 100%);">
            <h3 id="totalStudents">0</h3>
            <p><i class="fa fa-user"></i> <?php echo get_phrase('students_with_discounts'); ?></p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card" style="background: linear-gradient(135deg, #5b69bc 0%, #3b5998 100%);">
            <h3 id="totalRules">0</h3>
            <p><i class="fa fa-cog"></i> <?php echo get_phrase('total_rules'); ?></p>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="panel panel-primary">
            <div class="panel-heading" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <h3 class="panel-title"><i class="fa fa-list"></i> <?php echo get_phrase('student_discount_assignments'); ?></h3>
            </div>
            <div class="panel-body">
                <div class="row mb-3">
                    <div class="col-md-3">
                        <select id="filterYear" class="form-control">
                            <option value=""><?php echo get_phrase('all_years'); ?></option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select id="filterTerm" class="form-control">
                            <option value=""><?php echo get_phrase('all_terms'); ?></option>
                            <option value="1">Term 1</option>
                            <option value="2">Term 2</option>
                            <option value="3">Term 3</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select id="filterProfile" class="form-control">
                            <option value=""><?php echo get_phrase('all_profiles'); ?></option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-primary btn-block" onclick="loadAssignments()">
                            <i class="fa fa-filter"></i> <?php echo get_phrase('filter'); ?>
                        </button>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-12">
                        <a href="<?php echo site_url('admin/discount_profiles'); ?>" class="btn btn-success">
                            <i class="fa fa-tags"></i> <?php echo get_phrase('manage_profiles'); ?>
                        </a>
                        <a href="<?php echo site_url('admin/assign_student_discount'); ?>" class="btn btn-primary">
                            <i class="fa fa-user-plus"></i> <?php echo get_phrase('assign_students'); ?>
                        </a>
                        <button class="btn btn-info" onclick="showBulkAssignModal()">
                            <i class="fa fa-users"></i> <?php echo get_phrase('bulk_assign'); ?>
                        </button>
                    </div>
                </div>

                <table id="assignmentsTable" class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th><?php echo get_phrase('student'); ?></th>
                            <th><?php echo get_phrase('student_code'); ?></th>
                            <th><?php echo get_phrase('profile'); ?></th>
                            <th><?php echo get_phrase('type'); ?></th>
                            <th><?php echo get_phrase('year'); ?></th>
                            <th><?php echo get_phrase('term'); ?></th>
                            <th><?php echo get_phrase('assigned_by'); ?></th>
                            <th><?php echo get_phrase('actions'); ?></th>
                        </tr>
                    </thead>
                    <tbody id="assignmentsBody">
                        <tr>
                            <td colspan="8" class="text-center">
                                <i class="fa fa-spinner fa-spin"></i> <?php echo get_phrase('loading'); ?>...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    loadStats();
    loadFilters();
    loadAssignments();
});

function loadStats() {
    $.get('<?php echo site_url("admin/get_discount_stats"); ?>', function(data) {
        $('#totalProfiles').text(data.profiles || 0);
        $('#totalAssignments').text(data.assignments || 0);
        $('#totalStudents').text(data.students || 0);
        $('#totalRules').text(data.rules || 0);
    }, 'json');
}

function loadFilters() {
    $.get('<?php echo site_url("admin/get_discount_filters"); ?>', function(data) {
        data.years.forEach(function(year) {
            $('#filterYear').append('<option value="'+year+'">'+year+'</option>');
        });
        data.profiles.forEach(function(profile) {
            $('#filterProfile').append('<option value="'+profile.profile_id+'">'+profile.profile_name+'</option>');
        });
        $('#filterYear').val('<?php echo get_settings("running_year"); ?>');
        $('#filterTerm').val('<?php echo get_settings("running_term"); ?>');
    }, 'json');
}

function loadAssignments() {
    var year = $('#filterYear').val();
    var term = $('#filterTerm').val();
    var profile = $('#filterProfile').val();
    
    $('#assignmentsBody').html('<tr><td colspan="8" class="text-center"><i class="fa fa-spinner fa-spin"></i> Loading...</td></tr>');
    
    $.get('<?php echo site_url("admin/get_discount_assignments"); ?>', {
        year: year,
        term: term,
        profile_id: profile
    }, function(data) {
        if(data.length === 0) {
            $('#assignmentsBody').html('<tr><td colspan="8" class="text-center">No assignments found</td></tr>');
            return;
        }
        
        var html = '';
        data.forEach(function(row) {
            html += '<tr>';
            html += '<td>'+row.student_name+'</td>';
            html += '<td>'+row.student_code+'</td>';
            html += '<td><span class="label label-primary">'+row.profile_name+'</span></td>';
            html += '<td><span class="badge badge-info">'+row.discount_type+'</span></td>';
            html += '<td>'+row.year+'</td>';
            html += '<td>'+row.term+'</td>';
            html += '<td>'+row.assigned_by_name+'</td>';
            html += '<td><button class="btn btn-danger btn-xs" onclick="unassign('+row.assignment_id+')"><i class="fa fa-trash"></i></button></td>';
            html += '</tr>';
        });
        $('#assignmentsBody').html(html);
    }, 'json');
}

function unassign(id) {
    showConfirmModal(
        '<?php echo get_phrase("confirm_unassign"); ?>',
        '<?php echo get_phrase("are_you_sure"); ?>',
        function() {
            showAjaxModal_alert('<?php echo get_phrase("removing"); ?>...', 'loading');
            $.post('<?php echo site_url("admin/unassign_discount"); ?>', {assignment_id: id}, function(response) {
                if(response.status === 'success') {
                    showAjaxModal_alert(response.message, 'success');
                    setTimeout(() => loadAssignments(), 2000);
                } else {
                    showAjaxModal_alert(response.message, 'error');
                }
            }, 'json');
        },
        '<?php echo get_phrase("unassign"); ?>',
        'danger'
    );
}

function showBulkAssignModal() {
    loadModalContent('detailsModal', '<?php echo site_url("admin/assign_student_discount_modal"); ?>', '<i class="fa fa-users"></i> <?php echo get_phrase("bulk_assign_discounts"); ?>');
    $('#detailsModal').on('shown.bs.modal', function() {
        if(typeof initModalData === 'function') {
            initModalData();
        }
    });
}

function loadData() {
    loadStats();
    loadAssignments();
}
</script>
