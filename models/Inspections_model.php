<?php

use app\services\AbstractKanban;
use app\services\inspections\InspectionsPipeline;

defined('BASEPATH') or exit('No direct script access allowed');

class Inspections_model extends App_Model
{
    private $statuses;

    private $shipping_fields = ['shipping_street', 'shipping_city', 'shipping_city', 'shipping_state', 'shipping_zip', 'shipping_country'];

    public function __construct()
    {
        parent::__construct();

        $this->statuses = hooks()->apply_filters('before_set_inspection_statuses', [
            1,
            2,
            5,
            3,
            4,
        ]);
    }

    /**
     * Get unique sale agent for inspections / Used for filters
     * @return array
     */
    public function get_sale_agents()
    {
        return $this->db->query("SELECT DISTINCT(sale_agent) as sale_agent, CONCAT(firstname, ' ', lastname) as full_name FROM " . db_prefix() . 'inspections JOIN ' . db_prefix() . 'staff on ' . db_prefix() . 'staff.staffid=' . db_prefix() . 'inspections.sale_agent WHERE sale_agent != 0')->result_array();
    }

    /**
     * Get inspection/s
     * @param mixed $id inspection id
     * @param array $where perform where
     * @return mixed
     */
    public function get($id = '', $where = [])
    {
        $this->db->select('*,' . db_prefix() . 'currencies.id as currencyid, ' . db_prefix() . 'inspections.id as id, ' . db_prefix() . 'currencies.name as currency_name');
        $this->db->from(db_prefix() . 'inspections');
        $this->db->join(db_prefix() . 'currencies', db_prefix() . 'currencies.id = ' . db_prefix() . 'inspections.currency', 'left');
        $this->db->where($where);
        if (is_numeric($id)) {
            $this->db->where(db_prefix() . 'inspections.id', $id);
            $inspection = $this->db->get()->row();
            if ($inspection) {
                $inspection->attachments                           = $this->get_attachments($id);
                $inspection->visible_attachments_to_customer_found = false;

                foreach ($inspection->attachments as $attachment) {
                    if ($attachment['visible_to_customer'] == 1) {
                        $inspection->visible_attachments_to_customer_found = true;

                        break;
                    }
                }

                $inspection->items = get_items_by_type('inspection', $id);

                if ($inspection->program_id != 0) {
                    $this->load->model('projects_model');
                    $inspection->project_data = $this->projects_model->get($inspection->program_id);
                }

                $inspection->client = $this->clients_model->get($inspection->clientid);

                if (!$inspection->client) {
                    $inspection->client          = new stdClass();
                    $inspection->client->company = $inspection->deleted_customer_name;
                }

                $this->load->model('email_schedule_model');
                $inspection->inspectiond_email = $this->email_schedule_model->get($id, 'inspection');
            }

            return $inspection;
        }
        $this->db->order_by('number,YEAR(date)', 'desc');

        return $this->db->get()->result_array();
    }

    /**
     * Get inspection statuses
     * @return array
     */
    public function get_statuses()
    {
        return $this->statuses;
    }

    public function clear_signature($id)
    {
        $this->db->select('signature');
        $this->db->where('id', $id);
        $inspection = $this->db->get(db_prefix() . 'inspections')->row();

        if ($inspection) {
            $this->db->where('id', $id);
            $this->db->update(db_prefix() . 'inspections', ['signature' => null]);

            if (!empty($inspection->signature)) {
                unlink(get_upload_path_by_type('inspection') . $id . '/' . $inspection->signature);
            }

            return true;
        }

        return false;
    }

    /**
     * Convert inspection to invoice
     * @param mixed $id inspection id
     * @return mixed     New invoice ID
     */
    public function convert_to_invoice($id, $client = false, $draft_invoice = false)
    {
        // Recurring invoice date is okey lets convert it to new invoice
        $_inspection = $this->get($id);

        $new_invoice_data = [];
        if ($draft_invoice == true) {
            $new_invoice_data['save_as_draft'] = true;
        }
        $new_invoice_data['clientid']   = $_inspection->clientid;
        $new_invoice_data['program_id'] = $_inspection->program_id;
        $new_invoice_data['number']     = get_option('next_invoice_number');
        $new_invoice_data['date']       = _d(date('Y-m-d'));
        $new_invoice_data['duedate']    = _d(date('Y-m-d'));
        if (get_option('invoice_due_after') != 0) {
            $new_invoice_data['duedate'] = _d(date('Y-m-d', strtotime('+' . get_option('invoice_due_after') . ' DAY', strtotime(date('Y-m-d')))));
        }
        $new_invoice_data['show_quantity_as'] = $_inspection->show_quantity_as;
        $new_invoice_data['currency']         = $_inspection->currency;
        $new_invoice_data['subtotal']         = $_inspection->subtotal;
        $new_invoice_data['total']            = $_inspection->total;
        $new_invoice_data['adjustment']       = $_inspection->adjustment;
        $new_invoice_data['discount_percent'] = $_inspection->discount_percent;
        $new_invoice_data['discount_total']   = $_inspection->discount_total;
        $new_invoice_data['discount_type']    = $_inspection->discount_type;
        $new_invoice_data['sale_agent']       = $_inspection->sale_agent;
        // Since version 1.0.6
        $new_invoice_data['billing_street']   = clear_textarea_breaks($_inspection->billing_street);
        $new_invoice_data['billing_city']     = $_inspection->billing_city;
        $new_invoice_data['billing_state']    = $_inspection->billing_state;
        $new_invoice_data['billing_zip']      = $_inspection->billing_zip;
        $new_invoice_data['billing_country']  = $_inspection->billing_country;
        $new_invoice_data['shipping_street']  = clear_textarea_breaks($_inspection->shipping_street);
        $new_invoice_data['shipping_city']    = $_inspection->shipping_city;
        $new_invoice_data['shipping_state']   = $_inspection->shipping_state;
        $new_invoice_data['shipping_zip']     = $_inspection->shipping_zip;
        $new_invoice_data['shipping_country'] = $_inspection->shipping_country;

        if ($_inspection->include_shipping == 1) {
            $new_invoice_data['include_shipping'] = 1;
        }

        $new_invoice_data['show_shipping_on_invoice'] = $_inspection->show_shipping_on_inspection;
        $new_invoice_data['terms']                    = get_option('predefined_terms_invoice');
        $new_invoice_data['clientnote']               = get_option('predefined_clientnote_invoice');
        // Set to unpaid status automatically
        $new_invoice_data['status']    = 1;
        $new_invoice_data['adminnote'] = '';

        $this->load->model('payment_modes_model');
        $modes = $this->payment_modes_model->get('', [
            'expenses_only !=' => 1,
        ]);
        $temp_modes = [];
        foreach ($modes as $mode) {
            if ($mode['selected_by_default'] == 0) {
                continue;
            }
            $temp_modes[] = $mode['id'];
        }
        $new_invoice_data['allowed_payment_modes'] = $temp_modes;
        $new_invoice_data['newitems']              = [];
        $custom_fields_items                       = get_custom_fields('items');
        $key                                       = 1;
        foreach ($_inspection->items as $item) {
            $new_invoice_data['newitems'][$key]['description']      = $item['description'];
            $new_invoice_data['newitems'][$key]['long_description'] = clear_textarea_breaks($item['long_description']);
            $new_invoice_data['newitems'][$key]['qty']              = $item['qty'];
            $new_invoice_data['newitems'][$key]['unit']             = $item['unit'];
            $new_invoice_data['newitems'][$key]['taxname']          = [];
            $taxes                                                  = get_inspection_item_taxes($item['id']);
            foreach ($taxes as $tax) {
                // tax name is in format TAX1|10.00
                array_push($new_invoice_data['newitems'][$key]['taxname'], $tax['taxname']);
            }
            $new_invoice_data['newitems'][$key]['rate']  = $item['rate'];
            $new_invoice_data['newitems'][$key]['order'] = $item['item_order'];
            foreach ($custom_fields_items as $cf) {
                $new_invoice_data['newitems'][$key]['custom_fields']['items'][$cf['id']] = get_custom_field_value($item['id'], $cf['id'], 'items', false);

                if (!defined('COPY_CUSTOM_FIELDS_LIKE_HANDLE_POST')) {
                    define('COPY_CUSTOM_FIELDS_LIKE_HANDLE_POST', true);
                }
            }
            $key++;
        }
        $this->load->model('invoices_model');
        $id = $this->invoices_model->add($new_invoice_data);
        if ($id) {
            // Customer accepted the inspection and is auto converted to invoice
            if (!is_staff_logged_in()) {
                $this->db->where('rel_type', 'invoice');
                $this->db->where('rel_id', $id);
                $this->db->delete(db_prefix() . 'sales_activity');
                $this->invoices_model->log_invoice_activity($id, 'invoice_activity_auto_converted_from_inspection', true, serialize([
                    '<a href="' . admin_url('inspections/list_inspections/' . $_inspection->id) . '">' . format_inspection_number($_inspection->id) . '</a>',
                ]));
            }
            // For all cases update addefrom and sale agent from the invoice
            // May happen staff is not logged in and these values to be 0
            $this->db->where('id', $id);
            $this->db->update(db_prefix() . 'invoices', [
                'addedfrom'  => $_inspection->addedfrom,
                'sale_agent' => $_inspection->sale_agent,
            ]);

            // Update inspection with the new invoice data and set to status accepted
            $this->db->where('id', $_inspection->id);
            $this->db->update(db_prefix() . 'inspections', [
                'invoiced_date' => date('Y-m-d H:i:s'),
                'invoiceid'     => $id,
                'status'        => 4,
            ]);


            if (is_custom_fields_smart_transfer_enabled()) {
                $this->db->where('fieldto', 'inspection');
                $this->db->where('active', 1);
                $cfInspections = $this->db->get(db_prefix() . 'customfields')->result_array();
                foreach ($cfInspections as $field) {
                    $tmpSlug = explode('_', $field['slug'], 2);
                    if (isset($tmpSlug[1])) {
                        $this->db->where('fieldto', 'invoice');

                        $this->db->group_start();
                        $this->db->like('slug', 'invoice_' . $tmpSlug[1], 'after');
                        $this->db->where('type', $field['type']);
                        $this->db->where('options', $field['options']);
                        $this->db->where('active', 1);
                        $this->db->group_end();

                        // $this->db->where('slug LIKE "invoice_' . $tmpSlug[1] . '%" AND type="' . $field['type'] . '" AND options="' . $field['options'] . '" AND active=1');
                        $cfTransfer = $this->db->get(db_prefix() . 'customfields')->result_array();

                        // Don't make mistakes
                        // Only valid if 1 result returned
                        // + if field names similarity is equal or more then CUSTOM_FIELD_TRANSFER_SIMILARITY%
                        if (count($cfTransfer) == 1 && ((similarity($field['name'], $cfTransfer[0]['name']) * 100) >= CUSTOM_FIELD_TRANSFER_SIMILARITY)) {
                            $value = get_custom_field_value($_inspection->id, $field['id'], 'inspection', false);

                            if ($value == '') {
                                continue;
                            }

                            $this->db->insert(db_prefix() . 'customfieldsvalues', [
                                'relid'   => $id,
                                'fieldid' => $cfTransfer[0]['id'],
                                'fieldto' => 'invoice',
                                'value'   => $value,
                            ]);
                        }
                    }
                }
            }

            if ($client == false) {
                $this->log_inspection_activity($_inspection->id, 'inspection_activity_converted', false, serialize([
                    '<a href="' . admin_url('invoices/list_invoices/' . $id) . '">' . format_invoice_number($id) . '</a>',
                ]));
            }

            hooks()->do_action('inspection_converted_to_invoice', ['invoice_id' => $id, 'inspection_id' => $_inspection->id]);
        }

        return $id;
    }

    /**
     * Copy inspection
     * @param mixed $id inspection id to copy
     * @return mixed
     */
    public function copy($id)
    {
        $_inspection                       = $this->get($id);
        $new_inspection_data               = [];
        $new_inspection_data['clientid']   = $_inspection->clientid;
        $new_inspection_data['program_id'] = $_inspection->program_id;
        $new_inspection_data['number']     = get_option('next_inspection_number');
        $new_inspection_data['date']       = _d(date('Y-m-d'));
        $new_inspection_data['expirydate'] = null;

        if ($_inspection->expirydate && get_option('inspection_due_after') != 0) {
            $new_inspection_data['expirydate'] = _d(date('Y-m-d', strtotime('+' . get_option('inspection_due_after') . ' DAY', strtotime(date('Y-m-d')))));
        }

        $new_inspection_data['show_quantity_as'] = $_inspection->show_quantity_as;
        //$new_inspection_data['currency']         = $_inspection->currency;
        //$new_inspection_data['subtotal']         = $_inspection->subtotal;
        //$new_inspection_data['total']            = $_inspection->total;
        $new_inspection_data['adminnote']        = $_inspection->adminnote;
        $new_inspection_data['adjustment']       = $_inspection->adjustment;
        //$new_inspection_data['discount_percent'] = $_inspection->discount_percent;
        //$new_inspection_data['discount_total']   = $_inspection->discount_total;
        //$new_inspection_data['discount_type']    = $_inspection->discount_type;
        $new_inspection_data['terms']            = $_inspection->terms;
        $new_inspection_data['sale_agent']       = $_inspection->sale_agent;
        $new_inspection_data['reference_no']     = $_inspection->reference_no;
        // Since version 1.0.6
        $new_inspection_data['billing_street']   = clear_textarea_breaks($_inspection->billing_street);
        $new_inspection_data['billing_city']     = $_inspection->billing_city;
        $new_inspection_data['billing_state']    = $_inspection->billing_state;
        $new_inspection_data['billing_zip']      = $_inspection->billing_zip;
        $new_inspection_data['billing_country']  = $_inspection->billing_country;
        $new_inspection_data['shipping_street']  = clear_textarea_breaks($_inspection->shipping_street);
        $new_inspection_data['shipping_city']    = $_inspection->shipping_city;
        $new_inspection_data['shipping_state']   = $_inspection->shipping_state;
        $new_inspection_data['shipping_zip']     = $_inspection->shipping_zip;
        $new_inspection_data['shipping_country'] = $_inspection->shipping_country;
        if ($_inspection->include_shipping == 1) {
            $new_inspection_data['include_shipping'] = $_inspection->include_shipping;
        }
        $new_inspection_data['show_shipping_on_inspection'] = $_inspection->show_shipping_on_inspection;
        // Set to unpaid status automatically
        $new_inspection_data['status']     = 1;
        $new_inspection_data['clientnote'] = $_inspection->clientnote;
        $new_inspection_data['adminnote']  = '';
        $new_inspection_data['newitems']   = [];

        //
        // get_rest_inspection_items here
        //

        echo '<pre>';
        var_dump($_inspection);
        echo '----------------- <br />';


        var_dump($_inspection->items);
        echo '</pre>';



        $id = $this->add($new_inspection_data);

        if ($id) {
            $key                             = 1;
            $_inspection->items = $this->get_rest_inspection_items($_inspection);
            
            foreach ($_inspection->items as $item) {
                $this->db->where('id', $item['id']);    
                $this->db->update(db_prefix() . 'program_items', [
                    'inspection_id'   => $id,
                ]);

                $key++;
            }

            log_activity('Copied Inspection ' . format_inspection_number($_inspection->id));

            return $id;
        }

        return false;
    }
    /**
     * Performs rest of program items for inspections 
     * @param array $data
     * @return array
     */
    public function get_rest_inspection_items($inspection)
    {
        $this->db->where('clientid',$inspection->clientid);
        $this->db->where('program_id',$inspection->program_id);
        $this->db->where('inspection_id', null);
        $items = $this->db->get(db_prefix(). 'program_items')->result_array();
        return $items;
    }


    /**
     * Performs inspections totals status
     * @param array $data
     * @return array
     */
    public function get_inspections_total($data)
    {
        $statuses            = $this->get_statuses();
        $has_permission_view = has_permission('inspections', '', 'view');
        $this->load->model('currencies_model');
        if (isset($data['currency'])) {
            $currencyid = $data['currency'];
        } elseif (isset($data['customer_id']) && $data['customer_id'] != '') {
            $currencyid = $this->clients_model->get_customer_default_currency($data['customer_id']);
            if ($currencyid == 0) {
                $currencyid = $this->currencies_model->get_base_currency()->id;
            }
        } elseif (isset($data['program_id']) && $data['program_id'] != '') {
            $this->load->model('projects_model');
            $currencyid = $this->projects_model->get_currency($data['program_id'])->id;
        } else {
            $currencyid = $this->currencies_model->get_base_currency()->id;
        }

        $currency = get_currency($currencyid);
        $where    = '';
        if (isset($data['customer_id']) && $data['customer_id'] != '') {
            $where = ' AND clientid=' . $data['customer_id'];
        }

        if (isset($data['program_id']) && $data['program_id'] != '') {
            $where .= ' AND program_id=' . $data['program_id'];
        }

        if (!$has_permission_view) {
            $where .= ' AND ' . get_inspections_where_sql_for_staff(get_staff_user_id());
        }

        $sql = 'SELECT';
        foreach ($statuses as $inspection_status) {
            $sql .= '(SELECT SUM(total) FROM ' . db_prefix() . 'inspections WHERE status=' . $inspection_status;
            $sql .= ' AND currency =' . $this->db->escape_str($currencyid);
            if (isset($data['years']) && count($data['years']) > 0) {
                $sql .= ' AND YEAR(date) IN (' . implode(', ', array_map(function ($year) {
                    return get_instance()->db->escape_str($year);
                }, $data['years'])) . ')';
            } else {
                $sql .= ' AND YEAR(date) = ' . date('Y');
            }
            $sql .= $where;
            $sql .= ') as "' . $inspection_status . '",';
        }

        $sql     = substr($sql, 0, -1);
        $result  = $this->db->query($sql)->result_array();
        $_result = [];
        $i       = 1;
        foreach ($result as $key => $val) {
            foreach ($val as $status => $total) {
                $_result[$i]['total']         = $total;
                $_result[$i]['symbol']        = $currency->symbol;
                $_result[$i]['currency_name'] = $currency->name;
                $_result[$i]['status']        = $status;
                $i++;
            }
        }
        $_result['currencyid'] = $currencyid;

        return $_result;
    }

    /**
     * Insert new inspection to database
     * @param array $data invoiec data
     * @return mixed - false if not insert, inspection ID if succes
     */
    public function add($data)
    {
        $data['datecreated'] = date('Y-m-d H:i:s');

        $data['addedfrom'] = get_staff_user_id();

        $data['prefix'] = get_option('inspection_prefix');

        $data['number_format'] = get_option('inspection_number_format');

        $save_and_send = isset($data['save_and_send']);

        $inspectionRequestID = false;
        if (isset($data['inspection_request_id'])) {
            $inspectionRequestID = $data['inspection_request_id'];
            unset($data['inspection_request_id']);
        }

        if (isset($data['custom_fields'])) {
            $custom_fields = $data['custom_fields'];
            unset($data['custom_fields']);
        }

        $data['hash'] = app_generate_hash();
        $tags         = isset($data['tags']) ? $data['tags'] : '';

        $items = [];
        if (isset($data['newitems'])) {
            $items = $data['newitems'];
            unset($data['newitems']);
        }

        $data = $this->map_shipping_columns($data);

        $data['billing_street'] = trim($data['billing_street']);
        $data['billing_street'] = nl2br($data['billing_street']);

        if (isset($data['shipping_street'])) {
            $data['shipping_street'] = trim($data['shipping_street']);
            $data['shipping_street'] = nl2br($data['shipping_street']);
        }

        $hook = hooks()->apply_filters('before_inspection_added', [
            'data'  => $data,
            'items' => $items,
        ]);

        $data  = $hook['data'];
        $items = $hook['items'];

        $this->db->insert(db_prefix() . 'inspections', $data);
        $insert_id = $this->db->insert_id();

        if ($insert_id) {
            // Update next inspection number in settings
            $this->db->where('name', 'next_inspection_number');
            $this->db->set('value', 'value+1', false);
            $this->db->update(db_prefix() . 'options');

            if ($inspectionRequestID !== false && $inspectionRequestID != '') {
                $this->load->model('inspection_request_model');
                $completedStatus = $this->inspection_request_model->get_status_by_flag('completed');
                $this->inspection_request_model->update_request_status([
                    'requestid' => $inspectionRequestID,
                    'status'    => $completedStatus->id,
                ]);
            }

            if (isset($custom_fields)) {
                handle_custom_fields_post($insert_id, $custom_fields);
            }

            handle_tags_save($tags, $insert_id, 'inspection');

            foreach ($items as $key => $item) {
                if ($itemid = add_new_sales_item_post($item, $insert_id, 'inspection')) {
                    _maybe_insert_post_item_tax($itemid, $item, $insert_id, 'inspection');
                }
            }

            update_sales_total_tax_column($insert_id, 'inspection', db_prefix() . 'inspections');
            $this->log_inspection_activity($insert_id, 'inspection_activity_created');

            hooks()->do_action('after_inspection_added', $insert_id);

            if ($save_and_send === true) {
                $this->send_inspection_to_client($insert_id, '', true, '', true);
            }

            return $insert_id;
        }

        return false;
    }

    /**
     * Get item by id
     * @param mixed $id item id
     * @return object
     */
    public function get_inspection_item($id)
    {
        $this->db->where('id', $id);

        return $this->db->get(db_prefix() . 'itemable')->row();
    }

    /**
     * Get item by id
     * @param mixed $id item id
     * @return object
     */
    public function get_inspection_items($id)
    {
        $this->db->where('id', $id);

        return $this->db->get(db_prefix() . 'program_items')->row();
    }
    /**
     * Get item by id
     * @param mixed $id item id
     * @return object
     */
    public function get_inspection_item_data($inspection_item_id, $jenis_pesawat)
    {
        $this->db->where('inspection_item_id', $inspection_item_id);
        return $this->db->get(db_prefix() . $jenis_pesawat)->row();
    }

    /**
     * Update inspection data
     * @param array $data inspection data
     * @param mixed $id inspectionid
     * @return boolean
     */
    public function update($data, $id)
    {
        $affectedRows = 0;

        $data['number'] = trim($data['number']);

        $original_inspection = $this->get($id);

        $original_status = $original_inspection->status;

        $original_number = $original_inspection->number;

        $original_number_formatted = format_inspection_number($id);

        $save_and_send = isset($data['save_and_send']);

        $items = [];
        if (isset($data['items'])) {
            $items = $data['items'];
            unset($data['items']);
        }

        $newitems = [];
        if (isset($data['newitems'])) {
            $newitems = $data['newitems'];
            unset($data['newitems']);
        }

        if (isset($data['custom_fields'])) {
            $custom_fields = $data['custom_fields'];
            if (handle_custom_fields_post($id, $custom_fields)) {
                $affectedRows++;
            }
            unset($data['custom_fields']);
        }

        if (isset($data['tags'])) {
            if (handle_tags_save($data['tags'], $id, 'inspection')) {
                $affectedRows++;
            }
        }

        $data['billing_street'] = trim($data['billing_street']);
        $data['billing_street'] = nl2br($data['billing_street']);

        $data['shipping_street'] = trim($data['shipping_street']);
        $data['shipping_street'] = nl2br($data['shipping_street']);

        $data = $this->map_shipping_columns($data);

        $hook = hooks()->apply_filters('before_inspection_updated', [
            'data'          => $data,
            'items'         => $items,
            'newitems'      => $newitems,
            'removed_items' => isset($data['removed_items']) ? $data['removed_items'] : [],
        ], $id);

        $data                  = $hook['data'];
        $items                 = $hook['items'];
        $newitems              = $hook['newitems'];
        $data['removed_items'] = $hook['removed_items'];

        // Delete items checked to be removed from database
        foreach ($data['removed_items'] as $remove_item_id) {
            $original_item = $this->get_inspection_item($remove_item_id);
            if (handle_removed_sales_item_post($remove_item_id, 'inspection')) {
                $affectedRows++;
                $this->log_inspection_activity($id, 'invoice_inspection_activity_removed_item', false, serialize([
                    $original_item->description,
                ]));
            }
        }

        unset($data['removed_items']);

        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'inspections', $data);

        if ($this->db->affected_rows() > 0) {
            // Check for status change
            if ($original_status != $data['status']) {
                $this->log_inspection_activity($original_inspection->id, 'not_inspection_status_updated', false, serialize([
                    '<original_status>' . $original_status . '</original_status>',
                    '<new_status>' . $data['status'] . '</new_status>',
                ]));
                if ($data['status'] == 2) {
                    $this->db->where('id', $id);
                    $this->db->update(db_prefix() . 'inspections', ['sent' => 1, 'datesend' => date('Y-m-d H:i:s')]);
                }
            }
            if ($original_number != $data['number']) {
                $this->log_inspection_activity($original_inspection->id, 'inspection_activity_number_changed', false, serialize([
                    $original_number_formatted,
                    format_inspection_number($original_inspection->id),
                ]));
            }
            $affectedRows++;
        }

        foreach ($items as $key => $item) {
            $original_item = $this->get_inspection_item($item['itemid']);

            if (update_sales_item_post($item['itemid'], $item, 'item_order')) {
                $affectedRows++;
            }

            if (update_sales_item_post($item['itemid'], $item, 'unit')) {
                $affectedRows++;
            }

            if (update_sales_item_post($item['itemid'], $item, 'rate')) {
                $this->log_inspection_activity($id, 'invoice_inspection_activity_updated_item_rate', false, serialize([
                    $original_item->rate,
                    $item['rate'],
                ]));
                $affectedRows++;
            }

            if (update_sales_item_post($item['itemid'], $item, 'qty')) {
                $this->log_inspection_activity($id, 'invoice_inspection_activity_updated_qty_item', false, serialize([
                    $item['description'],
                    $original_item->qty,
                    $item['qty'],
                ]));
                $affectedRows++;
            }

            if (update_sales_item_post($item['itemid'], $item, 'description')) {
                $this->log_inspection_activity($id, 'invoice_inspection_activity_updated_item_short_description', false, serialize([
                    $original_item->description,
                    $item['description'],
                ]));
                $affectedRows++;
            }

            if (update_sales_item_post($item['itemid'], $item, 'long_description')) {
                $this->log_inspection_activity($id, 'invoice_inspection_activity_updated_item_long_description', false, serialize([
                    $original_item->long_description,
                    $item['long_description'],
                ]));
                $affectedRows++;
            }

            if (isset($item['custom_fields'])) {
                if (handle_custom_fields_post($item['itemid'], $item['custom_fields'])) {
                    $affectedRows++;
                }
            }

            if (!isset($item['taxname']) || (isset($item['taxname']) && count($item['taxname']) == 0)) {
                if (delete_taxes_from_item($item['itemid'], 'inspection')) {
                    $affectedRows++;
                }
            } else {
                $item_taxes        = get_inspection_item_taxes($item['itemid']);
                $_item_taxes_names = [];
                foreach ($item_taxes as $_item_tax) {
                    array_push($_item_taxes_names, $_item_tax['taxname']);
                }

                $i = 0;
                foreach ($_item_taxes_names as $_item_tax) {
                    if (!in_array($_item_tax, $item['taxname'])) {
                        $this->db->where('id', $item_taxes[$i]['id'])
                            ->delete(db_prefix() . 'item_tax');
                        if ($this->db->affected_rows() > 0) {
                            $affectedRows++;
                        }
                    }
                    $i++;
                }
                if (_maybe_insert_post_item_tax($item['itemid'], $item, $id, 'inspection')) {
                    $affectedRows++;
                }
            }
        }

        foreach ($newitems as $key => $item) {
            if ($new_item_added = add_new_sales_item_post($item, $id, 'inspection')) {
                _maybe_insert_post_item_tax($new_item_added, $item, $id, 'inspection');
                $this->log_inspection_activity($id, 'invoice_inspection_activity_added_item', false, serialize([
                    $item['description'],
                ]));
                $affectedRows++;
            }
        }

        if ($affectedRows > 0) {
            update_sales_total_tax_column($id, 'inspection', db_prefix() . 'inspections');
        }

        if ($save_and_send === true) {
            $this->send_inspection_to_client($id, '', true, '', true);
        }

        if ($affectedRows > 0) {
            hooks()->do_action('after_inspection_updated', $id);

            return true;
        }

        return false;
    }

    public function mark_action_status($action, $id, $client = false)
    {
        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'inspections', [
            'status' => $action,
        ]);

        $notifiedUsers = [];

        if ($this->db->affected_rows() > 0) {
            $inspection = $this->get($id);
            if ($client == true) {
                $this->db->where('staffid', $inspection->addedfrom);
                $this->db->or_where('staffid', $inspection->sale_agent);
                $staff_inspection = $this->db->get(db_prefix() . 'staff')->result_array();

                $invoiceid = false;
                $invoiced  = false;

                $contact_id = !is_client_logged_in()
                    ? get_primary_contact_user_id($inspection->clientid)
                    : get_contact_user_id();

                if ($action == 4) {
                    if (get_option('inspection_auto_convert_to_invoice_on_client_accept') == 1) {
                        $invoiceid = $this->convert_to_invoice($id, true);
                        $this->load->model('invoices_model');
                        if ($invoiceid) {
                            $invoiced = true;
                            $invoice  = $this->invoices_model->get($invoiceid);
                            $this->log_inspection_activity($id, 'inspection_activity_client_accepted_and_converted', true, serialize([
                                '<a href="' . admin_url('invoices/list_invoices/' . $invoiceid) . '">' . format_invoice_number($invoice->id) . '</a>',
                            ]));
                        }
                    } else {
                        $this->log_inspection_activity($id, 'inspection_activity_client_accepted', true);
                    }

                    // Send thank you email to all contacts with permission inspections
                    $contacts = $this->clients_model->get_contacts($inspection->clientid, ['active' => 1, 'inspection_emails' => 1]);

                    foreach ($contacts as $contact) {
                        send_mail_template('inspection_accepted_to_customer', $inspection, $contact);
                    }

                    foreach ($staff_inspection as $member) {
                        $notified = add_notification([
                            'fromcompany'     => true,
                            'touserid'        => $member['staffid'],
                            'description'     => 'not_inspection_customer_accepted',
                            'link'            => 'inspections/list_inspections/' . $id,
                            'additional_data' => serialize([
                                format_inspection_number($inspection->id),
                            ]),
                        ]);

                        if ($notified) {
                            array_push($notifiedUsers, $member['staffid']);
                        }

                        send_mail_template('inspection_accepted_to_staff', $inspection, $member['email'], $contact_id);
                    }

                    pusher_trigger_notification($notifiedUsers);
                    hooks()->do_action('inspection_accepted', $id);

                    return [
                        'invoiced'  => $invoiced,
                        'invoiceid' => $invoiceid,
                    ];
                } elseif ($action == 3) {
                    foreach ($staff_inspection as $member) {
                        $notified = add_notification([
                            'fromcompany'     => true,
                            'touserid'        => $member['staffid'],
                            'description'     => 'not_inspection_customer_declined',
                            'link'            => 'inspections/list_inspections/' . $id,
                            'additional_data' => serialize([
                                format_inspection_number($inspection->id),
                            ]),
                        ]);

                        if ($notified) {
                            array_push($notifiedUsers, $member['staffid']);
                        }
                        // Send staff email notification that customer declined inspection
                        send_mail_template('inspection_declined_to_staff', $inspection, $member['email'], $contact_id);
                    }

                    pusher_trigger_notification($notifiedUsers);
                    $this->log_inspection_activity($id, 'inspection_activity_client_declined', true);
                    hooks()->do_action('inspection_declined', $id);

                    return [
                        'invoiced'  => $invoiced,
                        'invoiceid' => $invoiceid,
                    ];
                }
            } else {
                if ($action == 2) {
                    $this->db->where('id', $id);
                    $this->db->update(db_prefix() . 'inspections', ['sent' => 1, 'datesend' => date('Y-m-d H:i:s')]);
                }
                // Admin marked inspection
                $this->log_inspection_activity($id, 'inspection_activity_marked', false, serialize([
                    '<status>' . $action . '</status>',
                ]));

                return true;
            }
        }

        return false;
    }

    /**
     * Get inspection attachments
     * @param mixed $inspection_id
     * @param string $id attachment id
     * @return mixed
     */
    public function get_attachments($inspection_id, $id = '')
    {
        // If is passed id get return only 1 attachment
        if (is_numeric($id)) {
            $this->db->where('id', $id);
        } else {
            $this->db->where('rel_id', $inspection_id);
        }
        $this->db->where('rel_type', 'inspection');
        $result = $this->db->get(db_prefix() . 'files');
        if (is_numeric($id)) {
            return $result->row();
        }

        return $result->result_array();
    }

    /**
     *  Delete inspection attachment
     * @param mixed $id attachmentid
     * @return  boolean
     */
    public function delete_attachment($id)
    {
        $attachment = $this->get_attachments('', $id);
        $deleted    = false;
        if ($attachment) {
            if (empty($attachment->external)) {
                unlink(get_upload_path_by_type('inspection') . $attachment->rel_id . '/' . $attachment->file_name);
            }
            $this->db->where('id', $attachment->id);
            $this->db->delete(db_prefix() . 'files');
            if ($this->db->affected_rows() > 0) {
                $deleted = true;
                log_activity('Inspection Attachment Deleted [InspectionID: ' . $attachment->rel_id . ']');
            }

            if (is_dir(get_upload_path_by_type('inspection') . $attachment->rel_id)) {
                // Check if no attachments left, so we can delete the folder also
                $other_attachments = list_files(get_upload_path_by_type('inspection') . $attachment->rel_id);
                if (count($other_attachments) == 0) {
                    // okey only index.html so we can delete the folder also
                    delete_dir(get_upload_path_by_type('inspection') . $attachment->rel_id);
                }
            }
        }

        return $deleted;
    }

    /**
     * Delete inspection items and all connections
     * @param mixed $id inspectionid
     * @return boolean
     */
    public function delete($id, $simpleDelete = false)
    {
        if (get_option('delete_only_on_last_inspection') == 1 && $simpleDelete == false) {
            if (!is_last_inspection($id)) {
                return false;
            }
        }
        $inspection = $this->get($id);
        if (!is_null($inspection->invoiceid) && $simpleDelete == false) {
            return [
                'is_invoiced_inspection_delete_error' => true,
            ];
        }
        hooks()->do_action('before_inspection_deleted', $id);

        $number = format_inspection_number($id);

        $this->clear_signature($id);

        $this->db->where('id', $id);
        $this->db->delete(db_prefix() . 'inspections');

        if ($this->db->affected_rows() > 0) {
            if (!is_null($inspection->short_link)) {
                app_archive_short_link($inspection->short_link);
            }

            if (get_option('inspection_number_decrement_on_delete') == 1 && $simpleDelete == false) {
                $current_next_inspection_number = get_option('next_inspection_number');
                if ($current_next_inspection_number > 1) {
                    // Decrement next inspection number to
                    $this->db->where('name', 'next_inspection_number');
                    $this->db->set('value', 'value-1', false);
                    $this->db->update(db_prefix() . 'options');
                }
            }

            if (total_rows(db_prefix() . 'proposals', [
                    'inspection_id' => $id,
                ]) > 0) {
                $this->db->where('inspection_id', $id);
                $inspection = $this->db->get(db_prefix() . 'proposals')->row();
                $this->db->where('id', $inspection->id);
                $this->db->update(db_prefix() . 'proposals', [
                    'inspection_id'    => null,
                    'date_converted' => null,
                ]);
            }

            delete_tracked_emails($id, 'inspection');

            $this->db->where('relid IN (SELECT id from ' . db_prefix() . 'itemable WHERE rel_type="inspection" AND rel_id="' . $this->db->escape_str($id) . '")');
            $this->db->where('fieldto', 'items');
            $this->db->delete(db_prefix() . 'customfieldsvalues');

            $this->db->where('rel_id', $id);
            $this->db->where('rel_type', 'inspection');
            $this->db->delete(db_prefix() . 'notes');

            $this->db->where('rel_type', 'inspection');
            $this->db->where('rel_id', $id);
            $this->db->delete(db_prefix() . 'views_tracking');

            $this->db->where('rel_type', 'inspection');
            $this->db->where('rel_id', $id);
            $this->db->delete(db_prefix() . 'taggables');

            $this->db->where('rel_type', 'inspection');
            $this->db->where('rel_id', $id);
            $this->db->delete(db_prefix() . 'reminders');

            $this->db->where('rel_id', $id);
            $this->db->where('rel_type', 'inspection');
            $this->db->delete(db_prefix() . 'itemable');

            $this->db->where('rel_id', $id);
            $this->db->where('rel_type', 'inspection');
            $this->db->delete(db_prefix() . 'item_tax');

            $this->db->where('rel_id', $id);
            $this->db->where('rel_type', 'inspection');
            $this->db->delete(db_prefix() . 'sales_activity');

            // Delete the custom field values
            $this->db->where('relid', $id);
            $this->db->where('fieldto', 'inspection');
            $this->db->delete(db_prefix() . 'customfieldsvalues');

            $attachments = $this->get_attachments($id);
            foreach ($attachments as $attachment) {
                $this->delete_attachment($attachment['id']);
            }

            $this->db->where('rel_id', $id);
            $this->db->where('rel_type', 'inspection');
            $this->db->delete('inspectiond_emails');

            // Get related tasks
            $this->db->where('rel_type', 'inspection');
            $this->db->where('rel_id', $id);
            $tasks = $this->db->get(db_prefix() . 'tasks')->result_array();
            foreach ($tasks as $task) {
                $this->tasks_model->delete_task($task['id']);
            }
            if ($simpleDelete == false) {
                log_activity('Inspections Deleted [Number: ' . $number . ']');
            }

            return true;
        }

        return false;
    }

    /**
     * Set inspection to sent when email is successfuly sended to client
     * @param mixed $id inspectionid
     */
    public function set_inspection_sent($id, $emails_sent = [])
    {
        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'inspections', [
            'sent'     => 1,
            'datesend' => date('Y-m-d H:i:s'),
        ]);

        $this->log_inspection_activity($id, 'invoice_inspection_activity_sent_to_client', false, serialize([
            '<custom_data>' . implode(', ', $emails_sent) . '</custom_data>',
        ]));

        // Update inspection status to sent
        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'inspections', [
            'status' => 2,
        ]);

        $this->db->where('rel_id', $id);
        $this->db->where('rel_type', 'inspection');
        $this->db->delete('inspectiond_emails');
    }

    /**
     * Send expiration reminder to customer
     * @param mixed $id inspection id
     * @return boolean
     */
    public function send_expiry_reminder($id)
    {
        $inspection        = $this->get($id);
        $inspection_number = format_inspection_number($inspection->id);
        set_mailing_constant();
        $pdf              = inspection_pdf($inspection);
        $attach           = $pdf->Output($inspection_number . '.pdf', 'S');
        $emails_sent      = [];
        $sms_sent         = false;
        $sms_reminder_log = [];

        // For all cases update this to prevent sending multiple reminders eq on fail
        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'inspections', [
            'is_expiry_notified' => 1,
        ]);

        $contacts = $this->clients_model->get_contacts($inspection->clientid, ['active' => 1, 'inspection_emails' => 1]);

        foreach ($contacts as $contact) {
            $template = mail_template('inspection_expiration_reminder', $inspection, $contact);

            $merge_fields = $template->get_merge_fields();

            $template->add_attachment([
                'attachment' => $attach,
                'filename'   => str_replace('/', '-', $inspection_number . '.pdf'),
                'type'       => 'application/pdf',
            ]);

            if ($template->send()) {
                array_push($emails_sent, $contact['email']);
            }

            if (can_send_sms_based_on_creation_date($inspection->datecreated)
                && $this->app_sms->trigger(SMS_TRIGGER_ESTIMATE_EXP_REMINDER, $contact['phonenumber'], $merge_fields)) {
                $sms_sent = true;
                array_push($sms_reminder_log, $contact['firstname'] . ' (' . $contact['phonenumber'] . ')');
            }
        }

        if (count($emails_sent) > 0 || $sms_sent) {
            if (count($emails_sent) > 0) {
                $this->log_inspection_activity($id, 'not_expiry_reminder_sent', false, serialize([
                    '<custom_data>' . implode(', ', $emails_sent) . '</custom_data>',
                ]));
            }

            if ($sms_sent) {
                $this->log_inspection_activity($id, 'sms_reminder_sent_to', false, serialize([
                    implode(', ', $sms_reminder_log),
                ]));
            }

            return true;
        }

        return false;
    }

    /**
     * Send inspection to client
     * @param mixed $id inspectionid
     * @param string $template email template to sent
     * @param boolean $attachpdf attach inspection pdf or not
     * @return boolean
     */
    public function send_inspection_to_client($id, $template_name = '', $attachpdf = true, $cc = '', $manually = false)
    {
        $inspection = $this->get($id);

        if ($template_name == '') {
            $template_name = $inspection->sent == 0 ?
                'inspection_send_to_customer' :
                'inspection_send_to_customer_already_sent';
        }

        $inspection_number = format_inspection_number($inspection->id);

        $emails_sent = [];
        $send_to     = [];

        // Manually is used when sending the inspection via add/edit area button Save & Send
        if (!DEFINED('CRON') && $manually === false) {
            $send_to = $this->input->post('sent_to');
        } elseif (isset($GLOBALS['inspectiond_email_contacts'])) {
            $send_to = $GLOBALS['inspectiond_email_contacts'];
        } else {
            $contacts = $this->clients_model->get_contacts(
                $inspection->clientid,
                ['active' => 1, 'inspection_emails' => 1]
            );

            foreach ($contacts as $contact) {
                array_push($send_to, $contact['id']);
            }
        }

        $status_auto_updated = false;
        $status_now          = $inspection->status;

        if (is_array($send_to) && count($send_to) > 0) {
            $i = 0;

            // Auto update status to sent in case when user sends the inspection is with status draft
            if ($status_now == 1) {
                $this->db->where('id', $inspection->id);
                $this->db->update(db_prefix() . 'inspections', [
                    'status' => 2,
                ]);
                $status_auto_updated = true;
            }

            if ($attachpdf) {
                $_pdf_inspection = $this->get($inspection->id);
                set_mailing_constant();
                $pdf = inspection_pdf($_pdf_inspection);

                $attach = $pdf->Output($inspection_number . '.pdf', 'S');
            }

            foreach ($send_to as $contact_id) {
                if ($contact_id != '') {
                    // Send cc only for the first contact
                    if (!empty($cc) && $i > 0) {
                        $cc = '';
                    }

                    $contact = $this->clients_model->get_contact($contact_id);

                    if (!$contact) {
                        continue;
                    }

                    $template = mail_template($template_name, $inspection, $contact, $cc);

                    if ($attachpdf) {
                        $hook = hooks()->apply_filters('send_inspection_to_customer_file_name', [
                            'file_name' => str_replace('/', '-', $inspection_number . '.pdf'),
                            'inspection'  => $_pdf_inspection,
                        ]);

                        $template->add_attachment([
                            'attachment' => $attach,
                            'filename'   => $hook['file_name'],
                            'type'       => 'application/pdf',
                        ]);
                    }

                    if ($template->send()) {
                        array_push($emails_sent, $contact->email);
                    }
                }
                $i++;
            }
        } else {
            return false;
        }

        if (count($emails_sent) > 0) {
            $this->set_inspection_sent($id, $emails_sent);
            hooks()->do_action('inspection_sent', $id);

            return true;
        }

        if ($status_auto_updated) {
            // Inspection not send to customer but the status was previously updated to sent now we need to revert back to draft
            $this->db->where('id', $inspection->id);
            $this->db->update(db_prefix() . 'inspections', [
                'status' => 1,
            ]);
        }

        return false;
    }

    /**
     * All inspection activity
     * @param mixed $id inspectionid
     * @return array
     */
    public function get_inspection_activity($id)
    {
        $this->db->where('rel_id', $id);
        $this->db->where('rel_type', 'inspection');
        $this->db->order_by('date', 'desc');

        return $this->db->get(db_prefix() . 'sales_activity')->result_array();
    }

    /**
     * Log inspection activity to database
     * @param mixed $id inspectionid
     * @param string $description activity description
     */
    public function log_inspection_activity($id, $description = '', $client = false, $additional_data = '')
    {
        $staffid   = get_staff_user_id();
        $full_name = get_staff_full_name(get_staff_user_id());
        if (DEFINED('CRON')) {
            $staffid   = '[CRON]';
            $full_name = '[CRON]';
        } elseif ($client == true) {
            $staffid   = null;
            $full_name = '';
        }

        $this->db->insert(db_prefix() . 'sales_activity', [
            'description'     => $description,
            'date'            => date('Y-m-d H:i:s'),
            'rel_id'          => $id,
            'rel_type'        => 'inspection',
            'staffid'         => $staffid,
            'full_name'       => $full_name,
            'additional_data' => $additional_data,
        ]);
    }

    /**
     * Updates pipeline order when drag and drop
     * @param mixe $data $_POST data
     * @return void
     */
    public function update_pipeline($data)
    {
        $this->mark_action_status($data['status'], $data['inspectionid']);
        AbstractKanban::updateOrder($data['order'], 'pipeline_order', 'inspections', $data['status']);
    }

    /**
     * Get inspection unique year for filtering
     * @return array
     */
    public function get_inspections_years()
    {
        return $this->db->query('SELECT DISTINCT(YEAR(date)) as year FROM ' . db_prefix() . 'inspections ORDER BY year DESC')->result_array();
    }

    private function map_shipping_columns($data)
    {
        if (!isset($data['include_shipping'])) {
            foreach ($this->shipping_fields as $_s_field) {
                if (isset($data[$_s_field])) {
                    $data[$_s_field] = null;
                }
            }
            $data['show_shipping_on_inspection'] = 1;
            $data['include_shipping']          = 0;
        } else {
            $data['include_shipping'] = 1;
            // set by default for the next time to be checked
            if (isset($data['show_shipping_on_inspection']) && ($data['show_shipping_on_inspection'] == 1 || $data['show_shipping_on_inspection'] == 'on')) {
                $data['show_shipping_on_inspection'] = 1;
            } else {
                $data['show_shipping_on_inspection'] = 0;
            }
        }

        return $data;
    }

    public function do_kanban_query($status, $search = '', $page = 1, $sort = [], $count = false)
    {
        _deprecated_function('Inspections_model::do_kanban_query', '2.9.2', 'InspectionsPipeline class');

        $kanBan = (new InspectionsPipeline($status))
            ->search($search)
            ->page($page)
            ->sortBy($sort['sort'] ?? null, $sort['sort_by'] ?? null);

        if ($count) {
            return $kanBan->countAll();
        }

        return $kanBan->get();
    }

    public function inspections_add_inspection_item($data){
        $data['inspectionedfrom'] = get_staff_user_id();
        $this->db->set('inspection_id', $data['inspection_id']);
        $this->db->where('id', $data['id']);
        $this->db->update(db_prefix() . 'program_items', $data);
    }


    public function inspections_remove_inspection_item($data){
        $this->db->set('inspection_id', null);
        $this->db->where('id', $data['id']);
        $this->db->update(db_prefix() . 'program_items', $data);
    }


    /**
     * Insert new inspection to database
     * @param array $data invoiec data
     * @return mixed - false if not insert, inspection ID if succes
     */
    public function add_inspection_item_data($data, $jenis_pesawat)
    {
        $data['datecreated'] = date('Y-m-d H:i:s');

        $data['addedfrom'] = get_staff_user_id();

        $save_and_send = isset($data['save_and_send']);


        $this->db->insert(db_prefix() . $jenis_pesawat, $data);
        $insert_id = $this->db->insert_id();

        if ($insert_id) {
            // Update next inspection number in settings
            $this->db->where('name', 'next_inspection_number');
            $this->db->set('value', 'value+1', false);
            $this->db->update(db_prefix() . 'options');


            $this->log_inspection_activity($insert_id, 'inspection_item_data_activity_created');

            hooks()->do_action('after_inspection_item_added', $insert_id);

            return $insert_id;
        }

        return false;
    }

    /**
     * Update inspection data
     * @param array $data inspection data
     * @param mixed $id inspectionid
     * @return boolean
     */
    public function update_inspection_item_data($data, $jenis_pesawat, $id)
    {
        $affectedRows = 0;

        $origin = $this->get_inspection_item_data($id, $jenis_pesawat);

        $save_and_send = isset($data['save_and_send']);
        $data['removed_items'] = ['remove']; 
        // Delete items checked to be removed from database
        foreach ($data['removed_items'] as $remove_item_id) {
            $original_item = $this->get_inspection_item($remove_item_id);
            if (handle_removed_sales_item_post($remove_item_id, 'inspection')) {
                $affectedRows++;
                $this->log_inspection_activity($id, 'invoice_inspection_activity_removed_item', false, serialize([
                    $original_item->description,
                ]));
            }
        }

        unset($data['removed_items']);

        $this->db->where('id', $id);
        $this->db->update(db_prefix() . $jenis_pesawat, $data);

        if ($this->db->affected_rows() > 0) {
            // Check for status change
            if ($original_status != $data['status']) {
                $this->log_inspection_activity($origin->id, 'not_inspection_status_updated', false, serialize([
                    '<original_status>' . $original_status . '</original_status>',
                    '<new_status>' . $data['status'] . '</new_status>',
                ]));
                if ($data['status'] == 2) {
                    $this->db->where('id', $id);
                    $this->db->update(db_prefix() . 'inspections', ['sent' => 1, 'datesend' => date('Y-m-d H:i:s')]);
                }
            }
            if ($original_number != $data['number']) {
                $this->log_inspection_activity($origin->id, 'inspection_activity_number_changed', false, serialize([
                    $original_number_formatted,
                    format_inspection_number($origin->id),
                ]));
            }
            $affectedRows++;
        }

        if ($affectedRows > 0) {
            hooks()->do_action('after_inspection_item_data_updated', $id);

            return true;
        }

        return false;
    }


}

