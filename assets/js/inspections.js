// Init single inspection
function init_inspection(id) {
    load_small_table_item(id, '#inspection', 'inspectionid', 'inspections/get_inspection_data_ajax', '.table-inspections');
}


// Validates inspection add/edit form
function validate_inspection_form(selector) {

    selector = typeof (selector) == 'undefined' ? '#inspection-form' : selector;

    appValidateForm($(selector), {
        clientid: {
            required: {
                depends: function () {
                    var customerRemoved = $('select#clientid').hasClass('customer-removed');
                    return !customerRemoved;
                }
            }
        },
        date: 'required',
        office_id: 'required',
        number: {
            required: true
        }
    });

    $("body").find('input[name="number"]').rules('add', {
        remote: {
            url: admin_url + "inspections/validate_inspection_number",
            type: 'post',
            data: {
                number: function () {
                    return $('input[name="number"]').val();
                },
                isedit: function () {
                    return $('input[name="number"]').data('isedit');
                },
                original_number: function () {
                    return $('input[name="number"]').data('original-number');
                },
                date: function () {
                    return $('body').find('.inspection input[name="date"]').val();
                },
            }
        },
        messages: {
            remote: app.lang.inspection_number_exists,
        }
    });

}


// Get the preview main values
function get_inspection_item_preview_values() {
    var response = {};
    response.description = $('.main textarea[name="description"]').val();
    response.long_description = $('.main textarea[name="long_description"]').val();
    response.qty = $('.main input[name="quantity"]').val();
    return response;
}

// From inspection table mark as
function inspection_mark_as(status_id, inspection_id) {
    var data = {};
    data.status = status_id;
    data.inspectionid = inspection_id;
    $.post(admin_url + 'inspections/update_inspection_status', data).done(function (response) {
        //table_inspections.DataTable().ajax.reload(null, false);
        reload_inspections_tables();
    });
}

// Reload all inspections possible table where the table data needs to be refreshed after an action is performed on task.
function reload_inspections_tables() {
    var av_inspections_tables = ['.table-inspections', '.table-program_items', '.table-inspection_items'];
    $.each(av_inspections_tables, function (i, selector) {
        if ($.fn.DataTable.isDataTable(selector)) {
            $(selector).DataTable().ajax.reload(null, false);
        }
    });
}


function inspections_add_inspection_item(inspection_id, id) {
    var data = {};
    data.inspection_id = inspection_id;
    data.id = id;
    console.log(data);
    $.post(admin_url + 'inspections/add_inspection_item', data).done(function (response) {
        reload_inspections_tables();
    });
}

function inspections_remove_inspection_item(id) {
    var data = {};
    data.id = id;
    console.log(data);
    $.post(admin_url + 'inspections/remove_inspection_item', data).done(function (response) {
        reload_inspections_tables();
    });
}


function inspections_load_inspection_template(id) {
    var data = {};
    data.id = id;
    console.log(data);
    $.post(admin_url + 'inspections/load_inspection_template', data).done(function (response) {
        //reload_inspections_tables();
    });
}

// Init inspection modal and get data from server
function init_inspection_items_modal(inspection_id, jenis_pesawat_id) {
  var queryStr = "";
  var $leadModal = $("#lead-modal");
  var $inspectionAddEditModal = $("#_inspection_modal");
  if ($leadModal.is(":visible")) {
    queryStr +=
      "?opened_from_lead_id=" + $leadModal.find('input[name="leadid"]').val();
    $leadModal.modal("hide");
  } else if ($inspectionAddEditModal.attr("data-lead-id") != undefined) {
    queryStr +=
      "?opened_from_lead_id=" + $inspectionAddEditModal.attr("data-lead-id");
  }

  requestGet(admin_url + "inspections/get_inspection_item_data/" + inspection_id + '/' + jenis_pesawat_id)
    .done(function (response) {
      _inspection_append_html(response);
      /*
      if (typeof jenis_pesawat_id != "undefined") {
        setTimeout(function () {
          $('[data-inspection-jenis_pesawat-href-id="' + jenis_pesawat_id + '"]').click();
        }, 1000);
      }
      */

    })
    .fail(function (data) {
      $("#inspection-modal").modal("hide");
      alert_float("danger", data.responseText);
    });
}

// General function to append inspection html returned from request
function _inspection_append_html(html) {
  var $inspectionModal = $("#inspection-modal");

  $inspectionModal.find(".data").html(html);
  //init_inspections_checklist_items(false, inspection_id);
  //recalculate_checklist_items_progress();
  //do_inspection_checklist_items_height();

  setTimeout(function () {
    $inspectionModal.modal("show");
    // Init_tags_input is trigged too when inspection modal is shown
    // This line prevents triggering twice.
    if ($inspectionModal.is(":visible")) {
      init_tags_inputs();
    }
    //init_form_reminder("inspection");
    //fix_inspection_modal_left_col_height();

    // Show the comment area on mobile when inspection modal is opened
    // Because the user may want only to upload file, but if the comment textarea is not focused the dropzone won't be shown

    if (is_mobile()) {
      //init_new_inspection_comment(true);
    }
  }, 150);
}