<?php

defined('BASEPATH') or exit('No direct script access allowed');

$input = $this->ci->input->post('id');

$aColumns = [
    'nama_pesawat',
    'nomor_seri',
    'nomor_unit',
    'surveyor_staff_id',
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

        if ($aColumns[$i] == 'surveyor_staff_id') {


            $staffs = get_staff_client($inspection_surveyor_id);

            $span = '';
                //if (!$locked) {
                    $span .= '<div class="dropdown inline-block mleft5 table-export-exclude">';
                    $span .= '<a href="#" style="font-size:14px;vertical-align:middle;" class="dropdown-toggle text-dark" id="tableLeadsStatus-' . $aRow['id'] . '" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">';
                    $span .= '<span data-toggle="tooltip" title="' . _l('inspection_get_staff') . '"><i class="fa fa-caret-down" aria-hidden="true"></i></span>';
                    $span .= '</a>';

                    $span .= '<ul class="dropdown-menu dropdown-menu-right" aria-labelledby="tableLicence-' . $aRow['id'] . '">';
                    foreach ($staffs as $staff) {
                        if ($aRow['surveyor_id'] == $staff->client_id) {
                            $span .= '<li>
                          <a href="#" onclick="inspections_set_surveyor_staff_id('.$staff->staffid.',' . $aRow['id'] . '); return false;">
                             ' . $staff->firstname . ' ' . $staff->lastname .'
                          </a>
                       </li>';
                        }
                    }
                    $span .= '</ul>';
                    $span .= '</div>';
                //}
                $span .= '</span>';


            if (is_null($aRow['surveyor_staff_id']) || $aRow['surveyor_staff_id']=='') {
                $_data = '<span class="label label-danger inline-block">' . _l('inspection_get_staff') . $span;
            }else{
                $_data = '<span class="label label-success inline-block">' . get_staff_full_name($_data) . $span;                
            }
        }
        elseif ($aColumns[$i] == '1') {
            $current_user = get_client_type(get_staff_user_id());
            if((is_admin()
                || get_staff_user_id() == $aRow['addedfrom']
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
