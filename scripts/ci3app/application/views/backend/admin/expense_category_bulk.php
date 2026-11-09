<style>
.bulk-header { background:linear-gradient(135deg, #dc2626 0%, #991b1b 100%); color:white; padding:20px; border-radius:8px 8px 0 0; margin:-20px -20px 20px -20px; }
.bulk-header h3 { margin:0; font-size:20px; display:flex; align-items:center; gap:12px; }
.bulk-header p { margin:8px 0 0 0; font-size:13px; opacity:0.9; }
.bulk-table { width:100%; border-collapse:collapse; }
.bulk-table thead { background:#f9fafb; }
.bulk-table th { padding:12px; text-align:left; font-size:13px; font-weight:600; color:#374151; border-bottom:2px solid #e5e7eb; }
.bulk-table td { padding:12px; border-bottom:1px solid #e5e7eb; }
.bulk-table input, .bulk-table textarea { width:100%; padding:8px 12px; border:1px solid #d1d5db; border-radius:6px; font-size:14px; }
.bulk-table input:focus, .bulk-table textarea:focus { outline:none; border-color:#dc2626; }
.icon-select { width:50px; height:50px; border:2px solid #e5e7eb; border-radius:8px; display:flex; align-items:center; justify-content:center; cursor:pointer; font-size:20px; transition:all 0.2s; }
.icon-select:hover { border-color:#dc2626; background:#fef2f2; }
.icon-select.active { border-color:#dc2626; background:#dc2626; color:white; }
.remove-btn { width:32px; height:32px; border:none; border-radius:6px; background:#fee2e2; color:#dc2626; cursor:pointer; transition:all 0.2s; }
.remove-btn:hover { background:#dc2626; color:white; }
.add-row-btn { width:100%; padding:12px; border:2px dashed #d1d5db; border-radius:8px; background:white; color:#6b7280; font-size:14px; font-weight:600; cursor:pointer; transition:all 0.2s; margin-top:12px; }
.add-row-btn:hover { border-color:#dc2626; color:#dc2626; background:#fef2f2; }
.action-bar { display:flex; justify-content:space-between; align-items:center; margin-top:20px; padding-top:20px; border-top:2px solid #e5e7eb; }
.btn { padding:10px 20px; border:none; border-radius:8px; font-size:14px; font-weight:600; cursor:pointer; transition:all 0.2s; display:inline-flex; align-items:center; gap:8px; }
.btn-primary { background:#dc2626; color:white; }
.btn-primary:hover { background:#b91c1c; }
.btn-secondary { background:#f3f4f6; color:#374151; }
.btn-secondary:hover { background:#e5e7eb; }
</style>

<div class="bulk-header">
    <h3><i class="fa fa-layer-group"></i> Bulk Create Categories</h3>
    <p>Add multiple expense categories at once. Click icons to select, fill in details, and save all together.</p>
</div>

<?php echo form_open('admin/bulk_create_categories', ['id' => 'bulkCategoryForm']); ?>
    <table class="bulk-table">
        <thead>
            <tr>
                <th style="width:60px;">Icon</th>
                <th style="width:200px;">Category Name *</th>
                <th>Description</th>
                <th style="width:50px;"></th>
            </tr>
        </thead>
        <tbody id="categoryRows">
            <tr class="category-row">
                <td>
                    <div class="icon-select active" onclick="showIconPicker(this)" data-icon="folder">
                        <i class="fa fa-folder"></i>
                    </div>
                </td>
                <td><input type="text" name="categories[0][name]" placeholder="e.g., Utilities" required></td>
                <td><textarea name="categories[0][description]" rows="1" placeholder="Optional description"></textarea></td>
                <td><button type="button" class="remove-btn" onclick="removeRow(this)" disabled><i class="fa fa-times"></i></button></td>
            </tr>
        </tbody>
    </table>
    
    <button type="button" class="add-row-btn" onclick="addRow()">
        <i class="fa fa-plus"></i> Add Another Category
    </button>
    
    <div class="action-bar">
        <div style="color:#6b7280; font-size:13px;">
            <i class="fa fa-info-circle"></i> <span id="rowCount">1</span> category ready to create
        </div>
        <div>
            <button type="button" class="btn btn-secondary" onclick="$('.close')[0].click()">
                <i class="fa fa-times"></i> Cancel
            </button>
            <button type="submit" class="btn btn-primary">
                <i class="fa fa-save"></i> Create All Categories
            </button>
        </div>
    </div>
<?php echo form_close(); ?>

<!-- Icon Picker Modal -->
<div id="iconPickerModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:9999; align-items:center; justify-content:center;">
    <div style="background:white; border-radius:12px; padding:24px; max-width:500px; width:90%;">
        <h4 style="margin:0 0 16px 0; font-size:18px;">Select Icon</h4>
        <div style="display:grid; grid-template-columns:repeat(8, 1fr); gap:8px; margin-bottom:20px;" id="iconGrid"></div>
        <button type="button" class="btn btn-secondary" onclick="closeIconPicker()" style="width:100%;">Close</button>
    </div>
</div>

<script>
let rowIndex = 1;
let currentIconTarget = null;
const icons = ['folder', 'utensils', 'bus', 'book', 'lightbulb', 'tools', 'building', 'users', 'clipboard', 'chart-line', 'cog', 'graduation-cap', 'laptop', 'phone', 'wifi', 'print', 'home', 'car', 'shopping-cart', 'heart', 'star', 'flag', 'bell', 'envelope'];

function addRow() {
    const html = `
        <tr class="category-row">
            <td>
                <div class="icon-select active" onclick="showIconPicker(this)" data-icon="folder">
                    <i class="fa fa-folder"></i>
                </div>
            </td>
            <td><input type="text" name="categories[${rowIndex}][name]" placeholder="e.g., Utilities" required></td>
            <td><textarea name="categories[${rowIndex}][description]" rows="1" placeholder="Optional description"></textarea></td>
            <td><button type="button" class="remove-btn" onclick="removeRow(this)"><i class="fa fa-times"></i></button></td>
        </tr>
    `;
    $('#categoryRows').append(html);
    rowIndex++;
    updateRowCount();
}

function removeRow(btn) {
    $(btn).closest('tr').remove();
    updateRowCount();
    updateRemoveButtons();
}

function updateRowCount() {
    const count = $('.category-row').length;
    $('#rowCount').text(count);
    updateRemoveButtons();
}

function updateRemoveButtons() {
    const count = $('.category-row').length;
    $('.remove-btn').prop('disabled', count === 1);
}

function showIconPicker(element) {
    currentIconTarget = element;
    let html = '';
    icons.forEach(icon => {
        html += `<div class="icon-select" onclick="selectIcon('${icon}')" style="margin:0;"><i class="fa fa-${icon}"></i></div>`;
    });
    $('#iconGrid').html(html);
    $('#iconPickerModal').css('display', 'flex');
}

function selectIcon(icon) {
    if(currentIconTarget) {
        $(currentIconTarget).html(`<i class="fa fa-${icon}"></i>`).attr('data-icon', icon);
    }
    closeIconPicker();
}

function closeIconPicker() {
    $('#iconPickerModal').hide();
    currentIconTarget = null;
}

$('#bulkCategoryForm').submit(function(e) {
    e.preventDefault();
    
    const categories = [];
    $('.category-row').each(function() {
        const icon = $(this).find('.icon-select').attr('data-icon');
        const name = $(this).find('input[type="text"]').val();
        const description = $(this).find('textarea').val();
        if(name) categories.push({ name, description, icon });
    });
    
    $.ajax({
        url: '<?php echo site_url("admin/bulk_create_categories"); ?>',
        type: 'POST',
        data: { 
            categories: JSON.stringify(categories),
            <?php echo $this->security->get_csrf_token_name(); ?>: '<?php echo $this->security->get_csrf_hash(); ?>'
        },
        dataType: 'json'
    }).done(function(response) {
        if(response.status === 'success') {
            $('.modal').modal('hide');
            $('.modal-backdrop').remove();
            $('body').removeClass('modal-open');
            showAjaxModal_alert(response.message, 'success', false);
            setTimeout(() => location.reload(), 100);
        } else {
            showAjaxModal_alert(response.message, 'error');
        }
    }).fail(function() {
        showAjaxModal_alert('An error occurred', 'error');
    });
});

$(document).ready(function() {
    updateRemoveButtons();
});
</script>
