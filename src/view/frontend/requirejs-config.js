var config = {
    map: {
        '*': {
            collectorCheckout: 'Webbhuset_CollectorBankCheckout/js/checkout',
        }
    },
    config: {
        mixins: {
            'Magento_Checkout/js/action/get-payment-information': {
                'Webbhuset_CollectorBankCheckout/js/action/suspend-wrapper': true
            },
            'Magento_Checkout/js/action/set-shipping-information' : {
                'Webbhuset_CollectorBankCheckout/js/action/suspend-wrapper': true
            }
        }
    }
};
