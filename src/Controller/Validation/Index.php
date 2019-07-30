<?php

namespace Webbhuset\CollectorBankCheckout\Controller\Validation;
class Index extends \Magento\Framework\App\Action\Action
{
    public function execute()
    {
        $params = $this->getRequest()->getParams();

        if (!isset($params['orderReference'])) {
            // handle error
        }
        $orderReference = $params['orderReference'];

    }
}