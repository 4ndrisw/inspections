<?php

defined('BASEPATH') or exit('No direct script access allowed');

$aColumns = [
    'nama_pesawat',
    'nomor_seri',
    'nomor_unit',
    '1',
    ];

$sIndexColumn = 'id';
$sTable       = db_prefix().'program_items';

$where        = [
    'AND clientid =' . $program_clientid,
    ];

//array_push($where, 'AND inspection_id IS NULL');

array_push($where, 'AND inspection_id IS NULL');
array_push($where, 'AND program_id = ' . $program_id);

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
    'kelompok_alat',
    'addedfrom',
    ]);
$output  = $result['output'];
$rResult = $result['rResult'];
foreach ($rResult as $aRow) {
    $row = [];
    for ($i = 0; $i < count($aColumns); $i++) {
        $_data = $aRow[$aColumns[$i]];

        if ($aColumns[$i] == 'staff') {
            $_data = '<a href="' . admin_url('staff/profile/' . $aRow['staff']) . '">' . staff_profile_image($aRow['staff'], [
                'staff-profile-image-small',
                ]) . ' ' . $aRow['firstname'] . ' ' . $aRow['lastname'] . '</a>';
        } elseif ($aColumns[$i] == 'description') {
            if ($aRow['creator'] == get_staff_user_id() || is_admin()) {
                $_data .= '<div class="row-options">';
                if ($aRow['isnotified'] == 0) {
                    $_data .= '<a href="#" onclick="edit_reminder(' . $aRow['id'] . ',this); return false;" class="edit-reminder">' . _l('edit') . '</a> | ';
                }
                $_data .= '<a href="' . admin_url('misc/delete_reminder/' . $id . '/' . $aRow['id'] . '/' . $aRow['rel_type']) . '" class="text-danger delete-reminder">' . _l('delete') . '</a>';
                $_data .= '</div>';
            }
        } elseif ($aColumns[$i] == 'isnotified') {
            if ($_data == 1) {
                $_data = _l('reminder_is_notified_boolean_yes');
            } else {
                $_data = _l('reminder_is_notified_boolean_no');
            }
        } elseif ($aColumns[$i] == 'date') {
            $_data = _dt($_data);
        }
        elseif ($aColumns[$i] == '1') {
            $current_user = get_client_type(get_staff_user_id());
            if((is_admin()
                || get_staff_user_id() == $aRow['addedfrom']
                || $current_user->client_id == $aRow['clientid']
                ) && (!in_array($inspection_status, [2,4]))){
                $_data = '<a class="btn btn-success" title = "'._l('inspection_this_item').'" href="#" onclick="inspections_add_inspection_item(' . $inspection_id . ','. $aRow['jenis_pesawat_id'] .','. $aRow['id'] . '); return false;">+</a>';
            }else{
                $_data = '';
            }
        }
        $row[] = $_data;
    }
    $row['DT_RowClass'] = 'has-row-options';
    $output['aaData'][] = $row;
}
