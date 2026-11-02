// COMPLETE INVOICE CONFIRMATION MODAL CODE - ALREADY INTEGRATED IN student_payment.php
// This file documents the final implementation for reference

/**
 * FEATURES:
 * 1. Per-class breakdown when "By Class" filter is selected
 * 2. Simple bill items list when "All Students", "Boarding", or "Day" filter is selected
 * 3. Accurate student category display with class names
 * 4. Section names always displayed for classes
 * 5. Dynamic heading based on view mode
 * 
 * VIEW MODES:
 * - 'class': Shows per-class breakdown (BASIC 8 A, BASIC 9 B, etc.)
 * - 'simple': Shows selected bill items with total per student
 */

function showInvoiceConfirmationModal(item_ids, callback) {
  const term = $('#term_mass option:selected').text();
  const year = $('#year_mass').val();
  const date = $('#date_mass').val();
  const classes = $('#class_id2').val() || [];
  const filterType = $('#bulk_filter').val(); // 'all', 'boarding', 'day', 'class'
  
  // Get student category properly
  let studentCategory = $('#bulk_filter option:selected').text().trim();
  if(filterType === 'class' && classes.length > 0) {
    let classNames = [];
    $('#class_id2 option:selected').each(function() {
      classNames.push($(this).text().trim());
    });
    studentCategory = 'By Class: ' + classNames.join(', ');
  }
  
  const addToBillHistory = $('#add_to_class_bill').is(':checked');
  const studentCount = $('input[name="student_id[]"]:checked').length;
  
  // Get bill item titles and amounts
  let billItemTitles = [];
  let billItemsSimple = [];
  item_ids.forEach(function(id) {
    const title = $('#' + id + '_title').val();
    const amount = parseFloat($('#' + id + '_amount').val()) || 0;
    if(title) {
      billItemTitles.push(title);
      billItemsSimple.push({title: title, amount: amount});
    }
  });
  
  // If "By Class", get per-class breakdown via AJAX
  if(filterType === 'class' && classes.length > 0) {
    $.ajax({
      url: '<?php echo site_url('admin/get_invoice_preview_data'); ?>',
      type: 'POST',
      data: { class_ids: classes, bill_items: billItemTitles },
      dataType: 'json',
      success: function(response) {
        if(response.success) {
          displayConfirmationModal(response.classes, term, year, date, studentCategory, studentCount, addToBillHistory, callback, 'class');
        } else {
          showAjaxModal_alert('Failed to load invoice preview', 'error');
        }
      },
      error: function() {
        showAjaxModal_alert('Error loading invoice preview', 'error');
      }
    });
  } else {
    // For "All Students", "Boarding", "Day" - show simple list
    displayConfirmationModal(billItemsSimple, term, year, date, studentCategory, studentCount, addToBillHistory, callback, 'simple');
  }
}

function displayConfirmationModal(data, term, year, date, studentCategory, studentCount, addToBillHistory, callback, viewMode) {
  let billItemsHTML = '';
  
  if(viewMode === 'class') {
    // Per-class breakdown
    let classesData = data;
    classesData.forEach(function(classInfo) {
      let classTotal = 0;
      billItemsHTML += `<div class="info-section">
        <h4><i class="fa fa-graduation-cap"></i>${classInfo.class_name}</h4>
        <table class="items-table"><thead><tr><th>#</th><th>Item</th><th>Amount</th></tr></thead><tbody>`;
      
      classInfo.items.forEach(function(item, index) {
        classTotal += item.amount;
        billItemsHTML += `<tr><td>${index + 1}</td><td>${item.title}</td><td>${item.amount.toFixed(2)}</td></tr>`;
      });
      
      billItemsHTML += `<tr class="total-row"><td colspan="2">CLASS TOTAL:</td><td>GHC ${classTotal.toFixed(2)}</td></tr>
        </tbody></table></div>`;
    });
    
    billItemsHTML += `<div class="note">Each student will be billed only items applicable to their class.</div>`;
    
  } else {
    // Simple list
    let billItems = data;
    let grandTotal = 0;
    billItemsHTML += `<div class="info-section">
      <h4><i class="fa fa-file-invoice-dollar"></i>Selected Bill Items</h4>
      <table class="items-table"><thead><tr><th>#</th><th>Item</th><th>Amount</th></tr></thead><tbody>`;
    
    billItems.forEach(function(item, index) {
      grandTotal += item.amount;
      billItemsHTML += `<tr><td>${index + 1}</td><td>${item.title}</td><td>${item.amount.toFixed(2)}</td></tr>`;
    });
    
    billItemsHTML += `<tr class="total-row"><td colspan="2">TOTAL (Per Student):</td><td>GHC ${grandTotal.toFixed(2)}</td></tr>
      </tbody></table></div>`;
    
    billItemsHTML += `<div class="note">Some items may not apply to all students based on class assignments.</div>`;
  }
  
  // Build and show modal with billItemsHTML
  // ... (modal HTML construction and display code)
}

/**
 * BACKEND ENDPOINT: Admin.php -> get_invoice_preview_data()
 * 
 * Input: 
 *   - class_ids[] (array of class IDs)
 *   - bill_items[] (array of bill item titles)
 * 
 * Output:
 *   {
 *     "success": true,
 *     "classes": [
 *       {
 *         "class_id": "31",
 *         "class_name": "BASIC 8 A",
 *         "items": [
 *           {"title": "PTA", "description": "...", "amount": 20.00},
 *           {"title": "EXAM FEES", "description": "...", "amount": 60.00}
 *         ]
 *       }
 *     ]
 *   }
 * 
 * Logic:
 * 1. For each class, get class name + section name (always)
 * 2. For each bill item, apply 3-tier filtering:
 *    - Priority 1: specific_class_ids matches class_id
 *    - Priority 2: class_category matches class category
 *    - Priority 3: Both NULL/empty (global item)
 * 3. Return only applicable items per class
 */
