<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php echo form_hidden('_attachment_sale_id',$inspection->id); ?>
<?php echo form_hidden('_attachment_sale_type','inspection'); ?>
<div class="col-md-12 no-padding">
   <div class="panel_s">
      <div class="panel-body">
         <div class="horizontal-scrollable-tabs preview-tabs-top">
            <div class="scroller arrow-left"><i class="fa fa-angle-left"></i></div>
            <div class="scroller arrow-right"><i class="fa fa-angle-right"></i></div>
            <div class="horizontal-tabs">
               <ul class="nav nav-tabs nav-tabs-horizontal mbot15" role="tablist">
                  <li role="presentation" class="active">
                     <a href="#tab_inspection" aria-controls="tab_inspection" role="tab" data-toggle="tab">
                     <?php echo _l('inspection'); ?>
                     </a>
                  </li>
                  <li role="presentation">
                     <a href="#tab_inspection_items" onclick="initDataTable('.table-inspection_items', admin_url + 'inspections/get_inspection_items_table/'
                                                                                       + <?php echo $program->clientid ?> + '/'
                                                                                       + <?php echo $program->inspection_id ?> + '/'
                                                                                       + <?php echo $inspection->id ;?>, undefined, undefined, undefined,[1,'asc']); return false;" aria-controls="tab_inspection_items" role="tab" data-toggle="tab">
                     <?php echo _l('inspection_items_tab'); ?>
                     <?php
                        $total_inspection_items = total_rows(db_prefix().'program_items',
                          array(
                           'inspection_id'=>$inspection->id,
                           )
                          );
                        if($total_inspection_items > 0){
                          echo '<span class="badge">'.$total_inspection_items.'</span>';
                        }
                        ?>
                     </a>
                  </li>
                  <li role="presentation">
                     <a href="#tab_program_items" onclick="initDataTable('.table-program_items', admin_url + 'inspections/get_program_items_table/'
                                                                                       + <?php echo $program->clientid ?> + '/'
                                                                                       + <?php echo $inspection->program_id ?> + '/'
                                                                                       + <?php echo $inspection->id ;?>, undefined, undefined, undefined,[1,'asc']); return false;" aria-controls="tab_program_items" role="tab" data-toggle="tab">
                     <?php echo _l('program_items_tab'); ?>
                     <?php
                        $total_program_items = total_rows(db_prefix().'program_items',
                          array(
                           'program_id'=>$program->id,
                           'inspection_id'=>NULL,
                           )
                          );
                        if($total_program_items > 0){
                          echo '<span class="badge">'.$total_program_items.'</span>';
                        }
                        ?>
                     </a>
                  </li>
                  <li role="presentation">
                     <a href="#tab_tasks" onclick="init_rel_tasks_table(<?php echo $inspection->id; ?>,'inspection'); return false;" aria-controls="tab_tasks" role="tab" data-toggle="tab">
                     <?php echo _l('tasks'); ?>
                     </a>
                  </li>
                  <li role="presentation">
                     <a href="#tab_activity" aria-controls="tab_activity" role="tab" data-toggle="tab">
                     <?php echo _l('inspection_view_activity_tooltip'); ?>
                     </a>
                  </li>
                  <li role="presentation">
                     <a href="#tab_reminders" onclick="initDataTable('.table-reminders', admin_url + 'misc/get_reminders/' + <?php echo $inspection->id ;?> + '/' + 'inspection', undefined, undefined, undefined,[1,'asc']); return false;" aria-controls="tab_reminders" role="tab" data-toggle="tab">
                     <?php echo _l('inspection_reminders'); ?>
                     <?php
                        $total_reminders = total_rows(db_prefix().'reminders',
                          array(
                           'isnotified'=>0,
                           'staff'=>get_staff_user_id(),
                           'rel_type'=>'inspection',
                           'rel_id'=>$inspection->id
                           )
                          );
                        if($total_reminders > 0){
                          echo '<span class="badge">'.$total_reminders.'</span>';
                        }
                        ?>
                     </a>
                  </li>
                  <li role="presentation" class="tab-separator">
                     <a href="#tab_notes" onclick="get_sales_notes(<?php echo $inspection->id; ?>,'inspections'); return false" aria-controls="tab_notes" role="tab" data-toggle="tab">
                     <?php echo _l('inspection_notes'); ?>
                     <span class="notes-total">
                        <?php if($totalNotes > 0){ ?>
                           <span class="badge"><?php echo $totalNotes; ?></span>
                        <?php } ?>
                     </span>
                     </a>
                  </li>
                  <li role="presentation" data-toggle="tooltip" title="<?php echo _l('emails_tracking'); ?>" class="tab-separator">
                     <a href="#tab_emails_tracking" aria-controls="tab_emails_tracking" role="tab" data-toggle="tab">
                     <?php if(!is_mobile()){ ?>
                     <i class="fa fa-envelope-open-o" aria-hidden="true"></i>
                     <?php } else { ?>
                     <?php echo _l('emails_tracking'); ?>
                     <?php } ?>
                     </a>
                  </li>
                  <li role="presentation" data-toggle="tooltip" data-title="<?php echo _l('view_tracking'); ?>" class="tab-separator">
                     <a href="#tab_views" aria-controls="tab_views" role="tab" data-toggle="tab">
                     <?php if(!is_mobile()){ ?>
                     <i class="fa fa-eye"></i>
                     <?php } else { ?>
                     <?php echo _l('view_tracking'); ?>
                     <?php } ?>
                     </a>
                  </li>
                  <li role="presentation" data-toggle="tooltip" data-title="<?php echo _l('toggle_full_view'); ?>" class="tab-separator toggle_view">
                     <a href="#" onclick="small_table_full_view(); return false;">
                     <i class="fa fa-expand"></i></a>
                  </li>
               </ul>
            </div>
         </div>
         <div class="row mtop10">
            <div class="col-md-3">
               <?php echo format_inspection_status($inspection->status,'mtop5');  ?>
            </div>
            <div class="col-md-9">
               <div class="visible-xs">
                  <div class="mtop10"></div>
               </div>
               <div class="pull-right _buttons">
                  <?php if(staff_can('edit', 'inspections')){ ?>
                  <a href="<?php echo admin_url('inspections/inspection/'.$inspection->id); ?>" class="btn btn-default btn-with-tooltip" data-toggle="tooltip" title="<?php echo _l('edit_inspection_tooltip'); ?>" data-placement="bottom"><i class="fa fa-pencil-square-o"></i></a>
                  <?php } ?>
                  <div class="btn-group">
                     <a href="#" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><i class="fa fa-file-pdf-o"></i><?php if(is_mobile()){echo ' PDF';} ?> <span class="caret"></span></a>
                     <ul class="dropdown-menu dropdown-menu-right">
                        <li class="hidden-xs"><a href="<?php echo admin_url('inspections/pdf/'.$inspection->id.'?output_type=I'); ?>"><?php echo _l('view_pdf'); ?></a></li>
                        <li class="hidden-xs"><a href="<?php echo admin_url('inspections/pdf/'.$inspection->id.'?output_type=I'); ?>" target="_blank"><?php echo _l('view_pdf_in_new_window'); ?></a></li>
                        <li><a href="<?php echo admin_url('inspections/pdf/'.$inspection->id); ?>"><?php echo _l('download'); ?></a></li>
                        <li>
                           <a href="<?php echo admin_url('inspections/pdf/'.$inspection->id.'?print=true'); ?>" target="_blank">
                           <?php echo _l('print'); ?>
                           </a>
                        </li>
                     </ul>
                  </div>
                  <?php
                     $_tooltip = _l('inspection_sent_to_email_tooltip');
                     $_tooltip_already_send = '';
                     if($inspection->sent == 1){
                        $_tooltip_already_send = _l('inspection_already_send_to_client_tooltip', time_ago($inspection->datesend));
                     }
                     ?>
                  <?php if(!empty($inspection->clientid)){ ?>
                  <a href="#" class="inspection-send-to-client btn btn-default btn-with-tooltip" data-toggle="tooltip" title="<?php echo $_tooltip; ?>" data-placement="bottom"><span data-toggle="tooltip" data-title="<?php echo $_tooltip_already_send; ?>"><i class="fa fa-envelope"></i></span></a>
                  <?php } ?>
                  <div class="btn-group">
                     <button type="button" class="btn btn-default pull-left dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                     <?php echo _l('more'); ?> <span class="caret"></span>
                     </button>
                     <ul class="dropdown-menu dropdown-menu-right">
                        <li>
                           <a href="<?php echo site_url('inspection/' . $inspection->id . '/' .  $inspection->hash) ?>" target="_blank">
                           <?php echo _l('view_inspection_as_client'); ?>
                           </a>
                        </li>
                        <?php hooks()->do_action('after_inspection_view_as_client_link', $inspection); ?>
                        <?php if((!empty($inspection->expirydate) && date('Y-m-d') < $inspection->expirydate && ($inspection->status == 2 || $inspection->status == 5)) && is_inspections_expiry_reminders_enabled()){ ?>
                        <li>
                           <a href="<?php echo admin_url('inspections/send_expiry_reminder/'.$inspection->id); ?>">
                           <?php echo _l('send_expiry_reminder'); ?>
                           </a>
                        </li>
                        <?php } ?>
                        <li>
                           <a href="#" data-toggle="modal" data-target="#sales_attach_file"><?php echo _l('invoice_attach_file'); ?></a>
                        </li>
                        <?php if (staff_can('create', 'projects') && $inspection->program_id == 0) { ?>
                           <li>
                              <a href="<?php echo admin_url("projects/project?via_inspection_id={$inspection->id}&customer_id={$inspection->clientid}") ?>">
                                 <?php echo _l('inspection_convert_to_project'); ?>
                              </a>
                           </li>
                        <?php } ?>
                        <?php if($inspection->invoiceid == NULL){
                           if(staff_can('edit', 'inspections')){
                             foreach($inspection_statuses as $status){
                               if($inspection->status != $status){ ?>
                        <li>
                           <a href="<?php echo admin_url() . 'inspections/mark_action_status/'.$status.'/'.$inspection->id; ?>">
                           <?php echo _l('inspection_mark_as',format_inspection_status($status,'',false)); ?></a>
                        </li>
                        <?php }
                           }
                           ?>
                        <?php } ?>
                        <?php } ?>
                        <?php if(staff_can('create', 'inspections')){ ?>
                        <li>
                           <a href="<?php echo admin_url('inspections/copy/'.$inspection->id); ?>">
                           <?php echo _l('copy_inspection'); ?>
                           </a>
                        </li>
                        <?php } ?>
                        <?php if(!empty($inspection->signature) && staff_can('delete', 'inspections')){ ?>
                        <li>
                           <a href="<?php echo admin_url('inspections/clear_signature/'.$inspection->id); ?>" class="_delete">
                           <?php echo _l('clear_signature'); ?>
                           </a>
                        </li>
                        <?php } ?>
                        <?php if(staff_can('delete', 'inspections')){ ?>
                        <?php
                           if((get_option('delete_only_on_last_inspection') == 1 && is_last_inspection($inspection->id)) || (get_option('delete_only_on_last_inspection') == 0)){ ?>
                        <li>
                           <a href="<?php echo admin_url('inspections/delete/'.$inspection->id); ?>" class="text-danger delete-text _delete"><?php echo _l('delete_inspection_tooltip'); ?></a>
                        </li>
                        <?php
                           }
                           }
                           ?>
                     </ul>
                  </div>
                  <?php if($inspection->invoiceid == NULL){ ?>
                  <?php if(staff_can('create', 'invoices') && !empty($inspection->clientid)){ ?>
                  <div class="btn-group pull-right mleft5">
                     <button type="button" class="btn btn-success dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                     <?php echo _l('inspection_convert_to_licence'); ?> <span class="caret"></span>
                     </button>
                     <ul class="dropdown-menu">
                        <li><a href="<?php echo admin_url('inspections/convert_to_invoice/'.$inspection->id.'?save_as_draft=true'); ?>"><?php echo _l('convert_and_save_as_draft'); ?></a></li>
                        <li class="divider">
                        <li><a href="<?php echo admin_url('inspections/convert_to_invoice/'.$inspection->id); ?>"><?php echo _l('convert'); ?></a></li>
                        </li>
                     </ul>
                  </div>
                  <?php } ?>
                  <?php } else { ?>
                  <a href="<?php echo admin_url('invoices/list_invoices/'.$inspection->invoice->id); ?>" data-placement="bottom" data-toggle="tooltip" title="<?php echo _l('inspection_invoiced_date',_dt($inspection->invoiced_date)); ?>"class="btn mleft10 btn-info"><?php echo format_invoice_number($inspection->invoice->id); ?></a>
                  <?php } ?>
               </div>
            </div>
         </div>
         <div class="clearfix"></div>
         <hr class="hr-panel-heading" />
         <div class="tab-content">
            <div role="tabpanel" class="tab-pane ptop10 active" id="tab_inspection">
               <?php if(isset($inspection->inspectiond_email) && $inspection->inspectiond_email) { ?>
                     <div class="alert alert-warning">
                        <?php echo _l('invoice_will_be_sent_at', _dt($inspection->inspectiond_email->inspectiond_at)); ?>
                        <?php if(staff_can('edit', 'inspections') || $inspection->addedfrom == get_staff_user_id()) { ?>
                           <a href="#"
                           onclick="edit_inspection_inspectiond_email(<?php echo $inspection->inspectiond_email->id; ?>); return false;">
                           <?php echo _l('edit'); ?>
                        </a>
                     <?php } ?>
                  </div>
               <?php } ?>
               <div id="inspection-preview">
                  <div class="row">
                     <?php if($inspection->status == 4 && !empty($inspection->acceptance_firstname) && !empty($inspection->acceptance_lastname) && !empty($inspection->acceptance_email)){ ?>
                     <div class="col-md-12">
                        <div class="alert alert-info mbot15">
                           <?php echo _l('accepted_identity_info',array(
                              _l('inspection_lowercase'),
                              '<b>'.$inspection->acceptance_firstname . ' ' . $inspection->acceptance_lastname . '</b> (<a href="mailto:'.$inspection->acceptance_email.'">'.$inspection->acceptance_email.'</a>)',
                              '<b>'. _dt($inspection->acceptance_date).'</b>',
                              '<b>'.$inspection->acceptance_ip.'</b>'.(is_admin() ? '&nbsp;<a href="'.admin_url('inspections/clear_acceptance_info/'.$inspection->id).'" class="_delete text-muted" data-toggle="tooltip" data-title="'._l('clear_this_information').'"><i class="fa fa-remove"></i></a>' : '')
                              )); ?>
                        </div>
                     </div>
                     <?php } ?>
                     <?php if($inspection->program_id != 0){ ?>
                     <div class="col-md-12">
                        <h4 class="font-medium mbot15"><?php echo _l('related_to_project',array(
                           _l('inspection_lowercase'),
                           _l('project_lowercase'),
                           '<a href="'.admin_url('projects/view/'.$inspection->program_id).'" target="_blank">' . $inspection->project_data->name . '</a>',
                           )); ?></h4>
                     </div>
                     <?php } ?>
                     <div class="col-md-6 col-sm-6">
                        <h4 class="bold">
                           <?php
                              $tags = get_tags_in($inspection->id,'inspection');
                              if(count($tags) > 0){
                                echo '<i class="fa fa-tag" aria-hidden="true" data-toggle="tooltip" data-title="'.html_escape(implode(', ',$tags)).'"></i>';
                              }
                              ?>
                           <a href="<?php echo admin_url('inspections/inspection/'.$inspection->id); ?>">
                           <span id="inspection-number">
                           <?php echo format_inspection_number($inspection->id); ?>
                           </span>
                           </a>
                        </h4>
                        <address>
                           <?php echo format_organization_info(); ?>
                        </address>
                     </div>
                     <div class="col-sm-6 text-right">
                        <span class="bold"><?php echo _l('inspection_to'); ?>:</span>
                        <address>
                           <?php echo format_customer_info($inspection, 'inspection', 'billing', true); ?>
                        </address>
                        <?php if($inspection->include_shipping == 1 && $inspection->show_shipping_on_inspection == 1){ ?>
                        <span class="bold"><?php echo _l('ship_to'); ?>:</span>
                        <address>
                           <?php echo format_customer_info($inspection, 'inspection', 'shipping'); ?>
                        </address>
                        <?php } ?>
                        <p class="no-mbot">
                           <span class="bold">
                           <?php echo _l('inspection_data_date'); ?>:
                           </span>
                           <?php echo $inspection->date; ?>
                        </p>
                        <?php if(!empty($inspection->expirydate)){ ?>
                        <p class="no-mbot">
                           <span class="bold"><?php echo _l('inspection_data_expiry_date'); ?>:</span>
                           <?php echo $inspection->expirydate; ?>
                        </p>
                        <?php } ?>
                        <?php if(!empty($inspection->reference_no)){ ?>
                        <p class="no-mbot">
                           <span class="bold"><?php echo _l('reference_no'); ?>:</span>
                           <?php echo $inspection->reference_no; ?>
                        </p>
                        <?php } ?>
                        <?php if($inspection->sale_agent != 0 && get_option('show_sale_agent_on_inspections') == 1){ ?>
                        <p class="no-mbot">
                           <span class="bold"><?php echo _l('sale_agent_string'); ?>:</span>
                           <?php echo get_staff_full_name($inspection->sale_agent); ?>
                        </p>
                        <?php } ?>
                        <?php if($inspection->program_id != 0 && get_option('show_project_on_inspection') == 1){ ?>
                        <p class="no-mbot">
                           <span class="bold"><?php echo _l('project'); ?>:</span>
                           <?php echo get_project_name_by_id($inspection->program_id); ?>
                        </p>
                        <?php } ?>
                        <?php $pdf_custom_fields = get_custom_fields('inspection',array('show_on_pdf'=>1));
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
                                 //$items = get_items_table_data($inspection, 'inspection', 'html', true);
                                 //echo $items->table();
                              ?>
                        </div>
                     </div>
                     <div class="col-md-5 col-md-offset-7">
                     </div>
                     <?php if(count($inspection->attachments) > 0){ ?>
                     <div class="clearfix"></div>
                     <hr />
                     <div class="col-md-12">
                        <p class="bold text-muted"><?php echo _l('inspection_files'); ?></p>
                     </div>
                     <?php foreach($inspection->attachments as $attachment){
                        $attachment_url = site_url('download/file/sales_attachment/'.$attachment['attachment_key']);
                        if(!empty($attachment['external'])){
                          $attachment_url = $attachment['external_link'];
                        }
                        ?>
                     <div class="mbot15 row col-md-12" data-attachment-id="<?php echo $attachment['id']; ?>">
                        <div class="col-md-8">
                           <div class="pull-left"><i class="<?php echo get_mime_class($attachment['filetype']); ?>"></i></div>
                           <a href="<?php echo $attachment_url; ?>" target="_blank"><?php echo $attachment['file_name']; ?></a>
                           <br />
                           <small class="text-muted"> <?php echo $attachment['filetype']; ?></small>
                        </div>
                        <div class="col-md-4 text-right">
                           <?php if($attachment['visible_to_customer'] == 0){
                              $icon = 'fa fa-toggle-off';
                              $tooltip = _l('show_to_customer');
                              } else {
                              $icon = 'fa fa-toggle-on';
                              $tooltip = _l('hide_from_customer');
                              }
                              ?>
                           <a href="#" data-toggle="tooltip" onclick="toggle_file_visibility(<?php echo $attachment['id']; ?>,<?php echo $inspection->id; ?>,this); return false;" data-title="<?php echo $tooltip; ?>"><i class="<?php echo $icon; ?>" aria-hidden="true"></i></a>
                           <?php if($attachment['staffid'] == get_staff_user_id() || is_admin()){ ?>
                           <a href="#" class="text-danger" onclick="delete_inspection_attachment(<?php echo $attachment['id']; ?>); return false;"><i class="fa fa-times"></i></a>
                           <?php } ?>
                        </div>
                     </div>
                     <?php } ?>
                     <?php } ?>
                     <?php if($inspection->clientnote != ''){ ?>
                     <div class="col-md-12 mtop15">
                        <p class="bold text-muted"><?php echo _l('inspection_note'); ?></p>
                        <p><?php echo $inspection->clientnote; ?></p>
                     </div>
                     <?php } ?>
                     <?php if($inspection->terms != ''){ ?>
                     <div class="col-md-12 mtop15">
                        <p class="bold text-muted"><?php echo _l('terms_and_conditions'); ?></p>
                        <p><?php echo $inspection->terms; ?></p>
                     </div>
                     <?php } ?>
                  </div>
               </div>
            </div>
            <div role="tabpanel" class="tab-pane" id="tab_inspection_items">
               <span class="label label-success mbot5 mtop5"><?php echo _l($inspection->inspection_item_info); ?> </span>
               <hr />
               <?php render_datatable(array( _l( 'inspection_items'), _l( 'serial_number'), _l( 'unit_number'), _l( 'kelompok_alat'), _l( 'process')), 'inspection_items'); ?>
            </div>
            <div role="tabpanel" class="tab-pane" id="tab_program_items">
               <span class="label label-success mbot5 mtop5"><?php echo _l('program_item_proposed'); ?> </span>
               <hr />
               <?php render_datatable(array( _l( 'program_items'), _l( 'serial_number'), _l( 'unit_number'), _l( 'kelompok_alat'), _l( 'process')), 'program_items'); ?>
            </div>
            <div role="tabpanel" class="tab-pane" id="tab_tasks">
               <?php init_relation_tasks_table(array('data-new-rel-id'=>$inspection->id,'data-new-rel-type'=>'inspection')); ?>
            </div>
            <div role="tabpanel" class="tab-pane" id="tab_reminders">
               <a href="#" data-toggle="modal" class="btn btn-info" data-target=".reminder-modal-inspection-<?php echo $inspection->id; ?>"><i class="fa fa-bell-o"></i> <?php echo _l('inspection_set_reminder_title'); ?></a>
               <hr />
               <?php render_datatable(array( _l( 'reminder_description'), _l( 'reminder_date'), _l( 'reminder_staff'), _l( 'reminder_is_notified')), 'reminders'); ?>
               <?php $this->load->view('admin/includes/modals/reminder',array('id'=>$inspection->id,'name'=>'inspection','members'=>$members,'reminder_title'=>_l('inspection_set_reminder_title'))); ?>
            </div>
            <div role="tabpanel" class="tab-pane" id="tab_emails_tracking">
               <?php
                  $this->load->view('admin/includes/emails_tracking',array(
                     'tracked_emails'=>
                     get_tracked_emails($inspection->id, 'inspection'))
                  );
                  ?>
            </div>
            <div role="tabpanel" class="tab-pane" id="tab_notes">
               <?php echo form_open(admin_url('inspections/add_note/'.$inspection->id),array('id'=>'sales-notes','class'=>'inspection-notes-form')); ?>
               <?php echo render_textarea('description'); ?>
               <div class="text-right">
                  <button type="submit" class="btn btn-info mtop15 mbot15"><?php echo _l('inspection_add_note'); ?></button>
               </div>
               <?php echo form_close(); ?>
               <hr />
               <div class="panel_s mtop20 no-shadow" id="sales_notes_area">
               </div>
            </div>
            <div role="tabpanel" class="tab-pane" id="tab_activity">
               <div class="row">
                  <div class="col-md-12">
                     <div class="activity-feed">
                        <?php foreach($activity as $activity){
                           $_custom_data = false;
                           ?>
                        <div class="feed-item" data-sale-activity-id="<?php echo $activity['id']; ?>">
                           <div class="date">
                              <span class="text-has-action" data-toggle="tooltip" data-title="<?php echo _dt($activity['date']); ?>">
                              <?php echo time_ago($activity['date']); ?>
                              </span>
                           </div>
                           <div class="text">
                              <?php if(is_numeric($activity['staffid']) && $activity['staffid'] != 0){ ?>
                              <a href="<?php echo admin_url('profile/'.$activity["staffid"]); ?>">
                              <?php echo staff_profile_image($activity['staffid'],array('staff-profile-xs-image pull-left mright5'));
                                 ?>
                              </a>
                              <?php } ?>
                              <?php
                                 $additional_data = '';
                                 if(!empty($activity['additional_data'])){
                                  $additional_data = unserialize($activity['additional_data']);
                                  $i = 0;
                                  foreach($additional_data as $data){
                                    if(strpos($data,'<original_status>') !== false){
                                      $original_status = get_string_between($data, '<original_status>', '</original_status>');
                                      $additional_data[$i] = format_inspection_status($original_status,'',false);
                                    } else if(strpos($data,'<new_status>') !== false){
                                      $new_status = get_string_between($data, '<new_status>', '</new_status>');
                                      $additional_data[$i] = format_inspection_status($new_status,'',false);
                                    } else if(strpos($data,'<status>') !== false){
                                      $status = get_string_between($data, '<status>', '</status>');
                                      $additional_data[$i] = format_inspection_status($status,'',false);
                                    } else if(strpos($data,'<custom_data>') !== false){
                                      $_custom_data = get_string_between($data, '<custom_data>', '</custom_data>');
                                      unset($additional_data[$i]);
                                    }
                                    $i++;
                                  }
                                 }
                                 $_formatted_activity = _l($activity['description'],$additional_data);
                                 if($_custom_data !== false){
                                 $_formatted_activity .= ' - ' .$_custom_data;
                                 }
                                 if(!empty($activity['full_name'])){
                                 $_formatted_activity = $activity['full_name'] . ' - ' . $_formatted_activity;
                                 }
                                 echo $_formatted_activity;
                                 if(is_admin()){
                                 echo '<a href="#" class="pull-right text-danger" onclick="delete_sale_activity('.$activity['id'].'); return false;"><i class="fa fa-remove"></i></a>';
                                 }
                                 ?>
                           </div>
                        </div>
                        <?php } ?>
                     </div>
                  </div>
               </div>
            </div>
            <div role="tabpanel" class="tab-pane" id="tab_views">
               <?php
                  $views_activity = get_views_tracking('inspection',$inspection->id);
                  if(count($views_activity) === 0) {
                     echo '<h4 class="no-mbot">'._l('not_viewed_yet',_l('inspection_lowercase')).'</h4>';
                  }
                  foreach($views_activity as $activity){ ?>
               <p class="text-success no-margin">
                  <?php echo _l('view_date') . ': ' . _dt($activity['date']); ?>
               </p>
               <p class="text-muted">
                  <?php echo _l('view_ip') . ': ' . $activity['view_ip']; ?>
               </p>
               <hr />
               <?php } ?>
            </div>
         </div>
      </div>
   </div>
</div>
<script>
   init_items_sortable(true);
   init_btn_with_tooltips();
   init_datepicker();
   init_selectpicker();
   init_form_reminder();
   init_tabs_scrollable();
   <?php if($send_later) { ?>
      inspection_inspection_send(<?php echo $inspection->id; ?>);
   <?php } ?>
</script>
<?php $this->load->view('admin/inspections/inspection_send_to_client'); ?>
