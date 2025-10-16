<?php
/*
 * Copyright Extend (c) 2025. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Plugin\ConfigurableProduct\Block\Product\View\Type\Configurable;

use Magento\ConfigurableProduct\Block\Product\View\Type\Configurable as ConfigurableBlock;
use Magento\Framework\Serialize\Serializer\Json;
use Psr\Log\LoggerInterface;

/**
 * Plugin to add SKU information to configurable product JSON config
 *
 * This plugin ensures that the 'sku' node is present in spConfig regardless of
 * whether Magento Inventory Management (MSI) modules are enabled. The Extend
 * integration relies on this data to display warranty offers for configurable products.
 *
 * Background:
 * Magento's InventoryConfigurableProductFrontendUi module provides a similar plugin
 * (AddAdditionalInfo) that adds SKU data, but only when MSI is enabled. This plugin
 * provides a fallback to ensure Extend functionality works even when MSI is disabled.
 */
class AddSkuInfo
{
    /**
     * @var Json
     */
    private $jsonSerializer;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param Json $jsonSerializer
     * @param LoggerInterface $logger
     */
    public function __construct(
        Json $jsonSerializer,
        LoggerInterface $logger
    ) {
        $this->jsonSerializer = $jsonSerializer;
        $this->logger = $logger;
    }

    /**
     * Add SKU information to configurable product JSON config
     *
     * This plugin runs after getJsonConfig() and adds a 'sku' array mapping
     * product IDs to SKUs if it's not already present (from MSI).
     *
     * @param ConfigurableBlock $subject
     * @param string $result
     * @return string
     */
    public function afterGetJsonConfig(ConfigurableBlock $subject, string $result): string
    {
        try {
            $jsonConfig = $this->jsonSerializer->unserialize($result);

            // Only add SKU data if it's not already present (avoid duplicate work if MSI is enabled)
            if (!isset($jsonConfig['sku']) || empty($jsonConfig['sku'])) {
                $jsonConfig['sku'] = $this->getProductVariationsSku($subject);
            }

            return $this->jsonSerializer->serialize($jsonConfig);
        } catch (\Exception $e) {
            // Log error but return original result to avoid breaking the page
            $this->logger->error(
                'Extend Integration: Error adding SKU info to configurable product config',
                ['exception' => $e->getMessage()]
            );
            return $result;
        }
    }

    /**
     * Get SKU for each product variation
     *
     * @param ConfigurableBlock $subject
     * @return array Array mapping product IDs to SKUs
     */
    private function getProductVariationsSku(ConfigurableBlock $subject): array
    {
        $skus = [];
        foreach ($subject->getAllowProducts() as $product) {
            $skus[$product->getId()] = $product->getSku();
        }

        return $skus;
    }
}
