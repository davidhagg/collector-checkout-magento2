## Responsibilities
* Log errors to a specific collector log file

Extend magentos logger, but change output file
so we can call `$this->collectorLogger->debug('debug info')`
and it will be saved to `var/log/collector_checkout.log`
