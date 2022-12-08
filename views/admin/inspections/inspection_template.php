<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="panel_s accounting-template inspection">
   <div class="panel-body">
      <?php if(isset($inspection)){ ?>
      <?php echo format_inspection_status($inspection->status); ?>
      <hr class="hr-panel-heading" />
      <?php } ?>
      <div class="row">
          <?php if (isset($inspection_request_id) && $inspection_request_id != '') {
              echo form_hidden('inspection_request_id',$inspection_request_id);
          }
          ?>
         <div class="col-md-6">
            <div class="f_client_id">
             <div class="form-group select-placeholder">
                <label for="clientid" class="control-label"><?php echo _l('inspection_select_customer'); ?></label>
                <select id="clientid" name="clientid" data-live-search="true" data-width="100%" class="ajax-search<?php if(isset($inspection) && empty($inspection->clientid)){echo ' customer-removed';} ?>" data-none-selected-text="<?php echo _l('dropdown_non_selected_tex'); ?>">
               <?php $selected = (isset($inspection) ? $inspection->clientid : '');
                 if($selected == ''){
                   $selected = (isset($customer_id) ? $customer_id: '');
                 }
                 if($selected != ''){
                    $rel_data = get_relation_data('customer',$selected);
                    $rel_val = get_relation_values($rel_data,'customer');
                    echo '<option value="'.$rel_val['id'].'" selected>'.$rel_val['name'].'</option>';
                 } ?>
                </select>
              </div>
            </div>
            <div class="form-group select-placeholder projects-wrapper<?php if((!isset($inspection)) || (isset($inspection) && !customer_has_projects($inspection->clientid))){ echo ' hide';} ?>">
             <label for="program_id"><?php echo _l('project'); ?></label>
             <div id="project_ajax_search_wrapper">
               <select name="program_id" id="program_id" class="projects ajax-search" data-live-search="true" data-width="100%" data-none-selected-text="<?php echo _l('dropdown_non_selected_tex'); ?>">
                <?php
                  if(isset($inspection) && $inspection->program_id != 0){
                    echo '<option value="'.$inspection->program_id.'" selected>'.get_project_name_by_id($inspection->program_id).'</option>';
                  }
                ?>
              </select>
            </div>
           </div>
            <div class="row">
               <div class="col-md-12">
                  <a href="#" class="edit_shipping_billing_info" data-toggle="modal" data-target="#billing_and_shipping_details"><i class="fa fa-pencil-square-o"></i></a>
                  <?php include_once(module_views_path('inspections','admin/inspections/billing_and_shipping_template.php')); ?>
               </div>
               <div class="col-md-6">
                  <p class="bold"><?php echo _l('licence_bill_to'); ?></p>
                  <address>
                     <span class="billing_street">
                     <?php $billing_street = (isset($inspection) ? $inspection->billing_street : '--'); ?>
                     <?php $billing_street = ($billing_street == '' ? '--' :$billing_street); ?>
                     <?php echo $billing_street; ?></span><br>
                     <span class="billing_city">
                     <?php $billing_city = (isset($inspection) ? $inspection->billing_city : '--'); ?>
                     <?php $billing_city = ($billing_city == '' ? '--' :$billing_city); ?>
                     <?php echo $billing_city; ?></span>,
                     <span class="billing_state">
                     <?php $billing_state = (isset($inspection) ? $inspection->billing_state : '--'); ?>
                     <?php $billing_state = ($billing_state == '' ? '--' :$billing_state); ?>
                     <?php echo $billing_state; ?></span>
                     <br/>
                     <span class="billing_country">
                     <?php $billing_country = (isset($inspection) ? get_country_short_name($inspection->billing_country) : '--'); ?>
                     <?php $billing_country = ($billing_country == '' ? '--' :$billing_country); ?>
                     <?php echo $billing_country; ?></span>,
                     <span class="billing_zip">
                     <?php $billing_zip = (isset($inspection) ? $inspection->billing_zip : '--'); ?>
                     <?php $billing_zip = ($billing_zip == '' ? '--' :$billing_zip); ?>
                     <?php echo $billing_zip; ?></span>
                  </address>
               </div>
               <div class="col-md-6">
                  <p class="bold"><?php echo _l('ship_to'); ?></p>
                  <address>
                     <span class="shipping_street">
                     <?php $shipping_street = (isset($inspection) ? $inspection->shipping_street : '--'); ?>
                     <?php $shipping_street = ($shipping_street == '' ? '--' :$shipping_street); ?>
                     <?php echo $shipping_street; ?></span><br>
                     <span class="shipping_city">
                     <?php $shipping_city = (isset($inspection) ? $inspection->shipping_city : '--'); ?>
                     <?php $shipping_city = ($shipping_city == '' ? '--' :$shipping_city); ?>
                     <?php echo $shipping_city; ?></span>,
                     <span class="shipping_state">
                     <?php $shipping_state = (isset($inspection) ? $inspection->shipping_state : '--'); ?>
                     <?php $shipping_state = ($shipping_state == '' ? '--' :$shipping_state); ?>
                     <?php echo $shipping_state; ?></span>
                     <br/>
                     <span class="shipping_country">
                     <?php $shipping_country = (isset($inspection) ? get_country_short_name($inspection->shipping_country) : '--'); ?>
                     <?php $shipping_country = ($shipping_country == '' ? '--' :$shipping_country); ?>
                     <?php echo $shipping_country; ?></span>,
                     <span class="shipping_zip">
                     <?php $shipping_zip = (isset($inspection) ? $inspection->shipping_zip : '--'); ?>
                     <?php $shipping_zip = ($shipping_zip == '' ? '--' :$shipping_zip); ?>
                     <?php echo $shipping_zip; ?></span>
                  </address>
               </div>
            </div>
            <?php
               $next_inspection_number = get_option('next_inspection_number');
               $format = get_option('inspection_number_format');

                if(isset($inspection)){
                  $format = $inspection->number_format;
                }

               $prefix = get_option('inspection_prefix');

               if ($format == 1) {
                 $__number = $next_inspection_number;
                 if(isset($inspection)){
                   $__number = $inspection->number;
                   $prefix = '<span id="prefix">' . $inspection->prefix . '</span>';
                 }
               } else if($format == 2) {
                 if(isset($inspection)){
                   $__number = $inspection->number;
                   $prefix = $inspection->prefix;
                   $prefix = '<span id="prefix">'. $prefix . '</span><span id="prefix_year">' . date('Y',strtotime($inspection->date)).'</span>/';
                 } else {
                   $__number = $next_inspection_number;
                   $prefix = $prefix.'<span id="prefix_year">'.date('Y').'</span>/';
                 }
               } else if($format == 3) {
                  if(isset($inspection)){
                   $yy = date('y',strtotime($inspection->date));
                   $__number = $inspection->number;
                   $prefix = '<span id="prefix">'. $inspection->prefix . '</span>';
                 } else {
                  $yy = date('y');
                  $__number = $next_inspection_number;
                }
               } else if($format == 4) {
                  if(isset($inspection)){
                   $yyyy = date('Y',strtotime($inspection->date));
                   $mm = date('m',strtotime($inspection->date));
                   $__number = $inspection->number;
                   $prefix = '<span id="prefix">'. $inspection->prefix . '</span>';
                 } else {
                  $yyyy = date('Y');
                  $mm = date('m');
                  $__number = $next_inspection_number;
                }
               }

               $_inspection_number = str_pad($__number, get_option('number_padding_prefixes'), '0', STR_PAD_LEFT);
               $isedit = isset($inspection) ? 'true' : 'false';
               $data_original_number = isset($inspection) ? $inspection->number : 'false';
               ?>
            <div class="form-group">
               <label for="number"><?php echo _l('inspection_add_edit_number'); ?></label>
               <div class="input-group">
                  <span class="input-group-addon">
                  <?php if(isset($inspection)){ ?>
                  <a href="#" onclick="return false;" data-toggle="popover" data-container='._transaction_form' data-html="true" data-content="<label class='control-label'><?php echo _l('settings_sales_inspection_prefix'); ?></label><div class='input-group'><input name='s_prefix' type='text' class='form-control' value='<?php echo $inspection->prefix; ?>'></div><button type='button' onclick='save_sales_number_settings(this); return false;' data-url='<?php echo admin_url('inspections/update_number_settings/'.$inspection->id); ?>' class='btn btn-info btn-block mtop15'><?php echo _l('submit'); ?></button>"><i class="fa fa-cog"></i></a>
                   <?php }
                    echo $prefix;
                  ?>
                 </span>
                  <input type="text" name="number" class="form-control" value="<?php echo $_inspection_number; ?>" data-isedit="<?php echo $isedit; ?>" data-original-number="<?php echo $data_original_number; ?>">
                  <?php if($format == 3) { ?>
                  <span class="input-group-addon">
                     <span id="prefix_year" class="format-n-yy"><?php echo $yy; ?></span>
                  </span>
                  <?php } else if($format == 4) { ?>
                   <span class="input-group-addon">
                     <span id="prefix_month" class="format-mm-yyyy"><?php echo $mm; ?></span>
                     /
                     <span id="prefix_year" class="format-mm-yyyy"><?php echo $yyyy; ?></span>
                  </span>
                  <?php } ?>
               </div>
            </div>

            <div class="row">
               <div class="col-md-6">
                  <?php $value = (isset($inspection) ? _d($inspection->date) : _d(date('Y-m-d'))); ?>
                  <?php echo render_date_input('date','inspection_add_edit_date',$value); ?>
               </div>
               <div class="col-md-6">
                 <div class="form-group select-placeholder">
                    <label class="control-label"><?php echo _l('inspection_status'); ?></label>
                    <select class="selectpicker display-block mbot15" name="status" data-width="100%" data-none-selected-text="<?php echo _l('dropdown_non_selected_tex'); ?>">
                       <?php foreach($inspection_statuses as $status){ ?>
                       <option value="<?php echo $status; ?>" <?php if(isset($inspection) && $inspection->status == $status){echo 'selected';} ?>><?php echo format_inspection_status($status,'',false); ?></option>
                       <?php } ?>
                    </select>
                 </div>
              </div>
            </div>
            <div class="clearfix mbot15"></div>
            <?php $rel_id = (isset($inspection) ? $inspection->id : false); ?>
            <?php
                  if(isset($custom_fields_rel_transfer)) {
                      $rel_id = $custom_fields_rel_transfer;
                  }
             ?>
         </div>
         <div class="col-md-6">
            <div class="panel_s no-border">
               <div class="row">
                  <div class="col-md-12">
                    <?php $value = (isset($inspection) ? $inspection->reference_no : ''); ?>
                    <?php echo render_input('reference_no','reference_no',$value); ?>
                  </div>
               </div>
            </div>
         </div>
      </div>
   </div>
   
   <div class="row">
    <div class="col-md-12 mtop15">
      <div class="panel-body bottom-transaction">
        <div class="btn-bottom-toolbar text-right">
          <div class="btn-group dropup">
            <button type="button" class="btn-tr btn btn-info inspection-form-submit transaction-submit">
              <?php echo _l('submit'); ?>
            </button>
          <button type="button"
            class="btn btn-info dropdown-toggle"
            data-toggle="dropdown"
            aria-haspopup="true"
            aria-expanded="false">
            <span class="caret"></span>
          </button>
          <ul class="dropdown-menu dropdown-menu-right width200">
            <li>
              <a href="#" class="inspection-form-submit save-and-send transaction-submit">
                <?php echo _l('save_and_send'); ?>
              </a>
            </li>
            <?php if(!isset($inspection)) { ?>
              <li>
                <a href="#" class="inspection-form-submit save-and-send-later transaction-submit">
                  <?php echo _l('save_and_send_later'); ?>
                </a>
              </li>
            <?php } ?>
          </ul>
        </div>
      </div>
    </div>
    <div class="btn-bottom-pusher"></div>
  </div>
</div>
</div>
