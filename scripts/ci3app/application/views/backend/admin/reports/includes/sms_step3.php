<?php
/**
 * SMS Debtors Wizard — Step 3: Result (AJAX fragment)
 * Filled client-side by step 2's send handler (#smsResTitle, #smsResBody, #smsResSkip).
 */
?>
<style>
.sms-wiz { font-family: 'Inter', -apple-system, 'Segoe UI', Roboto, sans-serif; color: #1f2937; }
.sms-steps { display: flex; gap: 8px; margin-bottom: 18px; }
.sms-step-dot { flex: 1; text-align: center; font-size: 11px; font-weight: 700; padding: 7px 4px; border-radius: 8px; background: #f3f4f6; color: #9ca3af; }
.sms-step-dot.active { background: linear-gradient(135deg, #667eea, #764ba2); color: #fff; }
.sms-step-dot.done { background: #d1fae5; color: #065f46; }
.sms-result { text-align: center; padding: 28px 16px; }
.sms-result .big-ico { font-size: 52px; color: #10b981; }
.sms-result h3 { font-size: 19px; font-weight: 800; margin: 12px 0 6px; }
.sms-result .body { font-size: 13.5px; color: #4b5563; line-height: 1.6; }
.sms-skip { display: none; background: #fef2f2; border: 1px solid #fecaca; color: #b91c1c; border-radius: 10px; padding: 9px 12px; font-size: 12.5px; margin-top: 14px; text-align: left; }
.sms-foot { display: flex; justify-content: center; margin-top: 18px; padding-top: 14px; border-top: 2px solid #e5e7eb; }
.sms-btn { border: none; border-radius: 9px; padding: 10px 26px; font-weight: 700; font-size: 13px; cursor: pointer; display: inline-flex; align-items: center; gap: 7px; }
.sms-btn-go { background: linear-gradient(135deg, #667eea, #764ba2); color: #fff; }
.sms-btn-go:hover { transform: translateY(-1px); box-shadow: 0 6px 14px rgba(102,126,234,.4); }
</style>

<div class="sms-wiz">
    <div class="sms-steps">
        <div class="sms-step-dot done"><i class="fa fa-check"></i> 1 · Compose</div>
        <div class="sms-step-dot done"><i class="fa fa-check"></i> 2 · Preview</div>
        <div class="sms-step-dot active">3 · Result</div>
    </div>

    <div class="sms-result">
        <div class="big-ico"><i class="fa fa-paper-plane"></i></div>
        <h3 id="smsResTitle">Sending…</h3>
        <div class="body" id="smsResBody">Your messages are being submitted to the SMS gateway.</div>
        <div class="sms-skip" id="smsResSkip"></div>
        <div class="sms-foot">
            <button type="button" class="sms-btn sms-btn-go" data-dismiss="modal"><i class="fa fa-check"></i> Done</button>
        </div>
    </div>
</div>
