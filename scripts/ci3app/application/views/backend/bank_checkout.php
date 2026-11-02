<?php
/**
 * Bank Checkout — parents portal standalone page
 * Data: $student_details (student row), $invoice_details (invoice row)
 * Shows bank account details and payment instructions for the selected invoice.
 */
$school_row = $this->db->get_where('settings', array('type' => 'system_name'))->row();
$school_name = $school_row ? $school_row->description : 'School';
$currency_row = $this->db->get_where('settings', array('type' => 'currency'))->row();
$currency = $currency_row ? $currency_row->description : 'GHS';

$banks = $this->db->get_where('accounts', array('account_is_bank' => 1))->result_array();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Bank Payment | <?php echo htmlspecialchars($school_name); ?></title>
    <link rel="stylesheet" href="<?php echo base_url('assets/login_page/css/font-awesome.min.css'); ?>">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, sans-serif; font-size: 14px; color: #1f2937; background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%); min-height: 100vh; padding: 30px 16px; }
        .card { max-width: 640px; margin: 0 auto; background: #fff; border-radius: 16px; box-shadow: 0 20px 50px rgba(0,0,0,.3); overflow: hidden; }
        .head { background: linear-gradient(135deg, #111827, #1f2937); color: #fff; padding: 24px 28px; text-align: center; }
        .head img { max-height: 56px; margin-bottom: 8px; }
        .head h1 { font-size: 20px; }
        .head p { font-size: 12.5px; opacity: .85; margin-top: 4px; }
        .body { padding: 26px 28px; }
        .inv { background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 12px; padding: 16px 18px; margin-bottom: 20px; }
        .inv .row { display: flex; justify-content: space-between; padding: 5px 0; font-size: 13.5px; }
        .inv .row strong { color: #1e3a8a; }
        .amt { text-align: center; margin: 18px 0 22px; }
        .amt .l { font-size: 12px; text-transform: uppercase; letter-spacing: 1.2px; color: #6b7280; font-weight: 700; }
        .amt .v { font-size: 34px; font-weight: 800; color: #1e3a8a; }
        h3.sec { font-size: 13px; text-transform: uppercase; letter-spacing: 1px; color: #1e3a8a; margin: 0 0 10px; }
        .bank { border: 1.5px solid #e5e7eb; border-radius: 12px; padding: 14px 18px; margin-bottom: 12px; display: flex; justify-content: space-between; align-items: center; }
        .bank .nm { font-weight: 800; font-size: 15px; color: #111827; }
        .bank .no { font-family: 'Courier New', monospace; font-weight: 700; font-size: 15px; color: #1e3a8a; }
        .steps { background: #f9fafb; border: 1.5px solid #e5e7eb; border-radius: 12px; padding: 16px 18px; }
        .steps ol { margin-left: 20px; }
        .steps li { padding: 4px 0; font-size: 13.5px; line-height: 1.55; }
        .foot { text-align: center; padding: 18px; border-top: 2px dashed #e5e7eb; }
        .foot a { display: inline-flex; align-items: center; gap: 8px; background: #111827; color: #fff; text-decoration: none; border-radius: 10px; padding: 11px 24px; font-weight: 700; font-size: 13.5px; }
        .empty-bank { text-align: center; padding: 26px; color: #b91c1c; font-weight: 600; background: #fef2f2; border: 1px solid #fecaca; border-radius: 12px; }
    </style>
</head>
<body>
    <div class="card">
        <div class="head">
            <img src="<?php echo base_url('uploads/school_logo.png'); ?>" alt="" onerror="this.style.display='none'">
            <h1><i class="fa fa-university"></i> Bank Payment</h1>
            <p><?php echo htmlspecialchars($school_name); ?></p>
        </div>
        <div class="body">
            <?php if (!empty($invoice_details)): ?>
            <div class="inv">
                <div class="row"><span>Invoice</span><strong>#<?php echo htmlspecialchars($invoice_details->invoice_code); ?></strong></div>
                <div class="row"><span>Title</span><strong><?php echo htmlspecialchars($invoice_details->title); ?></strong></div>
                <div class="row"><span>Student</span><strong><?php echo htmlspecialchars($student_details->name ?? '—'); ?></strong></div>
                <div class="row"><span>Term / Year</span><strong><?php echo htmlspecialchars(($invoice_details->term ?? '—') . ' / ' . ($invoice_details->year ?? '—')); ?></strong></div>
            </div>
            <div class="amt">
                <div class="l">Amount Due</div>
                <div class="v"><?php echo $currency; ?> <?php echo number_format((float)($invoice_details->due ?? 0), 2); ?></div>
            </div>
            <?php else: ?>
            <div class="empty-bank" style="margin-bottom:18px;"><i class="fa fa-exclamation-triangle"></i> No invoice was selected.</div>
            <?php endif; ?>

            <h3 class="sec"><i class="fa fa-university"></i> Our Bank Accounts</h3>
            <?php if (empty($banks)): ?>
            <div class="empty-bank">No bank accounts are configured yet. Please contact the school accounts office for payment details.</div>
            <?php else: foreach ($banks as $b): ?>
            <div class="bank">
                <div class="nm"><i class="fa fa-piggy-bank"></i> <?php echo htmlspecialchars($b['account_name']); ?></div>
                <div class="no"><?php echo htmlspecialchars($b['account_number'] ?? '—'); ?></div>
            </div>
            <?php endforeach; endif; ?>

            <h3 class="sec" style="margin-top:20px;"><i class="fa fa-list-ol"></i> How to Pay</h3>
            <div class="steps">
                <ol>
                    <li>Transfer or deposit the <strong>exact amount due</strong> to any of the bank accounts above.</li>
                    <li>Use the <strong>invoice number</strong> as the payment reference.</li>
                    <li>Keep your payment receipt / transfer confirmation.</li>
                    <li>Send the confirmation to the school accounts office to have your payment approved and your receipt issued.</li>
                </ol>
            </div>
        </div>
        <div class="foot">
            <a href="javascript:history.back()"><i class="fa fa-arrow-left"></i> Back to Invoice</a>
        </div>
    </div>
</body>
</html>
