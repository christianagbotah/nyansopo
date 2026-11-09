<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
| -------------------------------------------------------------------------
| URI ROUTING
| -------------------------------------------------------------------------
| This file lets you re-map URI requests to specific controller functions.
|
| Typically there is a one-to-one relationship between a URL string
| and its corresponding controller class/method. The segments in a
| URL normally follow this pattern:
|
|	example.com/class/method/id/
|
| In some instances, however, you may want to remap this relationship
| so that a different class/function is called than the one
| corresponding to the URL.
|
| Please see the user guide for complete details:
|
|	http://codeigniter.com/user_guide/general/routing.html
|
| -------------------------------------------------------------------------
| RESERVED ROUTES
| -------------------------------------------------------------------------
|
| There are three reserved routes:
|
|	$route['default_controller'] = 'welcome';
|
| This route indicates which controller class should be loaded if the
| URI contains no data. In the above example, the "welcome" class
| would be loaded.
|
|	$route['404_override'] = 'errors/page_missing';
|
| This route will tell the Router which controller/method to use if those
| provided in the URL cannot be matched to a valid route.
|
|	$route['translate_uri_dashes'] = FALSE;
|
| This is not exactly a route, but allows you to automatically route
| controller and method names that contain dashes. '-' isn't a valid
| class or method name character, so it requires translation.
| When you set this option to TRUE, it will replace ALL dashes in the
| controller and method URI segments.
|
| Examples:	my-controller/index	-> my_controller/index
|		my-controller/my-method	-> my_controller/my_method
*/
$route['default_controller'] = 'login';
$route['backend/(:any)'] = 'backend/$1';

// Public Report Card Verification Route (NO LOGIN REQUIRED)
$route['verify/(:any)/(:any)/(:any)'] = 'verify/index/$1/$2/$3';

$route['sync/push'] = 'sync/push';
$route['sync/pull'] = 'sync/pull';
$route['sync/complete'] = 'sync/complete';
$route['sync/ping'] = 'sync/ping';
$route['sync/cache_data'] = 'sync/cache_data';

// Sync Locations Management Routes (12 routes)
// Maps admin/sync_locations/* URLs to Sync_locations controller
$route['admin/sync_locations/test_connection/(:num)'] = 'sync_locations/test_connection/$1';
$route['admin/sync_locations/deactivate/(:num)'] = 'sync_locations/deactivate/$1';
$route['admin/sync_locations/activate/(:num)'] = 'sync_locations/activate/$1';
$route['admin/sync_locations/delete/(:num)'] = 'sync_locations/delete/$1';
$route['admin/sync_locations/update/(:num)'] = 'sync_locations/update/$1';
$route['admin/sync_locations/modal_edit/(:num)'] = 'sync_locations/modal_edit/$1';
$route['admin/sync_locations/view/(:num)'] = 'sync_locations/view/$1';
$route['admin/sync_locations/edit/(:num)'] = 'sync_locations/edit/$1';
$route['admin/sync_locations/modal_add'] = 'sync_locations/modal_add';
$route['admin/sync_locations/create'] = 'sync_locations/create';
$route['admin/sync_locations/add'] = 'sync_locations/add';
$route['admin/sync_locations'] = 'sync_locations/index';

// Sync Conflicts Resolution Routes (9 routes)
// Maps admin/sync_conflicts/* URLs to Sync_conflicts controller
$route['admin/sync_conflicts/bulk_resolve'] = 'sync_conflicts/bulk_resolve';
$route['admin/sync_conflicts/stats'] = 'sync_conflicts/stats';
$route['admin/sync_conflicts/ignore/(:num)'] = 'sync_conflicts/ignore/$1';
$route['admin/sync_conflicts/merge/(:num)'] = 'sync_conflicts/merge/$1';
$route['admin/sync_conflicts/keep_remote/(:num)'] = 'sync_conflicts/keep_remote/$1';
$route['admin/sync_conflicts/keep_local/(:num)'] = 'sync_conflicts/keep_local/$1';
$route['admin/sync_conflicts/merge_view/(:num)'] = 'sync_conflicts/merge_view/$1';
$route['admin/sync_conflicts/view/(:num)'] = 'sync_conflicts/view/$1';
$route['admin/sync_conflicts'] = 'sync_conflicts/index';

// Sync Audit Trail Routes (5 routes)
// Maps admin/sync_audit/* URLs to Sync_audit controller
$route['admin/sync_audit/stats'] = 'sync_audit/stats';
$route['admin/sync_audit/export'] = 'sync_audit/export';
$route['admin/sync_audit/revert/(:num)'] = 'sync_audit/revert/$1';
$route['admin/sync_audit/view/(:num)'] = 'sync_audit/view/$1';
$route['admin/sync_audit'] = 'sync_audit/index';

// Sync Settings Route (1 route)
// Maps admin/sync_settings URL to Sync_server::settings() method
$route['admin/sync_settings'] = 'sync_server/settings';

// Conduct Items Management Routes
// Maps admin/conduct_items/* URLs to Conduct_items controller
$route['admin/conduct_items/get_form'] = 'conduct_items/get_form';
$route['admin/conduct_items/toggle/(:num)'] = 'conduct_items/toggle/$1';
$route['admin/conduct_items/reorder'] = 'conduct_items/reorder';
$route['admin/conduct_items/delete/(:num)'] = 'conduct_items/delete/$1';
$route['admin/conduct_items/edit/(:num)'] = 'conduct_items/edit/$1';
$route['admin/conduct_items/create'] = 'conduct_items/create';
$route['admin/conduct_items'] = 'conduct_items/index';

// Interest Items Management Routes
// Maps admin/interest_items/* URLs to Interest_items controller
$route['admin/interest_items/get_form'] = 'interest_items/get_form';
$route['admin/interest_items/toggle/(:num)'] = 'interest_items/toggle/$1';
$route['admin/interest_items/reorder'] = 'interest_items/reorder';
$route['admin/interest_items/delete/(:num)'] = 'interest_items/delete/$1';
$route['admin/interest_items/edit/(:num)'] = 'interest_items/edit/$1';
$route['admin/interest_items/create'] = 'interest_items/create';
$route['admin/interest_items'] = 'interest_items/index';

// Head Teacher Remarks Management Routes
// Maps admin/head_teacher_remarks/* URLs to Head_teacher_remarks controller
$route['admin/head_teacher_remarks/get_form'] = 'head_teacher_remarks/get_form';
$route['admin/head_teacher_remarks/toggle_active/(:num)'] = 'head_teacher_remarks/toggle_active/$1';
$route['admin/head_teacher_remarks/update_order'] = 'head_teacher_remarks/update_order';
$route['admin/head_teacher_remarks/delete/(:num)'] = 'head_teacher_remarks/delete/$1';
$route['admin/head_teacher_remarks/update/(:num)'] = 'head_teacher_remarks/update/$1';
$route['admin/head_teacher_remarks/create'] = 'head_teacher_remarks/create';
$route['admin/head_teacher_remarks/check_overlap'] = 'head_teacher_remarks/check_overlap';
$route['admin/head_teacher_remarks/test_percentage'] = 'head_teacher_remarks/test_percentage';
$route['admin/head_teacher_remarks/export_json'] = 'head_teacher_remarks/export_json';
$route['admin/head_teacher_remarks/import_json'] = 'head_teacher_remarks/import_json';
$route['admin/head_teacher_remarks/initialize_defaults'] = 'head_teacher_remarks/initialize_defaults';
$route['admin/head_teacher_remarks/get_by_id/(:num)'] = 'head_teacher_remarks/get_by_id/$1';
$route['admin/head_teacher_remarks/get_all_ajax'] = 'head_teacher_remarks/get_all_ajax';
$route['admin/head_teacher_remarks'] = 'head_teacher_remarks/index';

// Teacher Remarks Templates Routes (already exists, keeping for reference)
$route['admin/teacher_remarks_templates'] = 'teacher_remarks_templates/index';
// ============================================================================
// End of Configurable Items Routes
// ============================================================================

// Daily Fee Management Routes
$route['admin/daily_fee_rates'] = 'admin/daily_fee_rates';
$route['admin/daily_fee_rates/(:any)'] = 'admin/daily_fee_rates/$1';
$route['admin/daily_fee_rates/(:any)/(:any)'] = 'admin/daily_fee_rates/$1/$2';
$route['admin/get_rate_data/(:num)'] = 'admin/get_rate_data/$1';
$route['admin/morning_transport_collection'] = 'admin/morning_transport_collection';
$route['admin/get_route_info/(:num)'] = 'admin/get_route_info/$1';

// Daily Transport Choice & Payment Routes
$route['daily_transport/set_choice_and_collect'] = 'daily_transport/set_choice_and_collect';
$route['daily_transport/get_choice/(:num)/(:any)'] = 'daily_transport/get_choice/$1/$2';
$route['daily_transport/mark_boarding'] = 'daily_transport/mark_boarding';
$route['daily_transport/get_route_students_with_payment/(:num)/(:any)'] = 'daily_transport/get_route_students_with_payment/$1/$2';

// Conductor Routes
$route['conductor/mark_boarding'] = 'conductor/mark_boarding';

$route['404_override'] = '';
$route['translate_uri_dashes'] = FALSE;
