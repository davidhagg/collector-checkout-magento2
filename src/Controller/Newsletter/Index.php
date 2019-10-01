<?php

namespace Webbhuset\CollectorBankCheckout\Controller\Newsletter;

class Index extends \Magento\Framework\App\Action\Action
{
    protected $quoteHandler;
    protected $checkoutSession;
    protected $resultJsonFactory;
    protected $quoteRepository;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Webbhuset\CollectorBankCheckout\Data\QuoteHandler $quoteHandler,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
    ) {
        $this->checkoutSession   = $checkoutSession;
        $this->quoteHandler      = $quoteHandler;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->quoteRepository   = $quoteRepository;

        parent::__construct($context);
    }

    public function execute()
    {
        $quote = $this->checkoutSession->getQuote();
        $subscribe = ('true' == $this->getRequest()->getParam('subscribe')) ? 1 : 0;

        $this->quoteHandler->setNewsletterSubscribe($quote, $subscribe);
        $this->quoteRepository->save($quote);

        $result = $this->resultJsonFactory->create();
        $result->setData(
            [
                'newsletter' => $subscribe
            ]
        );

        return $result;
    }
}
