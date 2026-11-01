<?php
$invoice_data = $this->db->get_where('invoice', array('invoice_code' => $param2));
$edit_data = $invoice_data->result_array();
$currency_row = $this->db->get_where('settings', array('type' => 'currency'))->row();
$currency = $currency_row ? $currency_row->description : 'GHS';
$system_name_row = $this->db->get_where('settings', array('type' => 'system_name'))->row();
$system_name = $system_name_row ? $system_name_row->description : 'School Management System';
$theme_color_row = $this->db->get_where('settings', array('type' => 'theme_color'))->row();
$theme_color = $theme_color_row ? $theme_color_row->description : '667eea';

// Ensure theme color has # prefix
if(strpos($theme_color, '#') !== 0) {
    $theme_color = '#' . $theme_color;
}

// Create gradient colors from theme color
function adjustBrightness($hex, $steps) {
    $hex = str_replace('#', '', $hex);
    if(strlen($hex) == 3) {
        $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
    }
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));
    $r = max(0, min(255, $r + $steps));
    $g = max(0, min(255, $g + $steps));
    $b = max(0, min(255, $b + $steps));
    return '#' . str_pad(dechex($r), 2, '0', STR_PAD_LEFT) . str_pad(dechex($g), 2, '0', STR_PAD_LEFT) . str_pad(dechex($b), 2, '0', STR_PAD_LEFT);
}
$gradient_light = adjustBrightness($theme_color, 20);
$gradient_dark = adjustBrightness($theme_color, -30);

$fmt = new NumberFormatter('en_US', NumberFormatter::CURRENCY);

$first_invoice = $invoice_data->row();
$student_id = $first_invoice->student_id;
$param3 = $first_invoice->year;
$param4 = isset($first_invoice->sem) ? $first_invoice->sem : $first_invoice->term;
$inv_status = ucwords($first_invoice->status);

$student = $this->db->get_where('student', array('student_id' => $student_id))->row();
$this->db->order_by('enroll_id', 'desc');
$this->db->limit(1);
$enroll_data = $this->db->get_where('enroll', array('student_id' => $student_id))->row();
$class_id = $enroll_data->class_id;
$section_id = $enroll_data->section_id;
$class = $this->db->get_where('class', array('class_id' => $class_id))->row();
$section = $this->db->get_where('section', array('section_id' => $section_id))->row();

// Check for discounts
$discount_query = $this->db->get_where('invoice_discounts', array(
    'invoice_code' => $param2,
    'status' => 'approved'
));
$total_discount = 0;
$discount_details = array();
$discount_items_map = array();
if($discount_query->num_rows() > 0) {
    foreach($discount_query->result_array() as $disc) {
        $total_discount += round((float)$disc['discount_amount'], 2);
        // Get profile to check bill_item_ids
        $profile = $this->db->where('profile_id', $disc['profile_id'])->get('discount_profiles')->row();
        $applies_to = 'All Bill Items';
        if($profile && $profile->bill_item_ids !== '*') {
            $bill_item_ids = explode(',', $profile->bill_item_ids);
            $bill_items = $this->db->where_in('id', $bill_item_ids)->get('bill_item')->result_array();
            $applies_to = implode(', ', array_column($bill_items, 'title'));
        }
        $disc['applies_to'] = $applies_to;
        $discount_details[] = $disc;
        // Get per-item discount details
        $disc_items = $this->db->where('discount_id', $disc['discount_id'])->get('invoice_discount_items')->result_array();
        foreach($disc_items as $ditem) {
            $discount_items_map[$ditem['item_title']] = $ditem;
        }
    }
    // Round total discount to avoid precision errors
    $total_discount = round($total_discount, 2);
}
?>

<style>
.invoice-professional {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: #f8f9fa;
    padding: 0;
    min-height: 100vh;
    display: flex;
    flex-direction: column;
}
.invoice-container {
    max-width: 580px;
    margin: 0 auto;
    background: white;
    box-shadow: 0 0 20px rgba(0,0,0,0.1);
    border-radius: 0;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    min-height: 100vh;
}
.invoice-header {
    background: linear-gradient(135deg, <?php echo $gradient_light; ?> 0%, <?php echo $gradient_dark; ?> 100%);
    color: white;
    padding: 8px;
    position: relative;
    flex-shrink: 0;
}
.invoice-header::after {
    content: '';
    position: absolute;
    bottom: -10px;
    left: 0;
    right: 0;
    height: 10px;
    background: white;
    border-radius: 10px 10px 0 0;
}
.school-logo {
    max-height: 40px;
    background: white;
    padding: 4px;
    border-radius: 50%;
}
.invoice-title {
    font-size: 20px;
    font-weight: 700;
    margin: 0;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.invoice-meta {
    display: flex;
    justify-content: space-between;
    padding: 10px 15px;
    background: #f8f9fa;
    border-bottom: 2px solid <?php echo $theme_color; ?>;
}
.meta-box {
    flex: 1;
}
.meta-label {
    font-size: 9px;
    text-transform: uppercase;
    color: #6c757d;
    font-weight: 600;
    letter-spacing: 0.5px;
    margin-bottom: 2px;
}
.meta-value {
    font-size: 11px;
    font-weight: 600;
    color: #212529;
}
.invoice-body {
    padding: 8px;
    flex: 1;
    overflow: auto;
}
.party-details {
    display: flex;
    justify-content: space-between;
    margin-bottom: 20px;
}
.party-box {
    flex: 1;
    padding: 5px 8px;
    background: #f8f9fa;
    border-radius: 4px;
    margin: 0 4px;
}
.party-box:first-child {
    margin-left: 0;
}
.party-box:last-child {
    margin-right: 0;
}
.party-title {
    font-size: 11px;
    text-transform: uppercase;
    color: <?php echo $theme_color; ?>;
    font-weight: 700;
    margin-bottom: 2px;
    letter-spacing: 0.3px;
}
.party-info {
    font-size: 13px;
    line-height: 1.4;
    color: #495057;
}
.party-info strong {
    color: #212529;
    display: block;
    font-size: 14px;
    margin-bottom: 1px;
}
.invoice-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    margin-top: 20px;
    margin-bottom: 6px;
}
.invoice-table thead th {
    background: linear-gradient(135deg, <?php echo $gradient_light; ?> 0%, <?php echo $gradient_dark; ?> 100%);
    color: white;
    padding: 8px;
    text-align: left;
    font-size: 13px;
    text-transform: uppercase;
    letter-spacing: 0.3px;
    font-weight: 600;
}
.invoice-table thead th:first-child {
    border-radius: 6px 0 0 0;
}
.invoice-table thead th:last-child {
    border-radius: 0 6px 0 0;
    text-align: right;
}
.invoice-table tbody td {
    padding: 8px;
    border-bottom: 1px solid #e9ecef;
    font-size: 14px;
}
.invoice-table tbody tr:hover {
    background: #f8f9fa;
}
.invoice-table tfoot td {
    padding: 8px;
    font-weight: 600;
    font-size: 15px;
}
.total-row {
    background: #f8f9fa;
    border-top: 2px solid <?php echo $theme_color; ?>;
}
.discount-row {
    color: #28a745;
}
.final-total-row {
    background: linear-gradient(135deg, <?php echo $gradient_light; ?> 0%, <?php echo $gradient_dark; ?> 100%);
    color: white;
    font-size: 16px;
}
.status-badge {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 50px;
    font-size: 9px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.status-paid {
    background: #d4edda;
    color: #155724;
}
.status-unpaid {
    background: #f8d7da;
    color: #721c24;
}
.status-partial {
    background: #fff3cd;
    color: #856404;
}
.invoice-footer {
    padding: 10px 15px;
    background: #f8f9fa;
    border-top: 2px solid <?php echo $theme_color; ?>;
    text-align: center;
    flex-shrink: 0;
}
.print-btn {
    background: linear-gradient(135deg, <?php echo $gradient_light; ?> 0%, <?php echo $gradient_dark; ?> 100%);
    color: white;
    border: none;
    padding: 8px 20px;
    border-radius: 50px;
    font-weight: 600;
    cursor: pointer;
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 1px;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
    transition: all 0.3s;
}
.print-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
}
.discount-info {
    background: #d4edda;
    border-left: 3px solid #28a745;
    padding: 8px;
    margin: 20px 0;
    border-radius: 4px;
}
.discount-info-title {
    font-weight: 700;
    color: #155724;
    margin-bottom: 6px;
    font-size: 14px;
}
.discount-item {
    font-size: 13px;
    color: #155724;
    margin: 3px 0;
}
@media print {
    @page {
        size: A4;
        margin: 10mm;
    }
    body > *:not(.modal) {
        display: none !important;
    }
    .modal-backdrop {
        display: none !important;
    }
    .modal {
        display: block !important;
        position: static !important;
    }
    .modal-dialog {
        margin: 0 !important;
        max-width: 100% !important;
        width: 100% !important;
    }
    .modal-content {
        border: none !important;
        box-shadow: none !important;
    }
    .modal-body {
        padding: 0 !important;
    }
    .modal-header, .modal-footer {
        display: none !important;
    }
    .invoice-professional {
        display: block !important;
        padding: 0 !important;
        background: white;
    }
    .hidden-print, .print-btn {
        display: none !important;
    }
    .invoice-container {
        display: block !important;
        box-shadow: none;
        border-radius: 0;
        overflow: visible !important;
        page-break-inside: avoid;
        transform-origin: top left;
    }
    /* Dynamic scaling for many items */
    @supports (zoom: 1) {
        .invoice-container {
            zoom: 0.95;
        }
    }
    @supports not (zoom: 1) {
        .invoice-container {
            transform: scale(0.95);
        }
    }
    .invoice-header,
    .invoice-meta,
    .invoice-body,
    .invoice-footer,
    .school-logo,
    .party-details,
    .party-box,
    .discount-info,
    .invoice-table,
    .signature-section {
        display: block !important;
        visibility: visible !important;
    }
    .invoice-header {
        background: <?php echo $theme_color; ?> !important;
        background: linear-gradient(135deg, <?php echo $gradient_light; ?> 0%, <?php echo $gradient_dark; ?> 100%) !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
        color: white !important;
        padding: 10px !important;
    }
    .invoice-header::after {
        display: none !important;
    }
    .invoice-meta {
        display: flex !important;
        padding: 8px 10px !important;
    }
    .invoice-body {
        padding: 10px !important;
    }
    .party-details {
        display: flex !important;
        margin-bottom: 20px !important;
    }
    .invoice-table {
        display: table !important;
        margin-top: 20px !important;
        margin-bottom: 8px !important;
    }
    .invoice-table thead,
    .invoice-table tbody,
    .invoice-table tfoot {
        display: table-row-group !important;
    }
    .invoice-table tr {
        display: table-row !important;
    }
    .invoice-table th,
    .invoice-table td {
        display: table-cell !important;
    }
    .invoice-table thead th {
        background: <?php echo $theme_color; ?> !important;
        background: linear-gradient(135deg, <?php echo $gradient_light; ?> 0%, <?php echo $gradient_dark; ?> 100%) !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
        color: white !important;
        padding: 8px !important;
        font-size: 13px !important;
    }
    .invoice-table tbody td {
        padding: 8px !important;
        font-size: 14px !important;
    }
    .invoice-table tbody td small {
        display: block !important;
        color: #6c757d !important;
        font-size: 12px !important;
    }
    .invoice-table tfoot td {
        padding: 8px !important;
        font-size: 15px !important;
    }
    .final-total-row {
        background: <?php echo $theme_color; ?> !important;
        background: linear-gradient(135deg, <?php echo $gradient_light; ?> 0%, <?php echo $gradient_dark; ?> 100%) !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
        color: white !important;
    }
    .invoice-footer {
        padding: 8px 10px !important;
        page-break-inside: avoid;
    }
    .signature-section {
        display: flex !important;
        margin: 10px 0 5px !important;
    }
    .discount-info {
        padding: 8px !important;
        margin: 10px 0 !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
}
</style>

<div class="invoice-professional">
    <div style="text-align: right; margin-bottom: 20px; display: flex; gap: 10px; justify-content: flex-end;" class="hidden-print">
        <button onclick="PrintInvoice()" class="print-btn">
            <i class="entypo-print"></i> Print Invoice
        </button>
        <button onclick="EmailInvoice('<?php echo $param2; ?>')" class="print-btn" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
            <i class="entypo-mail"></i> Email Invoice
        </button>
        <button onclick="SmsInvoice('<?php echo $param2; ?>')" class="print-btn" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);">
            <i class="entypo-mobile"></i> SMS Invoice
        </button>
    </div>

    <div class="invoice-container" id="printable-invoice">
        <!-- Header -->
        <div class="invoice-header">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <img src="<?php echo base_url(); ?>uploads/school_logo.png" class="school-logo" alt="School Logo">
                </div>
                <div style="text-align: right;">
                    <h1 class="invoice-title" style="color: white;">Invoice</h1>
                    <div style="font-size: 18px; margin-top: 5px; opacity: 0.9; color: white;">
                        #<?php echo $param2; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Meta Information -->
        <div class="invoice-meta">
            <div class="meta-box">
                <div class="meta-label">Invoice Date</div>
                <div class="meta-value"><?php echo date('d M, Y', $first_invoice->creation_timestamp); ?></div>
            </div>
            <div class="meta-box">
                <div class="meta-label">Academic Period</div>
                <div class="meta-value">
                    <?php echo $param3; ?> | 
                    <?php echo isset($first_invoice->sem) ? 'Sem ' . $param4 : 'Term ' . $param4; ?>
                </div>
            </div>
            <div class="meta-box">
                <div class="meta-label">Status</div>
                <div class="meta-value">
                    <span class="status-badge status-<?php echo strtolower($inv_status); ?>">
                        <?php echo $inv_status; ?>
                    </span>
                </div>
            </div>
            <div class="meta-box">
                <div class="meta-label">Date Printed</div>
                <div class="meta-value"><?php echo date('d M, Y H:i'); ?></div>
            </div>
        </div>

        <!-- Body -->
        <div class="invoice-body">
            <!-- Party Details -->
            <div class="party-details">
                <div class="party-box">
                    <div class="party-title">Billed To</div>
                    <div class="party-info">
                        <strong><?php echo $student->name; ?></strong>
                        ID: <?php echo $student->student_code; ?><br>
                        <?php echo $class->name . ' ' . $class->name_numeric . ' ' . $section->name; ?><?php if(!empty($student->phone)): ?><br>Phone: <?php echo $student->phone; ?><?php endif; ?>
                    </div>
                </div>
                <div class="party-box">
                    <div class="party-title">Payment To</div>
                    <div class="party-info">
                        <strong><?php echo $system_name; ?></strong>
                        <?php echo $this->db->get_where('settings', array('type' => 'location'))->row()->description; ?> | <?php echo $this->db->get_where('settings', array('type' => 'phone'))->row()->description; ?>
                    </div>
                </div>
            </div>

            <!-- Discount Information -->
            <?php if($total_discount > 0): ?>
            <div class="discount-info">
                <div class="discount-info-title">
                    <i class="entypo-tag"></i> Discount Applied
                </div>
                <?php foreach($discount_details as $disc): ?>
                <div class="discount-item">
                    • <?php echo isset($disc['discount_type']) ? ucwords(str_replace('_', ' ', $disc['discount_type'])) : 'Discount'; ?>: 
                    <?php echo $disc['discount_method'] == 'percentage' ? $disc['discount_value'] . '%' : numfmt_format_currency($fmt, $disc['discount_value'], $currency); ?>
                    = <?php echo numfmt_format_currency($fmt, $disc['discount_amount'], $currency); ?>
                    <br>&nbsp;&nbsp;<strong>Applies To:</strong> <?php echo $disc['applies_to']; ?>
                    <?php if(!empty($disc['reason'])): ?>
                    <br>&nbsp;&nbsp;Reason: <?php echo $disc['reason']; ?>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Invoice Items -->
            <table class="invoice-table">
                <thead>
                    <tr>
                        <th>Description</th>
                        <th style="text-align: right;">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $total_amount = 0;
                    $total_before_discount = 0;
                    foreach ($edit_data as $row):
                        // Round individual amounts to prevent precision errors
                        $row_amount = round((float)$row['amount'], 2);
                        $total_amount += $row_amount;
                        $item_discount = isset($discount_items_map[$row['title']]) ? round((float)$discount_items_map[$row['title']]['discount_amount'], 2) : 0;
                        $original_amount = round($row_amount + $item_discount, 2);
                        $total_before_discount += $original_amount;
                    ?>
                    <tr>
                        <td>
                            <strong><?php echo $row['title']; ?></strong>
                            <?php if(!empty($row['description'])): ?>
                            <br><small style="color: #6c757d; display: block;"><?php echo $row['description']; ?></small>
                            <?php endif; ?>
                        </td>
                        <td style="text-align: right;">
                            <?php echo numfmt_format_currency($fmt, $original_amount, $currency); ?>
                        </td>
                    </tr>
                    <?php endforeach; 
                    // Round final totals to avoid floating-point precision errors
                    $total_before_discount = round($total_before_discount, 2);
                    $total_amount = round($total_amount, 2);
                    ?>
                </tbody>
                <tfoot>
                    <?php if($total_discount > 0): ?>
                    <tr class="total-row">
                        <td>Current Term Total (Before Discount)</td>
                        <td style="text-align: right;">
                            <?php echo numfmt_format_currency($fmt, $total_before_discount, $currency); ?>
                        </td>
                    </tr>
                    <tr class="discount-row">
                        <td>Discount</td>
                        <td style="text-align: right;">
                            - <?php echo numfmt_format_currency($fmt, $total_discount, $currency); ?>
                        </td>
                    </tr>
                    <tr class="total-row">
                        <td>Current Term Total</td>
                        <td style="text-align: right;">
                            <?php echo numfmt_format_currency($fmt, $total_amount, $currency); ?>
                        </td>
                    </tr>
                    <?php else: ?>
                    <tr class="total-row">
                        <td>Total</td>
                        <td style="text-align: right;">
                            <?php echo numfmt_format_currency($fmt, $total_amount, $currency); ?>
                        </td>
                    </tr>
                    <?php endif; ?>
                    <tr class="final-total-row">
                        <td>GRAND TOTAL</td>
                        <td style="text-align: right;">
                            <?php echo numfmt_format_currency($fmt, $total_amount, $currency); ?>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- Footer -->
        <div class="invoice-footer">
            <span style="font-weight: 500; font-size: 14px;">Thank You For Choosing <?php echo ucwords(strtolower($system_name)); ?></span>
            <p style="color: #6c757d; margin-top: 10px; font-size: 11px;">
                This is a computer-generated invoice and does not require a physical signature.
            </p>
        </div>
    </div>
</div>

<script>
function PrintInvoice() {
    const invoiceCode = '<?php echo $param2; ?>';
    window.open('<?php echo site_url('admin/print_invoice/'); ?>' + invoiceCode, '_blank');
}

function EmailInvoice(invoice_code) {
    const modalContent = `
      <div style="padding: 15px;">
        <h5 style="margin-bottom: 15px; font-weight: 600; color: #374151; font-size: 15px;"><i class="fa fa-envelope"></i> Select Recipients</h5>
        <div style="display: flex; flex-direction: column; gap: 8px;">
          <label style="display: flex; align-items: center; padding: 8px; border: 2px solid #e5e7eb; border-radius: 6px; cursor: pointer;" onmouseover="this.style.borderColor='#3b82f6'" onmouseout="this.style.borderColor='#e5e7eb'">
            <input type="checkbox" value="student" class="email-recipient" style="width: 16px; height: 16px; margin-right: 10px;" checked>
            <span style="font-weight: 500; color: #1f2937; font-size: 14px;"><i class="fa fa-user-graduate" style="color: #3b82f6; margin-right: 6px;"></i>Student</span>
          </label>
          <label style="display: flex; align-items: center; padding: 8px; border: 2px solid #e5e7eb; border-radius: 6px; cursor: pointer;" onmouseover="this.style.borderColor='#3b82f6'" onmouseout="this.style.borderColor='#e5e7eb'">
            <input type="checkbox" value="guardian" class="email-recipient" style="width: 16px; height: 16px; margin-right: 10px;">
            <span style="font-weight: 500; color: #1f2937; font-size: 14px;"><i class="fa fa-user-shield" style="color: #10b981; margin-right: 6px;"></i>Guardian</span>
          </label>
          <label style="display: flex; align-items: center; padding: 8px; border: 2px solid #e5e7eb; border-radius: 6px; cursor: pointer;" onmouseover="this.style.borderColor='#3b82f6'" onmouseout="this.style.borderColor='#e5e7eb'">
            <input type="checkbox" value="father" class="email-recipient" style="width: 16px; height: 16px; margin-right: 10px;">
            <span style="font-weight: 500; color: #1f2937; font-size: 14px;"><i class="fa fa-male" style="color: #6366f1; margin-right: 6px;"></i>Father</span>
          </label>
          <label style="display: flex; align-items: center; padding: 8px; border: 2px solid #e5e7eb; border-radius: 6px; cursor: pointer;" onmouseover="this.style.borderColor='#3b82f6'" onmouseout="this.style.borderColor='#e5e7eb'">
            <input type="checkbox" value="mother" class="email-recipient" style="width: 16px; height: 16px; margin-right: 10px;">
            <span style="font-weight: 500; color: #1f2937; font-size: 14px;"><i class="fa fa-female" style="color: #ec4899; margin-right: 6px;"></i>Mother</span>
          </label>
        </div>
        <div style="margin-top: 15px; display: flex; gap: 8px; justify-content: flex-end;">
          <button type="button" onclick="$('.close').click()" style="padding: 8px 16px; border: 2px solid #d1d5db; background: white; color: #374151; border-radius: 6px; font-weight: 500; cursor: pointer; font-size: 13px;">
            Cancel
          </button>
          <button type="button" onclick="sendInvoiceEmailModal('${invoice_code}')" style="padding: 8px 16px; border: none; background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; border-radius: 6px; font-weight: 500; cursor: pointer; font-size: 13px;">
            <i class="fa fa-paper-plane"></i> Send
          </button>
        </div>
      </div>
    `;
    showModalWithContent('modal_ajax', '<i class="fa fa-envelope"></i> Email Invoice', modalContent);
}

function sendInvoiceEmailModal(invoice_code) {
    const recipients = [];
    $('.email-recipient:checked').each(function() {
        recipients.push($(this).val());
    });

    if(recipients.length === 0) {
        showAjaxModal_alert('Please select at least one recipient', 'error');
        return;
    }

    $('.close').click();
    showAjaxModal_alert('Sending email...', 'loading');
    
    $.ajax({
        url: '<?php echo site_url('invoice_email/send/'); ?>' + invoice_code,
        type: 'POST',
        data: { recipients: recipients },
        dataType: 'json'
    }).done(function(response) {
        if(response.status === 'success') {
            showAjaxModal_alert(response.message, 'success');
        } else {
            showAjaxModal_alert(response.message, 'error');
        }
    }).fail(function() {
        showAjaxModal_alert('Failed to send email', 'error');
    });
}

function SmsInvoice(invoice_code) {
    const modalContent = `
      <div style="padding: 15px;">
        <h5 style="margin-bottom: 15px; font-weight: 600; color: #374151; font-size: 15px;"><i class="fa fa-mobile"></i> Select Recipients</h5>
        <div style="display: flex; flex-direction: column; gap: 8px;">
          <label style="display: flex; align-items: center; padding: 8px; border: 2px solid #e5e7eb; border-radius: 6px; cursor: pointer;" onmouseover="this.style.borderColor='#f59e0b'" onmouseout="this.style.borderColor='#e5e7eb'">
            <input type="checkbox" value="student" class="sms-recipient" style="width: 16px; height: 16px; margin-right: 10px;" checked>
            <span style="font-weight: 500; color: #1f2937; font-size: 14px;"><i class="fa fa-user-graduate" style="color: #3b82f6; margin-right: 6px;"></i>Student</span>
          </label>
          <label style="display: flex; align-items: center; padding: 8px; border: 2px solid #e5e7eb; border-radius: 6px; cursor: pointer;" onmouseover="this.style.borderColor='#f59e0b'" onmouseout="this.style.borderColor='#e5e7eb'">
            <input type="checkbox" value="guardian" class="sms-recipient" style="width: 16px; height: 16px; margin-right: 10px;">
            <span style="font-weight: 500; color: #1f2937; font-size: 14px;"><i class="fa fa-user-shield" style="color: #10b981; margin-right: 6px;"></i>Guardian</span>
          </label>
          <label style="display: flex; align-items: center; padding: 8px; border: 2px solid #e5e7eb; border-radius: 6px; cursor: pointer;" onmouseover="this.style.borderColor='#f59e0b'" onmouseout="this.style.borderColor='#e5e7eb'">
            <input type="checkbox" value="father" class="sms-recipient" style="width: 16px; height: 16px; margin-right: 10px;">
            <span style="font-weight: 500; color: #1f2937; font-size: 14px;"><i class="fa fa-male" style="color: #6366f1; margin-right: 6px;"></i>Father</span>
          </label>
          <label style="display: flex; align-items: center; padding: 8px; border: 2px solid #e5e7eb; border-radius: 6px; cursor: pointer;" onmouseover="this.style.borderColor='#f59e0b'" onmouseout="this.style.borderColor='#e5e7eb'">
            <input type="checkbox" value="mother" class="sms-recipient" style="width: 16px; height: 16px; margin-right: 10px;">
            <span style="font-weight: 500; color: #1f2937; font-size: 14px;"><i class="fa fa-female" style="color: #ec4899; margin-right: 6px;"></i>Mother</span>
          </label>
        </div>
        <div style="margin-top: 15px; display: flex; gap: 8px; justify-content: flex-end;">
          <button type="button" onclick="$('.close').click()" style="padding: 8px 16px; border: 2px solid #d1d5db; background: white; color: #374151; border-radius: 6px; font-weight: 500; cursor: pointer; font-size: 13px;">
            Cancel
          </button>
          <button type="button" onclick="sendInvoiceSms('${invoice_code}')" style="padding: 8px 16px; border: none; background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white; border-radius: 6px; font-weight: 500; cursor: pointer; font-size: 13px;">
            <i class="fa fa-paper-plane"></i> Send
          </button>
        </div>
      </div>
    `;
    showModalWithContent('modal_ajax', '<i class="fa fa-mobile"></i> SMS Invoice', modalContent);
}

function sendInvoiceSms(invoice_code) {
    const recipients = [];
    $('.sms-recipient:checked').each(function() {
        recipients.push($(this).val());
    });

    if(recipients.length === 0) {
        showAjaxModal_alert('Please select at least one recipient', 'error');
        return;
    }

    $('.close').click();
    showAjaxModal_alert('Sending SMS...', 'loading');
    
    $.ajax({
        url: '<?php echo site_url('invoice_sms/send/'); ?>' + invoice_code,
        type: 'POST',
        data: { recipients: recipients },
        dataType: 'json'
    }).done(function(response) {
        if(response.status === 'success') {
            showAjaxModal_alert(response.message, 'success');
        } else {
            showAjaxModal_alert(response.message, 'error');
        }
    }).fail(function() {
        showAjaxModal_alert('Failed to send SMS', 'error');
    });
}
</script>
