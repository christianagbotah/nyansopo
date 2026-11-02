<hr />
<div class="row">
    <div class="col-md-12">
        <a href="#" onclick="navigation('<?php echo site_url('admin/invoices_show'); ?>')" class="btn btn-<?php if($page_name == 'invoices' || $page_name == 'invoices_loaded') { echo 'success'; } else { echo 'info'; }; ?>">
            <?php echo get_phrase('invoices');?>
        </a>
        <a href="#" onclick="navigation('<?php echo site_url('admin/income/payment_history');?>')" class="btn btn-<?php echo $page_name == 'payment_history' ? 'success' : 'info'; ?>">
            <?php echo get_phrase('payment_history');?>
        </a>
        <a href="#" onclick="navigation('<?php echo site_url('admin/income/student_specific_payment_history');?>')" class="btn btn-<?php echo $page_name == 'student_specific_payment_history' ? 'success' : 'info'; ?>">
            <?php echo get_phrase('student_specific_payment_history');?>
        </a>
    </div>
</div>
<hr>

<?php
    $invoice_code_f       = $this->db->get_where('settings', array('type'=>'invoice_number_format'))->row()->description;
    $inv_number_len = strlen($invoice_code_f);
    $running_year = get_settings('running_year');
    $running_term = get_settings('running_term');

    // Years that actually have payments (for the year filter)
    $payment_years = $this->db->select('year')->distinct()
        ->where('payment_type', 'income')
        ->where('can_delete !=', 'trash')
        ->order_by('year', 'DESC')
        ->get('payment')->result_array();
?>

<!-- Filter bar -->
<div class="row" id="payment_history_filters">
    <div class="col-md-12">
        <div class="panel panel-default" style="border-radius: 8px;">
            <div class="panel-heading" style="display: flex; align-items: center; gap: 8px;">
                <i class="fa fa-filter"></i> <strong><?php echo get_phrase('filter_payments'); ?></strong>
                <button type="button" class="btn btn-default btn-xs pull-right" style="margin-left: auto;" onclick="resetPaymentFilters()">
                    <i class="fa fa-undo"></i> <?php echo get_phrase('reset'); ?>
                </button>
            </div>
            <div class="panel-body">
                <div class="row">
                    <div class="col-md-2 col-sm-4 col-xs-6">
                        <label for="ph_date_from"><i class="fa fa-calendar"></i> <?php echo get_phrase('from_date'); ?></label>
                        <input type="text" id="ph_date_from" class="form-control datepicker" data-format="dd-mm-yyyy" placeholder="<?php echo get_phrase('from_date'); ?>">
                    </div>
                    <div class="col-md-2 col-sm-4 col-xs-6">
                        <label for="ph_date_to"><i class="fa fa-calendar"></i> <?php echo get_phrase('to_date'); ?></label>
                        <input type="text" id="ph_date_to" class="form-control datepicker" data-format="dd-mm-yyyy" placeholder="<?php echo get_phrase('to_date'); ?>">
                    </div>
                    <div class="col-md-2 col-sm-4 col-xs-6">
                        <label for="ph_method"><i class="fa fa-credit-card"></i> <?php echo get_phrase('method'); ?></label>
                        <select id="ph_method" class="form-control selectboxit">
                            <option value=""><?php echo get_phrase('all_methods'); ?></option>
                            <option value="1"><?php echo get_phrase('cash'); ?></option>
                            <option value="2"><?php echo get_phrase('cheque'); ?></option>
                            <option value="3"><?php echo get_phrase('card'); ?></option>
                            <option value="4"><?php echo get_phrase('mobile_money'); ?></option>
                        </select>
                    </div>
                    <div class="col-md-2 col-sm-4 col-xs-6">
                        <label for="ph_term"><i class="fa fa-flag"></i> <?php echo get_phrase('term'); ?></label>
                        <select id="ph_term" class="form-control selectboxit">
                            <option value=""><?php echo get_phrase('all_terms'); ?></option>
                            <option value="1" <?php echo $running_term == 1 ? 'selected' : ''; ?>><?php echo get_phrase('term_1'); ?></option>
                            <option value="2" <?php echo $running_term == 2 ? 'selected' : ''; ?>><?php echo get_phrase('term_2'); ?></option>
                            <option value="3" <?php echo $running_term == 3 ? 'selected' : ''; ?>><?php echo get_phrase('term_3'); ?></option>
                        </select>
                    </div>
                    <div class="col-md-2 col-sm-4 col-xs-6">
                        <label for="ph_year"><i class="fa fa-graduation-cap"></i> <?php echo get_phrase('year'); ?></label>
                        <select id="ph_year" class="form-control selectboxit">
                            <?php if (!empty($payment_years)) : foreach ($payment_years as $y) : ?>
                                <option value="<?php echo $y['year']; ?>" <?php echo $y['year'] == $running_year ? 'selected' : ''; ?>><?php echo $y['year']; ?></option>
                            <?php endforeach; else : ?>
                                <option value="<?php echo $running_year; ?>"><?php echo $running_year; ?></option>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="col-md-2 col-sm-4 col-xs-6" style="display: flex; align-items: flex-end;">
                        <button type="button" class="btn btn-success btn-block" onclick="applyPaymentFilters()">
                            <i class="fa fa-search"></i> <?php echo get_phrase('apply_filters'); ?>
                        </button>
                    </div>
                </div>
                <div class="row" style="margin-top: 6px;">
                    <div class="col-md-12">
                        <span class="text-muted" id="ph_filter_summary" style="font-size: 12px;"></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <table class="table table-bordered" id="histories">
            <thead>
                <tr>
                    <th><div><?php echo get_phrase('title');?></div></th>
                    <th><div><?php echo get_phrase('description');?></div></th>
                    <th><div><?php echo get_phrase('method');?></div></th>
                    <th><div><?php echo get_phrase('amount');?></div></th>
                    <th><div><?php echo get_phrase('date');?></div></th>
                    <th><div><?php echo get_phrase('options');?></div></th>
                </tr>
            </thead>
        </table>
    </div>
</div>

<script type="text/javascript">
    var phTable;

    function paymentFilterParams() {
        return {
            date_from: $('#ph_date_from').val(),
            date_to: $('#ph_date_to').val(),
            payment_method: $('#ph_method').val(),
            term: $('#ph_term').val(),
            year: $('#ph_year').val()
        };
    }

    function updateFilterSummary() {
        var p = paymentFilterParams();
        var bits = [];
        if (p.date_from) bits.push('<?php echo get_phrase("from_date"); ?>: ' + p.date_from);
        if (p.date_to) bits.push('<?php echo get_phrase("to_date"); ?>: ' + p.date_to);
        if (p.payment_method) {
            var methods = {1: '<?php echo get_phrase("cash"); ?>', 2: '<?php echo get_phrase("cheque"); ?>', 3: '<?php echo get_phrase("card"); ?>', 4: '<?php echo get_phrase("mobile_money"); ?>'};
            bits.push('<?php echo get_phrase("method"); ?>: ' + (methods[p.payment_method] || p.payment_method));
        }
        if (p.term) bits.push('<?php echo get_phrase("term"); ?>: ' + p.term);
        if (p.year) bits.push('<?php echo get_phrase("year"); ?>: ' + p.year);
        $('#ph_filter_summary').html(bits.length
            ? '<i class="fa fa-info-circle"></i> ' + bits.join(' &nbsp;|&nbsp; ')
            : '');
    }

    function applyPaymentFilters() {
        if (phTable) {
            updateFilterSummary();
            phTable.ajax.reload();
        }
    }

    function resetPaymentFilters() {
        $('#ph_date_from').val('');
        $('#ph_date_to').val('');
        $('#ph_method').val('');
        $('#ph_term').val('');
        $('#ph_year').val('<?php echo $running_year; ?>');
        // refresh selectboxit widgets if present
        if ($.fn.selectBoxIt) {
            $('#ph_method, #ph_term, #ph_year').selectBoxIt('refresh');
        }
        applyPaymentFilters();
    }

    $(document).ready(function() {
        $.fn.dataTable.ext.errMode = 'throw';
        phTable = $('#histories').DataTable({
            "processing": true,
            "serverSide": true,
            "ajax":{
                "url": "<?php echo site_url('admin/get_payments'); ?>",
                "dataType": "json",
                "type": "POST",
                "data": function (d) {
                    var p = paymentFilterParams();
                    d.date_from = p.date_from;
                    d.date_to = p.date_to;
                    d.payment_method = p.payment_method;
                    d.term = p.term;
                    d.year = p.year;
                }
            },
            "columns": [
                { "data": "title" },
                { "data": "description" },
                { "data": "method" },
                { "data": "amount" },
                { "data": "date" },
                { "data": "options" },
            ],
            "columnDefs": [
                {
                    "targets": [2,5],
                    "orderable": false
                },
            ]
        });

        // Enter key inside the date fields applies the filters
        $('#ph_date_from, #ph_date_to').on('keyup', function(e) {
            if (e.keyCode === 13) applyPaymentFilters();
        });

        updateFilterSummary();
    });


    function invoice_view_modal(invoice_code) {

        invoice_code = invoice_code.toString();
        let invoice_original_len = '<?php echo $inv_number_len; ?>';
        let current_invoice_len = invoice_code.length;

        if(invoice_code.substring(0, 1) == '_') {
            invoice_code = invoice_code.substring(1);
        } else {
            invoice_code = invoice_code;
        }

        $.ajax({
            url: '<?php echo site_url('admin/get_invoice_term/'); ?>'+ invoice_code,
            success: function(response) {
              showAjaxModal('<?php echo site_url('modal/popup/modal_view_invoice/');?>' + invoice_code + '/' + response);
            }
        });
    }

    function bulk_invoice_view_modal(student_id) {

        showAjaxModal('<?php echo site_url('modal/popup/modal_view_bulk_invoice/');?>' + student_id);

    }
</script>
