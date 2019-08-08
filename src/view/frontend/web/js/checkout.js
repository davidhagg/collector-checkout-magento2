define([
    'uiElement',
    'Magento_Checkout/js/model/url-builder',
    'mage/storage',
    'Webbhuset_CollectorBankCheckout/js/iframe',
], function (Element, urlBuilder, storage, collectorIframe) {
    'use strict';
    return Element.extend({
        defaults: {
            template: 'Webbhuset_CollectorBankCheckout/checkout',
        },
        updateUrl: '',
        getUpdateUrl: function(eventName, publicId) {
            return this.updateUrl + '?event=' + eventName + '&quoteid=' + publicId
        },
        initialize: function (config) {
            var self = this;
            self.updateUrl = window.checkoutConfig.updateUrl;

            document.addEventListener('collectorCheckoutCustomerUpdated', self.listener.bind(self));
            document.addEventListener('collectorCheckoutOrderValidationFailed', self.listener.bind(self));
            document.addEventListener('collectorCheckoutLocked', self.listener.bind(self));
            document.addEventListener('collectorCheckoutUnlocked', self.listener.bind(self));
            document.addEventListener('collectorCheckoutReloadedByUser', self.listener.bind(self));
            document.addEventListener('collectorCheckoutExpired', self.listener.bind(self));
            document.addEventListener('collectorCheckoutResumed', self.listener.bind(self));

            this._super();
        },
        listener: function(event) {
            switch(event.type) {
                case 'collectorCheckoutCustomerUpdated':
                    /*
                        Occurs when the checkout client-side detects any change to customer information,
                        such as a changed email, mobile phone number or delivery address.
                        This event is also fired the first time the customer is identified.
                    */
                    this.updateCart(event);
                    break;

                case 'collectorCheckoutOrderValidationFailed':
                    /*
                        This event is only used if you use the optional validate order functionality.
                        Occurs if a purchase is denied due to the result of the backend order validation
                        (in other words if the response from the validationUri set at initialization is not successful).
                        This usually means that one or more items in the cart is no longer in stock.
                    */
                    break;

                case 'collectorCheckoutLocked':
                    /*
                        Occurs when no user input should be accepted, for instance during processing of a purchase.
                    */
                    break;

                case 'collectorCheckoutUnlocked':
                        /*
                            Occurs after a locked event when it is safe to allow user input again.
                            For instance after a purchase has been processed (regardless of whether the purchase was successful or not).
                        */
                    break;

                case 'collectorCheckoutReloadedByUser':
                    /*
                        Occurs when the user has clicked a "reload" button in the checkout.
                        This can occur when there is a version mismatch in the checkout.
                        An example is when adding an item to the cart and before calling suspend/resume trying to set an alternative delivery address.
                        This will show a message to the user that there is a conflict and the checkout must be reloaded.
                    */
                    break;

                case 'collectorCheckoutExpired':
                    /*
                        Occurs when the checkout session indicated by the public token is no longer valid.
                        At the moment this is after 7 days since the cart was initialized.
                        An new cart initialization has to be made and the new public token set on a new loader script.
                    */
                    break;

                case 'collectorCheckoutResumed':
                    /*
                        Occurs when the checkout has loaded new data and is back in its normal state after a suspend.
                    */
                    break;
            }
        },
        updateCart: function(event) {
            var self = this;
            collectorIframe.suspend();
            var payload = {}

            return storage.post(
                self.getUpdateUrl(event.type, event.detail), JSON.stringify(payload), true
            ).fail(
                function (response) {
                    console.error(response);
                }
            ).success(
                function () {
                    collectorIframe.resume();
                }
            );
        }
    });
});
