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

class Enable extends Value
{
    private AttributeSetInstaller $attributeSetInstaller;
    private ProductInstaller $productInstaller;
    private WriterInterface $writer;

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\Config\ScopeConfigInterface $config,
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
    }

    /**
     * If disabled, shipping protection and V2 product protection is disabled as well.
     *
     * @return Enable
     */
    public function afterSave()
    {
        $isV2Enabled = (int) $this->getValue();
        if ($isV2Enabled === 0) {
            $dependentConfigPaths = [
                \Extend\Integration\Service\Extend::ENABLE_SHIPPING_PROTECTION,
                \Extend\Integration\Service\Extend::ENABLE_PRODUCT_PROTECTION,
                \Extend\Integration\Service\Extend::ENABLE_CART_BALANCING,
            ];

            foreach ($dependentConfigPaths as $path) {
                $this->writer->save($path, 0);
            }
        }
        return parent::afterSave();
    }
}
