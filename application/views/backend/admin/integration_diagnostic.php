<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Integration Diagnostic Tool
 * Tests if finance-to-accounts integration is working
 */

// Check if tables exist
$tables_to_check = [
    'student_ledger',
    'chart_of_accounts', 
    'journal_entries',
    'journal_entry_lines',
    'financial_integration_log'
];

echo "<h2>Integration Diagnostic Report</h2>";
echo "<hr>";

echo "<h3>1. Database Tables Check</h3>";
foreach($tables_to_check as $table) {
    if($this->db->table_exists($table)) {
        $count = $this->db->count_all($table);
        echo "✅ <strong>$table</strong> exists ($count records)<br>";
    } else {
        echo "❌ <strong>$table</strong> MISSING - Run database/student_ledger_schema.sql and database/accounts_finance_schema.sql<br>";
    }
}

echo "<hr>";
echo "<h3>2. Required Columns Check</h3>";

// Check invoice table
if($this->db->field_exists('synced_to_ledger', 'invoice')) {
    echo "✅ invoice.synced_to_ledger exists<br>";
} else {
    echo "❌ invoice.synced_to_ledger MISSING<br>";
}

// Check payment table
if($this->db->field_exists('synced_to_accounts', 'payment')) {
    echo "✅ payment.synced_to_accounts exists<br>";
} else {
    echo "❌ payment.synced_to_accounts MISSING<br>";
}

echo "<hr>";
echo "<h3>3. Helper Functions Check</h3>";

if(function_exists('sync_invoice_to_ledger')) {
    echo "✅ sync_invoice_to_ledger() function exists<br>";
} else {
    echo "❌ sync_invoice_to_ledger() MISSING - Check if finance_integration_helper.php is loaded<br>";
}

if(function_exists('sync_payment_to_accounts')) {
    echo "✅ sync_payment_to_accounts() function exists<br>";
} else {
    echo "❌ sync_payment_to_accounts() MISSING<br>";
}

echo "<hr>";
echo "<h3>4. Recent Sync Status</h3>";

// Check recent invoices
$recent_invoices = $this->db->select('invoice_id, invoice_code, student_id, synced_to_ledger')
    ->order_by('invoice_id', 'DESC')
    ->limit(5)
    ->get('invoice')->result();

echo "<strong>Last 5 Invoices:</strong><br>";
foreach($recent_invoices as $inv) {
    $synced = $inv->synced_to_ledger == 1 ? '✅ Synced' : '❌ Not Synced';
    echo "Invoice #{$inv->invoice_code} - Student #{$inv->student_id} - $synced<br>";
}

echo "<br>";

// Check recent payments
$recent_payments = $this->db->select('payment_id, student_id, amount, synced_to_accounts')
    ->order_by('payment_id', 'DESC')
    ->limit(5)
    ->get('payment')->result();

echo "<strong>Last 5 Payments:</strong><br>";
foreach($recent_payments as $pay) {
    $synced = $pay->synced_to_accounts == 1 ? '✅ Synced' : '❌ Not Synced';
    echo "Payment #{$pay->payment_id} - Student #{$pay->student_id} - Amount: {$pay->amount} - $synced<br>";
}

echo "<hr>";
echo "<h3>5. Integration Log</h3>";

if($this->db->table_exists('financial_integration_log')) {
    $logs = $this->db->order_by('created_at', 'DESC')->limit(10)->get('financial_integration_log')->result();
    if(count($logs) > 0) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Type</th><th>Source</th><th>Status</th><th>Error</th><th>Date</th></tr>";
        foreach($logs as $log) {
            $status_icon = $log->status == 'success' ? '✅' : '❌';
            echo "<tr>";
            echo "<td>{$log->integration_type}</td>";
            echo "<td>{$log->source_table} #{$log->source_id}</td>";
            echo "<td>$status_icon {$log->status}</td>";
            echo "<td>{$log->error_message}</td>";
            echo "<td>".date('Y-m-d H:i', $log->created_at)."</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "⚠️ No integration logs found - Integration may not be running<br>";
    }
} else {
    echo "❌ financial_integration_log table doesn't exist<br>";
}

echo "<hr>";
echo "<h3>6. Recommendations</h3>";

$issues = [];

if(!$this->db->table_exists('student_ledger')) {
    $issues[] = "Run: database/student_ledger_schema.sql";
}

if(!$this->db->table_exists('chart_of_accounts')) {
    $issues[] = "Run: database/accounts_finance_schema.sql";
}

if(!$this->db->field_exists('synced_to_ledger', 'invoice')) {
    $issues[] = "Run ALTER TABLE commands from student_ledger_schema.sql";
}

if(count($issues) > 0) {
    echo "<strong style='color: red;'>⚠️ Action Required:</strong><br>";
    foreach($issues as $issue) {
        echo "• $issue<br>";
    }
} else {
    echo "✅ All tables and columns exist. If sync is not working, check:<br>";
    echo "• Is finance_integration_helper.php being autoloaded?<br>";
    echo "• Are there any PHP errors in the log?<br>";
    echo "• Try creating a new invoice/payment to test<br>";
}
