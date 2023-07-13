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
        // NOTE: This should include any previous Product Protection patches
        return [];
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
     * @throws SetupException
     */
    public function apply()
    {
        if (ProductInstaller::CURRENT_VERSION !== 'V1') {
            return;
        }

        $isPPV2Enabled = (int) $this->scopeConfig->getValue(Extend::ENABLE_PRODUCT_PROTECTION);
        if ($isPPV2Enabled === 1) {
            $this->state->emulateAreaCode(Area::AREA_ADMINHTML, function () {
                $this->productInstaller->deleteProduct();
                $this->productInstaller->createProduct($this->productProtectionV1);
            });
        }
    }

    /**
     * @inheritDoc
     * @throws FileSystemException|SetupException
     */
    public function revert()
    {
        $this->productInstaller->deleteProduct();
    }
}
