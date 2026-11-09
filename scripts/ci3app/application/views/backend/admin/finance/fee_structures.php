<?php $currency = get_settings('currency'); ?>

<div class="row">
    <div class="col-12">
        <div class="page-title-box d-flex align-items-center justify-content-between">
            <h4 class="mb-0 font-size-18"><?php echo get_phrase('fee_structures'); ?></h4>
            <button class="btn btn-primary" onclick="showCreateModal()">
                <i class="mdi mdi-plus-circle"></i> <?php echo get_phrase('create_fee_structure'); ?>
            </button>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-4">
                        <select id="filter-class" class="form-control">
                            <option value=""><?php echo get_phrase('all_classes'); ?></option>
                            <?php
                            $classes = $this->db->get('class')->result_array();
                            foreach ($classes as $class):
                            ?>
                            <option value="<?php echo $class['class_id']; ?>"><?php echo $class['name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <select id="filter-year" class="form-control">
                            <option value=""><?php echo get_phrase('all_years'); ?></option>
                            <?php
                            $current_year = get_settings('running_year');
                            for ($i = -2; $i <= 2; $i++):
                                $year_parts = explode('-', $current_year);
                                $year = ($year_parts[0] + $i) . '-' . ($year_parts[1] + $i);
                            ?>
                            <option value="<?php echo $year; ?>" <?php echo $i == 0 ? 'selected' : ''; ?>><?php echo $year; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <button class="btn btn-info btn-block" onclick="applyFilters()">
                            <i class="mdi mdi-filter"></i> <?php echo get_phrase('apply_filters'); ?>
                        </button>
                    </div>
                </div>

                <table id="fee-structures-table" class="table table-striped dt-responsive nowrap w-100">
                    <thead>
                        <tr>
                            <th><?php echo get_phrase('class'); ?></th>
                            <th><?php echo get_phrase('academic_year'); ?></th>
                            <th><?php echo get_phrase('term'); ?></th>
                            <th><?php echo get_phrase('total_amount'); ?></th>
                            <th><?php echo get_phrase('items'); ?></th>
                            <th><?php echo get_phrase('status'); ?></th>
                            <th><?php echo get_phrase('actions'); ?></th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>



<script>
let feeStructuresTable;
let feeItemCounter = 1;

$(document).ready(function() {
    initializeDataTable();
    setupEventListeners();
});

function initializeDataTable() {
    feeStructuresTable = $('#fee-structures-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '<?php echo site_url('finance/fee_structures/get_data'); ?>',
            type: 'POST',
            data: function(d) {
                d.class_id = $('#filter-class').val();
                d.year = $('#filter-year').val();
            }
        },
        columns: [
            { data: 'class_name' },
            { data: 'academic_year' },
            { data: 'term' },
            { 
                data: 'total_amount',
                render: function(data) {
                    return '<?php echo $currency; ?>' + parseFloat(data).toFixed(2);
                }
            },
            {
                data: 'fee_items',
                render: function(data) {
                    const items = JSON.parse(data);
                    return '<span class="badge badge-primary">' + items.length + ' items</span>';
                }
            },
            {
                data: 'is_active',
                render: function(data) {
                    return data == 1 
                        ? '<span class="badge badge-success"><?php echo get_phrase('active'); ?></span>'
                        : '<span class="badge badge-secondary"><?php echo get_phrase('inactive'); ?></span>';
                }
            },
            {
                data: 'structure_id',
                orderable: false,
                render: function(data, type, row) {
                    return `
                        <div class="btn-group">
                            <button class="btn btn-sm btn-info" onclick='viewFeeStructure(${JSON.stringify(row)})' title="<?php echo get_phrase('view'); ?>">
                                <i class="mdi mdi-eye"></i>
                            </button>
                            <button class="btn btn-sm btn-primary" onclick='editFeeStructure(${JSON.stringify(row)})' title="<?php echo get_phrase('edit'); ?>">
                                <i class="mdi mdi-pencil"></i>
                            </button>
                            <button class="btn btn-sm btn-secondary" onclick="cloneFeeStructure(${data})" title="<?php echo get_phrase('clone'); ?>">
                                <i class="mdi mdi-content-copy"></i>
                            </button>
                            ${row.is_active == 1 ? 
                                `<button class="btn btn-sm btn-warning" onclick="deactivateFeeStructure(${data})" title="<?php echo get_phrase('deactivate'); ?>">
                                    <i class="mdi mdi-close-circle"></i>
                                </button>` : ''}
                        </div>
                    `;
                }
            }
        ],
        order: [[1, 'desc'], [2, 'desc']],
        pageLength: 25,
        responsive: true
    });
}

function setupEventListeners() {
    $('#feeForm').submit(function(e) {
        e.preventDefault();
        $('.close')[0].click();
        showAjaxModal_alert('<?php echo get_phrase('saving'); ?>...', 'loading');
        
        const formData = new FormData(this);
        const url = $('#structure-id').val() 
            ? '<?php echo site_url('finance/fee_structures/update/'); ?>' + $('#structure-id').val()
            : $(this).attr('action');
        
        $.ajax({
            url: url,
            type: 'POST',
            data: formData,
            cache: false,
            contentType: false,
            processData: false
        }).done(function(response) {
            var data = typeof response === 'string' ? JSON.parse(response) : response;
            if(data.status === 'success') {
                showAjaxModal_alert(data.message, 'success');
                setTimeout(() => feeStructuresTable.ajax.reload(), 2000);
            } else {
                showAjaxModal_alert(data.message, 'error');
            }
        }).fail(function() {
            showAjaxModal_alert('<?php echo get_phrase('an_error_occurred'); ?>', 'error');
        });
    });

    $(document).on('input', '.fee-item-amount', calculateTotal);
}

function showCreateModal() {
    const formHtml = `
        <form id="feeForm" action="<?php echo site_url('finance/fee_structures/create'); ?>" method="post">
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label><?php echo get_phrase('class'); ?> <span class="text-danger">*</span></label>
                        <select name="class_id" class="form-control" required>
                            <option value=""><?php echo get_phrase('select_class'); ?></option>
                            <?php foreach ($classes as $class): ?>
                            <option value="<?php echo $class['class_id']; ?>"><?php echo $class['name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label><?php echo get_phrase('academic_year'); ?> <span class="text-danger">*</span></label>
                        <select name="academic_year" class="form-control" required>
                            <?php
                            for ($i = -1; $i <= 2; $i++):
                                $year_parts = explode('-', $current_year);
                                $year = ($year_parts[0] + $i) . '-' . ($year_parts[1] + $i);
                            ?>
                            <option value="<?php echo $year; ?>" <?php echo $i == 0 ? 'selected' : ''; ?>><?php echo $year; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label><?php echo get_phrase('term'); ?> <span class="text-danger">*</span></label>
                        <select name="term" class="form-control" required>
                            <option value="1">1</option>
                            <option value="2">2</option>
                            <option value="3">3</option>
                        </select>
                    </div>
                </div>
            </div>
            <hr>
            <h5><?php echo get_phrase('fee_items'); ?></h5>
            <div id="fee-items-container">
                <div class="fee-item-row row mb-2">
                    <div class="col-md-5">
                        <input type="text" class="form-control" name="fee_items[0][name]" placeholder="<?php echo get_phrase('item_name'); ?>" required>
                    </div>
                    <div class="col-md-4">
                        <input type="number" step="0.01" class="form-control fee-item-amount" name="fee_items[0][amount]" placeholder="<?php echo get_phrase('amount'); ?>" required>
                    </div>
                    <div class="col-md-3">
                        <button type="button" class="btn btn-success btn-block" onclick="addFeeItem()">
                            <i class="mdi mdi-plus"></i> <?php echo get_phrase('add_item'); ?>
                        </button>
                    </div>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-md-12">
                    <div class="alert alert-info">
                        <strong><?php echo get_phrase('total_amount'); ?>:</strong> 
                        <span id="total-amount" class="float-right font-size-18"><?php echo $currency; ?>0.00</span>
                    </div>
                </div>
            </div>
            <input type="hidden" name="total_amount" id="total-amount-input">
            <input type="hidden" name="structure_id" id="structure-id">
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal"><?php echo get_phrase('close'); ?></button>
                <button type="submit" class="btn btn-primary">
                    <i class="mdi mdi-check"></i> <?php echo get_phrase('save'); ?>
                </button>
            </div>
        </form>
    `;
    showModalWithContent('createModal', '<i class="mdi mdi-plus-circle"></i> <?php echo get_phrase('create_fee_structure'); ?>', formHtml);
    feeItemCounter = 1;
    setTimeout(() => {
        setupEventListeners();
        calculateTotal();
    }, 100);
}

function addFeeItem() {
    const html = `
        <div class="fee-item-row row mb-2">
            <div class="col-md-5">
                <input type="text" class="form-control" name="fee_items[${feeItemCounter}][name]" placeholder="<?php echo get_phrase('item_name'); ?>" required>
            </div>
            <div class="col-md-4">
                <input type="number" step="0.01" class="form-control fee-item-amount" name="fee_items[${feeItemCounter}][amount]" placeholder="<?php echo get_phrase('amount'); ?>" required>
            </div>
            <div class="col-md-3">
                <button type="button" class="btn btn-danger btn-block" onclick="removeFeeItem(this)">
                    <i class="mdi mdi-delete"></i> <?php echo get_phrase('remove'); ?>
                </button>
            </div>
        </div>
    `;
    $('#fee-items-container').append(html);
    feeItemCounter++;
}

function removeFeeItem(btn) {
    $(btn).closest('.fee-item-row').remove();
    calculateTotal();
}

function calculateTotal() {
    let total = 0;
    $('.fee-item-amount').each(function() {
        const val = parseFloat($(this).val()) || 0;
        total += val;
    });
    $('#total-amount').text('<?php echo $currency; ?>' + total.toFixed(2));
    $('#total-amount-input').val(total.toFixed(2));
}

function editFeeStructure(row) {
    const items = JSON.parse(row.fee_items);
    let itemsHtml = '';
    items.forEach((item, index) => {
        itemsHtml += `
            <div class="fee-item-row row mb-2">
                <div class="col-md-5">
                    <input type="text" class="form-control" name="fee_items[${index}][name]" value="${item.name}" required>
                </div>
                <div class="col-md-4">
                    <input type="number" step="0.01" class="form-control fee-item-amount" name="fee_items[${index}][amount]" value="${item.amount}" required>
                </div>
                <div class="col-md-3">
                    <button type="button" class="btn btn-${index === 0 ? 'success' : 'danger'} btn-block" onclick="${index === 0 ? 'addFeeItem()' : 'removeFeeItem(this)'}">
                        <i class="mdi mdi-${index === 0 ? 'plus' : 'delete'}"></i> ${index === 0 ? '<?php echo get_phrase('add_item'); ?>' : '<?php echo get_phrase('remove'); ?>'}
                    </button>
                </div>
            </div>
        `;
    });
    
    const formHtml = `
        <form id="feeForm" action="<?php echo site_url('finance/fee_structures/create'); ?>" method="post">
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label><?php echo get_phrase('class'); ?> <span class="text-danger">*</span></label>
                        <select name="class_id" class="form-control" required>
                            <option value=""><?php echo get_phrase('select_class'); ?></option>
                            <?php foreach ($classes as $class): ?>
                            <option value="<?php echo $class['class_id']; ?>"><?php echo $class['name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label><?php echo get_phrase('academic_year'); ?> <span class="text-danger">*</span></label>
                        <select name="academic_year" class="form-control" required>
                            <?php
                            for ($i = -1; $i <= 2; $i++):
                                $year_parts = explode('-', $current_year);
                                $year = ($year_parts[0] + $i) . '-' . ($year_parts[1] + $i);
                            ?>
                            <option value="<?php echo $year; ?>" <?php echo $i == 0 ? 'selected' : ''; ?>><?php echo $year; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label><?php echo get_phrase('term'); ?> <span class="text-danger">*</span></label>
                        <select name="term" class="form-control" required>
                            <option value="1">1</option>
                            <option value="2">2</option>
                            <option value="3">3</option>
                        </select>
                    </div>
                </div>
            </div>
            <hr>
            <h5><?php echo get_phrase('fee_items'); ?></h5>
            <div id="fee-items-container">${itemsHtml}</div>
            <div class="row mt-3">
                <div class="col-md-12">
                    <div class="alert alert-info">
                        <strong><?php echo get_phrase('total_amount'); ?>:</strong> 
                        <span id="total-amount" class="float-right font-size-18"><?php echo $currency; ?>0.00</span>
                    </div>
                </div>
            </div>
            <input type="hidden" name="total_amount" id="total-amount-input">
            <input type="hidden" name="structure_id" id="structure-id" value="${row.structure_id}">
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal"><?php echo get_phrase('close'); ?></button>
                <button type="submit" class="btn btn-primary">
                    <i class="mdi mdi-check"></i> <?php echo get_phrase('save'); ?>
                </button>
            </div>
        </form>
    `;
    showModalWithContent('createModal', '<i class="mdi mdi-pencil"></i> <?php echo get_phrase('edit_fee_structure'); ?>', formHtml);
    feeItemCounter = items.length;
    setTimeout(() => {
        $('[name="class_id"]').val(row.class_id);
        $('[name="academic_year"]').val(row.academic_year);
        $('[name="term"]').val(row.term);
        setupEventListeners();
        calculateTotal();
    }, 100);
}

function viewFeeStructure(row) {
    const items = JSON.parse(row.fee_items);
    let itemsHtml = '<ul class="list-group">';
    items.forEach(item => {
        itemsHtml += `<li class="list-group-item d-flex justify-content-between">
            <span>${item.name}</span>
            <strong><?php echo $currency; ?>${parseFloat(item.amount).toFixed(2)}</strong>
        </li>`;
    });
    itemsHtml += '</ul>';
    
    const content = `
        <div class="row">
            <div class="col-md-6"><strong><?php echo get_phrase('class'); ?>:</strong> ${row.class_name}</div>
            <div class="col-md-6"><strong><?php echo get_phrase('academic_year'); ?>:</strong> ${row.academic_year}</div>
        </div>
        <div class="row mt-2">
            <div class="col-md-6"><strong><?php echo get_phrase('term'); ?>:</strong> ${row.term}</div>
            <div class="col-md-6"><strong><?php echo get_phrase('total_amount'); ?>:</strong> <?php echo $currency; ?>${parseFloat(row.total_amount).toFixed(2)}</div>
        </div>
        <hr>
        <h6><?php echo get_phrase('fee_items'); ?></h6>
        ${itemsHtml}
    `;
    
    showAjaxModal_alert(content, 'info');
}

function cloneFeeStructure(id) {
    showConfirmModal(
        '<?php echo get_phrase('confirm_clone'); ?>',
        '<?php echo get_phrase('clone_fee_structure_confirmation'); ?>',
        function() {
            showAjaxModal_alert('<?php echo get_phrase('cloning'); ?>...', 'loading');
            $.get('<?php echo site_url('finance/fee_structures/clone/'); ?>' + id, function(response) {
                var data = typeof response === 'string' ? JSON.parse(response) : response;
                showAjaxModal_alert(data.message, data.status);
                if(data.status === 'success') {
                    setTimeout(() => feeStructuresTable.ajax.reload(), 2000);
                }
            });
        },
        '<?php echo get_phrase('clone'); ?>',
        'primary'
    );
}

function deactivateFeeStructure(id) {
    showConfirmModal(
        '<?php echo get_phrase('confirm_deactivate'); ?>',
        '<?php echo get_phrase('deactivate_fee_structure_confirmation'); ?>',
        function() {
            showAjaxModal_alert('<?php echo get_phrase('deactivating'); ?>...', 'loading');
            $.get('<?php echo site_url('finance/fee_structures/deactivate/'); ?>' + id, function(response) {
                var data = typeof response === 'string' ? JSON.parse(response) : response;
                showAjaxModal_alert(data.message, data.status);
                if(data.status === 'success') {
                    setTimeout(() => feeStructuresTable.ajax.reload(), 2000);
                }
            });
        },
        '<?php echo get_phrase('deactivate'); ?>',
        'warning'
    );
}

function applyFilters() {
    feeStructuresTable.ajax.reload();
}
</script>
