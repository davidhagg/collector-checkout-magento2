define([
    'underscore',
    'jquery',
    'ko',
    'mage/utils/wrapper',
    'Webbhuset_CollectorBankCheckout/js/iframe',
], function (_, $, ko, wrapper, collectorIframe) {
    'use strict';

    return function (overriddenFunction) {
        return wrapper.wrap(overriddenFunction, function (originalAction) {
            collectorIframe.suspend();
            return originalAction().done(function () {
                collectorIframe.resume();
            });
        });
    }
});
