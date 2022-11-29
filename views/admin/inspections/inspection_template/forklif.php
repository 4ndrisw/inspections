<?php

defined('BASEPATH') or exit('No direct script access allowed');

echo form_hidden('inspection_item_id', $inspection_item->id);
echo form_hidden('rel_id', $inspection->id);
echo form_hidden('addedfrom', get_staff_user_id());
?>
<div class="row">

	<div class="panel_s">
	  <div class="panel-body bg-light">
	    <p class="panel-text">Dari Master Data Peralatan</p>
	    <div class="row">
			<div class="col-md-12">
				<?php $value = (!is_null($inspection_item_data->nama_pesawat) ? $inspection_item_data->nama_pesawat : $inspection_item->nama_pesawat); ?>
		        <?php echo render_input('nama_pesawat','nama_pesawat',$value); ?>
			</div>

			<div class="col-md-6">
				<?php $value = (isset($inspection_item_data) ? $inspection_item_data->nomor_seri : ''); ?>
		        <?php echo render_input('nomor_seri','nomor_seri',$value); ?>
			</div>
			<div class="col-md-6">
				<?php $value = (isset($inspection_item_data) ? $inspection_item_data->nomor_unit : ''); ?>
		        <?php echo render_input('nomor_unit','nomor_unit',$value); ?>
			</div>
			<div class="col-md-12">
				<?php $value = (isset($inspection_item_data) ? $inspection_item_data->lokasi : ''); ?>
		        <?php echo render_input('lokasi','lokasi',$value); ?>
			</div>
		</div>
	    <p class="panel-text">Mengubah data ini tidak mengubah data master, untuk mengubah data master silahkan dari menu peralatan.</p>		
	  </div>
	</div>

	<hr />

	<div class="col-md-6 form-group-no-margin tooltip-right">
		<i class="fa fa-question-circle pull-left" data-toggle="tooltip" data-title="<?php echo _l('nomor suket sebelumnya jika ada'); ?>"></i>
		<?php $value = (isset($inspection_item_data) ? $inspection_item_data->nomor_pengesahan : ''); ?>
        <?php echo render_input('nomor_pengesahan','nomor_pengesahan',$value); ?>
	</div>
	<div class="col-md-6">
		<?php $value = (isset($inspection_item_data) ? $inspection_item_data->nomor_laporan : ''); ?>
        <?php echo render_input('nomor_laporan','nomor_laporan',$value); ?>
	</div>

	<div class="col-md-6">
		<?php $value = (isset($inspection_item_data) ? $inspection_item_data->jenis_tegangan : ''); ?>
        <?php echo render_input('jenis_tegangan','jenis_tegangan',$value); ?>
	</div>
	<div class="col-md-6">
		<?php $value = (isset($inspection_item_data) ? $inspection_item_data->jenis_arus : ''); ?>
        <?php echo render_input('jenis_arus','jenis_arus',$value); ?>
	</div>

	<div class="col-md-6">
		<?php $value = (isset($inspection_item_data) ? $inspection_item_data->tempat_pembuatan : ''); ?>
        <?php echo render_input('tempat_pembuatan','tempat_pembuatan',$value); ?>
	</div>
	<div class="col-md-6">
		<?php $value = (isset($inspection_item_data) ? $inspection_item_data->tahun_pembuatan : ''); ?>
        <?php echo render_input('tahun_pembuatan','tahun_pembuatan',$value, 'number', ['min'=>1980]); ?>
	</div>

	<div class="col-md-6">
		<?php $value = (isset($inspection_item_data) ? $inspection_item_data->merk : ''); ?>
        <?php echo render_input('merk','merk',$value); ?>
	</div>
	<div class="col-md-6">
		<?php $value = (isset($inspection_item_data) ? $inspection_item_data->type_model : ''); ?>
        <?php echo render_input('type_model','type_model',$value); ?>
	</div>

	<div class="col-md-6">
		<?php $value = (isset($inspection_item_data) ? $inspection_item_data->kapasitas : ''); ?>
        <?php echo render_input('kapasitas','kapasitas',$value); ?>
	</div>
	<div class="col-md-6">
		<?php $value = (isset($inspection_item_data) ? $inspection_item_data->digunakan_untuk : ''); ?>
        <?php echo render_input('digunakan_untuk','digunakan_untuk',$value); ?>
	</div>

	<div class="col-md-12">
		<?php $value = (isset($inspection_item_data) ? $inspection_item_data->temuan : ''); ?>
        <?php echo render_textarea('temuan','temuan',$value); ?>
	</div>
	<div class="col-md-12">
		<?php $value = (isset($inspection_item_data) ? $inspection_item_data->kesimpulan : ''); ?>
        <?php echo render_textarea('kesimpulan','kesimpulan',$value); ?>
 	</div>

</div>
      
