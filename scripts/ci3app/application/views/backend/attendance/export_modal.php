<style>
.form-label { font-weight: 600; color: #374151; font-size: 13px; margin-bottom: 8px; display: block; }
.form-control { border: 2px solid #e5e7eb; border-radius: 10px; padding: 10px 14px; font-size: 14px; transition: all 0.3s; height: 42px; width: 100%; }
.form-control:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1); outline: none; }
.btn-modern { padding: 10px 20px; border: none; border-radius: 10px; font-weight: 600; font-size: 14px; cursor: pointer; transition: all 0.3s; display: inline-flex; align-items: center; gap: 8px; height: 42px; }
.btn-success-modern { background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3); }
.btn-success-modern:hover { background: linear-gradient(135deg, #059669 0%, #047857 100%); box-shadow: 0 6px 16px rgba(16, 185, 129, 0.4); transform: translateY(-2px); }
</style>

<div style="padding: 20px;">
    <div class="row">
        <div class="col-md-4">
            <label class="form-label"><?php echo get_phrase('export_format'); ?></label>
            <select id="export_format" class="form-control">
                <option value="excel">Excel (.xlsx)</option>
                <option value="pdf">PDF</option>
                <option value="csv">CSV</option>
            </select>
        </div>
        
        <div class="col-md-4">
            <label class="form-label"><?php echo get_phrase('start_date'); ?></label>
            <input type="date" id="export_start_date" class="form-control" value="<?php echo date('Y-m-01'); ?>">
        </div>
        
        <div class="col-md-4">
            <label class="form-label"><?php echo get_phrase('end_date'); ?></label>
            <input type="date" id="export_end_date" class="form-control" value="<?php echo date('Y-m-d'); ?>">
        </div>
    </div>
    
    <div class="row" style="margin-top: 20px;">
        <div class="col-md-12">
            <button onclick="exportAttendance()" class="btn-modern btn-success-modern">
                <i class="fa fa-download"></i> <?php echo get_phrase('export'); ?>
            </button>
        </div>
    </div>
</div>

<script>
function exportAttendance() {
    const format = $('#export_format').val();
    const startDate = $('#export_start_date').val();
    const endDate = $('#export_end_date').val();
    
    window.open('<?php echo site_url('admin/export_attendance'); ?>?format=' + format + '&start=' + startDate + '&end=' + endDate, '_blank');
    $('.close').click();
}
</script>
