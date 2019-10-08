<?php

namespace Webbhuset\CollectorBankCheckout\Controller\Success;

/**
 * Class Index
 *
 * @package Webbhuset\CollectorBankCheckout\Controller\Success
 */
class Index extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $pageFactory;
    /**
     * @var
     */
    protected $checkoutSession;
    /**
     * @var \Webbhuset\CollectorBankCheckout\Adapter
     */
    protected $collectorAdapter;
    /**
     * @var \Webbhuset\CollectorBankCheckout\Checkout\Order\ManagerFactory
     */
    protected $orderManager;
    /**
     * @var \Webbhuset\CollectorBankCheckout\Data\OrderHandlerFactory
     */
    protected $orderDataHandler;
    /**
     * @var \Webbhuset\CollectorBankCheckout\Logger\Logger
     */
    protected $logger;

    /**
     * Index constructor.
     *
     * @param \Magento\Framework\App\Action\Context                          $context
     * @param \Webbhuset\CollectorBankCheckout\Adapter                       $collectorAdapter
     * @param \Webbhuset\CollectorBankCheckout\Checkout\Order\ManagerFactory $orderManager
     * @param \Webbhuset\CollectorBankCheckout\Data\OrderHandlerFactory      $orderDataHandler
     * @param \Magento\Framework\View\Result\PageFactory                     $pageFactory
     * @param \Webbhuset\CollectorBankCheckout\Logger\Logger                 $logger
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Webbhuset\CollectorBankCheckout\Adapter $collectorAdapter,
        \Webbhuset\CollectorBankCheckout\Checkout\Order\ManagerFactory $orderManager,
        \Webbhuset\CollectorBankCheckout\Data\OrderHandlerFactory $orderDataHandler,
        \Magento\Framework\View\Result\PageFactory $pageFactory,
        \Webbhuset\CollectorBankCheckout\Logger\Logger $logger
    ) {
        $this->pageFactory      = $pageFactory;
        $this->collectorAdapter = $collectorAdapter;
        $this->orderManager     = $orderManager;
        $this->orderDataHandler = $orderDataHandler;
        $this->logger           = $logger;

        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|\Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        $reference = $this->getRequest()->getParam('reference');
        $orderManager = $this->orderManager->create();

        $page = $this->pageFactory->create();
        try {
            $order = $orderManager->getOrderByPublicToken($reference);
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            $page->getLayout()
                ->getBlock('collectorbank_success_iframe');
            $this->logger->addCritical(
                "Failed to load success page - Could not open order by publicToken: $reference. "
                . $e->getMessage()
            );
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
            ->setIframe($iframe)
            ->setSuccessOrder($order);

        return $page;
    }
}
