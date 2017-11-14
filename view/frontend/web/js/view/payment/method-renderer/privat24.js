define(
    [
        'Magento_Payment/js/view/payment/cc-form',
        'jquery',
        'mage/url',
        'Magento_Checkout/js/action/place-order',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Magento_Payment/js/model/credit-card-validation/validator'
    ],
    function (Component) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Privat24_Privat24/payment/checkout'
            },
            getCode: function() {
                return 'privat24';
            },
            afterPlaceOrder: function () {
                jQuery.post('/privat24/checkout/form', {}
                ).done(function(data) {
                    if (!data.status) {
                        return
                    }
                    if (data.status == 'success') {
                        if (data.content) {
                            var html = '<div id="privat24SubmitFrom" style="display: none;">' + data.content + '</div>';
                            jQuery('body').append(html);
                            jQuery('#privat24-form').submit();
                        }
                    } else {
                        if (data.redirect) {
                            window.location = data.redirect;
                        }
                    }
                });
            },
            isActive: function () {
                return true;
            },
            validate: function() {
                var $form = jQuery('#' + this.getCode() + '-form');
                return $form.validation() && $form.validation('isValid');
            }
        });
    }
);
