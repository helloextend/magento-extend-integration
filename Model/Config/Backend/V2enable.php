<?php
/*
 * Copyright Extend (c) 2023. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Model\Config\Backend;

use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\Config\Value;
use Extend\Integration\Setup\Model\AttributeSetInstaller;
use Extend\Integration\Setup\Model\ProductInstaller;

class V2enable extends Value
{
    /**
     * @var WriterInterface
     */
    private WriterInterface $writer;
    private AttributeSetInstaller $attributeSetInstaller;
    private ProductInstaller $productInstaller;

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\Config\ScopeConfigInterface $config,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        WriterInterface $writer,
        AttributeSetInstaller $attributeSetInstaller,
        ProductInstaller $productInstaller,
    ) {
        parent::__construct(
            $context,
            $registry,
            $config,
            $cacheTypeList,
            $resource,
            $resourceCollection
        );
        $this->writer = $writer;
        $this->attributeSetInstaller = $attributeSetInstaller;
        $this->productInstaller = $productInstaller;
    }

    /**
     * This will disable Product Protection on the new module if it's enabled on the old module.
     *
     * @return V2enable
     */
    public function afterSave()
    {
        $value = (int) $this->getValue();
        if ($value === 1) {
          $attributeSet = $this->attributeSetInstaller->createAttributeSet();
          $this->productInstaller->createProduct($attributeSet);
        }
        return parent::afterSave(); // TODO: Change the autogenerated stub
    }
}
