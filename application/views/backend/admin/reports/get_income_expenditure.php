<?php
// Currency
$currency = $this->db->get_where('settings', array('type' => 'currency'))->row()->description;
$fmt = new NumberFormatter('en_US', NumberFormatter::CURRENCY);

// ============================================
// INCOME SECTION
// ============================================

// 1. BILLED INVOICES INCOME (payments with invoice_id or invoice_code)
$this->db->select_sum('amount');
$this->db->where('day_timestamp BETWEEN "'. $start_date .'" AND "'. $end_date. '"');
$this->db->where('(invoice_id IS NOT NULL OR invoice_code IS NOT NULL OR invoice_code != "")');
$billed_invoice_income = $this->db->get('payment')->row()->amount ?? 0;

// 2. DAILY FEES INCOME
$this->db->select('
    SUM(feeding_amount) as feeding,
    SUM(breakfast_amount) as breakfast,
    SUM(classes_amount) as classes,
    SUM(water_amount) as water,
    SUM(transport_amount) as transport
');
$this->db->where('payment_date >=', $start_date);
$this->db->where('payment_date <=', $end_date);
$daily_fees = $this->db->get('daily_fee_transactions')->row();

$feeding_income = $daily_fees->feeding ?? 0;
$breakfast_income = $daily_fees->breakfast ?? 0;
$classes_income = $daily_fees->classes ?? 0;
$water_income = $daily_fees->water ?? 0;
$transport_income = $daily_fees->transport ?? 0;
$total_daily_fees = $feeding_income + $breakfast_income + $classes_income + $water_income + $transport_income;

// 3. OTHER INCOME (payments without invoice_id and invoice_code)
$this->db->select_sum('amount');
$this->db->where('payment_type', 'income');
$this->db->where('can_delete !=', 'trash');
$this->db->where('day_timestamp BETWEEN "'. $start_date .'" AND "'. $end_date. '"');
$this->db->where('(invoice_id IS NULL OR invoice_id = "" OR invoice_id = 0)');
$this->db->where('(invoice_code IS NULL OR invoice_code = "")');
$other_income = $this->db->get('payment')->row()->amount ?? 0;

// 4. INVENTORY SALES INCOME
$CI = &get_instance();
$CI->load->model('Inventory_model');
// Convert Unix timestamps to Y-m-d format for inventory query
$inventory_start_date = date('Y-m-d', $start_date);
$inventory_end_date = date('Y-m-d', $end_date);
$inventory_sales_income = $CI->Inventory_model->get_total_sales_revenue($inventory_start_date, $inventory_end_date);

$total_income = $billed_invoice_income + $total_daily_fees + $other_income + $inventory_sales_income;

// ============================================
// EXPENDITURE SECTION
// ============================================
$this->db->select('title');
$this->db->distinct();
$this->db->where('payment_type', 'expense');
$this->db->where('can_delete !=', 'trash');
$this->db->where('day_timestamp BETWEEN "'. $start_date .'" AND "'. $end_date. '"');
$expenditure_items = $this->db->get('payment')->result_array();

$total_expenditure = 0;
?>

<!-- INCOME SECTION -->
<tr style="background:#f3f4f6;">
    <td colspan="3"><strong style="font-size:16px; color:#1f2937;">INCOME</strong></td>
</tr>

<!-- A. Billed Invoices -->
<tr style="background:#e0f2fe;">
    <td colspan="3"><strong style="padding-left:20px;">A. Billed Invoices (School Fees, PTA, etc.)</strong></td>
</tr>
<tr>
    <td style="padding-left:40px;">Invoice Payments Received</td>
    <td style="text-align:right;"><?= number_format($billed_invoice_income, 2, '.', ','); ?></td>
    <td></td>
</tr>
<tr style="background:#f9fafb;">
    <td colspan="2" style="text-align:right; padding-right:20px;"><strong>Sub-Total (A)</strong></td>
    <td style="text-align:right;"><strong><?= number_format($billed_invoice_income, 2, '.', ','); ?></strong></td>
</tr>

<!-- B. Daily Fees Collection -->
<tr style="background:#e0f2fe;">
    <td colspan="3"><strong style="padding-left:20px;">B. Daily Fees Collection</strong></td>
</tr>
<?php if($feeding_income > 0): ?>
<tr>
    <td style="padding-left:40px;">Feeding Fees</td>
    <td style="text-align:right;"><?= number_format($feeding_income, 2, '.', ','); ?></td>
    <td></td>
</tr>
<?php endif; ?>
<?php if($breakfast_income > 0): ?>
<tr>
    <td style="padding-left:40px;">Breakfast Fees</td>
    <td style="text-align:right;"><?= number_format($breakfast_income, 2, '.', ','); ?></td>
    <td></td>
</tr>
<?php endif; ?>
<?php if($classes_income > 0): ?>
<tr>
    <td style="padding-left:40px;">Classes Fees</td>
    <td style="text-align:right;"><?= number_format($classes_income, 2, '.', ','); ?></td>
    <td></td>
</tr>
<?php endif; ?>
<?php if($water_income > 0): ?>
<tr>
    <td style="padding-left:40px;">Water Fees</td>
    <td style="text-align:right;"><?= number_format($water_income, 2, '.', ','); ?></td>
    <td></td>
</tr>
<?php endif; ?>
<?php if($transport_income > 0): ?>
<tr>
    <td style="padding-left:40px;">Transport Fees</td>
    <td style="text-align:right;"><?= number_format($transport_income, 2, '.', ','); ?></td>
    <td></td>
</tr>
<?php endif; ?>
<tr style="background:#f9fafb;">
    <td colspan="2" style="text-align:right; padding-right:20px;"><strong>Sub-Total (B)</strong></td>
    <td style="text-align:right;"><strong><?= number_format($total_daily_fees, 2, '.', ','); ?></strong></td>
</tr>

<!-- C. Other Income -->
<?php if($other_income > 0): ?>
<tr style="background:#e0f2fe;">
    <td colspan="3"><strong style="padding-left:20px;">C. Other Income</strong></td>
</tr>
<tr>
    <td style="padding-left:40px;">Miscellaneous Income</td>
    <td style="text-align:right;"><?= number_format($other_income, 2, '.', ','); ?></td>
    <td></td>
</tr>
<tr style="background:#f9fafb;">
    <td colspan="2" style="text-align:right; padding-right:20px;"><strong>Sub-Total (C)</strong></td>
    <td style="text-align:right;"><strong><?= number_format($other_income, 2, '.', ','); ?></strong></td>
</tr>
<?php endif; ?>

<!-- D. Inventory Sales -->
<?php if($inventory_sales_income > 0): ?>
<tr style="background:#e0f2fe;">
    <td colspan="3"><strong style="padding-left:20px;">D. Inventory Sales</strong></td>
</tr>
<tr>
    <td style="padding-left:40px;">Products/Items Sold</td>
    <td style="text-align:right;"><?= number_format($inventory_sales_income, 2, '.', ','); ?></td>
    <td></td>
</tr>
<tr style="background:#f9fafb;">
    <td colspan="2" style="text-align:right; padding-right:20px;"><strong>Sub-Total (D)</strong></td>
    <td style="text-align:right;"><strong><?= number_format($inventory_sales_income, 2, '.', ','); ?></strong></td>
</tr>
<?php endif; ?>

<!-- TOTAL INCOME -->
<tr style="background:#dbeafe; border-top:2px solid #3b82f6;">
    <td colspan="2" style="text-align:right; padding-right:20px;"><strong style="font-size:15px;">TOTAL INCOME</strong></td>
    <td style="text-align:right;"><strong style="font-size:15px;"><?= numfmt_format_currency($fmt, $total_income, $currency); ?></strong></td>
</tr>

<tr><td colspan="3" style="height:40px;"></td></tr>

<!-- EXPENDITURE SECTION -->
<tr style="background:#f3f4f6;">
    <td colspan="3"><strong style="font-size:16px; color:#1f2937;">EXPENDITURE</strong></td>
</tr>

<?php
if(count($expenditure_items) > 0) {
    foreach($expenditure_items as $item):
        $this->db->select_sum('amount');
        $this->db->where('title', $item['title']);
        $this->db->where('payment_type', 'expense');
        $this->db->where('can_delete !=', 'trash');
        $this->db->where('day_timestamp BETWEEN "'. $start_date .'" AND "'. $end_date. '"');
        $amount = $this->db->get('payment')->row()->amount ?? 0;
        $total_expenditure += $amount;
?>
<tr>
    <td style="padding-left:20px;"><?= strtoupper($item['title']); ?></td>
    <td style="text-align:right;"><?= number_format($amount, 2, '.', ','); ?></td>
    <td></td>
</tr>
<?php
    endforeach;
} else {
?>
<tr>
    <td colspan="3" style="text-align:center; color:#ef4444;">No expenditure found</td>
</tr>
<?php
}
?>

<!-- TOTAL EXPENDITURE -->
<tr style="background:#fee2e2; border-top:2px solid #ef4444;">
    <td colspan="2" style="text-align:right; padding-right:20px;"><strong style="font-size:15px;">TOTAL EXPENDITURE</strong></td>
    <td style="text-align:right;"><strong style="font-size:15px;"><?= numfmt_format_currency($fmt, $total_expenditure, $currency); ?></strong></td>
</tr>

<tr><td colspan="3" style="height:40px;"></td></tr>

<!-- PROFIT/LOSS -->
<?php
$profit_loss = $total_income - $total_expenditure;
$bg_color = $profit_loss > 0 ? '#d1fae5' : ($profit_loss < 0 ? '#fee2e2' : '#f3f4f6');
$label = $profit_loss > 0 ? 'PROFIT' : ($profit_loss < 0 ? 'LOSS' : 'BREAK-EVEN');
?>
<tr style="background:<?= $bg_color; ?>; border-top:3px double #000;">
    <td colspan="2" style="text-align:right; padding-right:20px;"><strong style="font-size:16px;"><?= $label; ?></strong></td>
    <td style="text-align:right;"><strong style="font-size:16px;"><?= $profit_loss < 0 ? '(' : ''; ?><?= numfmt_format_currency($fmt, abs($profit_loss), $currency); ?><?= $profit_loss < 0 ? ')' : ''; ?></strong></td>
</tr>
