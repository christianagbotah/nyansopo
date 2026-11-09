<?php
/**
 * Performance Monitor Hook
 * 
 * Measures real page load performance with detailed breakdowns
 * Install in config/hooks.php
 */

defined('BASEPATH') OR exit('No direct script access allowed');

class Performance_monitor {
    
    private $CI;
    private $checkpoints = [];
    private $start_time;
    private $start_memory;
    
    public function __construct() {
        $this->start_time = microtime(true);
        $this->start_memory = memory_get_usage();
        $this->checkpoint('Hook Start');
    }
    
    /**
     * Record a checkpoint
     */
    public function checkpoint($name) {
        $this->checkpoints[$name] = [
            'time' => microtime(true),
            'elapsed' => (microtime(true) - $this->start_time) * 1000,
            'memory' => memory_get_usage() / 1024 / 1024
        ];
    }
    
    /**
     * Pre-controller hook
     */
    public function pre_controller() {
        $this->checkpoint('Pre Controller');
    }
    
    /**
     * Post-controller constructor hook
     */
    public function post_controller_constructor() {
        $this->checkpoint('Post Controller Constructor');
    }
    
    /**
     * Post-controller hook
     */
    public function post_controller() {
        $this->checkpoint('Post Controller');
    }
    
    /**
     * Display output hook
     */
    public function display_override() {
        $this->checkpoint('Display Override');
        
        $this->CI =& get_instance();
        $output = $this->CI->output->get_output();
        
        // Only add profiler to HTML pages
        if (strpos($output, '</body>') !== false && !$this->is_ajax()) {
            $profiler = $this->generate_profiler();
            $output = str_replace('</body>', $profiler . '</body>', $output);
        }
        
        $this->CI->output->set_output($output);
        $this->CI->output->_display();
    }
    
    /**
     * Check if request is AJAX
     */
    private function is_ajax() {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    }
    
    /**
     * Generate profiler HTML
     */
    private function generate_profiler() {
        $total_time = (microtime(true) - $this->start_time) * 1000;
        $total_memory = memory_get_peak_usage(true) / 1024 / 1024;
        
        // Get database query count if available
        $query_count = 0;
        $query_time = 0;
        if (isset($this->CI->db)) {
            $query_count = count($this->CI->db->queries);
            // Approximate query time
            foreach ($this->CI->db->query_times as $time) {
                $query_time += $time;
            }
            $query_time *= 1000; // Convert to ms
        }
        
        $status_class = 'good';
        if ($total_time > 500) $status_class = 'critical';
        elseif ($total_time > 200) $status_class = 'warning';
        
        ob_start();
        ?>
        <div id="performance-profiler" style="position: fixed; bottom: 0; left: 0; right: 0; background: rgba(0,0,0,0.9); color: white; padding: 10px 20px; font-family: monospace; font-size: 12px; z-index: 99999; box-shadow: 0 -2px 10px rgba(0,0,0,0.3);">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div style="display: flex; gap: 20px; align-items: center;">
                    <span style="font-weight: bold; font-size: 14px;">⚡ Performance</span>
                    <span class="perf-metric perf-<?php echo $status_class; ?>">
                        Time: <?php echo number_format($total_time, 2); ?>ms
                    </span>
                    <span class="perf-metric">
                        Memory: <?php echo number_format($total_memory, 2); ?>MB
                    </span>
                    <span class="perf-metric">
                        Queries: <?php echo $query_count; ?> (<?php echo number_format($query_time, 2); ?>ms)
                    </span>
                    <span class="perf-metric">
                        Controller: <?php echo $this->CI->router->fetch_class() . '/' . $this->CI->router->fetch_method(); ?>
                    </span>
                </div>
                <button onclick="document.getElementById('perf-details').style.display = document.getElementById('perf-details').style.display === 'none' ? 'block' : 'none'" style="background: #4CAF50; color: white; border: none; padding: 5px 15px; border-radius: 3px; cursor: pointer;">Details</button>
            </div>
            
            <div id="perf-details" style="display: none; margin-top: 15px; padding-top: 15px; border-top: 1px solid rgba(255,255,255,0.2);">
                <table style="width: 100%; color: white;">
                    <thead>
                        <tr style="text-align: left; border-bottom: 1px solid rgba(255,255,255,0.2);">
                            <th style="padding: 5px;">Checkpoint</th>
                            <th style="padding: 5px;">Elapsed (ms)</th>
                            <th style="padding: 5px;">Delta (ms)</th>
                            <th style="padding: 5px;">Memory (MB)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $prev_time = 0;
                        foreach ($this->checkpoints as $name => $data) {
                            $delta = $data['elapsed'] - $prev_time;
                            $style = '';
                            if ($delta > 100) $style = 'color: #f44336;';
                            elseif ($delta > 50) $style = 'color: #ff9800;';
                            else $style = 'color: #4CAF50;';
                            
                            echo "<tr>";
                            echo "<td style='padding: 5px;'>{$name}</td>";
                            echo "<td style='padding: 5px;'>" . number_format($data['elapsed'], 2) . " ms</td>";
                            echo "<td style='padding: 5px; {$style} font-weight: bold;'>" . number_format($delta, 2) . " ms</td>";
                            echo "<td style='padding: 5px;'>" . number_format($data['memory'], 2) . " MB</td>";
                            echo "</tr>";
                            
                            $prev_time = $data['elapsed'];
                        }
                        ?>
                    </tbody>
                </table>
            </div>
            
            <style>
                .perf-metric {
                    padding: 4px 12px;
                    background: rgba(255,255,255,0.1);
                    border-radius: 3px;
                }
                .perf-good {
                    background: rgba(76, 175, 80, 0.3) !important;
                    border-left: 3px solid #4CAF50;
                }
                .perf-warning {
                    background: rgba(255, 152, 0, 0.3) !important;
                    border-left: 3px solid #ff9800;
                }
                .perf-critical {
                    background: rgba(244, 67, 54, 0.3) !important;
                    border-left: 3px solid #f44336;
                    font-weight: bold;
                }
            </style>
        </div>
        <?php
        return ob_get_clean();
    }
}
