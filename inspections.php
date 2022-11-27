<?php

defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: Inspections
Description: Default module for defining inspections
Version: 2.3.4
Requires at least: 2.3.*
*/

define('INSPECTIONS_MODULE_NAME', 'inspections');
define('INSPECTION_ATTACHMENTS_FOLDER', 'uploads/inspections/');

hooks()->add_filter('before_inspection_updated', '_format_data_inspection_feature');
hooks()->add_filter('before_inspection_added', '_format_data_inspection_feature');

hooks()->add_action('after_cron_run', 'inspections_notification');
hooks()->add_action('admin_init', 'inspections_module_init_menu_items');
hooks()->add_action('admin_init', 'inspections_permissions');
hooks()->add_action('admin_init', 'inspections_settings_tab');
hooks()->add_action('clients_init', 'inspections_clients_area_menu_items');
hooks()->add_filter('get_contact_permissions', 'inspections_contact_permission',10,1);

hooks()->add_action('staff_member_deleted', 'inspections_staff_member_deleted');

hooks()->add_filter('migration_tables_to_replace_old_links', 'inspections_migration_tables_to_replace_old_links');
hooks()->add_filter('global_search_result_query', 'inspections_global_search_result_query', 10, 3);
hooks()->add_filter('global_search_result_output', 'inspections_global_search_result_output', 10, 2);
hooks()->add_filter('get_dashboard_widgets', 'inspections_add_dashboard_widget');
hooks()->add_filter('module_inspections_action_links', 'module_inspections_action_links');


function inspections_add_dashboard_widget($widgets)
{/*
    $widgets[] = [
        'path'      => 'inspections/widgets/inspection_this_week',
        'container' => 'left-8',
    ];
    $widgets[] = [
        'path'      => 'inspections/widgets/project_not_inspectiond',
        'container' => 'left-8',
    ];
*/
    return $widgets;
}


function inspections_staff_member_deleted($data)
{
    $CI = &get_instance();
    $CI->db->where('staff_id', $data['id']);
    $CI->db->update(db_prefix() . 'inspections', [
            'staff_id' => $data['transfer_data_to'],
        ]);
}

function inspections_global_search_result_output($output, $data)
{
    if ($data['type'] == 'inspections') {
        $output = '<a href="' . admin_url('inspections/inspection/' . $data['result']['id']) . '">' . format_inspection_number($data['result']['id']) . '</a>';
    }

    return $output;
}

function inspections_global_search_result_query($result, $q, $limit)
{
    $CI = &get_instance();
    if (has_permission('inspections', '', 'view')) {

        // inspections
        $CI->db->select()
           ->from(db_prefix() . 'inspections')
           ->like(db_prefix() . 'inspections.formatted_number', $q)->limit($limit);
        
        $result[] = [
                'result'         => $CI->db->get()->result_array(),
                'type'           => 'inspections',
                'search_heading' => _l('inspections'),
            ];
        
        if(isset($result[0]['result'][0]['id'])){
            return $result;
        }

        // inspections
        $CI->db->select()->from(db_prefix() . 'inspections')->like(db_prefix() . 'clients.company', $q)->or_like(db_prefix() . 'inspections.formatted_number', $q)->limit($limit);
        $CI->db->join(db_prefix() . 'clients',db_prefix() . 'inspections.clientid='.db_prefix() .'clients.userid', 'left');
        $CI->db->order_by(db_prefix() . 'clients.company', 'ASC');

        $result[] = [
                'result'         => $CI->db->get()->result_array(),
                'type'           => 'inspections',
                'search_heading' => _l('inspections'),
            ];
    }

    return $result;
}

function inspections_migration_tables_to_replace_old_links($tables)
{
    $tables[] = [
                'table' => db_prefix() . 'inspections',
                'field' => 'description',
            ];

    return $tables;
}

function inspections_contact_permission($permissions){
        $item = array(
            'id'         => 8,
            'name'       => _l('inspections'),
            'short_name' => 'inspections',
        );
        $permissions[] = $item;
      return $permissions;

}

function inspections_permissions()
{
    $capabilities = [];

    $capabilities['capabilities'] = [
            'view'   => _l('permission_view') . '(' . _l('permission_global') . ')',
            'create' => _l('permission_create'),
            'edit'   => _l('permission_edit'),
            'delete' => _l('permission_delete'),
    ];

    register_staff_capabilities('inspections', $capabilities, _l('inspections'));
}


/**
* Register activation module hook
*/
register_activation_hook(INSPECTIONS_MODULE_NAME, 'inspections_module_activation_hook');

function inspections_module_activation_hook()
{
    $CI = &get_instance();
    require_once(__DIR__ . '/install.php');
}

/**
* Register deactivation module hook
*/
register_deactivation_hook(INSPECTIONS_MODULE_NAME, 'inspections_module_deactivation_hook');

function inspections_module_deactivation_hook()
{

     log_activity( 'Hello, world! . inspections_module_deactivation_hook ' );
}

//hooks()->add_action('deactivate_' . $module . '_module', $function);

/**
* Register language files, must be registered if the module is using languages
*/
register_language_files(INSPECTIONS_MODULE_NAME, [INSPECTIONS_MODULE_NAME]);

/**
 * Init inspections module menu items in setup in admin_init hook
 * @return null
 */
function inspections_module_init_menu_items()
{
    $CI = &get_instance();

    $CI->app->add_quick_actions_link([
            'name'       => _l('inspection'),
            'url'        => 'inspections',
            'permission' => 'inspections',
            'position'   => 57,
            ]);

    if (has_permission('inspections', '', 'view')) {
        $CI->app_menu->add_sidebar_menu_item('inspections', [
                'slug'     => 'inspections-tracking',
                'name'     => _l('inspections'),
                'icon'     => 'fa fa-calendar',
                'href'     => admin_url('inspections'),
                'position' => 12,
        ]);
    }
}

function module_inspections_action_links($actions)
{
    $actions[] = '<a href="' . admin_url('settings?group=inspections') . '">' . _l('settings') . '</a>';

    return $actions;
}

function inspections_clients_area_menu_items()
{   
    // Show menu item only if client is logged in
    if (is_client_logged_in() && has_contact_permission('inspections')) {
        add_theme_menu_item('inspections', [
                    'name'     => _l('inspections'),
                    'href'     => site_url('inspections/list'),
                    'position' => 15,
        ]);
    }
}

/**
 * [perfex_dark_theme_settings_tab net menu item in setup->settings]
 * @return void
 */
function inspections_settings_tab()
{
    $CI = &get_instance();
    $CI->app_tabs->add_settings_tab('inspections', [
        'name'     => _l('settings_group_inspections'),
        //'view'     => module_views_path(INSPECTIONS_MODULE_NAME, 'admin/settings/includes/inspections'),
        'view'     => 'inspections/inspections_settings',
        'position' => 51,
    ]);
}

$CI = &get_instance();
$CI->load->helper(INSPECTIONS_MODULE_NAME . '/inspections');
if(($CI->uri->segment(1)=='admin' && $CI->uri->segment(2)=='inspections') || $CI->uri->segment(1)=='inspections'){
    $CI->app_css->add(INSPECTIONS_MODULE_NAME.'-css', base_url('modules/'.INSPECTIONS_MODULE_NAME.'/assets/css/'.INSPECTIONS_MODULE_NAME.'.css'));
    $CI->app_scripts->add(INSPECTIONS_MODULE_NAME.'-js', base_url('modules/'.INSPECTIONS_MODULE_NAME.'/assets/js/'.INSPECTIONS_MODULE_NAME.'.js'));
}


