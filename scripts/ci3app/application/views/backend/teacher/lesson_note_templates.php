<?php
$teacher_id = $this->session->userdata('login_user_id');
$this->load->model('Lesson_note_template_model');
$stats = $this->Lesson_note_template_model->get_teacher_stats($teacher_id);
$all_tags = $this->Lesson_note_template_model->get_all_tags();
?>

<style>
/* Modern Template Library Styles */
.template-library-container {
    max-width: 1600px;
    margin: 0 auto;
    padding: 24px;
}

.template-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 32px;
    border-radius: 16px;
    margin-bottom: 32px;
    box-shadow: 0 8px 24px rgba(102, 126, 234, 0.3);
}

.template-header h1 {
    margin: 0 0 8px 0;
    font-size: 32px;
    font-weight: 700;
}

.template-header p {
    margin: 0;
    opacity: 0.9;
    font-size: 16px;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
    margin-top: 24px;
}

.stat-card {
    background: rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(10px);
    padding: 20px;
    border-radius: 12px;
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.stat-value {
    font-size: 36px;
    font-weight: 700;
    margin-bottom: 4px;
}

.stat-label {
    font-size: 14px;
    opacity: 0.9;
}

.template-toolbar {
    background: white;
    padding: 24px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    margin-bottom: 24px;
}

.toolbar-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 16px;
}

.filter-group {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
    align-items: center;
}

.filter-input {
    padding: 12px 16px;
    border: 2px solid #e5e7eb;
    border-radius: 10px;
    font-size: 15px;
    transition: all 0.2s;
    min-width: 200px;
}

.filter-input:focus {
    border-color: #667eea;
    outline: none;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.filter-select {
    padding: 12px 40px 12px 16px;
    border: 2px solid #e5e7eb;
    border-radius: 10px;
    font-size: 15px;
    background: white;
    cursor: pointer;
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%236b7280'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 12px center;
    background-size: 18px;
    min-width: 180px;
}

.filter-select:focus {
    border-color: #667eea;
    outline: none;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.btn-enterprise {
    padding: 12px 24px;
    border-radius: 10px;
    font-weight: 600;
    font-size: 15px;
    border: none;
    cursor: pointer;
    transition: all 0.3s;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    text-decoration: none;
}

.btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(102, 126, 234, 0.4);
    color: white;
}

.btn-secondary {
    background: #f3f4f6;
    color: #374151;
}

.btn-secondary:hover {
    background: #e5e7eb;
}

.templates-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
    gap: 24px;
}

.template-card {
    background: white;
    border: 2px solid #e5e7eb;
    border-radius: 16px;
    padding: 24px;
    transition: all 0.3s;
    position: relative;
    cursor: pointer;
}

.template-card:hover {
    border-color: #667eea;
    box-shadow: 0 8px 24px rgba(102, 126, 234, 0.15);
    transform: translateY(-4px);
}

.template-card-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 16px;
}

.template-title {
    font-size: 20px;
    font-weight: 700;
    color: #1f2937;
    margin: 0 0 8px 0;
}

.template-description {
    color: #6b7280;
    font-size: 14px;
    line-height: 1.6;
    margin-bottom: 16px;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.template-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    margin-bottom: 16px;
}

.meta-badge {
    padding: 6px 12px;
    border-radius: 6px;
    font-size: 13px;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.meta-subject {
    background: #dbeafe;
    color: #1e40af;
}

.meta-class {
    background: #d1fae5;
    color: #065f46;
}

.meta-public {
    background: #fef3c7;
    color: #92400e;
}

.template-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-bottom: 16px;
    min-height: 32px;
}

.tag-badge {
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
    color: white;
}

.template-stats {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 16px;
    border-top: 1px solid #e5e7eb;
}

.stat-item {
    display: flex;
    align-items: center;
    gap: 6px;
    color: #6b7280;
    font-size: 14px;
}

.stat-item i {
    color: #9ca3af;
}

.template-actions {
    position: absolute;
    top: 20px;
    right: 20px;
    display: flex;
    gap: 8px;
}

.action-btn {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    border: none;
    background: #f3f4f6;
    color: #6b7280;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
}

.action-btn:hover {
    background: #667eea;
    color: white;
    transform: scale(1.1);
}

.action-btn.favorite.active {
    background: #fbbf24;
    color: white;
}

.empty-state {
    text-align: center;
    padding: 80px 20px;
}

.empty-state i {
    font-size: 80px;
    color: #d1d5db;
    margin-bottom: 24px;
}

.empty-state h3 {
    font-size: 24px;
    color: #374151;
    margin-bottom: 12px;
}

.empty-state p {
    color: #6b7280;
    font-size: 16px;
    margin-bottom: 24px;
}

@media (max-width: 768px) {
    .templates-grid {
        grid-template-columns: 1fr;
    }
    
    .toolbar-row {
        flex-direction: column;
        align-items: stretch;
    }
    
    .filter-group {
        flex-direction: column;
    }
    
    .filter-input, .filter-select {
        width: 100%;
    }
}
</style>

<div class="template-library-container">
    <!-- Header with Stats -->
    <div class="template-header">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 20px;">
            <div>
                <h1><i class="fa fa-file-text"></i> <?php echo get_phrase('lesson_note_templates'); ?></h1>
                <p><?php echo get_phrase('create_and_manage_reusable_lesson_structures'); ?></p>
            </div>
            <a href="<?php echo site_url('teacher/lesson_note_template_create'); ?>" class="btn-enterprise btn-primary" style="background: white; color: #667eea;">
                <i class="fa fa-plus"></i> <?php echo get_phrase('create_template'); ?>
            </a>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value"><?php echo $stats['total_templates']; ?></div>
                <div class="stat-label"><?php echo get_phrase('my_templates'); ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $stats['public_templates']; ?></div>
                <div class="stat-label"><?php echo get_phrase('shared_templates'); ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $stats['total_usage']; ?></div>
                <div class="stat-label"><?php echo get_phrase('times_used'); ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $stats['favorites']; ?></div>
                <div class="stat-label"><?php echo get_phrase('favorites'); ?></div>
            </div>
        </div>
    </div>
    
    <!-- Toolbar with Filters -->
    <div class="template-toolbar">
        <div class="toolbar-row">
            <div class="filter-group">
                <input type="text" id="search_input" class="filter-input" placeholder="<?php echo get_phrase('search_templates'); ?>..." />
                
                <select id="subject_filter" class="filter-select">
                    <option value=""><?php echo get_phrase('all_subjects'); ?></option>
                    <?php
                    $subjects = $this->db->get_where('subject', ['school_id' => $this->session->userdata('school_id')])->result();
                    foreach ($subjects as $subject):
                    ?>
                    <option value="<?php echo $subject->subject_id; ?>"><?php echo $subject->name; ?></option>
                    <?php endforeach; ?>
                </select>
                
                <select id="class_filter" class="filter-select">
                    <option value=""><?php echo get_phrase('all_classes'); ?></option>
                    <?php
                    $classes = $this->db->get('class')->result();
                    foreach ($classes as $class):
                    ?>
                    <option value="<?php echo $class->id; ?>"><?php echo $class->name; ?></option>
                    <?php endforeach; ?>
                </select>
                
                <select id="tag_filter" class="filter-select">
                    <option value=""><?php echo get_phrase('all_tags'); ?></option>
                    <?php foreach ($all_tags as $tag): ?>
                    <option value="<?php echo $tag->tag_id; ?>"><?php echo $tag->tag_name; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="filter-group">
                <button class="btn-enterprise btn-secondary" id="favorites_toggle">
                    <i class="fa fa-star"></i> <?php echo get_phrase('favorites_only'); ?>
                </button>
                
                <select id="sort_by" class="filter-select" style="min-width: 150px;">
                    <option value="usage_count"><?php echo get_phrase('most_used'); ?></option>
                    <option value="created_at"><?php echo get_phrase('newest_first'); ?></option>
                    <option value="name"><?php echo get_phrase('name_a_z'); ?></option>
                </select>
            </div>
        </div>
    </div>
    
    <!-- Templates Grid -->
    <div id="templates_container">
        <div class="templates-grid" id="templates_grid">
            <!-- Templates will be loaded here via AJAX -->
        </div>
    </div>
</div>

<script>
var LessonNoteTemplates = (function() {
    'use strict';
    
    var state = {
        templates: [],
        filters: {
            search: '',
            subject_id: '',
            class_id: '',
            tag_id: '',
            favorites_only: false,
            sort_by: 'usage_count',
            sort_order: 'DESC'
        }
    };
    
    function init() {
        loadTemplates();
        bindEvents();
    }
    
    function bindEvents() {
        // Search input
        $('#search_input').on('input', debounce(function() {
            state.filters.search = $(this).val();
            loadTemplates();
        }, 300));
        
        // Filter selects
        $('#subject_filter, #class_filter, #tag_filter').on('change', function() {
            var filterId = $(this).attr('id').replace('_filter', '_id');
            state.filters[filterId] = $(this).val();
            loadTemplates();
        });
        
        // Sort select
        $('#sort_by').on('change', function() {
            state.filters.sort_by = $(this).val();
            loadTemplates();
        });
        
        // Favorites toggle
        $('#favorites_toggle').on('click', function() {
            state.filters.favorites_only = !state.filters.favorites_only;
            $(this).toggleClass('btn-primary btn-secondary');
            loadTemplates();
        });
        
        // Template actions (delegated)
        $(document).on('click', '.template-card', function(e) {
            if ($(e.target).closest('.action-btn').length) return;
            var templateId = $(this).data('id');
            useTemplate(templateId);
        });
        
        $(document).on('click', '.action-favorite', function(e) {
            e.stopPropagation();
            var templateId = $(this).closest('.template-card').data('id');
            toggleFavorite(templateId, $(this));
        });
        
        $(document).on('click', '.action-edit', function(e) {
            e.stopPropagation();
            var templateId = $(this).closest('.template-card').data('id');
            window.location.href = base_url + 'teacher/lesson_note_template_edit/' + templateId;
        });
        
        $(document).on('click', '.action-duplicate', function(e) {
            e.stopPropagation();
            var templateId = $(this).closest('.template-card').data('id');
            duplicateTemplate(templateId);
        });
        
        $(document).on('click', '.action-delete', function(e) {
            e.stopPropagation();
            var templateId = $(this).closest('.template-card').data('id');
            deleteTemplate(templateId);
        });
    }
    
    function loadTemplates() {
        $.ajax({
            url: base_url + 'teacher/get_lesson_note_templates_ajax',
            type: 'GET',
            data: state.filters,
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    state.templates = response.templates;
                    renderTemplates();
                }
            },
            error: function() {
                showError('Failed to load templates');
            }
        });
    }
    
    function renderTemplates() {
        var $grid = $('#templates_grid');
        
        if (state.templates.length === 0) {
            $grid.html(
                '<div class="empty-state">' +
                '<i class="fa fa-file-text-o"></i>' +
                '<h3>No Templates Found</h3>' +
                '<p>Create your first template to get started</p>' +
                '<a href="' + base_url + 'teacher/lesson_note_template_create" class="btn-enterprise btn-primary">' +
                '<i class="fa fa-plus"></i> Create Template' +
                '</a>' +
                '</div>'
            );
            return;
        }
        
        var html = '';
        
        $.each(state.templates, function(index, template) {
            var tags = template.tags ? template.tags.split(', ') : [];
            var tagColors = template.tag_colors ? template.tag_colors.split(',') : [];
            var isFavorited = template.is_favorited > 0;
            var isOwner = template.teacher_id == <?php echo $teacher_id; ?>;
            
            html += '<div class="template-card" data-id="' + template.template_id + '">';
            
            // Actions
            html += '<div class="template-actions">';
            html += '<button class="action-btn action-favorite ' + (isFavorited ? 'active' : '') + '" title="Favorite">';
            html += '<i class="fa fa-star' + (isFavorited ? '' : '-o') + '"></i>';
            html += '</button>';
            if (isOwner) {
                html += '<button class="action-btn action-edit" title="Edit"><i class="fa fa-edit"></i></button>';
            }
            html += '<button class="action-btn action-duplicate" title="Duplicate"><i class="fa fa-copy"></i></button>';
            if (isOwner) {
                html += '<button class="action-btn action-delete" title="Delete"><i class="fa fa-trash"></i></button>';
            }
            html += '</div>';
            
            // Header
            html += '<div class="template-card-header">';
            html += '<div>';
            html += '<h3 class="template-title">' + escapeHtml(template.template_name) + '</h3>';
            if (template.description) {
                html += '<p class="template-description">' + escapeHtml(template.description) + '</p>';
            }
            html += '</div>';
            html += '</div>';
            
            // Meta badges
            html += '<div class="template-meta">';
            if (template.subject_name) {
                html += '<span class="meta-badge meta-subject"><i class="fa fa-book"></i> ' + template.subject_name + '</span>';
            }
            if (template.class_name) {
                html += '<span class="meta-badge meta-class"><i class="fa fa-users"></i> ' + template.class_name + '</span>';
            }
            if (template.is_public == 1) {
                html += '<span class="meta-badge meta-public"><i class="fa fa-globe"></i> Public</span>';
            }
            html += '</div>';
            
            // Tags
            html += '<div class="template-tags">';
            $.each(tags, function(i, tag) {
                var color = tagColors[i] || '#667eea';
                html += '<span class="tag-badge" style="background: ' + color + ';">' + tag + '</span>';
            });
            html += '</div>';
            
            // Stats
            html += '<div class="template-stats">';
            html += '<div class="stat-item"><i class="fa fa-user"></i> ' + template.teacher_name + '</div>';
            html += '<div class="stat-item"><i class="fa fa-check-circle"></i> Used ' + template.usage_count + ' times</div>';
            html += '</div>';
            
            html += '</div>';
        });
        
        $grid.html(html);
    }
    
    function useTemplate(templateId) {
        window.location.href = base_url + 'teacher/lesson_note_create?template_id=' + templateId;
    }
    
    function toggleFavorite(templateId, $btn) {
        $.ajax({
            url: base_url + 'teacher/toggle_template_favorite',
            type: 'POST',
            data: { template_id: templateId },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    $btn.toggleClass('active');
                    $btn.find('i').toggleClass('fa-star fa-star-o');
                    showSuccess(response.message);
                }
            }
        });
    }
    
    function duplicateTemplate(templateId) {
        showCustomConfirm('Create a copy of this template?', function() {
            $.ajax({
                url: base_url + 'teacher/duplicate_template',
                type: 'POST',
                data: { template_id: templateId },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        showSuccess(response.message);
                        loadTemplates();
                    }
                }
            });
        });
    }
    
    function deleteTemplate(templateId) {
        showCustomConfirm('Are you sure you want to delete this template?', function() {
            $.ajax({
                url: base_url + 'teacher/delete_template',
                type: 'POST',
                data: { template_id: templateId },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        showSuccess(response.message);
                        loadTemplates();
                    }
                }
            });
        });
    }
        });
    }
    
    function debounce(func, wait) {
        var timeout;
        return function() {
            var context = this, args = arguments;
            clearTimeout(timeout);
            timeout = setTimeout(function() {
                func.apply(context, args);
            }, wait);
        };
    }
    
    function escapeHtml(text) {
        var map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }
    
    function showSuccess(message) {
        toastr.success(message);
    }
    
    function showError(message) {
        toastr.error(message);
    }
    
    return {
        init: init
    };
})();

$(document).ready(function() {
    LessonNoteTemplates.init();
});
</script>
