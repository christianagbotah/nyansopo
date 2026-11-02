<style>
.alerts-header{background:linear-gradient(135deg,#f59e0b 0%,#d97706 100%);padding:2.5rem;border-radius:16px;margin-bottom:2rem;box-shadow:0 10px 40px rgba(245,158,11,0.25);position:relative;overflow:hidden}
.alerts-header::before{content:'';position:absolute;top:-50%;right:-10%;width:300px;height:300px;background:radial-gradient(circle,rgba(255,255,255,0.1),transparent);border-radius:50%}
.alerts-header h2{color:#fff;font-size:2rem;font-weight:800;margin:0;letter-spacing:-0.5px}
.alerts-header p{color:rgba(255,255,255,0.85);margin:0.75rem 0 0;font-size:1rem}
.summary-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1rem;margin-bottom:1.5rem}
.summary-card{background:#fff;border-radius:16px;padding:1.75rem;box-shadow:0 4px 16px rgba(0,0,0,0.06);text-align:center;transition:all 0.3s;border:1px solid;position:relative;overflow:hidden}
.summary-card::after{content:'';position:absolute;top:0;left:0;width:100%;height:4px}
.summary-card.critical{border-color:rgba(239,68,68,0.2)}
.summary-card.critical::after{background:linear-gradient(90deg,#ef4444,#dc2626)}
.summary-card.high{border-color:rgba(245,158,11,0.2)}
.summary-card.high::after{background:linear-gradient(90deg,#f59e0b,#d97706)}
.summary-card.medium{border-color:rgba(59,130,246,0.2)}
.summary-card.medium::after{background:linear-gradient(90deg,#3b82f6,#2563eb)}
.summary-card.low{border-color:rgba(16,185,129,0.2)}
.summary-card.low::after{background:linear-gradient(90deg,#10b981,#059669)}
.summary-card:hover{transform:translateY(-4px);box-shadow:0 8px 24px rgba(0,0,0,0.1)}
.summary-label{color:#64748b;font-size:0.9375rem;font-weight:600;text-transform:uppercase;letter-spacing:1px;margin-bottom:0.75rem}
.summary-value{font-size:3.25rem;font-weight:800;line-height:1;margin:0.5rem 0}
.summary-card.critical .summary-value{color:#ef4444}
.summary-card.high .summary-value{color:#f59e0b}
.summary-card.medium .summary-value{color:#3b82f6}
.summary-card.low .summary-value{color:#10b981}
.filter-tabs{background:#fff;border-radius:16px;padding:1.5rem;box-shadow:0 4px 16px rgba(0,0,0,0.06);margin-bottom:1.5rem;border:1px solid rgba(245,158,11,0.08)}
.tab-group{display:flex;gap:0.5rem;flex-wrap:wrap}
.tab-btn{padding:0.75rem 1.75rem;border:2px solid #e2e8f0;border-radius:10px;background:#fff;transition:all 0.3s;cursor:pointer;font-weight:600;font-size:1rem;color:#475569}
.tab-btn:hover{border-color:#f59e0b;color:#f59e0b;background:rgba(245,158,11,0.05)}
.tab-btn.active{background:linear-gradient(135deg,#f59e0b,#d97706);color:#fff;border-color:transparent;box-shadow:0 4px 12px rgba(245,158,11,0.3)}
.alert-item{background:#fff;border-radius:16px;padding:1.75rem;margin-bottom:1rem;box-shadow:0 4px 16px rgba(0,0,0,0.06);border:1px solid;transition:all 0.3s;position:relative;overflow:hidden}
.alert-item::before{content:'';position:absolute;left:0;top:0;width:6px;height:100%}
.alert-item.critical{border-color:rgba(239,68,68,0.2)}
.alert-item.critical::before{background:linear-gradient(180deg,#ef4444,#dc2626)}
.alert-item.high{border-color:rgba(245,158,11,0.2)}
.alert-item.high::before{background:linear-gradient(180deg,#f59e0b,#d97706)}
.alert-item.medium{border-color:rgba(59,130,246,0.2)}
.alert-item.medium::before{background:linear-gradient(180deg,#3b82f6,#2563eb)}
.alert-item.low{border-color:rgba(16,185,129,0.2)}
.alert-item.low::before{background:linear-gradient(180deg,#10b981,#059669)}
.alert-item:hover{transform:translateX(6px);box-shadow:0 8px 24px rgba(0,0,0,0.1)}
.alert-content{display:flex;align-items:flex-start;gap:1.25rem;padding-left:1rem;cursor:pointer}
.alert-icon{width:56px;height:56px;border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:1.75rem;flex-shrink:0}
.alert-item.critical .alert-icon{background:rgba(239,68,68,0.1);color:#ef4444}
.alert-item.high .alert-icon{background:rgba(245,158,11,0.1);color:#f59e0b}
.alert-item.medium .alert-icon{background:rgba(59,130,246,0.1);color:#3b82f6}
.alert-item.low .alert-icon{background:rgba(16,185,129,0.1);color:#10b981}
.alert-body{flex:1}
.alert-title{font-size:1.25rem;font-weight:700;color:#0f172a;margin-bottom:0.5rem}
.alert-desc{color:#64748b;font-size:1.0625rem;line-height:1.6;margin-bottom:0.75rem}
.alert-meta{display:flex;gap:1.5rem;font-size:0.9375rem;color:#94a3b8;flex-wrap:wrap}
.alert-meta span{display:flex;align-items:center;gap:0.375rem}
.alert-actions{position:absolute;top:1.25rem;right:1.25rem;z-index:10}
.btn-resolve{padding:0.5rem 1rem;border-radius:8px;border:none;font-weight:600;cursor:pointer;transition:all 0.3s;font-size:0.8125rem;white-space:nowrap}
.btn-resolve.primary{background:linear-gradient(135deg,#10b981,#059669);color:#fff;box-shadow:0 2px 8px rgba(16,185,129,0.2)}
.btn-resolve.primary:hover{transform:translateY(-2px);box-shadow:0 4px 12px rgba(16,185,129,0.35)}
.alert-item.resolved{opacity:0.6;border-color:#e2e8f0}
.alert-item.resolved::before{background:#94a3b8}
.resolved-badge{background:rgba(16,185,129,0.1);color:#059669;padding:0.25rem 0.75rem;border-radius:6px;font-size:0.75rem;font-weight:700;margin-left:0.5rem}
.alert-badge{padding:0.375rem 0.875rem;border-radius:8px;font-size:0.8125rem;font-weight:700;text-transform:uppercase;letter-spacing:0.5px}
.badge-critical{background:rgba(239,68,68,0.1);color:#dc2626}
.badge-high{background:rgba(245,158,11,0.1);color:#d97706}
.badge-medium{background:rgba(59,130,246,0.1);color:#2563eb}
.badge-low{background:rgba(16,185,129,0.1);color:#059669}
.empty-state{text-align:center;padding:4rem 2rem;color:#94a3b8}
.empty-state i{font-size:5rem;opacity:0.2;margin-bottom:1.5rem;display:block}
.empty-state h4{font-size:1.25rem;font-weight:700;color:#64748b;margin-bottom:0.5rem}
</style>

<div class="alerts-header">
    <h2><i class="fa fa-bell"></i> Financial Alerts & Risk Monitoring</h2>
    <p>Real-time anomaly detection and risk assessment system</p>
</div>

<div class="summary-grid">
    <div class="summary-card critical">
        <div class="summary-label">Critical Alerts</div>
        <div class="summary-value" id="critical_count">0</div>
    </div>
    <div class="summary-card high">
        <div class="summary-label">High Priority</div>
        <div class="summary-value" id="high_count">0</div>
    </div>
    <div class="summary-card medium">
        <div class="summary-label">Medium Priority</div>
        <div class="summary-value" id="medium_count">0</div>
    </div>
    <div class="summary-card low">
        <div class="summary-label">Low Priority</div>
        <div class="summary-value" id="low_count">0</div>
    </div>
</div>

<div class="filter-tabs">
    <div class="tab-group">
        <button class="tab-btn active" onclick="filterAlerts('all')"><i class="fa fa-list"></i> All Alerts</button>
        <button class="tab-btn" onclick="filterAlerts('critical')"><i class="fa fa-exclamation-circle"></i> Critical</button>
        <button class="tab-btn" onclick="filterAlerts('high')"><i class="fa fa-exclamation-triangle"></i> High</button>
        <button class="tab-btn" onclick="filterAlerts('medium')"><i class="fa fa-info-circle"></i> Medium</button>
        <button class="tab-btn" onclick="filterAlerts('low')"><i class="fa fa-check-circle"></i> Low</button>
        <button class="tab-btn" onclick="filterAlerts('resolved')"><i class="fa fa-check-double"></i> Resolved</button>
    </div>
</div>

<div id="alerts_container"></div>

<?php echo form_open('admin/resolve_financial_alert', ['id' => 'resolve_alert_form', 'style' => 'display:none']); ?>
    <input type="hidden" name="alert_id" id="resolve_alert_id">
    <input type="hidden" name="notes" id="resolve_notes" value="Resolved by admin">
</form>

<script>
let allAlerts=[];

function loadAlerts(){
    $.get('<?php echo site_url("admin/get_financial_alerts");?>',function(data){
        allAlerts=typeof data==='string'?JSON.parse(data):data;
        updateSummary();
        renderAlerts(allAlerts);
    }).fail(function(){
        allAlerts=generateSampleAlerts();
        updateSummary();
        renderAlerts(allAlerts);
    });
}

function generateSampleAlerts(){
    return[
        {id:1,severity:'critical',title:'Critical Outstanding Balance Detected',description:'Student ID 2024001 has an outstanding balance of GH₵ 5,420.00 exceeding 90 days. Immediate action required.',category:'Receivables',timestamp:'2 hours ago',icon:'exclamation-triangle'},
        {id:2,severity:'high',title:'Collection Rate Below Target Threshold',description:'Today\'s collection efficiency is 45% - significantly below the 70% target threshold. Review collection processes.',category:'Performance',timestamp:'3 hours ago',icon:'chart-line'},
        {id:3,severity:'medium',title:'Unusual Transaction Pattern Detected',description:'Collector John Doe recorded 15 transactions within 5 minutes. Possible data entry error or system anomaly.',category:'Anomaly Detection',timestamp:'5 hours ago',icon:'flag'},
        {id:4,severity:'high',title:'Bank Reconciliation Overdue',description:'Bank account reconciliation for GCB-001 has been pending for 7 days. Reconcile immediately to ensure accuracy.',category:'Reconciliation',timestamp:'1 day ago',icon:'university'},
        {id:5,severity:'low',title:'Daily Collection Target Achieved',description:'Congratulations! Daily collection target of GH₵ 15,000 has been successfully achieved.',category:'Success',timestamp:'2 days ago',icon:'check-circle'},
        {id:6,severity:'critical',title:'Multiple Failed Payment Attempts',description:'5 mobile money payment attempts failed in the last hour. Check payment gateway connectivity.',category:'Payment Gateway',timestamp:'30 minutes ago',icon:'credit-card'},
        {id:7,severity:'medium',title:'Variance Threshold Exceeded',description:'Cash variance of 8.5% detected in today\'s reconciliation. Expected variance is below 5%.',category:'Variance',timestamp:'4 hours ago',icon:'balance-scale'}
    ];
}

function updateSummary(){
    const counts={critical:0,high:0,medium:0,low:0};
    allAlerts.forEach(a=>counts[a.severity]=(counts[a.severity]||0)+1);
    $('#critical_count').text(counts.critical||0);
    $('#high_count').text(counts.high||0);
    $('#medium_count').text(counts.medium||0);
    $('#low_count').text(counts.low||0);
}

function renderAlerts(alerts){
    if(!alerts.length){
        $('#alerts_container').html('<div class="empty-state"><i class="fa fa-check-circle"></i><h4>All Clear!</h4><p>No active alerts at this time. All systems operating normally.</p></div>');
        return;
    }
    let html='';
    alerts.forEach(a=>{
        const resolvedClass=a.resolved?' resolved':'';
        const resolvedBadge=a.resolved?'<span class="resolved-badge"><i class="fa fa-check"></i> RESOLVED</span>':'';
        html+=`<div class="alert-item ${a.severity}${resolvedClass}">
            ${!a.resolved?`<div class="alert-actions">
                <button class="btn-resolve primary" onclick="resolveAlert('${a.id}')"><i class="fa fa-check"></i> Resolve</button>
            </div>`:''}
            <div class="alert-content" onclick="viewAlertDetails('${a.id}')">
                <div class="alert-icon"><i class="fa fa-${a.icon||'bell'}"></i></div>
                <div class="alert-body">
                    <div class="alert-title">${a.title}${resolvedBadge}</div>
                    <div class="alert-desc">${a.description}</div>
                    <div class="alert-meta">
                        <span><i class="fa fa-tag"></i> ${a.category}</span>
                        <span><i class="fa fa-clock"></i> ${a.timestamp}</span>
                        <span class="alert-badge badge-${a.severity}">${a.severity}</span>
                    </div>
                </div>
            </div>
        </div>`;
    });
    $('#alerts_container').html(html);
}

function filterAlerts(severity){
    $('.tab-btn').removeClass('active');
    event.target.classList.add('active');
    let filtered;
    if(severity==='all')filtered=allAlerts.filter(a=>!a.resolved);
    else if(severity==='resolved')filtered=allAlerts.filter(a=>a.resolved);
    else filtered=allAlerts.filter(a=>a.severity===severity&&!a.resolved);
    renderAlerts(filtered);
}

function resolveAlert(id){
    showConfirmModal(
        'Resolve Alert',
        'Are you sure you want to mark this alert as resolved?',
        function(){
            $('#resolve_alert_id').val(id);
            showAjaxModal_alert('Resolving alert...','loading');
            $.ajax({
                url:$('#resolve_alert_form').attr('action'),
                type:'POST',
                data:new FormData($('#resolve_alert_form')[0]),
                cache:false,
                contentType:false,
                processData:false,
                dataType:'json'
            }).done(function(response){
                if(response.status==='success'){
                    showAjaxModal_alert(response.message,'success');
                    setTimeout(()=>loadAlerts(),2000);
                }else{
                    showAjaxModal_alert(response.message||'Failed to resolve alert','error');
                }
            }).fail(function(){
                showAjaxModal_alert('An error occurred','error');
            });
        },
        'Resolve',
        'success'
    );
}

function viewAlertDetails(id){
    const alert=allAlerts.find(a=>a.id==id);
    if(alert){
        const statusBadge=alert.resolved?'<span style="background:rgba(16,185,129,0.1);color:#059669;padding:0.375rem 0.875rem;border-radius:6px;font-size:0.75rem;font-weight:700">RESOLVED</span>':'';
        showAjaxModal_alert(`<div style="text-align:left"><h4 style="margin:0 0 1rem;font-weight:700;font-size:1.25rem">${alert.title} ${statusBadge}</h4><p style="margin-bottom:1rem;font-size:1.0625rem;line-height:1.6">${alert.description}</p><div style="padding:1.25rem;background:#f8fafc;border-radius:8px;font-size:1rem"><strong>Category:</strong> ${alert.category}<br><strong>Time:</strong> ${alert.timestamp}<br><strong>Severity:</strong> <span style="text-transform:uppercase;font-weight:700;font-size:1.0625rem;color:${alert.severity==='critical'?'#ef4444':alert.severity==='high'?'#f59e0b':alert.severity==='medium'?'#3b82f6':'#10b981'}">${alert.severity}</span></div></div>`,'info');
    }
}

$(document).ready(function(){loadAlerts();setInterval(loadAlerts,60000)});
</script>
