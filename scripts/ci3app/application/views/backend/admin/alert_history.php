<style>
.history-header{background:linear-gradient(135deg,#8b5cf6,#7c3aed);padding:2.5rem;border-radius:16px;margin-bottom:2rem;color:#fff;box-shadow:0 10px 40px rgba(139,92,246,0.25)}
.history-table{background:#fff;border-radius:16px;padding:1.5rem;box-shadow:0 4px 16px rgba(0,0,0,0.06)}
table{width:100%;border-collapse:collapse}
th{background:#f8fafc;padding:1rem;text-align:left;font-weight:700;color:#334155;border-bottom:2px solid #e2e8f0}
td{padding:1rem;border-bottom:1px solid #f1f5f9}
tr:hover{background:#f8fafc}
.badge{padding:0.375rem 0.875rem;border-radius:8px;font-size:0.8125rem;font-weight:700;text-transform:uppercase}
.badge-critical{background:rgba(239,68,68,0.1);color:#dc2626}
.badge-high{background:rgba(245,158,11,0.1);color:#d97706}
.badge-medium{background:rgba(59,130,246,0.1);color:#2563eb}
.badge-low{background:rgba(16,185,129,0.1);color:#059669}
</style>

<div class="history-header">
    <h2><i class="fa fa-history"></i> Alert Resolution History</h2>
    <p>Audit trail of all resolved financial alerts</p>
</div>

<div class="history-table">
    <table id="history_table">
        <thead>
            <tr>
                <th>Alert Key</th>
                <th>Resolved By</th>
                <th>Resolved At</th>
                <th>Notes</th>
            </tr>
        </thead>
        <tbody id="history_body"></tbody>
    </table>
</div>

<script>
function loadHistory(){
    $.get('<?php echo site_url("admin/get_alert_history");?>',function(data){
        const history=typeof data==='string'?JSON.parse(data):data;
        let html='';
        history.forEach(h=>{
            const date=new Date(h.resolved_at*1000);
            html+=`<tr>
                <td><code>${h.alert_key}</code></td>
                <td>${h.resolved_by_name}</td>
                <td>${date.toLocaleString()}</td>
                <td>${h.notes||'N/A'}</td>
            </tr>`;
        });
        $('#history_body').html(html||'<tr><td colspan="4" style="text-align:center;color:#94a3b8">No history found</td></tr>');
    });
}
$(document).ready(function(){loadHistory()});
</script>
