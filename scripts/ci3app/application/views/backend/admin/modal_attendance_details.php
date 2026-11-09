<!-- Attendance Details Modal -->
<div class="modal fade" id="attendanceDetailsModal" tabindex="-1" role="dialog" aria-labelledby="attendanceDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-attendance-wide" role="document">
        <div class="modal-content">
            <!-- Modal Header with Gradient -->
            <div class="modal-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 10px 10px 0 0;">
                <h5 class="modal-title" id="attendanceDetailsModalLabel" style="color: white; font-weight: 600;">
                    <i class="fa fa-users"></i>
                    <span id="modal-title-text">Student Details</span>
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color: white; opacity: 1;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            
            <!-- Modal Body -->
            <div class="modal-body" style="padding: 25px;">
                <!-- Filters Row -->
                <div class="row mb-4">
                    <div class="col-md-4 mb-2">
                        <input type="text" id="modal-search" class="form-control" placeholder="🔍 <?php echo get_phrase('search_students'); ?>..." style="height: 38px; padding-left: 12px;">
                    </div>
                    
                    <div class="col-md-2 mb-2">
                        <select id="modal-class-filter" class="form-control" style="height: 38px;">
                            <option value=""><?php echo get_phrase('all_classes'); ?></option>
                            <?php
                            $classes = $this->db->get('class')->result_array();
                            foreach ($classes as $class):
                            ?>
                                <option value="<?php echo $class['class_id']; ?>">
                                    <?php echo htmlspecialchars($class['name'] . ' ' . $class['name_numeric']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-2 mb-2">
                        <select id="modal-gender-filter" class="form-control" style="height: 38px;">
                            <option value=""><?php echo get_phrase('all_genders'); ?></option>
                            <option value="male"><?php echo get_phrase('male'); ?></option>
                            <option value="female"><?php echo get_phrase('female'); ?></option>
                        </select>
                    </div>
                    
                    <div class="col-md-2 mb-2">
                        <select id="modal-residential-filter" class="form-control" style="height: 38px;">
                            <option value=""><?php echo get_phrase('all_types'); ?></option>
                            <option value="boarding"><?php echo get_phrase('boarding'); ?></option>
                            <option value="day"><?php echo get_phrase('day'); ?></option>
                        </select>
                    </div>
                    
                    <div class="col-md-2 mb-2">
                        <button class="btn btn-success btn-block" id="modal-export-excel" style="height: 38px; line-height: 1.5;">
                            <i class="fa fa-file-excel"></i> <?php echo get_phrase('export'); ?>
                        </button>
                    </div>
                </div>
                
                <!-- Loading Indicator -->
                <div id="modal-loading" class="text-center" style="display: none; padding: 40px;">
                    <i class="fa fa-spinner fa-spin fa-3x" style="color: #667eea;"></i>
                    <p class="mt-3" style="color: #666;"><?php echo get_phrase('loading_data'); ?>...</p>
                </div>
                
                <!-- DataTable -->
                <div id="modal-table-container">
                    <table id="attendance-details-table" class="table table-striped table-bordered dt-responsive nowrap" style="width:100%">
                        <thead style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                            <tr>
                                <th><?php echo get_phrase('student_code'); ?></th>
                                <th><?php echo get_phrase('name'); ?></th>
                                <th><?php echo get_phrase('class'); ?></th>
                                <th><?php echo get_phrase('gender'); ?></th>
                                <th><?php echo get_phrase('residential_status'); ?></th>
                                <th><?php echo get_phrase('status'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Data will be populated via AJAX -->
                        </tbody>
                    </table>
                </div>
                
                <!-- Summary Info -->
                <div class="row mt-3">
                    <div class="col-md-12">
                        <div class="alert alert-info" style="background: #e3f2fd; border: 1px solid #90caf9; border-radius: 8px;">
                            <i class="fa fa-info-circle"></i>
                            <strong><?php echo get_phrase('total_records'); ?>:</strong>
                            <span id="modal-total-count">0</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Modal Footer -->
            <div class="modal-footer" style="background: #f8f9fa; border-radius: 0 0 10px 10px;">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fa fa-times"></i> <?php echo get_phrase('close'); ?>
                </button>
                <button type="button" class="btn btn-primary" id="modal-print-btn">
                    <i class="fa fa-print"></i> <?php echo get_phrase('print'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<style>
/* Modal Enhancements */
#attendanceDetailsModal .modal-attendance-wide {
    max-width: 1400px;
    width: 90%;
}

#attendanceDetailsModal .modal-content {
    border-radius: 10px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.2);
}

#attendanceDetailsModal .modal-header {
    border-bottom: none;
    padding: 20px 25px;
}

#attendanceDetailsModal .modal-header .close {
    font-size: 28px;
    font-weight: 300;
    text-shadow: none;
}

#attendanceDetailsModal .modal-header .close:hover {
    opacity: 0.8;
}

#attendanceDetailsModal .form-control,
#attendanceDetailsModal .input-group-text {
    border-radius: 6px;
}

#attendanceDetailsModal .input-group .input-group-prepend .input-group-text {
    border-top-right-radius: 0;
    border-bottom-right-radius: 0;
}

#attendanceDetailsModal .input-group .form-control {
    border-top-left-radius: 0;
    border-bottom-left-radius: 0;
}

#attendanceDetailsModal .btn {
    border-radius: 6px;
    font-weight: 500;
}

/* DataTable Styling */
#attendance-details-table thead th {
    border: none;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 12px;
    letter-spacing: 0.5px;
}

#attendance-details-table tbody tr:hover {
    background-color: #f5f5f5;
    transition: background-color 0.2s ease;
}

#attendance-details-table tbody td {
    vertical-align: middle;
    padding: 12px 8px;
}

/* Status Badges */
.badge-present {
    background: linear-gradient(135deg, #4caf50 0%, #66bb6a 100%);
    color: white;
    padding: 6px 12px;
    border-radius: 20px;
    font-weight: 500;
}

.badge-absent {
    background: linear-gradient(135deg, #f44336 0%, #ef5350 100%);
    color: white;
    padding: 6px 12px;
    border-radius: 20px;
    font-weight: 500;
}

.badge-not-marked {
    background: linear-gradient(135deg, #ff9800 0%, #ffa726 100%);
    color: white;
    padding: 6px 12px;
    border-radius: 20px;
    font-weight: 500;
}

/* Responsive */
@media (max-width: 768px) {
    #attendanceDetailsModal .modal-attendance-wide {
        max-width: 95%;
        width: 95%;
        margin: 10px auto;
    }
    
    #attendanceDetailsModal .modal-body {
        padding: 15px;
    }
    
    #attendanceDetailsModal .row > div {
        margin-bottom: 10px;
    }
}

@media (min-width: 1600px) {
    #attendanceDetailsModal .modal-attendance-wide {
        max-width: 1600px;
    }
}
</style>
