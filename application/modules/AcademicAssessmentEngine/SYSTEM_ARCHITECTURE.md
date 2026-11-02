# Academic Assessment Engine - System Architecture

## 🏗️ Architecture Overview

```
┌─────────────────────────────────────────────────────────────┐
│                     Presentation Layer                       │
├─────────────────────────────────────────────────────────────┤
│  Admin Dashboard    │  Teacher Dashboard  │  Reports        │
│  - Control Panel    │  - Completion View  │  - Analytics    │
│  - Approval Actions │  - Missing Students │  - Audit Trail  │
└─────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────┐
│                     Controller Layer                         │
├─────────────────────────────────────────────────────────────┤
│  Academic_control   │  Teacher_completion │  Portfolio      │
│  - Metrics          │  - Assignments      │  - Assessment   │
│  - Workflow Actions │  - Validation       │  - Computation  │
└─────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────┐
│                     Middleware Layer                         │
├─────────────────────────────────────────────────────────────┤
│  Result_approval_middleware                                  │
│  - Status Guards    │  - Permission Checks │  - Validation  │
└─────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────┐
│                     Service Layer                            │
├─────────────────────────────────────────────────────────────┤
│  Result_approval_service  │  Portfolio_computation_service  │
│  - Workflow Management    │  - Score Calculation            │
│  - Audit Logging          │  - SBA Integration              │
└─────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────┐
│                     Data Access Layer                        │
├─────────────────────────────────────────────────────────────┤
│  Database Models & Queries                                   │
│  - CRUD Operations  │  - Aggregations     │  - Joins        │
└─────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────┐
│                     Database Layer                           │
├─────────────────────────────────────────────────────────────┤
│  MySQL Database                                              │
│  - result_approval_status    │  - portfolio_headers         │
│  - result_approval_audit     │  - portfolio_aggregates      │
│  - sba_components            │  - class_subject             │
└─────────────────────────────────────────────────────────────┘
```

## 🔐 Security Architecture

### Authentication Flow
```
User Login → Session Created → Role Identified
                                      ↓
                    ┌─────────────────┴─────────────────┐
                    ↓                                   ↓
              Admin (Level 1/2/3)                  Teacher
                    ↓                                   ↓
         Full/Partial Access                  Subject-Specific Access
```

### Authorization Matrix

| Resource | Admin L1 | Admin L2 | Admin L3 | Teacher (Assigned) | Teacher (Not Assigned) |
|----------|----------|----------|----------|-------------------|----------------------|
| View Control Dashboard | ✅ | ✅ | ✅ | ❌ | ❌ |
| View Teacher Dashboard | ✅ | ✅ | ✅ | ✅ | ✅ |
| Edit Portfolio (Draft) | ✅ | ✅ | ✅ | ✅ | ❌ |
| Edit SBA (Draft) | ✅ | ✅ | ✅ | ✅ | ❌ |
| Submit Results | ✅ | ✅ | ❌ | ❌ | ❌ |
| Approve Results | ✅ | ✅ | ❌ | ❌ | ❌ |
| Lock Results | ✅ | ✅ | ❌ | ❌ | ❌ |
| Unlock Results | ✅ | ✅ | ❌ | ❌ | ❌ |
| View Audit Trail | ✅ | ✅ | ✅ | ❌ | ❌ |

### Data Protection
- **Encryption**: Session data encrypted
- **Sanitization**: All inputs sanitized
- **Parameterized Queries**: SQL injection prevention
- **XSS Protection**: Output escaping
- **CSRF Tokens**: Form protection
- **Audit Trail**: All actions logged

## 📊 Data Flow Diagrams

### Portfolio Assessment Flow
```
Teacher Enters Scores
        ↓
Status Check (Draft?)
        ↓
Subject Assignment Check
        ↓
Save to portfolio_scores
        ↓
Compute Aggregates
        ↓
Update portfolio_aggregates
        ↓
Auto-sync to SBA (if enabled)
        ↓
Update sba_components.class_test
        ↓
Log Audit Trail
```

### Approval Workflow
```
Teacher Completes Assessments (100%)
        ↓
Admin Reviews Completion Metrics
        ↓
Admin Submits Results
        ↓
Status: draft → submitted
        ↓
Log Audit (submit action)
        ↓
Senior Admin Reviews
        ↓
Admin Approves Results
        ↓
Status: submitted → approved
        ↓
Log Audit (approve action)
        ↓
Admin Locks Results
        ↓
Status: approved → locked
        ↓
Log Audit (lock action)
        ↓
Results Immutable (unless unlocked)
```

### Unlock Workflow
```
Admin L1/L2 Requests Unlock
        ↓
Reason Required (mandatory)
        ↓
Verify Permission (level 1 or 2)
        ↓
Status: locked → draft
        ↓
Log Audit (unlock action + reason)
        ↓
Teachers Can Edit Again
```

## 🗄️ Database Schema

### Core Tables

#### result_approval_status
```sql
Primary Key: id
Unique Key: (class_id, academic_year, term)
Indexes: 
  - idx_status (status)
  - idx_class_year_term (class_id, academic_year, term)
Foreign Keys: None
```

#### result_approval_audit
```sql
Primary Key: id
Foreign Keys: 
  - approval_status_id → result_approval_status(id)
Indexes:
  - idx_approval_status (approval_status_id)
  - idx_action (action)
  - idx_performed_by (performed_by)
```

#### portfolio_headers
```sql
Primary Key: header_id
Foreign Keys:
  - class_id → class(class_id)
  - subject_id → subject(subject_id)
Indexes:
  - idx_class_subject (class_id, subject_id, year, term)
  - idx_teacher (teacher_id)
  - idx_week (week_number, term, year)
  - idx_portfolio_class_year_term (class_id, year, term)
```

#### portfolio_aggregates
```sql
Primary Key: aggregate_id
Unique Key: (student_id, subject_id, year, term, semester)
Foreign Keys:
  - student_id → student(student_id)
  - subject_id → subject(subject_id)
Indexes:
  - idx_student_term (student_id, year, term)
  - idx_subject (subject_id)
  - idx_aggregate_class_year_term (class_id, year, term)
```

#### sba_components
```sql
Primary Key: component_id
Unique Key: (student_id, subject_id, year, term, semester)
Foreign Keys:
  - student_id → student(student_id)
  - subject_id → subject(subject_id)
Indexes:
  - idx_student_sba (student_id, year, term)
  - idx_sba_class_year_term (class_id, year, term)
```

### Relationships
```
class (1) ──────< (N) class_subject (N) ──────> (1) subject
                        │
                        │ (N)
                        ↓
                    teacher (1)

student (1) ──────< (N) portfolio_aggregates (N) ──────> (1) subject
student (1) ──────< (N) sba_components (N) ──────> (1) subject

result_approval_status (1) ──────< (N) result_approval_audit
```

## ⚡ Performance Optimization

### Query Optimization
```sql
-- Optimized completion query
SELECT 
  COUNT(DISTINCT pa.student_id) * 100.0 / 
  (SELECT COUNT(*) FROM student WHERE class_id = ?) as completion_pct
FROM portfolio_aggregates pa
WHERE pa.class_id = ? 
  AND pa.year = ? 
  AND pa.term = ?
  AND pa.term_average IS NOT NULL;
```

### Caching Strategy
- **Status Cache**: 5 minutes TTL
- **Completion Metrics**: 10 minutes TTL
- **Teacher Assignments**: Session lifetime
- **Audit Trail**: No cache (real-time)

### Index Strategy
- All foreign keys indexed
- Composite indexes on (class_id, year, term)
- Covering indexes for common queries
- Regular ANALYZE TABLE maintenance

## 🔄 Integration Points

### Existing Systems
1. **Student Management**
   - Uses: student table
   - Sync: Real-time

2. **Class Management**
   - Uses: class, class_subject tables
   - Sync: Real-time

3. **Teacher Management**
   - Uses: teacher, class_subject tables
   - Sync: Real-time

4. **Subject Management**
   - Uses: subject table
   - Sync: Real-time

### External Systems (Future)
- GES Reporting API
- SMS Gateway (parent notifications)
- Email Service (admin alerts)
- Mobile App API

## 📱 API Endpoints

### Admin Endpoints
```
GET  /academic_control
POST /academic_control/get_class_metrics
POST /academic_control/submit_results
POST /academic_control/approve_results
POST /academic_control/lock_results
POST /academic_control/unlock_results
POST /academic_control/get_audit_trail
```

### Teacher Endpoints
```
GET  /teacher_completion
POST /teacher_completion/get_my_assignments
```

### Response Format
```json
{
  "status": "success|error",
  "message": "Human readable message",
  "data": {
    // Response data
  }
}
```

## 🛡️ Error Handling

### Error Levels
1. **User Errors**: Validation failures, permission denied
2. **System Errors**: Database errors, service failures
3. **Critical Errors**: Data corruption, security breaches

### Error Response
```json
{
  "status": "error",
  "message": "User-friendly error message",
  "code": "ERROR_CODE",
  "details": "Technical details (dev mode only)"
}
```

### Logging Strategy
- **User Actions**: Audit trail table
- **System Errors**: Application log file
- **Security Events**: Security log file
- **Performance**: Slow query log

## 📈 Scalability Considerations

### Current Capacity
- **Classes**: Unlimited
- **Students per class**: 100+
- **Concurrent users**: 50+
- **Database size**: 10GB+

### Scaling Strategy
1. **Horizontal**: Add read replicas
2. **Vertical**: Increase server resources
3. **Caching**: Redis/Memcached
4. **CDN**: Static assets
5. **Load Balancer**: Multiple app servers

## 🔍 Monitoring & Observability

### Key Metrics
- Request rate (req/sec)
- Response time (ms)
- Error rate (%)
- Database connections
- Cache hit rate

### Health Checks
```php
// System health endpoint
GET /health
Response: {
  "status": "healthy",
  "database": "connected",
  "cache": "available",
  "disk_space": "85% free"
}
```

## 📚 Technology Stack

### Backend
- **Framework**: CodeIgniter 3
- **Language**: PHP 7.4+
- **Database**: MySQL 5.7+
- **Session**: Database-backed

### Frontend
- **CSS**: Tailwind CSS 3.x
- **Components**: Flowbite
- **JavaScript**: jQuery 3.x
- **Charts**: Chart.js (future)

### Infrastructure
- **Web Server**: Apache/Nginx
- **PHP**: mod_php/PHP-FPM
- **Database**: MySQL/MariaDB
- **Cache**: File-based (upgradable to Redis)

---

**Architecture Version**: 1.0.0  
**Last Updated**: <?php echo date('Y-m-d H:i:s'); ?>  
**Status**: Production Ready ✅  
**Compliance**: GES Standards
