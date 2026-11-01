<link href="<?php echo base_url(); ?>assets/cdn/css/flatpickr.min.css" rel="stylesheet">
<style>
.handover-header{background:linear-gradient(135deg,#14b8a6 0%,#0d9488 100%);padding:2.5rem;border-radius:16px;margin-bottom:2rem;box-shadow:0 10px 40px rgba(20,184,166,0.25);position:relative;overflow:hidden}
.handover-header::before{content:'';position:absolute;top:-50%;right:-10%;width:300px;height:300px;background:radial-gradient(circle,rgba(255,255,255,0.1),transparent);border-radius:50%}
.handover-header h2{color:#fff;font-size:2rem;font-weight:800;margin:0;letter-spacing:-0.5px}
.handover-header p{color:rgba(255,255,255,0.85);margin:0.75rem 0 0;font-size:1rem}
.handover-form{background:#fff;border-radius:16px;padding:2rem;box-shadow:0 4px 16px rgba(0,0,0,0.06);margin-bottom:1.5rem;border:1px solid rgba(20,184,166,0.08)}
.form-group{margin-bottom:1.5rem}
.form-group label{display:block;margin-bottom:0.625rem;font-weight:600;color:#475569;font-size:0.9375rem}
.form-control{width:100%;border:2px solid #e2e8f0;border-radius:10px;padding:0.875rem 1rem;transition:all 0.3s;background:#f8fafc;font-size:1rem}
.form-control:focus{outline:none;border-color:#14b8a6;background:#fff;box-shadow:0 0 0 4px rgba(20,184,166,0.1)}
.handover-summary{background:linear-gradient(135deg,#14b8a6,#0d9488);color:#fff;border-radius:16px;padding:2.5rem;margin-bottom:1.5rem;box-shadow:0 8px 24px rgba(20,184,166,0.3);position:relative;overflow:hidden}
.handover-summary::before{content:'';position:absolute;top:-30%;right:-10%;width:250px;height:250px;background:radial-gradient(circle,rgba(255,255,255,0.1),transparent);border-radius:50%}
.handover-summary h3{margin:0 0 0.5rem;font-size:1.5rem;font-weight:800}
.handover-summary p{opacity:0.9;margin:0 0 1.5rem}
.summary-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1.25rem;margin-top:1.5rem}
.summary-item{text-align:center;padding:1.5rem;background:rgba(255,255,255,0.15);border-radius:12px;backdrop-filter:blur(10px);border:1px solid rgba(255,255,255,0.2)}
.summary-value{font-size:2.25rem;font-weight:800;margin:0.5rem 0;line-height:1}
.summary-label{font-size:0.8125rem;opacity:0.9;text-transform:uppercase;letter-spacing:0.5px;font-weight:600}
.breakdown-card{background:#fff;border-radius:16px;padding:2rem;box-shadow:0 4px 16px rgba(0,0,0,0.06);margin-bottom:1.5rem;border:1px solid rgba(20,184,166,0.08)}
.breakdown-card h4{font-size:1.125rem;font-weight:700;margin:0 0 1.5rem;color:#0f172a;display:flex;align-items:center;gap:0.5rem}
.breakdown-card h4 i{color:#14b8a6}
.fee-row{display:flex;justify-content:space-between;align-items:center;padding:1rem;background:linear-gradient(135deg,#f8fafc,#fff);border-radius:10px;margin-bottom:0.75rem;border:1px solid rgba(20,184,166,0.08);transition:all 0.3s}
.fee-row:hover{border-color:rgba(20,184,166,0.2);box-shadow:0 4px 12px rgba(20,184,166,0.08)}
.fee-name{font-weight:700;color:#0f172a;text-transform:capitalize}
.fee-amount{font-weight:700;color:#14b8a6;font-size:1.125rem}
.denomination-table{width:100%;border-collapse:separate;border-spacing:0 0.5rem}
.denomination-table td{padding:0.875rem;background:#f8fafc;border-radius:8px;font-weight:600}
.denomination-table input{width:100px;text-align:center;border:2px solid #e2e8f0;border-radius:8px;padding:0.625rem;font-weight:700;transition:all 0.3s}
.denomination-table input:focus{outline:none;border-color:#14b8a6;box-shadow:0 0 0 4px rgba(20,184,166,0.1)}
.denomination-table .total-row{background:linear-gradient(135deg,#14b8a6,#0d9488);color:#fff}
.denomination-table .total-row td{font-size:1.125rem}
.signature-section{display:grid;grid-template-columns:1fr 1fr;gap:2.5rem;margin-top:2.5rem;padding-top:2.5rem;border-top:2px dashed #e2e8f0}
.signature-box{text-align:center}
.signature-box p{color:#64748b;margin-bottom:1.5rem;font-weight:600}
.signature-line{border-top:3px solid #0f172a;margin-top:4rem;padding-top:0.75rem;font-weight:700;color:#0f172a}
.signature-date{color:#94a3b8;font-size:0.875rem;margin-top:0.5rem;font-weight:600}
.action-buttons{display:flex;gap:1rem;margin-top:2rem}
.btn-handover{flex:1;padding:1rem 2rem;border:none;border-radius:10px;font-weight:700;font-size:1rem;cursor:pointer;transition:all 0.3s;display:flex;align-items:center;justify-content:center;gap:0.5rem}
.btn-print{background:linear-gradient(135deg,#14b8a6,#0d9488);color:#fff;box-shadow:0 4px 12px rgba(20,184,166,0.3)}
.btn-print:hover{transform:translateY(-2px);box-shadow:0 6px 20px rgba(20,184,166,0.4)}
.btn-export{background:linear-gradient(135deg,#f59e0b,#d97706);color:#fff;box-shadow:0 4px 12px rgba(245,158,11,0.3)}
.btn-export:hover{transform:translateY(-2px);box-shadow:0 6px 20px rgba(245,158,11,0.4)}
.btn-generate{background:linear-gradient(135deg,#14b8a6,#0d9488);color:#fff;box-shadow:0 4px 12px rgba(20,184,166,0.3)}
.btn-generate:hover{transform:translateY(-2px);box-shadow:0 6px 20px rgba(20,184,166,0.4)}
</style>

<div class="handover-header">
    <h2><i class="fa fa-exchange-alt"></i> Collector Handover Report</h2>
    <p>Professional cash reconciliation and shift handover documentation</p>
</div>

<div class="handover-form">
    <div class="row">
        <div class="col-md-3">
            <div class="form-group">
                <label><i class="fa fa-calendar"></i> Handover Date</label>
                <input type="text" id="handover_date" class="form-control" placeholder="Select date">
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label><i class="fa fa-user"></i> Collector Name</label>
                <select id="collector_id" class="form-control">
                    <option value="">Select Collector</option>
                    <?php
                    $collectors=$this->db->get_where('admin',['level >='=>3])->result_array();
                    foreach($collectors as $c):
                    ?>
                    <option value="<?php echo $c['admin_id'];?>"><?php echo $c['name'];?></option>
                    <?php endforeach;?>
                </select>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label><i class="fa fa-clock"></i> Shift Period</label>
                <select id="shift" class="form-control">
                    <option value="morning">Morning (6AM-12PM)</option>
                    <option value="afternoon">Afternoon (12PM-6PM)</option>
                    <option value="full">Full Day</option>
                </select>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label>&nbsp;</label>
                <button class="btn-handover btn-generate btn-block" onclick="generateHandover()">
                    <i class="fa fa-file-alt"></i> Generate Report
                </button>
            </div>
        </div>
    </div>
</div>

<div id="handover_report" style="display:none">
    <div class="handover-summary">
        <h3><i class="fa fa-user-circle"></i> <span id="collector_name"></span></h3>
        <p><span id="report_date"></span> | <span id="report_shift"></span></p>
        <div class="summary-grid">
            <div class="summary-item">
                <div class="summary-label">Total Collected</div>
                <div class="summary-value" id="total_collected">₵ 0.00</div>
            </div>
            <div class="summary-item">
                <div class="summary-label">Transactions</div>
                <div class="summary-value" id="total_transactions">0</div>
            </div>
            <div class="summary-item">
                <div class="summary-label">Cash Payments</div>
                <div class="summary-value" id="cash_amount">₵ 0.00</div>
            </div>
            <div class="summary-item">
                <div class="summary-label">Mobile Money</div>
                <div class="summary-value" id="momo_amount">₵ 0.00</div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="breakdown-card">
                <h4><i class="fa fa-layer-group"></i> Collection by Fee Type</h4>
                <div id="fee_breakdown"></div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="breakdown-card">
                <h4><i class="fa fa-money-bill-wave"></i> Cash Denomination Count</h4>
                <table class="denomination-table">
                    <tr><td>₵ 200 x</td><td><input type="number" id="d_200" value="0" min="0"></td><td>=</td><td class="text-right" id="t_200">₵ 0.00</td></tr>
                    <tr><td>₵ 100 x</td><td><input type="number" id="d_100" value="0" min="0"></td><td>=</td><td class="text-right" id="t_100">₵ 0.00</td></tr>
                    <tr><td>₵ 50 x</td><td><input type="number" id="d_50" value="0" min="0"></td><td>=</td><td class="text-right" id="t_50">₵ 0.00</td></tr>
                    <tr><td>₵ 20 x</td><td><input type="number" id="d_20" value="0" min="0"></td><td>=</td><td class="text-right" id="t_20">₵ 0.00</td></tr>
                    <tr><td>₵ 10 x</td><td><input type="number" id="d_10" value="0" min="0"></td><td>=</td><td class="text-right" id="t_10">₵ 0.00</td></tr>
                    <tr><td>₵ 5 x</td><td><input type="number" id="d_5" value="0" min="0"></td><td>=</td><td class="text-right" id="t_5">₵ 0.00</td></tr>
                    <tr class="total-row"><td colspan="3">Total Cash Count</td><td class="text-right" id="cash_total">₵ 0.00</td></tr>
                </table>
            </div>
        </div>
    </div>

    <div class="breakdown-card">
        <h4><i class="fa fa-camera"></i> Photo Evidence</h4>
        <div class="row">
            <div class="col-md-6">
                <label>Cash Photo</label>
                <input type="file" accept="image/*" id="cash_photo" class="form-control" onchange="previewPhoto(this,'cash_preview')">
                <img id="cash_preview" style="max-width:100%;margin-top:1rem;border-radius:8px;display:none">
            </div>
            <div class="col-md-6">
                <label>Receipts Photo</label>
                <input type="file" accept="image/*" id="receipt_photo" class="form-control" onchange="previewPhoto(this,'receipt_preview')">
                <img id="receipt_preview" style="max-width:100%;margin-top:1rem;border-radius:8px;display:none">
            </div>
        </div>
    </div>

    <div class="breakdown-card">
        <div class="signature-section">
            <div class="signature-box">
                <p><i class="fa fa-pen"></i> Collector Signature</p>
                <canvas id="collector_signature" width="300" height="150" style="border:2px solid #e2e8f0;border-radius:8px;cursor:crosshair;background:#fff"></canvas>
                <button class="btn-handover btn-print" style="margin-top:0.5rem;padding:0.5rem 1rem;font-size:0.875rem" onclick="clearSignature('collector_signature')"><i class="fa fa-eraser"></i> Clear</button>
                <p class="signature-date">Date: <span id="sig_date"></span></p>
            </div>
            <div class="signature-box">
                <p><i class="fa fa-pen"></i> Supervisor Signature</p>
                <canvas id="supervisor_signature" width="300" height="150" style="border:2px solid #e2e8f0;border-radius:8px;cursor:crosshair;background:#fff"></canvas>
                <button class="btn-handover btn-print" style="margin-top:0.5rem;padding:0.5rem 1rem;font-size:0.875rem" onclick="clearSignature('supervisor_signature')"><i class="fa fa-eraser"></i> Clear</button>
                <p class="signature-date">Date: <span id="sig_date2"></span></p>
            </div>
        </div>
    </div>

    <div class="action-buttons">
        <button class="btn-handover btn-generate" onclick="saveHandover()"><i class="fa fa-save"></i> Save Handover</button>
        <button class="btn-handover btn-print" onclick="printHandover()"><i class="fa fa-print"></i> Print Report</button>
        <button class="btn-handover btn-export" onclick="exportHandover()"><i class="fa fa-file-pdf"></i> Export PDF</button>
    </div>
</div>

<div class="breakdown-card">
    <h4><i class="fa fa-history"></i> Handover History</h4>
    <button class="btn-handover btn-primary" onclick="loadHandoverHistory()"><i class="fa fa-sync"></i> Load History</button>
    <div id="history_list" style="margin-top:1.5rem"></div>
</div>

<script src="<?php echo base_url(); ?>assets/cdn/js/flatpickr.min.js"></script>
<script>
let handoverPicker;
const currency='<?php echo $this->db->get_where("settings",["type"=>"currency"])->row()->description;?>';

function generateHandover(){
    const date=$('#handover_date').val();
    const collectorId=$('#collector_id').val();
    const shift=$('#shift').val();
    
    if(!collectorId){showAjaxModal_alert('Please select a collector','error');return;}
    if(!date){showAjaxModal_alert('Please select a date','error');return;}
    
    $.get('<?php echo site_url("fee_collection/get_collector_summary");?>/'+collectorId+'/'+date+'/'+shift,function(data){
        const d=typeof data==='string'?JSON.parse(data):data;
        populateHandover(d);
        $('#handover_report').slideDown();
    }).fail(function(){
        populateHandover({total:0,transactions:0,cash:0,momo:0,by_fee_type:{}});
        $('#handover_report').slideDown();
    });
}

function populateHandover(data){
    $('#collector_name').text($('#collector_id option:selected').text());
    $('#report_date').text($('#handover_date').val());
    $('#report_shift').text($('#shift option:selected').text());
    $('#total_collected').text(currency+' '+((data.total||0)/1).toLocaleString('en-US',{minimumFractionDigits:2}));
    $('#total_transactions').text((data.transactions||0).toLocaleString());
    $('#cash_amount').text(currency+' '+((data.cash||0)/1).toLocaleString('en-US',{minimumFractionDigits:2}));
    $('#momo_amount').text(currency+' '+((data.momo||0)/1).toLocaleString('en-US',{minimumFractionDigits:2}));
    
    let feeHtml='';
    const fees=data.by_fee_type||{};
    Object.keys(fees).forEach(k=>{
        feeHtml+=`<div class="fee-row"><span class="fee-name">${k}</span><span class="fee-amount">${currency} ${(fees[k]||0).toLocaleString('en-US',{minimumFractionDigits:2})}</span></div>`;
    });
    $('#fee_breakdown').html(feeHtml||'<p style="text-align:center;color:#94a3b8;padding:2rem">No fee data available</p>');
    
    $('#sig_collector').text($('#collector_id option:selected').text());
    $('#sig_date,#sig_date2').text(new Date().toLocaleDateString());
}

$('.denomination-table input').on('input',function(){
    const denom=parseInt($(this).attr('id').split('_')[1]);
    const count=parseInt($(this).val())||0;
    const total=denom*count;
    $('#t_'+denom).text(currency+' '+total.toFixed(2));
    
    let cashTotal=0;
    [200,100,50,20,10,5].forEach(d=>cashTotal+=(d*(parseInt($('#d_'+d).val())||0)));
    $('#cash_total').text(currency+' '+cashTotal.toFixed(2));
});

let collectorCanvas,supervisorCanvas,isDrawing=false;

function initSignaturePads(){
    collectorCanvas=document.getElementById('collector_signature');
    supervisorCanvas=document.getElementById('supervisor_signature');
    [collectorCanvas,supervisorCanvas].forEach(canvas=>{
        const ctx=canvas.getContext('2d');
        ctx.strokeStyle='#000';
        ctx.lineWidth=2;
        ctx.lineCap='round';
        canvas.addEventListener('mousedown',e=>{isDrawing=true;ctx.beginPath();ctx.moveTo(e.offsetX,e.offsetY)});
        canvas.addEventListener('mousemove',e=>{if(isDrawing){ctx.lineTo(e.offsetX,e.offsetY);ctx.stroke()}});
        canvas.addEventListener('mouseup',()=>isDrawing=false);
        canvas.addEventListener('mouseout',()=>isDrawing=false);
    });
}

function clearSignature(canvasId){
    const canvas=document.getElementById(canvasId);
    canvas.getContext('2d').clearRect(0,0,canvas.width,canvas.height);
}

function previewPhoto(input,previewId){
    if(input.files&&input.files[0]){
        const reader=new FileReader();
        reader.onload=e=>{
            $('#'+previewId).attr('src',e.target.result).show();
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function saveHandover(){
    const formData=new FormData();
    formData.append('collector_id',$('#collector_id').val());
    formData.append('date',$('#handover_date').val());
    formData.append('shift',$('#shift').val());
    formData.append('total_collected',$('#total_collected').text().replace(currency,'').trim());
    formData.append('cash_counted',$('#cash_total').text().replace(currency,'').trim());
    formData.append('collector_signature',collectorCanvas.toDataURL());
    formData.append('supervisor_signature',supervisorCanvas.toDataURL());
    if($('#cash_photo')[0].files[0])formData.append('cash_photo',$('#cash_photo')[0].files[0]);
    if($('#receipt_photo')[0].files[0])formData.append('receipt_photo',$('#receipt_photo')[0].files[0]);
    
    showAjaxModal_alert('Saving handover...','loading');
    $.ajax({
        url:'<?php echo site_url("fee_collection/save_handover");?>',
        type:'POST',
        data:formData,
        cache:false,
        contentType:false,
        processData:false,
        dataType:'json'
    }).done(function(response){
        if(response.status==='success'){
            showAjaxModal_alert(response.message,'success');
            setTimeout(()=>loadHandoverHistory(),2000);
        }else{
            showAjaxModal_alert(response.message||'Failed to save','error');
        }
    }).fail(function(){
        showAjaxModal_alert('An error occurred','error');
    });
}

function loadHandoverHistory(){
    $.get('<?php echo site_url("fee_collection/get_handover_history");?>',function(data){
        const history=typeof data==='string'?JSON.parse(data):data;
        let html='';
        history.forEach(h=>{
            const date=new Date(h.date);
            html+=`<div class="fee-row">
                <div>
                    <div class="fee-name">${h.collector_name} - ${h.shift}</div>
                    <div style="font-size:0.875rem;color:#64748b;margin-top:0.25rem">${date.toLocaleDateString()} | ${currency} ${parseFloat(h.total_collected).toLocaleString('en-US',{minimumFractionDigits:2})}</div>
                </div>
                <button class="btn-handover btn-print" style="padding:0.5rem 1rem;font-size:0.875rem" onclick="viewHandover(${h.id})"><i class="fa fa-eye"></i> View</button>
            </div>`;
        });
        $('#history_list').html(html||'<p style="text-align:center;color:#94a3b8;padding:2rem">No handover history found</p>');
    });
}

function viewHandover(id){
    window.open('<?php echo site_url("fee_collection/view_handover");?>/'+id,'_blank');
}

function printHandover(){window.print()}
function exportHandover(){window.location.href='<?php echo site_url("fee_collection/export_handover");?>/'+$('#collector_id').val()+'/'+$('#handover_date').val()}

$(document).ready(function(){
    handoverPicker=flatpickr('#handover_date',{dateFormat:'Y-m-d',defaultDate:'today',maxDate:'today'});
    initSignaturePads();
    loadHandoverHistory();
});
</script>
