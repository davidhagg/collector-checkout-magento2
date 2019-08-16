<?php

namespace Webbhuset\CollectorBankCheckout\Block;

class Checkout extends \Magento\Framework\View\Element\Template
{
    protected $iframe;
    protected $serializer;
    protected $configProvider;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Checkout\Model\CompositeConfigProvider $configProvider,
        array $data = [],
        \Magento\Framework\Serialize\Serializer\Json $serializer = null,
        \Magento\Framework\Serialize\SerializerInterface $serializerInterface = null
    ) {
        parent::__construct($context, $data);
        $this->jsLayout = isset($data['jsLayout']) && is_array($data['jsLayout']) ? $data['jsLayout'] : [];
        $this->configProvider = $configProvider;
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
}
