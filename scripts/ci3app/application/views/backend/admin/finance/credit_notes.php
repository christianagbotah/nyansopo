<?php $currency = get_settings('currency'); ?>

<div class="row">
    <div class="col-12">
        <div class="page-title-box d-flex align-items-center justify-content-between">
            <h4 class="mb-0 font-size-18"><?php echo get_phrase('credit_notes'); ?></h4>
            <button class="btn btn-primary" onclick="showCreateModal()">
                <i class="mdi mdi-plus-circle"></i> <?php echo get_phrase('create_credit_note'); ?>
            </button>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <ul class="nav nav-tabs nav-tabs-custom mb-3" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" data-toggle="tab" href="#pending" role="tab">
                            <span class="d-none d-md-block"><?php echo get_phrase('pending'); ?></span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-toggle="tab" href="#approved" role="tab">
                            <span class="d-none d-md-block"><?php echo get_phrase('approved'); ?></span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-toggle="tab" href="#applied" role="tab">
                            <span class="d-none d-md-block"><?php echo get_phrase('applied'); ?></span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-toggle="tab" href="#cancelled" role="tab">
                            <span class="d-none d-md-block"><?php echo get_phrase('cancelled'); ?></span>
                        </a>
                    </li>
                </ul>

                <div class="tab-content">
                    <div class="tab-pane active" id="pending" role="tabpanel">
                        <table class="table table-striped dt-responsive nowrap w-100 credit-notes-table" data-status="pending">
                            <thead>
                                <tr>
                                    <th><?php echo get_phrase('credit_note_number'); ?></th>
                                    <th><?php echo get_phrase('student'); ?></th>
                                    <th><?php echo get_phrase('amount'); ?></th>
                                    <th><?php echo get_phrase('reason'); ?></th>
                                    <th><?php echo get_phrase('created_date'); ?></th>
                                    <th><?php echo get_phrase('actions'); ?></th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                    <div class="tab-pane" id="approved" role="tabpanel">
                        <table class="table table-striped dt-responsive nowrap w-100 credit-notes-table" data-status="approved">
                            <thead>
                                <tr>
                                    <th><?php echo get_phrase('credit_note_number'); ?></th>
                                    <th><?php echo get_phrase('student'); ?></th>
                                    <th><?php echo get_phrase('amount'); ?></th>
                                    <th><?php echo get_phrase('approved_by'); ?></th>
                                    <th><?php echo get_phrase('approved_date'); ?></th>
                                    <th><?php echo get_phrase('actions'); ?></th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                    <div class="tab-pane" id="applied" role="tabpanel">
                        <table class="table table-striped dt-responsive nowrap w-100 credit-notes-table" data-status="applied">
                            <thead>
                                <tr>
                                    <th><?php echo get_phrase('credit_note_number'); ?></th>
                                    <th><?php echo get_phrase('student'); ?></th>
                                    <th><?php echo get_phrase('amount'); ?></th>
                                    <th><?php echo get_phrase('applied_date'); ?></th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                    <div class="tab-pane" id="cancelled" role="tabpanel">
                        <table class="table table-striped dt-responsive nowrap w-100 credit-notes-table" data-status="cancelled">
                            <thead>
                                <tr>
                                    <th><?php echo get_phrase('credit_note_number'); ?></th>
                                    <th><?php echo get_phrase('student'); ?></th>
                                    <th><?php echo get_phrase('amount'); ?></th>
                                    <th><?php echo get_phrase('reason'); ?></th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Create Modal -->
<div class="modal fade" id="createModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><?php echo get_phrase('create_credit_note'); ?></h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <form id="createForm" action="<?php echo site_url('finance/credit_notes/create'); ?>" method="post">
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="mdi mdi-information"></i> <?php echo get_phrase('credit_note_info_message'); ?>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><?php echo get_phrase('student'); ?> <span class="text-danger">*</span></label>
                                <select name="student_id" class="form-control select2" required>
                                    <option value=""><?php echo get_phrase('select_student'); ?></option>
                                    <?php
                                    $students = $this->db->get('student')->result_array();
                                    foreach ($students as $student):
                                    ?>
                                    <option value="<?php echo $student['student_id']; ?>"><?php echo $student['name']; ?> (<?php echo $student['student_code']; ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><?php echo get_phrase('invoice_code'); ?></label>
                                <input type="text" name="invoice_code" class="form-control" placeholder="<?php echo get_phrase('optional'); ?>">
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label><?php echo get_phrase('amount'); ?> <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><?php echo $currency; ?></span>
                            </div>
                            <input type="number" step="0.01" name="amount" class="form-control" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label><?php echo get_phrase('reason'); ?> <span class="text-danger">*</span></label>
                        <textarea name="reason" class="form-control" rows="4" required placeholder="<?php echo get_phrase('provide_detailed_reason'); ?>"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal"><?php echo get_phrase('close'); ?></button>
                    <button type="submit" class="btn btn-primary">
                        <i class="mdi mdi-check"></i> <?php echo get_phrase('create'); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let creditNotesTables = {};

$(document).ready(function() {
    initializeTables();
    setupEventListeners();
    $('.select2').select2({ dropdownParent: $('#createModal') });
});

function initializeTables() {
    $('.credit-notes-table').each(function() {
        const status = $(this).data('status');
        const columns = getColumnsForStatus(status);
        
        creditNotesTables[status] = $(this).DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '<?php echo site_url('finance/credit_notes/get_data'); ?>',
                type: 'POST',
                data: { status: status }
            },
            columns: columns,
            order: [[4, 'desc']],
            pageLength: 25,
            responsive: true
        });
    });
}

function getColumnsForStatus(status) {
    const baseColumns = [
        { data: 'credit_note_number' },
        { 
            data: 'student_name',
            render: function(data, type, row) {
                return data + '<br><small class="text-muted">' + row.student_code + '</small>';
            }
        },
        { 
            data: 'amount',
            render: function(data) {
                return '<?php echo $currency; ?>' + parseFloat(data).toFixed(2);
            }
        }
    ];

    if (status === 'pending') {
        return [
            ...baseColumns,
            { 
                data: 'reason',
                render: function(data) {
                    return data.length > 50 ? data.substring(0, 50) + '...' : data;
                }
            },
            { 
                data: 'created_at',
                render: function(data) {
                    return new Date(data).toLocaleDateString();
                }
            },
            {
                data: 'credit_note_id',
                orderable: false,
                render: function(data) {
                    return `
                        <button class="btn btn-sm btn-success" onclick="approveCreditNote(${data})" title="<?php echo get_phrase('approve'); ?>">
                            <i class="mdi mdi-check"></i>
                        </button>
                        <button class="btn btn-sm btn-danger" onclick="cancelCreditNote(${data})" title="<?php echo get_phrase('cancel'); ?>">
                            <i class="mdi mdi-close"></i>
                        </button>
                    `;
                }
            }
        ];
    } else if (status === 'approved') {
        return [
            ...baseColumns,
            { data: 'approved_by_name' },
            { 
                data: 'approved_at',
                render: function(data) {
                    return new Date(data).toLocaleDateString();
                }
            },
            {
                data: 'credit_note_id',
                orderable: false,
                render: function(data) {
                    return `
                        <button class="btn btn-sm btn-primary" onclick="applyCreditNote(${data})" title="<?php echo get_phrase('apply_to_account'); ?>">
                            <i class="mdi mdi-check-circle"></i> <?php echo get_phrase('apply'); ?>
                        </button>
                    `;
                }
            }
        ];
    } else {
        return [
            ...baseColumns,
            { 
                data: status === 'applied' ? 'approved_at' : 'created_at',
                render: function(data) {
                    return new Date(data).toLocaleDateString();
                }
            }
        ];
    }
}

function setupEventListeners() {
    $('#createForm').submit(function(e) {
        e.preventDefault();
        $('.close')[0].click();
        showAjaxModal_alert('<?php echo get_phrase('creating'); ?>...', 'loading');
        
        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: new FormData(this),
            cache: false,
            contentType: false,
            processData: false
        }).done(function(response) {
            var data = typeof response === 'string' ? JSON.parse(response) : response;
            if(data.status === 'success') {
                showAjaxModal_alert(data.message, 'success');
                setTimeout(() => {
                    creditNotesTables['pending'].ajax.reload();
                }, 2000);
            } else {
                showAjaxModal_alert(data.message, 'error');
            }
        }).fail(function() {
            showAjaxModal_alert('<?php echo get_phrase('an_error_occurred'); ?>', 'error');
        });
    });

    $('a[data-toggle="tab"]').on('shown.bs.tab', function(e) {
        const target = $(e.target).attr('href').substring(1);
        if (creditNotesTables[target]) {
            creditNotesTables[target].columns.adjust().responsive.recalc();
        }
    });
}

function showCreateModal() {
    $('#createForm')[0].reset();
    $('#createModal').modal('show');
}

function approveCreditNote(id) {
    showConfirmModal(
        '<?php echo get_phrase('confirm_approval'); ?>',
        '<?php echo get_phrase('approve_credit_note_confirmation'); ?>',
        function() {
            showAjaxModal_alert('<?php echo get_phrase('approving'); ?>...', 'loading');
            $.get('<?php echo site_url('finance/credit_notes/approve/'); ?>' + id, function(response) {
                var data = typeof response === 'string' ? JSON.parse(response) : response;
                showAjaxModal_alert(data.message, data.status);
                if(data.status === 'success') {
                    setTimeout(() => {
                        creditNotesTables['pending'].ajax.reload();
                        creditNotesTables['approved'].ajax.reload();
                    }, 2000);
                }
            });
        },
        '<?php echo get_phrase('approve'); ?>',
        'success'
    );
}

function applyCreditNote(id) {
    showConfirmModal(
        '<?php echo get_phrase('confirm_apply'); ?>',
        '<?php echo get_phrase('apply_credit_note_confirmation'); ?>',
        function() {
            showAjaxModal_alert('<?php echo get_phrase('applying'); ?>...', 'loading');
            $.get('<?php echo site_url('finance/credit_notes/apply/'); ?>' + id, function(response) {
                var data = typeof response === 'string' ? JSON.parse(response) : response;
                showAjaxModal_alert(data.message, data.status);
                if(data.status === 'success') {
                    setTimeout(() => {
                        creditNotesTables['approved'].ajax.reload();
                        creditNotesTables['applied'].ajax.reload();
                    }, 2000);
                }
            });
        },
        '<?php echo get_phrase('apply'); ?>',
        'primary'
    );
}

function cancelCreditNote(id) {
    showConfirmModal(
        '<?php echo get_phrase('confirm_cancel'); ?>',
        '<?php echo get_phrase('cancel_credit_note_confirmation'); ?>',
        function() {
            showAjaxModal_alert('<?php echo get_phrase('cancelling'); ?>...', 'loading');
            $.get('<?php echo site_url('finance/credit_notes/cancel/'); ?>' + id, function(response) {
                var data = typeof response === 'string' ? JSON.parse(response) : response;
                showAjaxModal_alert(data.message, data.status);
                if(data.status === 'success') {
                    setTimeout(() => {
                        creditNotesTables['pending'].ajax.reload();
                        creditNotesTables['cancelled'].ajax.reload();
                    }, 2000);
                }
            });
        },
        '<?php echo get_phrase('cancel'); ?>',
        'danger'
    );
}
</script>

<style>
.nav-tabs-custom {
    border-bottom: 2px solid #dee2e6;
}
.nav-tabs-custom .nav-link {
    border: none;
    color: #6c757d;
}
.nav-tabs-custom .nav-link.active {
    color: #495057;
    border-bottom: 2px solid #007bff;
}
</style>
