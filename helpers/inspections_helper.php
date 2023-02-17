<?php
defined('BASEPATH') or exit('No direct script access allowed');


function inspections_notification()
{
    $CI = &get_instance();
    $CI->load->model('inspections/inspections_model');
    $inspections = $CI->inspections_model->get('', true);
    /*
    foreach ($inspections as $goal) {
        $achievement = $CI->inspections_model->calculate_goal_achievement($goal['id']);

        if ($achievement['percent'] >= 100) {
            if (date('Y-m-d') >= $goal['end_date']) {
                if ($goal['notify_when_achieve'] == 1) {
                    $CI->inspections_model->notify_staff_members($goal['id'], 'success', $achievement);
                } else {
                    $CI->inspections_model->mark_as_notified($goal['id']);
                }
            }
        } else {
            // not yet achieved, check for end date
            if (date('Y-m-d') > $goal['end_date']) {
                if ($goal['notify_when_fail'] == 1) {
                    $CI->inspections_model->notify_staff_members($goal['id'], 'failed', $achievement);
                } else {
                    $CI->inspections_model->mark_as_notified($goal['id']);
                }
            }
        }
    }
    */
}


/**
 * Function that return inspection item taxes based on passed item id
 * @param  mixed $itemid
 * @return array
 */
function get_inspection_item_taxes($itemid)
{
    $CI = &get_instance();
    $CI->db->where('itemid', $itemid);
    $CI->db->where('rel_type', 'inspection');
    $taxes = $CI->db->get(db_prefix() . 'item_tax')->result_array();
    $i     = 0;
    foreach ($taxes as $tax) {
        $taxes[$i]['taxname'] = $tax['taxname'] . '|' . $tax['taxrate'];
        $i++;
    }

    return $taxes;
}

/**
 * Get Inspection short_url
 * @since  Version 2.7.3
 * @param  object $inspection
 * @return string Url
 */
function get_inspection_shortlink($inspection)
{
    $long_url = site_url("inspection/{$inspection->id}/{$inspection->hash}");
    if (!get_option('bitly_access_token')) {
        return $long_url;
    }

    // Check if inspection has short link, if yes return short link
    if (!empty($inspection->short_link)) {
        return $inspection->short_link;
    }

    // Create short link and return the newly created short link
    $short_link = app_generate_short_link([
        'long_url'  => $long_url,
        'title'     => format_inspection_number($inspection->id)
    ]);

    if ($short_link) {
        $CI = &get_instance();
        $CI->db->where('id', $inspection->id);
        $CI->db->update(db_prefix() . 'inspections', [
            'short_link' => $short_link
        ]);
        return $short_link;
    }
    return $long_url;
}

/**
 * Check inspection restrictions - hash, clientid
 * @param  mixed $id   inspection id
 * @param  string $hash inspection hash
 */
function check_inspection_restrictions($id, $hash)
{
    $CI = &get_instance();
    $CI->load->model('inspections_model');
    if (!$hash || !$id) {
        show_404();
    }
    if (!is_client_logged_in() && !is_staff_logged_in()) {
        if (get_option('view_inspection_only_logged_in') == 1) {
            redirect_after_login_to_current_url();
            redirect(site_url('authentication/login'));
        }
    }
    $inspection = $CI->inspections_model->get($id);
    if (!$inspection || ($inspection->hash != $hash)) {
        show_404();
    }
    // Do one more check
    if (!is_staff_logged_in()) {
        if (get_option('view_inspection_only_logged_in') == 1) {
            if ($inspection->clientid != get_client_user_id()) {
                show_404();
            }
        }
    }
}

/**
 * Check if inspection email template for expiry reminders is enabled
 * @return boolean
 */
function is_inspections_email_expiry_reminder_enabled()
{
    return total_rows(db_prefix() . 'emailtemplates', ['slug' => 'inspection-expiry-reminder', 'active' => 1]) > 0;
}

/**
 * Check if there are sources for sending inspection expiry reminders
 * Will be either email or SMS
 * @return boolean
 */
function is_inspections_expiry_reminders_enabled()
{
    return is_inspections_email_expiry_reminder_enabled() || is_sms_trigger_active(SMS_TRIGGER_SCHEDULE_EXP_REMINDER);
}

/**
 * Return RGBa inspection status color for PDF documents
 * @param  mixed $status_id current inspection status
 * @return string
 */
function inspection_status_color_pdf($status_id)
{
    if ($status_id == 1) {
        $statusColor = '119, 119, 119';
    } elseif ($status_id == 2) {
        // Sent
        $statusColor = '3, 169, 244';
    } elseif ($status_id == 3) {
        //Declines
        $statusColor = '252, 45, 66';
    } elseif ($status_id == 4) {
        //Accepted
        $statusColor = '0, 191, 54';
    } else {
        // Expired
        $statusColor = '255, 111, 0';
    }

    return hooks()->apply_filters('inspection_status_pdf_color', $statusColor, $status_id);
}

/**
 * Format inspection status
 * @param  integer  $status
 * @param  string  $classes additional classes
 * @param  boolean $label   To include in html label or not
 * @return mixed
 */
function format_inspection_status($status, $classes = '', $label = true)
{
    $id          = $status;
    $label_class = inspection_status_color_class($status);
    $status      = inspection_status_by_id($status);
    if ($label == true) {
        return '<span class="label label-' . $label_class . ' ' . $classes . ' s-status inspection-status-' . $id . ' inspection-status-' . $label_class . '">' . $status . '</span>';
    }

    return $status;
}

/**
 * Return inspection status translated by passed status id
 * @param  mixed $id inspection status id
 * @return string
 */
function inspection_status_by_id($id)
{
    $status = '';
    if ($id == 1) {
        $status = _l('inspection_status_draft');
    } elseif ($id == 2) {
        $status = _l('inspection_status_sent');
    } elseif ($id == 3) {
        $status = _l('inspection_status_declined');
    } elseif ($id == 4) {
        $status = _l('inspection_status_accepted');
    } elseif ($id == 5) {
        // status 5
        $status = _l('inspection_status_expired');
    } else {
        if (!is_numeric($id)) {
            if ($id == 'not_sent') {
                $status = _l('not_sent_indicator');
            }
        }
    }

    return hooks()->apply_filters('inspection_status_label', $status, $id);
}

/**
 * Return inspection status color class based on twitter bootstrap
 * @param  mixed  $id
 * @param  boolean $replace_default_by_muted
 * @return string
 */
function inspection_status_color_class($id, $replace_default_by_muted = false)
{
    $class = '';
    if ($id == 1) {
        $class = 'default';
        if ($replace_default_by_muted == true) {
            $class = 'muted';
        }
    } elseif ($id == 2) {
        $class = 'info';
    } elseif ($id == 3) {
        $class = 'danger';
    } elseif ($id == 4) {
        $class = 'success';
    } elseif ($id == 5) {
        // status 5
        $class = 'warning';
    } else {
        if (!is_numeric($id)) {
            if ($id == 'not_sent') {
                $class = 'default';
                if ($replace_default_by_muted == true) {
                    $class = 'muted';
                }
            }
        }
    }

    return hooks()->apply_filters('inspection_status_color_class', $class, $id);
}

/**
 * Check if the inspection id is last licence
 * @param  mixed  $id inspectionid
 * @return boolean
 */
function is_last_inspection($id)
{
    $CI = &get_instance();
    $CI->db->select('id')->from(db_prefix() . 'inspections')->order_by('id', 'desc')->limit(1);
    $query            = $CI->db->get();
    $last_inspection_id = $query->row()->id;
    if ($last_inspection_id == $id) {
        return true;
    }

    return false;
}

/**
 * Format inspection number based on description
 * @param  mixed $id
 * @return string
 */
function format_inspection_number($id)
{
    $CI = &get_instance();
    $CI->db->select('date,number,prefix,number_format')->from(db_prefix() . 'inspections')->where('id', $id);
    $inspection = $CI->db->get()->row();

    if (!$inspection) {
        return '';
    }

    $number = inspection_number_format($inspection->number, $inspection->number_format, $inspection->prefix, $inspection->date);

    return hooks()->apply_filters('format_inspection_number', $number, [
        'id'       => $id,
        'inspection' => $inspection,
    ]);
}


function inspection_number_format($number, $format, $applied_prefix, $date)
{
    $originalNumber = $number;
    $prefixPadding  = get_option('number_padding_prefixes');

    if ($format == 1) {
        // Number based
        $number = $applied_prefix . str_pad($number, $prefixPadding, '0', STR_PAD_LEFT);
    } elseif ($format == 2) {
        // Year based
        $number = $applied_prefix . date('Y', strtotime($date)) . '.' . str_pad($number, $prefixPadding, '0', STR_PAD_LEFT);
    } elseif ($format == 3) {
        // Number-yy based
        $number = $applied_prefix . str_pad($number, $prefixPadding, '0', STR_PAD_LEFT) . '-' . date('y', strtotime($date));
    } elseif ($format == 4) {
        // Number-mm-yyyy based
        $number = $applied_prefix . str_pad($number, $prefixPadding, '0', STR_PAD_LEFT) . '.' . date('m', strtotime($date)) . '.' . date('Y', strtotime($date));
    }

    return hooks()->apply_filters('inspection_number_format', $number, [
        'format'         => $format,
        'date'           => $date,
        'number'         => $originalNumber,
        'prefix_padding' => $prefixPadding,
    ]);
}

/**
 * Calculate inspections percent by status
 * @param  mixed $status          inspection status
 * @return array
 */
function get_inspections_percent_by_status($status, $program_id = null)
{
    $has_permission_view = has_permission('inspections', '', 'view');
    $where               = '';

    if (isset($program_id)) {
        $where .= 'program_id=' . get_instance()->db->escape_str($program_id) . ' AND ';
    }
    if (!$has_permission_view) {
        $where .= get_inspections_where_sql_for_staff(get_staff_user_id());
    }

    $where = trim($where);

    if (endsWith($where, ' AND')) {
        $where = substr_replace($where, '', -3);
    }

    $total_inspections = total_rows(db_prefix() . 'inspections', $where);

    $data            = [];
    $total_by_status = 0;

    if (!is_numeric($status)) {
        if ($status == 'not_sent') {
            $total_by_status = total_rows(db_prefix() . 'inspections', 'sent=0 AND status NOT IN(2,3,4)' . ($where != '' ? ' AND (' . $where . ')' : ''));
        }
    } else {
        $whereByStatus = 'status=' . $status;
        if ($where != '') {
            $whereByStatus .= ' AND (' . $where . ')';
        }
        $total_by_status = total_rows(db_prefix() . 'inspections', $whereByStatus);
    }

    $percent                 = ($total_inspections > 0 ? number_format(($total_by_status * 100) / $total_inspections, 2) : 0);
    $data['total_by_status'] = $total_by_status;
    $data['percent']         = $percent;
    $data['total']           = $total_inspections;

    return $data;
}

function get_inspections_where_sql_for_staff($staff_id)
{
    $CI = &get_instance();
    $has_permission_view_own             = has_permission('inspections', '', 'view_own');
    $allow_staff_view_inspections_assigned = get_option('allow_staff_view_inspections_assigned');
    $whereUser                           = '';
    if ($has_permission_view_own) {
        $whereUser = '((' . db_prefix() . 'inspections.addedfrom=' . $CI->db->escape_str($staff_id) . ' AND ' . db_prefix() . 'inspections.addedfrom IN (SELECT staff_id FROM ' . db_prefix() . 'staff_permissions WHERE feature = "inspections" AND capability="view_own"))';
        if ($allow_staff_view_inspections_assigned == 1) {
            $whereUser .= ' OR inspector_staff_id=' . $CI->db->escape_str($staff_id);
        }
        $whereUser .= ')';
    } else {
        $whereUser .= 'inspector_staff_id=' . $CI->db->escape_str($staff_id);
    }

    return $whereUser;
}
/**
 * Check if staff member have assigned inspections / added as sale agent
 * @param  mixed $staff_id staff id to check
 * @return boolean
 */
function staff_has_assigned_inspections($staff_id = '')
{
    $CI       = &get_instance();
    $staff_id = is_numeric($staff_id) ? $staff_id : get_staff_user_id();
    $cache    = $CI->app_object_cache->get('staff-total-assigned-inspections-' . $staff_id);

    if (is_numeric($cache)) {
        $result = $cache;
    } else {
        $result = total_rows(db_prefix() . 'inspections', ['inspector_staff_id' => $staff_id]);
        $CI->app_object_cache->add('staff-total-assigned-inspections-' . $staff_id, $result);
    }

    return $result > 0 ? true : false;
}
/**
 * Check if staff member can view inspection
 * @param  mixed $id inspection id
 * @param  mixed $staff_id
 * @return boolean
 */
function user_can_view_inspection($id, $staff_id = false)
{
    $CI = &get_instance();

    $staff_id = $staff_id ? $staff_id : get_staff_user_id();

    if (has_permission('inspections', $staff_id, 'view')) {
        return true;
    }

    if(is_client_logged_in()){

        $CI = &get_instance();
        $CI->load->model('inspections_model');

        $inspection = $CI->inspections_model->get($id);
        if (!$inspection) {
            show_404();
        }
        // Do one more check
        if (get_option('view_inspectiont_only_logged_in') == 1) {
            if ($inspection->clientid != get_client_user_id()) {
                show_404();
            }
        }

        return true;
    }

    $CI->db->select('id, addedfrom, inspector_staff_id');
    $CI->db->from(db_prefix() . 'inspections');
    $CI->db->where('id', $id);
    $inspection = $CI->db->get()->row();

    if ((has_permission('inspections', $staff_id, 'view_own') && $inspection->addedfrom == $staff_id)
        || ($inspection->inspector_staff_id == $staff_id && get_option('allow_staff_view_inspections_assigned') == '1')
    ) {
        return true;
    }

    return false;
}


/**
 * Prepare general inspection pdf
 * @since  Version 1.0.2
 * @param  object $inspection inspection as object with all necessary fields
 * @param  string $tag tag for bulk pdf exporter
 * @return mixed object
 */
function inspection_pdf($inspection, $tag = '')
{
    return app_pdf('inspection',  module_libs_path(INSPECTIONS_MODULE_NAME) . 'pdf/Inspection_pdf', $inspection, $tag);
}


/**
 * Prepare general inspection pdf
 * @since  Version 1.0.2
 * @param  object $inspection inspection as object with all necessary fields
 * @param  string $tag tag for bulk pdf exporter
 * @return mixed object
 */
function inspection_office_pdf($inspection, $tag = '')
{
    return app_pdf('inspection',  module_libs_path(INSPECTIONS_MODULE_NAME) . 'pdf/Inspection_office_pdf', $inspection, $tag);
}



/**
 * Get items table for preview
 * @param  object  $transaction   e.q. licence, inspection from database result row
 * @param  string  $type          type, e.q. licence, inspection, proposal
 * @param  string  $for           where the items will be shown, html or pdf
 * @param  boolean $admin_preview is the preview for admin area
 * @return object
 */
function get_inspection_items_table_data($transaction, $type, $for = 'html', $admin_preview = false)
{
    include_once(module_libs_path(INSPECTIONS_MODULE_NAME) . 'Inspection_items_table.php');

    $class = new Inspection_items_table($transaction, $type, $for, $admin_preview);

    $class = hooks()->apply_filters('items_table_class', $class, $transaction, $type, $for, $admin_preview);

    if (!$class instanceof App_items_table_template) {
        show_error(get_class($class) . ' must be instance of "Inspection_items_template"');
    }

    return $class;
}



/**
 * Add new item do database, used for proposals,inspections,credit notes,licences
 * This is repetitive action, that's why this function exists
 * @param array $item     item from $_POST
 * @param mixed $rel_id   relation id eq. licence id
 * @param string $rel_type relation type eq licence
 */
function add_new_inspection_item_post($item, $rel_id, $rel_type)
{

    $CI = &get_instance();

    $CI->db->insert(db_prefix() . 'itemable', [
                    'description'      => $item['description'],
                    'long_description' => nl2br($item['long_description']),
                    'qty'              => $item['qty'],
                    'rel_id'           => $rel_id,
                    'rel_type'         => $rel_type,
                    'item_order'       => $item['order'],
                    'unit'             => isset($item['unit']) ? $item['unit'] : 'unit',
                ]);

    $id = $CI->db->insert_id();

    return $id;
}

/**
 * Update inspection item from $_POST
 * @param  mixed $item_id item id to update
 * @param  array $data    item $_POST data
 * @param  string $field   field is require to be passed for long_description,rate,item_order to do some additional checkings
 * @return boolean
 */
function update_inspection_item_post($item_id, $data, $field = '')
{
    $update = [];
    if ($field !== '') {
        if ($field == 'long_description') {
            $update[$field] = nl2br($data[$field]);
        } elseif ($field == 'rate') {
            $update[$field] = number_format($data[$field], get_decimal_places(), '.', '');
        } elseif ($field == 'item_order') {
            $update[$field] = $data['order'];
        } else {
            $update[$field] = $data[$field];
        }
    } else {
        $update = [
            'item_order'       => $data['order'],
            'description'      => $data['description'],
            'long_description' => nl2br($data['long_description']),
            'qty'              => $data['qty'],
            'unit'             => $data['unit'],
        ];
    }

    $CI = &get_instance();
    $CI->db->where('id', $item_id);
    $CI->db->update(db_prefix() . 'itemable', $update);

    return $CI->db->affected_rows() > 0 ? true : false;
}


/**
 * Prepares email template preview $data for the view
 * @param  string $template    template class name
 * @param  mixed $customer_id_or_email customer ID to fetch the primary contact email or email
 * @return array
 */
function inspection_mail_preview_data($template, $customer_id_or_email, $mailClassParams = [])
{
    $CI = &get_instance();

    if (is_numeric($customer_id_or_email)) {
        $contact = $CI->clients_model->get_contact(get_primary_contact_user_id($customer_id_or_email));
        $email   = $contact ? $contact->email : '';
    } else {
        $email = $customer_id_or_email;
    }

    $CI->load->model('emails_model');

    $data['template'] = $CI->app_mail_template->prepare($email, $template);
    $slug             = $CI->app_mail_template->get_default_property_value('slug', $template, $mailClassParams);

    $data['template_name'] = $slug;

    $template_result = $CI->emails_model->get(['slug' => $slug, 'language' => 'english'], 'row');

    $data['template_system_name'] = $template_result->name;
    $data['template_id']          = $template_result->emailtemplateid;

    $data['template_disabled'] = $template_result->active == 0;

    return $data;
}


/**
 * Function that return full path for upload based on passed type
 * @param  string $type
 * @return string
 */
function get_inspection_upload_path($type=NULL)
{
   $type = 'inspection';
   $path = SCHEDULE_ATTACHMENTS_FOLDER;

    return hooks()->apply_filters('get_upload_path_by_type', $path, $type);
}

/**
 * Remove and format some common used data for the inspection feature eq licence,inspections etc..
 * @param  array $data $_POST data
 * @return array
 */
function _format_data_inspection_feature($data)
{
    foreach (_get_inspection_feature_unused_names() as $u) {
        if (isset($data['data'][$u])) {
            unset($data['data'][$u]);
        }
    }

    if (isset($data['data']['date'])) {
        $data['data']['date'] = to_sql_date($data['data']['date']);
    }

    if (isset($data['data']['open_till'])) {
        $data['data']['open_till'] = to_sql_date($data['data']['open_till']);
    }

    if (isset($data['data']['expirydate'])) {
        $data['data']['expirydate'] = to_sql_date($data['data']['expirydate']);
    }

    if (isset($data['data']['duedate'])) {
        $data['data']['duedate'] = to_sql_date($data['data']['duedate']);
    }

    if (isset($data['data']['clientnote'])) {
        $data['data']['clientnote'] = nl2br_save_html($data['data']['clientnote']);
    }

    if (isset($data['data']['terms'])) {
        $data['data']['terms'] = nl2br_save_html($data['data']['terms']);
    }

    if (isset($data['data']['adminnote'])) {
        $data['data']['adminnote'] = nl2br($data['data']['adminnote']);
    }

    foreach (['country', 'billing_country', 'shipping_country', 'program_id', 'inspector_staff_id'] as $should_be_zero) {
        if (isset($data['data'][$should_be_zero]) && $data['data'][$should_be_zero] == '') {
            $data['data'][$should_be_zero] = 0;
        }
    }

    return $data;
}


/**
 * Unsed $_POST request names, mostly they are used as helper inputs in the form
 * The top function will check all of them and unset from the $data
 * @return array
 */
function _get_inspection_feature_unused_names()
{
    return [
        'taxname', 'description',
        'currency_symbol', 'price',
        'isedit', 'taxid',
        'long_description', 'unit',
        'rate', 'quantity',
        'item_select', 'tax',
        'billed_tasks', 'billed_expenses',
        'task_select', 'task_id',
        'expense_id', 'repeat_every_custom',
        'repeat_type_custom', 'bill_expenses',
        'save_and_send', 'merge_current_licence',
        'cancel_merged_licences', 'licences_to_merge',
        'tags', 's_prefix', 'save_and_record_payment',
    ];
}

/**
 * When item is removed eq from licence will be stored in removed_items in $_POST
 * With foreach loop this function will remove the item from database and it's taxes
 * @param  mixed $id       item id to remove
 * @param  string $rel_type item relation eq. licence, inspection
 * @return boolena
 */
function handle_removed_inspection_item_post($id, $rel_type)
{
    $CI = &get_instance();

    $CI->db->where('id', $id);
    $CI->db->where('rel_type', $rel_type);
    $CI->db->delete(db_prefix() . 'itemable');
    if ($CI->db->affected_rows() > 0) {
        return true;
    }

    return false;
}

/**
 * Check if customer has project assigned
 * @param  mixed $customer_id customer id to check
 * @return boolean
 */
function project_has_inspections($program_id)
{
    $totalProjectsInspectiond = total_rows(db_prefix() . 'inspections', 'program_id=' . get_instance()->db->escape_str($program_id));

    return ($totalProjectsInspectiond > 0 ? true : false);
}


function delete_inspection_items($id){
    $CI = &get_instance();
    $CI->db->where('inspection_id',$id);
    $CI->db->set('inspection_id', null);
    $CI->db->update(db_prefix(). 'program_items')();

    $CI->db->where('inspection_id',$id);
    $CI->db->set('inspection_id', null);
    $CI->db->update(db_prefix(). 'programs')();
}

function inspections_before_parse_email_template_message($template){
    //log_activity(json_encode($template));
}

function get_staff_client($surveyor_id){
    $CI = &get_instance();
    $CI->db->select('staffid, firstname, lastname, client_id');
    $CI->db->from(db_prefix() . 'staff');
    $CI->db->where('client_id', $surveyor_id);
    return $CI->db->get()->result();

}
