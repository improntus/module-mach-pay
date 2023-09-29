define(
    [
        'Magento_Checkout/js/view/payment/default'
    ],
    function (Component) {
        'use strict';
        return Component.extend({
            defaults: {
                template: 'Improtus_MachPay/payment/machpay',
                code: 'machpay',
                active: false
            },
            // add required logic here
        });
    }
);
