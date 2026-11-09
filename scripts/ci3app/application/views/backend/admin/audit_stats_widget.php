<style>
.audit-stat-card {
    background: white;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.08);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    border-left: 4px solid;
    position: relative;
    overflow: hidden;
}
.audit-stat-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 24px rgba(0,0,0,0.12);
}
.audit-stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    right: 0;
    width: 100px;
    height: 100px;
    opacity: 0.05;
    border-radius: 50%;
    transform: translate(30%, -30%);
}
.stat-lock { border-left-color: #ef4444; }
.stat-lock::before { background: #ef4444; }
.stat-unlock { border-left-color: #10b981; }
.stat-unlock::before { background: #10b981; }
.stat-edit { border-left-color: #3b82f6; }
.stat-edit::before { background: #3b82f6; }
.stat-today { border-left-color: #8b5cf6; }
.stat-today::before { background: #8b5cf6; }
.stat-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    margin-bottom: 16px;
}
.stat-lock .stat-icon { background: #fee2e2; color: #dc2626; }
.stat-unlock .stat-icon { background: #d1fae5; color: #059669; }
.stat-edit .stat-icon { background: #dbeafe; color: #2563eb; }
.stat-today .stat-icon { background: #ede9fe; color: #7c3aed; }
.stat-value {
    font-size: 32px;
    font-weight: 700;
    line-height: 1;
    margin-bottom: 8px;
    color: #1f2937;
}
.stat-label {
    font-size: 13px;
    font-weight: 500;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.stat-skeleton {
    background: linear-gradient(90deg, #f3f4f6 25%, #e5e7eb 50%, #f3f4f6 75%);
    background-size: 200% 100%;
    animation: shimmer 1.5s infinite;
    border-radius: 4px;
    height: 32px;
    width: 80px;
}
@keyframes shimmer {
    0% { background-position: 200% 0; }
    100% { background-position: -200% 0; }
}
</style>

<div class="row" style="margin-bottom: 24px;">
    <div class="col-md-3">
        <div class="audit-stat-card stat-lock">
            <div class="stat-icon"><i class="fa fa-lock"></i></div>
            <div class="stat-value" id="total_locks"><div class="stat-skeleton"></div></div>
            <div class="stat-label"><?php echo get_phrase('total_locks'); ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="audit-stat-card stat-unlock">
            <div class="stat-icon"><i class="fa fa-unlock"></i></div>
            <div class="stat-value" id="total_unlocks"><div class="stat-skeleton"></div></div>
            <div class="stat-label"><?php echo get_phrase('total_unlocks'); ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="audit-stat-card stat-edit">
            <div class="stat-icon"><i class="fa fa-edit"></i></div>
            <div class="stat-value" id="total_edits"><div class="stat-skeleton"></div></div>
            <div class="stat-label"><?php echo get_phrase('total_edits'); ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="audit-stat-card stat-today">
            <div class="stat-icon"><i class="fa fa-clock-o"></i></div>
            <div class="stat-value" id="today_actions"><div class="stat-skeleton"></div></div>
            <div class="stat-label"><?php echo get_phrase('today_actions'); ?></div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $.ajax({
        url: '<?php echo site_url('admin/get_audit_stats'); ?>',
        type: 'GET',
        dataType: 'json',
        success: function(data) {
            animateValue('total_locks', 0, data.total_locks, 800);
            animateValue('total_unlocks', 0, data.total_unlocks, 800);
            animateValue('total_edits', 0, data.total_edits, 800);
            animateValue('today_actions', 0, data.today_actions, 800);
        },
        error: function() {
            $('.stat-value').html('<span style="color:#ef4444;font-size:14px;">Error</span>');
        }
    });
});

function animateValue(id, start, end, duration) {
    const obj = document.getElementById(id);
    const range = end - start;
    const increment = end > start ? 1 : -1;
    const stepTime = Math.abs(Math.floor(duration / range));
    let current = start;
    
    const timer = setInterval(function() {
        current += increment;
        obj.textContent = current.toLocaleString();
        if (current == end) clearInterval(timer);
    }, stepTime);
}
</script>
