<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<table class="table dt-table table-inspections" data-order-col="1" data-order-type="desc">
    <thead>
        <tr>
            <th><?php echo _l('inspection_number'); ?> #</th>
            <th><?php echo _l('inspection_list_project'); ?></th>
            <th><?php echo _l('inspection_list_date'); ?></th>
            <th><?php echo _l('inspection_list_status'); ?></th>

        </tr>
    </thead>
    <tbody>
        <?php foreach($inspections as $inspection){ ?>
            <tr>
                <td><?php echo '<a href="' . site_url("inspections/show/" . $inspection["id"] . '/' . $inspection["hash"]) . '">' . format_inspection_number($inspection["id"]) . '</a>'; ?></td>
                <td><?php echo $inspection['name']; ?></td>
                <td><?php echo _d($inspection['date']); ?></td>
                <td><?php echo format_inspection_status($inspection['status']); ?></td>
            </tr>
        <?php } ?>
    </tbody>
</table>
