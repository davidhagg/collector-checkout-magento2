### Responsibilities
* Runs capture, cancel, void, authorize, refund methods

### Todo
* Create Webbhuset\CollectorBankCheckout\Gateway\Command\CollectorBankCommand, implement all methods needed
* Use payments SDK to connect to Collector payments API


Notice:
some payment methods in Collectors end (Swish for example) cannot be activated by the module,
make a path for these types of exceptions

You may override the "variables" canXXY with methods
like
public function canCapturePartial() etc.

#### Capture method
Example code to use payment SDK:
```
<?php

public function capture()
{
    $config = new Config();

    $adapter = new Adapter($config);

    $invoiceAdmin = new InvoiceAdministration($adapter);
    try {
        $response = $invoiceAdmin->activateInvoice($correlationId, $invoiceNo);
    } catch (RequestError $e) {
        // do stuff
    }
}

```
