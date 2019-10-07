<?php

namespace Webbhuset\CollectorBankCheckout\Gateway;

use Magento\Checkout\Model\ConfigProviderInterface;

class Config implements ConfigProviderInterface
{
    const CHECKOUT_CODE = "collectorbank_checkout";
    const PAYMENT_METHOD_NAME = "Collector Bank Checkout";
    const CHECKOUT_URL_KEY = "collectorcheckout";
    const REMOVE_PENDING_ORDERS_HOURS = 5;

    protected $config;
    protected $assetRepo;
    protected $urlBuilder;
    protected $request;

    public function __construct(
        \Webbhuset\CollectorBankCheckout\Config\Config $config,
        \Magento\Framework\View\Asset\Repository $assetRepo,
        \Magento\Framework\UrlInterface $urlBuilder,
        \Magento\Framework\App\RequestInterface $request
    ) {
        $this->config = $config;
        $this->assetRepo = $assetRepo;
        $this->urlBuilder = $urlBuilder;
        $this->request = $request;
    }

    public function getConfig()
    {
        if (!$this->config->getIsActive()) {
            return [];
        }

        return [
            'payment' => [
                'collector_checkout' => [
                    'image_remove_item' => $this->getViewFileUrl('Webbhuset_CollectorBankCheckout::images/times-solid.svg'),
                    'image_plus_qty' => $this->getViewFileUrl('Webbhuset_CollectorBankCheckout::images/plus-solid.svg'),
                    'image_minus_qty' => $this->getViewFileUrl('Webbhuset_CollectorBankCheckout::images/minus-solid.svg'),
                    'newsletter_url' => $this->getNewsletterUrl(),
                    'reinit_url' => $this->getReinitUrl(),
                ],
            ],
        ];
    }

    public function getNewsletterUrl()
    {
        return $this->urlBuilder->getUrl('collectorcheckout/newsletter');
    }

    public function getReinitUrl()
    {
        return $this->urlBuilder->getUrl('collectorcheckout/reinit');
    }

    public function getViewFileUrl($fileId, array $params = [])
    {
        try {
            $params = array_merge(['_secure' => $this->request->isSecure()], $params);
            return $this->assetRepo->getUrlWithParams($fileId, $params);
        } catch (LocalizedException $e) {
            $this->logger->critical($e);
            return $this->urlBuilder->getUrl('', ['_direct' => 'core/index/notFound']);
        }
    }
}
