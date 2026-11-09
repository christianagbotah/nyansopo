<?php
$currency_row = $this->db->get_where('settings', array('type' => 'currency'))->row();
$currency = $currency_row ? $currency_row->description : '$';

// Validate required data
if (!isset($data) || !is_array($data)) {
    echo '<div class="alert alert-danger">No data available to display</div>';
    return;
}
if (!isset($term) || !isset($year)) {
    echo '<div class="alert alert-danger">Term and year information missing</div>';
    return;
}
?>
<div>
  <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 25px; border-radius: 0; margin: 0;">
    <div style="display: flex; align-items: center; justify-content: space-between; gap: 20px;">
      <div style="flex: 1;">
        <h3 style="margin: 0; font-size: 24px; font-weight: 700; display: flex; align-items: center; gap: 10px; color: white !important;">
          <i class="fa fa-list-alt" style="font-size: 28px; color: white;"></i>
          Fees Structure Overview
        </h3>
        <p style="margin: 6px 0 0 0; font-size: 14px; color: rgba(255,255,255,0.8);">Term <?php echo $term; ?> | Academic Year <?php echo $year; ?></p>
      </div>
      <div style="display: flex; gap: 15px;">
        <div style="text-align: center; background: rgba(255,255,255,0.15); padding: 12px 20px; border-radius: 8px;">
          <div style="font-size: 11px; opacity: 0.9; margin-bottom: 4px;">Total Classes</div>
          <div style="font-size: 22px; font-weight: 700;" id="summary_total_classes"><?php echo count($data); ?></div>
        </div>
        <div style="text-align: center; background: rgba(255,255,255,0.15); padding: 12px 20px; border-radius: 8px;">
          <div style="font-size: 11px; opacity: 0.9; margin-bottom: 4px;">Total Students</div>
          <div style="font-size: 22px; font-weight: 700;" id="summary_total_students"><?php echo !empty($data) ? array_sum(array_column($data, 'student_count')) : 0; ?></div>
        </div>
        <div style="text-align: center; background: rgba(255,255,255,0.15); padding: 12px 20px; border-radius: 8px;">
          <div style="font-size: 11px; opacity: 0.9; margin-bottom: 4px;">Grand Total</div>
          <div style="font-size: 22px; font-weight: 700;" id="summary_grand_total"><?php echo $currency . number_format(!empty($data) ? array_sum(array_column($data, 'total_amount')) : 0, 2); ?></div>
        </div>
      </div>
    </div>
  </div>
  <div style="padding: 20px; background: #f8fafc; border-bottom: 2px solid #e2e8f0;">
    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr auto; gap: 12px; align-items: end;">
      <div>
        <label style="display: block; font-size: 12px; font-weight: 600; color: #64748b; margin-bottom: 6px; text-transform: uppercase;">Filter by Class</label>
        <select id="fees_filter_class" onchange="filterFeesTable()" class="select2-fees-modal" style="width: 100%; padding: 10px; border: 2px solid #cbd5e1; border-radius: 8px; font-size: 14px; font-weight: 500; height: 46px;">
          <option value="">All Classes</option>
          <?php 
          $selected_classes = is_array($class_ids) ? $class_ids : [];
          $all_class_names = !empty($data) ? array_unique(array_column($data, 'class_name')) : [];
          foreach($all_class_names as $class): 
            // Pre-select if only one class was filtered
            $isSelected = (count($selected_classes) === 1) ? 'selected' : '';
          ?>
            <option value="<?php echo htmlspecialchars($class); ?>" <?php echo $isSelected; ?>><?php echo htmlspecialchars($class); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label style="display: block; font-size: 12px; font-weight: 600; color: #64748b; margin-bottom: 6px; text-transform: uppercase;">Filter by Item</label>
        <select id="fees_filter_item" onchange="filterFeesTable()" class="select2-fees-modal" style="width: 100%; padding: 10px; border: 2px solid #cbd5e1; border-radius: 8px; font-size: 14px; font-weight: 500; height: 46px;">
          <option value="">All Items</option>
          <?php 
          $allItems = [];
          if (!empty($data)) {
            foreach($data as $class) {
              if (isset($class['items']) && is_array($class['items'])) {
                foreach($class['items'] as $item) {
                  if (isset($item['title'])) {
                    $allItems[] = $item['title'];
                  }
                }
              }
            }
          }
          foreach(array_unique($allItems) as $item): ?>
            <option value="<?php echo htmlspecialchars($item); ?>"><?php echo htmlspecialchars($item); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label style="display: block; font-size: 12px; font-weight: 600; color: #64748b; margin-bottom: 6px; text-transform: uppercase;">Search</label>
        <input type="text" id="fees_search" onkeyup="filterFeesTable()" placeholder="Search..." style="width: 100%; padding: 0 12px; border: 2px solid #cbd5e1; border-radius: 8px; font-size: 14px; height: 46px; box-sizing: border-box; line-height: normal;">
      </div>
      <button onclick="exportFeesStructure()" style="padding: 0 20px; background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 8px; height: 46px; white-space: nowrap; box-sizing: border-box; align-self: flex-end;">
        <i class="fa fa-file-excel"></i> Export
      </button>
    </div>
  </div>
  <div style="padding: 20px;" id="fees_structure_container">
    <div id="fees_structure_content">
      <?php 
      if (empty($data)): ?>
        <div class="alert alert-info" style="text-align: center; padding: 20px;">
          <i class="fa fa-info-circle" style="font-size: 48px; color: #3b82f6; margin-bottom: 10px;"></i>
          <h4>No fees structure data available</h4>
          <p>There are no fee items configured for the selected term, year, and class(es).</p>
        </div>
      <?php else:
        foreach($data as $classData): 
          // Validate class data structure
          if (!isset($classData['class_name']) || !isset($classData['items']) || !is_array($classData['items'])) {
            continue;
          }
      ?>
        <div class="fees-class-card" data-class="<?php echo htmlspecialchars($classData['class_name']); ?>" data-student-count="<?php echo isset($classData['student_count']) ? intval($classData['student_count']) : 0; ?>" data-total-amount="<?php echo isset($classData['total_amount']) ? floatval($classData['total_amount']) : 0; ?>" style="background: white; border: 2px solid #e2e8f0; border-radius: 12px; margin-bottom: 20px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">
          <div style="background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%); padding: 20px; border-bottom: 2px solid #cbd5e1;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
              <div>
                <h4 style="margin: 0 0 6px 0; font-size: 20px; font-weight: 700; color: #1e293b;">
                  <i class="fa fa-school" style="color: #667eea;"></i> <?php echo htmlspecialchars($classData['class_name']); ?>
                </h4>
                <p style="margin: 0; font-size: 13px; color: #64748b;">
                  <i class="fa fa-users"></i> <?php echo isset($classData['student_count']) ? intval($classData['student_count']) : 0; ?> student(s)
                </p>
              </div>
              <div style="text-align: right;">
                <div style="font-size: 12px; color: #64748b;">Total Fees</div>
                <div style="font-size: 28px; font-weight: 700; color: #10b981;"><?php echo $currency . number_format(isset($classData['total_amount']) ? floatval($classData['total_amount']) : 0, 2); ?></div>
              </div>
            </div>
          </div>
          <table style="width: 100%; border-collapse: collapse;">
            <thead>
              <tr style="background: #f1f5f9;">
                <th style="padding: 12px 20px; text-align: left; font-size: 12px; font-weight: 700; color: #475569;">#</th>
                <th style="padding: 12px 20px; text-align: left; font-size: 12px; font-weight: 700; color: #475569;">Item Title</th>
                <th style="padding: 12px 20px; text-align: left; font-size: 12px; font-weight: 700; color: #475569;">Description</th>
                <th style="padding: 12px 20px; text-align: right; font-size: 12px; font-weight: 700; color: #475569;">Amount</th>
              </tr>
            </thead>
            <tbody>
              <?php 
              $itemIndex = 1;
              foreach($classData['items'] as $item): 
                // Validate item structure
                if (!isset($item['title']) || !isset($item['amount'])) {
                  continue;
                }
              ?>
                <tr class="fees-item-row" data-item="<?php echo htmlspecialchars($item['title']); ?>" data-amount="<?php echo floatval($item['amount']); ?>" style="border-bottom: 1px solid #f1f5f9;">
                  <td style="padding: 16px 20px; font-size: 14px; color: #64748b;"><?php echo $itemIndex++; ?></td>
                  <td style="padding: 16px 20px; font-size: 15px; font-weight: 600; color: #1e293b;"><?php echo htmlspecialchars($item['title']); ?></td>
                  <td style="padding: 16px 20px; font-size: 14px; color: #64748b;"><?php echo !empty($item['description']) ? htmlspecialchars($item['description']) : '<span style="color: #cbd5e1;">No description</span>'; ?></td>
                  <td style="padding: 16px 20px; text-align: right; font-size: 18px; font-weight: 700; color: #059669;"><?php echo $currency . number_format(floatval($item['amount']), 2); ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
            <tfoot>
              <tr style="background: #f8fafc; border-top: 3px solid #cbd5e1;">
                <td colspan="3" style="padding: 16px 20px; font-size: 16px; font-weight: 700;">Class Total</td>
                <td style="padding: 16px 20px; text-align: right; font-size: 20px; font-weight: 700; color: #10b981;"><?php echo $currency . number_format(isset($classData['total_amount']) ? floatval($classData['total_amount']) : 0, 2); ?></td>
              </tr>
            </tfoot>
          </table>
        </div>
      <?php 
        endforeach;
      endif;
      ?>
    </div>
  </div>
</div>

<script>
window.feesExportData = {
  term: <?php echo json_encode(isset($term) ? $term : null); ?>, 
  year: <?php echo json_encode(isset($year) ? $year : null); ?>, 
  classIds: <?php echo json_encode(isset($class_ids) ? $class_ids : []); ?>
};

// Currency symbol from PHP
const currencySymbol = '<?php echo $currency; ?>';

// Initialize Select2 for filters - execute immediately when script loads
// Use a small delay to ensure DOM is ready in the modal
setTimeout(function() {
  $('.select2-fees-modal').select2({
    dropdownParent: $('#createModal'),
    placeholder: 'Select an option',
    allowClear: true,
    width: '100%',
    minimumResultsForSearch: 0  // Always show search box
  });
}, 200);

function updateSummaryFigures() {
  let visibleClasses = 0;
  let totalStudents = 0;
  let grandTotal = 0;
  
  $('.fees-class-card').each(function() {
    const $card = $(this);
    
    // Only count visible cards
    if (!$card.is(':visible')) {
      return; // Skip hidden cards
    }
    
    visibleClasses++;
    
    // Get student count from data attribute
    const studentCount = parseInt($card.data('student-count')) || 0;
    totalStudents += studentCount;
    
    // Calculate total from visible item rows within this card
    let cardTotal = 0;
    $card.find('.fees-item-row').each(function() {
      if ($(this).is(':visible')) {
        const amount = parseFloat($(this).data('amount')) || 0;
        cardTotal += amount;
      }
    });
    
    grandTotal += cardTotal;
  });
  
  // Update the summary displays with proper number formatting
  $('#summary_total_classes').text(visibleClasses);
  $('#summary_total_students').text(totalStudents);
  $('#summary_grand_total').text(currencySymbol + grandTotal.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ','));
}

function filterFeesTable() {
  const classFilter = $('#fees_filter_class').val();
  const itemFilter = $('#fees_filter_item').val();
  const searchTerm = $('#fees_search').val().toLowerCase().trim();
  
  $('.fees-class-card').each(function() {
    const $card = $(this);
    const className = $card.data('class');
    
    // Class filter check - EXACT match only
    let showCard = true;
    if(classFilter) {
      showCard = (className === classFilter);
    }
    
    // Search term check (searches in class name and item names/descriptions)
    if(showCard && searchTerm) {
      const classNameMatch = className.toLowerCase().includes(searchTerm);
      const itemNameMatch = $card.find('.fees-item-row').filter(function() {
        return $(this).find('td:nth-child(2)').text().toLowerCase().includes(searchTerm) ||
               $(this).find('td:nth-child(3)').text().toLowerCase().includes(searchTerm);
      }).length > 0;
      showCard = classNameMatch || itemNameMatch;
    }
    
    // Item filter check
    if(showCard && itemFilter) {
      showCard = $card.find('.fees-item-row').filter(function() {
        return $(this).data('item') === itemFilter;
      }).length > 0;
    }
    
    // Toggle card visibility
    $card.toggle(showCard);
    
    // Handle item row visibility within visible cards
    if(showCard) {
      $card.find('.fees-item-row').each(function() {
        const $row = $(this);
        let showRow = true;
        
        // Apply item filter
        if(itemFilter && $row.data('item') !== itemFilter) {
          showRow = false;
        }
        
        // Apply search filter to items
        if(showRow && searchTerm) {
          const itemTitle = $row.find('td:nth-child(2)').text().toLowerCase();
          const itemDesc = $row.find('td:nth-child(3)').text().toLowerCase();
          showRow = itemTitle.includes(searchTerm) || itemDesc.includes(searchTerm);
        }
        
        $row.toggle(showRow);
      });
    }
  });
  
  // Update summary figures after filtering
  updateSummaryFigures();
}
</script>
