<?php defined('BASEPATH') or exit('No direct script access allowed');
   if ($inspection['status'] == $status) { ?>
<li data-inspection-id="<?php echo $inspection['id']; ?>" class="<?php if($inspection['invoiceid'] != NULL){echo 'not-sortable';} ?>">
   <div class="panel-body">
      <div class="row">
         <div class="col-md-12">
            <h4 class="bold pipeline-heading"><a href="<?php echo admin_url('inspections/list_inspections/'.$inspection['id']); ?>" onclick="inspection_pipeline_open(<?php echo $inspection['id']; ?>); return false;"><?php echo format_inspection_number($inspection['id']); ?></a>
               <?php if(has_permission('inspections','','edit')){ ?>
               <a href="<?php echo admin_url('inspections/inspection/'.$inspection['id']); ?>" target="_blank" class="pull-right"><small><i class="fa fa-pencil-square-o" aria-hidden="true"></i></small></a>
               <?php } ?>
            </h4>
            <span class="inline-block full-width mbot10">
            <a href="<?php echo admin_url('clients/client/'.$inspection['clientid']); ?>" target="_blank">
            <?php echo $inspection['company']; ?>
            </a>
            </span>
         </div>
         <div class="col-md-12">
            <div class="row">
               <div class="col-md-8">
                  <span class="bold">
                  <?php echo _l('inspection_total') . ':' . app_format_money($inspection['total'], $inspection['currency_name']); ?>
                  </span>
                  <br />
                  <?php echo _l('inspection_data_date') . ': ' . _d($inspection['date']); ?>
                  <?php if(is_date($inspection['expirydate']) || !empty($inspection['expirydate'])){
                     echo '<br />';
                     echo _l('inspection_data_expiry_date') . ': ' . _d($inspection['expirydate']);
                     } ?>
               </div>
               <div class="col-md-4 text-right">
                  <small><i class="fa fa-paperclip"></i> <?php echo _l('inspection_notes'); ?>: <?php echo total_rows(db_prefix().'notes', array(
                     'rel_id' => $inspection['id'],
                     'rel_type' => 'inspection',
                     )); ?></small>
               </div>
               <?php $tags = get_tags_in($inspection['id'],'inspection');
                  if(count($tags) > 0){ ?>
               <div class="col-md-12">
                  <div class="mtop5 kanban-tags">
                     <?php echo render_tags($tags); ?>
                  </div>
               </div>
               <?php } ?>
            </div>
         </div>
      </div>
   </div>
</li>
<?php } ?>
