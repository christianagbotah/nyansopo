<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * OpCache Initialization Configuration
 * 
 * Enables PHP OpCache for improved performance by caching compiled PHP scripts.
 * This configuration works with both Apache and Nginx web servers.
 * 
 * OpCache stores precompiled script bytecode in shared memory, eliminating the
 * need to parse and compile scripts on every request.
 * 
 * Performance Impact:
 * - 2-3x faster script execution
 * - Reduced CPU usage
 * - Better memory utilization
 * 
 * @package    School Manager
 * @subpackage Configuration
 * @category   Performance
 * @author     Performance Optimization Team
 * @created    2024
 */

// Check if OpCache extension is available
if (function_exists('opcache_get_status')) {
    
    /**
     * Enable OpCache
     * 
     * Stores compiled PHP code in memory for faster execution.
     * Setting to '1' enables opcode caching for CLI and web requests.
     */
    ini_set('opcache.enable', '1');
    
    /**
     * Memory Consumption (in MB)
     * 
     * Amount of memory allocated to OpCache for storing compiled scripts.
     * 128 MB is sufficient for most medium-to-large applications like School Manager.
     * 
     * Adjust based on application size:
     * - Small apps (<100 files): 64 MB
     * - Medium apps (100-500 files): 128 MB
     * - Large apps (>500 files): 256 MB
     */
    ini_set('opcache.memory_consumption', '128');
    
    /**
     * Interned Strings Buffer (in MB)
     * 
     * Memory allocated for storing strings that appear in multiple scripts
     * (variable names, function names, class names, etc.).
     * 
     * 8 MB is recommended for typical PHP applications.
     * Increase to 16 MB for very large codebases with extensive class hierarchies.
     */
    ini_set('opcache.interned_strings_buffer', '8');
    
    /**
     * Maximum Accelerated Files
     * 
     * Maximum number of PHP files that can be cached.
     * Should be higher than the total number of PHP files in the application.
     * 
     * School Manager has approximately 500+ PHP files.
     * Setting to 10000 provides ample headroom for growth.
     * 
     * Note: The actual value will be rounded up to the next highest prime number
     * for optimal hash table performance.
     */
    ini_set('opcache.max_accelerated_files', '10000');
    
    /**
     * Revalidation Frequency (in seconds)
     * 
     * How often OpCache checks if files have been modified.
     * 
     * Development: 2 seconds (quick detection of code changes)
     * Production: 60 seconds or higher (minimize file system checks)
     * Maximum Performance: 0 (never check - requires manual cache clear on updates)
     * 
     * Current setting: 2 seconds (suitable for development/staging)
     * 
     * For production deployment, consider changing to 60 or disabling
     * validate_timestamps entirely for maximum performance.
     */
    ini_set('opcache.revalidate_freq', '2');
    
    /**
     * Validate Timestamps
     * 
     * Enable/disable checking if cached files have been modified.
     * 
     * Development: 1 (enabled - automatically detect code changes)
     * Production: 0 (disabled - maximum performance, requires manual cache clear)
     * 
     * When disabled, you must manually clear OpCache after code deployments:
     * - Via code: opcache_reset()
     * - Via CLI: php -r "opcache_reset();"
     * - Via web server restart
     * 
     * Current setting: 1 (enabled for safety)
     */
    ini_set('opcache.validate_timestamps', '1');
    
    /**
     * Fast Shutdown
     * 
     * Use fast shutdown sequence for cached code.
     * Provides a faster mechanism for calling destructors in code.
     * 
     * Recommended: 1 (enabled) for improved performance.
     */
    ini_set('opcache.fast_shutdown', '1');
    
    /**
     * Save Comments
     * 
     * Store PHPDoc comments in cached code.
     * Required for some frameworks that use reflection to read annotations.
     * 
     * CodeIgniter doesn't rely heavily on comment parsing, but keeping enabled
     * for compatibility with third-party libraries and future features.
     */
    ini_set('opcache.save_comments', '1');
    
    /**
     * Enable File Override
     * 
     * If enabled, OpCache will check for file_exists(), is_file() and is_readable()
     * in the cache before hitting the file system.
     * 
     * Recommended: 0 (disabled) for CodeIgniter to avoid issues with file checks.
     */
    ini_set('opcache.enable_file_override', '0');
    
    // Log successful initialization
    // Note: Using error_log() instead of log_message() since this runs before CI logging is initialized
    error_log('OpCache: Initialized successfully with ' . 
                ini_get('opcache.memory_consumption') . 'MB memory, ' .
                ini_get('opcache.max_accelerated_files') . ' max files, ' .
                ini_get('opcache.revalidate_freq') . 's revalidation frequency');
    
} else {
    // OpCache extension not available - log warning but don't fail
    // Application will continue to function normally, just without opcode caching
    // Note: Using error_log() instead of log_message() since this runs before CI logging is initialized
    error_log('OpCache: Extension not available, opcode caching disabled. ' .
                'Install/enable PHP OpCache extension for improved performance.');
}

/**
 * DEPLOYMENT NOTES:
 * 
 * 1. For production deployment, consider these optimizations:
 *    - Set opcache.revalidate_freq to 60 or higher
 *    - Set opcache.validate_timestamps to 0 (requires manual cache clear)
 *    - Set opcache.memory_consumption to 256 if you have available RAM
 * 
 * 2. To manually clear OpCache after code deployment:
 *    - Create a script: <?php opcache_reset(); echo "OpCache cleared"; ?>
 *    - Call via web browser (protect with authentication!)
 *    - Or restart web server: sudo service apache2 restart / sudo service nginx restart
 * 
 * 3. To monitor OpCache performance:
 *    - Create a status script: <?php phpinfo(); ?> and search for "opcache"
 *    - Or use: <?php print_r(opcache_get_status()); ?>
 *    - Look for hit rate >90% for optimal performance
 * 
 * 4. If you encounter issues after enabling OpCache:
 *    - Check for cached stale code: clear OpCache and test again
 *    - Verify file permissions are correct
 *    - Check OpCache memory usage isn't maxed out
 *    - Review PHP error logs for OpCache warnings
 */
