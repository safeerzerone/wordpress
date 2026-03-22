var arf_paypal_actions = [];
"use strict";

function arf_add_paypal_action(action_name, callback, priority) {
    if (!priority) {
        priority = 10;
    }
    if (priority > 100) {
        priority = 100;
    }
    if (priority < 0) {
        priority = 0;
    }
    if (typeof arf_paypal_actions[action_name] == 'undefined') {
        arf_paypal_actions[action_name] = [];
    }
    if (typeof arf_paypal_actions[action_name][priority] == 'undefined') {
        arf_paypal_actions[action_name][priority] = []
    }
    arf_paypal_actions[action_name][priority].push(callback);
    if ('undefined' != typeof window.arf_actions) {
        var arf_action_keys = Object.keys(window.arf_actions);
        if (arf_action_keys.length > 0) {
            window.arf_actions[action_name] = arf_paypal_actions[action_name];
        } else {
            window.arf_actions = arf_paypal_actions;
        }
    }
}
arf_add_paypal_action('reset_field_in_outsite', 'arf_check_paypal_form', 10);

function arf_check_paypal_form(params) {
    var object = params[0];
    var result = params[1];
    try {
        result = jQuery.parseJSON(result);
        if ('addon' == result.conf_method && jQuery("#arf_paypal_form").length > 0) {
            jQuery("#arf_paypal_form").find("input[type='submit']").hide();
            jQuery("#arf_paypal_form").submit();
        }
    } catch (e) {
        console.warn(result);
        console.warn(e);
    }
}
jQuery(document).ready(function() {
    if ('undefined' != typeof MutationObserver) {
        var mutationObserver = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
            	if( jQuery("#arf_paypal_form").length > 0 && mutation.addedNodes[0] == jQuery("#arf_paypal_form")[0] ){
                    jQuery("#arf_paypal_form").find("input[type='submit']").hide();
                	jQuery('#arf_paypal_form').submit();
            	}
            });
        });
        mutationObserver.observe(document.documentElement, {
            attributes: true,
            characterData: true,
            childList: true,
            subtree: true,
            attributeOldValue: true,
            characterDataOldValue: true
        });
    }

    if( jQuery('form.arf_paypal_form_normal').length > 0 ){
        jQuery('form.arf_paypal_form_normal').submit();
    }
});