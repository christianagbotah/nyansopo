<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * NoInternetException
 * 
 * Exception thrown when no internet connectivity is detected
 * Used in sync error communication enhancement (Task 3.3)
 * 
 * Requirements: 2.1, 2.3, 2.8
 * 
 * @package    School Manager
 * @subpackage Exceptions
 * @category   Sync
 * @author     School Manager Team
 * @version    1.0.0
 */
class NoInternetException extends Exception {
    
    /**
     * Error diagnostics
     * @var array
     */
    private $diagnostics = [];
    
    /**
     * Constructor
     * 
     * @param string $message Error message
     * @param array $diagnostics Error diagnostic information (methods tried, timestamp, etc.)
     * @param int $code Error code
     * @param Throwable $previous Previous exception
     */
    public function __construct($message = "", array $diagnostics = [], $code = 0, Throwable $previous = null) {
        parent::__construct($message, $code, $previous);
        $this->diagnostics = $diagnostics;
    }
    
    /**
     * Get error diagnostics
     * 
     * @return array Diagnostic information including methods tried, connectivity tests
     */
    public function getDiagnostics() {
        return $this->diagnostics;
    }
    
    /**
     * Get formatted error message with diagnostics
     * 
     * @return string Formatted error message
     */
    public function getFullMessage() {
        $msg = $this->getMessage();
        
        if (!empty($this->diagnostics)) {
            $msg .= "\nDiagnostics:";
            foreach ($this->diagnostics as $key => $value) {
                if (is_array($value)) {
                    $msg .= "\n  $key: " . json_encode($value);
                } else {
                    $msg .= "\n  $key: $value";
                }
            }
        }
        
        return $msg;
    }
}
