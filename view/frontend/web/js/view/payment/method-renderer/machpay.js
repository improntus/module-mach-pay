define(
    [
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Magento_Checkout/js/action/place-order',
        'Magento_Checkout/js/model/full-screen-loader',
        'mage/url',
        'Improntus_MachPay/js/machpay-validation',
        'jquery'
    ],
    function (
        Component,
        quote,
        additionalValidators,
        placeOrderAction,
        fullScreenLoader,
        urlBuilder,
        MachPayValidation,
        $
    ) {
        'use strict';
        return Component.extend({
            defaults: {
                template: 'Improntus_MachPay/payment/machpay',
                code: 'machpay',
                active: false
            },

            getCode: function() {
                return this.code;
            },

            getTitle: function () {
                return window.checkoutConfig.payment[this.getCode()].title;
            },

            getLogo: function () {
                return window.checkoutConfig.payment[this.getCode()].logo;
            },

            getOrderTotal: function () {
                return quote.totals()['grand_total'];
            },

            afterPlaceOrder: function () {
                fullScreenLoader.startLoader();
                window.location.href = window.checkoutConfig.payment[this.getCode()].redirect_url;
                //validate transaction
                setTimeout(function() {
                    $.ajax({
                        url: urlBuilder.build('machpay/order/getToken'),
                        type: 'POST',
                        success: function (data) {
                            let tokenTransaction = data.token;
                            let ajaxValidationUrl = urlBuilder.build('machpay/order/validation');
                            //payment validation
                            MachPayValidation({token: tokenTransaction, ajaxUrl: ajaxValidationUrl});
                        },
                        error: function (error) {
                            console.error('Error: ', error);
                        }
                    });
                }, 5000);
            },

            placeOrder: function (data, event) {
                let self = this;
                if (event) {
                    event.preventDefault();
                }
                if (this.validate() && additionalValidators.validate()) {
                    this.isPlaceOrderActionAllowed(false);
                    this.getPlaceOrderDeferredObject()
                        .fail(function () {
                            self.isPlaceOrderActionAllowed(true);
                        })
                        .done(function () {
                            self.afterPlaceOrder();
                        })
                        .always(function () {
                            self.isPlaceOrderActionAllowed(true);
                        });
                    return true;
                }
                return false;
            },
            getPlaceOrderDeferredObject: function () {
                $('button.checkout').attr('disabled', 'disabled');
                return $.when(
                    placeOrderAction(this.getData(), this.messageContainer)
                );
            },
        });
    }
);
