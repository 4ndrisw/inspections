<?php defined('BASEPATH') or exit('No direct script access allowed');

$table_data = array(
   _l('inspection_dt_table_heading_number'),
   _l('inspection_dt_table_heading_amount'),
   _l('inspections_total_tax'),
   array(
      'name'=>_l('invoice_inspection_year'),
      'th_attrs'=>array('class'=>'not_visible')
   ),
   array(
      'name'=>_l('inspection_dt_table_heading_client'),
      'th_attrs'=>array('class'=> (isset($client) ? 'not_visible' : ''))
   ),
   _l('projects'),
   _l('inspection_dt_table_heading_date'),
   _l('inspection_dt_table_heading_expirydate'),
   _l('reference_no'),
   _l('inspection_dt_table_heading_status'));

$custom_fields = get_custom_fields('inspection',array('show_on_table'=>1));

foreach($custom_fields as $field){
   array_push($table_data,$field['name']);
}

$table_data = hooks()->apply_filters('inspections_table_columns', $table_data);

render_datatable($table_data, isset($class) ? $class : 'inspections');
