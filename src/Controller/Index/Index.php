<?php

namespace Webbhuset\CollectorBankCheckout\Controller\Index;

class Index extends \Magento\Framework\App\Action\Action
{
    protected $pageFactory;
    protected $checkoutSession;
    protected $collectorAdapter;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Webbhuset\CollectorBankCheckout\Adapter $collectorAdapter,
        \Magento\Framework\View\Result\PageFactory $pageFactory
    ) {
        $this->pageFactory = $pageFactory;
        $this->checkoutSession = $checkoutSession;
        $this->collectorAdapter = $collectorAdapter;

        return parent::__construct($context);
    }

    public function execute()
    {
        $page = $this->pageFactory->create();
        $quote = $this->checkoutSession->getQuote();

        if (!$quote->hasItems()) {
            return $page;
        }

        $collectorSession = $this->collectorAdapter->initialize($quote);

        $block = $page
            ->getLayout()
            ->getBlock('collectorbank_index_index')
            ->setCollectorSession($collectorSession);

        return $page;
    }
}
