<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Sync Metrics Model
 * 
 * Manages sync performance metrics for monitoring and analysis.
 * Tracks sync duration, record counts, conflicts, and other KPIs.
 * 
 * @package    School Manager
 * @subpackage Models
 * @category   Sync
 * @author     School Manager Team
 * @version    1.0.0
 * 
 * Requirements: Requirement 20 - Sync Monitoring and Metrics
 */
class Sync_metrics_model extends CI_Model {
    
    /**
     * Table name
     * @var string
     */
    protected $table = 'sync_metrics';
    
    /**
     * Primary key
     * @var string
     */
    protected $primary_key = 'id';
    
    /**
     * Metric name constants
     */
    const METRIC_SYNC_DURATION = 'sync_duration';
    const METRIC_SYNC_DURATION_AVG = 'sync_duration_avg';
    const METRIC_RECORDS_SYNCED = 'records_synced';
    const METRIC_RECORDS_FAILED = 'records_failed';
    const METRIC_CONFLICTS_DETECTED = 'conflicts_detected';
    const METRIC_CONFLICTS_RESOLVED = 'conflicts_resolved';
    const METRIC_PENDING_COUNT = 'pending_count';
    const METRIC_SYNC_HEALTH_SCORE = 'sync_health_score';
    
    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();
    }
    
    /**
     * Record a metric
     * 
     * @param string $name Metric name
     * @param float $value Metric value
     * @param int|null $location_id Optional location ID
     * @param string|null $table_name Optional table name
     * @param array|null $metadata Optional metadata
     * @return int|bool Insert ID or false on failure
     */
    public function record($name, $value, $location_id = null, $table_name = null, $metadata = null) {
        $data = [
            'metric_name' => $name,
            'metric_value' => $value,
            'location_id' => $location_id,
            'table_name' => $table_name,
            'recorded_at' => date('Y-m-d H:i:s')
        ];
        
        if ($metadata !== null) {
            $data['metadata'] = json_encode($metadata);
        }
        
        return $this->db->insert($this->table, $data) ? $this->db->insert_id() : false;
    }
    
    /**
     * Record sync duration
     * 
     * @param float $duration Duration in seconds
     * @param int|null $location_id Location ID
     * @param string|null $table_name Table name
     */
    public function record_sync_duration($duration, $location_id = null, $table_name = null) {
        $this->record(self::METRIC_SYNC_DURATION, $duration, $location_id, $table_name);
        
        // Update average
        $this->update_average(self::METRIC_SYNC_DURATION_AVG, $duration);
    }
    
    /**
     * Record records synced count
     * 
     * @param int $count Number of records
     * @param int|null $location_id Location ID
     * @param string|null $table_name Table name
     */
    public function record_synced($count, $location_id = null, $table_name = null) {
        $this->record(self::METRIC_RECORDS_SYNCED, $count, $location_id, $table_name);
        $this->increment_total(self::METRIC_RECORDS_SYNCED, $count);
    }
    
    /**
     * Record records failed count
     * 
     * @param int $count Number of records
     * @param int|null $location_id Location ID
     * @param string|null $table_name Table name
     */
    public function record_failed($count, $location_id = null, $table_name = null) {
        $this->record(self::METRIC_RECORDS_FAILED, $count, $location_id, $table_name);
        $this->increment_total(self::METRIC_RECORDS_FAILED, $count);
    }
    
    /**
     * Record conflicts detected
     * 
     * @param int $count Number of conflicts
     * @param int|null $location_id Location ID
     * @param string|null $table_name Table name
     */
    public function record_conflicts_detected($count, $location_id = null, $table_name = null) {
        $this->record(self::METRIC_CONFLICTS_DETECTED, $count, $location_id, $table_name);
        $this->increment_total(self::METRIC_CONFLICTS_DETECTED, $count);
    }
    
    /**
     * Record conflicts resolved
     * 
     * @param int $count Number of conflicts
     * @param int|null $location_id Location ID
     */
    public function record_conflicts_resolved($count, $location_id = null) {
        $this->record(self::METRIC_CONFLICTS_RESOLVED, $count, $location_id);
        $this->increment_total(self::METRIC_CONFLICTS_RESOLVED, $count);
    }
    
    /**
     * Update average metric
     * 
     * @param string $name Metric name
     * @param float $new_value New value to incorporate
     */
    private function update_average($name, $new_value) {
        // Get current average
        $current = $this->db->where('metric_name', $name)
                           ->order_by('recorded_at', 'DESC')
                           ->get($this->table, 1)
                           ->row();
        
        if ($current) {
            // Calculate new average (simple moving average with weight 0.1 for new value)
            $new_avg = ($current->metric_value * 0.9) + ($new_value * 0.1);
            $this->record($name, $new_avg);
        } else {
            $this->record($name, $new_value);
        }
    }
    
    /**
     * Increment total counter
     * 
     * @param string $name Metric name
     * @param int $increment Amount to add
     */
    private function increment_total($name, $increment) {
        // Get current total
        $current = $this->db->where('metric_name', $name . '_total')
                           ->order_by('recorded_at', 'DESC')
                           ->get($this->table, 1)
                           ->row();
        
        $new_total = $current ? ($current->metric_value + $increment) : $increment;
        $this->record($name . '_total', $new_total);
    }
    
    /**
     * Get metrics by name
     * 
     * @param string $name Metric name
     * @param int $limit Limit
     * @return array Array of metric records
     */
    public function get_metrics($name, $limit = 100) {
        return $this->db->where('metric_name', $name)
                        ->order_by('recorded_at', 'DESC')
                        ->get($this->table, $limit)
                        ->result_array();
    }
    
    /**
     * Get latest metric value
     * 
     * @param string $name Metric name
     * @return float|null Latest value or null
     */
    public function get_latest($name) {
        $result = $this->db->where('metric_name', $name)
                          ->order_by('recorded_at', 'DESC')
                          ->get($this->table, 1)
                          ->row();
        
        return $result ? (float)$result->metric_value : null;
    }
    
    /**
     * Get metrics for a time range
     * 
     * @param string $name Metric name
     * @param string $date_from Start date
     * @param string $date_to End date
     * @return array Array of metric records
     */
    public function get_metrics_range($name, $date_from, $date_to) {
        return $this->db->where('metric_name', $name)
                        ->where('recorded_at >=', $date_from)
                        ->where('recorded_at <=', $date_to)
                        ->order_by('recorded_at', 'ASC')
                        ->get($this->table)
                        ->result_array();
    }
    
    /**
     * Get metrics by location
     * 
     * @param int $location_id Location ID
     * @param string|null $name Optional metric name filter
     * @param int $limit Limit
     * @return array Array of metric records
     */
    public function get_by_location($location_id, $name = null, $limit = 100) {
        $this->db->where('location_id', $location_id);
        
        if ($name) {
            $this->db->where('metric_name', $name);
        }
        
        return $this->db->order_by('recorded_at', 'DESC')
                        ->get($this->table, $limit)
                        ->result_array();
    }
    
    /**
     * Get metrics by table
     * 
     * @param string $table_name Table name
     * @param string|null $name Optional metric name filter
     * @param int $limit Limit
     * @return array Array of metric records
     */
    public function get_by_table($table_name, $name = null, $limit = 100) {
        $this->db->where('table_name', $table_name);
        
        if ($name) {
            $this->db->where('metric_name', $name);
        }
        
        return $this->db->order_by('recorded_at', 'DESC')
                        ->get($this->table, $limit)
                        ->result_array();
    }
    
    /**
     * Calculate sync health score
     * 
     * @param int|null $location_id Optional location ID
     * @return float Health score (0-100)
     */
    public function calculate_health_score($location_id = null) {
        // Get recent metrics
        $synced = $this->get_latest(self::METRIC_RECORDS_SYNCED) ?? 0;
        $failed = $this->get_latest(self::METRIC_RECORDS_FAILED) ?? 0;
        $conflicts = $this->get_latest(self::METRIC_CONFLICTS_DETECTED) ?? 0;
        $duration = $this->get_latest(self::METRIC_SYNC_DURATION_AVG) ?? 0;
        
        // Calculate components
        $success_rate = ($synced + $failed) > 0 ? ($synced / ($synced + $failed)) * 100 : 100;
        $conflict_rate = $synced > 0 ? (1 - ($conflicts / $synced)) * 100 : 100;
        $duration_score = $duration < 60 ? 100 : max(0, 100 - (($duration - 60) / 60 * 10));
        
        // Weighted average
        $health_score = ($success_rate * 0.5) + ($conflict_rate * 0.3) + ($duration_score * 0.2);
        
        // Record the score
        $this->record(self::METRIC_SYNC_HEALTH_SCORE, round($health_score, 2), $location_id);
        
        return round($health_score, 2);
    }
    
    /**
     * Get dashboard metrics summary
     * 
     * @return array Summary of key metrics
     */
    public function get_dashboard_summary() {
        return [
            'sync_duration_avg' => $this->get_latest(self::METRIC_SYNC_DURATION_AVG),
            'records_synced_total' => $this->get_latest('records_synced_total'),
            'records_failed_total' => $this->get_latest('records_failed_total'),
            'conflicts_detected_total' => $this->get_latest('conflicts_detected_total'),
            'conflicts_resolved_total' => $this->get_latest('conflicts_resolved_total'),
            'health_score' => $this->get_latest(self::METRIC_SYNC_HEALTH_SCORE)
        ];
    }
    
    /**
     * Clean up old metrics (retention policy)
     * 
     * @param int $days Days to keep (default 30)
     * @return int Number of deleted records
     */
    public function cleanup_old_metrics($days = 30) {
        $cutoff = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        
        $this->db->where('recorded_at <', $cutoff);
        $this->db->delete($this->table);
        
        return $this->db->affected_rows();
    }
}
