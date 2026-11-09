<style>
.preview-container { max-width: 1200px; margin: 20px auto; padding: 20px; padding-bottom: 100px; }
.preview-item { background: white; padding: 20px; margin-bottom: 15px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
.preview-phone { font-weight: 600; color: #1a202c; font-size: 16px; margin-bottom: 10px; }
.preview-phone i { font-size: 12px; margin-right: 5px; }
.no-print { display: inline; }
.preview-message { color: #4b5563; line-height: 1.6; padding: 15px; background: #f9fafb; border-radius: 6px; border-left: 4px solid #10b981; }
.preview-divider { border: 0; border-top: 2px solid #e5e7eb; margin: 20px 0; }
.preview-header { text-align: center; margin-bottom: 30px; }
.preview-count { background: #10b981; color: white; padding: 8px 16px; border-radius: 20px; display: inline-block; font-weight: 600; }
.action-buttons { position: fixed; bottom: 0; right: 0; left: 260px; display: flex; justify-content: space-between; align-items: center; gap: 10px; padding: 16px 24px; background: white; border-top: 3px solid <?php echo get_settings('theme_color') ?: '#667eea'; ?>; box-shadow: 0 -2px 10px rgba(0,0,0,0.1); z-index: 1000; }
@media (max-width: 768px) { .action-buttons { left: 0; flex-direction: column; gap: 12px; } .filter-input { width: 100%; } }
.filter-section { display: flex; align-items: center; gap: 10px; }
.filter-input { padding: 8px 12px; border: 1px solid #e5e7eb; border-radius: 6px; font-size: 14px; width: 300px; transition: all 0.2s; }
.filter-input:focus { outline: none; border-color: <?php echo get_settings('theme_color') ?: '#667eea'; ?>; box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1); }
.buttons-section { display: flex; gap: 10px; }
.btn-action { padding: 10px 20px; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; transition: all 0.2s ease; font-size: 14px; box-shadow: 0 2px 8px rgba(0,0,0,0.15); display: inline-flex; align-items: center; gap: 8px; }
.btn-print { background: #3b82f6; color: white; }
.btn-send { background: #10b981; color: white; }
.btn-action:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.25); }
.btn-action:active { transform: translateY(0); }
@media print { 
    .action-buttons { display: none !important; }
    .navbar, .sidebar-menu, #sidebar, .page-sidebar, header, .header, nav, .nav, .breadcrumb { display: none !important; }
    body { margin: 0; padding: 0; }
    .preview-container { max-width: 100%; margin: 0; padding: 20px; padding-bottom: 20px; }
    .preview-item { box-shadow: none; border: 1px solid #e5e7eb; page-break-inside: avoid; }
    .preview-header { margin-bottom: 20px; }
    .no-print { display: none !important; }
}
</style>

<div class="preview-container">
    <div class="preview-header">
        <h2><i class="fa fa-bell no-print" style="font-size: 20px;"></i> Bill Reminder Preview</h2>
        <p style="color: #6b7280; margin-top: 10px;">Review messages before sending</p>
        <div class="preview-count">
            <?php echo count($messages); ?> parent(s) will receive SMS
        </div>
        <div id="smsInfo" style="margin-top: 15px; display: flex; gap: 15px; justify-content: center; flex-wrap: wrap;">
            <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 12px 20px; border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">
                <div style="font-size: 12px; opacity: 0.9;">SMS Balance</div>
                <div id="smsBalance" style="font-size: 20px; font-weight: 700;">Loading...</div>
            </div>
            <div style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; padding: 12px 20px; border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">
                <div style="font-size: 12px; opacity: 0.9;">Estimated Cost</div>
                <div id="smsCost" style="font-size: 20px; font-weight: 700;">Loading...</div>
            </div>
            <div style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); color: white; padding: 12px 20px; border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">
                <div style="font-size: 12px; opacity: 0.9;">SMS Pages</div>
                <div id="smsPages" style="font-size: 20px; font-weight: 700;">Loading...</div>
            </div>
        </div>
    </div>

    <div class="action-buttons">
        <div class="filter-section">
            <i class="fa fa-search" style="color: #9ca3af; font-size: 12px;"></i>
            <input type="text" id="filterInput" class="filter-input" placeholder="Search by parent name or message..." onkeyup="filterMessages()">
        </div>
        <div class="buttons-section">
            <button class="btn-action btn-print" onclick="printPreview()">
                <i class="fa fa-print" style="font-size: 12px;"></i> Print
            </button>
            <button class="btn-action" style="background: #f59e0b; color: white;" onclick="showTestModal()">
                <i class="fa fa-flask" style="font-size: 12px;"></i> Test SMS
            </button>
            <button class="btn-action btn-send" onclick="sendSMSNow()">
                <i class="fa fa-paper-plane" style="font-size: 12px;"></i> Send SMS
            </button>
        </div>
    </div>

    <?php foreach ($messages as $index => $preview): ?>
        <div class="preview-item" data-parent="<?php echo strtolower($preview['parent_name']); ?>" data-message="<?php echo strtolower($preview['message']); ?>">
            <div class="preview-phone">
                <i class="fa fa-user no-print"></i> <?php echo $preview['parent_name']; ?> - 
                <i class="fa fa-phone no-print"></i> <?php echo $preview['phone']; ?>
            </div>
            <div class="preview-message">
                <?php echo $preview['message']; ?>
            </div>
        </div>
        <?php if ($index < count($messages) - 1): ?>
            <hr class="preview-divider">
        <?php endif; ?>
    <?php endforeach; ?>
</div>

<script>
// Load SMS bundle info on page load
$(document).ready(function() {
    loadSMSInfo();
});

function loadSMSInfo() {
    const messages = <?php echo json_encode(array_column($messages, 'message')); ?>;
    
    $.ajax({
        url: '<?php echo site_url("admin/get_sms_bundle_info"); ?>',
        type: 'POST',
        data: {
            message_count: <?php echo count($messages); ?>,
            messages: messages,
            '<?php echo $this->security->get_csrf_token_name(); ?>': '<?php echo $this->security->get_csrf_hash(); ?>'
        },
        dataType: 'json'
    }).done(function(response) {
        if(response.balance && response.balance.Balance !== undefined) {
            $('#smsBalance').text('GHS ' + parseFloat(response.balance.Balance).toFixed(2));
        } else {
            $('#smsBalance').text('N/A');
        }
        
        if(response.cost) {
            $('#smsCost').text('GHS ' + response.cost.total_cost.toFixed(2));
            $('#smsPages').text(response.cost.total_pages + ' pages');
        }
    }).fail(function() {
        $('#smsBalance').text('Error');
        $('#smsCost').text('Error');
        $('#smsPages').text('Error');
    });
}

function filterMessages() {
    const filter = document.getElementById('filterInput').value.toLowerCase();
    const items = document.querySelectorAll('.preview-item');
    
    items.forEach(item => {
        const parentText = item.getAttribute('data-parent');
        const messageText = item.getAttribute('data-message');
        const searchText = parentText + ' ' + messageText;
        
        if (searchText.includes(filter)) {
            item.style.display = 'block';
            item.nextElementSibling?.classList.contains('preview-divider') && (item.nextElementSibling.style.display = 'block');
        } else {
            item.style.display = 'none';
            item.nextElementSibling?.classList.contains('preview-divider') && (item.nextElementSibling.style.display = 'none');
        }
    });
}

function printPreview() {
    // Open new window with only the content
    const printWindow = window.open('', '_blank');
    const content = document.querySelector('.preview-container').cloneNode(true);
    
    // Remove action buttons from cloned content
    const actionButtons = content.querySelector('.action-buttons');
    if(actionButtons) actionButtons.remove();
    
    printWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>Bill Reminder Preview</title>
            <link rel="stylesheet" href="<?php echo base_url(); ?>assets/cdn/css/font-awesome-6.4.0.min.css">
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                .preview-container { max-width: 100%; margin: 0; padding: 0; }
                .preview-item { background: white; padding: 20px; margin-bottom: 15px; border: 1px solid #e5e7eb; page-break-inside: avoid; }
                .preview-phone { font-weight: 600; color: #1a202c; font-size: 14px; margin-bottom: 10px; }
                .no-print { display: inline; }
                .preview-message { color: #4b5563; line-height: 1.6; padding: 15px; background: #f9fafb; border-radius: 6px; border-left: 4px solid #10b981; }
                .preview-divider { border: 0; border-top: 1px solid #e5e7eb; margin: 15px 0; }
                .preview-header { text-align: center; margin-bottom: 30px; }
                .preview-header h2 { margin: 0 0 10px 0; }
                @media print { .no-print { display: none !important; } }
                .preview-header p { margin: 5px 0; color: #6b7280; }
                .preview-count { background: #10b981; color: white; padding: 8px 16px; border-radius: 20px; display: inline-block; font-weight: 600; }
                @media print { body { margin: 0; } i.fa { display: none !important; } }
            </style>
        </head>
        <body>
            ${content.innerHTML}
        </body>
        </html>
    `);
    printWindow.document.close();
    printWindow.focus();
    setTimeout(() => {
        printWindow.print();
        printWindow.close();
    }, 250);
}

function sendSMSNow() {
    showConfirmModal(
        'Send Bill Reminders',
        'This will send SMS to all <?php echo count($messages); ?> parent(s). Continue?',
        function() {
            showAjaxModal_alert('Sending SMS...', 'loading');
            $.ajax({
                url: '<?php echo site_url("admin/send_bill_reminder_now"); ?>',
                type: 'POST',
                data: {
                    '<?php echo $this->security->get_csrf_token_name(); ?>': '<?php echo $this->security->get_csrf_hash(); ?>'
                },
                dataType: 'json'
            }).done(function(response) {
                if(response.status === 'success') {
                    showAjaxModal_alert(response.message, 'success', false);
                } else {
                    showAjaxModal_alert(response.message, 'error');
                }
            }).fail(function() {
                showAjaxModal_alert('An error occurred while sending SMS', 'error');
            });
        },
        'Send Now',
        'success'
    );
}

function showTestModal() {
    const sampleMessage = <?php echo json_encode($messages[0]['message'] ?? ''); ?>;
    
    showModalWithContent('createModal', 
        '<i class="fa fa-flask"></i> Test SMS',
        `<style>
            .phone-tags-container {
                display: flex;
                flex-wrap: wrap;
                gap: 8px;
                padding: 12px;
                border: 2px solid #e5e7eb;
                border-radius: 8px;
                min-height: 60px;
                background: white;
                cursor: text;
                transition: border-color 0.2s;
            }
            .phone-tags-container:focus-within {
                border-color: #667eea;
                box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            }
            .phone-tag {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                padding: 6px 12px;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                border-radius: 6px;
                font-size: 14px;
                font-weight: 500;
                animation: tagSlideIn 0.2s ease;
            }
            @keyframes tagSlideIn {
                from { opacity: 0; transform: scale(0.8); }
                to { opacity: 1; transform: scale(1); }
            }
            .phone-tag-remove {
                cursor: pointer;
                font-size: 16px;
                line-height: 1;
                opacity: 0.8;
                transition: opacity 0.2s;
            }
            .phone-tag-remove:hover {
                opacity: 1;
            }
            .phone-input {
                flex: 1;
                min-width: 150px;
                border: none;
                outline: none;
                font-size: 14px;
                padding: 6px;
            }
            .phone-count {
                color: #6b7280;
                font-size: 13px;
                margin-top: 8px;
            }
        </style>
        <div style="padding: 10px;">
            <p style="margin-bottom: 20px; color: #6b7280;">Enter phone numbers and press Enter or comma to add:</p>
            <div class="form-group">
                <label style="font-weight: 600; margin-bottom: 8px; display: block;">Test Phone Numbers</label>
                <div class="phone-tags-container" id="phoneTagsContainer" onclick="document.getElementById('phoneInput').focus()">
                    <input type="text" id="phoneInput" class="phone-input" placeholder="Type number and press Enter...">
                </div>
                <div class="phone-count" id="phoneCount">0 numbers added</div>
            </div>
            <div class="form-group" style="margin-top: 20px;">
                <label style="font-weight: 600; margin-bottom: 8px; display: block;">Sample Message</label>
                <div style="padding: 15px; background: #f9fafb; border-radius: 6px; border-left: 4px solid #f59e0b; color: #4b5563; line-height: 1.6; max-height: 200px; overflow-y: auto;">
                    ${sampleMessage}
                </div>
            </div>
            <div style="margin-top: 25px; display: flex; gap: 10px; justify-content: flex-end;">
                <button class="btn btn-default" onclick="$('#createModal').modal('hide')" style="padding: 10px 20px;">Cancel</button>
                <button class="btn btn-warning" onclick="sendTestSMS()" style="padding: 10px 20px; background: #f59e0b; border-color: #f59e0b;">
                    <i class="fa fa-paper-plane"></i> Send Test
                </button>
            </div>
        </div>`
    );
    
    // Initialize tag input after modal is shown
    setTimeout(() => {
        const phones = [];
        const input = document.getElementById('phoneInput');
        const container = document.getElementById('phoneTagsContainer');
        const count = document.getElementById('phoneCount');
        
        function updateCount() {
            count.textContent = phones.length + ' number' + (phones.length !== 1 ? 's' : '') + ' added';
        }
        
        function addPhone(phone) {
            phone = phone.trim();
            if (!phone || phones.includes(phone)) return;
            
            phones.push(phone);
            
            const tag = document.createElement('span');
            tag.className = 'phone-tag';
            tag.innerHTML = `${phone} <span class="phone-tag-remove" onclick="this.parentElement.remove(); window.removePhone('${phone}')">&times;</span>`;
            
            container.insertBefore(tag, input);
            input.value = '';
            updateCount();
        }
        
        window.removePhone = function(phone) {
            const index = phones.indexOf(phone);
            if (index > -1) phones.splice(index, 1);
            updateCount();
        };
        
        window.getTestPhones = function() {
            return phones.join(',');
        };
        
        input.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ',') {
                e.preventDefault();
                addPhone(input.value.replace(',', ''));
            } else if (e.key === 'Backspace' && !input.value && phones.length > 0) {
                const lastTag = container.querySelector('.phone-tag:last-of-type');
                if (lastTag) {
                    const phone = lastTag.textContent.trim().replace('×', '').trim();
                    window.removePhone(phone);
                    lastTag.remove();
                }
            }
        });
        
        input.addEventListener('blur', () => {
            if (input.value.trim()) {
                addPhone(input.value);
            }
        });
    }, 100);
}

function sendTestSMS() {
    const phones = window.getTestPhones ? window.getTestPhones() : '';
    
    if(!phones) {
        showAjaxModal_alert('Please add at least one phone number', 'error');
        return;
    }
    
    $('#createModal').modal('hide');
    showAjaxModal_alert('Sending test SMS...', 'loading');
    
    $.ajax({
        url: '<?php echo site_url("admin/send_test_bill_reminder"); ?>',
        type: 'POST',
        data: {
            test_phones: phones,
            '<?php echo $this->security->get_csrf_token_name(); ?>': '<?php echo $this->security->get_csrf_hash(); ?>'
        },
        dataType: 'json'
    }).done(function(response) {
        if(response.status === 'success') {
            showAjaxModal_alert(response.message, 'success', false);
        } else {
            showAjaxModal_alert(response.message, 'error');
        }
    }).fail(function() {
        showAjaxModal_alert('An error occurred while sending test SMS', 'error');
    });
}
</script>
