<?php

defined('BASEPATH') or exit('No direct script access allowed');

$input = $this->ci->input->post('id');

log_activity(json_encode($input));

$aColumns = [
    'nama_pesawat',
    'nomor_seri',
    'nomor_unit',
    '1',
    ];

$sIndexColumn = 'id';
$sTable       = db_prefix().'program_items';

$where        = [
    'AND clientid=' . $program_clientid,
    ];

array_push($where, 'AND inspection_id = ' . $inspection_id);

$join = [
//    'JOIN '.db_prefix().'staff ON '.db_prefix().'staff.staffid = '.db_prefix().'reminders.staff',
    ];
$result = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, $where, [
    'id',
    'institution_id',
    'inspector_id',
    'inspector_staff_id',
    'surveyor_id',
    'program_id',
    'clientid',
    'jenis_pesawat_id',
    'addedfrom',
    ]);
$output  = $result['output'];
$rResult = $result['rResult'];
foreach ($rResult as $aRow) {
    $row = [];
    for ($i = 0; $i < count($aColumns); $i++) {
        $_data = $aRow[$aColumns[$i]];








        if ($aColumns[$i] == 'nama_pesawat') {

            //$_data = '<a href="'. admin_url('inspections/inspection_item/' . $aRow['id']. '/' . $aRow['jenis_pesawat_id']) .'" onclick="init_inspection_items_modal(' . $aRow['id'] .','. $aRow['jenis_pesawat_id'] . '); return false;" >' . $aRow['nama_pesawat'] . '</a>';
            $_data = '<a href="'. admin_url('inspections/inspection_item/' . $aRow['id']. '/' . $aRow['jenis_pesawat_id']) .'">' . $aRow['nama_pesawat'] . '</a>';
        }
        elseif ($aColumns[$i] == 'kelompok_alat') {
            $row[] = strtoupper($_data);
        }
        elseif ($aColumns[$i] == '1') {
            $current_user = get_client_type(get_staff_user_id());
            if((get_staff_user_id() == $aRow['addedfrom']
                || $current_user->client_id == $aRow['clientid']
                ) && (!in_array($inspection_status, [2,4]))){
                $_data = '<a class="btn btn-danger" title = "'._l('remove_this_item').'" href="#" onclick="inspections_remove_inspection_item(' . $aRow['id'] . ',' . '); return false;">X</a>';
            }else{
                $_data = '';
            }
        }
        $row[] = $_data;
    }
    $row['DT_RowClass'] = 'has-row-options';
    $output['aaData'][] = $row;
}
