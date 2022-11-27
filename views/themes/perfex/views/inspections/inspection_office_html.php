<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="mtop15 preview-top-wrapper">
   <div class="row">
      <div class="col-md-3">
         <div class="mbot30">
            <div class="inspection-html-logo">
               <?php echo get_dark_company_logo(); ?>
            </div>
         </div>
      </div>
      <div class="clearfix"></div>
   </div>
   <div class="top" data-sticky data-sticky-class="preview-sticky-header">
      <div class="container preview-sticky-container">
         <div class="row">
            <div class="col-md-12">
               <div class="col-md-3">
                  <h3 class="bold no-mtop inspection-html-number no-mbot">
                     <span class="sticky-visible hide">
                     <?php echo format_inspection_number($inspection->id); ?>
                     </span>
                  </h3>
                  <h4 class="inspection-html-status mtop7">
                     <?php echo format_inspection_status($inspection->status,'',true); ?>
                  </h4>
               </div>
               <div class="col-md-9">
                  <?php echo form_open(site_url('inspections/office_pdf/'.$inspection->id), array('class'=>'pull-right action-button')); ?>
                  <button type="submit" name="inspectionpdf" class="btn btn-default action-button download mright5 mtop7" value="inspectionpdf">
                  <i class="fa fa-file-pdf-o"></i>
                  <?php echo _l('clients_invoice_html_btn_download'); ?>
                  </button>
                  <?php echo form_close(); ?>
                  <?php if(is_client_logged_in() || is_staff_member()){ ?>
                  <a href="<?php echo site_url('clients/inspections/'); ?>" class="btn btn-default pull-right mright5 mtop7 action-button go-to-portal">
                  <?php echo _l('client_go_to_dashboard'); ?>
                  </a>
                  <?php } ?>
               </div>
            </div>
            <div class="clearfix"></div>
         </div>
      </div>
   </div>
</div>
<div class="clearfix"></div>
<div class="panel_s mtop20">
   <div class="panel-body">
      <div class="col-md-10 col-md-offset-1">
         <div class="row mtop20">
            <div class="col-md-6 col-sm-6 transaction-html-info-col-left">
               <h4 class="bold inspection-html-number"><?php echo format_inspection_number($inspection->id); ?></h4>
               <address class="inspection-html-company-info">
                  <?php echo format_organization_info(); ?>
               </address>
            </div>
            <div class="col-sm-6 text-right transaction-html-info-col-right">
               <span class="bold inspection_to"><?php echo _l('inspection_office_to'); ?>:</span>
               <address class="inspection-html-customer-billing-info">
                  <?php echo format_office_info($inspection->office, 'office', 'billing'); ?>
               </address>
               <!-- shipping details -->
               <?php if($inspection->include_shipping == 1 && $inspection->show_shipping_on_inspection == 1){ ?>
               <span class="bold inspection_ship_to"><?php echo _l('ship_to'); ?>:</span>
               <address class="inspection-html-customer-shipping-info">
                  <?php echo format_office_info($inspection->office, 'office', 'shipping'); ?>
               </address>
               <?php } ?>
            </div>
         </div>
         <div class="row">

            <div class="col-sm-12 text-left transaction-html-info-col-left">
               <p class="inspection_to"><?php echo _l('inspection_opening'); ?>:</p>
               <span class="inspection_to"><?php echo _l('inspection_client'); ?>:</span>
               <address class="inspection-html-customer-billing-info">
                  <?php echo format_customer_info($inspection, 'inspection', 'billing'); ?>
               </address>
               <!-- shipping details -->
               <?php if($inspection->include_shipping == 1 && $inspection->show_shipping_on_inspection == 1){ ?>
               <span class="bold inspection_ship_to"><?php echo _l('ship_to'); ?>:</span>
               <address class="inspection-html-customer-shipping-info">
                  <?php echo format_customer_info($inspection, 'inspection', 'shipping'); ?>
               </address>
               <?php } ?>
            </div>



            <div class="col-md-6">
               <div class="container-fluid">
                  <?php if(!empty($inspection_members)){ ?>
                     <strong><?= _l('inspection_members') ?></strong>
                     <ul class="inspection_members">
                     <?php 
                        foreach($inspection_members as $member){
                          echo ('<li style="list-style:auto" class="member">' . $member['firstname'] .' '. $member['lastname'] .'</li>');
                         }
                     ?>
                     </ul>
                  <?php } ?>
               </div>
            </div>
            <div class="col-md-6 text-right">
               <p class="no-mbot inspection-html-date">
                  <span class="bold">
                  <?php echo _l('inspection_data_date'); ?>:
                  </span>
                  <?php echo _d($inspection->date); ?>
               </p>
               <?php if(!empty($inspection->expirydate)){ ?>
               <p class="no-mbot inspection-html-expiry-date">
                  <span class="bold"><?php echo _l('inspection_data_expiry_date'); ?></span>:
                  <?php echo _d($inspection->expirydate); ?>
               </p>
               <?php } ?>
               <?php if(!empty($inspection->reference_no)){ ?>
               <p class="no-mbot inspection-html-reference-no">
                  <span class="bold"><?php echo _l('reference_no'); ?>:</span>
                  <?php echo $inspection->reference_no; ?>
               </p>
               <?php } ?>
               <?php if($inspection->program_id != 0 && get_option('show_project_on_inspection') == 1){ ?>
               <p class="no-mbot inspection-html-project">
                  <span class="bold"><?php echo _l('project'); ?>:</span>
                  <?php echo get_project_name_by_id($inspection->program_id); ?>
               </p>
               <?php } ?>
               <?php $pdf_custom_fields = get_custom_fields('inspection',array('show_on_pdf'=>1,'show_on_client_portal'=>1));
                  foreach($pdf_custom_fields as $field){
                    $value = get_custom_field_value($inspection->id,$field['id'],'inspection');
                    if($value == ''){continue;} ?>
               <p class="no-mbot">
                  <span class="bold"><?php echo $field['name']; ?>: </span>
                  <?php echo $value; ?>
               </p>
               <?php } ?>
            </div>
         </div>
         <div class="row">
            <div class="col-md-12">
               <div class="table-responsive">
                  <?php
                     $items = get_inspection_items_table_data($inspection, 'inspection');
                     echo $items->table();
                  ?>
               </div>
            </div>


            <div class="row mtop25">
               <div class="col-md-12">
                  <div class="col-md-6 text-center">
                     <div class="bold"><?php echo get_option('invoice_company_name'); ?></div>
                     <div class="qrcode text-center">
                        <img src="<?php echo site_url('download/preview_image?path='.protected_file_url_by_path(get_inspection_upload_path('inspection').$inspection->id.'/assigned-'.$inspection_number.'.png')); ?>" class="img-responsive center-block inspection-assigned" alt="inspection-<?= $inspection->id ?>">
                     </div>
                     <div class="assigned">
                     <?php if($inspection->assigned != 0 && get_option('show_assigned_on_inspections') == 1){ ?>
                        <?php echo get_staff_full_name($inspection->assigned); ?>
                     <?php } ?>

                     </div>
                  </div>
                     <div class="col-md-6 text-center">
                       <div class="bold"><?php echo $client_company; ?></div>
                       <?php if(!empty($inspection->signature)) { ?>
                           <div class="bold">
                              <p class="no-mbot"><?php echo _l('inspection_signed_by') . ": {$inspection->acceptance_firstname} {$inspection->acceptance_lastname}"?></p>
                              <p class="no-mbot"><?php echo _l('inspection_signed_date') . ': ' . _dt($inspection->acceptance_date) ?></p>
                              <p class="no-mbot"><?php echo _l('inspection_signed_ip') . ": {$inspection->acceptance_ip}"?></p>
                           </div>
                           <p class="bold"><?php echo _l('document_customer_signature_text'); ?>
                           <?php if($inspection->signed == 1 && has_permission('inspections','','delete')){ ?>
                              <a href="<?php echo admin_url('inspections/clear_signature/'.$inspection->id); ?>" data-toggle="tooltip" title="<?php echo _l('clear_signature'); ?>" class="_delete text-danger">
                                 <i class="fa fa-remove"></i>
                              </a>
                           <?php } ?>
                           </p>
                           <div class="customer_signature text-center">
                              <img src="<?php echo site_url('download/preview_image?path='.protected_file_url_by_path(get_inspection_upload_path('inspection').$inspection->id.'/'.$inspection->signature)); ?>" class="img-responsive center-block inspection-signature" alt="inspection-<?= $inspection->id ?>">
                           </div>
                       <?php } ?>
                     </div>
               </div>
            </div>

         </div>
      </div>
   </div>
</div>

