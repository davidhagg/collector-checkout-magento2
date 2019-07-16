
## Responsibilities

* Takes Magento quote
* returns Collector order data structure


NOTE:
have in mind that customer may have installed third party order totals etc.
You may want to flip the logic so instead of
add
[X Y Z] to the order, instead get a collection of the actual totals and ignore some
(tax, subtotal, cost_total, grand_total etc)
You also may want to have plugins in mind so that other developers can jack in to add custom fees and other stuff easy
dispatch an event? make methods public etc
"all necessary information" may include a basic address in order to have the checkout actually have a valid quote
it may also include choosing a shipping method etc?