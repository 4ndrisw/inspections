<?php

defined('BASEPATH') or exit('No direct script access allowed');

$input = $this->ci->input->post('id');

log_activity(json_encode($input));

$aColumns = [
    'nama_pesawat',
    'nomor_seri',
    'nomor_unit',
    'kelompok_alat',
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
            $btn_disable = '';
//            if($status == '2'){
//                $btn_disable = 'btn_disable';
//            }

            $_data = '<a class="btn btn-danger '.$btn_disable.'" title = "'._l('remove_this_item').'" href="#" onclick="inspections_remove_inspection_item(' . $aRow['id'] . ',' . '); return false;">X</a>';
        }
        $row[] = $_data;
    }
    $row['DT_RowClass'] = 'has-row-options';
    $output['aaData'][] = $row;
}
