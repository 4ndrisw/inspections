<?php

defined('BASEPATH') or exit('No direct script access allowed');

$program_id = $this->ci->input->post('program_id');

$aColumns = [
    db_prefix() . 'inspections.number',
    db_prefix() . 'inspections.total',
    db_prefix() . 'inspections.total_tax',
    'YEAR('. db_prefix() .'inspections.date) as year',
    get_sql_select_client_company(),
    db_prefix() . 'programs.number as program_number',
    '(SELECT GROUP_CONCAT(name SEPARATOR ",") FROM ' . db_prefix() . 'taggables JOIN ' . db_prefix() . 'tags ON ' . db_prefix() . 'taggables.tag_id = ' . db_prefix() . 'tags.id WHERE rel_id = ' . db_prefix() . 'inspections.id and rel_type="inspection" ORDER by tag_order ASC) as tags',
    db_prefix() . 'inspections.date',
    'expirydate',
    db_prefix() . 'inspections.reference_no',
    db_prefix() . 'inspections.status',
    ];

$join = [
    'LEFT JOIN ' . db_prefix() . 'clients ON ' . db_prefix() . 'clients.userid = ' . db_prefix() . 'inspections.clientid',
    'LEFT JOIN ' . db_prefix() . 'currencies ON ' . db_prefix() . 'currencies.id = ' . db_prefix() . 'inspections.currency',
    'LEFT JOIN ' . db_prefix() . 'programs ON ' . db_prefix() . 'programs.id = ' . db_prefix() . 'inspections.program_id',
];

$sIndexColumn = 'id';
$sTable       = db_prefix() . 'inspections';

$custom_fields = get_table_custom_fields('inspection');

foreach ($custom_fields as $key => $field) {
    $selectAs = (is_cf_date($field) ? 'date_picker_cvalue_' . $key : 'cvalue_' . $key);
    array_push($customFieldsColumns, $selectAs);
    array_push($aColumns, 'ctable_' . $key . '.value as ' . $selectAs);
    array_push($join, 'LEFT JOIN ' . db_prefix() . 'customfieldsvalues as ctable_' . $key . ' ON ' . db_prefix() . 'inspections.id = ctable_' . $key . '.relid AND ctable_' . $key . '.fieldto="' . $field['fieldto'] . '" AND ctable_' . $key . '.fieldid=' . $field['id']);
}

$where  = [];
$filter = [];

if ($this->ci->input->post('not_sent')) {
    array_push($filter, 'OR (sent= 0 AND ' . db_prefix() . 'inspections.status NOT IN (2,3,4))');
}
if ($this->ci->input->post('invoiced')) {
    array_push($filter, 'OR invoiceid IS NOT NULL');
}

if ($this->ci->input->post('not_invoiced')) {
    array_push($filter, 'OR invoiceid IS NULL');
}
$statuses  = $this->ci->inspections_model->get_statuses();
$statusIds = [];
foreach ($statuses as $status) {
    if ($this->ci->input->post('inspections_' . $status)) {
        array_push($statusIds, $status);
    }
}
if (count($statusIds) > 0) {
    array_push($filter, 'AND ' . db_prefix() . 'inspections.status IN (' . implode(', ', $statusIds) . ')');
}

$agents    = $this->ci->inspections_model->get_sale_agents();
$agentsIds = [];
foreach ($agents as $agent) {
    if ($this->ci->input->post('sale_agent_' . $agent['sale_agent'])) {
        array_push($agentsIds, $agent['sale_agent']);
    }
}
if (count($agentsIds) > 0) {
    array_push($filter, 'AND sale_agent IN (' . implode(', ', $agentsIds) . ')');
}

$years      = $this->ci->inspections_model->get_inspections_years();
$yearsArray = [];
foreach ($years as $year) {
    if ($this->ci->input->post('year_' . $year['year'])) {
        array_push($yearsArray, $year['year']);
    }
}
if (count($yearsArray) > 0) {
    array_push($filter, 'AND YEAR('. db_prefix() .'inspections.date) IN (' . implode(', ', $yearsArray) . ')');
}

if (count($filter) > 0) {
    array_push($where, 'AND (' . prepare_dt_filter($filter) . ')');
}

if (isset($clientid) && $clientid != '') {
    array_push($where, 'AND ' . db_prefix() . 'inspections.clientid=' . $this->ci->db->escape_str($clientid));
}

if ($program_id) {
    array_push($where, 'AND program_id=' . $this->ci->db->escape_str($program_id));
}

if (!has_permission('inspections', '', 'view')) {
    $userWhere = 'AND ' . get_inspections_where_sql_for_staff(get_staff_user_id());
    array_push($where, $userWhere);
}

$aColumns = hooks()->apply_filters('inspections_table_sql_columns', $aColumns);

// Fix for big queries. Some hosting have max_join_limit
if (count($custom_fields) > 4) {
    @$this->ci->db->query('SET SQL_BIG_SELECTS=1');
}

$result = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, $where, [
    db_prefix() . 'inspections.id',
    db_prefix() . 'inspections.clientid',
    db_prefix() . 'inspections.invoiceid',
    db_prefix() . 'currencies.name as currency_name',
    'program_id',
    db_prefix() . 'inspections.deleted_customer_name',
    db_prefix() . 'inspections.hash',
]);

$output  = $result['output'];
$rResult = $result['rResult'];

foreach ($rResult as $aRow) {
    $row = [];

    $numberOutput = '';
    // If is from client area table or programs area request
    if ((isset($clientid) && is_numeric($clientid)) || $program_id) {
        $numberOutput = '<a href="' . admin_url('inspections/list_inspections/' . $aRow['id']) . '" target="_blank">' . format_inspection_number($aRow['id']) . '</a>';
    } else {
        $numberOutput = '<a href="' . admin_url('inspections/list_inspections/' . $aRow['id']) . '" onclick="init_inspection(' . $aRow['id'] . '); return false;">' . format_inspection_number($aRow['id']) . '</a>';
    }

    $numberOutput .= '<div class="row-options">';

    $numberOutput .= '<a href="' . site_url('inspection/' . $aRow['id'] . '/' . $aRow['hash']) . '" target="_blank">' . _l('view') . '</a>';
    if (has_permission('inspections', '', 'edit')) {
        $numberOutput .= ' | <a href="' . admin_url('inspections/inspection/' . $aRow['id']) . '">' . _l('edit') . '</a>';
    }
    $numberOutput .= '</div>';

    $row[] = $numberOutput;

    $amount = isset($aRow['total']) ? $aRow['total'] : '';

    if ($aRow['invoiceid']) {
        $amount .= '<br /><span class="hide"> - </span><span class="text-success">' . _l('inspection_invoiced') . '</span>';
    }

    $row[] = $amount;

    $row[] = isset($aRow['total_tax']) ? $aRow['total_tax'] : '';

    $row[] = $aRow['year'];

    if (empty($aRow['deleted_customer_name'])) {
        $row[] = '<a href="' . admin_url('clients/client/' . $aRow['clientid']) . '">' . $aRow['company'] . '</a>';
    } else {
        $row[] = $aRow['deleted_customer_name'];
    }

    $row[] = '<a href="' . admin_url('programs/view/' . $aRow['program_id']) . '">' . $aRow['program_number'] . '</a>';

    $row[] = render_tags($aRow['tags']);

    $row[] = _d($aRow[db_prefix() . 'inspections.date']);

    $row[] = _d($aRow['expirydate']);

    $row[] = $aRow[db_prefix() . 'inspections.reference_no'];

    $row[] = format_inspection_status($aRow[db_prefix() . 'inspections.status']);

    // Custom fields add values
    foreach ($customFieldsColumns as $customFieldColumn) {
        $row[] = (strpos($customFieldColumn, 'date_picker_') !== false ? _d($aRow[$customFieldColumn]) : $aRow[$customFieldColumn]);
    }

    $row['DT_RowClass'] = 'has-row-options';

    $row = hooks()->apply_filters('inspections_table_row_data', $row, $aRow);

    $output['aaData'][] = $row;
}

echo json_encode($output);
die();