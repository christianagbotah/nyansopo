<div class="p-6">
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900">My Completion Status</h1>
        <p class="text-gray-600 mt-2">Complete all assessments before submitting for approval</p>
    </div>

    <!-- Summary Cards -->
    <div id="summaryCards" class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow p-4">
            <div class="text-sm text-gray-600">Total Assignments</div>
            <div class="text-2xl font-bold text-gray-900" id="totalAssignments">-</div>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <div class="text-sm text-gray-600">Completed</div>
            <div class="text-2xl font-bold text-green-600" id="completedCount">-</div>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <div class="text-sm text-gray-600">Incomplete</div>
            <div class="text-2xl font-bold text-red-600" id="incompleteCount">-</div>
        </div>
    </div>

    <!-- Assignment Cards -->
    <div id="assignmentGrid" class="space-y-4">
        <div class="text-center py-12">
            <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
            <p class="mt-4 text-gray-600">Loading assignments...</p>
        </div>
    </div>
</div>

<!-- Missing Students Modal -->
<div id="missingModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-lg bg-white">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-xl font-bold">Missing Entries</h3>
            <button onclick="closeMissingModal()" class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        <div id="missingContent" class="max-h-96 overflow-y-auto"></div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    loadAssignments();
});

function loadAssignments() {
    $.post('<?php echo site_url('teacher_completion/get_my_assignments'); ?>', function(response) {
        if(response.status === 'success') {
            renderAssignments(response.data);
            updateSummary(response.data);
        }
    }, 'json');
}

function updateSummary(data) {
    const total = data.length;
    const completed = data.filter(d => 
        d.portfolio_completion === 100 && 
        d.sba_completion === 100 && 
        d.exam_completion === 100
    ).length;
    
    document.getElementById('totalAssignments').textContent = total;
    document.getElementById('completedCount').textContent = completed;
    document.getElementById('incompleteCount').textContent = total - completed;
}

function renderAssignments(data) {
    const grid = document.getElementById('assignmentGrid');
    grid.innerHTML = '';
    
    if(data.length === 0) {
        grid.innerHTML = '<div class="text-center py-12 text-gray-500">No assignments found</div>';
        return;
    }
    
    data.forEach(item => {
        const isComplete = item.portfolio_completion === 100 && 
                          item.sba_completion === 100 && 
                          item.exam_completion === 100;
        
        const borderColor = isComplete ? 'border-green-500' : 'border-red-500';
        const bgColor = isComplete ? 'bg-green-50' : 'bg-red-50';
        
        const card = `
            <div class="bg-white rounded-lg shadow-lg border-l-4 ${borderColor} p-6">
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <h3 class="text-lg font-bold text-gray-900">${item.class_name}</h3>
                        <p class="text-sm text-gray-600">${item.subject_name}</p>
                        <p class="text-xs text-gray-500 mt-1">${item.total_students} students</p>
                    </div>
                    ${isComplete ? 
                        '<span class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-xs font-semibold">COMPLETE</span>' :
                        '<span class="px-3 py-1 bg-red-100 text-red-800 rounded-full text-xs font-semibold">INCOMPLETE</span>'
                    }
                </div>
                
                <div class="space-y-3 mb-4">
                    <div>
                        <div class="flex justify-between text-sm mb-1">
                            <span class="text-gray-600">Portfolio</span>
                            <span class="font-semibold ${item.portfolio_completion === 100 ? 'text-green-600' : 'text-red-600'}">
                                ${item.portfolio_completion}%
                            </span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-purple-600 h-2 rounded-full" style="width: ${item.portfolio_completion}%"></div>
                        </div>
                    </div>
                    
                    <div>
                        <div class="flex justify-between text-sm mb-1">
                            <span class="text-gray-600">SBA</span>
                            <span class="font-semibold ${item.sba_completion === 100 ? 'text-green-600' : 'text-red-600'}">
                                ${item.sba_completion}%
                            </span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-blue-600 h-2 rounded-full" style="width: ${item.sba_completion}%"></div>
                        </div>
                    </div>
                    
                    <div>
                        <div class="flex justify-between text-sm mb-1">
                            <span class="text-gray-600">Exams</span>
                            <span class="font-semibold ${item.exam_completion === 100 ? 'text-green-600' : 'text-red-600'}">
                                ${item.exam_completion}%
                            </span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-green-600 h-2 rounded-full" style="width: ${item.exam_completion}%"></div>
                        </div>
                    </div>
                </div>
                
                ${!isComplete && item.missing_students.length > 0 ? `
                    <div class="mt-4 p-3 ${bgColor} rounded-lg">
                        <div class="flex justify-between items-center">
                            <span class="text-sm font-semibold text-red-800">
                                ${item.missing_students.length} students with missing entries
                            </span>
                            <button onclick='viewMissing(${JSON.stringify(item.missing_students)})' 
                                    class="text-xs bg-red-600 text-white px-3 py-1 rounded hover:bg-red-700">
                                View Details
                            </button>
                        </div>
                    </div>
                ` : ''}
                
                ${!isComplete ? `
                    <div class="mt-4 p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                        <p class="text-sm text-yellow-800">
                            <strong>⚠️ Warning:</strong> Complete all assessments before submitting for approval
                        </p>
                    </div>
                ` : ''}
            </div>
        `;
        
        grid.innerHTML += card;
    });
}

function viewMissing(students) {
    const content = document.getElementById('missingContent');
    let html = '<div class="space-y-2">';
    
    students.forEach(student => {
        html += `
            <div class="p-3 bg-red-50 border border-red-200 rounded">
                <div class="font-semibold text-gray-900">${student.student_name}</div>
                <div class="text-sm text-red-600">Missing: ${student.missing}</div>
            </div>
        `;
    });
    
    html += '</div>';
    content.innerHTML = html;
    document.getElementById('missingModal').classList.remove('hidden');
}

function closeMissingModal() {
    document.getElementById('missingModal').classList.add('hidden');
}
</script>
