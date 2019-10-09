<?php

namespace Webbhuset\CollectorBankCheckout\Block;

/**
 * Class Checkout
 *
 * @package Webbhuset\CollectorBankCheckout\Block
 */
class Checkout extends \Magento\Framework\View\Element\Template
{
    /**
     * @var string $frame html block with iframe
     */
    protected $iframe;
    /**
     * @var \Magento\Framework\Serialize\Serializer\JsonHexTag|mixed
     */
    protected $serializer;
    /**
     * @var \Magento\Checkout\Model\CompositeConfigProvider
     */
    protected $configProvider;

    /**
     * Checkout constructor.
     *
     * @param \Magento\Framework\View\Element\Template\Context      $context
     * @param \Magento\Checkout\Model\CompositeConfigProvider       $configProvider
     * @param array                                                 $data
     * @param \Magento\Framework\Serialize\Serializer\Json|null     $serializer
     * @param \Magento\Framework\Serialize\SerializerInterface|null $serializerInterface
     */
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

    /**
     * Returns the html block with the iframe
     *
     * @return mixed
     */
    public function getIframe()
    {
        return $this->iframe;
    }

    /**
     * Sets iframe class variable
     *
     * @param $iframe
     * @return $this
     */
    public function setIframe($iframe)
    {
        $this->iframe = $iframe;

        return $this;
    }

    /**
     * Returns the url used for updating quote data while interacting with the iframe
     *
     * @return string
     */
    public function getUpdateUrl()
    {
        return $this->getUrl('collectorcheckout/update');
    }

    /**
     * Returns the javascript config
     *
     * @return array
     */
    public function getCheckoutConfig()
    {
        return $this->configProvider->getConfig();
    }

    /**
     * Returns the javascript config serialized
     *
     * @return string
     */
    public function getSerializedCheckoutConfig()
    {
        return  $this->serializer->serialize($this->getCheckoutConfig());
    }
}
