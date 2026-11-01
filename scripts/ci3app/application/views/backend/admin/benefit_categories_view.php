<!-- Benefit Categories Management View -->
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            
            <!-- Page Header -->
            <div class="bg-gradient-to-r from-blue-600 to-blue-800 rounded-xl shadow-lg p-6 mb-6">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <div class="bg-white bg-opacity-20 p-3 rounded-lg">
                            <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-3xl font-bold text-white">Benefit Categories</h2>
                            <p class="text-blue-100">Manage scholarship and discount programs for students</p>
                        </div>
                    </div>
                    <button onclick="openAddModal()" class="bg-white text-blue-600 hover:bg-blue-50 font-bold py-3 px-6 rounded-lg transition-all shadow-lg">
                        <i class="fa fa-plus mr-2"></i> Add New Category
                    </button>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <?php
                $total_categories = $this->db->get_where('benefit_category', array('status' => 1))->num_rows();
                $total_beneficiaries = $this->db->get_where('student', array('benefit_status' => 1))->num_rows();
                
                // Calculate total discounts given
                $this->db->select_sum('feeding_charged');
                $this->db->select_sum('classes_charged');
                $feeding_charged = $this->db->get('feeding_fee')->row()->feeding_charged;
                $classes_charged = $this->db->get('feeding_fee')->row()->classes_charged;
                
                $currency = $this->db->get_where('settings', array('type' => 'currency'))->row()->description;
                $fmt = new NumberFormatter('en_US', NumberFormatter::CURRENCY);
                ?>
                
                <div class="bg-white rounded-xl shadow-md border-2 border-blue-200 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-semibold text-gray-600 mb-1">Active Categories</p>
                            <p class="text-3xl font-bold text-blue-600"><?php echo $total_categories; ?></p>
                        </div>
                        <div class="bg-blue-100 p-3 rounded-lg">
                            <i class="fa fa-list-alt text-blue-600 text-2xl"></i>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-xl shadow-md border-2 border-green-200 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-semibold text-gray-600 mb-1">Total Beneficiaries</p>
                            <p class="text-3xl font-bold text-green-600"><?php echo $total_beneficiaries; ?></p>
                        </div>
                        <div class="bg-green-100 p-3 rounded-lg">
                            <i class="fa fa-users text-green-600 text-2xl"></i>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-xl shadow-md border-2 border-purple-200 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-semibold text-gray-600 mb-1">Feeding Discounts</p>
                            <p class="text-2xl font-bold text-purple-600"><?php echo numfmt_format_currency($fmt, $feeding_charged, $currency); ?></p>
                        </div>
                        <div class="bg-purple-100 p-3 rounded-lg">
                            <i class="fa fa-cutlery text-purple-600 text-2xl"></i>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-xl shadow-md border-2 border-orange-200 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-semibold text-gray-600 mb-1">Classes Discounts</p>
                            <p class="text-2xl font-bold text-orange-600"><?php echo numfmt_format_currency($fmt, $classes_charged, $currency); ?></p>
                        </div>
                        <div class="bg-orange-100 p-3 rounded-lg">
                            <i class="fa fa-book text-orange-600 text-2xl"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Categories Table -->
            <div class="bg-white rounded-xl shadow-md border border-gray-200 p-6">
                <div class="mb-4">
                    <input type="text" id="search_categories" placeholder="Search categories..." 
                           class="bg-white border-2 border-gray-300 text-gray-900 text-base rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 block w-full p-3">
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full" id="categories_table">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">#</th>
                                <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Category Name</th>
                                <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Description</th>
                                <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Feeding Discount</th>
                                <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Classes Discount</th>
                                <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Type</th>
                                <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Students</th>
                                <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php
                            $categories = $this->db->order_by('category_name', 'asc')->get('benefit_category')->result_array();
                            $count = 1;
                            foreach($categories as $category):
                                // Count students in this category
                                $student_count = $this->db->get_where('student', array(
                                    'benefit_category_id' => $category['category_id'],
                                    'benefit_status' => 1
                                ))->num_rows();
                            ?>
                            <tr class="hover:bg-gray-50 category-row">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo $count++; ?></td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-bold text-gray-900"><?php echo $category['category_name']; ?></div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-600"><?php echo substr($category['description'], 0, 50) . '...'; ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-sm font-bold text-green-600">
                                        <?php echo $category['feeding_discount']; ?>
                                        <?php echo $category['discount_type'] == 'percentage' ? '%' : $currency; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-sm font-bold text-blue-600">
                                        <?php echo $category['classes_discount']; ?>
                                        <?php echo $category['discount_type'] == 'percentage' ? '%' : $currency; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full <?php echo $category['discount_type'] == 'percentage' ? 'bg-purple-100 text-purple-800' : 'bg-orange-100 text-orange-800'; ?>">
                                        <?php echo ucfirst($category['discount_type']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-3 py-1 text-sm font-bold bg-blue-100 text-blue-800 rounded-full">
                                        <?php echo $student_count; ?> students
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php if($category['status'] == 1): ?>
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Active</span>
                                    <?php else: ?>
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <button onclick="editCategory(<?php echo $category['category_id']; ?>)" 
                                            class="text-blue-600 hover:text-blue-900 mr-3">
                                        <i class="fa fa-edit"></i> Edit
                                    </button>
                                    <button onclick="viewStudents(<?php echo $category['category_id']; ?>)" 
                                            class="text-green-600 hover:text-green-900 mr-3">
                                        <i class="fa fa-users"></i> Students
                                    </button>
                                    <?php if($category['status'] == 1): ?>
                                        <button onclick="toggleStatus(<?php echo $category['category_id']; ?>, 0)" 
                                                class="text-red-600 hover:text-red-900">
                                            <i class="fa fa-ban"></i> Deactivate
                                        </button>
                                    <?php else: ?>
                                        <button onclick="toggleStatus(<?php echo $category['category_id']; ?>, 1)" 
                                                class="text-green-600 hover:text-green-900">
                                            <i class="fa fa-check"></i> Activate
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Add/Edit Category Modal -->
<div id="category_modal" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-blue-600 text-white">
                <h5 class="modal-title" id="modal_title">Add New Benefit Category</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <form id="category_form">
                <div class="modal-body">
                    <input type="hidden" id="category_id" name="category_id">
                    
                    <div class="form-group">
                        <label class="font-bold">Category Name <span class="text-red-600">*</span></label>
                        <input type="text" id="category_name" name="category_name" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="font-bold">Description</label>
                        <textarea id="description" name="description" class="form-control" rows="3"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label class="font-bold">Discount Type <span class="text-red-600">*</span></label>
                        <select id="discount_type" name="discount_type" class="form-control" required>
                            <option value="percentage">Percentage (%)</option>
                            <option value="fixed">Fixed Amount</option>
                        </select>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="font-bold">Feeding Fee Discount <span class="text-red-600">*</span></label>
                                <input type="number" id="feeding_discount" name="feeding_discount" class="form-control" step="0.01" required>
                                <small class="text-gray-600">Enter percentage (0-100) or fixed amount</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="font-bold">Classes Fee Discount <span class="text-red-600">*</span></label>
                                <input type="number" id="classes_discount" name="classes_discount" class="form-control" step="0.01" required>
                                <small class="text-gray-600">Enter percentage (0-100) or fixed amount</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="font-bold">Status</label>
                        <select id="status" name="status" class="form-control">
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fa fa-save"></i> Save Category
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Search functionality
$('#search_categories').on('input', function() {
    const searchTerm = $(this).val().toLowerCase();
    $('.category-row').each(function() {
        const text = $(this).text().toLowerCase();
        if(text.includes(searchTerm)) {
            $(this).show();
        } else {
            $(this).hide();
        }
    });
});

// Open add modal
function openAddModal() {
    $('#modal_title').text('Add New Benefit Category');
    $('#category_form')[0].reset();
    $('#category_id').val('');
    $('#category_modal').modal('show');
}

// Edit category
function editCategory(categoryId) {
    $.ajax({
        url: '<?php echo site_url('admin/get_benefit_category/'); ?>' + categoryId,
        type: 'GET',
        dataType: 'json',
        success: function(data) {
            $('#modal_title').text('Edit Benefit Category');
            $('#category_id').val(data.category_id);
            $('#category_name').val(data.category_name);
            $('#description').val(data.description);
            $('#discount_type').val(data.discount_type);
            $('#feeding_discount').val(data.feeding_discount);
            $('#classes_discount').val(data.classes_discount);
            $('#status').val(data.status);
            $('#category_modal').modal('show');
        }
    });
}

// Save category
$('#category_form').submit(function(e) {
    e.preventDefault();
    
    const formData = $(this).serialize();
    const url = $('#category_id').val() ? 
        '<?php echo site_url('admin/update_benefit_category'); ?>' :
        '<?php echo site_url('admin/create_benefit_category'); ?>';
    
    $.ajax({
        url: url,
        type: 'POST',
        data: formData,
        dataType: 'json',
        success: function(response) {
            if(response.status == 'success') {
                $('#category_modal').modal('hide');
                showAjaxModal_alert('Category saved successfully!', 'Success', true, true);
            } else {
                showAjaxModal_alert(response.message, 'Error', false, true);
            }
        }
    });
});

// Toggle status
function toggleStatus(categoryId, newStatus) {
    showCustomConfirm('Are you sure you want to change the status of this category?', function() {
        $.ajax({
            url: '<?php echo site_url('admin/toggle_benefit_category_status'); ?>',
            type: 'POST',
            data: {
                category_id: categoryId,
                status: newStatus
            },
            dataType: 'json',
            success: function(response) {
                if(response.status == 'success') {
                    showAjaxModal_alert('Category status updated successfully!', 'Success', true, true);
                } else {
                    showAjaxModal_alert(response.message, 'Error', false, true);
                }
            }
        });
    });
}

// View students in category
function viewStudents(categoryId) {
    window.location.href = '<?php echo site_url('admin/benefit_category_students/'); ?>' + categoryId;
}
</script>
