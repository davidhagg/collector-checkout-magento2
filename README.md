# Collector Checkout for Magento 2

## Requirements:

Magento 2.2.0 or above
PHP 7.2 and above

Installation, run these commands from the Magento base folder:

composer require webbhuset/collector-checkout-magento2
bin/magento module:enable Webbhuset_CollectorCheckout
bin/magento setup:upgrade && bin/magento setup:di:compile && bin/magento cache:flush

After that, head to administration -> Stores -> Configuration -> Payment methods -> Collector Bank Checkout and click configure to configure Collector Checkout.

To upgrade run composer update webbhuset/collector-checkout-magento2 --with-dependencies. Then run bin/magento setup:upgrade && bin/magento setup:di:compile && bin/magento cache:flush from your base folder.
