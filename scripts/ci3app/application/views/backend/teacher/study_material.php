<script src="<?php echo base_url(); ?>assets/cdn/js/tailwindcss.js"></script>

<style>
    /* Enhanced readability styles */
    .study-materials-container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 0 0.5rem;
    }
    
    .material-card {
        transition: all 0.2s ease;
    }
    
    .material-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }
    
    .status-badge {
        font-weight: 600;
        letter-spacing: 0.025em;
    }
    
    /* Better table readability */
    #table-2 {
        font-size: 1rem;
        line-height: 1.7;
    }
    
    #table-2 thead th {
        font-weight: 600;
        letter-spacing: 0.05em;
        padding: 1rem;
        white-space: nowrap;
        font-size: 0.875rem;
    }
    
    #table-2 tbody td {
        padding: 1.25rem 1rem;
        vertical-align: middle;
        font-size: 1rem;
    }
    
    /* Enhanced file type icons */
    .file-icon {
        width: 32px;
        height: 32px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 6px;
        font-size: 14px;
    }
    
    /* Better action buttons */
    .action-btn {
        transition: all 0.15s ease;
        font-weight: 500;
    }
    
    .action-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
    }
    
    /* DataTable enhancements */
    .dataTables_wrapper .dataTables_length select,
    .dataTables_wrapper .dataTables_filter input {
        padding: 0.5rem 0.75rem;
        border: 1px solid #e5e7eb;
        border-radius: 0.5rem;
        font-size: 0.875rem;
    }
    
    .dataTables_wrapper .dataTables_paginate .paginate_button {
        padding: 0.5rem 0.75rem;
        margin: 0 0.25rem;
        border-radius: 0.375rem;
    }
    
    /* Mobile Responsive Enhancements */
    @media (max-width: 768px) {
        .study-materials-container {
            padding: 0 0.25rem;
        }
        
        /* Stack header content vertically on mobile */
        .bg-gradient-to-br .flex {
            flex-direction: column;
            gap: 1rem;
        }
        
        /* Make add button full width on mobile */
        .action-btn {
            width: 100%;
            justify-content: center;
        }
        
        /* Reduce padding on mobile */
        #table-2 thead th,
        #table-2 tbody td {
            padding: 0.75rem 0.5rem;
            font-size: 0.875rem;
        }
        
        /* Make badges smaller on mobile */
        .status-badge,
        .inline-flex.items-center {
            font-size: 0.75rem;
            padding: 0.375rem 0.5rem;
        }
        
        /* Adjust icon sizes for mobile */
        .file-icon {
            width: 24px;
            height: 24px;
            font-size: 12px;
        }
        
        /* Stack action buttons vertically on mobile */
        .flex.gap-2 {
            flex-direction: column;
            gap: 0.5rem;
        }
        
        .flex.gap-2 .action-btn {
            width: 100%;
        }
        
        /* Make DataTable responsive */
        .dataTables_wrapper {
            font-size: 0.875rem;
        }
        
        .dataTables_wrapper .dataTables_length,
        .dataTables_wrapper .dataTables_filter {
            margin-bottom: 0.75rem;
        }
        
        /* Improve touch targets */
        .action-btn {
            min-height: 44px;
            padding: 0.75rem 1rem;
        }
        
        /* Hide less important columns on mobile */
        @media (max-width: 640px) {
            #table-2 tbody td:nth-child(2), /* Period */
            #table-2 thead th:nth-child(2) {
                display: none;
            }
            
            #table-2 tbody td:nth-child(4), /* Description */
            #table-2 thead th:nth-child(4) {
                display: none;
            }
        }
    }
    
    /* Tablet optimizations */
    @media (min-width: 769px) and (max-width: 1024px) {
        #table-2 thead th,
        #table-2 tbody td {
            padding: 1rem 0.75rem;
            font-size: 0.9375rem;
        }
    }
    
    /* Touch-friendly improvements for all mobile devices */
    @media (hover: none) and (pointer: coarse) {
        .action-btn {
            min-height: 48px;
            padding: 1rem;
        }
        
        .material-card:active {
            transform: scale(0.98);
        }
        
        .action-btn:active {
            transform: scale(0.95);
        }
    }
</style>

<div class="study-materials-container">
    <div class="bg-gradient-to-br from-white to-gray-50 rounded-xl shadow-lg p-8 mb-6">
        <div class="flex flex-col md:flex-row md:justify-between md:items-center gap-4">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 mb-2 flex items-center gap-3">
                    <span class="bg-blue-100 text-blue-600 p-3 rounded-lg">
                        <i class="entypo-book-open text-2xl"></i>
                    </span>
                    <?php echo get_phrase('study_materials');?>
                </h1>
                <p class="text-gray-600 text-lg"><?php echo get_phrase('manage_and_share_study_materials');?></p>
            </div>
            <button onclick="showAjaxModal('<?php echo site_url('modal/popup/modal_study_material_add');?>');" 
                    class="action-btn bg-blue-600 hover:bg-blue-700 text-white px-6 py-3.5 rounded-lg font-semibold shadow-md flex items-center gap-2 whitespace-nowrap">
                <i class="entypo-plus text-xl"></i> 
                <span><?php echo get_phrase('add_study_material'); ?></span>
            </button>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full datatable" id="table-2">
                <thead class="bg-gradient-to-r from-gray-50 to-gray-100 border-b-2 border-gray-200">
                    <tr>
                        <th class="px-4 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">
                            <div class="flex items-center gap-2">
                                <i class="entypo-calendar text-gray-500"></i>
                                Created
                            </div>
                        </th>
                        <th class="px-4 py-4 text-center text-xs font-bold text-gray-700 uppercase tracking-wider">
                            <div class="flex items-center justify-center gap-2">
                                <i class="entypo-clock text-gray-500"></i>
                                Period
                            </div>
                        </th>
                        <th class="px-4 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">
                            <div class="flex items-center gap-2">
                                <i class="entypo-doc-text text-gray-500"></i>
                                Title
                            </div>
                        </th>
                        <th class="px-4 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">
                            <div class="flex items-center gap-2">
                                <i class="entypo-align-left text-gray-500"></i>
                                Description
                            </div>
                        </th>
                        <th class="px-4 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">
                            <div class="flex items-center gap-2">
                                <i class="entypo-users text-gray-500"></i>
                                Class
                            </div>
                        </th>
                        <th class="px-4 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">
                            <div class="flex items-center gap-2">
                                <i class="entypo-book text-gray-500"></i>
                                Subject
                            </div>
                        </th>
                        <th class="px-4 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">
                            <div class="flex items-center gap-2">
                                <i class="entypo-attach text-gray-500"></i>
                                File
                            </div>
                        </th>
                        <th class="px-4 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">
                            <div class="flex items-center gap-2">
                                <i class="entypo-info-circled text-gray-500"></i>
                                Status
                            </div>
                        </th>
                        <th class="px-4 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">
                            <div class="flex items-center gap-2">
                                <i class="entypo-cog text-gray-500"></i>
                                Actions
                            </div>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php
                    if(empty($study_material_info)) {
                        echo '<tr><td colspan="9" class="px-4 py-12 text-center text-gray-500">
                                <div class="flex flex-col items-center gap-3">
                                    <i class="entypo-folder text-6xl text-gray-300"></i>
                                    <p class="text-lg font-medium">No study materials found</p>
                                    <p class="text-sm">Click "Add Study Material" to create your first material</p>
                                </div>
                              </td></tr>';
                    }
                    
                    foreach ($study_material_info as $row) { 
                        $statusColour = $row['status'] == 'Pending' ? 'bg-yellow-400' : ($row['status'] == 'Approved' ? 'bg-green-500' : 'bg-red-500');
                        $statusBg = $row['status'] == 'Pending' ? 'bg-yellow-50' : ($row['status'] == 'Approved' ? 'bg-green-50' : 'bg-red-50');
                        $statusText = $row['status'] == 'Pending' ? 'text-yellow-700' : ($row['status'] == 'Approved' ? 'text-green-700' : 'text-red-700');
                        
                        // Get file extension for icon
                        $file_ext = strtolower($row['file_type']);
                        $file_icon = 'entypo-doc';
                        $file_color = 'bg-gray-100 text-gray-600';
                        
                        if($file_ext == 'pdf') {
                            $file_icon = 'entypo-doc-text';
                            $file_color = 'bg-red-100 text-red-600';
                        } elseif($file_ext == 'doc') {
                            $file_icon = 'entypo-doc-text';
                            $file_color = 'bg-blue-100 text-blue-600';
                        } elseif($file_ext == 'excel') {
                            $file_icon = 'entypo-chart-bar';
                            $file_color = 'bg-green-100 text-green-600';
                        } elseif($file_ext == 'image') {
                            $file_icon = 'entypo-picture';
                            $file_color = 'bg-purple-100 text-purple-600';
                        }
                    ?>
                    <tr class="material-card hover:bg-blue-50 transition-colors">
                        <td class="px-4 py-5">
                            <div class="flex items-center gap-2">
                                <i class="entypo-calendar text-gray-400 text-lg"></i>
                                <span class="text-base font-medium text-gray-700"><?php echo date("d M, Y", $row['timestamp']); ?></span>
                            </div>
                        </td>
                        <td class="px-4 py-5">
                            <div class="flex flex-col items-center gap-1">
                                <span class="font-semibold text-gray-700 text-base"><?php echo date("d M, Y", $row['start_date']); ?></span>
                                <span class="text-sm text-gray-400 font-medium">↓</span>
                                <span class="font-semibold text-gray-700 text-base"><?php echo date("d M, Y", $row['end_date']); ?></span>
                            </div>
                        </td>
                        <td class="px-4 py-5">
                            <div class="font-semibold text-gray-900 text-lg leading-relaxed">
                                <?php echo $row['title']?>
                            </div>
                        </td>
                        <td class="px-4 py-5">
                            <div class="text-base text-gray-600 leading-relaxed max-w-xs">
                                <?php echo substr($row['description'], 0, 80) . (strlen($row['description']) > 80 ? '...' : ''); ?>
                            </div>
                        </td>
                        <td class="px-4 py-5">
                            <div class="inline-flex items-center gap-2 bg-indigo-50 text-indigo-700 px-3 py-2 rounded-lg font-medium text-base">
                                <i class="entypo-users"></i>
                                <?php 
                                $class = $this->db->get_where('class', array('class_id' => $row['class_id']))->row();
                                $section = $this->db->get_where('section', array('class_id' => $row['class_id']))->row();
                                $section_name = $section ? $section->name : '';
                                
                                echo $class->name.' '.$class->name_numeric.' '.$section_name;
                                ?>
                            </div>
                        </td>
                        <td class="px-4 py-5">
                            <div class="inline-flex items-center gap-2 bg-purple-50 text-purple-700 px-3 py-2 rounded-lg font-medium text-base">
                                <i class="entypo-book"></i>
                                <?php echo $this->db->get_where('subject', array('subject_id' => $row['subject_id']))->row()->name;?>
                            </div>
                        </td>
                        <td class="px-4 py-5">
                            <a href="<?php echo base_url().$row['file_path']; ?>" 
                               class="action-btn inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2.5 rounded-lg font-semibold shadow-sm text-base">
                                <span class="file-icon <?php echo $file_color; ?>">
                                    <i class="<?php echo $file_icon; ?>"></i>
                                </span>
                                <span>Download</span>
                            </a>
                        </td>
                        <td class="px-4 py-5">
                            <span class="status-badge inline-flex items-center gap-2 px-4 py-2 rounded-full <?= $statusText;?> <?= $statusBg;?> border border-current border-opacity-20 text-sm">
                                <span class="w-2 h-2 rounded-full <?= $statusColour;?> animate-pulse"></span>
                                <?php echo $row['status'];?>
                            </span>
                        </td>
                        <td class="px-4 py-5">
                            <?php if($row['status'] == 'Approved'): ?>
                                <div class="flex items-center gap-2 text-gray-400 text-base">
                                    <i class="entypo-check"></i>
                                    <span class="font-medium">Approved</span>
                                </div>
                            <?php else: ?>
                                <div class="flex gap-2">
                                    <button onclick="showAjaxModal('<?php echo site_url('modal/popup/modal_study_material_edit/'.$row['document_id']);?>');" 
                                            class="action-btn inline-flex items-center gap-1.5 bg-green-50 hover:bg-green-100 text-green-700 px-3 py-2 rounded-lg font-semibold border border-green-200 text-base">
                                        <i class="entypo-pencil"></i> 
                                        <span>Edit</span>
                                    </button>
                                    <a href="<?php echo site_url('teacher/study_material/delete/'.$row['document_id']);?>" 
                                       onclick="event.preventDefault(); showCustomConfirm('Are you sure you want to delete this material?', function() { window.location.href = '<?php echo site_url('teacher/study_material/delete/'.$row['document_id']);?>'; });" 
                                       class="action-btn inline-flex items-center gap-1.5 bg-red-50 hover:bg-red-100 text-red-700 px-3 py-2 rounded-lg font-semibold border border-red-200 text-base">
                                        <i class="entypo-trash"></i> 
                                        <span>Delete</span>
                                    </a>
                                </div>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
$(document).ready(function() { 
    $("#table-2").dataTable({
        "pageLength": 10,
        "order": [[0, "desc"]], // Sort by created date descending
        "language": {
            "search": "Search materials:",
            "lengthMenu": "Show _MENU_ materials per page",
            "info": "Showing _START_ to _END_ of _TOTAL_ materials",
            "infoEmpty": "No materials available",
            "infoFiltered": "(filtered from _MAX_ total materials)",
            "zeroRecords": "No matching materials found",
            "paginate": {
                "first": "First",
                "last": "Last",
                "next": "Next →",
                "previous": "← Previous"
            }
        }
    }); 
});
</script>