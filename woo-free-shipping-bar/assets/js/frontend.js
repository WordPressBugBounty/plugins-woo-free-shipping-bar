(function ($) {
    'use strict';
    if (typeof _wfsb_params === "undefined"){
        return;
    }
    _wfsb_params.time_to_disappear = 1;
    _wfsb_params.displayTime = 20;
    $(document).ready(function () {
        if (typeof wc !== "undefined" && typeof wc.blocksCheckout !== "undefined"){
            let { registerCheckoutFilters   } = wc.blocksCheckout;
            registerCheckoutFilters( 'wfspb_add_checkout_filter', {
                proceedToCheckoutButtonLink: function (defaultValue, extensions, args){
                    let messages = extensions?.wfspb_message || args?.cart?.extensions?.wfspb_message;
                    if (!messages){
                        return defaultValue;
                    }
                    $(document).triggerHandler('wfspb-refresh-message', [messages]);
                    return defaultValue;
                }
            } );
        }
        let shipping_bar_show_delay,shipping_bar_timeout;
        $(document).on('click', '.wfspb-gift-box', function () {
            $(document).trigger('wfspb-show-bar');
        });
        $(document).on('wfspb-show-bar', function () {
            $(document).trigger('wfspb-design');
            clearTimeout(shipping_bar_timeout);
            clearTimeout(shipping_bar_show_delay);
            $('.wfspb-gift-box').addClass('wfspb-hidden');
            if (!$('#wfspb-top-bar').hasClass('wfspb-hidden')) {
                $('#wfspb-top-bar').fadeIn(500);
            }
            /*
            wp.wfspbConditionalVariable - Used in some specific conditions if the customer wants to add the condition.
            Eg:
            wp.wfspbConditionalVariable = true
            or
            wp.wfspbConditionalVariable = $('body')hasClass('home')
            */
            if (typeof wp.wfspbConditionalVariable === 'undefined' || wp.wfspbConditionalVariable) {
                if (_wfsb_params?.time_to_disappear && _wfsb_params?.displayTime) {
                    shipping_bar_timeout = setTimeout(function () {
                        $('.wfspb-gift-box').removeClass('wfspb-hidden');
                        $('#wfspb-top-bar').removeClass('wfsb-fixed').fadeOut(500);
                    }, parseInt(_wfsb_params.displayTime) * 1000);
                }
            }
        });
        setTimeout(function () {
            $(document).trigger('wfspb-bar-init');
        }, 100);
        $(document.body).on('wc_fragments_refreshed wc_fragments_ajax_error updated_checkout', function () {
            $(document).trigger('wfspb-design');
        });
        $(document).on('wfspb-bar-init', function () {
            if (_wfsb_params?.initialDelay) {
                shipping_bar_show_delay = setTimeout(function () {
                    $(document).trigger('wfspb-show-bar');
                }, parseInt(_wfsb_params.initialDelay) * 1000);
            } else {
                $(document).trigger('wfspb-show-bar');
            }
        });
        $(document).on('wfspb-refresh-message', function (e, fragments) {
            if (!_wfsb_params?.html_refresh || !fragments){
                return false;
            }
            let refresh = false;
            $.each(_wfsb_params.html_refresh, function (k,v){
                if ($(v).length && typeof fragments[v] !== "undefined"){
                    refresh = true;
                    $(v).replaceWith(fragments[v]);
                }
            });
            $(document).trigger('wfspb-design');
            return refresh;
        });
        $(document).on('wfspb-design', function () {
            let inline_css = '';
            if (!$('#wfspb-main-content').length || !$('#wfspb-main-content').html()){
                $('#wfspb-top-bar, #wfspb-shortcode').addClass('wfspb-hidden');
            }else {
                $('#wfspb-top-bar, #wfspb-shortcode, .woocommerce-free-shipping-bar-order').removeClass('wfspb-hidden');
            }
            if ($('#wfspb-current-progress').length) {
                inline_css += '#wfspb-current-progress{width: ' + $('#wfspb-current-progress').data('current_percent') + '%}';
            }
            if ($('.woocommerce-free-shipping-bar-order-bar-inner').length) {
                inline_css += '.woocommerce-free-shipping-bar-order-bar-inner{width: ' + $('.woocommerce-free-shipping-bar-order-bar-inner').data('current_percent') + '%}';
            }
            if (!$('#wfspb-design-inline-css').length) {
                $('head').append('<style id="wfspb-design-inline-css"></style>');
            }
            $('#wfspb-design-inline-css').html(inline_css);
        });
    });
}(jQuery));