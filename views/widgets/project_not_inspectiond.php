<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
    $CI = &get_instance();
    $CI->load->model('inspections/inspections_model');
    $inspections = $CI->inspections_model->get_project_not_inspectiond(get_staff_user_id());
?>

<div class="widget" id="widget-<?php echo create_widget_id(); ?>" data-name="<?php echo _l('project_not_inspectiond'); ?>">
    <?php if(staff_can('view', 'inspections') || staff_can('view_own', 'inspections')) { ?>
    <div class="panel_s inspections-expiring">
        <div class="panel-body padding-10">
            <p class="padding-5"><?php echo _l('project_not_inspectiond'); ?></p>
            <hr class="hr-panel-heading-dashboard">
            <?php if (!empty($inspections)) { ?>
                <div class="table-vertical-scroll">
                    <a href="<?php echo admin_url('inspections'); ?>" class="mbot20 inline-block full-width"><?php echo _l('home_widget_view_all'); ?></a>
                    <table id="widget-<?php echo create_widget_id(); ?>" class="table dt-table" data-order-col="2" data-order-type="desc">
                        <thead>
                            <tr>
                                <th>No.</th>
                                <th class="<?php echo (isset($client) ? 'not_visible' : ''); ?>"><?php echo _l('inspection_list_project'); ?></th>
                                <th><?php echo _l('inspection_list_client'); ?></th>
                                <th><?php echo _l('inspection_list_date'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $i = 1; ?>
                            <?php foreach ($inspections as $inspection) { ?>
                                <tr>
                                    <td> <?php echo $i; ?>
                                    </td>
                                    <td>
                                        <?php //echo $inspection['name']; ?>
                                        <?php echo '<a href="' . admin_url("projects/view/" . $inspection["id"]) . '">' . $inspection['name'] . '</a>'; ?>
                                    </td>
                                    <td>
                                        <?php echo '<a href="' . admin_url("clients/client/" . $inspection["userid"]) . '">' . $inspection["company"] . '</a>'; ?>
                                    </td>
                                    <td>
                                        <?php echo _d($inspection['start_date']); ?>
                                    </td>
                                </tr>
                            <?php $i++; ?>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            <?php } else { ?>
                <div class="text-center padding-5">
                    <i class="fa fa-check fa-5x" aria-hidden="true"></i>
                    <h4><?php echo _l('no_project_not_inspectiond',["7"]) ; ?> </h4>
                </div>
            <?php } ?>
        </div>
    </div>
    <?php } ?>
</div>
