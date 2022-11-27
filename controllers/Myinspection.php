<?php defined('BASEPATH') or exit('No direct script access allowed');

class Myinspection extends ClientsController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('inspections_model');
        $this->load->model('clients_model');
    }

    /* Get all inspections in case user go on index page */
    public function list($id = '')
    {
        if ($this->input->is_ajax_request()) {
            $this->app->get_table_data(module_views_path('inspections', 'admin/tables/table'));
        }
        $contact_id = get_contact_user_id();
        $user_id = get_user_id_by_contact_id($contact_id);
        $client = $this->clients_model->get($user_id);
        $data['inspections'] = $this->inspections_model->get_client_inspections($client);
        $data['inspectionid']            = $id;
        $data['title']                 = _l('inspections_tracking');

        $data['bodyclass'] = 'inspections';
        $this->data($data);
        $this->view('themes/'. active_clients_theme() .'/views/inspections/inspections');
        $this->layout();
    }

    public function show($id, $hash)
    {
        check_inspection_restrictions($id, $hash);
        $inspection = $this->inspections_model->get($id);

        if (!is_client_logged_in()) {
            load_client_language($inspection->clientid);
        }

        $identity_confirmation_enabled = get_option('inspection_accept_identity_confirmation');

        if ($this->input->post('inspection_action')) {
            $action = $this->input->post('inspection_action');

            // Only decline and accept allowed
            if ($action == 4 || $action == 3) {
                $success = $this->inspections_model->mark_action_status($action, $id, true);

                $redURL   = $this->uri->uri_string();
                $accepted = false;

                if (is_array($success)) {
                    if ($action == 4) {
                        $accepted = true;
                        set_alert('success', _l('clients_inspection_accepted_not_invoiced'));
                    } else {
                        set_alert('success', _l('clients_inspection_declined'));
                    }
                } else {
                    set_alert('warning', _l('clients_inspection_failed_action'));
                }
                if ($action == 4 && $accepted = true) {
                    process_digital_signature_image($this->input->post('signature', false), SCHEDULE_ATTACHMENTS_FOLDER . $id);

                    $this->db->where('id', $id);
                    $this->db->update(db_prefix() . 'inspections', get_acceptance_info_array());
                }
            }
            redirect($redURL);
        }
        // Handle Inspection PDF generator

        $inspection_number = format_inspection_number($inspection->id);
        /*
        if ($this->input->post('inspectionpdf')) {
            try {
                $pdf = inspection_pdf($inspection);
            } catch (Exception $e) {
                echo $e->getMessage();
                die;
            }

            //$inspection_number = format_inspection_number($inspection->id);
            $companyname     = get_option('company_name');
            if ($companyname != '') {
                $inspection_number .= '-' . mb_strtoupper(slug_it($companyname), 'UTF-8');
            }

            $filename = hooks()->apply_filters('customers_area_download_inspection_filename', mb_strtoupper(slug_it($inspection_number), 'UTF-8') . '.pdf', $inspection);

            $pdf->Output($filename, 'D');
            die();
        }
        */

        $data['title'] = $inspection_number;
        $this->disableNavigation();
        $this->disableSubMenu();

        $data['inspection_number']              = $inspection_number;
        $data['hash']                          = $hash;
        $data['can_be_accepted']               = false;
        $data['inspection']                     = hooks()->apply_filters('inspection_html_pdf_data', $inspection);
        $data['bodyclass']                     = 'viewinspection';
        $data['client_company']                = $this->clients_model->get($inspection->clientid)->company;
        $setSize = get_option('inspection_qrcode_size');

        $data['identity_confirmation_enabled'] = $identity_confirmation_enabled;
        if ($identity_confirmation_enabled == '1') {
            $data['bodyclass'] .= ' identity-confirmation';
        }
        $data['inspection_members']  = $this->inspections_model->get_inspection_members($inspection->id,true);

        $qrcode_data  = '';
        $qrcode_data .= _l('inspection_number') . ' : ' . $inspection_number ."\r\n";
        $qrcode_data .= _l('inspection_date') . ' : ' . $inspection->date ."\r\n";
        $qrcode_data .= _l('inspection_datesend') . ' : ' . $inspection->datesend ."\r\n";
        //$qrcode_data .= _l('inspection_assigned_string') . ' : ' . get_staff_full_name($inspection->assigned) ."\r\n";
        //$qrcode_data .= _l('inspection_url') . ' : ' . site_url('inspections/show/'. $inspection->id .'/'.$inspection->hash) ."\r\n";


        $inspection_path = get_upload_path_by_type('inspections') . $inspection->id . '/';
        _maybe_create_upload_path('uploads/inspections');
        _maybe_create_upload_path('uploads/inspections/'.$inspection_path);

        $params['data'] = $qrcode_data;
        $params['writer'] = 'png';
        $params['setSize'] = isset($setSize) ? $setSize : 160;
        $params['encoding'] = 'UTF-8';
        $params['setMargin'] = 0;
        $params['setForegroundColor'] = ['r'=>0,'g'=>0,'b'=>0];
        $params['setBackgroundColor'] = ['r'=>255,'g'=>255,'b'=>255];

        $params['crateLogo'] = true;
        $params['logo'] = './uploads/company/favicon.png';
        $params['setResizeToWidth'] = 60;

        $params['crateLabel'] = false;
        $params['label'] = $inspection_number;
        $params['setTextColor'] = ['r'=>255,'g'=>0,'b'=>0];
        $params['ErrorCorrectionLevel'] = 'hight';

        $params['saveToFile'] = FCPATH.'uploads/inspections/'.$inspection_path .'assigned-'.$inspection_number.'.'.$params['writer'];

        $this->load->library('endroid_qrcode');
        $this->endroid_qrcode->generate($params);

        $this->data($data);
        $this->app_scripts->theme('sticky-js', 'assets/plugins/sticky/sticky.js');
        $this->view('themes/'. active_clients_theme() .'/views/inspections/inspectionhtml');
        add_views_tracking('inspection', $id);
        hooks()->do_action('inspection_html_viewed', $id);
        no_index_customers_area();
        $this->layout();
    }


    public function office($id, $hash)
    {
        check_inspection_restrictions($id, $hash);
        $inspection = $this->inspections_model->get($id);

        if (!is_client_logged_in()) {
            load_client_language($inspection->clientid);
        }

        $identity_confirmation_enabled = get_option('inspection_accept_identity_confirmation');

        if ($this->input->post('inspection_action')) {
            $action = $this->input->post('inspection_action');

            // Only decline and accept allowed
            if ($action == 4 || $action == 3) {
                $success = $this->inspections_model->mark_action_status($action, $id, true);

                $redURL   = $this->uri->uri_string();
                $accepted = false;

                if (is_array($success)) {
                    if ($action == 4) {
                        $accepted = true;
                        set_alert('success', _l('clients_inspection_accepted_not_invoiced'));
                    } else {
                        set_alert('success', _l('clients_inspection_declined'));
                    }
                } else {
                    set_alert('warning', _l('clients_inspection_failed_action'));
                }
                if ($action == 4 && $accepted = true) {
                    process_digital_signature_image($this->input->post('signature', false), SCHEDULE_ATTACHMENTS_FOLDER . $id);

                    $this->db->where('id', $id);
                    $this->db->update(db_prefix() . 'inspections', get_acceptance_info_array());
                }
            }
            redirect($redURL);
        }
        // Handle Inspection PDF generator

        $inspection_number = format_inspection_number($inspection->id);
        /*
        if ($this->input->post('inspectionpdf')) {
            try {
                $pdf = inspection_pdf($inspection);
            } catch (Exception $e) {
                echo $e->getMessage();
                die;
            }

            //$inspection_number = format_inspection_number($inspection->id);
            $companyname     = get_option('company_name');
            if ($companyname != '') {
                $inspection_number .= '-' . mb_strtoupper(slug_it($companyname), 'UTF-8');
            }

            $filename = hooks()->apply_filters('customers_area_download_inspection_filename', mb_strtoupper(slug_it($inspection_number), 'UTF-8') . '.pdf', $inspection);

            $pdf->Output($filename, 'D');
            die();
        }
        */

        $data['title'] = $inspection_number;
        $this->disableNavigation();
        $this->disableSubMenu();

        $data['inspection_number']              = $inspection_number;
        $data['hash']                          = $hash;
        $data['can_be_accepted']               = false;
        $data['inspection']                     = hooks()->apply_filters('inspection_html_pdf_data', $inspection);
        $data['bodyclass']                     = 'viewinspection';
        $data['client_company']                = $this->clients_model->get($inspection->clientid)->company;
        $setSize = get_option('inspection_qrcode_size');

        $data['identity_confirmation_enabled'] = $identity_confirmation_enabled;
        if ($identity_confirmation_enabled == '1') {
            $data['bodyclass'] .= ' identity-confirmation';
        }
        $data['inspection_members']  = $this->inspections_model->get_inspection_members($inspection->id,true);

        $qrcode_data  = '';
        $qrcode_data .= _l('inspection_number') . ' : ' . $inspection_number ."\r\n";
        $qrcode_data .= _l('inspection_date') . ' : ' . $inspection->date ."\r\n";
        $qrcode_data .= _l('inspection_datesend') . ' : ' . $inspection->datesend ."\r\n";
        //$qrcode_data .= _l('inspection_assigned_string') . ' : ' . get_staff_full_name($inspection->assigned) ."\r\n";
        //$qrcode_data .= _l('inspection_url') . ' : ' . site_url('inspections/show/'. $inspection->id .'/'.$inspection->hash) ."\r\n";


        $inspection_path = get_upload_path_by_type('inspections') . $inspection->id . '/';
        _maybe_create_upload_path('uploads/inspections');
        _maybe_create_upload_path('uploads/inspections/'.$inspection_path);

        $params['data'] = $qrcode_data;
        $params['writer'] = 'png';
        $params['setSize'] = isset($setSize) ? $setSize : 160;
        $params['encoding'] = 'UTF-8';
        $params['setMargin'] = 0;
        $params['setForegroundColor'] = ['r'=>0,'g'=>0,'b'=>0];
        $params['setBackgroundColor'] = ['r'=>255,'g'=>255,'b'=>255];

        $params['crateLogo'] = true;
        $params['logo'] = './uploads/company/favicon.png';
        $params['setResizeToWidth'] = 60;

        $params['crateLabel'] = false;
        $params['label'] = $inspection_number;
        $params['setTextColor'] = ['r'=>255,'g'=>0,'b'=>0];
        $params['ErrorCorrectionLevel'] = 'hight';

        $params['saveToFile'] = FCPATH.'uploads/inspections/'.$inspection_path .'assigned-'.$inspection_number.'.'.$params['writer'];

        $this->load->library('endroid_qrcode');
        $this->endroid_qrcode->generate($params);

        $this->data($data);
        $this->app_scripts->theme('sticky-js', 'assets/plugins/sticky/sticky.js');
        $this->view('themes/'. active_clients_theme() .'/views/inspections/inspection_office_html');
        add_views_tracking('inspection', $id);
        hooks()->do_action('inspection_html_viewed', $id);
        no_index_customers_area();
        $this->layout();
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
            redirect(admin_url('inspections'));
        }
        $inspection        = $this->inspections_model->get($id);
        $inspection_number = format_inspection_number($inspection->id);
        
        $inspection->assigned_path = FCPATH . get_inspection_upload_path('inspection').$inspection->id.'/assigned-'.$inspection_number.'.png';
        $inspection->acceptance_path = FCPATH . get_inspection_upload_path('inspection').$inspection->id .'/'.$inspection->signature;
        
        $inspection->client_company = $this->clients_model->get($inspection->clientid)->company;
        $inspection->acceptance_date_string = _dt($inspection->acceptance_date);


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

    /* Generates inspection PDF and senting to email  */
    public function office_pdf($id)
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
            redirect(admin_url('inspections'));
        }
        $inspection        = $this->inspections_model->get($id);
        $inspection_number = format_inspection_number($inspection->id);
        
        $inspection->assigned_path = FCPATH . get_inspection_upload_path('inspection').$inspection->id.'/assigned-'.$inspection_number.'.png';
        $inspection->acceptance_path = FCPATH . get_inspection_upload_path('inspection').$inspection->id .'/'.$inspection->signature;
        
        $inspection->client_company = $this->clients_model->get($inspection->clientid)->company;
        $inspection->acceptance_date_string = _dt($inspection->acceptance_date);


        try {
            $pdf = inspection_office_pdf($inspection);
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
                            'file_name' => str_replace("SCH", "SCH-UPT", mb_strtoupper(slug_it($inspection_number)) . '.pdf'),
                            'inspection'  => $inspection,
                        ]);

        $pdf->Output($fileNameHookData['file_name'], $type);
    }
}
