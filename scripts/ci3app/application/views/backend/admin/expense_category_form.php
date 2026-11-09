<style>
.form-group { margin-bottom:20px; }
.form-label { display:block; font-size:14px; font-weight:600; color:#374151; margin-bottom:8px; }
.form-control { width:100%; padding:10px 14px; border:1px solid #d1d5db; border-radius:8px; font-size:14px; }
.form-control:focus { outline:none; border-color:#dc2626; }
.icon-grid { display:grid; grid-template-columns:repeat(8, 1fr); gap:8px; margin-top:8px; }
.icon-option { width:40px; height:40px; border:2px solid #e5e7eb; border-radius:8px; display:flex; align-items:center; justify-content:center; cursor:pointer; transition:all 0.2s; }
.icon-option:hover { border-color:#dc2626; background:#fef2f2; }
.icon-option.selected { border-color:#dc2626; background:#dc2626; color:white; }
</style>

<?php echo form_open('admin/save_expense_category', ['id' => 'categoryForm']); ?>
    <input type="hidden" name="expense_category_id" value="<?php echo $category ? $category->expense_category_id : ''; ?>">
    
    <div class="form-group">
        <label class="form-label">Category Name *</label>
        <input type="text" name="name" class="form-control" value="<?php echo $category ? $category->name : ''; ?>" required>
    </div>
    
    <div class="form-group">
        <label class="form-label">Description</label>
        <textarea name="description" class="form-control" rows="3"><?php echo $category ? $category->description : ''; ?></textarea>
    </div>
    
    <div class="form-group">
        <label class="form-label">Icon</label>
        <input type="hidden" name="icon" id="selectedIcon" value="<?php echo $category ? $category->icon : 'folder'; ?>">
        <div class="icon-grid">
            <?php 
            $icons = ['folder', 'utensils', 'bus', 'book', 'lightbulb', 'tools', 'building', 'users', 'clipboard', 'chart-line', 'cog', 'graduation-cap', 'laptop', 'phone', 'wifi', 'print'];
            foreach($icons as $icon): 
            ?>
            <div class="icon-option <?php echo ($category && $category->icon == $icon) || (!$category && $icon == 'folder') ? 'selected' : ''; ?>" onclick="selectIcon('<?php echo $icon; ?>', this)">
                <i class="fa fa-<?php echo $icon; ?>"></i>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <div style="text-align:right; margin-top:24px;">
        <button type="submit" class="btn btn-primary">
            <i class="fa fa-save"></i> <?php echo $category ? 'Update' : 'Create'; ?> Category
        </button>
    </div>
<?php echo form_close(); ?>

<script>
function selectIcon(icon, element) {
    $('.icon-option').removeClass('selected');
    $(element).addClass('selected');
    $('#selectedIcon').val(icon);
}

$('#categoryForm').submit(function(e) {
    e.preventDefault();
    
    $.ajax({
        url: $(this).attr('action'),
        type: 'POST',
        data: $(this).serialize(),
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
</script>
