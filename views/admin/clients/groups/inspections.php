<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php if(isset($client)){ ?>
	<h4 class="customer-profile-group-heading"><?php echo _l('inspections'); ?></h4>
	<?php if(has_permission('inspections','','create')){ ?>
		<a href="<?php echo admin_url('inspections/inspection?customer_id='.$client->userid); ?>" class="btn btn-info mbot15<?php if($client->active == 0){echo ' disabled';} ?>"><?php echo _l('create_new_inspection'); ?></a>
	<?php } ?>
	<?php if(has_permission('inspections','','view') || has_permission('inspections','','view_own') || get_option('allow_staff_view_inspections_assigned') == '1'){ ?>
		<a href="#" class="btn btn-info mbot15" data-toggle="modal" data-target="#client_zip_inspections"><?php echo _l('zip_inspections'); ?></a>
	<?php } ?>
	<div id="inspections_total"></div>
	<?php
	$this->load->view('admin/inspections/table_html', array('class'=>'inspections-single-client'));
	//$this->load->view('admin/clients/modals/zip_inspections');
	?>
<?php } ?>
