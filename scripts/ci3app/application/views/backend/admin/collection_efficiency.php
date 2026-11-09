<link href="<?php echo base_url(); ?>assets/cdn/css/flatpickr.min.css" rel="stylesheet">
<style>
.efficiency-header{background:linear-gradient(135deg,#059669 0%,#047857 100%);padding:2.5rem;border-radius:16px;margin-bottom:2rem;box-shadow:0 10px 40px rgba(5,150,105,0.25);position:relative;overflow:hidden}
.efficiency-header::before{content:'';position:absolute;top:-50%;right:-10%;width:300px;height:300px;background:radial-gradient(circle,rgba(255,255,255,0.1),transparent);border-radius:50%}
.efficiency-header h2{color:#fff;font-size:2rem;font-weight:800;margin:0;letter-spacing:-0.5px}
.efficiency-header p{color:rgba(255,255,255,0.85);margin:0.75rem 0 0;font-size:1rem}
.kpi-card{background:#fff;border-radius:16px;padding:2rem;box-shadow:0 4px 16px rgba(0,0,0,0.06);position:relative;overflow:hidden;transition:all 0.4s cubic-bezier(0.4,0,0.2,1);border:1px solid rgba(5,150,105,0.1)}
.kpi-card:hover{transform:translateY(-8px);box-shadow:0 12px 32px rgba(5,150,105,0.15);border-color:rgba(5,150,105,0.3)}
.kpi-card::after{content:'';position:absolute;top:0;left:0;width:100%;height:4px;background:linear-gradient(90deg,#059669,#10b981)}
.kpi-icon{width:56px;height:56px;border-radius:14px;background:linear-gradient(135deg,rgba(5,150,105,0.1),rgba(16,185,129,0.05));display:flex;align-items:center;justify-content:center;font-size:1.75rem;color:#059669;margin-bottom:1.25rem}
.kpi-value{font-size:2.75rem;font-weight:800;margin:0.5rem 0;color:#0f172a;line-height:1}
.kpi-label{color:#64748b;font-size:0.8125rem;font-weight:600;text-transform:uppercase;letter-spacing:1px;margin-bottom:0.75rem}
.kpi-trend{font-size:0.875rem;margin-top:0.75rem;padding:0.375rem 0.75rem;border-radius:6px;display:inline-flex;align-items:center;gap:0.375rem;font-weight:600}
.trend-up{background:rgba(16,185,129,0.1);color:#059669}
.trend-down{background:rgba(239,68,68,0.1);color:#dc2626}
.trend-neutral{background:rgba(100,116,139,0.1);color:#64748b}
.chart-container{background:#fff;border-radius:16px;padding:2rem;box-shadow:0 4px 16px rgba(0,0,0,0.06);margin-top:1.5rem;border:1px solid rgba(5,150,105,0.08)}
.chart-container h4{font-size:1.125rem;font-weight:700;margin:0 0 1.5rem;color:#0f172a;display:flex;align-items:center;gap:0.5rem}
.chart-container h4 i{color:#059669}
.collector-rank{display:flex;align-items:center;padding:1.25rem;background:linear-gradient(135deg,#f8fafc,#fff);border-radius:12px;margin-bottom:0.875rem;box-shadow:0 2px 8px rgba(0,0,0,0.04);transition:all 0.3s;border:1px solid rgba(5,150,105,0.08)}
.collector-rank:hover{box-shadow:0 6px 20px rgba(5,150,105,0.12);transform:translateX(6px);border-color:rgba(5,150,105,0.2)}
.rank-badge{width:48px;height:48px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-weight:800;margin-right:1.25rem;font-size:1.25rem;box-shadow:0 4px 12px rgba(0,0,0,0.15)}
.rank-1{background:linear-gradient(135deg,#fbbf24,#f59e0b);color:#78350f}
.rank-2{background:linear-gradient(135deg,#d1d5db,#9ca3af);color:#374151}
.rank-3{background:linear-gradient(135deg,#fb923c,#ea580c);color:#7c2d12}
.rank-other{background:linear-gradient(135deg,#e2e8f0,#cbd5e1);color:#475569}
.rank-info{flex:1}
.rank-name{font-weight:700;font-size:1rem;color:#0f172a;margin-bottom:0.25rem}
.rank-amount{color:#059669;font-size:0.9375rem;font-weight:600}
.rank-stats{font-size:0.8125rem;color:#64748b;margin-top:0.25rem}
.period-selector{background:#fff;border-radius:16px;padding:1.5rem;box-shadow:0 4px 16px rgba(0,0,0,0.06);margin-bottom:1.5rem;border:1px solid rgba(5,150,105,0.08)}
.period-tabs{display:flex;gap:0.5rem;flex-wrap:wrap}
.period-btn{padding:0.75rem 1.75rem;border:2px solid #e2e8f0;border-radius:10px;background:#fff;transition:all 0.3s;cursor:pointer;font-weight:600;font-size:0.9375rem;color:#475569}
.period-btn:hover{border-color:#059669;color:#059669;background:rgba(5,150,105,0.05)}
.period-btn.active{background:linear-gradient(135deg,#059669,#047857);color:#fff;border-color:transparent;box-shadow:0 4px 12px rgba(5,150,105,0.3)}
.date-range-modal{display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(15,23,42,0.6);backdrop-filter:blur(4px);z-index:9999;align-items:center;justify-content:center;animation:fadeIn 0.3s}
.date-range-modal.show{display:flex}
.modal-dialog{background:#fff;border-radius:20px;padding:2.5rem;max-width:480px;width:90%;box-shadow:0 25px 80px rgba(0,0,0,0.3);animation:slideUp 0.3s}
.modal-header{margin-bottom:2rem}
.modal-header h3{font-size:1.5rem;font-weight:800;color:#0f172a;margin:0;display:flex;align-items:center;gap:0.75rem}
.modal-header h3 i{color:#059669}
.form-group{margin-bottom:1.5rem}
.form-group label{display:block;margin-bottom:0.625rem;font-weight:600;color:#475569;font-size:0.9375rem}
.form-control{width:100%;border:2px solid #e2e8f0;border-radius:10px;padding:0.875rem 1rem;font-size:1rem;transition:all 0.3s;background:#f8fafc}
.form-control:focus{outline:none;border-color:#059669;background:#fff;box-shadow:0 0 0 4px rgba(5,150,105,0.1)}
.modal-actions{display:flex;gap:1rem;margin-top:2rem}
.btn{flex:1;padding:1rem;border:none;border-radius:10px;font-weight:700;font-size:1rem;cursor:pointer;transition:all 0.3s;display:flex;align-items:center;justify-content:center;gap:0.5rem}
.btn-primary{background:linear-gradient(135deg,#059669,#047857);color:#fff;box-shadow:0 4px 12px rgba(5,150,105,0.3)}
.btn-primary:hover{transform:translateY(-2px);box-shadow:0 6px 20px rgba(5,150,105,0.4)}
.btn-secondary{background:#f1f5f9;color:#475569}
.btn-secondary:hover{background:#e2e8f0}
@keyframes fadeIn{from{opacity:0}to{opacity:1}}
@keyframes slideUp{from{transform:translateY(20px);opacity:0}to{transform:translateY(0);opacity:1}}
.performance-badge{display:inline-flex;align-items:center;gap:0.375rem;padding:0.375rem 0.875rem;border-radius:8px;font-size:0.8125rem;font-weight:600}
.badge-excellent{background:rgba(5,150,105,0.1);color:#047857}
.badge-good{background:rgba(16,185,129,0.1);color:#059669}
.badge-average{background:rgba(245,158,11,0.1);color:#d97706}
.badge-poor{background:rgba(239,68,68,0.1);color:#dc2626}
</style>

<div class="efficiency-header">
    <h2><i class="fa fa-chart-line"></i> Collection Efficiency Analytics</h2>
    <p>Real-time performance monitoring and predictive insights</p>
</div>

<div class="period-selector">
    <div class="period-tabs">
        <button class="period-btn active" onclick="loadPeriod('today')"><i class="fa fa-calendar-day"></i> Today</button>
        <button class="period-btn" onclick="loadPeriod('week')"><i class="fa fa-calendar-week"></i> This Week</button>
        <button class="period-btn" onclick="loadPeriod('month')"><i class="fa fa-calendar"></i> This Month</button>
        <button class="period-btn" onclick="loadPeriod('quarter')"><i class="fa fa-calendar-alt"></i> Quarter</button>
        <button class="period-btn" onclick="showDateRangeModal()"><i class="fa fa-calendar-alt"></i> Custom Range</button>
    </div>
</div>

<div class="date-range-modal" id="dateRangeModal">
    <div class="modal-dialog">
        <div class="modal-header">
            <h3><i class="fa fa-calendar-alt"></i> Select Date Range</h3>
        </div>
        <div class="form-group">
            <label><i class="fa fa-calendar-day"></i> Start Date</label>
            <input id="start_date" type="text" class="form-control" placeholder="Select start date">
        </div>
        <div class="form-group">
            <label><i class="fa fa-calendar-day"></i> End Date</label>
            <input id="end_date" type="text" class="form-control" placeholder="Select end date">
        </div>
        <div class="modal-actions">
            <button class="btn btn-primary" onclick="applyDateRange()"><i class="fa fa-check"></i> Apply Filter</button>
            <button class="btn btn-secondary" onclick="closeDateRangeModal()"><i class="fa fa-times"></i> Cancel</button>
        </div>
    </div>
</div>

<div class="row" id="kpi_metrics">
    <div class="col-md-3">
        <div class="kpi-card">
            <div class="kpi-icon"><i class="fa fa-percentage"></i></div>
            <div class="kpi-label">Collection Efficiency</div>
            <div class="kpi-value" id="collection_rate">0%</div>
            <div class="kpi-trend trend-neutral" id="rate_trend"><i class="fa fa-minus"></i> No change</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="kpi-card">
            <div class="kpi-icon"><i class="fa fa-coins"></i></div>
            <div class="kpi-label">Total Collected</div>
            <div class="kpi-value" id="collected_amount">₵ 0.00</div>
            <div class="kpi-trend trend-neutral" id="collected_trend"><i class="fa fa-minus"></i> No change</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="kpi-card">
            <div class="kpi-icon"><i class="fa fa-bullseye"></i></div>
            <div class="kpi-label">Target Amount</div>
            <div class="kpi-value" id="target_amount">₵ 0.00</div>
            <div class="kpi-trend trend-neutral" id="target_info"><i class="fa fa-info-circle"></i> Period target</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="kpi-card">
            <div class="kpi-icon"><i class="fa fa-exclamation-triangle"></i></div>
            <div class="kpi-label">Outstanding Balance</div>
            <div class="kpi-value" id="outstanding_amount">₵ 0.00</div>
            <div class="kpi-trend trend-neutral" id="outstanding_trend"><i class="fa fa-minus"></i> No change</div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="chart-container">
            <h4><i class="fa fa-chart-line"></i> Collection Performance Trend</h4>
            <canvas id="trend_chart" height="80"></canvas>
        </div>
    </div>
    <div class="col-md-4">
        <div class="chart-container">
            <h4><i class="fa fa-trophy"></i> Top Performing Collectors</h4>
            <div id="collector_rankings"></div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="chart-container">
            <h4><i class="fa fa-layer-group"></i> Revenue Distribution by Fee Type</h4>
            <canvas id="fee_type_chart" height="200"></canvas>
        </div>
    </div>
    <div class="col-md-6">
        <div class="chart-container">
            <h4><i class="fa fa-clock"></i> Hourly Collection Pattern Analysis</h4>
            <canvas id="hourly_chart" height="200"></canvas>
        </div>
    </div>
</div>

<script src="<?php echo base_url(); ?>assets/cdn/js/chart-3.9.1.min.js"></script>
<script>
let trendChart,feeTypeChart,hourlyChart;

let currentPeriod='today',customStartDate='',customEndDate='',startPicker,endPicker;
const currency='<?php echo $this->db->get_where("settings",["type"=>"currency"])->row()->description;?>';

function loadPeriod(period){
    $('.period-btn').removeClass('active');
    event.target.classList.add('active');
    currentPeriod=period;
    loadEfficiencyData(period);
}

function showDateRangeModal(){
    $('#dateRangeModal').addClass('show');
    if(!startPicker){
        startPicker=flatpickr('#start_date',{dateFormat:'Y-m-d',maxDate:'today',onChange:function(selectedDates){if(endPicker)endPicker.set('minDate',selectedDates[0])}});
        endPicker=flatpickr('#end_date',{dateFormat:'Y-m-d',maxDate:'today'});
    }
}

function closeDateRangeModal(){
    $('#dateRangeModal').removeClass('show');
}

function applyDateRange(){
    customStartDate=$('#start_date').val();
    customEndDate=$('#end_date').val();
    if(!customStartDate||!customEndDate){
        showAjaxModal_alert('Please select both start and end dates','warning');
        return;
    }
    if(new Date(customStartDate)>new Date(customEndDate)){
        showAjaxModal_alert('Start date cannot be after end date','error');
        return;
    }
    closeDateRangeModal();
    loadEfficiencyData('custom',customStartDate,customEndDate);
}

function loadEfficiencyData(period='today',startDate='',endDate=''){
    let url='<?php echo site_url("fee_collection/get_efficiency_data");?>/'+period;
    if(period==='custom'&&startDate&&endDate){
        url+='/'+startDate+'/'+endDate;
    }
    $.get(url,function(data){
        const d=typeof data==='string'?JSON.parse(data):data;
        updateKPIs(d);
        renderTrendChart(d.trend||[]);
        renderCollectorRankings(d.collectors||[]);
        renderFeeTypeChart(d.by_fee_type||{});
        renderHourlyChart(d.hourly||[]);
    }).fail(function(){
        updateKPIs({collection_rate:0,collected:0,target:0,outstanding:0});
    });
}

function updateKPIs(data){
    const rate=data.collection_rate||0;
    const collected=data.collected||0;
    const target=data.target||0;
    const outstanding=data.outstanding||0;
    const prevCollected=data.prev_collected||0;
    const prevOutstanding=data.prev_outstanding||0;
    
    $('#collection_rate').text(rate.toFixed(1)+'%');
    $('#collected_amount').text(currency+' '+collected.toLocaleString('en-US',{minimumFractionDigits:2}));
    $('#target_amount').text(currency+' '+target.toLocaleString('en-US',{minimumFractionDigits:2}));
    $('#outstanding_amount').text(currency+' '+outstanding.toLocaleString('en-US',{minimumFractionDigits:2}));
    
    updateTrend('#rate_trend',rate,data.prev_rate||0,'%');
    updateTrend('#collected_trend',collected,prevCollected,currency);
    updateTrend('#outstanding_trend',outstanding,prevOutstanding,currency,true);
    
    const targetInfo=rate>=100?'<i class="fa fa-check-circle"></i> Target achieved':rate>=80?'<i class="fa fa-thumbs-up"></i> On track':'<i class="fa fa-info-circle"></i> Below target';
    $('#target_info').html(targetInfo).removeClass('trend-up trend-down trend-neutral').addClass(rate>=100?'trend-up':rate>=80?'trend-neutral':'trend-down');
}

function updateTrend(selector,current,previous,prefix='',inverse=false){
    if(!previous||previous===0){$(selector).html('<i class="fa fa-minus"></i> No comparison data').removeClass('trend-up trend-down').addClass('trend-neutral');return;}
    const change=((current-previous)/previous)*100;
    const isPositive=inverse?change<0:change>0;
    const icon=change>0?'arrow-up':change<0?'arrow-down':'minus';
    const text=Math.abs(change).toFixed(1)+'% '+(change>0?'increase':'decrease');
    $(selector).html(`<i class="fa fa-${icon}"></i> ${text}`).removeClass('trend-up trend-down trend-neutral').addClass(isPositive?'trend-up':change===0?'trend-neutral':'trend-down');
}

function renderTrendChart(trend){
    const ctx=document.getElementById('trend_chart').getContext('2d');
    if(trendChart)trendChart.destroy();
    trendChart=new Chart(ctx,{
        type:'line',
        data:{
            labels:trend.map(t=>t.date)||['Mon','Tue','Wed','Thu','Fri','Sat','Sun'],
            datasets:[{
                label:'Collected Amount',
                data:trend.map(t=>t.amount)||[0,0,0,0,0,0,0],
                borderColor:'#059669',
                backgroundColor:'rgba(5,150,105,0.08)',
                borderWidth:3,
                tension:0.4,
                fill:true,
                pointRadius:5,
                pointHoverRadius:7,
                pointBackgroundColor:'#fff',
                pointBorderColor:'#059669',
                pointBorderWidth:2
            }]
        },
        options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false},tooltip:{backgroundColor:'#0f172a',padding:12,titleFont:{size:14,weight:'bold'},bodyFont:{size:13},callbacks:{label:ctx=>currency+' '+ctx.parsed.y.toLocaleString('en-US',{minimumFractionDigits:2})}}},scales:{y:{beginAtZero:true,grid:{color:'rgba(0,0,0,0.05)'},ticks:{callback:v=>currency+' '+v.toLocaleString()}},x:{grid:{display:false}}}}
    });
}

function renderCollectorRankings(collectors){
    if(!collectors.length){
        $('#collector_rankings').html('<div style="text-align:center;padding:2rem;color:#94a3b8"><i class="fa fa-users" style="font-size:3rem;opacity:0.3;margin-bottom:1rem"></i><p>No collector data available</p></div>');
        return;
    }
    const total=collectors.reduce((sum,c)=>sum+(c.amount||0),0);
    let html='';
    collectors.slice(0,5).forEach((c,i)=>{
        const rankClass=i===0?'rank-1':i===1?'rank-2':i===2?'rank-3':'rank-other';
        const percentage=total>0?((c.amount||0)/total*100).toFixed(1):0;
        const performance=percentage>=30?'excellent':percentage>=20?'good':percentage>=10?'average':'poor';
        html+=`<div class="collector-rank">
            <div class="rank-badge ${rankClass}">${i+1}</div>
            <div class="rank-info">
                <div class="rank-name">${c.name}</div>
                <div class="rank-amount">${currency} ${(c.amount||0).toLocaleString('en-US',{minimumFractionDigits:2})}</div>
                <div class="rank-stats">${c.count||0} transactions • ${percentage}% of total <span class="performance-badge badge-${performance}">${performance}</span></div>
            </div>
        </div>`;
    });
    $('#collector_rankings').html(html);
}

function renderFeeTypeChart(feeTypes){
    const ctx=document.getElementById('fee_type_chart').getContext('2d');
    if(feeTypeChart)feeTypeChart.destroy();
    const colors=['#059669','#10b981','#14b8a6','#06b6d4','#0ea5e9'];
    feeTypeChart=new Chart(ctx,{
        type:'bar',
        data:{
            labels:['Feeding','Classes','Transport','Breakfast','Water'],
            datasets:[{data:[feeTypes.feeding||0,feeTypes.classes||0,feeTypes.transport||0,feeTypes.breakfast||0,feeTypes.water||0],backgroundColor:colors,borderRadius:8,borderWidth:0}]
        },
        options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false},tooltip:{backgroundColor:'#0f172a',padding:12,callbacks:{label:ctx=>currency+' '+ctx.parsed.y.toLocaleString('en-US',{minimumFractionDigits:2})}}},scales:{y:{beginAtZero:true,grid:{color:'rgba(0,0,0,0.05)'},ticks:{callback:v=>currency+' '+v.toLocaleString()}},x:{grid:{display:false}}}}
    });
}

function renderHourlyChart(hourly){
    const ctx=document.getElementById('hourly_chart').getContext('2d');
    if(hourlyChart)hourlyChart.destroy();
    hourlyChart=new Chart(ctx,{
        type:'line',
        data:{
            labels:['6AM','7AM','8AM','9AM','10AM','11AM','12PM','1PM','2PM','3PM','4PM','5PM'],
            datasets:[{label:'Hourly Collection',data:hourly.length?hourly:[0,0,0,0,0,0,0,0,0,0,0,0],borderColor:'#047857',backgroundColor:'rgba(4,120,87,0.08)',borderWidth:3,tension:0.4,fill:true,pointRadius:4,pointHoverRadius:6,pointBackgroundColor:'#fff',pointBorderColor:'#047857',pointBorderWidth:2}]
        },
        options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false},tooltip:{backgroundColor:'#0f172a',padding:12,callbacks:{label:ctx=>currency+' '+ctx.parsed.y.toLocaleString('en-US',{minimumFractionDigits:2})}}},scales:{y:{beginAtZero:true,grid:{color:'rgba(0,0,0,0.05)'},ticks:{callback:v=>currency+' '+v.toLocaleString()}},x:{grid:{display:false}}}}
    });
}

$(document).ready(function(){loadEfficiencyData()});
</script>
<script src="<?php echo base_url(); ?>assets/cdn/js/flatpickr.min.js"></script>
