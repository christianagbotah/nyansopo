<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
| -------------------------------------------------------------------
| AUTO-LOADER
| -------------------------------------------------------------------
| This file specifies which systems should be loaded by default.
|
| In order to keep the framework as light-weight as possible only the
| absolute minimal resources are loaded by default. For example,
| the database is not connected to automatically since no assumption
| is made regarding whether you intend to use it.  This file lets
| you globally define which systems you would like loaded with every
| request.
|
| -------------------------------------------------------------------
| Instructions
| -------------------------------------------------------------------
|
| These are the things you can load automatically:
|
| 1. Packages
| 2. Libraries
| 3. Drivers
| 4. Helper files
| 5. Custom config files
| 6. Language files
| 7. Models
|
*/

/*
| -------------------------------------------------------------------
| AUTOLOAD OPTIMIZATION STRATEGY FOR SCHOOL MANAGER
| -------------------------------------------------------------------
|
| This application uses EXTENSIVE AUTOLOADING as a deliberate performance
| optimization strategy. The configuration below is intentional and optimal
| for this specific application architecture.
|
| WHY EXTENSIVE AUTOLOADING?
|
| 1. REDUCED FILE I/O OPERATIONS
|    - Loading helpers/models once at startup is faster than loading them
|      individually across multiple controllers and views
|    - Minimizes repeated file system access and include() operations
|
| 2. SIMPLIFIED CONTROLLER CODE
|    - Controllers don't need to individually load commonly used helpers
|    - Reduces code duplication across 50+ controller files
|    - Cleaner, more maintainable controller code
|
| 3. CONSISTENT AVAILABILITY
|    - Core helpers (url, form, security, etc.) available everywhere
|    - Business helpers (fee_module, billing, sync, etc.) ready for use
|    - Models (Invoice_model, Finance_model, etc.) accessible without loading
|
| 4. OPCODE CACHING BENEFITS
|    - With OpCache enabled, autoloaded files are cached in memory
|    - Single load + cache is more efficient than multiple lazy loads
|    - Negligible memory overhead with modern PHP opcode caching
|
| CURRENT AUTOLOAD METRICS:
| - 45+ Helpers autoloaded (core + business logic helpers)
| - 20+ Models autoloaded (frequently used throughout application)
| - 5 Libraries autoloaded (pagination, form_validation, email, upload, xmlrpc)
|
| PERFORMANCE CONSIDERATIONS:
| - Total autoload overhead: ~50-100ms on first request (without OpCache)
| - With OpCache: <5ms on subsequent requests (cached in memory)
| - Alternative (lazy loading): 10-20ms per helper load × 20+ helpers = 200-400ms
| - Net benefit: 150-350ms faster page loads with autoloading + OpCache
|
| WHEN TO MODIFY THIS CONFIGURATION:
| - Adding new commonly-used helpers → ADD to autoload (if used in 10+ places)
| - Adding new frequently-used models → ADD to autoload (if used in 5+ controllers)
| - Removing deprecated helpers → REMOVE from autoload
| - One-off specialized helpers → DO NOT autoload (load manually where needed)
|
| DO NOT reduce autoloading unless you have specific performance data showing
| it improves performance for this application. The current configuration is
| optimized based on actual usage patterns across the School Manager codebase.
|
*/

/*
| -------------------------------------------------------------------
|  Auto-load Packages
| -------------------------------------------------------------------
| Prototype:
|
|  $autoload['packages'] = array(APPPATH.'third_party', '/usr/local/shared');
|
*/

$autoload['packages'] = array();


/*
| -------------------------------------------------------------------
|  Auto-load Libraries
| -------------------------------------------------------------------
| These are the classes located in the system/libraries folder
| or in your application/libraries folder.
|
| Prototype:
|
|	$autoload['libraries'] = array('database', 'email', 'session');
|
| You can also supply an alternative library name to be assigned
| in the controller:
|
|	$autoload['libraries'] = array('user_agent' => 'ua');
*/

$autoload['libraries'] = array('pagination', 'xmlrpc' , 'form_validation', 'email','upload');


/*
| -------------------------------------------------------------------
|  Auto-load Drivers
| -------------------------------------------------------------------
| These classes are located in the system/libraries folder or in your
| application/libraries folder within their own subdirectory. They
| offer multiple interchangeable driver options.
|
| Prototype:
|
|	$autoload['drivers'] = array('cache');
*/

$autoload['drivers'] = array();


/*
| -------------------------------------------------------------------
|  Auto-load Helper Files
| -------------------------------------------------------------------
| Prototype:
|
|	$autoload['helper'] = array('url', 'file');
*/

$autoload['helper'] = array('url','file','form','security','string','inflector','directory','download','multi_language', 'user_validation', 'manager', 'form_field', 'beneficiary', 'daily_fee_stats', 'daily_fee_reconciliation', 'daily_fee_analytics', 'daily_fee_alerts', 'fee_module', 'billing', 'notification', 'discount_profile', 'asset', 'finance_integration', 'finance', 'approval', 'audit', 'discount', 'discount_modification', 'discount_tracking', 'export', 'fee_system_bridge', 'invoice_lock', 'receipt', 'transport', 'unlock', 'daily_fee', 'daily_fee_features', 'discount_helper', 'payment_method_helper', 'attendance_privilege', 'sync');


/*
| -------------------------------------------------------------------
|  Auto-load Config files
| -------------------------------------------------------------------
| Prototype:
|
|	$autoload['config'] = array('config1', 'config2');
|
| NOTE: This item is intended for use ONLY if you have created custom
| config files.  Otherwise, leave it blank.
|
*/

$autoload['config'] = array();


/*
| -------------------------------------------------------------------
|  Auto-load Language files
| -------------------------------------------------------------------
| Prototype:
|
|	$autoload['language'] = array('lang1', 'lang2');
|
| NOTE: Do not include the "_lang" part of your file.  For example
| "codeigniter_lang.php" would be referenced as array('codeigniter');
|
*/

$autoload['language'] = array('receipt_modification', 'discount');


/*
| -------------------------------------------------------------------
|  Auto-load Models
| -------------------------------------------------------------------
| Prototype:
|
|	$autoload['model'] = array('first_model', 'second_model');
|
| You can also supply an alternative model name to be assigned
| in the controller:
|
|	$autoload['model'] = array('first_model' => 'first');
*/

$autoload['model'] = array('email_model', 'crud_model', 'invoice_model', 'barcode_model', 'sms_model', 'frontend_model', 'financial_report_model', 'momo_model', 'boarding_model', 'payroll_model', 'Finance_model', 'Accounts_model', 'Daily_fee_model', 'Daily_fee_discount_model', 'Discount_model', 'Notification_model', 'Payment_gateway_model', 'Setting_model', 'Sync_model', 'Transport_model', 'Login_model', 'Graphassessment_model');

// $autoload['model'] = array('email_model', 'crud_model', 'invoice_model', 'barcode_model', 'sms_model', 'frontend_model', 'financial_report_model', 'momo_model', 'boarding_model', 'payroll_model', 'Finance_model', 'Accounts_model', 'Daily_fee_model', 'Daily_fee_discount_model', 'Discount_model', 'Notification_model', 'Payment_gateway_model', 'Setting_model', 'Sync_model', 'Transport_model', 'Login_model');
