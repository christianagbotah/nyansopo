<!doctype html>
<html>
    <head>
        <title>FINANCIAL STATEMENT</title>
        <?php include 'includes/includes_top.php';?>
    </head>
    <body>
        <div class="container" id="top">

            <!-- Page Title -->
            <div class="page-title-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 20px 30px; border-radius: 12px; margin-bottom: 20px; box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);">
                <h1 style="margin: 0; color: white; font-size: 28px; font-weight: 700; letter-spacing: 1px;">
                    <i class="fa fa-balance-scale" style="margin-right: 12px;"></i>Financial Statement
                </h1>
                <p style="margin: 8px 0 0 0; color: rgba(255,255,255,0.9); font-size: 14px; font-weight: 500;">
                    Monthly summary of fees billed vs payments collected
                </p>
            </div>

            <!-- Report Navigation Tabs -->
            <div class="report-tabs" style="background: white; border-radius: 12px; padding: 15px 20px; margin-bottom: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); display: flex; gap: 10px; flex-wrap: wrap;">
                <a href="<?php echo site_url('admin/financial_reports/receivables'); ?>" class="tab-link" style="padding: 10px 20px; border-radius: 8px; font-weight: 600; font-size: 14px; text-decoration: none; transition: all 0.3s; background: #f3f4f6; color: #374151;">
                    <i class="fa fa-file-text"></i> Receivables
                </a>
                <a href="<?php echo site_url('admin/financial_reports/payables'); ?>" class="tab-link" style="padding: 10px 20px; border-radius: 8px; font-weight: 600; font-size: 14px; text-decoration: none; transition: all 0.3s; background: #f3f4f6; color: #374151;">
                    <i class="fa fa-file-text"></i> Payables
                </a>
                <a href="<?php echo site_url('admin/financial_reports/payments'); ?>" class="tab-link" style="padding: 10px 20px; border-radius: 8px; font-weight: 600; font-size: 14px; text-decoration: none; transition: all 0.3s; background: #f3f4f6; color: #374151;">
                    <i class="fa fa-money"></i> Payments
                </a>
                <a href="<?php echo site_url('admin/financial_reports/income-expenditure'); ?>" class="tab-link" style="padding: 10px 20px; border-radius: 8px; font-weight: 600; font-size: 14px; text-decoration: none; transition: all 0.3s; background: #f3f4f6; color: #374151;">
                    <i class="fa fa-bar-chart"></i> Income &amp; Expenditure
                </a>
                <a href="#" class="tab-link active" style="padding: 10px 20px; border-radius: 8px; font-weight: 600; font-size: 14px; text-decoration: none; transition: all 0.3s; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);">
                    <i class="fa fa-balance-scale"></i> Statement
                </a>
            </div>
            <style>
                .tab-link:hover:not(.active) { background: #e5e7eb !important; color: #1f2937 !important; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
                @media print { .report-tabs { display: none !important; } .page-title-header { display: none !important; } #selectors_div { display: none !important; } }
            </style>

            <?php
            $currency_row = $this->db->get_where('settings', array('type' => 'currency'))->row();
            $currency = $currency_row ? $currency_row->description : 'GHS';

            $start = $date_start ?: strtotime(date('Y-01-01'));
            $end = $date_end ?: strtotime(date('d-m-Y 23:59'));

            // Billed per month (invoices created within period)
            $billed = array();
            $this->db->select("FROM_UNIXTIME(creation_timestamp, '%Y-%m') AS ym, SUM(amount) AS total", FALSE);
            $this->db->where('creation_timestamp >=', $start);
            $this->db->where('creation_timestamp <=', $end);
            $this->db->group_by('ym');
            $billed_rows = $this->db->get('invoice')->result_array();
            foreach ($billed_rows as $r) { if ($r['ym']) $billed[$r['ym']] = (float)$r['total']; }

            // Collected per month (payments received within period)
            $collected = array();
            $this->db->select("FROM_UNIXTIME(timestamp, '%Y-%m') AS ym, SUM(amount) AS total", FALSE);
            $this->db->where('timestamp >=', $start);
            $this->db->where('timestamp <=', $end);
            $this->db->group_by('ym');
            $collected_rows = $this->db->get('payment')->result_array();
            foreach ($collected_rows as $r) { if ($r['ym']) $collected[$r['ym']] = (float)$r['total']; }

            // Payments by method within period
            $by_method = array();
            $this->db->select('payment_method, SUM(amount) AS total');
            $this->db->where('timestamp >=', $start);
            $this->db->where('timestamp <=', $end);
            $this->db->group_by('payment_method');
            $method_rows = $this->db->get('payment')->result_array();
            foreach ($method_rows as $r) {
                $m = trim($r['payment_method']);
                if ($m === '') $m = 'unknown';
                $by_method[ucfirst($m)] = (float)$r['total'];
            }

            // Total outstanding as at end of period (all invoices created up to end)
            $this->db->select('SUM(due) AS total_due');
            $this->db->where('creation_timestamp <=', $end);
            $due_row = $this->db->get('invoice')->row();
            $outstanding = $due_row ? (float)$due_row->total_due : 0;

            // Merge month keys
            $months = array_unique(array_merge(array_keys($billed), array_keys($collected)));
            sort($months);

            $total_billed = array_sum($billed);
            $total_collected = array_sum($collected);
            ?>

            <!-- Filter / date range -->
            <div class="row" id="selectors_div">
                <div class="col-sm-12">
                    <div style="background:#ffffff; border:1px solid #e5e7eb; border-radius:12px; padding:20px 24px; box-shadow:0 4px 12px rgba(0,0,0,0.08);">
                        <form onsubmit="statementNav(); return false;" class="form-inline" style="display:flex; gap:14px; flex-wrap:wrap; align-items:flex-end;">
                            <div style="flex:1; min-width:170px;">
                                <label style="display:block; font-weight:600; margin-bottom:6px; color:#111827; font-size:13px;"><i class="fa fa-calendar"></i> Start Date</label>
                                <input type="text" id="stStart" class="form-control datepicker" readonly="readonly" style="width:100%; padding:9px 12px; border:2px solid #e5e7eb; border-radius:10px; background:#fff;" value="<?php echo date('d-m-Y', $start); ?>">
                            </div>
                            <div style="flex:1; min-width:170px;">
                                <label style="display:block; font-weight:600; margin-bottom:6px; color:#111827; font-size:13px;"><i class="fa fa-calendar"></i> End Date</label>
                                <input type="text" id="stEnd" class="form-control datepicker" readonly="readonly" style="width:100%; padding:9px 12px; border:2px solid #e5e7eb; border-radius:10px; background:#fff;" value="<?php echo date('d-m-Y', $end); ?>">
                            </div>
                            <button type="submit" class="btn btn-success" style="height:42px; border-radius:10px; font-weight:600; padding:0 22px;"><i class="fa fa-sync"></i> Load</button>
                            <button type="button" onclick="PrintElem('#print')" class="btn btn-primary" style="height:42px; border-radius:10px; font-weight:600; padding:0 22px;"><i class="fa fa-print"></i> Print</button>
                        </form>
                    </div>
                </div>
            </div>

            <div id="print">
                <!-- Summary Cards -->
                <div class="row" style="margin-top: 20px;">
                    <div class="col-md-3 col-sm-6">
                        <div style="background:linear-gradient(135deg,#3b82f6,#2563eb); color:#fff; border-radius:12px; padding:20px; box-shadow:0 4px 12px rgba(59,130,246,.3);">
                            <div style="font-size:12px; text-transform:uppercase; letter-spacing:1px; opacity:.9;">Total Billed</div>
                            <div style="font-size:26px; font-weight:800; margin-top:6px;"><?php echo $currency; ?> <?php echo number_format($total_billed, 2); ?></div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <div style="background:linear-gradient(135deg,#10b981,#059669); color:#fff; border-radius:12px; padding:20px; box-shadow:0 4px 12px rgba(16,185,129,.3);">
                            <div style="font-size:12px; text-transform:uppercase; letter-spacing:1px; opacity:.9;">Total Collected</div>
                            <div style="font-size:26px; font-weight:800; margin-top:6px;"><?php echo $currency; ?> <?php echo number_format($total_collected, 2); ?></div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <div style="background:linear-gradient(135deg,#f59e0b,#d97706); color:#fff; border-radius:12px; padding:20px; box-shadow:0 4px 12px rgba(245,158,11,.3);">
                            <div style="font-size:12px; text-transform:uppercase; letter-spacing:1px; opacity:.9;">Period Variance</div>
                            <div style="font-size:26px; font-weight:800; margin-top:6px;"><?php echo $currency; ?> <?php echo number_format($total_collected - $total_billed, 2); ?></div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <div style="background:linear-gradient(135deg,#ef4444,#dc2626); color:#fff; border-radius:12px; padding:20px; box-shadow:0 4px 12px rgba(239,68,68,.3);">
                            <div style="font-size:12px; text-transform:uppercase; letter-spacing:1px; opacity:.9;">Total Outstanding (all time to date)</div>
                            <div style="font-size:26px; font-weight:800; margin-top:6px;"><?php echo $currency; ?> <?php echo number_format($outstanding, 2); ?></div>
                        </div>
                    </div>
                </div>

                <!-- Monthly statement table -->
                <div style="background:#fff; border-radius:12px; padding:22px; margin-top:20px; box-shadow:0 2px 8px rgba(0,0,0,0.08); overflow-x:auto;">
                    <h4 style="margin:0 0 14px 0; font-weight:700; color:#1f2937;"><i class="fa fa-table" style="color:#667eea;"></i> Monthly Statement (<?php echo date('d M Y', $start); ?> — <?php echo date('d M Y', $end); ?>)</h4>
                    <table class="table table-bordered" style="font-size:13.5px;">
                        <thead>
                            <tr style="background:#111827; color:#fff;">
                                <th style="padding:10px 12px;">Month</th>
                                <th style="padding:10px 12px; text-align:right;">Fees Billed (<?php echo $currency; ?>)</th>
                                <th style="padding:10px 12px; text-align:right;">Payments Collected (<?php echo $currency; ?>)</th>
                                <th style="padding:10px 12px; text-align:right;">Net Movement (<?php echo $currency; ?>)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($months)): ?>
                            <tr><td colspan="4" style="text-align:center; padding:30px; color:#6b7280;">No transactions in the selected period.</td></tr>
                            <?php else: $run = 0; foreach ($months as $ym):
                                $b = isset($billed[$ym]) ? $billed[$ym] : 0;
                                $c = isset($collected[$ym]) ? $collected[$ym] : 0;
                                $net = $c - $b;
                                $run += $net;
                            ?>
                            <tr>
                                <td style="padding:9px 12px; font-weight:700;"><?php echo date('F Y', strtotime($ym . '-01')); ?></td>
                                <td style="padding:9px 12px; text-align:right;"><?php echo number_format($b, 2); ?></td>
                                <td style="padding:9px 12px; text-align:right; color:#059669; font-weight:700;"><?php echo number_format($c, 2); ?></td>
                                <td style="padding:9px 12px; text-align:right; font-weight:800; color:<?php echo $net >= 0 ? '#059669' : '#b91c1c'; ?>;"><?php echo number_format($net, 2); ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <tr style="background:#f9fafb; font-weight:800; border-top:2px solid #111827;">
                                <td style="padding:10px 12px;">TOTAL</td>
                                <td style="padding:10px 12px; text-align:right;"><?php echo number_format($total_billed, 2); ?></td>
                                <td style="padding:10px 12px; text-align:right; color:#059669;"><?php echo number_format($total_collected, 2); ?></td>
                                <td style="padding:10px 12px; text-align:right; color:<?php echo ($total_collected - $total_billed) >= 0 ? '#059669' : '#b91c1c'; ?>;"><?php echo number_format($total_collected - $total_billed, 2); ?></td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Collections by method -->
                <?php if (!empty($by_method)): ?>
                <div style="background:#fff; border-radius:12px; padding:22px; margin-top:20px; box-shadow:0 2px 8px rgba(0,0,0,0.08);">
                    <h4 style="margin:0 0 14px 0; font-weight:700; color:#1f2937;"><i class="fa fa-credit-card" style="color:#667eea;"></i> Collections by Payment Method</h4>
                    <div class="row">
                        <?php foreach ($by_method as $method => $total):
                            $pct = $total_collected > 0 ? round($total / $total_collected * 100, 1) : 0; ?>
                        <div class="col-md-3 col-sm-6" style="margin-bottom:12px;">
                            <div style="border:1px solid #e5e7eb; border-radius:10px; padding:14px 16px;">
                                <div style="font-size:12.5px; font-weight:700; color:#374151;"><?php echo htmlspecialchars($method); ?></div>
                                <div style="font-size:19px; font-weight:800; color:#4338ca; margin-top:4px;"><?php echo $currency; ?> <?php echo number_format($total, 2); ?></div>
                                <div style="font-size:11.5px; color:#6b7280;"><?php echo $pct; ?>% of collections</div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <div style="margin-top:18px; text-align:center; font-size:11.5px; color:#6b7280;">
                    Generated on <?php echo date('d M Y, h:i A'); ?>
                </div>
            </div>
        </div>

        <?php include 'includes/includes_bottom.php'; ?>
        <script>
        jQuery(document).ready(function() {
            jQuery('.datepicker').datepicker({ format: 'dd-mm-yyyy' });
        });
        function statementNav() {
            var d1 = jQuery('#stStart').val() || '<?php echo date('d-m-Y', $start); ?>';
            var d2 = jQuery('#stEnd').val() || '<?php echo date('d-m-Y', $end); ?>';
            window.location = '<?php echo site_url('admin/financial_reports/statement'); ?>/' + d1 + '/' + d2;
        }
        function PrintElem(elem) {
            window.print();
        }
        </script>
    </body>
</html>
