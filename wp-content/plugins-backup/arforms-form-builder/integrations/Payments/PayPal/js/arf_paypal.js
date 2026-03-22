"use strict";

function arf_ChangeID(id) {
    document.getElementById('delete_entry_id').value = id;
    arfchangedeletemodalwidth('arfdeletemodabox');
}

function arfpaypalactionfunc(act, id) {
    
    if (act == 'delete') {
        id = document.getElementById('delete_entry_id').value;
    }
    var paypal_order_nonce = jQuery('#arf_paypal_order_list_nonce').val();
    var form_id = jQuery('#arf_paypal_forms_dropdown').val();
    var start_date = jQuery('#datepicker_from').val();
    var end_date = jQuery('#datepicker_to').val();
    var msg_for_blank_table = __NO_FORM_FOUND_MSG;
    jQuery.ajax({
        type: "POST",
        url: ajaxurl,
        dataType: 'json',
        data: "action=arf_paypal_delete_order&act=" + act + "&id=" + id + "&form_id=" + form_id + '&wp_arflite_paypal_nonce=' +paypal_order_nonce ,
        beforeSend: function() {
            jQuery(".arf_loader_icon_wrapper").show();
        },
        success: function(response) {
            jQuery(".arf_loader_icon_wrapper").hide();
            if (response.errors.length > 0) {
                jQuery('#form_error_message_des').html(response.errors[0]);
                arflite_error_msg();
            } else {
                jQuery("#form_success_message_desc").html(response.message);
                arflite_success_msg();
            }
            jQuery('#datepicker_from').val(start_date);
            jQuery('#datepicker_to').val(end_date);
            var paypal_order_table = jQuery('#arf_paypal_order_form #example').DataTable();
            
            var paypal_order_table_new = jQuery("#arf_paypal_order_form #example");

            paypal_order_table.clear();
            paypal_order_table.destroy();
            

            var dtobj = {
                    "oLanguage": {
                        "sProcessing": "",
                        "sEmptyTable": msg_for_blank_table,
                        "sZeroRecords": msg_for_blank_table
                    },
                    "sDom": '<"H"lfr>t<"footer"ip>',
                    "sPaginationType": "four_button",
                    "bJQueryUI": true,
                    "bPaginate": true,
                    "bAutoWidth": false,
                    "bProcessing": true,
                    "bServerSide": true,
                    "sAjaxSource": ajaxurl,
                    "sServerMethod": "POST",
                    "fnServerParams": function (aoData) {
                        var form_id = jQuery('#arf_paypal_forms_dropdown').val();
                        var start_date = jQuery('#datepicker_from').val();
                        var end_date = jQuery('#datepicker_to').val();
                        aoData.push(
                            {'name': 'action', 'value': 'arf_retrieve_paypal_transaction_data'},
                            {'name': 'form_id', 'value': form_id},
                            {'name': 'start_date', 'value': start_date},
                            {'name': 'end_date', 'value': end_date},
                        );
                    },
                    "fnPreDrawCallback": function () {
                            jQuery("#arf_full_width_loader").show();
                        },
                    "fnDrawCallback": function (oSettings) {
                        jQuery("#arf_full_width_loader").hide();
                        jQuery('.arfhelptip').tipso('destroy');
                        jQuery('.arfhelptip').tipso({
                            position: 'top',
                            maxWidth: '400',
                            useTitle: true,
                            background: '#444444',
                            color: '#ffffff',
                            width: 'auto'
                        });
                    },
                    "aoColumnDefs": [{
                        "bVisible": false,
                        "aTargets": []
                    }, {
                        "bSortable": false,
                        "aTargets": [0, 8]
                    }],
                    "ordering": true,
                    "order":[[1,'desc']],
                    "aoColumnDefs": [            
                        {"bSortable": false, "aTargets": [0,4]},
                        {"sClass": "", "sWidth": "30px", "aTargets":[0]}, 
                        {"sClass": "","aTargets":[1]}, 
                        {"sClass": "","aTargets":[2]}, 
                        {"sClass": "","aTargets":[3]}, 
                        {"sClass": "","aTargets":[4]}, 
                        {"sClass": "","aTargets":[5]}, 
                        {"sClass": "","aTargets":[6]}, 
                        {"sClass": "","aTargets":[7]}, 
                        {"sClass": "arf_action_cell","aTargets":[8]} 
                    ],
                    "oColVis": {
                        "aiExclude": [0, 8]
                    },
                };

            paypal_order_table_new.dataTable(dtobj);

            jQuery('#arf_paypal_orders .arfhelptip').tipso('destroy');
            if (jQuery.isFunction(jQuery().tipso)) {
                jQuery('#arf_paypal_orders .arfhelptip').tipso({
                    position: 'top',
                    width: 'auto',
                    maxWidth: '400',
                    useTitle: true,
                    background: '#444444',
                    color: '#ffffff'
                });
            }
        }
    });
    if (act == 'delete') {
        jQuery('[data-dismiss="arfmodal"]').trigger("click");
    }
    return false;
}

function arf_paypal_order_bulk_act() {
    var str = jQuery('#arf_paypal_order').serialize();
    var p_form_id = jQuery('#arf_paypal_forms_dropdown').val();
    var start_date = jQuery('#datepicker_from').val();
    var end_date = jQuery('#datepicker_to').val();
    var action1 = jQuery("#arf_bulk_action_one").val();
    var action2 = jQuery("#arf_bulk_action_two").val();
    var arf_paypal_nonce = jQuery('#arf_paypal_order_list_nonce').val();

    var chk_count = jQuery("input[name='item-action[]']:checked").length;
    var msg_for_blank_table = __NO_FORM_FOUND_MSG;
    var final_action = (action1 != '' && action1 != '-1') ? action1 : action2;
    if (final_action == '' || final_action == '-1') {
        jQuery("#form_error_message_des").html(__VALID_ACTION);
        arflite_error_msg();
        return false;
    } else if (final_action == 'bulk_delete') {
        if (chk_count == 0) {
            jQuery("#form_error_message_des").html(__SELECT_RECORD);
            arflite_error_msg();
            return false;
        } else {
            jQuery("#delete_bulk_transaction_message").addClass('arfactive');
            jQuery("#delete_bulk_transaction_message").parent('.arf_modal_overlay').addClass('arfactive');
        }
    } else {
        jQuery.ajax({
            type: "POST",
            url: ajaxurl,
            dataType: 'json',
            data: "action=arf_paypal_order_bulk_act&p_form_id=" + p_form_id + "&" + str + "&start_date=" + start_date + "&end_date=" + end_date + "&wp_arflite_paypal_nonce=" + arf_paypal_nonce ,
            beforeSend: function() {
                jQuery(".arf_loader_icon_wrapper").show();
            },
            success: function(response) {
                jQuery(".arf_loader_icon_wrapper").hide();
                jQuery("#arf_paypal_order")[0].reset();
                if (response.errors.length > 0) {
                    if (jQuery('#arf_old_version').length > 0) {} else {
                        jQuery('#form_error_message_des').html(response.errors[0]);
                        arflite_error_msg();
                    }
                } else if (response.message == 'csv') {
                    window.location.href = response.url;
                } else {
                    if (jQuery("#arf_old_version").length > 0) {} else {
                        jQuery("#form_success_message_desc").html(response.message);
                        arflite_success_msg();
                    }
                }
                jQuery('#datepicker_from').val(start_date);
                jQuery('#datepicker_to').val(end_date);
                var paypal_order_table = jQuery('#arf_paypal_order_form #example').DataTable();
                
                paypal_order_table.clear();
                paypal_order_table.destroy();
                
                var paypal_order_table_new = jQuery("#arf_paypal_order_form #example");

                var dtobj = {
                    "oLanguage": {
                        "sProcessing": "",
                        "sEmptyTable": msg_for_blank_table,
                        "sZeroRecords": msg_for_blank_table
                    },
                    "sDom": '<"H"lfr>t<"footer"ip>',
                    "sPaginationType": "four_button",
                    "bJQueryUI": true,
                    "bPaginate": true,
                    "bAutoWidth": false,
                    "aoColumnDefs": [{
                        "bVisible": false,
                        "aTargets": []
                    }, {
                        "bSortable": false,
                        "aTargets": [0, 8]
                    }, {
                        "sClass": "arf_action_cell",
                        "aTargets": [8]
                    }],
                    "ordering": true,
                    "order":[[1,'desc']],
                    "oColVis": {
                        "aiExclude": [0, 8]
                    },
                };

                
                dtobj.language = {
                    "searchPlaceholder":__ARF_SEARCH_PLACEHOLDER,
                    "search":"",
                };
                
                paypal_order_table_new.dataTable(dtobj);

                if(response.gridData.length > 0){
                    paypal_order_table_new.fnAddData(response.gridData);
                }

                jQuery('#arf_paypal_orders .arfhelptip').tipso('destroy');
                if (jQuery.isFunction(jQuery().tipso)) {
                    jQuery('#arf_paypal_orders .arfhelptip').tipso({
                        position: 'top',
                        width: 'auto',
                        maxWidth: '400',
                        useTitle: true,
                        background: '#444444',
                        color: '#ffffff'
                    });
                }
            }
        });
    }
    return false;
}
jQuery(document).on('click', '.arf_bulk_delete_transaction_close_btn', function() {
    jQuery('.arf_modal_overlay.arfactive').removeClass('arfactive');
});

function arf_delete_bulk_transaction(is_delete) {
    if (is_delete) {
        var str = jQuery('#arf_paypal_order').serialize();
        var p_form_id = jQuery('#arf_paypal_forms_dropdown').val();
        var start_date = jQuery('#datepicker_from').val();
        var end_date = jQuery('#datepicker_to').val();
        var arf_order_bulk_delete_nonce =  jQuery('#arf_paypal_order_list_nonce').val();
        var msg_for_blank_table = __NO_FORM_FOUND_MSG;
        jQuery.ajax({
            type: "POST",
            url: ajaxurl,
            data: "action=arf_paypal_order_bulk_act&p_form_id=" + p_form_id + "&" + str + "&start_date=" + start_date + "&end_date=" + end_date + '&wp_arflite_paypal_nonce=' + arf_order_bulk_delete_nonce ,
            dataType: 'json',
            beforeSend: function() {
                jQuery(".arf_loader_icon_wrapper").show();
            },
            success: function(response) {
                jQuery(".arf_loader_icon_wrapper").hide();
                if (response.errors.length > 0) {
                    if (jQuery('#arf_old_version').length > 0) {} else {
                        jQuery('#form_error_message_des').html(response.errors[0]);
                        arflite_error_msg();
                    }
                } else if (response.msg == 'csv') {
                    window.location.href = response.url;
                } else {
                    if (jQuery("#arf_old_version").length > 0) {} else {
                        jQuery("#form_success_message_desc").html(response.message);
                        arflite_success_msg();
                    }
                }
                jQuery('#datepicker_from').val(start_date);
                jQuery('#datepicker_to').val(end_date);
                var paypal_order_table = jQuery('#arf_paypal_order_form #example').DataTable();
                var paypal_order_table_new = jQuery("#arf_paypal_order_form #example");

                paypal_order_table.clear();
                paypal_order_table.destroy();

                var dtobj = {
                    "oLanguage": {
                        "sProcessing": "",
                        "sEmptyTable": msg_for_blank_table,
                        "sZeroRecords": msg_for_blank_table
                    },
                    "sDom": '<"H"lfr>t<"footer"ip>',
                    "sPaginationType": "four_button",
                    "bJQueryUI": true,
                    "bPaginate": true,
                    "bAutoWidth": false,
                    "bProcessing": true,
                    "bServerSide": true,
                    "sAjaxSource": ajaxurl,
                    "sServerMethod": "POST",
                    "fnServerParams": function (aoData) {
                        var form_id = jQuery('#arf_paypal_forms_dropdown').val();
                        var start_date = jQuery('#datepicker_from').val();
                        var end_date = jQuery('#datepicker_to').val();
                        aoData.push(
                            {'name': 'action', 'value': 'arf_retrieve_paypal_transaction_data'},
                            {'name': 'form_id', 'value': form_id},
                            {'name': 'start_date', 'value': start_date},
                            {'name': 'end_date', 'value': end_date},
                        );
                    },
                    "fnPreDrawCallback": function () {
                            jQuery("#arf_full_width_loader").show();
                        },
                    "fnDrawCallback": function (oSettings) {
                        jQuery("#arf_full_width_loader").hide();
                        jQuery('.arfhelptip').tipso('destroy');
                        jQuery('.arfhelptip').tipso({
                            position: 'top',
                            maxWidth: '400',
                            useTitle: true,
                            background: '#444444',
                            color: '#ffffff',
                            width: 'auto'
                        });
                    },
                    "aoColumnDefs": [
                        {"bSortable": false, "aTargets": [0, 6]},
                        {"sClass": "cbox", "sWidth": "30px", "aTargets":[0]}, 
                        {"sClass": "","aTargets":[1]}, 
                        {"sClass": "","aTargets":[2]}, 
                        {"sClass": "","aTargets":[3]}, 
                        {"sClass": "","aTargets":[4]}, 
                        {"sClass": "","aTargets":[5]}, 
                        {"sClass": "arf_action_cell","aTargets":[6]} 
                    ],
                    "language":{
                        "searchPlaceholder": __ARF_SEARCH_PLACEHOLDER,
                        "search":"",
                    },
                    "ordering": true,
                    "order":[[1,'desc']],
                    "oColVis": {
                        "aiExclude": [0, 6]
                    },
                };
                paypal_order_table_new.dataTable(dtobj);

                jQuery('#arf_paypal_orders .arfhelptip').tipso('destroy');
                if (jQuery.isFunction(jQuery().tipso)) {
                    jQuery('#arf_paypal_orders .arfhelptip').tipso({
                        position: 'top',
                        width: 'auto',
                        maxWidth: '400',
                        useTitle: true,
                        background: '#444444',
                        color: '#ffffff'
                    });
                }
            }
        });
    }
    jQuery('.arf_modal_overlay.arfactive').removeClass('arfactive');
}

function arfpaypalformactionfunc(act, id) {
    if (act == 'delete') {
        id = document.getElementById('delete_entry_id').value;
    }
    var paypal_form_list_nonce = jQuery('#arf_paypal_form_list').val();
    var msg_for_blank_table = __NO_FORM_FOUND_MSG;
    jQuery.ajax({
        type: "POST",
        url: ajaxurl,
        data: "action=arf_paypal_delete_form&act=" + act + "&id=" + id +"&wp_arf_paypal_forms_nonce=" + paypal_form_list_nonce,
        dataType: 'json',
        beforeSend: function() {
            jQuery(".arf_loader_icon_wrapper").show();
        },
        success: function(msg) {
            jQuery(".arf_loader_icon_wrapper").hide();
            if (msg.errors.length > 0) {
                if (jQuery("#arf_old_version").length > 0 && jQuery('#arf_old_version').val() == 'true') {
                    jQuery("#arf_error_message #error_message").html(msg.errors[0]);
                    jQuery("#arf_error_message").show();
                    jQuery("#arf_error_message").fadeOut(5000);
                } else {
                    jQuery("#form_error_message_des").html(msg.errors[0]);
                    arflite_error_msg();
                }
            } else {
                if (jQuery("#arf_old_version").length > 0 && jQuery('#arf_old_version').val() == 'true') {
                    console.log('delete1');
                    jQuery('.arf_success_message').html(msg.message);
                    jQuery("#success_message").show();
                    jQuery("#success_message").fadeOut(5000);
                } else {
                    console.log('delete2');
                    jQuery("#form_success_message_desc").html(msg.message);
                    arflite_success_msg();
                }
            }
            var paypal_list_table = jQuery('#arf_paypal_list_form #example').DataTable();
            
            var paypal_list_table_new = jQuery("#arf_paypal_list_form #example");

            paypal_list_table.clear();
            paypal_list_table.destroy();
            
            var dtobj = {
                "oLanguage": {
                    "sProcessing": "",
                    "sEmptyTable": msg_for_blank_table,
                    "sZeroRecords": msg_for_blank_table
                },
                "sDom": '<"H"lfr>t<"footer"ip>',
                "sPaginationType": "four_button",
                "bJQueryUI": true,
                "bPaginate": true,
                "bAutoWidth": false,
                "bProcessing": true,
                "bServerSide": true,
                "sAjaxSource": ajaxurl,
                "sServerMethod": "POST",
                "fnServerParams": function (aoData) {
                    aoData.push(
                        {'name': 'action', 'value': 'arf_retrieve_paypal_config_data'},
                    );
                },
                "fnPreDrawCallback": function () {
                        jQuery("#arf_full_width_loader").show();
                    },
                "fnDrawCallback": function (oSettings) {
                    jQuery("#arf_full_width_loader").hide();
                    jQuery('.arfhelptip').tipso('destroy');
                    jQuery('.arfhelptip').tipso({
                        position: 'top',
                        maxWidth: '400',
                        useTitle: true,
                        background: '#444444',
                        color: '#ffffff',
                        width: 'auto'
                    });
                },
                "aoColumnDefs": [
                    {"bSortable": false, "aTargets": [0, 6]},
                    {"sClass": "cbox", "sWidth": "30px", "aTargets":[0]}, 
                    {"sClass": "","aTargets":[1]}, 
                    {"sClass": "","aTargets":[2]}, 
                    {"sClass": "","aTargets":[3]}, 
                    {"sClass": "","aTargets":[4]}, 
                    {"sClass": "","aTargets":[5]}, 
                    {"sClass": "arf_action_cell","aTargets":[6]} 
                ],
                "language":{
                    "searchPlaceholder": __ARF_SEARCH_PLACEHOLDER,
                    "search":"",
                },
                "ordering": true,
                "order":[[1,'desc']],
                "oColVis": {
                    "aiExclude": [0, 6]
                },
            };
            paypal_list_table_new.dataTable(dtobj);


            
            
            

            jQuery("form#arf_paypal_forms")[0].reset();
            jQuery('#arf_paypal_forms .arfhelptip').tipso('destroy');
            if (jQuery.isFunction(jQuery().tipso)) {
                jQuery('#arf_paypal_forms .arfhelptip').tipso({
                    position: 'top',
                    width: 'auto',
                    maxWidth: '400',
                    useTitle: true,
                    background: '#444444',
                    color: '#ffffff'
                });
            }
        },
        error: function() {
            jQuery(".arf_loader_icon_wrapper").hide();
            jQuery("form#arf_paypal_forms")[0].reset();
        }
    });
    if (act == 'delete') {
        jQuery('[data-dismiss="arfmodal"]').trigger("click");
    }
    return false;
}

function arf_paypal_form_bulk_act() {
    var str = jQuery('#arf_paypal_forms').serialize();
    var action1 = jQuery("#arf_bulk_action_one").val();
    var action2 = jQuery("#arf_bulk_action_two").val();
    var paypal_bulk_nonce = jQuery("#arf_paypal_form_list").val();
    var chk_count = jQuery("input[name='item-action[]']:checked").length;
    var final_action = (action1 != '' && action1 != '-1') ? action1 : action2;
    var msg_for_blank_table = __NO_FORM_FOUND_MSG;
    if (final_action == '' || final_action == '-1' || final_action == '-2') {
        jQuery("#form_error_message_des").html(__VALID_ACTION);
        arflite_error_msg();
        return false;
    } else if (final_action == 'bulk_delete') {
        if (chk_count == 0) {
            jQuery("#form_error_message_des").html(__SELECT_RECORD);
            arflite_error_msg();
            return false;
        } else {
            jQuery("#delete_bulk_paypal_form_message").addClass('arfactive');
            jQuery("#delete_bulk_paypal_form_message").parent('.arf_modal_overlay').addClass('arfactive');
        }
    } else {
        jQuery.ajax({
            type: "POST",
            url: ajaxurl,
            data: "action=arf_paypal_form_bulk_act&" + str + "&wp_paypal_bulk_nonce=" + paypal_bulk_nonce,
            dataType: 'json',
            beforeSend: function() {
                jQuery(".arf_loader_icon_wrapper").show();
            },
            success: function(msg) {
                jQuery(".arf_loader_icon_wrapper").hide();
                if (msg.errors.length > 0) {
                    if (jQuery("#arf_old_version").length > 0 && jQuery('#arf_old_version').val() == 'true') {
                        jQuery("#arf_error_message #error_message").html(msg.errors[0]);
                        jQuery("#arf_error_message").show();
                        jQuery("#arf_error_message").fadeOut(5000);
                    } else {
                        jQuery("#form_error_message_des").html(msg.errors[0]);
                        arflite_error_msg();
                    }
                } else {
                    if (jQuery("#arf_old_version").length > 0 && jQuery('#arf_old_version').val() == 'true') {
                        jQuery('.arf_success_message').html(msg.message);
                        jQuery("#success_message").show();
                        jQuery("#success_message").fadeOut(5000);
                    } else {
                        jQuery("#form_success_message_desc").html(msg.message);
                        arflite_success_msg();
                    }
                }
                var paypal_list_table = jQuery('#arf_paypal_list_form #example').DataTable();
                var paypal_list_table_new = jQuery("#arf_paypal_list_form #example");

                paypal_list_table.clear();
                paypal_list_table.destroy();

                var dtobj = {
                    "oLanguage": {
                        "sProcessing": "",
                        "sEmptyTable": msg_for_blank_table,
                        "sZeroRecords": msg_for_blank_table
                    },
                    "sDom": '<"H"lfr>t<"footer"ip>',
                    "sPaginationType": "four_button",
                    "bJQueryUI": true,
                    "bPaginate": true,
                    "bAutoWidth": false,
                    "bProcessing": true,
                    "bServerSide": true,
                    "sAjaxSource": ajaxurl,
                    "sServerMethod": "POST",
                    "fnServerParams": function (aoData) {
                        aoData.push(
                            {'name': 'action', 'value': 'arf_retrieve_paypal_config_data'},
                        );
                    },
                    "fnPreDrawCallback": function () {
                            jQuery("#arf_full_width_loader").show();
                        },
                    "fnDrawCallback": function (oSettings) {
                        jQuery("#arf_full_width_loader").hide();
                        jQuery('.arfhelptip').tipso('destroy');
                        jQuery('.arfhelptip').tipso({
                            position: 'top',
                            maxWidth: '400',
                            useTitle: true,
                            background: '#444444',
                            color: '#ffffff',
                            width: 'auto'
                        });
                    },
                    "aoColumnDefs": [
                        {"bSortable": false, "aTargets": [0, 6]},
                        {"sClass": "cbox", "sWidth": "30px", "aTargets":[0]}, 
                        {"sClass": "","aTargets":[1]}, 
                        {"sClass": "","aTargets":[2]}, 
                        {"sClass": "","aTargets":[3]}, 
                        {"sClass": "","aTargets":[4]}, 
                        {"sClass": "","aTargets":[5]}, 
                        {"sClass": "arf_action_cell","aTargets":[6]} 
                    ],
                    "language":{
                        "searchPlaceholder": __ARF_SEARCH_PLACEHOLDER,
                        "search":"",
                    },
                    "ordering": true,
                    "order":[[1,'desc']],
                    "oColVis": {
                        "aiExclude": [0, 6]
                    },
                };
                paypal_list_table_new.dataTable(dtobj);

             
               

            jQuery("form#arf_paypal_forms")[0].reset();
            jQuery('#arf_paypal_forms .arfhelptip').tipso('destroy');
            if (jQuery.isFunction(jQuery().tipso)) {
                jQuery('#arf_paypal_forms .arfhelptip').tipso({
                    position: 'top',
                    width: 'auto',
                    maxWidth: '400',
                    useTitle: true,
                    background: '#444444',
                    color: '#ffffff'
                });
            }
            },
            error: function() {
                jQuery(".arf_loader_icon_wrapper").hide();
                jQuery("form#arf_paypal_forms")[0].reset();
            }
        });
    }
    return false;
}

function arf_delete_bulk_paypal_form(is_delete) {

    if (is_delete) {
        var msg_for_blank_table = __NO_FORM_FOUND_MSG;
        var paypal_bulk_delete_nonce = jQuery('#arf_paypal_form_list').val();
        var str = jQuery('#arf_paypal_forms').serialize();
        jQuery.ajax({
            type: "POST",
            url: ajaxurl,
            data: "action=arf_paypal_form_bulk_act&" + str + "&wp_paypal_bulk_nonce=" + paypal_bulk_delete_nonce,
            dataType: 'json',
            beforeSend: function() {
                jQuery(".arf_loader_icon_wrapper").show();
            },
            success: function(msg) {
                jQuery(".arf_loader_icon_wrapper").hide();
                if (msg.errors.length > 0) {
                    jQuery("#form_error_message_des").html(msg.errors[0]);
                    arflite_error_msg();
                } else {
                    jQuery("#form_success_message_desc").html(msg.message);
                    arflite_success_msg();
                    var paypal_list_table = jQuery('#arf_paypal_list_form #example').DataTable();
                    var paypal_list_table_new = jQuery("#arf_paypal_list_form #example");

                    paypal_list_table.clear();
                    paypal_list_table.destroy();
                        
                    var dtobj = {
                        "oLanguage": {
                            "sProcessing": "",
                            "sEmptyTable": msg_for_blank_table,
                            "sZeroRecords": msg_for_blank_table
                        },
                        "sDom": '<"H"lfr>t<"footer"ip>',
                        "sPaginationType": "four_button",
                        "bJQueryUI": true,
                        "bPaginate": true,
                        "bAutoWidth": false,
                        "bProcessing": true,
                        "bServerSide": true,
                        "sAjaxSource": ajaxurl,
                        "sServerMethod": "POST",
                        "fnServerParams": function (aoData) {
                            aoData.push(
                                {'name': 'action', 'value': 'arf_retrieve_paypal_config_data'},
                            );
                        },
                        "fnPreDrawCallback": function () {
                                jQuery("#arf_full_width_loader").show();
                            },
                        "fnDrawCallback": function (oSettings) {
                            jQuery("#arf_full_width_loader").hide();
                            jQuery('.arfhelptip').tipso('destroy');
                            jQuery('.arfhelptip').tipso({
                                position: 'top',
                                maxWidth: '400',
                                useTitle: true,
                                background: '#444444',
                                color: '#ffffff',
                                width: 'auto'
                            });
                        },
                        "aoColumnDefs": [
                            {"bSortable": false, "aTargets": [0, 6]},
                            {"sClass": "cbox", "sWidth": "30px","aTargets":[0]}, 
                            {"sClass": "","aTargets":[1]}, 
                            {"sClass": "","aTargets":[2]}, 
                            {"sClass": "","aTargets":[3]}, 
                            {"sClass": "","aTargets":[4]}, 
                            {"sClass": "","aTargets":[5]}, 
                            {"sClass": "arf_action_cell","aTargets":[6]} 
                        ],
                        "language":{
                            "searchPlaceholder": __ARF_SEARCH_PLACEHOLDER,
                            "search":"",
                        },
                        "ordering": true,
                        "order":[[1,'desc']],
                        "oColVis": {
                            "aiExclude": [0, 6]
                        },
                    };
                    paypal_list_table_new.dataTable(dtobj);
                }

                jQuery("form#arf_paypal_forms")[0].reset();
                jQuery("input[name='action1']").val('-1');
                jQuery("input[name='action2']").val('-1');
                jQuery('#arf_paypal_forms .arfhelptip').tipso('destroy');
                if (jQuery.isFunction(jQuery().tipso)) {
                    jQuery('#arf_paypal_forms .arfhelptip').tipso({
                        position: 'top',
                        width: 'auto',
                        maxWidth: '400',
                        useTitle: true,
                        background: '#444444',
                        color: '#ffffff'
                    });
                }
            },
            error: function() {
                jQuery(".arf_loader_icon_wrapper").hide();
                jQuery("form#arf_paypal_forms")[0].reset();
            }
        });
    }
    jQuery('.arf_modal_overlay.arfactive').removeClass('arfactive');
}

function arf_paypal_save() {
    var req = 0;
    if (jQuery('#arf_paypal_email').val() == '') {
        jQuery('#arf_paypal_email').css('border-color', '#ff0000');
        jQuery('#arf_paypal_email_msg').css('display', 'block');
        req++;
    } else {
        jQuery('#arf_paypal_email').css('border-color', '');
        jQuery('#arf_paypal_email_msg').css('display', 'none');
    }
    if (jQuery('#arf_paypal_title').val() == '') {
        jQuery('#arf_paypal_title').css('border-color', '#ff0000');
        jQuery('#arf_paypal_title_msg').css('display', 'block');
        req++;
    } else {
        jQuery('#arf_paypal_title').css('border-color', '');
        jQuery('#arf_paypal_title_msg').css('display', 'none');
    }
    if (jQuery('#arf_paypal_currency').val() == '') {
        jQuery('.arf_paypal_currency .arfbtn.dropdown-toggle').css('border-color', '#ff0000');
        jQuery('.arf_paypal_currency .arfdropdown-menu.open').css('border-color', '#ff0000');
        jQuery('#arf_paypal_currency_msg').css('display', 'block');
        req++;
    } else {
        jQuery('.arf_paypal_currency .arfbtn.dropdown-toggle').css('border-color', '');
        jQuery('.arf_paypal_currency .arfdropdown-menu.open').css('border-color', '');
        jQuery('#arf_paypal_currency_msg').css('display', 'none');
    }
    if (jQuery('#arf_paypal_form').val() == '' && jQuery('#arfaction').val() == 'new') {
        jQuery('.arf_form_dropdown .arfbtn.dropdown-toggle').css('border-color', '#ff0000');
        jQuery('.arf_form_dropdown .arfdropdown-menu.open').css('border-color', '#ff0000');
        jQuery('#arf_paypal_form_msg').css('display', 'block');
        req++;
    } else {
        jQuery('.arf_form_dropdown .arfbtn.dropdown-toggle').css('border-color', '');
        jQuery('.arf_form_dropdown .arfdropdown-menu.open').css('border-color', '');
        jQuery('#arf_paypal_form_msg').css('display', 'none');
    }
    if (jQuery("input[name=arf_payment_type]:checked").val() == 'arf_payment_type_single') {
        if (jQuery('#arf_amount').val() == '') {
            jQuery('.arf_amount_dropdown .arfbtn.dropdown-toggle').css('border-color', '#ff0000');
            jQuery('.arf_amount_dropdown .arfdropdown-menu.open').css('border-color', '#ff0000');
            jQuery('#arf_amount_msg').css('display', 'block');
            req++;
        } else {
            jQuery('.arf_amount_dropdown .arfbtn.dropdown-toggle').css('border-color', '');
            jQuery('.arf_amount_dropdown .arfdropdown-menu.open').css('border-color', '');
            jQuery('#arf_amount_msg').css('display', 'none');
        }
    }
    if (jQuery("input[name=arf_payment_type]:checked").val() == 'arf_payment_type_multiple') {
        if (jQuery('#arf_multiple_product_service_type').is(':checked')) {
            if (jQuery('#arf_multiple_product_service_amount').val() == '') {
                jQuery('.arf_multiple_product_service_amount_dropdown .arfbtn.dropdown-toggle').css('border-color', '#ff0000');
                jQuery('.arf_multiple_product_service_amount_dropdown .arfdropdown-menu.open').css('border-color', '#ff0000');
                jQuery('#arf_multiple_product_service_amount_msg').css('display', 'block');
                req++;
            } else {
                jQuery('.arf_multiple_product_service_amount_dropdown .arfbtn.dropdown-toggle').css('border-color', '');
                jQuery('.arf_multiple_product_service_amount_dropdown .arfdropdown-menu.open').css('border-color', '');
                jQuery('#arf_multiple_product_service_amount_msg').css('display', 'none');
            }
        }
        if (jQuery('#arf_multiple_subscription_type').is(':checked')) {
            if (jQuery('#arf_multiple_subscription_amount').val() == '') {
                jQuery('.arf_multiple_subscription_amount_dropdown .arfbtn.dropdown-toggle').css('border-color', '#ff0000');
                jQuery('.arf_multiple_subscription_amount_dropdown .arfdropdown-menu.open').css('border-color', '#ff0000');
                jQuery('#arf_multiple_subscription_amount_msg').css('display', 'block');
                req++;
            } else {
                jQuery('.arf_multiple_subscription_amount_dropdown .arfbtn.dropdown-toggle').css('border-color', '');
                jQuery('.arf_multiple_subscription_amount_dropdown .arfdropdown-menu.open').css('border-color', '');
                jQuery('#arf_multiple_subscription_amount_msg').css('display', 'none');
            }
        }
    }
    if (jQuery('input[name="success_action"]:checked').val() == 'message' && jQuery('#success_msg').val() == '') {
        jQuery('#success_msg').css('border-color', '#ff0000');
        jQuery('#success_msg_error').css('display', 'block');
        req++;
    } else {
        jQuery('#success_msg').css('border-color', '');
        jQuery('#success_msg_error').css('display', 'none');
    }
    if (jQuery('input[name="success_action"]:checked').val() == 'redirect' && jQuery('#success_url').val() == '') {
        jQuery('#success_url').css('border-color', '#ff0000');
        jQuery('#success_url_error').css('display', 'block');
        req++;
    } else {
        jQuery('#success_url').css('border-color', '');
        jQuery('#success_url_error').css('display', 'none');
    }
    if (jQuery('input[name="success_action"]:checked').val() == 'page' && jQuery('#option_success_page_id').val() == '') {
        jQuery('.frm-pages-dropdown .arfbtn.dropdown-toggle').css('border-color', '#ff0000');
        jQuery('.frm-pages-dropdown .arfdropdown-menu.open').css('border-color', '#ff0000');
        jQuery('#option_success_page_id_error').css('display', 'block');
        req++;
    } else {
        jQuery('.frm-pages-dropdown .arfbtn.dropdown-toggle').css('border-color', '');
        jQuery('.frm-pages-dropdown .arfdropdown-menu.open').css('border-color', '');
        jQuery('#option_success_page_id_error').css('display', 'none');
    }
    if (req > 0) {
        jQuery(window.opera ? 'html' : 'html, body').animate({
            scrollTop: jQuery('#arf_paypal_email').offset().top - 250
        }, 'slow');
        return false;
    } else {
        var nonce = jQuery('#_wpnonce_paypal').val();
        //if (typeof ajaxurl == 'undefined') {
        var ajaxurl = jQuery("#ajax_url").val();
        var form = jQuery('form#arf_paypal_setting').serialize();
        jQuery.ajax({
            url: ajaxurl,
            type: 'POST',
            dataType: 'json',
            data: 'action=arf_paypal_save_settings&_wpnonce_arfpaypal=' + nonce + '&' + form,
            beforeSend: function() {
                jQuery("#arf_pp_config_loader_icon").css('display','inline-block');
                jQuery('.arf_pp_edit_loader').addClass('active');
            },
            success: function(response) {
                jQuery('#arf_pp_config_loader_icon').hide();
                jQuery('.arf_pp_edit_loader').removeClass('active');
                if (response.success == false && response.message != 'redirect') {
                    if (jQuery('#arf_old_version').length > 0) {
                        jQuery("#error_message").html( response.message );
                        jQuery("#arf_error_message").show();
                        jQuery(window.opera ? 'html' : 'html, body').animate({
                            scrollTop: jQuery('#arf_error_message').offset().top - 250
                        }, 'slow');
                        jQuery("#arf_error_message").fadeOut(5000);
                    } else {
                        jQuery("#form_error_message_des").html(response.message);
                        arflite_error_msg();
                    }
                } else if (response.success == false && 'redirect' == response.message) {
                    window.location.href = response.url;
                } else {
                    if (jQuery('#arf_old_version').length > 0) {
                        jQuery(".arf_success_message").html( response.message );
                        jQuery("#success_message").show();
                        jQuery(window.opera ? 'html' : 'html, body').animate({
                            scrollTop: jQuery('#success_message').offset().top - 250
                        }, 'slow');
                        jQuery("#success_message").fadeOut(5000);
                    } else {
                        jQuery('#form_success_message_desc').html(response.message);
                        arflite_success_msg();
                    }
                    if ('undefined' != typeof response.arfaction && 'new' == response.arfaction) {
                        var pageurl = removeVariableFromURL(document.URL, 'arfaction');
                        if (window.history.pushState) {
                            window.history.pushState({
                                path: pageurl
                            }, '', pageurl + '&arfaction=edit&id=' + response.new_id);
                        }
                        jQuery("#arfaction").val('edit');
                        jQuery("#form_id").val(response.form_id);
                        jQuery('#id').val(response.new_id);
                        jQuery('#form_name').val(response.form_name);
                        var form_name = "<label class='lblsubtitle' style='padding-top:5px;'><strong>";
                        form_name += response.form_name;
                        form_name += '</strong></label>';
                        jQuery("#arf_paypal_form_name").html(form_name);
                    }
                }
            },
            error: function() {}
        });
    }
    return false;
}

function arf_paypal_form_change() {
    var form_id = jQuery('#arf_paypal_form').val();
    jQuery.ajax({
        type: "POST",
        url: ajaxurl,
        data: "action=arf_paypal_field_dropdown&form_id=" + form_id + "&_wpnonce_paypal=" + jQuery('#_wpnonce_paypal').val(),
        success: function(msg) {
            jQuery('.arf_paypal_fields').each(function(i) {
                var id = jQuery(this).attr('id');
                if (id != 'undefined' && id != '') {
                    var dropdown = msg.split('^|^');
                    if (dropdown[0] != '' && (id == 'add_field_arf_amount' || id== 'add_field_arf_multiple_product_service_amount' || id== 'add_field_arf_multiple_donations_service_amount' || id=='add_field_arf_multiple_subscription_amount')) {
                        jQuery('#' + id).html(dropdown[0]);
                    } else if (dropdown[1] != '' && (id != 'add_field_arf_amount' || id != 'add_field_arf_multiple_product_service_amount' || id != 'add_field_arf_multiple_donations_service_amount' || id !='add_field_arf_multiple_subscription_amount')) {
                        jQuery('#' + id).html(dropdown[1]);
                    }
                }
            });
        }
    });
}

function removeVariableFromURL(url_string, variable_name) {
    var URL = String(url_string);
    var regex = new RegExp("\\?" + variable_name + "=[^&]*&?", "gi");
    URL = URL.replace(regex, '?');
    regex = new RegExp("\\&" + variable_name + "=[^&]*&?", "gi");
    URL = URL.replace(regex, '&');
    URL = URL.replace(/(\?|&)$/, '');
    regex = null;
    return URL;
}

function change_form_orders() {
    var arf_form_orders_nonce = jQuery('#arf_paypal_order_list_nonce').val();
    var form_id = jQuery('#arf_paypal_forms_dropdown').val();
    var start_date = jQuery('#datepicker_from').val();
    var end_date = jQuery('#datepicker_to').val();
    var msg_for_blank_table = __NO_FORM_FOUND_MSG;

    var paypal_order_table = jQuery('#arf_paypal_order_form #example').DataTable();

    paypal_order_table.clear();
    paypal_order_table.destroy();
        
    var paypal_order_table_new = jQuery("#arf_paypal_order_form #example");

    var dtobj = {
        "oLanguage": {
            "sProcessing": "",
            "sEmptyTable": msg_for_blank_table,
            "sZeroRecords": msg_for_blank_table
        },
        "sDom": '<"H"lfr>t<"footer"ip>',
        "sPaginationType": "four_button",
        "bJQueryUI": true,
        "bPaginate": true,
        "bAutoWidth": false,
        "bProcessing": true,
        "bServerSide": true,
        "sAjaxSource": ajaxurl,
        "sServerMethod": "POST",
        "fnServerParams": function (aoData) {
            var form_id = jQuery('#arf_paypal_forms_dropdown').val();
            var start_date = jQuery('#datepicker_from').val();
            var end_date = jQuery('#datepicker_to').val();
            aoData.push(
                {'name': 'action', 'value': 'arf_retrieve_paypal_transaction_data'},
                {'name': 'form_id', 'value': form_id},
                {'name': 'start_date', 'value': start_date},
                {'name': 'end_date', 'value': end_date},
            );
        },
        "fnPreDrawCallback": function () {
                jQuery("#arf_full_width_loader").show();
            },
        "fnDrawCallback": function (oSettings) {
            jQuery("#arf_full_width_loader").hide();
            jQuery('.arfhelptip').tipso('destroy');
            jQuery('.arfhelptip').tipso({
                position: 'top',
                maxWidth: '400',
                useTitle: true,
                background: '#444444',
                color: '#ffffff',
                width: 'auto'
            });
        },
        "aoColumnDefs": [{
            "bVisible": false,
            "aTargets": []
        }, {
            "bSortable": false,
            "aTargets": [0, 8]
        }],
        "ordering": true,
        "order":[[1,'desc']],
        "aoColumnDefs": [            
            {"bSortable": false, "aTargets": [0,4]},
            {"sClass": "", "sWidth": "30px", "aTargets":[0]}, 
            {"sClass": "","aTargets":[1]}, 
            {"sClass": "","aTargets":[2]}, 
            {"sClass": "","aTargets":[3]}, 
            {"sClass": "","aTargets":[4]}, 
            {"sClass": "","aTargets":[5]}, 
            {"sClass": "","aTargets":[6]}, 
            {"sClass": "","aTargets":[7]}, 
            {"sClass": "arf_action_cell","aTargets":[8]} 
        ],
        "oColVis": {
            "aiExclude": [0, 8]
        },
    };

    dtobj.language = {
        "searchPlaceholder":__ARF_SEARCH_PLACEHOLDER,
        "search":"",
    };
    

    paypal_order_table_new.dataTable(dtobj);
        
    

    jQuery('#arf_paypal_orders .arfhelptip').tipso('destroy');
    if (jQuery.isFunction(jQuery().tipso)) {
        jQuery('#arf_paypal_orders .arfhelptip').tipso({
            position: 'top',
            width: 'auto',
            maxWidth: '400',
            useTitle: true,
            background: '#444444',
            color: '#ffffff'
        });
    }
    
}

function arf_delete_close_popup(id) {
    jQuery('.delete_form_message_' + id).hide();
    if (jQuery('.arfdeleteform_div_' + id).parents('.arf_action_cell').find('.delete_form_message_' + id).length > 0) {
        jQuery('.arfdeleteform_div_' + id).parents('.arf_action_cell').find('.delete_form_message_' + id).remove();
    }
    if (jQuery('.arfdeleteentry_div_' + id).parents('.arf_action_cell').find('.delete_form_message_' + id).length > 0) {
        jQuery('.arfdeleteentry_div_' + id).parents('.arf_action_cell').find('.delete_form_message_' + id).remove();
    }
}
jQuery(document).on('click', '.arf_paypal_delete', function(event) {
    
    var id = jQuery(this).attr('data-id');
    
    if (id == null || id == 'undefined') {
        return;
    }
    var delete_popup_html = '';
    delete_popup_html += '<div class="delete_popup arfactive delete_form_message_' + id + '" id="delete_form_message">';
    delete_popup_html += '<input type="hidden" value="' + id + '" id="delete_entry_id"/>';
    delete_popup_html += '<div class="delete_column_arrow" style="position:absolute;"></div>';
    delete_popup_html += '<div class="delete_title"><div class="delete_confirm_message">' + __DELETE_CONFIG + '</div>';
    delete_popup_html += '<div class="delete_popup_footer">';
    delete_popup_html += '<button type="button" class="rounded_button add_button arf_delete_modal_left arfdelete_color_red" onclick="arfpaypalformactionfunc(\'delete\',' + id + ');">' + __DELETE + '</button>&nbsp;&nbsp;';
    delete_popup_html += '<button type="button" class="rounded_button delete_button arfdelete_color_gray" onclick="arf_delete_close_popup(' + id + ');">' + __CANCEL + '</button>';
    delete_popup_html += '</div>';
    delete_popup_html += '</div>';
    delete_popup_html += '</div>';
    var select_content = jQuery(this).parent().parent('.arf-row-actions');
    
    jQuery(select_content).find('.delete_popup').remove();
    jQuery(select_content).append(delete_popup_html);
    jQuery('.delete_form_message_' + id).show();

});
jQuery(document).on('click', '.arf_delete_entry', function(event) {
    
    var id = jQuery(this).attr('data-id');
    if (id == null || id == 'undefined') {
        return;
    }
    var delete_popup_html = '';
    delete_popup_html += '<div class="delete_popup arfformentry_delete arfactive delete_form_message_' + id + '" id="delete_form_message">';
    delete_popup_html += '<input type="hidden" value="' + id + '" id="delete_entry_id"/>';
    delete_popup_html += '<div class="delete_column_arrow" style="position:absolute;"></div>';
    delete_popup_html += '<div class="delete_title"><div class="delete_confirm_message">' + __DELETE_ORDER + '</div>';
    delete_popup_html += '<div class="delete_popup_footer">';
    delete_popup_html += '<button type="button" class="rounded_button add_button arf_delete_modal_left arfdelete_color_red" onclick="arfpaypalactionfunc(\'delete\',' + id + ');">' + __DELETE + '</button>&nbsp;&nbsp;';
    delete_popup_html += '<button type="button" class="rounded_button delete_button arfdelete_color_gray" onclick="arf_delete_close_popup(' + id + ');">' + __CANCEL +'</button>';
    delete_popup_html += '</div>';
    delete_popup_html += '</div>';
    delete_popup_html += '</div>';
    var select_content = jQuery(this).parent().parent('.arf-row-actions');
    jQuery(select_content).find('.delete_popup').remove();
    jQuery(select_content).append(delete_popup_html);
    jQuery('.delete_form_message_' + id).show();
});
jQuery(document).on('change', "input[name='success_action']", function() {
    var method = jQuery(this).val();
    if (method == 'page') {
        jQuery('.success_action_box').hide();
        jQuery('.success_action_page_box').show();
    } else if (method == 'redirect') {
        jQuery('.success_action_box').hide();
        jQuery('.success_action_redirect_box').show();
    } else if (method == 'message') {
        jQuery('.success_action_box').hide();
        jQuery('.success_action_message_box').show();
    }
});
if (document.getElementById("arf_paypal_recurring_type")) {
    arf_paypal_payment_type_change();
    arf_paypal_recurring_type_select();
    arf_paypal_trial_recurring_type_select();
}

function is_shipping_info() {
    if (jQuery('#shipping_info').is(':checked')) {
        jQuery('#paypal_shipping_fields').show();
    } else {
        jQuery('#paypal_shipping_fields').hide();
    }
}

function is_notification_info() {
    if (jQuery('#arf_paypal_notification').is(':checked')) {
        jQuery('#notification_option').show();
    } else {
        jQuery('#notification_option').hide();
    }
}

function is_user_notification_info() {
    if (jQuery('#arf_paypal_user_notification').is(':checked')) {
        jQuery('#user_notification_option').show();
    } else {
        jQuery('#user_notification_option').hide();
    }
}

function arf_paypal_payment_type_change(val) {
    var val = jQuery("#arf_paypal_payment_type").val();
    if (jQuery("input[name=arf_payment_type]:checked").val() == 'arf_payment_type_single') {
        if (val == "subscription") {
            jQuery(".arfsubscriptiondata").show(0);
            if (jQuery('#arf_paypal_trial_period').is(':checked')) {
                jQuery("#arf_paypal_trial_period_amount").show(0);
                jQuery("#arf_paypal_trial_period_selbox").show(0);
            } else {
                jQuery("#arf_paypal_trial_period_amount").hide(0);
                jQuery("#arf_paypal_trial_period_selbox").hide(0);
            }
        } else {
            jQuery(".arfsubscriptiondata").hide(0);
            if (jQuery('#arf_paypal_trial_period').is(':checked')) {
                jQuery("#arf_paypal_trial_period").trigger("click");
            }
        }
    }
    if (jQuery("input[name=arf_payment_type]:checked").val() == 'arf_payment_type_multiple') {
        jQuery(".arfsubscriptiondata").hide(0);
        if (jQuery('#arf_multiple_subscription_type').is(':checked')) {
            jQuery(".arfmultiplesubscriptiondata").show(0);
        } else {
            if (jQuery('#arf_paypal_trial_period').is(':checked')) {
                jQuery("#arf_paypal_trial_period").trigger("click");
            }
        }
    }
}

function arf_paypal_recurring_type_select() {
    var val = jQuery("#arf_paypal_recurring_type").val();
    if (val == "D") {
        jQuery("#arf_paypal_days_main").show(0);
        jQuery("#arf_paypal_months_main").hide(0);
        jQuery("#arf_paypal_years_main").hide(0);
    } else if (val == "M") {
        jQuery("#arf_paypal_days_main").hide(0);
        jQuery("#arf_paypal_months_main").show(0);
        jQuery("#arf_paypal_years_main").hide(0);
    }
    if (val == "Y") {
        jQuery("#arf_paypal_days_main").hide(0);
        jQuery("#arf_paypal_months_main").hide(0);
        jQuery("#arf_paypal_years_main").show(0);
    }
}

function arf_paypal_trial_recurring_type_select() {
    var val = jQuery("#arf_paypal_trial_recurring_type").val();
    if (val == "D") {
        jQuery("#arf_paypal_trial_days_main").show(0);
        jQuery("#arf_paypal_trial_months_main").hide(0);
        jQuery("#arf_paypal_trial_years_main").hide(0);
    } else if (val == "M") {
        jQuery("#arf_paypal_trial_days_main").hide(0);
        jQuery("#arf_paypal_trial_months_main").show(0);
        jQuery("#arf_paypal_trial_years_main").hide(0);
    }
    if (val == "Y") {
        jQuery("#arf_paypal_trial_days_main").hide(0);
        jQuery("#arf_paypal_trial_months_main").hide(0);
        jQuery("#arf_paypal_trial_years_main").show(0);
    }
}

function is_trial_period() {
    if (jQuery('#arf_paypal_trial_period').is(':checked')) {
        jQuery("#arf_paypal_trial_period_amount").show(0);
        jQuery("#arf_paypal_trial_period_selbox").show(0);
    } else {
        jQuery("#arf_paypal_trial_period_amount").hide(0);
        jQuery("#arf_paypal_trial_period_selbox").hide(0);
    }
}

function is_paypal_condition() {
    if (jQuery('#arf_paypal_condition').is(':checked')) {
        jQuery('#conditional_logic_div_paypal').show();
    } else {
        jQuery('#conditional_logic_div_paypal').hide();
    }
}
 

function delete_rule_paypal(field) {
    if (jQuery('.logic_rules_div .cl_rules').length > 1) {
        jQuery(field).parents('.cl_rules').first().remove();
    } else {
        jQuery(field).parents('.cl_rules').first().remove();
        jQuery('#conditional_logic_if_cond_paypal').attr('disabled', true);
        jQuery('#conditional_logic_if_cond_paypal').next('dl').children('dt').addClass('arf_disable_selectbox');
        jQuery('#arf_new_law_paypal').show();
        if (typeof(__ARFADDRULE) != 'undefined') {
            var atitle = __ARFADDRULE;
        } else {
            var atitle = 'Please add one or more rules';
        }
        jQuery('#conditional_logic_if_cond_paypal').parents('.sltstandard').first().tipso({
            position: 'top',
            width: 'auto',
            maxWidth: '400',
            useTitle: true,
            background: '#444444',
            color: '#ffffff'
        });
    }
}

function arf_modify_ajax_actions(actions_list){
    var arf_paypal_action_list = ['arf_retrieve_paypal_config_data', 'arf_retrieve_paypal_transaction_data'];
    actions_list = actions_list.concat(arf_paypal_action_list);
    return actions_list;
}

jQuery(document).ready(function() {

    wp.hooks.addFilter( 'arf_modify_datatable_action_outside', 'arfpaypal', arf_modify_ajax_actions, 10 );

        jQuery(document).on('change', '#arf_bulk_action_one, #arf_bulk_action_two', function() {
        
        var newValue = jQuery(this).val();
        var newLabel = jQuery(this).closest('.arf_list_bulk_action_wrapper').find('dd ul li[data-value="' + newValue + '"]').attr('data-label');

        if (typeof newLabel !== 'undefined') {
            jQuery('#arf_bulk_action_one, #arf_bulk_action_two').val(newValue);
            jQuery('dl[data-id="arf_bulk_action_one"] dt span, dl[data-id="arf_bulk_action_two"] dt span').text(newLabel);
        }
    });

    if (jQuery('#arfpaypal_form_error').length > 0) {
        var pageurl = removeVariableFromURL(document.URL, 'err');
        if (window.history.pushState) {
            window.history.pushState({
                path: pageurl
            }, '', pageurl);
        }
        if( jQuery(".arf_pp_old_version").length > 0 ){
            jQuery("#error_message").html( __INVALID_FORM_MSG );
            jQuery("#arf_error_message").show();
            jQuery("#arf_error_message").fadeOut(5000);
        } else {
            jQuery("#form_error_message_des").html(__INVALID_FORM_MSG);
            arflite_error_msg();
        }
    }
    setTimeout(function() {
        jQuery("#arf_full_width_loader").hide();
    }, 500);
    if (jQuery('#arf_paypal_list_form').length > 0) {
        arf_paypal_list_form_initialize();
    }
    if (jQuery("#arf_paypal_order_form").length > 0) {
        arf_paypal_transaction_form_init();
    }
});
jQuery(document).on('change', "input[name='arf_payment_type']", function() {
    var type = jQuery(this).val();
    if (type == 'arf_payment_type_single') {
        var payment_type = jQuery("#arf_paypal_payment_type").val();
        jQuery('.arfpaymenttypemultipledata').hide();
        jQuery('.arfpaymenttypesingledata').show();
        jQuery(".arfsubscriptiondata").hide(0);
        jQuery("#arf_paypal_trial_period_amount").hide(0);
        jQuery("#arf_paypal_trial_period_selbox").hide(0);
        if (payment_type == 'subscription') {
            jQuery(".arfsubscriptiondata").show(0);
            if (jQuery('#arf_paypal_trial_period').is(':checked')) {
                jQuery("#arf_paypal_trial_period_amount").show(0);
                jQuery("#arf_paypal_trial_period_selbox").show(0);
            } else {
                jQuery("#arf_paypal_trial_period_amount").hide(0);
                jQuery("#arf_paypal_trial_period_selbox").hide(0);
            }
        }
    }
    if (type == 'arf_payment_type_multiple') {
        jQuery('.arfpaymenttypesingledata').hide();
        jQuery('.arfpaymenttypemultipledata').show();
        jQuery(".arfsubscriptiondata").hide(0);
        jQuery("#arf_paypal_trial_period_amount").hide(0);
        jQuery("#arf_paypal_trial_period_selbox").hide(0);
        if (jQuery('#arf_multiple_subscription_type').is(':checked')) {
            jQuery(".arfmultiplesubscriptiondata").show(0);
            if (jQuery('#arf_paypal_trial_period').is(':checked')) {
                jQuery("#arf_paypal_trial_period_amount").show(0);
                jQuery("#arf_paypal_trial_period_selbox").show(0);
            } else {
                jQuery("#arf_paypal_trial_period_amount").hide(0);
                jQuery("#arf_paypal_trial_period_selbox").hide(0);
            }
        }
    }
});
jQuery(document).on('change', "#arf_multiple_subscription_amount", function() {
    var val = jQuery(this).next('dl').children('dt').children('span').html();
    if (val) {
        jQuery('#arf_billing_cycle_data').show();
        jQuery('.duration_feiled').html(val);
    }
});

function arf_multiple_payment_type_data() {
    jQuery(".arfsubscriptiondata").hide(0);
    jQuery("#arf_paypal_trial_period_amount").hide(0);
    jQuery("#arf_paypal_trial_period_selbox").hide(0);
    
    if (jQuery('#arf_multiple_product_service_type').is(':checked')) {
        jQuery('.arfmultipleproductservicedata').show();
    } else {
        jQuery('.arfmultipleproductservicedata').hide();
    }
    
    if (jQuery('#arf_multiple_donations_service_type').is(':checked')) {
        jQuery('.arfmultipledonationsservicedata').show();
    }else{
        jQuery('.arfmultipledonationsservicedata').hide();
    }

    if (jQuery('#arf_multiple_subscription_type').is(':checked')) {
        jQuery('.arfmultiplesubscriptiondata').show();
        if (jQuery('#arf_billing_cycle_data .logic_multiple_subscription_div').length == 0) {
            arf_add_new_law_multiple_subscription();
        }
        if (jQuery('#arf_paypal_trial_period').is(':checked')) {
            jQuery("#arf_paypal_trial_period_amount").show(0);
            jQuery("#arf_paypal_trial_period_selbox").show(0);
        } else {
            jQuery("#arf_paypal_trial_period_amount").hide(0);
            jQuery("#arf_paypal_trial_period_selbox").hide(0);
        }
    } else {
        jQuery('.arfmultiplesubscriptiondata').hide();
    }
}

function arf_add_new_law_multiple_subscription() {
    jQuery('.bulk_add').attr('disabled', true);
    var form_id = jQuery('#arf_paypal_form').val();
    if (jQuery("#arf_multiple_subscription_amount").find('option:selected').length > 0) {
        var duration_name = jQuery("#arf_multiple_subscription_amount").find('option:selected').text();
    } else {
        var duration_name = jQuery("#arf_multiple_subscription_amount").next('dl').children('dt').children('span').html();
    }
    var metas = [];
    jQuery('input[name^="rule_array_multiple_subscription"]').each(function() {
        metas.push(this.value);
    });
    if (metas.length > 0) {
        var maxValueInArray = Math.max.apply(Math, metas);
        var next_meta_id = parseInt(maxValueInArray) + parseInt(1);
    } else {
        var next_meta_id = 1;
    }
    if (form_id == '' || form_id == 'undefined') {
        form_id = '0';
    }
    jQuery.ajax({
        type: "POST",
        url: ajaxurl,
        data: "action=add_new_rule_multiple_subscription&form_id=" + form_id + "&next_rule_id=" + next_meta_id + "&duration_name=" + duration_name,
        success: function(msg) {
            jQuery('#arf_new_law_multiple_subscription').hide();
            jQuery('.bulk_add_remove_subscription_multiple .bulk_remove').show();
            jQuery('#arf_billing_cycle_data').show();
            jQuery('#arf_billing_cycle_data').append('<div id="arf_cl_rule_multiple_subscription_' + next_meta_id + '" class="logic_multiple_subscription_div" style="float:left; margin:2px 0 10px 0;">' + msg + '</div>');
            jQuery('.bulk_add').attr('disabled', false);
        }
    });
}

function delete_rule_multiple_subscription(field) {
    if (jQuery('#arf_billing_cycle_data .logic_multiple_subscription_div').length > 2) {
        jQuery(field).parents('.logic_multiple_subscription_div').first().remove();
    } else {
        jQuery(field).parents('.logic_multiple_subscription_div').first().remove();
        jQuery('.bulk_add_remove_subscription_multiple .bulk_remove').hide();
    }
}

function arf_multiple_subscription_paypal_recurring_type_select(rule_i) {
    var val = jQuery("#arf_paypal_recurring_type_" + rule_i).val();
    if (val == "D") {
        jQuery("#arf_paypal_days_main_" + rule_i).show(0);
        jQuery("#arf_paypal_months_main_" + rule_i).hide(0);
        jQuery("#arf_paypal_years_main_" + rule_i).hide(0);
    } else if (val == "M") {
        jQuery("#arf_paypal_days_main_" + rule_i).hide(0);
        jQuery("#arf_paypal_months_main_" + rule_i).show(0);
        jQuery("#arf_paypal_years_main_" + rule_i).hide(0);
    }
    if (val == "Y") {
        jQuery("#arf_paypal_days_main_" + rule_i).hide(0);
        jQuery("#arf_paypal_months_main_" + rule_i).hide(0);
        jQuery("#arf_paypal_years_main_" + rule_i).show(0);
    }
}
jQuery(document).on('click', "#cb-select-all-1", function() {
    jQuery('input[name="item-action[]"]').prop('checked', this.checked);
});
jQuery(document).on('click', 'input[name="item-action[]"]', function() {
    if (jQuery('input[name="item-action[]"]').length == jQuery('input[name="item-action[]"]:checked').length) {
        jQuery("#cb-select-all-1").prop("checked", true);
    } else {
        jQuery("#cb-select-all-1").prop("checked", false);
    }
});


function arf_paypal_list_form_initialize() {
    jQuery.fn.dataTableExt.oPagination.four_button = {
        "fnInit": function(oSettings, nPaging, fnCallbackDraw) {
            nFirst = document.createElement('span');
            nPrevious = document.createElement('span');
            var nInput = document.createElement('input');
            var nPage = document.createElement('span');
            var nOf = document.createElement('span');
            nOf.className = "paginate_of";
            nInput.className = "current_page_no";
            nPage.className = "paginate_page";
            nInput.type = "text";
            nInput.style.width = "40px";
            nInput.style.height = "26px";
            nInput.style.display = "inline";
            nPaging.appendChild(nPage);
            jQuery(nInput).keyup(function(e) {
                if (e.which == 38 || e.which == 39) {
                    this.value++;
                } else if ((e.which == 37 || e.which == 40) && this.value > 1) {
                    this.value--;
                }
                if (this.value == "" || this.value.match(/[^0-9]/)) {
                    return;
                }
                var iNewStart = oSettings._iDisplayLength * (this.value - 1);
                if (iNewStart > oSettings.fnRecordsDisplay()) {
                    oSettings._iDisplayStart = (Math.ceil((oSettings.fnRecordsDisplay() - 1) / oSettings._iDisplayLength) - 1) * oSettings._iDisplayLength;
                    fnCallbackDraw(oSettings);
                    return;
                }
                oSettings._iDisplayStart = iNewStart;
                fnCallbackDraw(oSettings);
            });
            var nNext = document.createElement('span');
            var nLast = document.createElement('span');
            var nFirst = document.createElement('span');
            var nPrevious = document.createElement('span');
            var nPage = document.createElement('span');
            var nOf = document.createElement('span');
            nNext.style.backgroundRepeat = "no-repeat";
            nNext.style.backgroundPosition = "center";
            nNext.title = "Next";
            nLast.style.backgroundRepeat = "no-repeat";
            nLast.style.backgroundPosition = "center";
            nLast.title = "Last";
            nFirst.style.backgroundRepeat = "no-repeat";
            nFirst.style.backgroundPosition = "center";
            nFirst.title = "First";
            nPrevious.style.backgroundRepeat = "no-repeat";
            nPrevious.style.backgroundPosition = "center";
            nPrevious.title = "Previous";
            nFirst.appendChild(document.createTextNode(' '));
            nPrevious.appendChild(document.createTextNode(' '));
            nLast.appendChild(document.createTextNode(' '));
            nNext.appendChild(document.createTextNode(' '));
            nOf.className = "paginate_button nof";
            nPaging.appendChild(nFirst);
            nPaging.appendChild(nPrevious);
            nPaging.appendChild(nInput);
            nPaging.appendChild(nOf);
            nPaging.appendChild(nNext);
            nPaging.appendChild(nLast);
            jQuery(nFirst).click(function() {
                if (jQuery(this).hasClass('paginate_disabled_first')) { return; }
                oSettings.oApi._fnPageChange(oSettings, "first");
                fnCallbackDraw(oSettings);
            });
            jQuery(nPrevious).click(function() {
                if (jQuery(this).hasClass('paginate_disabled_previous')) { return; }
                oSettings.oApi._fnPageChange(oSettings, "previous");
                fnCallbackDraw(oSettings);
            });
            jQuery(nNext).click(function() {
                if (jQuery(this).hasClass('paginate_disabled_next')) { return; }
                oSettings.oApi._fnPageChange(oSettings, "next");
                fnCallbackDraw(oSettings);
            });
            jQuery(nLast).click(function() {
                if (jQuery(this).hasClass('paginate_disabled_last')) { return; }
                oSettings.oApi._fnPageChange(oSettings, "last");
                fnCallbackDraw(oSettings);
            });
            jQuery(nFirst).bind('selectstart', function() {
                return false;
            });
            jQuery(nPrevious).bind('selectstart', function() {
                return false;
            });
            jQuery('span', nPaging).bind('mousedown', function() {
                return false;
            });
            jQuery('span', nPaging).bind('selectstart', function() {
                return false;
            });
            jQuery(nNext).bind('selectstart', function() {
                return false;
            });
            jQuery(nLast).bind('selectstart', function() {
                return false;
            });
        },
        "fnUpdate": function(oSettings, fnCallbackDraw) {
            if (!oSettings.aanFeatures.p) {
                return;
            }
            var an = oSettings.aanFeatures.p;
            for (var i = 0, iLen = an.length; i < iLen; i++) {
                var buttons = an[i].getElementsByTagName('span');
                if (oSettings._iDisplayStart === 0) {
                    buttons[1].className = "paginate_disabled_first arfhelptip";
                    buttons[2].className = "paginate_disabled_previous arfhelptip";
                } else {
                    buttons[1].className = "paginate_enabled_first arfhelptip";
                    buttons[2].className = "paginate_enabled_previous  arfhelptip";
                }
                if (oSettings.fnDisplayEnd() == oSettings.fnRecordsDisplay()) {
                    buttons[4].className = "paginate_disabled_next  arfhelptip";
                    buttons[5].className = "paginate_disabled_last  arfhelptip";
                } else {
                    buttons[4].className = "paginate_enabled_next arfhelptip";
                    buttons[5].className = "paginate_enabled_last  arfhelptip";
                }
                if (!oSettings.aanFeatures.p) {
                    return;
                }
                var iPages = Math.ceil((oSettings.fnRecordsDisplay()) / oSettings._iDisplayLength);
                var iCurrentPage = Math.ceil(oSettings._iDisplayStart / oSettings._iDisplayLength) + 1;
                if (document.getElementById('of_grid')) {
                    var of_grid = document.getElementById('of_grid').value;
                } else {
                    var of_grid = 'of';
                }
                var an = oSettings.aanFeatures.p;
                for (var i = 0, iLen = an.length; i < iLen; i++) {
                    var spans = an[i].getElementsByTagName('span');
                    var inputs = an[i].getElementsByTagName('input');
                    spans[spans.length - 3].innerHTML = " " + of_grid + " " + iPages;
                    inputs[0].value = iCurrentPage;
                }
            }
        }
    }
    var msg_for_blank_table = __NO_FORM_FOUND_MSG;

    var dtobj = {
        "oLanguage": {
            "sProcessing": "",
            "sEmptyTable": msg_for_blank_table,
            "sZeroRecords": msg_for_blank_table
        },
        "sDom": '<"H"lfr>t<"footer"ip>',
        "sPaginationType": "four_button",
        "bJQueryUI": true,
        "bPaginate": true,
        "bAutoWidth": false,
        "language":{
            "searchPlaceholder": __ARF_SEARCH_PLACEHOLDER,
            "search":"",
        },
        "ordering": true,
        "order":[[1,'desc']],
        "oColVis": {
            "aiExclude": [0, 6]
        },
    }; 

    
    dtobj.bProcessing = true;
    dtobj.bServerSide = true;
    dtobj.sAjaxSource = ajaxurl;
    dtobj.sServerMethod = "POST";
    dtobj.fnServerParams = function (aoData) {
        aoData.push(
            {'name': 'action', 'value': 'arf_retrieve_paypal_config_data'},
        );
    };
    dtobj.fnPreDrawCallback = function () {
        jQuery("#arf_full_width_loader").show();
    };
    dtobj.fnDrawCallback = function (oSettings) {
        jQuery("#arf_full_width_loader").hide();
        jQuery('.arfhelptip').tipso('destroy');
        jQuery('.arfhelptip').tipso({
            position: 'top',
            maxWidth: '400',
            useTitle: true,
            background: '#444444',
            color: '#ffffff',
            width: 'auto'
        });
    };
    dtobj.aoColumnDefs = [
        {"bSortable": false, "aTargets": [0, 6]},
        {"sClass": "cbox", "sWidth": "30px", "aTargets":[0]}, 
        {"sClass": "","aTargets":[1]}, 
        {"sClass": "","aTargets":[2]}, 
        {"sClass": "","aTargets":[3]}, 
        {"sClass": "","aTargets":[4]}, 
        {"sClass": "","aTargets":[5]}, 
        {"sClass": "arf_action_cell","aTargets":[6]} 
    ];
    
    dtobj.language = {
        "searchPlaceholder":__ARF_SEARCH_PLACEHOLDER,
        "search":"",
    };
    
    jQuery('#arf_paypal_list_form #example').dataTable(dtobj);
}

function arf_paypal_transaction_form_init() {
    var dateformat = jQuery('#datepicker_format').val();
    var date_locale = jQuery("#datepicker_locale").val();
    var dateformat_new = jQuery("#datepicker_format_new").val();
    var date_start = jQuery('#datepicker_start_date').val();
    var date_end = jQuery("#datepicker_end_date").val();

    jQuery("#datepicker_from").datetimepicker({
        useCurrent: false,
        format: dateformat,
        locale: date_locale,
        minDate: moment(date_start, dateformat_new),
        maxDate: moment(date_end, dateformat_new)
    });
    jQuery("#datepicker_to").datetimepicker({
        useCurrent: false,
        format: dateformat,
        locale: date_locale,
        minDate: moment(date_start, dateformat_new),
        maxDate: moment(date_end, dateformat_new)
    });
    
    jQuery.fn.dataTableExt.oPagination.four_button = {
        "fnInit": function(oSettings, nPaging, fnCallbackDraw) {
            nFirst = document.createElement('span');
            nPrevious = document.createElement('span');
            var nInput = document.createElement('input');
            var nPage = document.createElement('span');
            var nOf = document.createElement('span');
            nOf.className = "paginate_of";
            nInput.className = "current_page_no";
            nPage.className = "paginate_page";
            nInput.type = "text";
            nInput.style.width = "40px";
            nInput.style.height = "26px";
            nInput.style.display = "inline";
            nPaging.appendChild(nPage);
            jQuery(nInput).keyup(function(e) {
                if (e.which == 38 || e.which == 39) {
                    this.value++;
                } else if ((e.which == 37 || e.which == 40) && this.value > 1) {
                    this.value--;
                }
                if (this.value == "" || this.value.match(/[^0-9]/)) {
                    return;
                }
                var iNewStart = oSettings._iDisplayLength * (this.value - 1);
                if (iNewStart > oSettings.fnRecordsDisplay()) {
                    oSettings._iDisplayStart = (Math.ceil((oSettings.fnRecordsDisplay() - 1) / oSettings._iDisplayLength) - 1) * oSettings._iDisplayLength;
                    fnCallbackDraw(oSettings);
                    return;
                }
                oSettings._iDisplayStart = iNewStart;
                fnCallbackDraw(oSettings);
            });
            var nNext = document.createElement('span');
            var nLast = document.createElement('span');
            var nFirst = document.createElement('span');
            var nPrevious = document.createElement('span');
            var nPage = document.createElement('span');
            var nOf = document.createElement('span');
            nNext.style.backgroundRepeat = "no-repeat";
            nNext.style.backgroundPosition = "center";
            nNext.title = "Next";
            nLast.style.backgroundRepeat = "no-repeat";
            nLast.style.backgroundPosition = "center";
            nLast.title = "Last";
            nFirst.style.backgroundRepeat = "no-repeat";
            nFirst.style.backgroundPosition = "center";
            nFirst.title = "First";
            nPrevious.style.backgroundRepeat = "no-repeat";
            nPrevious.style.backgroundPosition = "center";
            nPrevious.title = "Previous";
            nFirst.appendChild(document.createTextNode(' '));
            nPrevious.appendChild(document.createTextNode(' '));
            nLast.appendChild(document.createTextNode(' '));
            nNext.appendChild(document.createTextNode(' '));
            nOf.className = "paginate_button nof";
            nPaging.appendChild(nFirst);
            nPaging.appendChild(nPrevious);
            nPaging.appendChild(nInput);
            nPaging.appendChild(nOf);
            nPaging.appendChild(nNext);
            nPaging.appendChild(nLast);
            jQuery(nFirst).click(function() {
                if (jQuery(this).hasClass('paginate_disabled_first')) { return; }
                oSettings.oApi._fnPageChange(oSettings, "first");
                fnCallbackDraw(oSettings);
            });
            jQuery(nPrevious).click(function() {
                if (jQuery(this).hasClass('paginate_disabled_previous')) { return; }
                oSettings.oApi._fnPageChange(oSettings, "previous");
                fnCallbackDraw(oSettings);
            });
            jQuery(nNext).click(function() {
                if (jQuery(this).hasClass('paginate_disabled_next')) { return; }
                oSettings.oApi._fnPageChange(oSettings, "next");
                fnCallbackDraw(oSettings);
            });
            jQuery(nLast).click(function() {
                if (jQuery(this).hasClass('paginate_disabled_last')) { return; }
                oSettings.oApi._fnPageChange(oSettings, "last");
                fnCallbackDraw(oSettings);
            });
            jQuery(nFirst).bind('selectstart', function() {
                return false;
            });
            jQuery(nPrevious).bind('selectstart', function() {
                return false;
            });
            jQuery('span', nPaging).bind('mousedown', function() {
                return false;
            });
            jQuery('span', nPaging).bind('selectstart', function() {
                return false;
            });
            jQuery(nNext).bind('selectstart', function() {
                return false;
            });
            jQuery(nLast).bind('selectstart', function() {
                return false;
            });
        },
        "fnUpdate": function(oSettings, fnCallbackDraw) {
            if (!oSettings.aanFeatures.p) {
                return;
            }
            var an = oSettings.aanFeatures.p;
            for (var i = 0, iLen = an.length; i < iLen; i++) {
                var buttons = an[i].getElementsByTagName('span');
                if (oSettings._iDisplayStart === 0) {
                    buttons[1].className = "paginate_disabled_first arfhelptip";
                    buttons[2].className = "paginate_disabled_previous arfhelptip";
                } else {
                    buttons[1].className = "paginate_enabled_first arfhelptip";
                    buttons[2].className = "paginate_enabled_previous  arfhelptip";
                }
                if (oSettings.fnDisplayEnd() == oSettings.fnRecordsDisplay()) {
                    buttons[4].className = "paginate_disabled_next  arfhelptip";
                    buttons[5].className = "paginate_disabled_last  arfhelptip";
                } else {
                    buttons[4].className = "paginate_enabled_next arfhelptip";
                    buttons[5].className = "paginate_enabled_last  arfhelptip";
                }
                if (!oSettings.aanFeatures.p) {
                    return;
                }
                var iPages = Math.ceil((oSettings.fnRecordsDisplay()) / oSettings._iDisplayLength);
                var iCurrentPage = Math.ceil(oSettings._iDisplayStart / oSettings._iDisplayLength) + 1;
                if (document.getElementById('of_grid')) {
                    var of_grid = document.getElementById('of_grid').value;
                } else {
                    var of_grid = 'of';
                }
                var an = oSettings.aanFeatures.p;
                for (var i = 0, iLen = an.length; i < iLen; i++) {
                    var spans = an[i].getElementsByTagName('span');
                    var inputs = an[i].getElementsByTagName('input');
                    spans[spans.length - 3].innerHTML = " " + of_grid + " " + iPages;
                    inputs[0].value = iCurrentPage;
                }
            }
        }
    }
    var msg_for_blank_table = __NO_FORM_FOUND_MSG;

    var dtobj = {
        "oLanguage": {
            "sProcessing": "",
            "sEmptyTable": msg_for_blank_table,
            "sZeroRecords": msg_for_blank_table
        },
        "sDom": '<"H"lfr>t<"footer"ip>',
        "sPaginationType": "four_button",
        "bJQueryUI": true,
        "bPaginate": true,
        "bAutoWidth": false,
        "aoColumnDefs": [{
            "bVisible": false,
            "aTargets": []
        }, {
            "bSortable": false,
            "aTargets": [0, 8]
        }],
        "ordering": true,
        "order":[[5,'desc']],
        "oColVis": {
            "aiExclude": [0, 8]
        },
    }; 

    
    dtobj.bProcessing = true;
    dtobj.bServerSide = true;
    dtobj.sAjaxSource = ajaxurl;
    dtobj.sServerMethod = "POST";
    dtobj.fnServerParams = function (aoData) {
        var form_id = jQuery('#arf_paypal_forms_dropdown').val();
        var start_date = jQuery('#datepicker_from').val();
        var end_date = jQuery('#datepicker_to').val();
        aoData.push(
            {'name': 'action', 'value': 'arf_retrieve_paypal_transaction_data'},
            {'name': 'form_id', 'value': form_id},
            {'name': 'start_date', 'value': start_date},
            {'name': 'end_date', 'value': end_date},
        );
    };
    dtobj.fnPreDrawCallback = function () {
            jQuery("#arf_full_width_loader").show();
    };
    dtobj.fnDrawCallback = function (oSettings) {
        jQuery("#arf_full_width_loader").hide();
        jQuery('.arfhelptip').tipso('destroy');
        jQuery('.arfhelptip').tipso({
            position: 'top',
            maxWidth: '400',
            useTitle: true,
            background: '#444444',
            color: '#ffffff',
            width: 'auto'
        });
    };
    dtobj.aoColumnDefs = [            
        {"bSortable": false, "aTargets": [0,4]},
        {"sClass": "", "sWidth": "30px", "aTargets":[0]}, 
        {"sClass": "","aTargets":[1]}, 
        {"sClass": "","aTargets":[2]}, 
        {"sClass": "","aTargets":[3]}, 
        {"sClass": "","aTargets":[4]}, 
        {"sClass": "","aTargets":[5]}, 
        {"sClass": "","aTargets":[6]}, 
        {"sClass": "","aTargets":[7]}, 
        {"sClass": "arf_action_cell","aTargets":[8]} 
    ];
    
    dtobj.language = {
        "searchPlaceholder":__ARF_SEARCH_PLACEHOLDER,
        "search":""
    };
    
    jQuery('#arf_paypal_order_form #example').dataTable(dtobj);
}