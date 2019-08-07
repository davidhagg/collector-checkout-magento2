<?php

namespace Webbhuset\CollectorBankCheckout\Controller\Index;

class Index extends \Magento\Framework\App\Action\Action
{
    protected $pageFactory;
    protected $checkoutSession;
    protected $collectorAdapter;
    protected $quoteDataHandler;
    protected $quoteConverter;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Webbhuset\CollectorBankCheckout\Adapter $collectorAdapter,
        \Webbhuset\CollectorBankCheckout\Data\QuoteHandler $quoteDataHandler,
        \Webbhuset\CollectorBankCheckout\QuoteConverter $quoteConverter,
        \Magento\Framework\View\Result\PageFactory $pageFactory
    ) {
        $this->pageFactory = $pageFactory;
        $this->checkoutSession = $checkoutSession;
        $this->collectorAdapter = $collectorAdapter;
        $this->quoteDataHandler = $quoteDataHandler;
        $this->quoteConverter = $quoteConverter;

        return parent::__construct($context);
    }

    public function execute()
    {
        $page = $this->pageFactory->create();
        $quote = $this->checkoutSession->getQuote();

        if (!$quote->hasItems()) {
            return $page;
        }

        $publicToken = $this->quoteDataHandler->getPublicToken($quote);
        if ($publicToken) {
            $checkoutData = $this->collectorAdapter->acquireCheckoutInformationFromQuote($quote);
            $oldFees = $checkoutData->getFees();
            $oldCart = $checkoutData->getCart();
            $newFees = $this->quoteConverter->getFees($quote);
            if ($oldFees != $newFees) {
                $this->collectorAdapter->updateFees($quote);
            }

            $newCart = $this->quoteConverter->getCart($quote);
            if ($oldCart != $newCart) {
                $this->collectorAdapter->updateCart($quote);
            }
        } else {
            $collectorSession = $this->collectorAdapter->initialize($quote);
            $publicToken = $collectorSession->getPublicToken();
        }

        $iframeConfig = new \CollectorBank\CheckoutSDK\Config\IframeConfig(
            $publicToken
        );
        $iframe = \CollectorBank\CheckoutSDK\Iframe::getScript($iframeConfig);

        $block = $page
            ->getLayout()
            ->getBlock('collectorbank_checkout_iframe')
            ->setIframe($iframe);

        return $page;
    }
}
