<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Manual Sync Utility
 * Syncs all unsynced payments to accounts
 */

echo "<h2>Manual Sync Utility</h2>";
echo "<hr>";

// Get all unsynced payments
$unsynced_payments = $this->db->select('payment_id, student_id, amount, payment_method, timestamp')
    ->where('synced_to_accounts', 0)
    ->or_where('synced_to_accounts IS NULL', null, false)
    ->order_by('payment_id', 'ASC')
    ->get('payment')->result();

echo "<h3>Found ".count($unsynced_payments)." unsynced payments</h3>";

if(count($unsynced_payments) == 0) {
    echo "<p>✅ All payments are already synced!</p>";
    echo "<p>If you want to test, make a new payment and it should auto-sync.</p>";
} else {
    echo "<form method='post' action='".site_url('admin/sync_payments_now')."'>";
    echo "<p>Click the button below to sync all ".count($unsynced_payments)." payments to the accounting system:</p>";
    echo "<button type='submit' class='btn btn-primary btn-lg' onclick='return confirm(\"Sync ".count($unsynced_payments)." payments to accounts?\")'>
        <i class='fa fa-sync'></i> Sync All Payments Now
    </button>";
    echo "</form>";
    
    echo "<br><h4>Preview (First 10):</h4>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Payment ID</th><th>Student ID</th><th>Amount</th><th>Method</th><th>Date</th></tr>";
    
    $preview = array_slice($unsynced_payments, 0, 10);
    foreach($preview as $pay) {
        echo "<tr>";
        echo "<td>#{$pay->payment_id}</td>";
        echo "<td>#{$pay->student_id}</td>";
        echo "<td>".number_format($pay->amount, 2)."</td>";
        echo "<td>{$pay->payment_method}</td>";
        echo "<td>".date('Y-m-d', $pay->timestamp)."</td>";
        echo "</tr>";
    }
    echo "</table>";
}

echo "<hr>";
echo "<h3>Test New Payment Sync</h3>";
echo "<p>To test if auto-sync is working:</p>";
echo "<ol>";
echo "<li>Go to Fee Collection</li>";
echo "<li>Record a new payment</li>";
echo "<li>Come back here and check if journal_entries increased</li>";
echo "</ol>";

echo "<p><a href='".site_url('admin/integration_diagnostic')."' class='btn btn-info'>← Back to Diagnostic</a></p>";
