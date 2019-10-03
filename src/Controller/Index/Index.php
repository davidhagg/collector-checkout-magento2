<?php

namespace Webbhuset\CollectorBankCheckout\Controller\Index;

class Index extends \Magento\Framework\App\Action\Action
{
    protected $pageFactory;
    protected $checkoutSession;
    protected $collectorAdapter;
    protected $quoteDataHandler;
    protected $quoteConverter;
    protected $quoteRepository;
    protected $config;
    protected $quoteValidator;
    protected $quoteComparer;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Webbhuset\CollectorBankCheckout\Adapter $collectorAdapter,
        \Webbhuset\CollectorBankCheckout\Data\QuoteHandler $quoteDataHandler,
        \Webbhuset\CollectorBankCheckout\QuoteConverter $quoteConverter,
        \Magento\Framework\View\Result\PageFactory $pageFactory,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Webbhuset\CollectorBankCheckout\Config\Config $config,
        \Webbhuset\CollectorBankCheckout\QuoteValidator $quoteValidator,
        \Webbhuset\CollectorBankCheckout\QuoteComparerFactory $quoteComparer
    ) {
        $this->pageFactory      = $pageFactory;
        $this->checkoutSession  = $checkoutSession;
        $this->collectorAdapter = $collectorAdapter;
        $this->quoteDataHandler = $quoteDataHandler;
        $this->quoteConverter   = $quoteConverter;
        $this->quoteRepository  = $quoteRepository;
        $this->config           = $config;
        $this->quoteValidator   = $quoteValidator;
        $this->quoteComparer    = $quoteComparer;

        return parent::__construct($context);
    }

    public function execute()
    {
        $page = $this->pageFactory->create();
        $quote = $this->checkoutSession->getQuote();

        if (!$this->quoteComparer->create()->isCurrencyMatching()) {
            $this->messageManager->addErrorMessage(__('Currencies are not matching with what is allowed in CollectorBank checkout'));
        }

        $quoteCheckoutErrors = $this->quoteValidator->getErrors($quote);
        if (!empty($quoteCheckoutErrors)) {
            foreach ($quoteCheckoutErrors as $error) {
                $this->messageManager->addErrorMessage(__('Cannot use Collector Checkout: ') . $error);
            }

            return $this->resultRedirectFactory->create()->setPath('checkout/index');
        }

        $customerType = $this->getRequest()->getParam('customerType');

        if (\Webbhuset\CollectorBankCheckout\Config\Source\Customer\Type::BOTH_CUSTOMERS == $this->config->getCustomerTypeAllowed()
            && $customerType
        ) {
            $this->quoteDataHandler->setCustomerType($quote, $customerType);
            $this->quoteDataHandler->setPublicToken($quote, null);
            $this->quoteDataHandler->setPrivateId($quote, null);
            $this->quoteRepository->save($quote);
        }

        $publicToken = $this->collectorAdapter->initOrSync($quote);

        $iframeConfig = new \CollectorBank\CheckoutSDK\Config\IframeConfig(
            $publicToken,
            $this->config->getStyleDataLang(),
            $this->config->getStyleDataPadding(),
            $this->config->getStyleDataContainerId(),
            $this->config->getStyleDataActionColor(),
            $this->config->getStyleDataActionTextColor()
        );
        $iframe = \CollectorBank\CheckoutSDK\Iframe::getScript($iframeConfig);

        $block = $page
            ->getLayout()
            ->getBlock('collectorbank_checkout_iframe')
            ->setIframe($iframe);

        return $page;
    }
}
