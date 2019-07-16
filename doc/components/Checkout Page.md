## Responsibilities

* Initialize Collector iframe
* Set initialized response data from collector on quote
* Cart on checkout page with decrement and increment, remove items
* Coupon code input box
* Order review
* Shipping methods
* Switch button/link between B2C and B2B
* Data layer pusher for GTM

```
public function execute()
{
    $collectorDataStructure = $this->converter->convert($quote);
    $data = $sdk->initalize($colllectorDataStructure);
    $this->lbasfasfa->saveDataOnQuote($data, $quote);
}
```
