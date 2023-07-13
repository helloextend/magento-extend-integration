<?php
/*
 * Copyright Extend (c) 2023. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Setup\Patch\Data;

use Extend\Integration\Service\Extend;
use Extend\Integration\Setup\Model\ProductInstaller;
use Extend\Integration\Setup\Model\AttributeSetInstaller;
use Extend\Integration\Setup\Model\ProductProtection\ProductProtectionV1;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Setup\Exception as SetupException;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;

class ProductProtectionV1Patch implements DataPatchInterface, PatchRevertableInterface
{
    private State $state;
    private AttributeSetInstaller $attributeSetInstaller;
    private ProductProtectionV1 $productProtectionV1;
    private ProductInstaller $productInstaller;
    private ScopeConfigInterface $scopeConfig;

    public function __construct(
        State $state,
        AttributeSetInstaller $attributeSetInstaller,
        ProductProtectionV1 $productProtectionV1,
        ProductInstaller $productInstaller,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->state = $state;
        $this->attributeSetInstaller = $attributeSetInstaller;
        $this->productProtectionV1 = $productProtectionV1;
        $this->productInstaller = $productInstaller;
        $this->scopeConfig = $scopeConfig;
    }
    /**
     * @inheritDoc
     */
    public static function getDependencies()
    {
        return [
                // NOTE: This should include the previous Product Protection patch's class
                // to maintain the order of patches being applied, for example:
                // \Extend\Integration\Setup\Patch\Data\ProductProtectionV0Patch::class
            ];
    }

    /**
     * @inheritDoc
     */
    public function getAliases()
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function apply()
    {
        $isPPV2Enabled = (int) $this->scopeConfig->getValue(Extend::ENABLE_PRODUCT_PROTECTION);
        if (
            ProductInstaller::CURRENT_VERSION !== ProductProtectionV1::VERSION ||
            isPPV2Enabled !== 1
        ) {
            return;
        }

        $this->state->emulateAreaCode(Area::AREA_ADMINHTML, function () {
            $this->productInstaller->deleteProduct();
            $this->productInstaller->createProduct($this->productProtectionV1);
        });
    }

    /**
     * @inheritDoc
     */
    public function revert()
    {
        $this->productInstaller->deleteProduct();
    }
}
