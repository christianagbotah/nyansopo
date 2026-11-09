// ============================================
// AJAX PAGE REFRESH WITH TAB PERSISTENCE
// ============================================

// Store current active tab
let currentActiveTab = 'bill_item'; // default

// Track active tab changes
$('.nav-tabs a[data-toggle="tab"]').on('shown.bs.tab', function(e) {
    currentActiveTab = $(e.target).attr('href').substring(1);
});

// Refresh page content via AJAX
function refreshPageContent(callback) {
    const activeTab = currentActiveTab;
    
    $.ajax({
        url: '<?php echo site_url('admin/student_payment_content'); ?>',
        type: 'GET',
        dataType: 'html',
        success: function(response) {
            // Replace main content
            $('#page_content_wrapper').html(response);
            
            // Reactivate the tab
            $('.nav-tabs a[href="#' + activeTab + '"]').tab('show');
            
            // Reinitialize any necessary components
            if(typeof callback === 'function') {
                callback();
            }
            
            // Reinitialize select2
            $('.select2').select2();
        },
        error: function() {
            showAjaxModal_alert('Failed to refresh page', 'error');
        }
    });
}

// Use after successful operations
// Example: After adding bill item
$('#add_bill_item_form').submit(function(ev) {
    ev.preventDefault();
    
    // ... validation code ...
    
    showAjaxModal_alert('Adding bill item, please wait...', 'loading');
    $.ajax({
        url: '<?=site_url('admin/invoice/add_bill_item/'); ?>',
        type: 'post',
        dataType: 'json',
        data: {
            'title': title,
            'desc': desc,
            'category': category,
            'amount': amount,
        }
    })
    .done(function(response) {
        if(response.success == 1) {
            showAjaxModal_alert('Item added successfully.', 'success', false);
            setTimeout(() => {
                $('.close').click();
                refreshPageContent(); // Refresh with active tab
            }, 2000);
        } else {
            showAjaxModal_alert(response.message, 'error');
        }
    });
});
