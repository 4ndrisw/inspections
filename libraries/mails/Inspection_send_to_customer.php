<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Inspection_send_to_customer extends App_mail_template
{
    protected $for = 'customer';

    protected $inspection;

    protected $contact;

    public $slug = 'inspection-send-to-client';

    public $rel_type = 'inspection';

    public function __construct($inspection, $contact, $cc = '')
    {
        parent::__construct();

        $this->inspection = $inspection;
        $this->contact = $contact;
        $this->cc      = $cc;
    }

    public function build()
    {
        if ($this->ci->input->post('email_attachments')) {
            $_other_attachments = $this->ci->input->post('email_attachments');
            foreach ($_other_attachments as $attachment) {
                $_attachment = $this->ci->inspections_model->get_attachments($this->inspection->id, $attachment);
                $this->add_attachment([
                                'attachment' => get_upload_path_by_type('inspection') . $this->inspection->id . '/' . $_attachment->file_name,
                                'filename'   => $_attachment->file_name,
                                'type'       => $_attachment->filetype,
                                'read'       => true,
                            ]);
            }
        }

        $this->to($this->contact->email)
        ->set_rel_id($this->inspection->id)
        ->set_merge_fields('client_merge_fields', $this->inspection->clientid, $this->contact->id)
        ->set_merge_fields('inspection_merge_fields', $this->inspection->id);
    }
}
