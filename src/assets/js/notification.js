jQuery(document).ready(function ($) {
    'use strict';
    if (orderData.hasSubscription) {
        const newTextOrder = document.querySelector('.wc-order-data-row');
        newTextOrder.append('O valor final pode variar com base nas condições da assinatura.');
    }
});