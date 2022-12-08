<?php

defined('BASEPATH') or exit('No direct script access allowed');

$dimensions = $pdf->getPageDimensions();

$info_right_column = '';
$info_left_column  = '';

$info_right_column .= '<span style="font-weight:bold;font-size:27px;">' . _l('inspection_office_pdf_heading') . '</span><br />';
$info_right_column .= '<b style="color:#4e4e4e;"># ' . str_replace("SCH","SCH-UPT",$inspection_number) . '</b>';

if (get_option('show_status_on_pdf_ei') == 1) {
    $info_right_column .= '<br /><span style="color:rgb(' . inspection_status_color_pdf($status) . ');text-transform:uppercase;">' . format_inspection_status($status, '', false) . '</span>';
}

// Add logo
$info_left_column .= pdf_logo_url();
// Write top left logo and right column info/text
pdf_multi_row($info_left_column, $info_right_column, $pdf, ($dimensions['wk'] / 2) - $dimensions['lm']);

$pdf->ln(8);

$organization_info = '<div style="color:#424242;">';
    $organization_info .= format_organization_info();
$organization_info .= '</div>';

// Inspection to
$inspection_info = '<b>' . _l('inspection_office_to') . '</b>';
$inspection_info .= '<div style="color:#424242;">';
$inspection_info .= format_office_info($inspection->office, 'inspection', 'billing');
$inspection_info .= '</div>';

$left_info  = $swap == '1' ? $inspection_info : $organization_info;
$right_info = $swap == '1' ? $organization_info : $inspection_info;

pdf_multi_row($left_info, $right_info, $pdf, ($dimensions['wk'] / 2) - $dimensions['lm']);

// Inspection to
$left_info ='<p>' . _l('inspection_opening') . ',</p>';
$left_info .= _l('inspection_client');
$left_info .= '<div style="color:#424242;">';
$left_info .= format_customer_info($inspection, 'inspection', 'billing');
$left_info .= '</div>';

$right_info = '';

$pdf->ln(4);
pdf_multi_row($left_info, $right_info, $pdf, ($dimensions['wk'] / 1) - $dimensions['lm']);

$organization_info = '<strong>'. _l('inspection_members') . ': </strong><br />';

$CI = &get_instance();
$CI->load->model('inspections_model');
$inspection_members = $CI->inspections_model->get_inspection_members($inspection->id,true);
$i=1;
foreach($inspection_members as $member){
  $organization_info .=  $i.'. ' .$member['firstname'] .' '. $member['lastname']. '<br />';
  $i++;
}

$inspection_info = '<br />' . _l('inspection_data_date') . ': ' . _d($inspection->date) . '<br />';


if ($inspection->program_id != 0 && get_option('show_project_on_inspection') == 1) {
    $inspection_info .= _l('project') . ': ' . get_project_name_by_id($inspection->program_id) . '<br />';
}


$left_info  = $swap == '1' ? $inspection_info : $organization_info;
$right_info = $swap == '1' ? $organization_info : $inspection_info;

$pdf->ln(4);
pdf_multi_row($left_info, $right_info, $pdf, ($dimensions['wk'] / 2) - $dimensions['lm']);

// The Table
$pdf->Ln(hooks()->apply_filters('pdf_info_and_table_separator', 6));

// The items table
$items = get_inspection_items_table_data($inspection, 'inspection', 'pdf');

$tblhtml = $items->table();

$pdf->writeHTML($tblhtml, true, false, false, false, '');

$pdf->SetFont($font_name, '', $font_size);

$assigned_path = <<<EOF
        <img width="150" height="150" src="$inspection->assigned_path">
    EOF;    
$assigned_info = '<div style="text-align:center;">';
    $assigned_info .= get_option('licence_company_name') . '<br />';
    $assigned_info .= $assigned_path . '<br />';

if ($inspection->assigned != 0 && get_option('show_assigned_on_inspections') == 1) {
    $assigned_info .= get_staff_full_name($inspection->assigned);
}
$assigned_info .= '</div>';

$acceptance_path = <<<EOF
    <img src="$inspection->acceptance_path">
EOF;
$client_info = '<div style="text-align:center;">';
    $client_info .= $inspection->client_company .'<br />';

if ($inspection->signed != 0) {
    $client_info .= _l('inspection_signed_by') . ": {$inspection->acceptance_firstname} {$inspection->acceptance_lastname}" . '<br />';
    $client_info .= _l('inspection_signed_date') . ': ' . _dt($inspection->acceptance_date_string) . '<br />';
    $client_info .= _l('inspection_signed_ip') . ": {$inspection->acceptance_ip}" . '<br />';

    $client_info .= $acceptance_path;
    $client_info .= '<br />';
}
$client_info .= '</div>';


$left_info  = $swap == '1' ? $client_info : $assigned_info;
$right_info = $swap == '1' ? $assigned_info : $client_info;
pdf_multi_row($left_info, $right_info, $pdf, ($dimensions['wk'] / 2) - $dimensions['lm']);

$pdf->ln(2);   
$companyname = get_option('companyname');
$pdf->writeHTMLCell('', '', '', '', _l('inspection_crm_info', $companyname), 0, 1, false, true, 'L', true);
