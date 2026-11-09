# Receipt Modification Status Display - Usage Guide

## Overview
This system automatically checks and displays the modification status (pending/rejected) on receipt edit/delete buttons.

## Implementation

### Step 1: Include the Status Checker
Add this to any view that displays receipts:
```php
<?php $this->load->view('backend/admin/receipt_status_checker'); ?>
```

### Step 2: Mark Receipt Button Containers
Wrap your receipt action buttons with a container that has:
- `id` attribute (unique identifier)
- `data-receipt-code` attribute (the receipt code)

Example:
```html
<div id="receipt-actions-<?php echo $receipt_code; ?>" data-receipt-code="<?php echo $receipt_code; ?>">
    <button class="btn btn-warning" onclick="editReceipt('<?php echo $receipt_code; ?>')">
        <i class="fa fa-edit"></i> Edit
    </button>
    <button class="btn btn-danger" onclick="deleteReceipt('<?php echo $receipt_code; ?>')">
        <i class="fa fa-trash"></i> Delete
    </button>
</div>
```

### Step 3: Automatic Status Checking
The system will automatically:
- Check modification status on page load
- Update buttons if there's a pending/rejected request
- Disable buttons for pending requests
- Show status badges on buttons

## Button States

### Normal State (No Request)
```html
<button class="btn btn-warning">
    <i class="fa fa-edit"></i> Edit
</button>
```

### Pending State
```html
<button class="btn btn-warning" disabled style="opacity: 0.6; cursor: not-allowed;">
    <i class="fa fa-clock-o"></i> Pending Approval
</button>
```

### Rejected State
```html
<button class="btn btn-danger">
    <i class="fa fa-times-circle"></i> Request Rejected
</button>
```

## Manual Status Check
You can manually check status for a specific receipt:
```javascript
checkReceiptModificationStatus('RECEIPT_CODE', 'container-id');
```

## Example: DataTable Integration

```javascript
$('#receipts-table').DataTable({
    // ... your config
    "drawCallback": function() {
        // Check status after table draws
        initReceiptStatusChecking();
    }
});
```

## Example: Payment History

```php
<!-- In payment_history.php or similar -->
<?php foreach($payments as $payment): ?>
<tr>
    <td><?php echo $payment['receipt_code']; ?></td>
    <td><?php echo $payment['amount']; ?></td>
    <td>
        <div id="receipt-actions-<?php echo $payment['receipt_code']; ?>" 
             data-receipt-code="<?php echo $payment['receipt_code']; ?>">
            <button class="btn btn-sm btn-warning" 
                    onclick="editReceipt('<?php echo $payment['receipt_code']; ?>')">
                <i class="fa fa-edit"></i> Edit
            </button>
            <button class="btn btn-sm btn-danger" 
                    onclick="deleteReceipt('<?php echo $payment['receipt_code']; ?>')">
                <i class="fa fa-trash"></i> Delete
            </button>
        </div>
    </td>
</tr>
<?php endforeach; ?>

<?php $this->load->view('backend/admin/receipt_status_checker'); ?>
```

## Notes
- Only works for receipts with modification requests
- Automatically updates on page load
- Works with DataTables and dynamic content
- Requires jQuery
