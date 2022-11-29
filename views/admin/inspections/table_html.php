<?php defined('BASEPATH') or exit('No direct script access allowed');

$table_data = array(
   _l('inspection_dt_table_heading_number'),
   array(
      'name'=>_l('inspection_dt_table_heading_client'),
      'th_attrs'=>array('class'=> (isset($client) ? 'not_visible' : ''))
   ),
   _l('inspection_dt_table_heading_amount'),
   _l('inspections_total_tax'),
   array(
      'name'=>_l('invoice_inspection_year'),
      'th_attrs'=>array('class'=>'not_visible')
   ),
   _l('projects'),
   _l('inspection_dt_table_heading_date'),
   _l('reference_no'),
   _l('inspection_dt_table_heading_status'));

$table_data = hooks()->apply_filters('inspections_table_columns', $table_data);

render_datatable($table_data, isset($class) ? $class : 'inspections');