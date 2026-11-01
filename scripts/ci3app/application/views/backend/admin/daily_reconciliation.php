<link href="<?php echo base_url(); ?>assets/cdn/css/flatpickr.min.css" rel="stylesheet">
<style>
.recon-header{background:linear-gradient(135deg,#6366f1 0%,#4f46e5 100%);padding:2.5rem;border-radius:16px;margin-bottom:2rem;box-shadow:0 10px 40px rgba(99,102,241,0.25);position:relative;overflow:hidden}
.recon-header::before{content:'';position:absolute;top:-50%;right:-10%;width:300px;height:300px;background:radial-gradient(circle,rgba(255,255,255,0.1),transparent);border-radius:50%}
.recon-header h2{color:#fff;font-size:2rem;font-weight:800;margin:0;letter-spacing:-0.5px}
.recon-header p{color:rgba(255,255,255,0.85);margin:0.75rem 0 0;font-size:1rem}
.metric-card{background:#fff;border-radius:16px;padding:2rem;box-shadow:0 4px 16px rgba(0,0,0,0.06);transition:all 0.4s cubic-bezier(0.4,0,0.2,1);border:1px solid rgba(99,102,241,0.1);position:relative;overflow:hidden}
.metric-card:hover{transform:translateY(-8px);box-shadow:0 12px 32px rgba(99,102,241,0.15)}
.metric-card::after{content:'';position:absolute;top:0;left:0;width:100%;height:4px}
.metric-card.success::after{background:linear-gradient(90deg,#10b981,#059669)}
.metric-card.info::after{background:linear-gradient(90deg,#3b82f6,#2563eb)}
.metric-card.warning::after{background:linear-gradient(90deg,#f59e0b,#d97706)}
.metric-card.danger::after{background:linear-gradient(90deg,#ef4444,#dc2626)}
.metric-icon{width:56px;height:56px;border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:1.75rem;margin-bottom:1.25rem}
.metric-card.success .metric-icon{background:rgba(16,185,129,0.1);color:#059669}
.metric-card.info .metric-icon{background:rgba(59,130,246,0.1);color:#2563eb}
.metric-card.warning .metric-icon{background:rgba(245,158,11,0.1);color:#d97706}
.metric-card.danger .metric-icon{background:rgba(239,68,68,0.1);color:#dc2626}
.metric-value{font-size:2.75rem;font-weight:800;margin:0.5rem 0;color:#0f172a;line-height:1}
.metric-label{color:#64748b;font-size:0.8125rem;font-weight:600;text-transform:uppercase;letter-spacing:1px;margin-bottom:0.75rem}
.variance-badge{display:inline-flex;align-items:center;gap:0.375rem;padding:0.375rem 0.875rem;border-radius:8px;font-size:0.8125rem;font-weight:600;margin-top:0.75rem}
.variance-positive{background:rgba(16,185,129,0.1);color:#059669}
.variance-negative{background:rgba(239,68,68,0.1);color:#dc2626}
.recon-table{background:#fff;border-radius:16px;padding:2rem;box-shadow:0 4px 16px rgba(0,0,0,0.06);margin-top:1.5rem;border:1px solid rgba(99,102,241,0.08)}
.recon-table h4{font-size:1.125rem;font-weight:700;margin:0 0 1.5rem;color:#0f172a;display:flex;align-items:center;gap:0.5rem}
.recon-table h4 i{color:#6366f1}
.filter-bar{background:#fff;border-radius:16px;padding:1.5rem;box-shadow:0 4px 16px rgba(0,0,0,0.06);margin-bottom:1.5rem;border:1px solid rgba(99,102,241,0.08)}
.form-group label{display:block;margin-bottom:0.625rem;font-weight:600;color:#475569;font-size:0.9375rem}
.form-control{border:2px solid #e2e8f0;border-radius:10px;padding:0.875rem 1rem;transition:all 0.3s;background:#f8fafc}
.form-control:focus{outline:none;border-color:#6366f1;background:#fff;box-shadow:0 0 0 4px rgba(99,102,241,0.1)}
.btn-recon{padding:0.875rem 1.75rem;border:none;border-radius:10px;font-weight:700;cursor:pointer;transition:all 0.3s;display:inline-flex;align-items:center;gap:0.5rem}
.btn-primary{background:linear-gradient(135deg,#6366f1,#4f46e5);color:#fff;box-shadow:0 4px 12px rgba(99,102,241,0.3)}
.btn-primary:hover{transform:translateY(-2px);box-shadow:0 6px 20px rgba(99,102,241,0.4)}
.btn-success{background:linear-gradient(135deg,#10b981,#059669);color:#fff;box-shadow:0 4px 12px rgba(16,185,129,0.3)}
.btn-success:hover{transform:translateY(-2px);box-shadow:0 6px 20px rgba(16,185,129,0.4)}
.collector-row{display:flex;justify-content:space-between;align-items:center;padding:1rem;background:linear-gradient(135deg,#f8fafc,#fff);border-radius:10px;margin-bottom:0.75rem;border:1px solid rgba(99,102,241,0.08);transition:all 0.3s}
.collector-row:hover{border-color:rgba(99,102,241,0.2);box-shadow:0 4px 12px rgba(99,102,241,0.08)}
.collector-name{font-weight:700;color:#0f172a}
.collector-amount{font-weight:700;color:#6366f1;font-size:1.125rem}
.collector-stats{font-size:0.8125rem;color:#64748b;margin-top:0.25rem}
</style>

<div class="recon-header">
    <h2><i class="fa fa-balance-scale-right"></i> Daily Cash Reconciliation</h2>
    <p>Real-time cash flow monitoring and variance analysis</p>
</div>

<div class="filter-bar">
    <div class="row">
        <div class="col-md-3">
            <div class="form-group">
                <label><i class="fa fa-calendar"></i> Reconciliation Date</label>
                <input type="text" id="recon_date" class="form-control" placeholder="Select date">
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label><i class="fa fa-filter"></i> Fee Type Filter</label>
                <select id="fee_type" class="form-control">
                    <option value="all">All Fee Types</option>
                    <option value="feeding">Feeding</option>
                    <option value="classes">Classes</option>
                    <option value="transport">Transport</option>
                    <option value="breakfast">Breakfast</option>
                    <option value="water">Water</option>
                </select>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label>&nbsp;</label>
                <button class="btn-recon btn-primary btn-block" onclick="loadReconciliation()">
                    <i class="fa fa-sync"></i> Load Report
                </button>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label>&nbsp;</label>
                <button class="btn-recon btn-success btn-block" onclick="exportReconciliation()">
                    <i class="fa fa-file-excel"></i> Export Excel
                </button>
            </div>
        </div>
    </div>
</div>

<div class="row" id="metrics_row"></div>
<div class="row">
    <div class="col-md-8">
        <div class="recon-table">
            <h4><i class="fa fa-users"></i> Collection by Collector</h4>
            <div id="collector_breakdown"></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="recon-table">
            <h4><i class="fa fa-credit-card"></i> Payment Methods</h4>
            <canvas id="payment_chart" height="200"></canvas>
        </div>
    </div>
</div>

<div class="recon-table" id="anomalies_section" style="display:none">
    <h4><i class="fa fa-exclamation-triangle" style="color:#f59e0b"></i> Anomalies Detected</h4>
    <div id="anomalies_list"></div>
</div>

<div class="recon-table">
    <h4><i class="fa fa-coins"></i> Cash Denomination Verification</h4>
    <button class="btn-recon btn-primary" onclick="showDenominationModal()"><i class="fa fa-calculator"></i> Verify Cash Count</button>
</div>

<!-- Denomination Modal -->
<div id="denomination_modal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:9999;align-items:center;justify-content:center">
    <div style="background:#fff;border-radius:16px;padding:2rem;max-width:600px;width:90%;max-height:90vh;overflow-y:auto">
        <h3 style="margin:0 0 1.5rem"><i class="fa fa-coins"></i> Cash Denomination Count</h3>
        <?php echo form_open('fee_collection/verify_denomination',['id'=>'denomination_form']); ?>
        <input type="hidden" name="date" id="denom_date">
        <table style="width:100%;margin-bottom:1rem">
            <tr><td>₵200 x</td><td><input type="number" name="d200" class="form-control denom-input" data-value="200" value="0" min="0"></td><td class="denom-total">₵0.00</td></tr>
            <tr><td>₵100 x</td><td><input type="number" name="d100" class="form-control denom-input" data-value="100" value="0" min="0"></td><td class="denom-total">₵0.00</td></tr>
            <tr><td>₵50 x</td><td><input type="number" name="d50" class="form-control denom-input" data-value="50" value="0" min="0"></td><td class="denom-total">₵0.00</td></tr>
            <tr><td>₵20 x</td><td><input type="number" name="d20" class="form-control denom-input" data-value="20" value="0" min="0"></td><td class="denom-total">₵0.00</td></tr>
            <tr><td>₵10 x</td><td><input type="number" name="d10" class="form-control denom-input" data-value="10" value="0" min="0"></td><td class="denom-total">₵0.00</td></tr>
            <tr><td>₵5 x</td><td><input type="number" name="d5" class="form-control denom-input" data-value="5" value="0" min="0"></td><td class="denom-total">₵0.00</td></tr>
            <tr><td>₵2 x</td><td><input type="number" name="d2" class="form-control denom-input" data-value="2" value="0" min="0"></td><td class="denom-total">₵0.00</td></tr>
            <tr><td>₵1 x</td><td><input type="number" name="d1" class="form-control denom-input" data-value="1" value="0" min="0"></td><td class="denom-total">₵0.00</td></tr>
        </table>
        <div style="background:#f8fafc;padding:1rem;border-radius:8px;margin-bottom:1rem">
            <strong>Total Counted:</strong> <span id="total_counted" style="font-size:1.5rem;color:#6366f1">₵0.00</span><br>
            <strong>Expected:</strong> <span id="expected_amount" style="font-size:1.5rem">₵0.00</span><br>
            <strong>Variance:</strong> <span id="variance_amount" style="font-size:1.5rem">₵0.00</span>
        </div>
        <div style="display:flex;gap:1rem">
            <button type="submit" class="btn-recon btn-success"><i class="fa fa-check"></i> Verify</button>
            <button type="button" class="btn-recon btn-primary" onclick="closeDenominationModal()">Cancel</button>
        </div>
        </form>
    </div>
</div>

<script src="<?php echo base_url(); ?>assets/cdn/js/chart-3.9.1.min.js"></script>
<script src="<?php echo base_url(); ?>assets/cdn/js/flatpickr.min.js"></script>
<script>
let paymentChart,reconPicker;
const currency='<?php echo $this->db->get_where("settings",["type"=>"currency"])->row()->description;?>';

function loadReconciliation(){
    const date=$('#recon_date').val();
    const feeType=$('#fee_type').val();
    $('#metrics_row').html('<div class="col-12 text-center"><i class="fa fa-spinner fa-spin fa-3x" style="color:#6366f1"></i></div>');
    
    $.get('<?php echo site_url("fee_collection/get_daily_summary");?>/'+date,function(data){
        const report=typeof data==='string'?JSON.parse(data):data;
        renderMetrics(report);
        renderCollectorBreakdown(report.by_collector||[]);
        renderPaymentChart(report.by_payment_method||{});
        renderAnomalies(report.anomalies||[]);
    }).fail(function(){
        $('#metrics_row').html('<div class="col-12"><div class="alert alert-danger"><i class="fa fa-exclamation-circle"></i> Failed to load data</div></div>');
    });
}

function renderMetrics(report){
    const html=`
        <div class="col-md-3">
            <div class="metric-card success">
                <div class="metric-icon"><i class="fa fa-money-bill-wave"></i></div>
                <div class="metric-label">Total Cash Collected</div>
                <div class="metric-value">${currency} ${(report.total_cash||0).toLocaleString('en-US',{minimumFractionDigits:2})}</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="metric-card info">
                <div class="metric-icon"><i class="fa fa-receipt"></i></div>
                <div class="metric-label">Total Transactions</div>
                <div class="metric-value">${(report.total_transactions||0).toLocaleString()}</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="metric-card warning">
                <div class="metric-icon"><i class="fa fa-calculator"></i></div>
                <div class="metric-label">Average Transaction</div>
                <div class="metric-value">${currency} ${report.total_transactions>0?((report.total_cash||0)/(report.total_transactions||1)).toFixed(2):'0.00'}</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="metric-card ${(report.variance||0)>=0?'success':'danger'}">
                <div class="metric-icon"><i class="fa fa-chart-line"></i></div>
                <div class="metric-label">Variance</div>
                <div class="metric-value"><span class="variance-badge ${(report.variance||0)>=0?'variance-positive':'variance-negative'}">${(report.variance||0)>=0?'+':''}${(report.variance||0).toFixed(2)}%</span></div>
            </div>
        </div>
    `;
    $('#metrics_row').html(html);
}

function renderCollectorBreakdown(collectors){
    if(!collectors.length){
        $('#collector_breakdown').html('<div style="text-align:center;padding:2rem;color:#94a3b8"><i class="fa fa-users" style="font-size:3rem;opacity:0.3;margin-bottom:1rem"></i><p>No collector data available</p></div>');
        return;
    }
    const total=collectors.reduce((sum,c)=>sum+(c.amount||0),0);
    let html='';
    collectors.forEach(c=>{
        const percentage=total>0?((c.amount||0)/total*100).toFixed(1):0;
        const avg=c.count>0?((c.amount||0)/c.count).toFixed(2):'0.00';
        html+=`<div class="collector-row">
            <div>
                <div class="collector-name"><i class="fa fa-user-circle" style="color:#6366f1;margin-right:0.5rem"></i>${c.name}</div>
                <div class="collector-stats">${c.count||0} transactions • Avg: ${currency} ${avg} • ${percentage}% of total</div>
            </div>
            <div class="collector-amount">${currency} ${(c.amount||0).toLocaleString('en-US',{minimumFractionDigits:2})}</div>
        </div>`;
    });
    $('#collector_breakdown').html(html);
}

function renderPaymentChart(methods){
    const ctx=document.getElementById('payment_chart').getContext('2d');
    if(paymentChart)paymentChart.destroy();
    paymentChart=new Chart(ctx,{
        type:'doughnut',
        data:{
            labels:['Cash','Mobile Money','Bank Transfer'],
            datasets:[{data:[methods.cash||0,methods.mobile_money||0,methods.bank_transfer||0],backgroundColor:['#6366f1','#10b981','#f59e0b'],borderWidth:0}]
        },
        options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{position:'bottom',labels:{padding:15,font:{size:13,weight:'600'}}},tooltip:{backgroundColor:'#0f172a',padding:12,callbacks:{label:ctx=>ctx.label+': '+currency+' '+ctx.parsed.toLocaleString('en-US',{minimumFractionDigits:2})}}}}
    });
}

function renderAnomalies(anomalies){
    if(!anomalies.length){
        $('#anomalies_section').hide();
        return;
    }
    let html='<div class="alert alert-warning">';
    anomalies.forEach(a=>html+=`<div><i class="fa fa-exclamation-circle"></i> ${a.message}</div>`);
    html+='</div>';
    $('#anomalies_list').html(html);
    $('#anomalies_section').show();
}

function exportReconciliation(){
    window.location.href='<?php echo site_url("fee_collection/export_reconciliation");?>/'+$('#recon_date').val();
}

function showDenominationModal(){
    $('#denom_date').val($('#recon_date').val());
    $('#denomination_modal').css('display','flex');
}

function closeDenominationModal(){
    $('#denomination_modal').hide();
}

$('.denom-input').on('input',function(){
    let total=0;
    $('.denom-input').each(function(){
        const count=parseInt($(this).val())||0;
        const value=parseFloat($(this).data('value'));
        const lineTotal=count*value;
        $(this).closest('tr').find('.denom-total').text('₵'+lineTotal.toFixed(2));
        total+=lineTotal;
    });
    $('#total_counted').text('₵'+total.toFixed(2));
    const expected=parseFloat($('#expected_amount').text().replace('₵',''))||0;
    const variance=total-expected;
    $('#variance_amount').text('₵'+variance.toFixed(2)).css('color',variance>=0?'#10b981':'#ef4444');
});

$('#denomination_form').submit(function(e){
    e.preventDefault();
    closeDenominationModal();
    showAjaxModal_alert('Verifying cash count...','loading');
    $.ajax({
        url:$(this).attr('action'),
        type:'POST',
        data:new FormData(this),
        cache:false,
        contentType:false,
        processData:false,
        dataType:'json'
    }).done(function(response){
        if(response.status==='success'){
            showAjaxModal_alert(response.message,'success');
            setTimeout(()=>loadReconciliation(),2000);
        }else{
            showAjaxModal_alert(response.message||'Verification failed','error');
        }
    }).fail(function(){
        showAjaxModal_alert('An error occurred','error');
    });
});

$(document).ready(function(){
    reconPicker=flatpickr('#recon_date',{dateFormat:'Y-m-d',defaultDate:'today',maxDate:'today'});
    loadReconciliation();
});
</script>
<script>
// Variance drill-down
function showVarianceDetails(variance){
    showAjaxModal_alert(`<div style="text-align:left"><h4>Variance Analysis</h4><p>Total Variance: <strong style="color:${variance>=0?'#10b981':'#ef4444'}">₵${variance.toFixed(2)}</strong></p><p>This variance may be due to:</p><ul><li>Cash denomination counting differences</li><li>Pending mobile money confirmations</li><li>Bank transfer processing delays</li><li>Data entry errors</li></ul><p><strong>Action Required:</strong> Verify cash count and reconcile payment methods.</p></div>`,'info');
}
</script>
