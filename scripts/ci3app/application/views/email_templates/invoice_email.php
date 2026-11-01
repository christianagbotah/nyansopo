<?php
$invoice_data = $this->db->get_where('invoice', ['invoice_code' => $invoice_code]);
$edit_data = $invoice_data->result_array();
$currency = $this->db->get_where('settings', ['type' => 'currency'])->row()->description;
$system_name = $this->db->get_where('settings', ['type' => 'system_name'])->row()->description;
$fmt = new NumberFormatter('en_US', NumberFormatter::CURRENCY);

$first_invoice = $invoice_data->row();
$student_id = $first_invoice->student_id;
$student = $this->db->get_where('student', ['student_id' => $student_id])->row();

// Check for discounts
$discount_query = $this->db->get_where('invoice_discounts', [
    'invoice_code' => $invoice_code,
    'status' => 'approved'
]);
$total_discount = 0;
if($discount_query->num_rows() > 0) {
    foreach($discount_query->result_array() as $disc) {
        $total_discount += $disc['discount_amount'];
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; }
        .content { background: #f8f9fa; padding: 20px; }
        .invoice-details { background: white; padding: 20px; margin: 20px 0; border-radius: 8px; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #667eea; color: white; }
        .total { font-weight: bold; font-size: 18px; }
        .discount { color: #28a745; }
        .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>INVOICE</h1>
            <p>#<?php echo $invoice_code; ?></p>
        </div>
        
        <div class="content">
            <p>Dear <?php echo $student->name; ?>,</p>
            <p>Please find your invoice details below:</p>
            
            <div class="invoice-details">
                <p><strong>Invoice Date:</strong> <?php echo date('d M, Y', $first_invoice->creation_timestamp); ?></p>
                <p><strong>Status:</strong> <?php echo ucwords($first_invoice->status); ?></p>
                
                <table>
                    <thead>
                        <tr>
                            <th>Description</th>
                            <th style="text-align: right;">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $total_amount = 0;
                        foreach ($edit_data as $row):
                            $total_amount += $row['amount'];
                        ?>
                        <tr>
                            <td><?php echo $row['title']; ?></td>
                            <td style="text-align: right;"><?php echo numfmt_format_currency($fmt, $row['amount'], $currency); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td><strong>Subtotal</strong></td>
                            <td style="text-align: right;"><strong><?php echo numfmt_format_currency($fmt, $total_amount, $currency); ?></strong></td>
                        </tr>
                        <?php if($total_discount > 0): ?>
                        <tr class="discount">
                            <td><strong>Discount</strong></td>
                            <td style="text-align: right;"><strong>- <?php echo numfmt_format_currency($fmt, $total_discount, $currency); ?></strong></td>
                        </tr>
                        <tr class="total">
                            <td>TOTAL AMOUNT DUE</td>
                            <td style="text-align: right;"><?php echo numfmt_format_currency($fmt, $total_amount - $total_discount, $currency); ?></td>
                        </tr>
                        <?php else: ?>
                        <tr class="total">
                            <td>TOTAL AMOUNT DUE</td>
                            <td style="text-align: right;"><?php echo numfmt_format_currency($fmt, $total_amount, $currency); ?></td>
                        </tr>
                        <?php endif; ?>
                    </tfoot>
                </table>
            </div>
            
            <p>Please make payment at your earliest convenience.</p>
            <p>For any questions, please contact our finance department.</p>
        </div>
        
        <div class="footer">
            <p><?php echo $system_name; ?></p>
            <p><?php echo $this->db->get_where('settings', ['type' => 'address'])->row()->description; ?></p>
            <p><?php echo $this->db->get_where('settings', ['type' => 'phone'])->row()->description; ?></p>
        </div>
    </div>
</body>
</html>
