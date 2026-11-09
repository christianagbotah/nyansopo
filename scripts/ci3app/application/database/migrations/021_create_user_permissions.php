<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Migration: Create User Permissions Table
 * 
 * This migration creates the user_permissions table for managing
 * granular user permissions across different modules and actions.
 * 
 * @package    School Manager
 * @subpackage Migrations
 * @category   Database
 * @author     System
 * @version    1.0
 */
class Migration_Create_user_permissions extends CI_Migration {

    public function up()
    {
        // Create user_permissions table
        $this->dbforge->add_field(array(
            'permission_id' => array(
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => TRUE,
                'auto_increment' => TRUE
            ),
            'user_id' => array(
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => TRUE,
                'comment' => 'ID of the user (teacher_id, admin_id, etc.)'
            ),
            'user_type' => array(
                'type' => 'VARCHAR',
                'constraint' => '50',
                'comment' => 'Type of user: teacher, admin, accountant, etc.'
            ),
            'module_name' => array(
                'type' => 'VARCHAR',
                'constraint' => '100',
                'comment' => 'Module name: head_teacher_remarks, teacher_remarks_templates, etc.'
            ),
            'permission_type' => array(
                'type' => 'VARCHAR',
                'constraint' => '50',
                'comment' => 'Permission type: view, add, edit, delete, manage'
            ),
            'status' => array(
                'type' => 'ENUM',
                'constraint' => array('active', 'revoked'),
                'default' => 'active',
                'comment' => 'Permission status'
            ),
            'granted_by' => array(
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => TRUE,
                'comment' => 'Admin ID who granted the permission'
            ),
            'granted_at' => array(
                'type' => 'DATETIME',
                'null' => FALSE,
                'comment' => 'When the permission was granted'
            ),
            'revoked_by' => array(
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => TRUE,
                'null' => TRUE,
                'comment' => 'Admin ID who revoked the permission'
            ),
            'revoked_at' => array(
                'type' => 'DATETIME',
                'null' => TRUE,
                'comment' => 'When the permission was revoked'
            ),
            'notes' => array(
                'type' => 'TEXT',
                'null' => TRUE,
                'comment' => 'Additional notes about the permission'
            ),
            'created_at' => array(
                'type' => 'TIMESTAMP',
                'null' => FALSE,
                'default' => 'CURRENT_TIMESTAMP'
            ),
            'updated_at' => array(
                'type' => 'TIMESTAMP',
                'null' => TRUE,
                'default' => NULL,
                'on_update' => 'CURRENT_TIMESTAMP'
            )
        ));
        
        $this->dbforge->add_key('permission_id', TRUE);
        $this->dbforge->add_key('user_id');
        $this->dbforge->add_key('user_type');
        $this->dbforge->add_key('module_name');
        $this->dbforge->add_key('status');
        
        // Create the table
        $this->dbforge->create_table('user_permissions', TRUE);
        
        // Add composite index for faster permission lookups
        $this->db->query('CREATE INDEX idx_user_module_permission 
                         ON user_permissions(user_id, user_type, module_name, permission_type, status)');
        
        // Create permission_modules table for managing available modules
        $this->dbforge->add_field(array(
            'module_id' => array(
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => TRUE,
                'auto_increment' => TRUE
            ),
            'module_name' => array(
                'type' => 'VARCHAR',
                'constraint' => '100',
                'unique' => TRUE,
                'comment' => 'Unique module identifier'
            ),
            'module_display_name' => array(
                'type' => 'VARCHAR',
                'constraint' => '150',
                'comment' => 'Human-readable module name'
            ),
            'module_description' => array(
                'type' => 'TEXT',
                'null' => TRUE,
                'comment' => 'Description of the module'
            ),
            'available_permissions' => array(
                'type' => 'VARCHAR',
                'constraint' => '255',
                'default' => 'view,add,edit,delete',
                'comment' => 'Comma-separated list of available permission types'
            ),
            'is_active' => array(
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 1,
                'comment' => 'Whether the module is active'
            ),
            'created_at' => array(
                'type' => 'TIMESTAMP',
                'null' => FALSE,
                'default' => 'CURRENT_TIMESTAMP'
            )
        ));
        
        $this->dbforge->add_key('module_id', TRUE);
        $this->dbforge->add_key('module_name');
        $this->dbforge->create_table('permission_modules', TRUE);
        
        // Insert default modules
        $default_modules = array(
            array(
                'module_name' => 'head_teacher_remarks',
                'module_display_name' => 'Head Teacher (Principal) Remarks',
                'module_description' => 'Manage head teacher/principal remarks and comments',
                'available_permissions' => 'view,add,edit,delete,manage',
                'is_active' => 1
            ),
            array(
                'module_name' => 'teacher_remarks_templates',
                'module_display_name' => 'Teacher Remarks Templates',
                'module_description' => 'Manage templates for teacher remarks',
                'available_permissions' => 'view,add,edit,delete,manage',
                'is_active' => 1
            ),
            array(
                'module_name' => 'student_management',
                'module_display_name' => 'Student Management',
                'module_description' => 'Manage student records and information',
                'available_permissions' => 'view,add,edit,delete',
                'is_active' => 1
            ),
            array(
                'module_name' => 'attendance',
                'module_display_name' => 'Attendance Management',
                'module_description' => 'Manage student and staff attendance',
                'available_permissions' => 'view,add,edit,delete,monitor',
                'is_active' => 1
            ),
            array(
                'module_name' => 'grade_management',
                'module_display_name' => 'Grade Management',
                'module_description' => 'Manage student grades and assessments',
                'available_permissions' => 'view,add,edit,delete,approve',
                'is_active' => 1
            )
        );
        
        $this->db->insert_batch('permission_modules', $default_modules);
    }

    public function down()
    {
        // Drop the tables in reverse order
        $this->dbforge->drop_table('user_permissions', TRUE);
        $this->dbforge->drop_table('permission_modules', TRUE);
    }
}
