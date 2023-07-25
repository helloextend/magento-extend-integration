<?php
/*
 * Copyright Extend (c) 2023. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Model\Config\Backend;

use Magento\Framework\App\Config\Value;
use Extend\Integration\Setup\Model\AttributeSetInstaller;
use Extend\Integration\Setup\Model\ProductInstaller;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;

class EnableProductProtection extends Value
{
    private AttributeSetInstaller $attributeSetInstaller;
    private ProductInstaller $productInstaller;
    private WriterInterface $writer;
    private ScopeConfigInterface $config;

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        ScopeConfigInterface $config,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
        AttributeSetInstaller $attributeSetInstaller,
        ProductInstaller $productInstaller,
        WriterInterface $writer,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null
    ) {
        parent::__construct(
            $context,
            $registry,
            $config,
            $cacheTypeList,
            $resource,
            $resourceCollection
        );
        $this->attributeSetInstaller = $attributeSetInstaller;
        $this->productInstaller = $productInstaller;
        $this->writer = $writer;
        $this->config = $config;
    }

    /**
     * This will create a new Product Protection product when V2 PP is enabled
     * If disabled, this will also disable cart offers
     *
     * @return EnableProductProtection
     */
    public function afterSave()
    {
        $isPPV2Enabled = (int) $this->getValue();
        if ($isPPV2Enabled === 1) {
            $attributeSet = $this->attributeSetInstaller->createAttributeSet();
            $this->productInstaller->createProduct($attributeSet);
        }
        return parent::afterSave();
    }
}
