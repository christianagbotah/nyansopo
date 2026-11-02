<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Sync Configuration File
 * 
 * Configuration settings for the multi-location bidirectional sync system.
 * These settings can be overridden in the database sync_settings table.
 * 
 * @package    School Manager
 * @subpackage Config
 * @category   Sync
 */

// ============================================================================
// Feature Flags
// ============================================================================

// Enable/disable the entire sync system (legacy setting for backward compatibility)
$config['sync_enabled'] = TRUE;

// Enable/disable the entire bidirectional sync system
$config['bidirectional_sync_enabled'] = TRUE;

// Enable/disable automatic sync column injection at database driver level
// When enabled, UPDATE operations automatically inject sync tracking columns
$config['auto_inject_sync_columns'] = TRUE;

// Enable/disable real-time sync
$config['realtime_sync_enabled'] = FALSE;

// Enable/disable offline mode
$config['offline_mode_enabled'] = TRUE;

// ============================================================================
// Sync Timing
// ============================================================================

// Default sync interval in minutes (can be overridden in database)
$config['sync_interval_minutes'] = 5;

// Maximum time for a sync operation in seconds
$config['sync_timeout_seconds'] = 300;

// Legacy timeout setting (for backward compatibility)
$config['sync_timeout'] = 30;

// Maximum retry attempts for failed syncs
$config['max_retry_attempts'] = 3;

// Legacy retry attempts setting (for backward compatibility)
$config['sync_retry_attempts'] = 3;

// Delay between retry attempts in seconds
$config['retry_delay_seconds'] = 30;

// ============================================================================
// Batch Processing
// ============================================================================

// Default batch size for sync operations
$config['default_batch_size'] = 100;

// Legacy batch size setting (for backward compatibility)
$config['sync_batch_size'] = 100;

// Maximum batch size allowed
$config['max_batch_size'] = 1000;

// ============================================================================
// Conflict Resolution
// ============================================================================

// Default conflict resolution strategy
// Options: TIMESTAMP_WINS, REMOTE_WINS, LOCAL_WINS, VERSION_WINS, MANUAL_REVIEW
// Legacy options: last_write_wins, server_wins, client_wins (mapped to new options)
$config['default_conflict_strategy'] = 'TIMESTAMP_WINS';

// Legacy conflict resolution setting (for backward compatibility)
// Options: last_write_wins, server_wins, client_wins
$config['conflict_resolution'] = 'last_write_wins';

// Automatically resolve conflicts where possible
$config['auto_resolve_conflicts'] = TRUE;

// Maximum age of conflicts before alerting (in hours)
$config['conflict_alert_age_hours'] = 24;

// ============================================================================
// Audit and Logging
// ============================================================================

// Enable audit logging
$config['audit_logging_enabled'] = TRUE;

// Retention period for audit logs in days
$config['audit_log_retention_days'] = 90;

// Log sync operations to file
$config['log_to_file'] = TRUE;

// Log file path (relative to application root)
$config['log_file_path'] = 'logs/sync-%Y-%m-%d.log';

// ============================================================================
// Notifications
// ============================================================================

// Enable email notifications
$config['email_notifications_enabled'] = TRUE;

// Email for conflict notifications (override in database)
$config['conflict_notification_email'] = '';

// Email for offline location alerts
$config['offline_alert_email'] = '';

// Minimum offline duration before alerting (in minutes)
$config['offline_alert_minutes'] = 60;

// Critical offline duration for urgent alert (in minutes)
$config['offline_critical_minutes'] = 1440; // 24 hours

// ============================================================================
// Device and Sync Column Settings
// ============================================================================

// Device ID for this local server instance (can be overridden in database settings table)
// Used to identify which device made changes for sync tracking
$config['device_id'] = 'local-server-001';

// Sync columns to inject during UPDATE operations
// All five columns are required for proper sync tracking
$config['sync_columns_to_inject'] = [
    'sync_status',
    'last_modified_at',
    'device_id',
    'last_modified_by',
    'version'
];

// ============================================================================
// Location Settings
// ============================================================================

// Auto-register new locations on first sync
$config['auto_register_locations'] = FALSE;

// Maximum number of locations allowed
$config['max_locations'] = 100;

// Default timezone for new locations
$config['default_timezone'] = 'UTC';

// ============================================================================
// Real-time Sync
// ============================================================================

// Tables that should use real-time sync by default
$config['realtime_tables'] = [
    'payment',
    'invoice',
    'daily_fee_transactions'
];

// Maximum queue size for real-time sync
$config['realtime_queue_size'] = 1000;

// Real-time sync timeout in seconds
$config['realtime_timeout'] = 10;

// ============================================================================
// Performance
// ============================================================================

// Enable query caching during sync
$config['enable_query_cache'] = TRUE;

// Memory limit for sync operations (in MB)
$config['sync_memory_limit'] = 512;

// Time limit for sync operations (in seconds)
$config['sync_time_limit'] = 600;

// ============================================================================
// Security
// ============================================================================

// Require HTTPS for remote sync
$config['require_https'] = TRUE;

// API key for authentication (set in database or environment)
$config['sync_api_key'] = getenv('SYNC_API_KEY') ?: '';

// Allowed IP addresses for sync API (empty = all allowed)
$config['allowed_ips'] = [];

// ============================================================================
// Development/Debug
// ============================================================================

// Enable debug mode
$config['sync_debug_mode'] = FALSE;

// Log level: 0=off, 1=error, 2=warning, 3=info, 4=debug
$config['sync_log_level'] = 2;

// Simulate sync (for testing)
$config['simulate_sync'] = FALSE;

// ============================================================================
// Deletion Tracking
// ============================================================================

// Enable/disable deletion tracking
$config['deletion_tracking_enabled'] = TRUE;

// Tables to exclude from deletion tracking
// Session tables - excluded because sessions are transient and high-volume
// Sync tables - excluded to prevent infinite recursion (deletion tracking creates records here)
// NOTE: payment, journal_entries, journal_entry_lines previously excluded as workarounds
// for WHERE clause parsing bug - now removed since bug is fixed (Task 6)
$config['deletion_tracking_excluded_tables'] = [
    'sessions',
    'ci_sessions',
    'sync_audit_log',
    'sync_conflicts',
    'sync_deletions',
    'sync_metadata'
];

// Retention period for synced deletion records (days)
$config['deletion_retention_days'] = 30;

// Maximum retry attempts for failed deletion syncs
$config['deletion_max_retries'] = 3;

// Batch size for deletion sync operations
$config['deletion_batch_size'] = 100;

// Warn if batch DELETE affects more than this many records
$config['deletion_batch_warning_threshold'] = 1000;

// ============================================================================
// End of Sync Configuration
// ============================================================================
