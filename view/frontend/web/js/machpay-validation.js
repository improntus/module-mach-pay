define([
    'jquery',
    'domReady',
    'mage/url'
], function ($, dom, url) {
    'use strict';

    function validate_order_status(token, ajax_url) {
        $.ajax({
            url: ajax_url,
            data: {
                token: token
            },
            type: 'POST',
            success: function (data) {
                if (data.status === 'COMPLETED' || data.status === 'CONFIRMED') {
                    window.location.href = url.build('checkout/onepage/success');
                } else if (data.status === 'EXPIRED' || data.status === 'REVERSED' ||  data.status === 'FAILED') {
                    window.location.href = url.build('checkout/onepage/failure');
                }
            },
            error: function (error) {
                console.error('Error: ', error);
            }
        });
    }

    return function (config) {
        const token_data = config.token;
        const ajax_url = config.ajaxUrl;

        var refreshIntID = setInterval(function () {
            validate_order_status(token_data, ajax_url);
        }, 5000);

        setTimeout(function () {
            clearInterval(refreshIntID);
            window.location.href = url.build('checkout/onepage/failure');
        }, 20 * 60 * 1000);
    }
});
