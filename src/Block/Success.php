<?php

namespace Webbhuset\CollectorBankCheckout\Block;

class Success extends \Magento\Framework\View\Element\Template
{
    protected $iframe;
    protected $serializer;
    protected $configProvider;
    protected $analytics;
    protected $enhancedEcommerce;
    protected $storeManager;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Checkout\Model\CompositeConfigProvider $configProvider,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        array $data = [],
        \Magento\Framework\Serialize\Serializer\Json $serializer = null,
        \Magento\Framework\Serialize\SerializerInterface $serializerInterface = null
    ) {
        parent::__construct($context, $data);
        $this->jsLayout = isset($data['jsLayout']) && is_array($data['jsLayout']) ? $data['jsLayout'] : [];
        $this->configProvider = $configProvider;
        $this->storeManager = $storeManager;
        $this->serializer = $serializerInterface ?: \Magento\Framework\App\ObjectManager::getInstance()
            ->get(\Magento\Framework\Serialize\Serializer\JsonHexTag::class);
    }

    public function getIframe()
    {
        return $this->iframe;
    }

    public function setIframe($iframe)
    {
        $this->iframe = $iframe;

        return $this;
    }

    public function getUpdateUrl()
    {
        return $this->getUrl('collectorcheckout/update');
    }

    public function getCheckoutConfig()
    {
        return $this->configProvider->getConfig();
    }

    public function getSerializedCheckoutConfig()
    {
        return  $this->serializer->serialize($this->getCheckoutConfig());
    }

    public function getAnalyticsDatalayer()
    {
        return json_encode($this->analytics);
    }

    public function getEnhancedEcommerceDatalayer()
    {
        return json_encode($this->enhancedEcommerce);
    }

    public function setSuccessOrder(\Magento\Sales\Api\Data\OrderInterface $order)
    {
        $this->setAnalyticsDatalayer($order);
        $this->setEnhancedEcommerceDatalayer($order);
    }

    protected function setAnalyticsDatalayer(\Magento\Sales\Api\Data\OrderInterface $order)
    {
        $products = [];
        foreach ($order->getAllVisibleItems() as $item) {
            $products[] = [
                'sku'      => $item->getSku(),
                'name'     => $item->getName(),
                'price'    => $item->getPrice(),
                'quantity' => round($item->getQtyOrdered())
            ];
        }

        $this->analytics = [
            'transactionId'       => $order->getIncrementId(),
            'transactionAffiliation' => $this->storeManager->getStore()->getName(),
            'transactionTotal'    => $order->getGrandTotal(),
            'transactionTax'      => $order->getTaxAmount(),
            'transactionShipping' => $order->getShippingAmount(),
            'transactionProducts' => $products
        ];
    }

    protected function setEnhancedEcommerceDatalayer(\Magento\Sales\Api\Data\OrderInterface $order)
    {
        $products = [];
        foreach ($order->getAllVisibleItems() as $item) {
            $products[] = [
                'id'      => $item->getSku(),
                'name'     => $item->getName(),
                'price'    => $item->getPrice(),
                'quantity' => round($item->getQtyOrdered())
            ];
        }

        $this->enhancedEcommerce = [
            'ecommerce' => [
                'purchase' => [
                    'actionField' => [
                        'id'       => $order->getIncrementId(),
                        'affiliation' => $this->storeManager->getStore()->getName(),
                        'revenue'    => $order->getGrandTotal(),
                        'tax'      => $order->getTaxAmount(),
                        'shipping' => $order->getShippingAmount(),
                    ],
                    'products' => $products
                ]
            ]
        ];
    }
}
