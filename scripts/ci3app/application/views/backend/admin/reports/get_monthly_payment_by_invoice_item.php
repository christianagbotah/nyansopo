<?php
// Get filter parameters from POST request
$start_month = $this->input->post('start_month');
$end_month = $this->input->post('end_month');
$academic_year = $this->input->post('academic_year');

// Set execution time limit to 30 seconds for this query
// This prevents long-running queries from hanging indefinitely
set_time_limit(30);

// Get currency symbol from system settings
try {
    $currency = $this->db->get_where('settings', array('type' => 'currency'))->row()->description;
} catch (Exception $e) {
    log_message('error', 'Failed to retrieve currency setting: ' . $e->getMessage());
    $currency = 'GHS'; // Default fallback currency
}

// Build months array from start_month to end_month
$months = [];
for ($m = $start_month; $m <= $end_month; $m++) {
    $months[] = $m;
}

// Month names mapping
$month_names = [
    1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
    5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Aug',
    9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dec'
];

// SQL Query to aggregate payments by invoice item and month
$sql = "
SELECT 
    bi.title AS invoice_item,
    MONTH(FROM_UNIXTIME(p.day_timestamp)) AS payment_month,
    SUM(p.amount) AS total_amount
FROM payment p
INNER JOIN invoice i ON (
    (p.invoice_id IS NOT NULL AND p.invoice_id = i.invoice_id) 
    OR 
    (p.invoice_id IS NULL AND p.invoice_code IS NOT NULL AND p.invoice_code = i.invoice_code)
)
INNER JOIN bill_item bi ON i.title = bi.title
WHERE 
    (p.invoice_id IS NOT NULL OR p.invoice_code IS NOT NULL)
    AND p.can_delete != 'trash'
    AND p.year = ?
    AND MONTH(FROM_UNIXTIME(p.day_timestamp)) BETWEEN ? AND ?
GROUP BY bi.title, MONTH(FROM_UNIXTIME(p.day_timestamp))
ORDER BY bi.title ASC, payment_month ASC
";

// Track query execution time for timeout detection
$query_start_time = microtime(true);

try {
    // Execute the query with parameterized values to prevent SQL injection
    $query = $this->db->query($sql, array($academic_year, $start_month, $end_month));
    
    // Calculate query execution time
    $query_execution_time = microtime(true) - $query_start_time;
    
    // Check if query took longer than 30 seconds (timeout threshold)
    if ($query_execution_time > 30) {
        log_message('info', 'Monthly payment report query exceeded 30 seconds: ' . $query_execution_time . 's for period ' . $start_month . '-' . $end_month . ', year ' . $academic_year);
        echo '<tr><td colspan="100" style="text-align:center; color:#f59e0b; padding:40px;">';
        echo '<i class="fa fa-clock" style="font-size:48px; margin-bottom:10px;"></i><br>';
        echo '<strong>Query took longer than expected</strong><br>';
        echo '<span style="color:#6b7280;">The report loaded but took ' . round($query_execution_time, 2) . ' seconds. Consider selecting a narrower date range for better performance.</span>';
        echo '</td></tr>';
        return;
    }
    
    // Get query results
    $results = $query->result_array();
    
    // Log successful query execution for monitoring
    log_message('info', 'Monthly payment report generated successfully in ' . round($query_execution_time, 2) . 's for period ' . $start_month . '-' . $end_month . ', year ' . $academic_year);
    
} catch (Exception $e) {
    // Log detailed error information for debugging
    log_message('error', 'Monthly payment report query failed: ' . $e->getMessage() . ' | Query: ' . $sql . ' | Parameters: year=' . $academic_year . ', start_month=' . $start_month . ', end_month=' . $end_month);
    
    // Display user-friendly error message
    echo '<tr><td colspan="100" style="text-align:center; color:#ef4444; padding:40px;">';
    echo '<i class="fa fa-exclamation-triangle" style="font-size:48px; margin-bottom:10px;"></i><br>';
    echo '<strong>An error occurred while loading the report</strong><br>';
    echo '<span style="color:#6b7280;">Please try again or contact support if the problem persists.</span><br>';
    echo '<span style="color:#9ca3af; font-size:12px; margin-top:10px; display:block;">Error details have been logged for technical review.</span>';
    echo '</td></tr>';
    return;
}

// Organize data into matrix structure
$matrix = [];
$invoice_items = [];
$month_totals = array_fill_keys($months, 0);
$grand_total = 0;

foreach ($results as $row) {
    $item = $row['invoice_item'];
    $month = $row['payment_month'];
    $amount = $row['total_amount'];
    
    if (!isset($matrix[$item])) {
        $matrix[$item] = array_fill_keys($months, 0);
        $matrix[$item]['row_total'] = 0;
        $invoice_items[] = $item;
    }
    
    $matrix[$item][$month] = $amount;
    $matrix[$item]['row_total'] += $amount;
    $month_totals[$month] += $amount;
    $grand_total += $amount;
}

// Check if data exists
if (empty($invoice_items)) {
    echo '<tr><td colspan="100" style="text-align:center; padding:60px 20px;">';
    echo '<div style="max-width:500px; margin:0 auto;">';
    echo '<i class="fa fa-inbox" style="font-size:64px; color:#cbd5e1; margin-bottom:20px; display:block;"></i>';
    echo '<h4 style="color:#1f2937; font-weight:700; margin-bottom:10px;">No Payment Data Found</h4>';
    echo '<p style="color:#6b7280; font-size:14px; line-height:1.6; margin-bottom:20px;">';
    echo 'There are no invoice-based payments recorded for the selected period.<br>';
    echo 'This could mean no payments were made during this time, or payments were not linked to invoices.';
    echo '</p>';
    echo '<div style="background:#f0f9ff; border:1px solid #bfdbfe; border-radius:8px; padding:15px; margin-top:20px;">';
    echo '<p style="color:#1e40af; font-size:13px; margin:0; line-height:1.5;">';
    echo '<i class="fa fa-lightbulb" style="color:#3b82f6; margin-right:5px;"></i> <strong>Suggestions:</strong><br>';
    echo '• Try selecting a different date range<br>';
    echo '• Verify the academic year is correct<br>';
    echo '• Check if payments were properly linked to invoices';
    echo '</p>';
    echo '</div>';
    echo '</div>';
    echo '</td></tr>';
    return;
}

// Output the complete table structure with header and data rows
// First, output the header row
echo '<tr><th style="background:#3b82f6; color:white; padding:12px; text-align:left;">Invoice Item</th>';
foreach ($months as $m) {
    echo '<th style="background:#3b82f6; color:white; padding:12px; text-align:right;">' . $month_names[$m] . '</th>';
}
echo '<th style="background:#1e40af; color:white; padding:12px; text-align:right;">Total</th></tr>';

// Generate table rows for each invoice item
foreach ($invoice_items as $item) {
    echo '<tr>';
    echo '<td style="font-weight:600; padding:10px; border-bottom:1px solid #e5e7eb;">' . htmlspecialchars($item) . '</td>';
    
    foreach ($months as $m) {
        $amount = $matrix[$item][$m];
        if ($amount > 0) {
            echo '<td style="text-align:right; padding:10px; border-bottom:1px solid #e5e7eb;">' . number_format($amount, 2, '.', ',') . '</td>';
        } else {
            echo '<td style="text-align:right; padding:10px; border-bottom:1px solid #e5e7eb; color:#9ca3af;">-</td>';
        }
    }
    
    echo '<td style="text-align:right; font-weight:700; padding:10px; background:#f3f4f6; border-bottom:1px solid #e5e7eb;">' . number_format($matrix[$item]['row_total'], 2, '.', ',') . '</td>';
    echo '</tr>';
}

// Generate totals row
echo '<tr style="background:#dbeafe; border-top:2px solid #3b82f6;">';
echo '<td style="font-weight:700; padding:12px;">TOTAL</td>';

foreach ($months as $m) {
    echo '<td style="text-align:right; font-weight:700; padding:12px;">' . number_format($month_totals[$m], 2, '.', ',') . '</td>';
}

echo '<td style="text-align:right; font-weight:700; padding:12px; background:#3b82f6; color:white;">' . number_format($grand_total, 2, '.', ',') . '</td>';
echo '</tr>';
?>
