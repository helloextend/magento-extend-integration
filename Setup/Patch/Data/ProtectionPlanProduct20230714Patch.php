<?php
/*
 * Copyright Extend (c) 2023. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Setup\Patch\Data;

use Extend\Integration\Service\Extend;
use Extend\Integration\Setup\Model\ProductInstaller;
use Extend\Integration\Setup\Model\ProductProtection\ProtectionPlanProduct20230714;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Setup\Exception as SetupException;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Extend\Integration\Setup\Model\AttributeSetInstaller;

class ProtectionPlanProduct20230714Patch implements DataPatchInterface, PatchRevertableInterface
{
    private State $state;
    private ProtectionPlanProduct20230714 $protectionPlanProduct;
    private ProductInstaller $productInstaller;
    private ScopeConfigInterface $scopeConfig;
    private AttributeSetInstaller $attributeSetInstaller;

    public function __construct(
        State $state,
        ProtectionPlanProduct20230714 $protectionPlanProduct,
        ProductInstaller $productInstaller,
        ScopeConfigInterface $scopeConfig,
        AttributeSetInstaller $attributeSetInstaller
    ) {
        $this->state = $state;
        $this->protectionPlanProduct = $protectionPlanProduct;
        $this->productInstaller = $productInstaller;
        $this->scopeConfig = $scopeConfig;
        $this->attributeSetInstaller = $attributeSetInstaller;
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
            ProductInstaller::CURRENT_VERSION !== ProtectionPlanProduct20230714::VERSION ||
            $isPPV2Enabled !== 1
        ) {
            return;
        }

        $this->state->emulateAreaCode(Area::AREA_ADMINHTML, function () {
            $this->productInstaller->deleteProduct();
            $attributeSet = $this->attributeSetInstaller->createAttributeSet();
            $this->productInstaller->createProduct($attributeSet, $this->protectionPlanProduct);
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
