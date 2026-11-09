<?php
$student = $this->db->where('student_id', $request['student_id'])->get('student')->row();
// Get running year and term
$running_year = $this->db->get_where('settings', array('type' => 'running_year'))->row()->description;
$running_term = $this->db->get_where('settings', array('type' => 'running_term'))->row()->description;
// Get class from enroll table
$enroll = $this->db->where('student_id', $request['student_id'])
                   ->where('year', $running_year)
                   ->where('term', $running_term)
                   ->get('enroll')->row();
$class = $this->db->where('class_id', $enroll->class_id)->get('class')->row();
$section = $this->db->where('section_id', $enroll->section_id)->get('section')->row();
$requester = $this->db->where('admin_id', $request['requested_by'])->get('admin')->row();
$old_data = json_decode($request['old_data'], true);
$new_data = $request['new_data'] ? json_decode($request['new_data'], true) : null;
$currency = $this->db->get_where('settings', array('type' => 'currency'))->row()->description;

// Get discount information
$original_discount = $this->db->where('invoice_code', $request['invoice_code'])->where('status', 'approved')->get('invoice_discounts')->row();
$original_discount_amount = $original_discount ? $original_discount->discount_amount : 0;

// Get per-item discount details
$discount_items_map = array();
if($original_discount) {
    $discount_items = $this->db->where('discount_id', $original_discount->discount_id)->get('invoice_discount_items')->result_array();
    foreach($discount_items as $disc_item) {
        $discount_items_map[$disc_item['item_title']] = $disc_item;
    }
}

// Calculate new discount if applicable
$new_discount_amount = 0;
$discount_changed = false;
if($original_discount && $request['request_type'] == 'edit' && $new_data) {
    $profile = $this->db->where('profile_id', $original_discount->profile_id)->get('discount_profiles')->row();
    if($profile) {
        // Get applicable bill items from profile and check if their amounts changed
        $applicable_total_old = 0;
        $applicable_total_new = 0;
        
        // Create lookup for old items
        $old_items_lookup = array();
        foreach($old_data as $old_item) {
            $old_items_lookup[$old_item['title']] = $old_item;
        }
        
        if($profile->bill_item_ids === '*') {
            // Wildcard: check ALL items
            foreach($new_data as $item) {
                $item_discount = isset($discount_items_map[$item['title']]) ? $discount_items_map[$item['title']]['discount_amount'] : 0;
                $original_item_amount = $item['amount'] + $item_discount;
                $applicable_total_new += $original_item_amount;
            }
            foreach($old_data as $item) {
                $item_discount = isset($discount_items_map[$item['title']]) ? $discount_items_map[$item['title']]['discount_amount'] : 0;
                $original_item_amount = $item['amount'] + $item_discount;
                $applicable_total_old += $original_item_amount;
            }
        } else {
            // Specific items: only check items that discount applies to
            $profile_bill_items = explode(',', $profile->bill_item_ids);
            foreach($profile_bill_items as $bill_item_id) {
                $bill_item = $this->db->where('id', trim($bill_item_id))->get('bill_item')->row();
                if($bill_item) {
                    // Check in new data
                    foreach($new_data as $item) {
                        if(strtolower(trim($item['title'])) == strtolower(trim($bill_item->title))) {
                            $item_discount = isset($discount_items_map[$item['title']]) ? $discount_items_map[$item['title']]['discount_amount'] : 0;
                            $original_item_amount = $item['amount'] + $item_discount;
                            $applicable_total_new += $original_item_amount;
                            break;
                        }
                    }
                    // Check in old data
                    foreach($old_data as $item) {
                        if(strtolower(trim($item['title'])) == strtolower(trim($bill_item->title))) {
                            $item_discount = isset($discount_items_map[$item['title']]) ? $discount_items_map[$item['title']]['discount_amount'] : 0;
                            $original_item_amount = $item['amount'] + $item_discount;
                            $applicable_total_old += $original_item_amount;
                            break;
                        }
                    }
                }
            }
        }
        
        // Only calculate new discount if applicable items' amounts changed
        if(abs($applicable_total_new - $applicable_total_old) > 0.01) {
            $new_discount_amount = $profile->discount_method == 'percentage' 
                ? ($applicable_total_new * $profile->discount_value) / 100 
                : min($profile->discount_value, $applicable_total_new);
            $discount_changed = (abs($new_discount_amount - $original_discount_amount) > 0.01);
        }
    }
}

// Get theme colors
$theme = $this->db->get_where('settings', array('type' => 'app_theme'))->row()->description ?? 'default';
$themes = array(
    'default' => array('primary' => '#667eea', 'secondary' => '#764ba2'),
    'ocean' => array('primary' => '#2E3192', 'secondary' => '#1BFFFF'),
    'sunset' => array('primary' => '#f12711', 'secondary' => '#f5af19'),
    'forest' => array('primary' => '#134E5E', 'secondary' => '#71B280'),
    'purple' => array('primary' => '#5f27cd', 'secondary' => '#341f97'),
    'crimson' => array('primary' => '#c0392b', 'secondary' => '#e74c3c'),
    'teal' => array('primary' => '#16a085', 'secondary' => '#1abc9c'),
    'midnight' => array('primary' => '#2c3e50', 'secondary' => '#34495e'),
);
if($theme === 'custom') {
    $primary = $this->db->get_where('settings', array('type' => 'theme_primary'))->row()->description ?? '#667eea';
    $secondary = $this->db->get_where('settings', array('type' => 'theme_secondary'))->row()->description ?? '#764ba2';
} else {
    $primary = $themes[$theme]['primary'] ?? '#667eea';
    $secondary = $themes[$theme]['secondary'] ?? '#764ba2';
}
?>

<style>
.details-container { background: linear-gradient(135deg, <?php echo $primary; ?> 0%, <?php echo $secondary; ?> 100%); padding: 0; border-radius: 16px; overflow: hidden; box-shadow: 0 20px 60px rgba(0,0,0,0.2); }
.details-header { background: rgba(255,255,255,0.15); backdrop-filter: blur(10px); padding: 30px; color: white; border-bottom: 1px solid rgba(255,255,255,0.2); }
.details-body { background: white; padding: 30px; }
.info-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 24px; margin-bottom: 30px; }
.info-card { background: #f8f9fa; padding: 20px; border-radius: 12px; border-left: 4px solid <?php echo $primary; ?>; }
.info-label { font-size: 12px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px; font-weight: 600; }
.info-value { font-size: 16px; color: #1a202c; font-weight: 700; }
.section-title { font-size: 20px; font-weight: 700; color: #1a202c; margin: 30px 0 20px; padding-bottom: 10px; border-bottom: 3px solid <?php echo $primary; ?>; display: flex; align-items: center; gap: 10px; }
.data-table { width: 100%; border-collapse: separate; border-spacing: 0; margin-bottom: 20px; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
.data-table th { background: linear-gradient(135deg, <?php echo $primary; ?> 0%, <?php echo $secondary; ?> 100%); color: white; padding: 16px; text-align: left; font-weight: 600; font-size: 14px; text-transform: uppercase; }
.data-table td { padding: 16px; border-bottom: 1px solid #e5e7eb; background: white; }
.data-table tr:last-child td { border-bottom: none; }
.data-table tr:hover td { background: #f9fafb; }
.badge-status { padding: 8px 16px; border-radius: 20px; font-size: 12px; font-weight: 700; text-transform: uppercase; display: inline-block; }
.badge-pending { background: #fef3c7; color: #92400e; }
.badge-approved { background: #d1fae5; color: #065f46; }
.badge-declined { background: #fee2e2; color: #991b1b; }
.change-indicator { padding: 4px 8px; border-radius: 6px; font-size: 12px; font-weight: 600; }
.change-old { background: #fee2e2; color: #991b1b; text-decoration: line-through; }
.change-new { background: #d1fae5; color: #065f46; }
.reason-box { background: #fef3c7; border-left: 4px solid #f59e0b; padding: 20px; border-radius: 8px; margin: 20px 0; }
.timeline-item { position: relative; padding-left: 40px; padding-bottom: 30px; }
.timeline-item:before { content: ''; position: absolute; left: 12px; top: 8px; bottom: -10px; width: 2px; background: #e5e7eb; }
.timeline-dot { position: absolute; left: 0; top: 0; width: 28px; height: 28px; border-radius: 50%; background: <?php echo $primary; ?>; color: white; display: flex; align-items: center; justify-content: center; font-size: 12px; z-index: 1; }
.timeline-content { background: #f9fafb; padding: 16px; border-radius: 8px; }

@media (max-width: 768px) {
    .details-header { padding: 20px; }
    .details-body { padding: 20px; }
    .info-grid { grid-template-columns: 1fr; gap: 16px; }
    .data-table { font-size: 14px; }
    .data-table th, .data-table td { padding: 12px 8px; }
}
</style>

<div class="details-container">
    <div class="details-header">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
            <div>
                <h2 style="margin: 0; font-size: 28px; font-weight: 700; color: white;">
                    <i class="fa fa-file-invoice"></i> Invoice Modification Request
                </h2>
                <p style="margin: 8px 0 0 0; opacity: 0.9; font-size: 14px; color: white;">Request ID: #<?php echo $request['request_id']; ?></p>
            </div>
            <span class="badge-status badge-<?php echo $request['status']; ?>">
                <?php echo strtoupper($request['status']); ?>
            </span>
        </div>
    </div>

    <div class="details-body">
        <!-- Request Information -->
        <div class="info-grid">
            <div class="info-card">
                <div class="info-label"><i class="fa fa-user"></i> Student</div>
                <div class="info-value"><?php echo $student->name; ?></div>
                <div style="font-size: 12px; color: #6b7280; margin-top: 4px;"><?php echo $student->student_code; ?></div>
                <div style="font-size: 12px; color: #6b7280; margin-top: 2px;">
                    <i class="fa fa-school"></i> <?php echo $class->name . ' ' . $class->name_numeric . ' ' . $section->name; ?>
                </div>
            </div>
            <div class="info-card">
                <div class="info-label"><i class="fa fa-file-invoice"></i> Invoice Code</div>
                <div class="info-value">#<?php echo $request['invoice_code']; ?></div>
                <div style="font-size: 12px; color: #6b7280; margin-top: 4px;">
                    <i class="fa fa-calendar"></i> Created: <?php 
                        $creation_ts = isset($old_data[0]['creation_timestamp']) && $old_data[0]['creation_timestamp'] > 0 
                            ? $old_data[0]['creation_timestamp'] 
                            : strtotime($request['created_at']);
                        echo date('M d, Y', $creation_ts); 
                    ?>
                </div>
            </div>
            <div class="info-card">
                <div class="info-label"><i class="fa fa-edit"></i> Request Type</div>
                <div class="info-value"><?php echo ucfirst($request['request_type']); ?></div>
            </div>
            <div class="info-card">
                <div class="info-label"><i class="fa fa-user-tie"></i> Requested By</div>
                <div class="info-value"><?php echo $requester->name; ?></div>
                <div style="font-size: 12px; color: #6b7280; margin-top: 4px;"><?php echo date('M d, Y H:i', strtotime($request['created_at'])); ?></div>
            </div>
        </div>

        <!-- Reason -->
        <?php if(isset($request['request_reason']) && $request['request_reason']): ?>
        <div class="reason-box">
            <div style="font-weight: 700; color: #92400e; margin-bottom: 8px; display: flex; align-items: center; gap: 8px;">
                <i class="fa fa-exclamation-circle"></i> Reason for Request
            </div>
            <div style="color: #78350f; line-height: 1.6;"><?php echo nl2br(htmlspecialchars($request['request_reason'])); ?></div>
        </div>
        <?php endif; ?>

        <!-- Original Invoice Data -->
        <div class="section-title">
            <i class="fa fa-file-alt"></i> Original Invoice Data
        </div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Description</th>
                    <th style="text-align: right;">Amount (<?php echo $currency; ?>)</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $total_old = 0;
                $total_old_before_discount = 0;
                foreach($old_data as $item): 
                    $total_old += $item['amount'];
                    // Get original amount before discount
                    $item_discount = isset($discount_items_map[$item['title']]) ? $discount_items_map[$item['title']]['discount_amount'] : 0;
                    $original_amount = $item['amount'] + $item_discount;
                    $total_old_before_discount += $original_amount;
                ?>
                <tr>
                    <td><strong><?php echo $item['title']; ?></strong></td>
                    <td><?php echo $item['description']; ?></td>
                    <td style="text-align: right;">
                        <?php if($item_discount > 0): ?>
                            <div style="display: flex; align-items: center; justify-content: flex-end; gap: 8px;">
                                <span style="text-decoration: line-through; color: #6b7280; font-size: 13px;"><?php echo number_format($original_amount, 2); ?></span>
                                <i class="fa fa-arrow-right" style="color: #6b7280; font-size: 11px;"></i>
                                <span style="font-weight: 700; color: #059669;"><?php echo number_format($item['amount'], 2); ?></span>
                                <span style="font-size: 11px; color: #92400e; background: #fef3c7; padding: 2px 6px; border-radius: 4px; font-weight: 600;">-<?php echo number_format($item_discount, 2); ?></span>
                            </div>
                        <?php else: ?>
                            <span style="font-weight: 700;"><?php echo number_format($item['amount'], 2); ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <tr style="background: #f3f4f6;">
                    <td colspan="2" style="text-align: right; font-weight: 700; font-size: 16px;">SUBTOTAL:</td>
                    <td style="text-align: right; font-weight: 700; font-size: 16px; color: #667eea;"><?php echo number_format($total_old_before_discount, 2); ?></td>
                </tr>
                <?php if($original_discount_amount > 0): ?>
                <tr style="background: #fef3c7;">
                    <td colspan="2" style="text-align: right; font-weight: 600; color: #92400e;"><i class="fa fa-tag"></i> Total Discount Applied:</td>
                    <td style="text-align: right; font-weight: 700; color: #92400e;">-<?php echo number_format($original_discount_amount, 2); ?></td>
                </tr>
                <?php endif; ?>
                <tr style="background: #e0e7ff;">
                    <td colspan="2" style="text-align: right; font-weight: 700; font-size: 16px;">TOTAL:</td>
                    <td style="text-align: right; font-weight: 700; font-size: 16px; color: #667eea;"><?php echo number_format($total_old, 2); ?></td>
                </tr>
            </tbody>
        </table>

        <!-- New Invoice Data (if edit request) -->
        <?php 
        // Force decode new_data if it's still a string
        if($new_data && !is_array($new_data)) {
            $new_data = json_decode($new_data, true);
        }
        ?>
        <?php if($request['request_type'] == 'edit' && $new_data && is_array($new_data) && !empty($new_data)): ?>
        <div class="section-title" style="margin-top: 40px;">
            <i class="fa fa-edit"></i> Proposed New Invoice
        </div>
        
        <table class="data-table">
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Description</th>
                    <th style="text-align: right;">Amount (<?php echo $currency; ?>)</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $total_new = 0;
                // Create lookup for old items by title
                $old_items_map = [];
                foreach($old_data as $old_item) {
                    $old_items_map[$old_item['title']] = $old_item;
                }
                
                foreach($new_data as $item): 
                    $total_new += $item['amount'];
                    $is_new_item = !isset($old_items_map[$item['title']]);
                    $amount_changed = false;
                    $old_amount = 0;
                    
                    if(!$is_new_item) {
                        $old_amount = $old_items_map[$item['title']]['amount'];
                        $amount_changed = ($old_amount != $item['amount']);
                    }
                    
                    // Get description: prioritize new_data, fallback to old_data, then bill_item
                    $description = '';
                    if(isset($item['description']) && $item['description']) {
                        $description = $item['description'];
                    } elseif(!$is_new_item && isset($old_items_map[$item['title']]['description'])) {
                        $description = $old_items_map[$item['title']]['description'];
                    } else {
                        // Fallback: get from bill_item table using title
                        $bill_item = $this->db->where('title', $item['title'])->get('bill_item')->row();
                        if($bill_item) {
                            $description = $bill_item->description;
                        }
                    }
                    
                    $row_class = $is_new_item ? 'background: #ecfdf5; border-left: 4px solid #10b981;' : '';
                ?>
                <tr style="<?php echo $row_class; ?>">
                    <td>
                        <strong><?php echo $item['title']; ?></strong>
                        <?php if($is_new_item): ?>
                            <span style="display: inline-block; margin-left: 8px; padding: 2px 8px; background: #10b981; color: white; border-radius: 4px; font-size: 10px; font-weight: 700; text-transform: uppercase;">NEW</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php echo $description; ?>
                    </td>
                    <td style="text-align: right; font-weight: 700;">
                        <?php if($amount_changed): ?>
                            <div style="display: flex; align-items: center; justify-content: flex-end; gap: 8px;">
                                <span style="text-decoration: line-through; color: #ef4444; font-size: 13px;"><?php echo number_format($old_amount, 2); ?></span>
                                <i class="fa fa-arrow-right" style="color: #6b7280; font-size: 12px;"></i>
                                <span style="padding: 4px 8px; background: #fef3c7; color: #92400e; border-radius: 4px; font-weight: 700;"><?php echo number_format($item['amount'], 2); ?></span>
                            </div>
                        <?php else: ?>
                            <?php echo number_format($item['amount'], 2); ?>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <tr style="background: #f3f4f6;">
                    <td colspan="2" style="text-align: right; font-weight: 700; font-size: 16px;"><?php echo $discount_changed ? 'SUBTOTAL:' : 'NEW TOTAL:'; ?></td>
                    <td style="text-align: right; font-weight: 700; font-size: 16px;">
                        <?php if(!$discount_changed): 
                            // When discount doesn't change, $total_new is the new total (user's proposed amounts)
                            // Just compare directly with $total_old
                            $total_diff = $total_new - $total_old;
                        ?>
                        <div style="display: flex; align-items: center; justify-content: flex-end; gap: 12px;">
                            <span style="color: #667eea;"><?php echo number_format($total_new, 2); ?></span>
                            <?php if(abs($total_diff) > 0.01): 
                                $diff_color = $total_diff > 0 ? '#059669' : '#dc2626';
                                $diff_bg = $total_diff > 0 ? '#d1fae5' : '#fee2e2';
                            ?>
                                <span style="padding: 4px 12px; background: <?php echo $diff_bg; ?>; color: <?php echo $diff_color; ?>; border-radius: 6px; font-size: 14px; font-weight: 600;">
                                    <?php echo $total_diff > 0 ? '+' : ''; ?><?php echo number_format($total_diff, 2); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        <?php else: ?>
                            <span style="color: #667eea;"><?php echo number_format($total_new, 2); ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php if($discount_changed): ?>
                <tr style="background: #fef3c7;">
                    <td colspan="2" style="text-align: right; font-weight: 600; color: #92400e;"><i class="fa fa-tag"></i> Proposed Discount to be Applied:</td>
                    <td style="text-align: right; font-weight: 700; color: #92400e;">-<?php echo number_format($new_discount_amount, 2); ?></td>
                </tr>
                <tr style="background: #e0e7ff;">
                    <td colspan="2" style="text-align: right; font-weight: 700; font-size: 16px;">NEW TOTAL:</td>
                    <td style="text-align: right; font-weight: 700; font-size: 16px;">
                        <?php 
                        $total_new_with_discount = $total_new - $new_discount_amount;
                        $total_diff = $total_new_with_discount - $total_old;
                        $diff_color = $total_diff > 0 ? '#059669' : ($total_diff < 0 ? '#dc2626' : '#6b7280');
                        $diff_bg = $total_diff > 0 ? '#d1fae5' : ($total_diff < 0 ? '#fee2e2' : '#f3f4f6');
                        ?>
                        <div style="display: flex; align-items: center; justify-content: flex-end; gap: 12px;">
                            <span style="color: #667eea;"><?php echo number_format($total_new_with_discount, 2); ?></span>
                            <?php if(abs($total_diff) > 0.01): ?>
                                <span style="padding: 4px 12px; background: <?php echo $diff_bg; ?>; color: <?php echo $diff_color; ?>; border-radius: 6px; font-size: 14px; font-weight: 600;">
                                    <?php echo $total_diff > 0 ? '+' : ''; ?><?php echo number_format($total_diff, 2); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
        <?php endif; ?>

        <!-- Timeline -->
        <div class="section-title">
            <i class="fa fa-history"></i> Request Timeline
        </div>
        <div style="margin-top: 20px;">
            <div class="timeline-item">
                <div class="timeline-dot"><i class="fa fa-plus"></i></div>
                <div class="timeline-content">
                    <div style="font-weight: 700; color: #1a202c; margin-bottom: 4px;">Request Created</div>
                    <div style="font-size: 14px; color: #6b7280;">
                        <?php echo date('F d, Y \a\t H:i', strtotime($request['created_at'])); ?> by <?php echo $requester->name; ?>
                    </div>
                </div>
            </div>
            
            <?php if($request['status'] != 'pending'): 
                $reviewer = $this->db->where('admin_id', $request['reviewed_by'])->get('admin')->row();
            ?>
            <div class="timeline-item" style="padding-bottom: 0;">
                <div class="timeline-dot" style="background: <?php echo $request['status'] == 'approved' ? '#10b981' : '#ef4444'; ?>;">
                    <i class="fa fa-<?php echo $request['status'] == 'approved' ? 'check' : 'times'; ?>"></i>
                </div>
                <div class="timeline-content">
                    <div style="font-weight: 700; color: #1a202c; margin-bottom: 4px;">
                        Request <?php echo ucfirst($request['status']); ?>
                    </div>
                    <div style="font-size: 14px; color: #6b7280;">
                        <?php echo date('F d, Y \a\t H:i', strtotime($request['reviewed_at'])); ?> by <?php echo $reviewer->name; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
