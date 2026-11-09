<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Conflict Resolver Library
 * 
 * Applies configurable conflict resolution strategies when the same record
 * is modified at multiple locations before synchronization occurs.
 * 
 * @package    School Manager
 * @subpackage Libraries
 * @category   Sync
 * @author     School Manager Team
 * @version    1.0.0
 * 
 * Requirements: 3, 4
 */
class Conflict_resolver {
    
    /**
     * CodeIgniter instance
     * @var object
     */
    private $CI;
    
    /**
     * Device ID for this local server
     * @var string
     */
    private $device_id;
    
    // Conflict resolution strategies
    const STRATEGY_REMOTE_WINS = 'REMOTE_WINS';
    const STRATEGY_LOCAL_WINS = 'LOCAL_WINS';
    const STRATEGY_TIMESTAMP_WINS = 'TIMESTAMP_WINS';
    const STRATEGY_VERSION_WINS = 'VERSION_WINS';
    const STRATEGY_MANUAL_REVIEW = 'MANUAL_REVIEW';
    
    // Resolution outcomes
    const RESOLUTION_LOCAL_WINS = 'local_wins';
    const RESOLUTION_REMOTE_WINS = 'remote_wins';
    const RESOLUTION_MERGED = 'merged';
    const RESOLUTION_MANUAL = 'manual';
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->CI =& get_instance();
        $this->CI->load->database();
        
        // Get device ID from settings
        $setting = $this->CI->db->get_where('settings', ['type' => 'device_id'])->row();
        $this->device_id = $setting ? $setting->description : 'local-server-001';
    }
    
    /**
     * Resolve a conflict using the configured strategy
     * 
     * @param string $table Table name
     * @param array $local_record Local version of the record
     * @param array $remote_record Remote version of the record
     * @param string $strategy Conflict resolution strategy
     * @return array Resolution result with keys: status, resolution, winner_data, message
     */
    public function resolve($table, $local_record, $remote_record, $strategy) {
        // Log conflict detection
        log_message('info', "[Conflict Resolver] Conflict detected in table $table, strategy: $strategy");
        
        // Validate strategy
        if (!$this->is_valid_strategy($strategy)) {
            log_message('error', "[Conflict Resolver] Invalid strategy: $strategy, defaulting to TIMESTAMP_WINS");
            $strategy = self::STRATEGY_TIMESTAMP_WINS;
        }
        
        // Apply strategy
        switch ($strategy) {
            case self::STRATEGY_REMOTE_WINS:
                return $this->resolve_remote_wins($table, $local_record, $remote_record);
            
            case self::STRATEGY_LOCAL_WINS:
                return $this->resolve_local_wins($table, $local_record, $remote_record);
            
            case self::STRATEGY_TIMESTAMP_WINS:
                return $this->resolve_timestamp_wins($table, $local_record, $remote_record);
            
            case self::STRATEGY_VERSION_WINS:
                return $this->resolve_version_wins($table, $local_record, $remote_record);
            
            case self::STRATEGY_MANUAL_REVIEW:
                return $this->resolve_manual_review($table, $local_record, $remote_record);
            
            default:
                return $this->resolve_timestamp_wins($table, $local_record, $remote_record);
        }
    }
    
    /**
     * Check if strategy is valid
     * 
     * @param string $strategy Strategy name
     * @return bool
     */
    private function is_valid_strategy($strategy) {
        $valid_strategies = [
            self::STRATEGY_REMOTE_WINS,
            self::STRATEGY_LOCAL_WINS,
            self::STRATEGY_TIMESTAMP_WINS,
            self::STRATEGY_VERSION_WINS,
            self::STRATEGY_MANUAL_REVIEW
        ];
        
        return in_array($strategy, $valid_strategies);
    }
    
    /**
     * Remote version always wins
     * 
     * @param string $table Table name
     * @param array $local Local record
     * @param array $remote Remote record
     * @return array Resolution result
     */
    private function resolve_remote_wins($table, $local, $remote) {
        log_message('info', "[Conflict Resolver] Applying REMOTE_WINS for $table");
        
        // Log conflict to database
        $this->log_conflict($table, $local, $remote, self::STRATEGY_REMOTE_WINS, self::RESOLUTION_REMOTE_WINS);
        
        // Apply remote version to local
        $primary_key = $this->get_primary_key($table);
        if ($primary_key && isset($remote[$primary_key])) {
            // Prepare remote data for update
            $update_data = $remote;
            $update_data['sync_status'] = 'SYNCED';
            $update_data['last_modified_at'] = date('Y-m-d H:i:s');
            
            // Update local record
            $this->CI->db->where($primary_key, $remote[$primary_key])
                        ->update($table, $update_data);
        }
        
        return [
            'status' => 'resolved',
            'resolution' => self::RESOLUTION_REMOTE_WINS,
            'winner_data' => $remote,
            'message' => 'Remote version applied (REMOTE_WINS strategy)'
        ];
    }
    
    /**
     * Local version always wins
     * 
     * @param string $table Table name
     * @param array $local Local record
     * @param array $remote Remote record
     * @return array Resolution result
     */
    private function resolve_local_wins($table, $local, $remote) {
        log_message('info', "[Conflict Resolver] Applying LOCAL_WINS for $table");
        
        // Log conflict to database
        $this->log_conflict($table, $local, $remote, self::STRATEGY_LOCAL_WINS, self::RESOLUTION_LOCAL_WINS);
        
        // Keep local version - mark for push to remote
        $primary_key = $this->get_primary_key($table);
        if ($primary_key && isset($local[$primary_key])) {
            // Ensure local record stays PENDING so it will be pushed
            $this->CI->db->where($primary_key, $local[$primary_key])
                        ->update($table, [
                            'sync_status' => 'PENDING',
                            'last_modified_at' => date('Y-m-d H:i:s')
                        ]);
        }
        
        return [
            'status' => 'resolved',
            'resolution' => self::RESOLUTION_LOCAL_WINS,
            'winner_data' => $local,
            'message' => 'Local version kept (LOCAL_WINS strategy)'
        ];
    }
    
    /**
     * Most recent timestamp wins
     * 
     * @param string $table Table name
     * @param array $local Local record
     * @param array $remote Remote record
     * @return array Resolution result
     */
    private function resolve_timestamp_wins($table, $local, $remote) {
        log_message('info', "[Conflict Resolver] Applying TIMESTAMP_WINS for $table");
        
        // Get timestamps
        $local_time = isset($local['last_modified_at']) ? strtotime($local['last_modified_at']) : 0;
        $remote_time = isset($remote['last_modified_at']) ? strtotime($remote['last_modified_at']) : 0;
        
        // Compare timestamps
        if ($remote_time > $local_time) {
            log_message('info', "[Conflict Resolver] Remote is newer, applying remote version");
            return $this->resolve_remote_wins($table, $local, $remote);
        } else if ($local_time > $remote_time) {
            log_message('info', "[Conflict Resolver] Local is newer, keeping local version");
            return $this->resolve_local_wins($table, $local, $remote);
        } else {
            // Timestamps are equal - default to remote wins
            log_message('info', "[Conflict Resolver] Timestamps equal, defaulting to remote");
            return $this->resolve_remote_wins($table, $local, $remote);
        }
    }
    
    /**
     * Higher version number wins
     * 
     * @param string $table Table name
     * @param array $local Local record
     * @param array $remote Remote record
     * @return array Resolution result
     */
    private function resolve_version_wins($table, $local, $remote) {
        log_message('info', "[Conflict Resolver] Applying VERSION_WINS for $table");
        
        // Get version numbers
        $local_version = isset($local['version']) ? (int)$local['version'] : 0;
        $remote_version = isset($remote['version']) ? (int)$remote['version'] : 0;
        
        // Compare versions
        if ($remote_version > $local_version) {
            log_message('info', "[Conflict Resolver] Remote version higher ($remote_version > $local_version)");
            return $this->resolve_remote_wins($table, $local, $remote);
        } else if ($local_version > $remote_version) {
            log_message('info', "[Conflict Resolver] Local version higher ($local_version > $remote_version)");
            return $this->resolve_local_wins($table, $local, $remote);
        } else {
            // Versions are equal - fall back to timestamp
            log_message('info', "[Conflict Resolver] Versions equal, falling back to timestamp");
            return $this->resolve_timestamp_wins($table, $local, $remote);
        }
    }
    
    /**
     * Flag for manual human review
     * 
     * @param string $table Table name
     * @param array $local Local record
     * @param array $remote Remote record
     * @return array Resolution result
     */
    private function resolve_manual_review($table, $local, $remote) {
        log_message('info', "[Conflict Resolver] Flagging for MANUAL_REVIEW in $table");
        
        // Log conflict to database with pending status
        $conflict_id = $this->log_conflict($table, $local, $remote, self::STRATEGY_MANUAL_REVIEW, null, 'pending');
        
        // Mark local record as MANUAL_REVIEW
        $primary_key = $this->get_primary_key($table);
        if ($primary_key && isset($local[$primary_key])) {
            $this->CI->db->where($primary_key, $local[$primary_key])
                        ->update($table, [
                            'sync_status' => 'MANUAL_REVIEW',
                            'sync_error' => 'Conflict requires manual review (conflict_id: ' . $conflict_id . ')'
                        ]);
        }
        
        // Send notification to admins
        $this->send_conflict_notification($table, $local, $remote, $conflict_id);
        
        return [
            'status' => 'pending',
            'resolution' => self::RESOLUTION_MANUAL,
            'conflict_id' => $conflict_id,
            'message' => 'Conflict flagged for manual review'
        ];
    }
    
    /**
     * Log conflict to sync_conflicts table
     * 
     * @param string $table Table name
     * @param array $local Local record
     * @param array $remote Remote record
     * @param string $strategy Strategy used
     * @param string|null $resolution Resolution outcome
     * @param string $status Conflict status (pending or resolved)
     * @return int Conflict ID
     */
    private function log_conflict($table, $local, $remote, $strategy, $resolution = null, $status = 'resolved') {
        $primary_key = $this->get_primary_key($table);
        $record_id = isset($local[$primary_key]) ? $local[$primary_key] : (isset($remote[$primary_key]) ? $remote[$primary_key] : 0);
        
        $conflict_data = [
            'table_name' => $table,
            'record_id' => $record_id,
            'local_device_id' => $this->device_id,
            'remote_device_id' => isset($remote['device_id']) ? $remote['device_id'] : 'remote-server',
            'local_version' => isset($local['version']) ? $local['version'] : 0,
            'remote_version' => isset($remote['version']) ? $remote['version'] : 0,
            'local_data' => json_encode($local),
            'remote_data' => json_encode($remote),
            'local_modified_at' => isset($local['last_modified_at']) ? $local['last_modified_at'] : date('Y-m-d H:i:s'),
            'remote_modified_at' => isset($remote['last_modified_at']) ? $remote['last_modified_at'] : date('Y-m-d H:i:s'),
            'conflict_strategy' => $strategy,
            'status' => $status,
            'resolution' => $resolution,
            'resolved_at' => $status === 'resolved' ? date('Y-m-d H:i:s') : null,
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $this->CI->db->insert('sync_conflicts', $conflict_data);
        return $this->CI->db->insert_id();
    }
    
    /**
     * Send notification to admins about conflict
     * 
     * @param string $table Table name
     * @param array $local Local record
     * @param array $remote Remote record
     * @param int $conflict_id Conflict ID
     */
    private function send_conflict_notification($table, $local, $remote, $conflict_id) {
        try {
            // Get admin email from settings
            $setting = $this->CI->db->get_where('settings', ['type' => 'system_email'])->row();
            $admin_email = $setting ? $setting->description : null;
            
            if (!$admin_email) {
                log_message('info', "[Conflict Resolver] Admin email not configured, cannot send notification");
                return;
            }
            
            // Load email library
            $this->CI->load->library('email');
            
            $primary_key = $this->get_primary_key($table);
            $record_id = isset($local[$primary_key]) ? $local[$primary_key] : 'unknown';
            
            $subject = "Sync Conflict Requires Manual Review - $table";
            $message = "
                <h2>Sync Conflict Detected</h2>
                <p>A conflict has been detected that requires manual review.</p>
                
                <h3>Details:</h3>
                <ul>
                    <li><strong>Table:</strong> $table</li>
                    <li><strong>Record ID:</strong> $record_id</li>
                    <li><strong>Conflict ID:</strong> $conflict_id</li>
                    <li><strong>Local Device:</strong> {$this->device_id}</li>
                    <li><strong>Remote Device:</strong> " . (isset($remote['device_id']) ? $remote['device_id'] : 'remote-server') . "</li>
                </ul>
                
                <p>Please review this conflict in the sync dashboard and resolve it manually.</p>
                
                <p><a href='" . site_url('sync_conflicts/view/' . $conflict_id) . "'>View Conflict Details</a></p>
                
                <hr>
                <p><small>This is an automated message from the School Manager Sync System.</small></p>
            ";
            
            $this->CI->email->from($admin_email, 'School Manager Sync System');
            $this->CI->email->to($admin_email);
            $this->CI->email->subject($subject);
            $this->CI->email->message($message);
            
            if ($this->CI->email->send()) {
                log_message('info', "[Conflict Resolver] Conflict notification sent to $admin_email");
            } else {
                log_message('error', "[Conflict Resolver] Failed to send conflict notification");
            }
            
        } catch (Exception $e) {
            log_message('error', "[Conflict Resolver] Error sending notification: " . $e->getMessage());
        }
    }
    
    /**
     * Get primary key column name for a table
     * 
     * @param string $table Table name
     * @return string|null Primary key column name
     */
    private function get_primary_key($table) {
        try {
            $query = $this->CI->db->query("SHOW KEYS FROM `$table` WHERE Key_name = 'PRIMARY'");
            $result = $query->row_array();
            return $result ? $result['Column_name'] : null;
        } catch (Exception $e) {
            log_message('error', "[Conflict Resolver] Error getting primary key for $table: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get conflict strategy for a table from sync_metadata
     * 
     * @param string $table Table name
     * @return string Strategy name
     */
    public function get_table_strategy($table) {
        $metadata = $this->CI->db->get_where('sync_metadata', ['table_name' => $table])->row();
        
        if ($metadata && isset($metadata->conflict_strategy)) {
            return $metadata->conflict_strategy;
        }
        
        // Default strategy
        return self::STRATEGY_TIMESTAMP_WINS;
    }
    
    /**
     * Resolve a push conflict (during bidirectional sync push phase)
     * Similar to resolve() but for push operations
     * 
     * @param string $table Table name
     * @param array $local_record Local version of the record
     * @param array $remote_record Remote version of the record
     * @param string $strategy Conflict resolution strategy
     * @return array Resolution result with keys: status, resolution, winner_data, message
     */
    public function resolve_push_conflict($table, $local_record, $remote_record, $strategy) {
        // Log conflict detection
        log_message('info', "[Conflict Resolver] Push conflict detected in table $table, strategy: $strategy");
        
        // Validate strategy
        if (!$this->is_valid_strategy($strategy)) {
            log_message('error', "[Conflict Resolver] Invalid strategy: $strategy, defaulting to TIMESTAMP_WINS");
            $strategy = self::STRATEGY_TIMESTAMP_WINS;
        }
        
        // Apply strategy (same logic as pull, but we return the decision without applying it)
        switch ($strategy) {
            case self::STRATEGY_REMOTE_WINS:
                return $this->resolve_push_remote_wins($table, $local_record, $remote_record);
            
            case self::STRATEGY_LOCAL_WINS:
                return $this->resolve_push_local_wins($table, $local_record, $remote_record);
            
            case self::STRATEGY_TIMESTAMP_WINS:
                return $this->resolve_push_timestamp_wins($table, $local_record, $remote_record);
            
            case self::STRATEGY_VERSION_WINS:
                return $this->resolve_push_version_wins($table, $local_record, $remote_record);
            
            case self::STRATEGY_MANUAL_REVIEW:
                return $this->resolve_push_manual_review($table, $local_record, $remote_record);
            
            default:
                return $this->resolve_push_timestamp_wins($table, $local_record, $remote_record);
        }
    }
    
    /**
     * Push conflict: Remote version wins (don't push local)
     */
    private function resolve_push_remote_wins($table, $local, $remote) {
        log_message('info', "[Conflict Resolver] Push conflict: REMOTE_WINS for $table");
        
        // Log conflict to database
        $this->log_conflict($table, $local, $remote, self::STRATEGY_REMOTE_WINS, self::RESOLUTION_REMOTE_WINS);
        
        return [
            'status' => 'resolved',
            'resolution' => 'remote_wins',
            'winner_data' => $remote,
            'message' => 'Remote version kept (REMOTE_WINS strategy)'
        ];
    }
    
    /**
     * Push conflict: Local version wins (push local to remote)
     */
    private function resolve_push_local_wins($table, $local, $remote) {
        log_message('info', "[Conflict Resolver] Push conflict: LOCAL_WINS for $table");
        
        // Log conflict to database
        $this->log_conflict($table, $local, $remote, self::STRATEGY_LOCAL_WINS, self::RESOLUTION_LOCAL_WINS);
        
        return [
            'status' => 'resolved',
            'resolution' => 'local_wins',
            'winner_data' => $local,
            'message' => 'Local version will be pushed (LOCAL_WINS strategy)'
        ];
    }
    
    /**
     * Push conflict: Most recent timestamp wins
     */
    private function resolve_push_timestamp_wins($table, $local, $remote) {
        log_message('info', "[Conflict Resolver] Push conflict: TIMESTAMP_WINS for $table");
        
        // Get timestamps
        $local_time = isset($local['last_modified_at']) ? strtotime($local['last_modified_at']) : 0;
        $remote_time = isset($remote['last_modified_at']) ? strtotime($remote['last_modified_at']) : 0;
        
        // Compare timestamps
        if ($local_time > $remote_time) {
            log_message('info', "[Conflict Resolver] Local is newer, pushing local version");
            return $this->resolve_push_local_wins($table, $local, $remote);
        } else if ($remote_time > $local_time) {
            log_message('info', "[Conflict Resolver] Remote is newer, keeping remote version");
            return $this->resolve_push_remote_wins($table, $local, $remote);
        } else {
            // Timestamps are equal - default to local wins (since we're in push phase)
            log_message('info', "[Conflict Resolver] Timestamps equal, defaulting to local");
            return $this->resolve_push_local_wins($table, $local, $remote);
        }
    }
    
    /**
     * Push conflict: Higher version number wins
     */
    private function resolve_push_version_wins($table, $local, $remote) {
        log_message('info', "[Conflict Resolver] Push conflict: VERSION_WINS for $table");
        
        // Get version numbers
        $local_version = isset($local['version']) ? (int)$local['version'] : 0;
        $remote_version = isset($remote['version']) ? (int)$remote['version'] : 0;
        
        // Compare versions
        if ($local_version > $remote_version) {
            log_message('info', "[Conflict Resolver] Local version higher ($local_version > $remote_version)");
            return $this->resolve_push_local_wins($table, $local, $remote);
        } else if ($remote_version > $local_version) {
            log_message('info', "[Conflict Resolver] Remote version higher ($remote_version > $local_version)");
            return $this->resolve_push_remote_wins($table, $local, $remote);
        } else {
            // Versions are equal - fall back to timestamp
            log_message('info', "[Conflict Resolver] Versions equal, falling back to timestamp");
            return $this->resolve_push_timestamp_wins($table, $local, $remote);
        }
    }
    
    /**
     * Push conflict: Flag for manual review
     */
    private function resolve_push_manual_review($table, $local, $remote) {
        log_message('info', "[Conflict Resolver] Push conflict: MANUAL_REVIEW for $table");
        
        // Log conflict to database with pending status
        $conflict_id = $this->log_conflict($table, $local, $remote, self::STRATEGY_MANUAL_REVIEW, null, 'pending');
        
        // Mark local record as MANUAL_REVIEW
        $primary_key = $this->get_primary_key($table);
        if ($primary_key && isset($local[$primary_key])) {
            $this->CI->db->where($primary_key, $local[$primary_key])
                        ->update($table, [
                            'sync_status' => 'MANUAL_REVIEW',
                            'sync_error' => 'Push conflict requires manual review (conflict_id: ' . $conflict_id . ')'
                        ]);
        }
        
        // Send notification to admins
        $this->send_conflict_notification($table, $local, $remote, $conflict_id);
        
        return [
            'status' => 'pending',
            'resolution' => 'MANUAL_REVIEW',
            'conflict_id' => $conflict_id,
            'message' => 'Push conflict flagged for manual review'
        ];
    }
}
