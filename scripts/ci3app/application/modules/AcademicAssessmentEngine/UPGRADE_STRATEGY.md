# Portfolio Assessment Engine - Upgrade Strategy

## Analysis of Existing System

### Current Structure
- **Table**: `portfolio_assessment` (single table)
- **Fields**: student_id, class_id, subject_id, week, timestamp, code, strand_score, assessment_id
- **UI**: Week-based spreadsheet with 5 days (Monday-Friday)
- **Logic**: Uses `class_name == 'JHSS'` check (❌ needs upgrade to category_id)
- **Permission**: Teacher subject assignment + class teacher check

### Key Observations
1. ✅ Already has week-based entry
2. ✅ Already has spreadsheet-style UI
3. ✅ Already has today highlighting
4. ✅ Already has preview mode
5. ❌ No SBA integration
6. ❌ No auto-computation
7. ❌ No audit trail
8. ❌ Uses class_name instead of category_id

## Upgrade Strategy

### Phase 1: Database Enhancement (Non-Breaking)
```sql
-- Add new enterprise tables (coexist with old table)
-- Migration already created in database_migration.sql

-- Add category_id to class table if missing
ALTER TABLE `class` 
ADD COLUMN IF NOT EXISTS `category_id` INT NULL AFTER `name`,
ADD INDEX idx_category (category_id);

-- Populate category_id based on class names
UPDATE `class` SET category_id = 1 WHERE name LIKE '%JHS%' OR name = 'JHSS';
UPDATE `class` SET category_id = 2 WHERE name LIKE '%SHS%';
UPDATE `class` SET category_id = 3 WHERE name LIKE '%Primary%' OR name LIKE '%Creche%';
```

### Phase 2: Backward Compatible Controller

**New Controller**: `Portfolio_enterprise.php` (separate from existing)
**Old Controller**: Keep `Admin::portfolio_assessment_*` methods unchanged

```php
// application/controllers/Portfolio_enterprise.php
class Portfolio_enterprise extends CI_Controller {
    
    public function __construct() {
        parent::__construct();
        $this->load->model('modules/AcademicAssessmentEngine/models/Portfolio_model');
        $this->load->library('modules/AcademicAssessmentEngine/services/Portfolio_computation_service');
        $this->load->library('modules/AcademicAssessmentEngine/middleware/Portfolio_permission_middleware');
    }
    
    public function index() {
        // New enterprise UI
        $page_data['page_name'] = 'portfolio_enterprise/dashboard';
        $page_data['page_title'] = 'Portfolio Assessment (Enterprise)';
        $this->load->view('backend/index', $page_data);
    }
    
    public function manage($class_id = null, $subject_id = null) {
        // Permission check
        $permission = $this->portfolio_permission_middleware->check_access($subject_id, $class_id);
        if(!$permission['allowed']) {
            show_error('Access denied', 403);
        }
        
        // Use category_id instead of class_name
        $class = $this->db->get_where('class', ['class_id' => $class_id])->row();
        $category_id = $class->category_id;
        
        // Load appropriate view based on category
        $page_data['class_id'] = $class_id;
        $page_data['subject_id'] = $subject_id;
        $page_data['category_id'] = $category_id;
        $page_data['page_name'] = 'portfolio_enterprise/manage';
        $page_data['page_title'] = 'Manage Portfolio';
        $this->load->view('backend/index', $page_data);
    }
    
    public function save_scores() {
        $header_id = $this->input->post('header_id');
        $scores = $this->input->post('scores');
        
        $this->db->trans_start();
        
        // Save scores
        $this->Portfolio_model->save_scores($header_id, $scores);
        
        // Get header details
        $header = $this->db->get_where('portfolio_headers', ['header_id' => $header_id])->row();
        
        // Compute averages for all students
        $students = array_column($scores, 'student_id');
        foreach($students as $student_id) {
            $this->portfolio_computation_service->compute_term_average(
                $student_id, 
                $header->subject_id, 
                $header->class_id, 
                $header->year, 
                $header->term, 
                $header->semester
            );
            
            // Auto-sync to SBA if enabled
            $this->portfolio_computation_service->sync_to_sba(
                $student_id, 
                $header->subject_id, 
                $header->class_id, 
                $header->year, 
                $header->term, 
                $header->semester
            );
        }
        
        $this->db->trans_complete();
        
        echo json_encode(['status' => 'success', 'message' => 'Scores saved and SBA updated']);
    }
}
```

### Phase 3: Migration Script (Old → New)

```php
// application/controllers/Portfolio_migration.php
public function migrate_old_to_new() {
    $this->db->trans_start();
    
    // Get all unique portfolio sessions from old table
    $sessions = $this->db->select('DISTINCT class_id, subject_id, year, term, sem, week, timestamp')
        ->from('portfolio_assessment')
        ->get()->result_array();
    
    foreach($sessions as $session) {
        // Create header
        $header_data = [
            'class_id' => $session['class_id'],
            'subject_id' => $session['subject_id'],
            'teacher_id' => $this->db->get_where('subject', ['subject_id' => $session['subject_id']])->row()->teacher_id,
            'year' => $session['year'],
            'term' => $session['term'],
            'semester' => $session['sem'],
            'week_number' => (int)explode('-W', $session['week'])[1],
            'strand_topic' => $this->db->get_where('portfolio_assessment', [
                'timestamp' => $session['timestamp'],
                'subject_id' => $session['subject_id']
            ])->row()->code ?? 'Migrated',
            'assessment_date' => date('Y-m-d', $session['timestamp']),
            'max_score' => 10,
            'status' => 'published',
            'created_by' => 1
        ];
        
        $this->db->insert('portfolio_headers', $header_data);
        $header_id = $this->db->insert_id();
        
        // Migrate scores
        $old_scores = $this->db->get_where('portfolio_assessment', [
            'class_id' => $session['class_id'],
            'subject_id' => $session['subject_id'],
            'week' => $session['week'],
            'timestamp' => $session['timestamp']
        ])->result_array();
        
        foreach($old_scores as $old_score) {
            $this->db->insert('portfolio_scores', [
                'header_id' => $header_id,
                'student_id' => $old_score['student_id'],
                'score' => $old_score['strand_score'],
                'recorded_by' => 1
            ]);
        }
    }
    
    $this->db->trans_complete();
    
    echo "Migration complete!";
}
```

### Phase 4: Dual System Operation

**Navigation Menu**:
```php
<li class="has-sub">
    <a href="javascript:;">
        <i class="fa fa-clipboard-list"></i>
        <span>Portfolio Assessment</span>
    </a>
    <ul>
        <!-- Old system (keep for compatibility) -->
        <li><a href="<?php echo site_url('admin/portfolio_assessment_manage'); ?>">
            <i class="fa fa-list"></i> Classic View
        </a></li>
        
        <!-- New enterprise system -->
        <li><a href="<?php echo site_url('portfolio_enterprise'); ?>">
            <i class="fa fa-chart-line"></i> Enterprise View (New)
        </a></li>
        
        <!-- SBA Management -->
        <li><a href="<?php echo site_url('portfolio_enterprise/sba_management'); ?>">
            <i class="fa fa-graduation-cap"></i> SBA Management
        </a></li>
    </ul>
</li>
```

## Key Differences: Old vs New

| Feature | Old System | New System |
|---------|-----------|------------|
| **Table Structure** | Single table | 5 normalized tables |
| **Class Detection** | `class_name == 'JHSS'` | `category_id = 1` |
| **Computation** | Manual | Auto-compute + SBA sync |
| **Audit Trail** | None | Complete audit log |
| **Permissions** | Basic | Role-based middleware |
| **UI** | Bootstrap | Tailwind + Flowbite |
| **SBA Integration** | None | Auto-fill class_test |
| **Aggregates** | None | Cached in aggregates table |

## Implementation Roadmap

### Week 1: Foundation
- ✅ Create database tables
- ✅ Create computation service
- ✅ Create permission middleware
- ✅ Create models

### Week 2: Controller & Views
- Create Portfolio_enterprise controller
- Create Tailwind-based views
- Implement spreadsheet-style entry
- Add auto-save functionality

### Week 3: SBA Integration
- Create SBA management interface
- Implement auto-sync logic
- Add manual override capability
- Create SBA reports

### Week 4: Migration & Testing
- Create migration script
- Test dual system operation
- Train users
- Deploy to production

## Rollback Plan

If issues arise:
1. Keep old system active
2. New system runs in parallel
3. Can switch back anytime
4. No data loss (both systems coexist)

## Success Metrics

- ✅ Old system continues working
- ✅ New system adds SBA auto-fill
- ✅ Uses category_id instead of class_name
- ✅ Complete audit trail
- ✅ Teacher permissions enforced
- ✅ Auto-computation working
- ✅ No breaking changes

---

**Status**: Strategy Complete
**Risk**: Low (parallel systems)
**Timeline**: 4 weeks
**Backward Compatible**: Yes ✅
