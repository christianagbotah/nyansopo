<style>
.benefit-container {
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    min-height: 100vh;
    padding: 1rem 0;
}
.modern-card {
    background: #ffffff;
    border-radius: 20px;
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
    padding: 2rem;
    margin-bottom: 2rem;
    transition: all 0.3s ease;
}
.modern-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.15);
}
.form-input-modern {
    padding: 1rem 1.2rem;
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    font-size: 1rem;
    font-weight: 600;
    width: 100%;
    transition: all 0.3s ease;
}
.form-input-modern:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
}
.btn-modern {
    padding: 1rem 2rem;
    border-radius: 12px;
    font-weight: 700;
    font-size: 1rem;
    transition: all 0.3s ease;
    border: none;
    cursor: pointer;
}
.btn-modern-primary {
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
    color: white;
}
.btn-modern-primary:hover {
    background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
    transform: translateY(-2px);
}
.btn-modern-success {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
}
.btn-modern-danger {
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    color: white;
}
.tab-navigation {
    display: flex;
    gap: 1rem;
    margin-bottom: 2rem;
    border-bottom: 2px solid #e5e7eb;
}
.tab-btn {
    padding: 1rem 2rem;
    background: transparent;
    border: none;
    border-bottom: 3px solid transparent;
    color: #6b7280;
    font-size: 1.2rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}
.tab-btn:hover {
    color: #3b82f6;
    background: rgba(59, 130, 246, 0.05);
}
.tab-btn.active {
    color: #3b82f6;
    border-bottom-color: #3b82f6;
}
.tab-content {
    display: none;
}
.tab-content.active {
    display: block;
}
@media (max-width: 768px) {
    .modern-card {
        padding: 1rem;
    }
    .tab-btn {
        font-size: 1rem;
        padding: 0.75rem 1rem;
    }
    .btn-modern {
        padding: 0.875rem 1.5rem;
        width: 100%;
    }
}
</style>

<div class="benefit-container">
    <div class="container-fluid">
        <!-- Page Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="modern-card text-center">
                    <h1 class="display-4 font-bold text-gray-900 mb-3">
                        <i class="fa fa-users text-blue-600 mr-3"></i>
                        Beneficiaries Management
                    </h1>
                    <p class="text-lg text-gray-600">Manage benefit categories and beneficiaries</p>
                </div>
            </div>
        </div>

        <!-- Tab Navigation -->
        <div class="tab-navigation">
            <button class="tab-btn active" data-tab="list-tab">
                <i class="fa fa-list mr-2"></i>
                Beneficiary List
            </button>
            <button class="tab-btn" data-tab="category-tab">
                <i class="fa fa-plus-circle mr-2"></i>
                Benefit Categories
            </button>
            <button class="tab-btn" data-tab="add-tab">
                <i class="fa fa-user-plus mr-2"></i>
                Add Beneficiary
            </button>
        </div>

        <!-- Beneficiary List Tab -->
        <div id="list-tab" class="tab-content active">
            <div class="modern-card">
                <h3 class="text-2xl font-bold mb-4">Beneficiary List</h3>
                <div class="row mb-4">
                    <div class="col-md-3">
                        <label class="block text-lg font-semibold mb-2">Year</label>
                        <select class="form-input-modern" id="filter_year">
                            <?php 
                                $years = range(date('Y'), date('Y') - 5);
                                foreach($years as $year):
                            ?>
                                <option value="<?= $year; ?>" <?= ($year == $running_year) ? 'selected' : ''; ?>><?= $year; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="block text-lg font-semibold mb-2">Term</label>
                        <select class="form-input-modern" id="filter_term">
                            <option value="1" <?= ($running_term == 1) ? 'selected' : ''; ?>>Term 1</option>
                            <option value="2" <?= ($running_term == 2) ? 'selected' : ''; ?>>Term 2</option>
                            <option value="3" <?= ($running_term == 3) ? 'selected' : ''; ?>>Term 3</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="block text-lg font-semibold mb-2">&nbsp;</label>
                        <button class="btn-modern btn-modern-primary" onclick="filter_beneficiaries()">
                            <i class="fa fa-filter"></i> Filter
                        </button>
                    </div>
                </div>
                <div class="table-responsive" id="beneficiary_table_container">
                    <table class="table datatable" id="table_export">
                        <thead style="background: linear-gradient(135deg, #9333ea 0%, #7e22ce 100%); color: white;">
                            <tr>
                                <th>Student ID</th>
                                <th>Student Name</th>
                                <th>Class</th>
                                <th>Benefit Category</th>
                                <th>Options</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($beneficiaries as $row):
                                // Check if using new or old system
                                if(isset($row['categories'])) {
                                    // New system
                                    $class_id = $row['class_id'];
                                    $categories = json_decode($row['categories'], true);
                                } else {
                                    // Old system fallback
                                    $class_id = $this->db->get_where('enroll', array('student_id' => $row['student_id'], 'mute' => '0', 'year'=>$running_year, 'term'=>$running_term))->row()->class_id;
                                    if($class_id == '') {
                                        $class_id = $this->db->get_where('enroll', array('student_id' => $row['student_id'], 'year'=>$running_year, 'sem'=>$running_sem))->row()->class_id;
                                    }
                                    $cateIds = explode(',', $row['benefit_status']);
                                    $categories = [];
                                    foreach($cateIds as $id) {
                                        $cat = $this->db->get_where('benefit_category', array('category_id' => $id))->row();
                                        if($cat) {
                                            $categories[] = array(
                                                'category_id' => $id,
                                                'feeding_amount' => $cat->feeding_charge,
                                                'classes_amount' => $cat->classes_charge
                                            );
                                        }
                                    }
                                }
                                
                                $class_name = $this->db->get_where('class', array('class_id'=>$class_id))->row()->name;
                                $class_name_numeric = $this->db->get_where('class', array('class_id'=>$class_id))->row()->name_numeric;
                                $section_name = $this->db->get_where('section', array('class_id' => $class_id))->row()->name;
                                $class_has_more_sections = $this->db->get_where('class', array('name' => $class_name, 'name_numeric' => $class_name_numeric))->num_rows();
                                $sec_name = ($class_has_more_sections > 1) ? $section_name : '';
                            ?>
                            <tr>
                                <td><?php echo $row['student_code'];?></td>
                                <td><?php echo $row['name'];?></td>
                                <td><?php echo $this->crud_model->get_type_name_by_id('class',$class_id).' '.$class_name_numeric.$sec_name;?></td>
                                <td><?php 
                                    $nameHolder = [];
                                    foreach($categories as $cat) {
                                        $category_data = $this->db->get_where('benefit_category', array('category_id' => $cat['category_id']))->row();
                                        $catName = $category_data->name;
                                        $cat_details = json_decode($category_data->details, true);
                                        $discount_type = isset($cat_details['discount_type']) ? $cat_details['discount_type'] : 'percentage';
                                        $symbol = $discount_type == 'percentage' ? '%' : 'GH₵';
                                        
                                        $feeding = isset($cat['feeding_amount']) ? 'Feeding: '.($discount_type == 'fixed' ? $symbol : '').number_format($cat['feeding_amount'], 2).($discount_type == 'percentage' ? $symbol : '') : '';
                                        $classes = isset($cat['classes_amount']) ? 'Classes: '.($discount_type == 'fixed' ? $symbol : '').number_format($cat['classes_amount'], 2).($discount_type == 'percentage' ? $symbol : '') : '';
                                        $tuition = isset($cat['tuition_amount']) ? 'Tuition: '.($discount_type == 'fixed' ? $symbol : '').number_format($cat['tuition_amount'], 2).($discount_type == 'percentage' ? $symbol : '') : '';
                                        $details = array_filter([$feeding, $classes, $tuition]);
                                        $nameHolder[] = $catName . ' (' . implode(', ', $details) . ')';
                                    }
                                    echo implode('<br>', $nameHolder);
                                ?></td>
                                <td>
                                    <button class="btn btn-sm btn-danger" onclick="remove_beneficiary('<?php echo $row['student_id'];?>');" style="border-radius: 8px;">
                                        <i class="fa fa-trash"></i> Remove
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Benefit Categories Tab -->
        <div id="category-tab" class="tab-content">
            <div class="modern-card">
                <h3 class="text-2xl font-bold mb-4">Add Benefit Category</h3>
                <?php echo form_open(site_url('admin/benefit_category/create'), array('id' => 'cat_form'));?>
                <div class="row">
                    <div class="col-md-9">
                        <div class="row mb-3">
                            <div class="col-md-8">
                                <label class="block text-lg font-semibold mb-2">Category Name</label>
                                <input type="text" class="form-input-modern" id="cat_name" name="name" required>
                            </div>
                            <div class="col-md-4">
                                <label class="block text-lg font-semibold mb-2">Discount Type</label>
                                <select class="form-input-modern" id="discount_type" name="discount_type">
                                    <option value="percentage">Percentage (%)</option>
                                    <option value="fixed">Fixed Amount</option>
                                </select>
                            </div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-md-3">
                                <label class="block text-lg font-semibold mb-2">Class Details</label>
                            </div>
                            <div class="col-md-3">
                                <input type="checkbox" id="feeding_master" onchange="toggle_column_fees('feeding')">
                                <label for="feeding_master" style="margin-left: 5px; font-weight: 600;">Feeding Fee</label>
                            </div>
                            <div class="col-md-2">
                                <input type="checkbox" id="classes_master" onchange="toggle_column_fees('classes')">
                                <label for="classes_master" style="margin-left: 5px; font-weight: 600;">Classes Fee</label>
                            </div>
                            <div class="col-md-2">
                                <input type="checkbox" id="tuition_master" onchange="toggle_column_fees('tuition')">
                                <label for="tuition_master" style="margin-left: 5px; font-weight: 600;">Tuition Fee</label>
                            </div>
                            <div class="col-md-2"></div>
                        </div>
                        <div id="class_rows_container">
                            <div class="row mb-2 class-row" data-row="0">
                                <div class="col-md-3">
                                    <select class="form-input-modern class-select" name="class_id[]" onchange="check_add_button()" required>
                                        <option value="">Select Class</option>
                                        <option value="0">All Classes</option>
                                        <?php getFullClassList(); ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <input type="number" step="0.01" class="form-input-modern feeding-input" name="feeding_charge[]" placeholder="0.00" disabled>
                                </div>
                                <div class="col-md-2">
                                    <input type="number" step="0.01" class="form-input-modern classes-input" name="classes_charge[]" placeholder="0.00" disabled>
                                </div>
                                <div class="col-md-2">
                                    <input type="number" step="0.01" class="form-input-modern tuition-input" name="tuition_charge[]" placeholder="0.00" disabled>
                                </div>
                                <div class="col-md-2">
                                    <button type="button" class="btn btn-sm btn-danger w-100" onclick="remove_class_row(0)" style="border-radius: 8px;">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="row mb-3">
                            <div class="col-md-12 text-right">
                                <button type="button" id="add_class_btn" class="btn btn-lg btn-success" onclick="add_class_row()" style="border-radius: 12px; padding: 1rem 1.5rem; font-size: 1.1rem; width: 200px;" disabled>
                                    <i class="fa fa-plus"></i> Add Class
                                </button>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12 text-right">
                                <button type="submit" class="btn btn-lg btn-primary" style="border-radius: 12px; padding: 1rem 1.5rem; font-size: 1.1rem; background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); border: none; width: 200px;">
                                    <i class="fa fa-save"></i> Save Category
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                </form>
            </div>

            <div class="modern-card">
                <h3 class="text-2xl font-bold mb-4">Existing Categories</h3>
                <div class="table-responsive">
                    <table class="table" id="category_tbl">
                        <thead style="background: linear-gradient(135deg, #9333ea 0%, #7e22ce 100%); color: white;">
                            <tr>
                                <th style="width: 5%;">S/N</th>
                                <th style="width: 15%;">Category Name</th>
                                <th style="width: 60%; min-width: 600px;">Class Details</th>
                                <th style="width: 20%;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                                $all_cat = $this->db->get('benefit_category')->result_array();
                                $sn = 1;
                                foreach($all_cat as $row):
                                    $details = isset($row['details']) && !empty($row['details']) ? json_decode($row['details'], true) : [];
                            ?>
                            <tr>
                                <td><?= $sn; ?></td>
                                <td>
                                    <div class="raw_<?= $row['category_id']; ?>"><?= $row['name']; ?></div>
                                    <div class="edit_<?= $row['category_id']; ?>" style="display: none;">
                                        <input type="text" class="form-input-modern" id="edit_category_name_<?= $row['category_id']; ?>" value="<?= $row['name']; ?>">
                                    </div>
                                </td>
                                <td>
                                    <div class="raw_<?= $row['category_id']; ?>">
                                        <?php if(!empty($details)): ?>
                                            <style>
                                                .modern-inner-table {
                                                    border-radius: 12px;
                                                    overflow: hidden;
                                                    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
                                                    background: #fff;
                                                }
                                                .modern-inner-table thead {
                                                    background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
                                                }
                                                .modern-inner-table thead th {
                                                    color: white;
                                                    font-weight: 600;
                                                    padding: 12px 16px;
                                                    border: none;
                                                    font-size: 0.9rem;
                                                }
                                                .modern-inner-table tbody tr {
                                                    transition: all 0.3s ease;
                                                    border-bottom: 1px solid #e5e7eb;
                                                }
                                                .modern-inner-table tbody tr:hover {
                                                    background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
                                                    transform: translateX(4px);
                                                    box-shadow: 0 2px 12px rgba(99, 102, 241, 0.15);
                                                }
                                                .modern-inner-table tbody td {
                                                    padding: 12px 16px;
                                                    border: none;
                                                    font-weight: 500;
                                                    color: #374151;
                                                }
                                                .modern-inner-table tbody td:first-child {
                                                    color: #1f2937;
                                                    font-weight: 600;
                                                }
                                                .fee-amount {
                                                    color: #059669;
                                                    font-weight: 700;
                                                }
                                                .fee-empty {
                                                    color: #9ca3af;
                                                    font-style: italic;
                                                }
                                            </style>
                                            <table class="table table-sm modern-inner-table">
                                                <thead>
                                                    <tr>
                                                        <th>Class</th>
                                                        <th>Feeding</th>
                                                        <th>Classes</th>
                                                        <th>Tuition</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php 
                                                    if(isset($details['classes'])):
                                                        $discount_type = isset($details['discount_type']) ? $details['discount_type'] : 'percentage';
                                                        $symbol = $discount_type == 'percentage' ? '%' : 'GH₵';
                                                        foreach($details['classes'] as $class_id => $charges): 
                                                            if($class_id === 'a' || $class_id == 0 || $class_id === '0') {
                                                                $class_name = 'All Classes';
                                                            } else {
                                                                $cls = $this->db->get_where('class', array('class_id' => $class_id))->row();
                                                                $class_name = $cls ? $cls->name.' '.$cls->name_numeric : 'Class '.$class_id;
                                                            }
                                                    ?>
                                                    <tr>
                                                        <td><?= $class_name; ?></td>
                                                        <td><?= isset($charges['feeding_charged']) ? '<span class="fee-amount">'.($discount_type == 'fixed' ? $symbol : '').number_format($charges['feeding_charged'], 2).($discount_type == 'percentage' ? $symbol : '').'</span>' : '<span class="fee-empty">-</span>'; ?></td>
                                                        <td><?= isset($charges['classes_charged']) ? '<span class="fee-amount">'.($discount_type == 'fixed' ? $symbol : '').number_format($charges['classes_charged'], 2).($discount_type == 'percentage' ? $symbol : '').'</span>' : '<span class="fee-empty">-</span>'; ?></td>
                                                        <td><?= isset($charges['tuition_charged']) ? '<span class="fee-amount">'.($discount_type == 'fixed' ? $symbol : '').number_format($charges['tuition_charged'], 2).($discount_type == 'percentage' ? $symbol : '').'</span>' : '<span class="fee-empty">-</span>'; ?></td>
                                                    </tr>
                                                    <?php 
                                                        endforeach;
                                                    endif;
                                                    ?>
                                                </tbody>
                                            </table>
                                        <?php else: ?>
                                            <em>No details available</em>
                                        <?php endif; ?>
                                    </div>
                                    <div class="edit_<?= $row['category_id']; ?>" style="display: none;" id="edit_details_<?= $row['category_id']; ?>">
                                        <!-- Edit form will be dynamically loaded here -->
                                    </div>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-success raw_<?= $row['category_id']; ?>" onclick="show_cat_edit('<?= $row['category_id']; ?>')" style="border-radius: 8px;">
                                        <i class="fa fa-edit"></i> Edit
                                    </button>
                                    <button class="btn btn-sm btn-primary edit_<?= $row['category_id']; ?>" onclick="update_category('<?= $row['category_id']; ?>')" style="display: none; border-radius: 8px;">
                                        <i class="fa fa-save"></i> Update
                                    </button>
                                    <button class="btn btn-sm btn-secondary edit_<?= $row['category_id']; ?>" onclick="cancel_cat_edit('<?= $row['category_id']; ?>')" style="display: none; border-radius: 8px;">
                                        <i class="fa fa-times"></i> Cancel
                                    </button>
                                    <button class="btn btn-sm btn-danger raw_<?= $row['category_id']; ?>" onclick="delete_cat('<?= $row['category_id']; ?>')" style="border-radius: 8px;">
                                        <i class="fa fa-trash"></i> Delete
                                    </button>
                                </td>
                            </tr>
                            <?php 
                                $sn++;
                                endforeach; 
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Add Beneficiary Tab -->
        <div id="add-tab" class="tab-content">
            <div class="modern-card">
                <h3 class="text-2xl font-bold mb-4">Add Beneficiary</h3>
                <?php echo form_open(site_url('admin/student/create_beneficiary'), array('id' => 'add_ben'));?>
                <div class="row">
                    <div class="col-md-4">
                        <label class="block text-lg font-semibold mb-2">Class</label>
                        <select name="class_id" class="form-input-modern select2" id="ben_class_id" onchange="load_students_and_categories(this.value)" required>
                            <option value="">Select Class</option>
                            <?php getFullClassList(); ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="block text-lg font-semibold mb-2">Students</label>
                        <select name="st_name[]" class="form-input-modern select2" id="st_name" multiple required>
                            <option value="">Select Class First</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="block text-lg font-semibold mb-2">Benefit Category</label>
                        <select name="category_id[]" class="form-input-modern select2" id="ben_category_id" multiple required disabled>
                            <option value="">Select Class First</option>
                        </select>
                    </div>
                </div>
                <div class="row mt-4">
                    <div class="col-md-12">
                        <button type="submit" class="btn-modern btn-modern-primary">
                            <i class="fa fa-user-plus"></i> Add Beneficiary
                        </button>
                    </div>
                </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
$(function() {
    $('.tab-btn').click(function() {
        const tabId = $(this).data('tab');
        $('.tab-btn').removeClass('active');
        $(this).addClass('active');
        $('.tab-content').removeClass('active');
        $('#' + tabId).addClass('active');
    });

    $('#table_export').DataTable();
    $('#category_tbl').DataTable();

    $.ajaxSetup({
        data: {
            '<?php echo $this->security->get_csrf_token_name(); ?>': '<?php echo $this->security->get_csrf_hash(); ?>'
        }
    });
    
    // Update categories when students selection changes
    $('#st_name').on('change', function() {
        var class_id = $('#ben_class_id').val();
        var student_ids = $(this).val();
        
        if(!class_id) return;
        
        $('#ben_category_id').html('<option value="">Loading...</option>').prop('disabled', true).val(null).trigger('change');
        
        $.ajax({
            url: '<?php echo site_url('admin/get_benefit_categories_for_students'); ?>',
            type: 'POST',
            data: {
                class_id: class_id,
                student_ids: student_ids || []
            },
            success: function(response) {
                $('#ben_category_id').html(response).prop('disabled', false).val(null).trigger('change');
            }
        });
    });
});

function filter_beneficiaries() {
    const year = $('#filter_year').val();
    const term = $('#filter_term').val();
    window.location.href = '<?php echo site_url('admin/beneficiary'); ?>?year=' + year + '&term=' + term;
}

function remove_beneficiary(student_id) {
    showConfirmModal(
        'Remove Beneficiary',
        'Are you sure you want to remove this student from beneficiaries? This action cannot be undone.',
        function() {
            $.ajax({
                url: '<?php echo site_url('admin/student/remove_beneficiary/'); ?>' + student_id,
                type: 'POST',
                dataType: 'json',
                success: function(response) {
                    if(response.message == 'done') {
                        showNotify('Beneficiary removed successfully!', 'Success', 'success');
                        setTimeout(() => {
                            location.reload();
                        }, 1500);
                    } else {
                        showNotify('Failed to remove beneficiary', 'Error', 'error');
                    }
                },
                error: function() {
                    showNotify('An error occurred. Please try again.', 'Error', 'error');
                }
            });
        }
    );
}

function showConfirmModal(title, message, onConfirm) {
    const modalHtml = `
        <div id="confirmModal" style="position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:9999;display:flex;align-items:center;justify-content:center;">
            <div style="background:#fff;border-radius:16px;padding:2rem;max-width:400px;width:90%;box-shadow:0 20px 60px rgba(0,0,0,0.3);">
                <div style="display:flex;align-items:center;gap:1rem;margin-bottom:1rem;">
                    <div style="width:48px;height:48px;border-radius:50%;background:linear-gradient(135deg,#ef4444,#dc2626);display:flex;align-items:center;justify-content:center;">
                        <span style="color:white;font-size:24px;font-weight:bold;">!</span>
                    </div>
                    <h3 style="margin:0;font-size:1.5rem;font-weight:700;color:#1f2937;">${title}</h3>
                </div>
                <p style="color:#6b7280;font-size:1rem;margin-bottom:1.5rem;line-height:1.5;">${message}</p>
                <div style="display:flex;gap:0.75rem;justify-content:flex-end;">
                    <button onclick="closeConfirmModal()" style="padding:0.75rem 1.5rem;border:2px solid #e5e7eb;background:#fff;color:#374151;border-radius:8px;font-weight:600;cursor:pointer;transition:all 0.2s;">
                        Cancel
                    </button>
                    <button onclick="confirmAction()" style="padding:0.75rem 1.5rem;border:none;background:linear-gradient(135deg,#ef4444,#dc2626);color:white;border-radius:8px;font-weight:600;cursor:pointer;transition:all 0.2s;">
                        Remove
                    </button>
                </div>
            </div>
        </div>
    `;
    
    $('body').append(modalHtml);
    
    window.confirmAction = function() {
        closeConfirmModal();
        onConfirm();
    };
}

function closeConfirmModal() {
    $('#confirmModal').remove();
    delete window.confirmAction;
}

function load_students_and_categories(class_id) {
    $('#st_name').html('<option value="">Loading...</option>').val(null).trigger('change');
    $('#ben_category_id').html('<option value="">Select students first</option>').prop('disabled', true).val(null).trigger('change');
    
    $.ajax({
        url: '<?php echo site_url('admin/select_student/'); ?>' + class_id,
        success: function(response) {
            $('#st_name').html(response).val(null).trigger('change');
        }
    });
}

function delete_cat(id) {
    showConfirmModal(
        'Delete Benefit Category',
        'If you delete this category, all beneficiaries under this category will be affected. Are you sure you want to proceed?',
        function() {
            $.ajax({
                url: '<?php echo site_url('admin/benefit_category/delete/') ?>' + id,
                success: function(response) {
                    showAjaxModal_alert('Category Successfully Deleted!', 'Success');
                    setTimeout(() => {
                        navigation('<?php echo site_url('admin/beneficiary'); ?>');
                    }, 2000);
                },
                error: function(err) {
                    showAjaxModal_alert(err.responseText, 'Error');
                }
            });
        }
    );
}

function show_cat_edit(id) {
    $.ajax({
        url: '<?php echo site_url('admin/get_category_edit_form/') ?>' + id,
        success: function(response) {
            $('#edit_details_' + id).html(response);
            $('.raw_' + id).hide();
            $('.edit_' + id).show();
        },
        error: function() {
            showAjaxModal_alert('Error loading edit form', 'Error');
        }
    });
}

function cancel_cat_edit(id) {
    $('.edit_' + id).hide();
    $('.raw_' + id).show();
}

function update_category(id) {
    showAjaxModal_alert('Please wait...', 'Loading');
    
    const cat_name = $('#edit_category_name_' + id).val();
    const discount_type = $('#edit_discount_type_' + id).val();
    const class_ids = [];
    const feeding_charges = [];
    const classes_charges = [];
    const tuition_charges = [];
    
    $('#edit_details_' + id + ' .edit-class-row').each(function() {
        const class_id = $(this).find('.edit-class-select').val();
        const feeding_input = $(this).find('.edit-feeding-input');
        const classes_input = $(this).find('.edit-classes-input');
        const tuition_input = $(this).find('.edit-tuition-input');
        
        class_ids.push(class_id);
        feeding_charges.push(feeding_input.prop('disabled') ? null : (feeding_input.val() || '0.00'));
        classes_charges.push(classes_input.prop('disabled') ? null : (classes_input.val() || '0.00'));
        tuition_charges.push(tuition_input.prop('disabled') ? null : (tuition_input.val() || '0.00'));
    });

    $.ajax({
        url: '<?php echo site_url('admin/benefit_category/do_update/') ?>' + id,
        type: 'post',
        data: {
            cat_name: cat_name,
            discount_type: discount_type,
            class_ids: class_ids,
            feeding_charges: feeding_charges,
            classes_charges: classes_charges,
            tuition_charges: tuition_charges
        },
        success: function(response) {
            if(response == 'done') {
                showAjaxModal_alert('Category Successfully Updated! All beneficiaries for current term have been updated.', 'Success');
                setTimeout(() => {
                    navigation('<?php echo site_url('admin/beneficiary'); ?>');
                }, 2000);
            } else {
                showAjaxModal_alert('Error please try again', 'Error');
            }
        },
        error: function(err) {
            showAjaxModal_alert(err.responseText, 'Error');
        }
    });
}

let row_counter = 1;

function toggle_column_fees(type) {
    const checkbox = $(`#${type}_master`);
    const inputs = $(`.${type}-input`);
    
    if(checkbox.is(':checked')) {
        inputs.prop('disabled', false);
    } else {
        inputs.prop('disabled', true).val('0.00');
    }
}

function check_add_button() {
    const all_selects = $('.class-select').map(function() { return $(this).val(); }).get();
    const has_empty = all_selects.some(v => v === '');
    $('#add_class_btn').prop('disabled', has_empty);
}

function get_available_classes() {
    const selected = $('.class-select').map(function() { return $(this).val(); }).get().filter(v => v !== '');
    const all_options = $('select[name="class_id[]"]').first().find('option');
    let options_html = '<option value="">Select Class</option>';
    
    all_options.each(function() {
        const val = $(this).val();
        if(val !== '' && !selected.includes(val)) {
            options_html += `<option value="${val}">${$(this).text()}</option>`;
        }
    });
    
    return options_html;
}

function add_class_row() {
    const available_options = get_available_classes();
    const feeding_enabled = $('#feeding_master').is(':checked');
    const classes_enabled = $('#classes_master').is(':checked');
    const tuition_enabled = $('#tuition_master').is(':checked');
    
    const new_row = `
        <div class="row mb-2 class-row" data-row="${row_counter}">
            <div class="col-md-3">
                <select class="form-input-modern class-select" name="class_id[]" onchange="check_add_button()" required>
                    ${available_options}
                </select>
            </div>
            <div class="col-md-3">
                <input type="number" step="0.01" class="form-input-modern feeding-input" name="feeding_charge[]" placeholder="0.00" ${feeding_enabled ? '' : 'disabled'}>
            </div>
            <div class="col-md-2">
                <input type="number" step="0.01" class="form-input-modern classes-input" name="classes_charge[]" placeholder="0.00" ${classes_enabled ? '' : 'disabled'}>
            </div>
            <div class="col-md-2">
                <input type="number" step="0.01" class="form-input-modern tuition-input" name="tuition_charge[]" placeholder="0.00" ${tuition_enabled ? '' : 'disabled'}>
            </div>
            <div class="col-md-2">
                <button type="button" class="btn btn-sm btn-danger w-100" onclick="remove_class_row(${row_counter})" style="border-radius: 8px;">
                    <i class="fa fa-trash"></i>
                </button>
            </div>
        </div>
    `;
    $('#class_rows_container').append(new_row);
    row_counter++;
    $('#add_class_btn').prop('disabled', true);
}

function remove_class_row(row_id) {
    if($('.class-row').length > 1) {
        $(`.class-row[data-row="${row_id}"]`).remove();
        check_add_button();
    } else {
        showAjaxModal_alert('At least one class is required', 'Error');
    }
}

$('#cat_form').submit(function(e) {
    e.preventDefault();
    showAjaxModal_alert('Please wait...', 'Loading');
    
    const cat_name = $('#cat_name').val();
    const discount_type = $('#discount_type').val();
    const class_ids = [];
    const feeding_charges = [];
    const classes_charges = [];
    const tuition_charges = [];
    
    $('.class-row').each(function() {
        const class_id = $(this).find('.class-select').val();
        const feeding_input = $(this).find('.feeding-input');
        const classes_input = $(this).find('.classes-input');
        const tuition_input = $(this).find('.tuition-input');
        
        class_ids.push(class_id);
        feeding_charges.push(feeding_input.prop('disabled') ? null : (feeding_input.val() || '0.00'));
        classes_charges.push(classes_input.prop('disabled') ? null : (classes_input.val() || '0.00'));
        tuition_charges.push(tuition_input.prop('disabled') ? null : (tuition_input.val() || '0.00'));
    });
    
    if(!cat_name) {
        showAjaxModal_alert('Category name is required!', 'Error');
        return false;
    }
    
    $.ajax({
        url: '<?php echo site_url('admin/benefit_category/create/') ?>',
        type: 'post',
        data: {
            cat_name: cat_name,
            discount_type: discount_type,
            class_ids: class_ids,
            feeding_charges: feeding_charges,
            classes_charges: classes_charges,
            tuition_charges: tuition_charges
        },
        success: function(response) {
            if(response == 'done') {
                showAjaxModal_alert('Category Successfully Created', 'Success');
                setTimeout(() => {
                    navigation('<?php echo site_url('admin/beneficiary'); ?>');
                }, 2000);
            } else {
                showAjaxModal_alert('Error please try again', 'Error');
            }
        },
        error: function(err) {
            showAjaxModal_alert(err.responseText, 'Error');
        }
    });
});

$('#add_ben').submit(function(e) {
    e.preventDefault();
    showNotify('Processing your request...', 'Processing', 'info');
    
    $.ajax({
        url: $(this).attr('action'),
        type: 'POST',
        data: $(this).serialize(),
        success: function(response) {
            if(response == 'done' || response.trim() == 'done') {
                showNotify('Beneficiary added successfully!', 'Success', 'success');
                setTimeout(() => {
                    location.reload();
                }, 2000);
            } else {
                showNotify(response || 'Failed to add beneficiary', 'Error', 'error');
            }
        },
        error: function(xhr) {
            const msg = xhr.status === 404 ? 'Page not found' : 
                        xhr.status === 500 ? 'Server error occurred' : 
                        'An error occurred. Please try again.';
            showNotify(msg, 'Error', 'error');
        }
    });
});
</script>

<!-- Notification Alert Modal -->
<div id="notifyModal" style="display:none;position:fixed;top:20px;right:20px;z-index:9999;min-width:300px;max-width:400px;max-height:200px;background:#fff;border-radius:12px;box-shadow:0 10px 40px rgba(0,0,0,0.2);padding:20px;animation:slideIn 0.3s ease;overflow:hidden;">
    <div style="display:flex;align-items:flex-start;gap:15px;">
        <div id="notifyIcon" style="font-size:24px;flex-shrink:0;"></div>
        <div style="flex:1;overflow:hidden;">
            <div id="notifyTitle" style="font-weight:700;font-size:16px;margin-bottom:5px;"></div>
            <div id="notifyMessage" style="color:#6b7280;font-size:14px;max-height:120px;overflow-y:auto;word-break:break-word;"></div>
        </div>
        <button onclick="closeNotify()" style="background:none;border:none;font-size:20px;color:#9ca3af;cursor:pointer;padding:0;line-height:1;flex-shrink:0;">&times;</button>
    </div>
</div>

<style>
@keyframes slideIn {
    from { transform: translateX(400px); opacity: 0; }
    to { transform: translateX(0); opacity: 1; }
}
</style>

<script>
function showNotify(message, title = 'Notification', type = 'info') {
    const modal = document.getElementById('notifyModal');
    const icon = document.getElementById('notifyIcon');
    const titleEl = document.getElementById('notifyTitle');
    const messageEl = document.getElementById('notifyMessage');
    
    const types = {
        success: { icon: '✓', color: '#10b981', bg: '#d1fae5' },
        error: { icon: '✕', color: '#ef4444', bg: '#fee2e2' },
        warning: { icon: '⚠', color: '#f59e0b', bg: '#fef3c7' },
        info: { icon: 'ℹ', color: '#3b82f6', bg: '#dbeafe' }
    };
    
    const config = types[type] || types.info;
    icon.innerHTML = config.icon;
    icon.style.cssText = `font-size:24px;width:40px;height:40px;border-radius:50%;background:${config.bg};color:${config.color};display:flex;align-items:center;justify-content:center;font-weight:bold;`;
    titleEl.textContent = title;
    messageEl.textContent = message;
    
    modal.style.display = 'block';
    setTimeout(() => closeNotify(), 5000);
}

function closeNotify() {
    document.getElementById('notifyModal').style.display = 'none';
}

function showAjaxModal_alert(message, title) {
    const typeMap = { 'Success': 'success', 'Error': 'error', 'Warning': 'warning', 'Loading': 'info' };
    showNotify(message, title, typeMap[title] || 'info');
}
</script>
