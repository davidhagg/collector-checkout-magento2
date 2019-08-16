<?php

namespace Webbhuset\CollectorBankCheckout\Controller\Success;

class Index extends \Magento\Framework\App\Action\Action
{
    protected $pageFactory;
    protected $checkoutSession;
    protected $collectorAdapter;
    protected $orderManager;
    protected $orderDataHandler;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Webbhuset\CollectorBankCheckout\Adapter $collectorAdapter,
        \Webbhuset\CollectorBankCheckout\Checkout\Order\ManagerFactory $orderManager,
        \Webbhuset\CollectorBankCheckout\Data\OrderHandlerFactory $orderDataHandler,
        \Magento\Framework\View\Result\PageFactory $pageFactory
    ) {
        $this->pageFactory      = $pageFactory;
        $this->collectorAdapter = $collectorAdapter;
        $this->orderManager     = $orderManager;
        $this->orderDataHandler = $orderDataHandler;

        parent::__construct($context);
    }

    public function execute()
    {
        $reference = $this->getRequest()->getParam('reference');
        $orderManager = $this->orderManager->create();

        $page = $this->pageFactory->create();
        try {
            $order = $orderManager->getOrderByPublicToken($reference);
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            // error handling and logging
            $page->getLayout()
                ->getBlock('collectorbank_success_iframe');

            return $page;
        }

        $orderDataHandler = $this->orderDataHandler->create();
        $publicToken = $orderDataHandler->getPublicToken($order);

        $iframeConfig = new \CollectorBank\CheckoutSDK\Config\IframeConfig(
            $publicToken
        );
        $iframe = \CollectorBank\CheckoutSDK\Iframe::getScript($iframeConfig);



        $page->getLayout()
            ->getBlock('collectorbank_success_iframe')
            ->setIframe($iframe);

        return $page;
    }
}
