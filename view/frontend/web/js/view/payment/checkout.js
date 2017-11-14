define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        Component,
        rendererList
    ) {
        'use strict';
        rendererList.push(
            {
                type: 'privat24',
                component: 'Privat24_Privat24/js/view/payment/method-renderer/privat24'
            }
        );
        /** Add view logic here if needed */
        return Component.extend({});
    }
);
