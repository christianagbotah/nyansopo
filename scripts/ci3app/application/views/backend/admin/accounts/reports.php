<?php include APPPATH . 'views/backend/components/enterprise_ui_components.php'; ?>

<?php render_page_header(
    get_phrase(str_replace('_', ' ', $report_type)),
    get_phrase('professional_financial_reporting')
); ?>

<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 no-print">
                <div class="row mb-3">
                    <div class="col-md-3">
                        <label><?php echo get_phrase('from_date'); ?></label>
                        <input type="date" id="fromDate" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label><?php echo get_phrase('to_date'); ?></label>
                        <input type="date" id="toDate" class="form-control">
                    </div>
                    <div class="col-md-2">
                        <label>&nbsp;</label>
                        <button class="btn btn-primary btn-block" onclick="generateReport()">
                            <i class="fa fa-sync"></i> <?php echo get_phrase('generate'); ?>
                        </button>
                    </div>
                    <div class="col-md-2">
                        <label>&nbsp;</label>
                        <button class="btn btn-success btn-block" onclick="exportReport('excel')">
                            <i class="fa fa-file-excel"></i> <?php echo get_phrase('export'); ?>
                        </button>
                    </div>
                    <div class="col-md-2">
                        <label>&nbsp;</label>
                        <button class="btn btn-info btn-block" onclick="printReport()">
                            <i class="fa fa-print"></i> <?php echo get_phrase('print'); ?>
                        </button>
                    </div>
                </div>

                <div id="reportContent">
                    <?php if($report_type == 'balance_sheet'): ?>
                        <div class="balance-sheet-report">
                            <h3 class="text-center"><?php echo get_phrase('balance_sheet'); ?></h3>
                            <p class="text-center">As of <span id="reportDate"></span></p>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <h4><?php echo get_phrase('assets'); ?></h4>
                                    <table class="table table-bordered">
                                        <tbody id="assetsSection"></tbody>
                                        <tfoot>
                                            <tr class="font-weight-bold">
                                                <td><?php echo get_phrase('total_assets'); ?></td>
                                                <td class="text-right" id="totalAssets">0.00</td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <h4><?php echo get_phrase('liabilities_equity'); ?></h4>
                                    <table class="table table-bordered">
                                        <tbody id="liabilitiesSection"></tbody>
                                        <tfoot>
                                            <tr class="font-weight-bold">
                                                <td><?php echo get_phrase('total_liabilities_equity'); ?></td>
                                                <td class="text-right" id="totalLiabilities">0.00</td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>

                    <?php elseif($report_type == 'income_statement'): ?>
                        <div class="income-statement-report">
                            <h3 class="text-center"><?php echo get_phrase('income_statement'); ?></h3>
                            <p class="text-center">For the period <span id="reportPeriod"></span></p>
                            
                            <table class="table table-bordered">
                                <tbody>
                                    <tr class="bg-light">
                                        <td colspan="2"><strong><?php echo get_phrase('revenue'); ?></strong></td>
                                    </tr>
                                    <tbody id="revenueSection"></tbody>
                                    <tr class="font-weight-bold">
                                        <td><?php echo get_phrase('total_revenue'); ?></td>
                                        <td class="text-right" id="totalRevenue">0.00</td>
                                    </tr>
                                    <tr class="bg-light">
                                        <td colspan="2"><strong><?php echo get_phrase('expenses'); ?></strong></td>
                                    </tr>
                                    <tbody id="expensesSection"></tbody>
                                    <tr class="font-weight-bold">
                                        <td><?php echo get_phrase('total_expenses'); ?></td>
                                        <td class="text-right" id="totalExpenses">0.00</td>
                                    </tr>
                                    <tr class="bg-success text-white font-weight-bold">
                                        <td><?php echo get_phrase('net_income'); ?></td>
                                        <td class="text-right" id="netIncome">0.00</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                    <?php elseif($report_type == 'cash_flow'): ?>
                        <div class="cash-flow-report">
                            <h3 class="text-center"><?php echo get_phrase('cash_flow_statement'); ?></h3>
                            <p class="text-center">For the period <span id="reportPeriod"></span></p>
                            
                            <table class="table table-bordered">
                                <tbody>
                                    <tr class="bg-light">
                                        <td colspan="2"><strong><?php echo get_phrase('operating_activities'); ?></strong></td>
                                    </tr>
                                    <tbody id="operatingSection"></tbody>
                                    <tr class="font-weight-bold">
                                        <td><?php echo get_phrase('net_cash_from_operations'); ?></td>
                                        <td class="text-right" id="netOperating">0.00</td>
                                    </tr>
                                    <tr class="bg-light">
                                        <td colspan="2"><strong><?php echo get_phrase('investing_activities'); ?></strong></td>
                                    </tr>
                                    <tbody id="investingSection"></tbody>
                                    <tr class="font-weight-bold">
                                        <td><?php echo get_phrase('net_cash_from_investing'); ?></td>
                                        <td class="text-right" id="netInvesting">0.00</td>
                                    </tr>
                                    <tr class="bg-light">
                                        <td colspan="2"><strong><?php echo get_phrase('financing_activities'); ?></strong></td>
                                    </tr>
                                    <tbody id="financingSection"></tbody>
                                    <tr class="font-weight-bold">
                                        <td><?php echo get_phrase('net_cash_from_financing'); ?></td>
                                        <td class="text-right" id="netFinancing">0.00</td>
                                    </tr>
                                    <tr class="bg-primary text-white font-weight-bold">
                                        <td><?php echo get_phrase('net_change_in_cash'); ?></td>
                                        <td class="text-right" id="netCashChange">0.00</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                    <?php elseif($report_type == 'trial_balance'): ?>
                        <div class="trial-balance-report">
                            <h3 class="text-center"><?php echo get_phrase('trial_balance'); ?></h3>
                            <p class="text-center">As of <span id="reportDate"></span></p>
                            
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th><?php echo get_phrase('account_code'); ?></th>
                                        <th><?php echo get_phrase('account_name'); ?></th>
                                        <th class="text-right"><?php echo get_phrase('debit'); ?></th>
                                        <th class="text-right"><?php echo get_phrase('credit'); ?></th>
                                    </tr>
                                </thead>
                                <tbody id="trialBalanceSection"></tbody>
                                <tfoot>
                                    <tr class="font-weight-bold">
                                        <td colspan="2"><?php echo get_phrase('total'); ?></td>
                                        <td class="text-right" id="totalDebit">0.00</td>
                                        <td class="text-right" id="totalCredit">0.00</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                    <?php elseif($report_type == 'general_ledger'): ?>
                        <div class="general-ledger-report">
                            <h3 class="text-center"><?php echo get_phrase('general_ledger'); ?></h3>
                            <p class="text-center">For the period <span id="reportPeriod"></span></p>
                            
                            <div class="mb-3">
                                <label><?php echo get_phrase('select_account'); ?></label>
                                <select id="accountSelect" class="form-control">
                                    <option value=""><?php echo get_phrase('all_accounts'); ?></option>
                                </select>
                            </div>
                            
                            <div id="ledgerContent"></div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
var reportType = '<?php echo $report_type; ?>';

function generateReport() {
    var fromDate = $('#fromDate').val();
    var toDate = $('#toDate').val();

    if(!fromDate || !toDate) {
        showAjaxModal_alert('<?php echo get_phrase("please_select_date_range"); ?>', 'error');
        return;
    }

    showAjaxModal_alert('<?php echo get_phrase("generating_report"); ?>...', 'loading');

    $.ajax({
        url: '<?php echo site_url("accounts/reports/"); ?>' + reportType,
        type: 'GET',
        data: { from_date: fromDate, to_date: toDate, format: 'json' },
        dataType: 'json'
    }).done(function(response) {
        if(response.status === 'success') {
            populateReport(response.data);
            $('.close').click();
        } else {
            showAjaxModal_alert(response.message, 'error');
        }
    }).fail(function() {
        showAjaxModal_alert('<?php echo get_phrase("operation_failed"); ?>', 'error');
    });
}

function populateReport(data) {
    // Implementation depends on report type
    // This is a placeholder - actual implementation would populate the specific report sections
}

function exportReport(format) {
    var fromDate = $('#fromDate').val();
    var toDate = $('#toDate').val();

    if(!fromDate || !toDate) {
        showAjaxModal_alert('<?php echo get_phrase("please_select_date_range"); ?>', 'error');
        return;
    }

    window.location.href = '<?php echo site_url("accounts/reports/export/"); ?>' + reportType + '/' + format + 
                           '?from_date=' + fromDate + '&to_date=' + toDate;
}

function printReport() {
    window.print();
}
</script>

<style>
@media print {
    .panel-heading, .mb-3, .btn { display: none !important; }
    .panel { border: none !important; box-shadow: none !important; }
}
</style>
