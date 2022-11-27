<?php

defined('BASEPATH') or exit('No direct script access allowed');

include_once(LIBSPATH . 'pdf/App_pdf.php');

class Inspection_pdf extends App_pdf
{
    protected $inspection;

    private $inspection_number;

    public function __construct($inspection, $tag = '')
    {
        $this->load_language($inspection->clientid);

        $inspection                = hooks()->apply_filters('inspection_html_pdf_data', $inspection);
        $GLOBALS['inspection_pdf'] = $inspection;

        parent::__construct();

        $this->tag             = $tag;
        $this->inspection        = $inspection;
        $this->inspection_number = format_inspection_number($this->inspection->id);

        $this->SetTitle($this->inspection_number);
    }

    public function prepare()
    {

        $this->set_view_vars([
            'status'          => $this->inspection->status,
            'inspection_number' => $this->inspection_number,
            'inspection'        => $this->inspection,
        ]);

        return $this->build();
    }

    protected function type()
    {
        return 'inspection';
    }

    protected function file_path()
    {
        $customPath = APPPATH . 'views/themes/' . active_clients_theme() . '/views/my_inspectionpdf.php';
        $actualPath = module_views_path('inspections','themes/' . active_clients_theme() . '/views/inspections/inspectionpdf.php');

        if (file_exists($customPath)) {
            $actualPath = $customPath;
        }

        return $actualPath;
    }
}
