<?php

use app\services\inspections\InspectionsPipeline;

defined('BASEPATH') or exit('No direct script access allowed');

class Inspections extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('inspections_model');
    }

    /* Get all inspections in case user go on index page */
    public function index($id = '')
    {
        $this->list_inspections($id);
    }

    /* List all inspections datatables */
    public function list_inspections($id = '')
    {
        if (!has_permission('inspections', '', 'view') && !has_permission('inspections', '', 'view_own') && get_option('allow_staff_view_inspections_assigned') == '0') {
            access_denied('inspections');
        }

        $isPipeline = $this->session->userdata('inspection_pipeline') == 'true';

        $data['inspection_statuses'] = $this->inspections_model->get_statuses();
        if ($isPipeline && !$this->input->get('status') && !$this->input->get('filter')) {
            $data['title']           = _l('inspections_pipeline');
            $data['bodyclass']       = 'inspections-pipeline inspections-total-manual';
            $data['switch_pipeline'] = false;

            if (is_numeric($id)) {
                $data['inspectionid'] = $id;
            } else {
                $data['inspectionid'] = $this->session->flashdata('inspectionid');
            }

            $this->load->view('admin/inspections/pipeline/manage', $data);
        } else {

            // Pipeline was initiated but user click from home page and need to show table only to filter
            if ($this->input->get('status') || $this->input->get('filter') && $isPipeline) {
                $this->pipeline(0, true);
            }

            $data['inspectionid']            = $id;
            $data['switch_pipeline']       = true;
            $data['title']                 = _l('inspections');
            $data['bodyclass']             = 'inspections-total-manual';
            $data['inspections_years']       = $this->inspections_model->get_inspections_years();
            $data['inspections_sale_agents'] = $this->inspections_model->get_sale_agents();
            if($id){
                $this->load->view('admin/inspections/manage_small_table', $data);

            }else{
                $this->load->view('admin/inspections/manage_table', $data);

            }

        }
    }

    public function table($clientid = '')
    {
        if (!has_permission('inspections', '', 'view') && !has_permission('inspections', '', 'view_own') && get_option('allow_staff_view_inspections_assigned') == '0') {
            ajax_access_denied();
        }
        $this->app->get_table_data(module_views_path('inspections', 'admin/tables/table',[
            'clientid' => $clientid,
        ]));
    }

    /* Add new inspection or update existing */
    public function inspection($id = '')
    {
        if ($this->input->post()) {
            $inspection_data = $this->input->post();

            $save_and_send_later = false;
            if (isset($inspection_data['save_and_send_later'])) {
                unset($inspection_data['save_and_send_later']);
                $save_and_send_later = true;
            }

            if ($id == '') {
                if (!has_permission('inspections', '', 'create')) {
                    access_denied('inspections');
                }
                $id = $this->inspections_model->add($inspection_data);

                if ($id) {
                    set_alert('success', _l('added_successfully', _l('inspection')));

                    $redUrl = admin_url('inspections/list_inspections/' . $id);

                    if ($save_and_send_later) {
                        $this->session->set_userdata('send_later', true);
                        // die(redirect($redUrl));
                    }

                    redirect(
                        !$this->set_inspection_pipeline_autoload($id) ? $redUrl : admin_url('inspections/list_inspections/')
                    );
                }
            } else {
                if (!has_permission('inspections', '', 'edit')) {
                    access_denied('inspections');
                }
                $success = $this->inspections_model->update($inspection_data, $id);
                if ($success) {
                    set_alert('success', _l('updated_successfully', _l('inspection')));
                }
                if ($this->set_inspection_pipeline_autoload($id)) {
                    redirect(admin_url('inspections/list_inspections/'));
                } else {
                    redirect(admin_url('inspections/list_inspections/' . $id));
                }
            }
        }
        if ($id == '') {
            $title = _l('create_new_inspection');
        } else {
            $inspection = $this->inspections_model->get($id);

            if (!$inspection || !user_can_view_inspection($id)) {
                blank_page(_l('inspection_not_found'));
            }

            $data['inspection'] = $inspection;
            $data['edit']     = true;
            $title            = _l('edit', _l('inspection_lowercase'));
        }

        if ($this->input->get('customer_id')) {
            $data['customer_id'] = $this->input->get('customer_id');
        }

        if ($this->input->get('inspection_request_id')) {
            $data['inspection_request_id'] = $this->input->get('inspection_request_id');
        }

        $this->load->model('taxes_model');
        $data['taxes'] = $this->taxes_model->get();
        $this->load->model('currencies_model');
        $data['currencies'] = $this->currencies_model->get();

        $data['base_currency'] = $this->currencies_model->get_base_currency();

        $this->load->model('invoice_items_model');

        $data['ajaxItems'] = false;
        if (total_rows(db_prefix() . 'items') <= ajax_on_total_items()) {
            $data['items'] = $this->invoice_items_model->get_grouped();
        } else {
            $data['items']     = [];
            $data['ajaxItems'] = true;
        }
        $data['items_groups'] = $this->invoice_items_model->get_groups();

        $data['staff']             = $this->staff_model->get('', ['active' => 1]);
        $data['inspection_statuses'] = $this->inspections_model->get_statuses();
        $data['title']             = $title;
//        $this->load->view(module_views_path('inspections','admin/inspections/inspection'), $data);
        $this->load->view('admin/inspections/inspection', $data);
    }
    

    public function get_program_items_table($clientid, $program_id, $id)
    {
        if ($this->input->is_ajax_request()) {
            $this->app->get_table_data(module_views_path('inspections', 'admin/tables/program_items_table'), [
                'clientid' => $clientid,
                'program_id' => $program_id,
                'inspection_id' => $id,
            ]);
        }
    }

    public function get_inspection_items_table($clientid, $program_id, $id)
    {
        if ($this->input->is_ajax_request()) {
            $this->app->get_table_data(module_views_path('inspections', 'admin/tables/inspection_items_table'), [
                'clientid' => $clientid,
                'program_id' => $program_id,
                'inspection_id' => $id,
            ]);
        }
    }

    public function clear_signature($id)
    {
        if (has_permission('inspections', '', 'delete')) {
            $this->inspections_model->clear_signature($id);
        }

        redirect(admin_url('inspections/list_inspections/' . $id));
    }

    public function update_number_settings($id)
    {
        $response = [
            'success' => false,
            'message' => '',
        ];
        if (has_permission('inspections', '', 'edit')) {
            $this->db->where('id', $id);
            $this->db->update(db_prefix() . 'inspections', [
                'prefix' => $this->input->post('prefix'),
            ]);
            if ($this->db->affected_rows() > 0) {
                $response['success'] = true;
                $response['message'] = _l('updated_successfully', _l('inspection'));
            }
        }

        echo json_encode($response);
        die;
    }

    public function validate_inspection_number()
    {
        $isedit          = $this->input->post('isedit');
        $number          = $this->input->post('number');
        $date            = $this->input->post('date');
        $original_number = $this->input->post('original_number');
        $number          = trim($number);
        $number          = ltrim($number, '0');

        if ($isedit == 'true') {
            if ($number == $original_number) {
                echo json_encode(true);
                die;
            }
        }

        if (total_rows(db_prefix() . 'inspections', [
            'YEAR(date)' => date('Y', strtotime(to_sql_date($date))),
            'number' => $number,
        ]) > 0) {
            echo 'false';
        } else {
            echo 'true';
        }
    }

    public function delete_attachment($id)
    {
        $file = $this->misc_model->get_file($id);
        if ($file->staffid == get_staff_user_id() || is_admin()) {
            echo $this->inspections_model->delete_attachment($id);
        } else {
            header('HTTP/1.0 400 Bad error');
            echo _l('access_denied');
            die;
        }
    }

    /* Get all inspection data used when user click on inspection number in a datatable left side*/
    public function get_inspection_data_ajax($id, $to_return = false)
    {
        if (!has_permission('inspections', '', 'view') && !has_permission('inspections', '', 'view_own') && get_option('allow_staff_view_inspections_assigned') == '0') {
            echo _l('access_denied');
            die;
        }

        if (!$id) {
            die('No inspection found');
        }

        $inspection = $this->inspections_model->get($id);

        if (!$inspection || !user_can_view_inspection($id)) {
            echo _l('inspection_not_found');
            die;
        }

        $inspection->date       = _d($inspection->date);
        $inspection->expirydate = _d($inspection->expirydate);
        if ($inspection->invoiceid !== null) {
            $this->load->model('invoices_model');
            $inspection->invoice = $this->invoices_model->get($inspection->invoiceid);
        }

        if ($inspection->sent == 0) {
            $template_name = 'inspection_send_to_customer';
        } else {
            $template_name = 'inspection_send_to_customer_already_sent';
        }

        $data = prepare_mail_preview_data($template_name, $inspection->clientid);
        include_once(FCPATH . 'modules/programs/models/programs_model.php');
        $this->load->model('programs_model');
        $program = $this->programs_model->get($inspection->program_id);

        $data['activity']          = $this->inspections_model->get_inspection_activity($id);
        $data['inspection']          = $inspection;
        $data['program']          = $program;
        $data['members']           = $this->staff_model->get('', ['active' => 1]);
        $data['inspection_statuses'] = $this->inspections_model->get_statuses();
        $data['totalNotes']        = total_rows(db_prefix() . 'notes', ['rel_id' => $id, 'rel_type' => 'inspection']);

        $data['send_later'] = false;
        if ($this->session->has_userdata('send_later')) {
            $data['send_later'] = true;
            $this->session->unset_userdata('send_later');
        }

        if ($to_return == false) {
            $this->load->view('admin/inspections/inspection_preview_template', $data);
        } else {
            return $this->load->view('admin/inspections/inspection_preview_template', $data, true);
        }
    }

    public function get_inspections_total()
    {
        if ($this->input->post()) {
            $data['totals'] = $this->inspections_model->get_inspections_total($this->input->post());

            $this->load->model('currencies_model');

            if (!$this->input->post('customer_id')) {
                $multiple_currencies = call_user_func('is_using_multiple_currencies', db_prefix() . 'inspections');
            } else {
                $multiple_currencies = call_user_func('is_client_using_multiple_currencies', $this->input->post('customer_id'), db_prefix() . 'inspections');
            }

            if ($multiple_currencies) {
                $data['currencies'] = $this->currencies_model->get();
            }

            $data['inspections_years'] = $this->inspections_model->get_inspections_years();

            if (
                count($data['inspections_years']) >= 1
                && !\app\services\utilities\Arr::inMultidimensional($data['inspections_years'], 'year', date('Y'))
            ) {
                array_unshift($data['inspections_years'], ['year' => date('Y')]);
            }

            $data['_currency'] = $data['totals']['currencyid'];
            unset($data['totals']['currencyid']);
            $this->load->view('admin/inspections/inspections_total_template', $data);
        }
    }

    public function add_note($rel_id)
    {
        if ($this->input->post() && user_can_view_inspection($rel_id)) {
            $this->misc_model->add_note($this->input->post(), 'inspection', $rel_id);
            echo $rel_id;
        }
    }

    public function get_notes($id)
    {
        if (user_can_view_inspection($id)) {
            $data['notes'] = $this->misc_model->get_notes($id, 'inspection');
            $this->load->view('admin/includes/sales_notes_template', $data);
        }
    }

    public function mark_action_status($status, $id)
    {
        if (!has_permission('inspections', '', 'edit')) {
            access_denied('inspections');
        }
        $success = $this->inspections_model->mark_action_status($status, $id);
        if ($success) {
            set_alert('success', _l('inspection_status_changed_success'));
        } else {
            set_alert('danger', _l('inspection_status_changed_fail'));
        }
        if ($this->set_inspection_pipeline_autoload($id)) {
            redirect($_SERVER['HTTP_REFERER']);
        } else {
            redirect(admin_url('inspections/list_inspections/' . $id));
        }
    }

    public function send_expiry_reminder($id)
    {
        $canView = user_can_view_inspection($id);
        if (!$canView) {
            access_denied('Inspections');
        } else {
            if (!has_permission('inspections', '', 'view') && !has_permission('inspections', '', 'view_own') && $canView == false) {
                access_denied('Inspections');
            }
        }

        $success = $this->inspections_model->send_expiry_reminder($id);
        if ($success) {
            set_alert('success', _l('sent_expiry_reminder_success'));
        } else {
            set_alert('danger', _l('sent_expiry_reminder_fail'));
        }
        if ($this->set_inspection_pipeline_autoload($id)) {
            redirect($_SERVER['HTTP_REFERER']);
        } else {
            redirect(admin_url('inspections/list_inspections/' . $id));
        }
    }

    /* Send inspection to email */
    public function send_to_email($id)
    {
        $canView = user_can_view_inspection($id);
        if (!$canView) {
            access_denied('inspections');
        } else {
            if (!has_permission('inspections', '', 'view') && !has_permission('inspections', '', 'view_own') && $canView == false) {
                access_denied('inspections');
            }
        }

        try {
            $success = $this->inspections_model->send_inspection_to_client($id, '', $this->input->post('attach_pdf'), $this->input->post('cc'));
        } catch (Exception $e) {
            $message = $e->getMessage();
            echo $message;
            if (strpos($message, 'Unable to get the size of the image') !== false) {
                show_pdf_unable_to_get_image_size_error();
            }
            die;
        }

        // In case client use another language
        load_admin_language();
        if ($success) {
            set_alert('success', _l('inspection_sent_to_client_success'));
        } else {
            set_alert('danger', _l('inspection_sent_to_client_fail'));
        }
        if ($this->set_inspection_pipeline_autoload($id)) {
            redirect($_SERVER['HTTP_REFERER']);
        } else {
            redirect(admin_url('inspections/list_inspections/' . $id));
        }
    }

    /* Convert inspection to invoice */
    public function convert_to_invoice($id)
    {
        if (!has_permission('invoices', '', 'create')) {
            access_denied('invoices');
        }
        if (!$id) {
            die('No inspection found');
        }
        $draft_invoice = false;
        if ($this->input->get('save_as_draft')) {
            $draft_invoice = true;
        }
        $invoiceid = $this->inspections_model->convert_to_invoice($id, false, $draft_invoice);
        if ($invoiceid) {
            set_alert('success', _l('inspection_convert_to_invoice_successfully'));
            redirect(admin_url('invoices/list_invoices/' . $invoiceid));
        } else {
            if ($this->session->has_userdata('inspection_pipeline') && $this->session->userdata('inspection_pipeline') == 'true') {
                $this->session->set_flashdata('inspectionid', $id);
            }
            if ($this->set_inspection_pipeline_autoload($id)) {
                redirect($_SERVER['HTTP_REFERER']);
            } else {
                redirect(admin_url('inspections/list_inspections/' . $id));
            }
        }
    }

    public function copy($id)
    {
        if (!has_permission('inspections', '', 'create')) {
            access_denied('inspections');
        }
        if (!$id) {
            die('No inspection found');
        }
        $new_id = $this->inspections_model->copy($id);
        if ($new_id) {
            set_alert('success', _l('inspection_copied_successfully'));
            if ($this->set_inspection_pipeline_autoload($new_id)) {
                redirect($_SERVER['HTTP_REFERER']);
            } else {
                redirect(admin_url('inspections/inspection/' . $new_id));
            }
        }
        set_alert('danger', _l('inspection_copied_fail'));
        if ($this->set_inspection_pipeline_autoload($id)) {
            redirect($_SERVER['HTTP_REFERER']);
        } else {
            redirect(admin_url('inspections/inspection/' . $id));
        }
    }

    /* Delete inspection */
    public function delete($id)
    {
        if (!has_permission('inspections', '', 'delete')) {
            access_denied('inspections');
        }
        if (!$id) {
            redirect(admin_url('inspections/list_inspections'));
        }
        $success = $this->inspections_model->delete($id);
        if (is_array($success)) {
            set_alert('warning', _l('is_invoiced_inspection_delete_error'));
        } elseif ($success == true) {
            set_alert('success', _l('deleted', _l('inspection')));
        } else {
            set_alert('warning', _l('problem_deleting', _l('inspection_lowercase')));
        }
        redirect(admin_url('inspections/list_inspections'));
    }

    public function clear_acceptance_info($id)
    {
        if (is_admin()) {
            $this->db->where('id', $id);
            $this->db->update(db_prefix() . 'inspections', get_acceptance_info_array(true));
        }

        redirect(admin_url('inspections/list_inspections/' . $id));
    }

    /* Generates inspection PDF and senting to email  */
    public function pdf($id)
    {
        $canView = user_can_view_inspection($id);
        if (!$canView) {
            access_denied('Inspections');
        } else {
            if (!has_permission('inspections', '', 'view') && !has_permission('inspections', '', 'view_own') && $canView == false) {
                access_denied('Inspections');
            }
        }
        if (!$id) {
            redirect(admin_url('inspections/list_inspections'));
        }
        $inspection        = $this->inspections_model->get($id);
        $inspection_number = format_inspection_number($inspection->id);

        try {
            $pdf = inspection_pdf($inspection);
        } catch (Exception $e) {
            $message = $e->getMessage();
            echo $message;
            if (strpos($message, 'Unable to get the size of the image') !== false) {
                show_pdf_unable_to_get_image_size_error();
            }
            die;
        }

        $type = 'D';

        if ($this->input->get('output_type')) {
            $type = $this->input->get('output_type');
        }

        if ($this->input->get('print')) {
            $type = 'I';
        }

        $fileNameHookData = hooks()->apply_filters('inspection_file_name_admin_area', [
                            'file_name' => mb_strtoupper(slug_it($inspection_number)) . '.pdf',
                            'inspection'  => $inspection,
                        ]);

        $pdf->Output($fileNameHookData['file_name'], $type);
    }

    // Pipeline
    public function get_pipeline()
    {
        if (has_permission('inspections', '', 'view') || has_permission('inspections', '', 'view_own') || get_option('allow_staff_view_inspections_assigned') == '1') {
            $data['inspection_statuses'] = $this->inspections_model->get_statuses();
            $this->load->view('admin/inspections/pipeline/pipeline', $data);
        }
    }

    public function pipeline_open($id)
    {
        $canView = user_can_view_inspection($id);
        if (!$canView) {
            access_denied('Inspections');
        } else {
            if (!has_permission('inspections', '', 'view') && !has_permission('inspections', '', 'view_own') && $canView == false) {
                access_denied('Inspections');
            }
        }

        $data['id']       = $id;
        $data['inspection'] = $this->get_inspection_data_ajax($id, true);
        $this->load->view('admin/inspections/pipeline/inspection', $data);
    }

    public function update_pipeline()
    {
        if (has_permission('inspections', '', 'edit')) {
            $this->inspections_model->update_pipeline($this->input->post());
        }
    }

    public function pipeline($set = 0, $manual = false)
    {
        if ($set == 1) {
            $set = 'true';
        } else {
            $set = 'false';
        }
        $this->session->set_userdata([
            'inspection_pipeline' => $set,
        ]);
        if ($manual == false) {
            redirect(admin_url('inspections/list_inspections'));
        }
    }

    public function pipeline_load_more()
    {
        $status = $this->input->get('status');
        $page   = $this->input->get('page');

        $inspections = (new InspectionsPipeline($status))
            ->search($this->input->get('search'))
            ->sortBy(
                $this->input->get('sort_by'),
                $this->input->get('sort')
            )
            ->page($page)->get();

        foreach ($inspections as $inspection) {
            $this->load->view('admin/inspections/pipeline/_kanban_card', [
                'inspection' => $inspection,
                'status'   => $status,
            ]);
        }
    }

    public function set_inspection_pipeline_autoload($id)
    {
        if ($id == '') {
            return false;
        }

        if ($this->session->has_userdata('inspection_pipeline')
                && $this->session->userdata('inspection_pipeline') == 'true') {
            $this->session->set_flashdata('inspectionid', $id);

            return true;
        }

        return false;
    }

    public function get_due_date()
    {
        if ($this->input->post()) {
            $date    = $this->input->post('date');
            $duedate = '';
            if (get_option('inspection_due_after') != 0) {
                $date    = to_sql_date($date);
                $d       = date('Y-m-d', strtotime('+' . get_option('inspection_due_after') . ' DAY', strtotime($date)));
                $duedate = _d($d);
                echo $duedate;
            }
        }
    }

    public function add_inspection_item()
    {
        if ($this->input->post() && $this->input->is_ajax_request()) {
            $this->inspections_model->inspections_add_inspection_item($this->input->post());
        }
    }

    public function remove_inspection_item()
    {
        if ($this->input->post() && $this->input->is_ajax_request()) {
            $this->inspections_model->inspections_remove_inspection_item($this->input->post());
        }
    }

}


