<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Sync Conflict Model
 * 
 * Manages sync conflict records for the multi-location bidirectional sync system.
 * Stores both versions of conflicting records for manual review and resolution.
 * 
 * @package    School Manager
 * @subpackage Models
 * @category   Sync
 * @author     School Manager Team
 * @version    1.0.0
 * 
 * Requirements: Requirement 3, 4, 5 - Conflict Resolution
 */
class Sync_conflict_model extends CI_Model {
    
    /**
     * Table name
     * @var string
     */
    protected $table = 'sync_conflicts';
    
    /**
     * Primary key
     * @var string
     */
    protected $primary_key = 'id';
    
    /**
     * Conflict status constants
     */
    const STATUS_PENDING = 'pending';
    const STATUS_RESOLVED = 'resolved';
    const STATUS_IGNORED = 'ignored';
    
    /**
     * Resolution type constants
     */
    const RESOLUTION_LOCAL_WINS = 'local_wins';
    const RESOLUTION_REMOTE_WINS = 'remote_wins';
    const RESOLUTION_MERGED = 'merged';
    const RESOLUTION_IGNORED = 'ignored';
    
    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();
    }
    
    /**
     * Create a new conflict record
     * 
     * @param array $data Conflict data
     * @return int|bool Insert ID or false on failure
     */
    public function create_conflict($data) {
        $conflict = [
            'table_name' => $data['table_name'],
            'record_id' => $data['record_id'],
            'local_device_id' => $data['local_device_id'],
            'remote_device_id' => $data['remote_device_id'],
            'local_version' => $data['local_version'],
            'remote_version' => $data['remote_version'],
            'local_data' => json_encode($data['local_data']),
            'remote_data' => json_encode($data['remote_data']),
            'local_modified_at' => $data['local_modified_at'],
            'remote_modified_at' => $data['remote_modified_at'],
            'conflict_strategy' => $data['conflict_strategy'],
            'status' => self::STATUS_PENDING,
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        return $this->insert($conflict);
    }
    
    /**
     * Insert conflict record
     * 
     * @param array $data Conflict data
     * @return int|bool Insert ID or false on failure
     */
    public function insert($data) {
        $result = $this->db->insert($this->table, $data);
        
        if ($result) {
            return $this->db->insert_id();
        }
        
        return false;
    }
    
    /**
     * Get pending conflicts
     * 
     * @param array $filters Optional filters (table_name, status)
     * @param int $limit Limit
     * @param int $offset Offset
     * @return array Array of conflict records
     */
    public function get_pending_conflicts($filters = [], $limit = 100, $offset = 0) {
        $this->db->where('status', self::STATUS_PENDING);
        
        if (!empty($filters['table_name'])) {
            $this->db->where('table_name', $filters['table_name']);
        }
        
        $this->db->order_by('created_at', 'DESC');
        
        return $this->db->get($this->table, $limit, $offset)->result_array();
    }
    
    /**
     * Get conflict by ID
     * 
     * @param int $id Conflict ID
     * @return object|null Conflict object or null
     */
    public function get_conflict($id) {
        $conflict = $this->db->where($this->primary_key, $id)
                             ->get($this->table)
                             ->row();
        
        if ($conflict) {
            // Decode JSON fields
            $conflict->local_data = json_decode($conflict->local_data, true);
            $conflict->remote_data = json_decode($conflict->remote_data, true);
            if ($conflict->merged_data) {
                $conflict->merged_data = json_decode($conflict->merged_data, true);
            }
        }
        
        return $conflict;
    }
    
    /**
     * Get conflicts for a specific table and record
     * 
     * @param string $table_name Table name
     * @param int $record_id Record ID
     * @return array Array of conflict records
     */
    public function get_conflicts_by_record($table_name, $record_id) {
        return $this->db->where('table_name', $table_name)
                        ->where('record_id', $record_id)
                        ->order_by('created_at', 'DESC')
                        ->get($this->table)
                        ->result_array();
    }
    
    /**
     * Resolve a conflict
     * 
     * @param int $id Conflict ID
     * @param string $resolution Resolution type (local_wins, remote_wins, merged, ignored)
     * @param int $resolved_by Admin ID who resolved the conflict
     * @param array $merged_data Optional merged data if resolution is 'merged'
     * @return bool Success status
     */
    public function resolve_conflict($id, $resolution, $resolved_by, $merged_data = null) {
        $data = [
            'status' => self::STATUS_RESOLVED,
            'resolution' => $resolution,
            'resolved_at' => date('Y-m-d H:i:s'),
            'resolved_by' => $resolved_by
        ];
        
        if ($resolution === self::RESOLUTION_MERGED && !empty($merged_data)) {
            $data['merged_data'] = json_encode($merged_data);
        }
        
        return $this->db->where($this->primary_key, $id)
                        ->update($this->table, $data);
    }
    
    /**
     * Ignore a conflict
     * 
     * @param int $id Conflict ID
     * @param int $resolved_by Admin ID
     * @return bool Success status
     */
    public function ignore_conflict($id, $resolved_by) {
        return $this->db->where($this->primary_key, $id)
                        ->update($this->table, [
                            'status' => self::STATUS_IGNORED,
                            'resolution' => self::RESOLUTION_IGNORED,
                            'resolved_at' => date('Y-m-d H:i:s'),
                            'resolved_by' => $resolved_by
                        ]);
    }
    
    /**
     * Bulk resolve conflicts
     * 
     * @param array $ids Array of conflict IDs
     * @param string $resolution Resolution type
     * @param int $resolved_by Admin ID
     * @return int Number of conflicts resolved
     */
    public function bulk_resolve($ids, $resolution, $resolved_by) {
        $count = 0;
        
        foreach ($ids as $id) {
            if ($this->resolve_conflict($id, $resolution, $resolved_by)) {
                $count++;
            }
        }
        
        return $count;
    }
    
    /**
     * Get conflict counts by table
     * 
     * @return array Array with table names as keys and counts as values
     */
    public function get_conflict_counts_by_table() {
        $result = $this->db->select('table_name, COUNT(*) as count')
                          ->where('status', self::STATUS_PENDING)
                          ->group_by('table_name')
                          ->get($this->table)
                          ->result_array();
        
        $counts = [];
        foreach ($result as $row) {
            $counts[$row['table_name']] = $row['count'];
        }
        
        return $counts;
    }
    
    /**
     * Get total pending conflict count
     * 
     * @return int Count of pending conflicts
     */
    public function get_pending_count() {
        return $this->db->where('status', self::STATUS_PENDING)
                        ->count_all_results($this->table);
    }
    
    /**
     * Get resolved conflict count
     * 
     * @return int Count of resolved conflicts
     */
    public function get_resolved_count() {
        return $this->db->where('status', self::STATUS_RESOLVED)
                        ->count_all_results($this->table);
    }
    
    /**
     * Get conflict statistics
     * 
     * @return array Statistics array
     */
    public function get_conflict_stats() {
        return [
            'pending' => $this->get_pending_count(),
            'resolved' => $this->get_resolved_count(),
            'ignored' => $this->db->where('status', self::STATUS_IGNORED)
                                  ->count_all_results($this->table),
            'by_table' => $this->get_conflict_counts_by_table()
        ];
    }
    
    /**
     * Check if a conflict exists for a record
     * 
     * @param string $table_name Table name
     * @param int $record_id Record ID
     * @return bool True if pending conflict exists
     */
    public function has_pending_conflict($table_name, $record_id) {
        return $this->db->where('table_name', $table_name)
                        ->where('record_id', $record_id)
                        ->where('status', self::STATUS_PENDING)
                        ->count_all_results($this->table) > 0;
    }
    
    /**
     * Get the winning data based on resolution
     * 
     * @param object $conflict Conflict object
     * @return array The winning record data
     */
    public function get_winning_data($conflict) {
        switch ($conflict->resolution) {
            case self::RESOLUTION_LOCAL_WINS:
                return $conflict->local_data;
            
            case self::RESOLUTION_REMOTE_WINS:
                return $conflict->remote_data;
            
            case self::RESOLUTION_MERGED:
                return $conflict->merged_data;
            
            default:
                return null;
        }
    }
}
