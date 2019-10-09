<?php

namespace Webbhuset\CollectorCheckout\Controller\Index;

/**
 * Class Index
 *
 * @package Webbhuset\CollectorCheckout\Controller\Index
 */
class Index extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $pageFactory;
    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;
    /**
     * @var \Webbhuset\CollectorCheckout\Adapter
     */
    protected $collectorAdapter;
    /**
     * @var \Webbhuset\CollectorCheckout\Data\QuoteHandler
     */
    protected $quoteDataHandler;
    /**
     * @var \Webbhuset\CollectorCheckout\QuoteConverter
     */
    protected $quoteConverter;
    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $quoteRepository;
    /**
     * @var \Webbhuset\CollectorCheckout\Config\Config
     */
    protected $config;
    /**
     * @var \Webbhuset\CollectorCheckout\QuoteValidator
     */
    protected $quoteValidator;
    /**
     * @var \Webbhuset\CollectorCheckout\QuoteComparerFactory
     */
    protected $quoteComparer;

    /**
     * Index constructor.
     *
     * @param \Magento\Framework\App\Action\Context                 $context
     * @param \Magento\Checkout\Model\Session                       $checkoutSession
     * @param \Webbhuset\CollectorCheckout\Adapter              $collectorAdapter
     * @param \Webbhuset\CollectorCheckout\Data\QuoteHandler    $quoteDataHandler
     * @param \Webbhuset\CollectorCheckout\QuoteConverter       $quoteConverter
     * @param \Magento\Framework\View\Result\PageFactory            $pageFactory
     * @param \Magento\Quote\Api\CartRepositoryInterface            $quoteRepository
     * @param \Webbhuset\CollectorCheckout\Config\Config        $config
     * @param \Webbhuset\CollectorCheckout\QuoteValidator       $quoteValidator
     * @param \Webbhuset\CollectorCheckout\QuoteComparerFactory $quoteComparer
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Webbhuset\CollectorCheckout\Adapter $collectorAdapter,
        \Webbhuset\CollectorCheckout\Data\QuoteHandler $quoteDataHandler,
        \Webbhuset\CollectorCheckout\QuoteConverter $quoteConverter,
        \Magento\Framework\View\Result\PageFactory $pageFactory,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Webbhuset\CollectorCheckout\Config\Config $config,
        \Webbhuset\CollectorCheckout\QuoteValidator $quoteValidator,
        \Webbhuset\CollectorCheckout\QuoteComparerFactory $quoteComparer
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

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Redirect|\Magento\Framework\Controller\ResultInterface|\Magento\Framework\View\Result\Page
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
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

        if (\Webbhuset\CollectorCheckout\Config\Source\Customer\Type::BOTH_CUSTOMERS == $this->config->getCustomerTypeAllowed()
            && $customerType
        ) {
            $this->quoteDataHandler->setCustomerType($quote, $customerType);
            $this->quoteDataHandler->setPublicToken($quote, null);
            $this->quoteDataHandler->setPrivateId($quote, null);
            $this->quoteRepository->save($quote);
        }

        $publicToken = $this->collectorAdapter->initOrSync($quote);

        $iframeConfig = new \Webbhuset\CollectorCheckoutSDK\Config\IframeConfig(
            $publicToken,
            $this->config->getStyleDataLang(),
            $this->config->getStyleDataPadding(),
            $this->config->getStyleDataContainerId(),
            $this->config->getStyleDataActionColor(),
            $this->config->getStyleDataActionTextColor()
        );
        $iframe = \Webbhuset\CollectorCheckoutSDK\Iframe::getScript($iframeConfig);

        $block = $page
            ->getLayout()
            ->getBlock('collectorbank_checkout_iframe')
            ->setIframe($iframe);

        return $page;
    }
}
