### Responsibilities
* Implement payment SDK and checkout SDK Config interfaces
* Fetch settings from magento and provide it to SDK's adapters

### Todo
* Implement Payment SDK ConfigInterface, Checkout SDK ConfigInterface
```
class Webbhuset\CollectorBank\Config
implements CollectorBank\PaymentSDK\Config\ConfigInterface
,CollectorBank\CheckoutSDK\Config\ConfigInterface
{
    public function getUsername()
    {
        return $this->scopeConfig->get('path/to/username');
    }

}

```