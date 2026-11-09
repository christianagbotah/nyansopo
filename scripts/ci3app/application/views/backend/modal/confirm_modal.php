<style>
@keyframes modalFadeIn {
    from {
        opacity: 0;
        transform: scale(0.9);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}

@keyframes modalFadeOut {
    from {
        opacity: 1;
        transform: scale(1);
    }
    to {
        opacity: 0;
        transform: scale(0.9);
    }
}

.modal.fade .modal-dialog {
    transition: transform 0.3s ease-out, opacity 0.3s ease-out;
}

.modal.fade.in .modal-dialog {
    transform: scale(1);
    opacity: 1;
}

.modal-backdrop.fade {
    transition: opacity 0.2s ease;
}
</style>

<div class="modal fade" id="confirm_modal" tabindex="-1" role="dialog" style="z-index:999999!important;">
    <div class="modal-dialog" role="document" style="margin: 10vh auto; max-width: 420px; width: 90%;">
        <div class="modal-content" style="border-radius: 12px; border: none; box-shadow: 0 8px 32px rgba(0,0,0,0.12); overflow: hidden; animation: modalFadeIn 0.3s ease-out;">
            <!-- Icon & Close Section -->
            <div style="position: relative; padding: 24px 24px 16px; background: #fff;">
                <button type="button" class="close" data-dismiss="modal" style="position: absolute; right: 12px; top: 12px; color: #94a3b8; opacity: 1; font-size: 24px; font-weight: 300; width: 32px; height: 32px; border-radius: 6px; display: flex; align-items: center; justify-content: center; transition: all 0.2s ease; background: transparent;" onmouseover="this.style.background='#f1f5f9'; this.style.color='#475569'" onmouseout="this.style.background='transparent'; this.style.color='#94a3b8'">&times;</button>
                
                <!-- Icon -->
                <div id="confirm_modal_icon" style="width: 56px; height: 56px; margin: 0 auto 16px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 28px;">
                    <i class="entypo-help-circled" style="color: #f59e0b;"></i>
                </div>
                
                <!-- Title -->
                <h4 id="confirm_modal_title" style="margin: 0 0 12px; font-weight: 600; color: #0f172a; font-size: 18px; text-align: center; line-height: 1.3;">Confirm Action</h4>
                
                <!-- Message -->
                <div id="confirm_modal_message" style="color: #64748b; font-size: 14px; line-height: 1.6; text-align: center; max-height: 300px; overflow-y: auto;">Are you sure you want to proceed?</div>
            </div>
            
            <!-- Actions Footer -->
            <div style="padding: 16px 24px 24px; background: #fff; display: flex; gap: 10px; justify-content: center;">
                <button type="button" class="btn" data-dismiss="modal" style="flex: 1; max-width: 140px; padding: 10px 20px; border-radius: 8px; font-weight: 500; font-size: 14px; border: 1.5px solid #e2e8f0; background: #fff; color: #64748b; transition: all 0.2s ease; box-shadow: 0 1px 2px rgba(0,0,0,0.05);" onmouseover="this.style.borderColor='#cbd5e1'; this.style.background='#f8fafc'; this.style.transform='translateY(-1px)'; this.style.boxShadow='0 2px 4px rgba(0,0,0,0.08)'" onmouseout="this.style.borderColor='#e2e8f0'; this.style.background='#fff'; this.style.transform='translateY(0)'; this.style.boxShadow='0 1px 2px rgba(0,0,0,0.05)'"><?php echo get_phrase('cancel'); ?></button>
                
                <button type="button" class="btn btn-primary" id="confirm_modal_btn" style="flex: 1; max-width: 180px; padding: 10px 20px; border-radius: 8px; font-weight: 500; font-size: 14px; border: none; transition: all 0.2s ease; box-shadow: 0 2px 6px rgba(0,0,0,0.12); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" onmouseover="this.style.transform='translateY(-1px)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.18)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 6px rgba(0,0,0,0.12)'" title=""><?php echo get_phrase('confirm'); ?></button>
            </div>
        </div>
    </div>
</div>

<script>
var confirmCallback = null;

function showConfirmModal(title, message, callback, btnText, iconType) {
    // Clear previous callback
    confirmCallback = null;
    
    $('#confirm_modal_title').text(title || 'Confirm Action');
    $('#confirm_modal_message').html(message || 'Are you sure?');
    
    var buttonText = btnText || '<?php echo get_phrase('confirm'); ?>';
    $('#confirm_modal_btn').text(buttonText);
    $('#confirm_modal_btn').attr('title', buttonText);
    
    var iconHtml = '';
    var iconBg = '';
    var btnStyle = '';
    
    switch(iconType) {
        case 'warning':
            iconHtml = '<i class="entypo-attention" style="color: #f59e0b;"></i>';
            iconBg = 'background: #fef3c7;';
            btnStyle = 'background: #f59e0b; color: white;';
            break;
        case 'danger':
            iconHtml = '<i class="entypo-cancel-circled" style="color: #ef4444;"></i>';
            iconBg = 'background: #fee2e2;';
            btnStyle = 'background: #ef4444; color: white;';
            break;
        case 'info':
            iconHtml = '<i class="entypo-info-circled" style="color: #3b82f6;"></i>';
            iconBg = 'background: #dbeafe;';
            btnStyle = 'background: #3b82f6; color: white;';
            break;
        case 'success':
            iconHtml = '<i class="entypo-check" style="color: #10b981;"></i>';
            iconBg = 'background: #d1fae5;';
            btnStyle = 'background: #10b981; color: white;';
            break;
        default:
            iconHtml = '<i class="entypo-help-circled" style="color: #f59e0b;"></i>';
            iconBg = 'background: #fef3c7;';
            btnStyle = 'background: #667eea; color: white;';
    }
    
    // Update icon
    $('#confirm_modal_icon').html(iconHtml);
    $('#confirm_modal_icon').attr('style', $('#confirm_modal_icon').attr('style').split(';').filter(s => !s.includes('background')).join(';') + ';' + iconBg);
    
    // Update button style
    var currentStyle = $('#confirm_modal_btn').attr('style');
    var baseStyle = currentStyle.split(';').filter(s => !s.includes('background') && !s.includes('color')).join(';');
    $('#confirm_modal_btn').attr('style', baseStyle + ';' + btnStyle);
    
    // Set callback AFTER modal setup
    confirmCallback = callback;
    
    // Show modal
    $('#confirm_modal').modal('show');
}

// Single event handler (use .off().on() to prevent duplicates)
$('#confirm_modal_btn').off('click').on('click', function() {
    console.log('Confirm button clicked');
    console.log('Callback exists:', confirmCallback !== null);
    console.log('Callback type:', typeof confirmCallback);
    
    if (confirmCallback && typeof confirmCallback === 'function') {
        var cb = confirmCallback; // Store reference
        confirmCallback = null; // Clear immediately to prevent double-execution
        $('#confirm_modal').modal('hide');
        
        // Execute callback after modal starts hiding
        setTimeout(function() {
            cb();
        }, 100);
    } else {
        console.error('No valid callback function');
        $('#confirm_modal').modal('hide');
    }
});

$('#confirm_modal').on('hidden.bs.modal', function() {
    console.log('Modal hidden, clearing callback');
    // Don't clear callback here as it may have already been called
});
</script>
<style>
#confirm_modal{z-index:999999!important;}
#confirm_modal + .modal-backdrop{z-index:999998!important;}
</style>
