<div class="p-6">
    <!-- Header -->
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Academic Control Dashboard</h1>
        <p class="text-gray-600 mt-2">Monitor result readiness and approval status across all classes</p>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow p-4 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Academic Year</label>
                <select id="year" class="w-full border-gray-300 rounded-lg">
                    <option value="<?php echo $this->db->get_where('settings', ['type' => 'running_year'])->row()->description; ?>">
                        <?php echo $this->db->get_where('settings', ['type' => 'running_year'])->row()->description; ?>
                    </option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Term</label>
                <select id="term" class="w-full border-gray-300 rounded-lg">
                    <?php $term = $this->db->get_where('settings', ['type' => 'running_term'])->row()->description; ?>
                    <option value="1" <?php echo $term == '1' ? 'selected' : ''; ?>>Term 1</option>
                    <option value="2" <?php echo $term == '2' ? 'selected' : ''; ?>>Term 2</option>
                    <option value="3" <?php echo $term == '3' ? 'selected' : ''; ?>>Term 3</option>
                </select>
            </div>
            <div class="flex items-end">
                <button onclick="loadMetrics()" class="w-full bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                    Load Data
                </button>
            </div>
        </div>
    </div>

    <!-- Class Cards Grid -->
    <div id="classGrid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <!-- Loading state -->
        <div class="col-span-full text-center py-12">
            <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
            <p class="mt-4 text-gray-600">Loading class metrics...</p>
        </div>
    </div>
</div>

<!-- Audit Trail Modal -->
<div id="auditModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-lg bg-white">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-xl font-bold">Audit Trail</h3>
            <button onclick="closeAuditModal()" class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        <div id="auditContent" class="max-h-96 overflow-y-auto"></div>
    </div>
</div>

<script>
let currentYear, currentTerm;

document.addEventListener('DOMContentLoaded', function() {
    loadMetrics();
});

function loadMetrics() {
    currentYear = document.getElementById('year').value;
    currentTerm = document.getElementById('term').value;
    
    $.post('<?php echo site_url('academic_control/get_class_metrics'); ?>', {
        year: currentYear,
        term: currentTerm
    }, function(response) {
        if(response.status === 'success') {
            renderClassCards(response.data);
        }
    }, 'json');
}

function renderClassCards(data) {
    const grid = document.getElementById('classGrid');
    grid.innerHTML = '';
    
    data.forEach(item => {
        const statusColors = {
            'draft': 'bg-yellow-100 text-yellow-800 border-yellow-300',
            'submitted': 'bg-blue-100 text-blue-800 border-blue-300',
            'approved': 'bg-green-100 text-green-800 border-green-300',
            'locked': 'bg-red-100 text-red-800 border-red-300'
        };
        
        const card = `
            <div class="bg-white rounded-lg shadow-lg p-6 border-l-4 ${statusColors[item.status]}">
                <div class="flex justify-between items-start mb-4">
                    <h3 class="text-lg font-bold text-gray-900">${item.class_name}</h3>
                    <span class="px-3 py-1 rounded-full text-xs font-semibold ${statusColors[item.status]}">
                        ${item.status.toUpperCase()}
                    </span>
                </div>
                
                <!-- Progress Bars -->
                <div class="space-y-3 mb-4">
                    <div>
                        <div class="flex justify-between text-sm mb-1">
                            <span class="text-gray-600">Portfolio</span>
                            <span class="font-semibold">${item.portfolio_completion}%</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-purple-600 h-2 rounded-full" style="width: ${item.portfolio_completion}%"></div>
                        </div>
                    </div>
                    
                    <div>
                        <div class="flex justify-between text-sm mb-1">
                            <span class="text-gray-600">SBA</span>
                            <span class="font-semibold">${item.sba_completion}%</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-blue-600 h-2 rounded-full" style="width: ${item.sba_completion}%"></div>
                        </div>
                    </div>
                    
                    <div>
                        <div class="flex justify-between text-sm mb-1">
                            <span class="text-gray-600">Exams</span>
                            <span class="font-semibold">${item.exam_completion}%</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-green-600 h-2 rounded-full" style="width: ${item.exam_completion}%"></div>
                        </div>
                    </div>
                </div>
                
                <!-- Actions -->
                <div class="flex gap-2 flex-wrap">
                    ${getActionButtons(item)}
                </div>
            </div>
        `;
        grid.innerHTML += card;
    });
}

function getActionButtons(item) {
    let buttons = '';
    
    if(item.status === 'draft') {
        buttons += `<button onclick="submitResults(${item.class_id})" class="flex-1 bg-blue-600 text-white px-3 py-1 rounded text-sm hover:bg-blue-700">Submit</button>`;
    }
    
    if(item.status === 'submitted') {
        buttons += `<button onclick="approveResults(${item.class_id})" class="flex-1 bg-green-600 text-white px-3 py-1 rounded text-sm hover:bg-green-700">Approve</button>`;
    }
    
    if(item.status === 'approved') {
        buttons += `<button onclick="lockResults(${item.class_id})" class="flex-1 bg-red-600 text-white px-3 py-1 rounded text-sm hover:bg-red-700">Lock</button>`;
    }
    
    if(item.status === 'locked') {
        buttons += `<button onclick="unlockResults(${item.class_id})" class="flex-1 bg-orange-600 text-white px-3 py-1 rounded text-sm hover:bg-orange-700">Unlock</button>`;
    }
    
    buttons += `<button onclick="viewAudit(${item.class_id})" class="bg-gray-600 text-white px-3 py-1 rounded text-sm hover:bg-gray-700">Audit</button>`;
    
    return buttons;
}

function submitResults(classId) {
    const notes = prompt('Notes (optional):');
    $.post('<?php echo site_url('academic_control/submit_results'); ?>', {
        class_id: classId,
        year: currentYear,
        term: currentTerm,
        notes: notes
    }, function(response) {
        showAjaxModal_alert(response.message);
        if(response.status === 'success') loadMetrics();
    }, 'json');
}

function approveResults(classId) {
    const notes = prompt('Approval notes (optional):');
    $.post('<?php echo site_url('academic_control/approve_results'); ?>', {
        class_id: classId,
        year: currentYear,
        term: currentTerm,
        notes: notes
    }, function(response) {
        showAjaxModal_alert(response.message);
        if(response.status === 'success') loadMetrics();
    }, 'json');
}

function lockResults(classId) {
    showCustomConfirm('Lock results? This prevents further edits.', function() {
        const notes = prompt('Lock reason (optional):');
        $.post('<?php echo site_url('academic_control/lock_results'); ?>', {
            class_id: classId,
            year: currentYear,
            term: currentTerm,
            notes: notes
        }, function(response) {
            showAjaxModal_alert(response.message);
            if(response.status === 'success') loadMetrics();
        }, 'json');
    });
}

function unlockResults(classId) {
    const reason = prompt('Unlock reason (REQUIRED):');
    if(!reason) {
        showAjaxModal_alert('Reason is required for unlock');
        return;
    }
    $.post('<?php echo site_url('academic_control/unlock_results'); ?>', {
        class_id: classId,
        year: currentYear,
        term: currentTerm,
        reason: reason
    }, function(response) {
        showAjaxModal_alert(response.message);
        if(response.status === 'success') loadMetrics();
    }, 'json');
}

function viewAudit(classId) {
    $.post('<?php echo site_url('academic_control/get_audit_trail'); ?>', {
        class_id: classId,
        year: currentYear,
        term: currentTerm
    }, function(response) {
        if(response.status === 'success') {
            renderAuditTrail(response.data);
            document.getElementById('auditModal').classList.remove('hidden');
        }
    }, 'json');
}

function renderAuditTrail(data) {
    const content = document.getElementById('auditContent');
    if(data.length === 0) {
        content.innerHTML = '<p class="text-gray-500 text-center py-4">No audit records found</p>';
        return;
    }
    
    let html = '<div class="space-y-3">';
    data.forEach(item => {
        html += `
            <div class="border-l-4 border-blue-500 pl-4 py-2">
                <div class="flex justify-between">
                    <span class="font-semibold">${item.action.toUpperCase()}</span>
                    <span class="text-sm text-gray-500">${item.performed_at}</span>
                </div>
                <p class="text-sm text-gray-600">${item.previous_status} → ${item.new_status}</p>
                <p class="text-sm text-gray-700">By: ${item.performed_by_name || 'Unknown'}</p>
                ${item.reason ? `<p class="text-sm text-gray-600 italic">${item.reason}</p>` : ''}
            </div>
        `;
    });
    html += '</div>';
    content.innerHTML = html;
}

function closeAuditModal() {
    document.getElementById('auditModal').classList.add('hidden');
}
</script>
