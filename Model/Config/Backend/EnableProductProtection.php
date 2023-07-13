<?php
/*
 * Copyright Extend (c) 2023. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Model\Config\Backend;

use Magento\Framework\App\Config\Value;
use Extend\Integration\Setup\Model\ProductInstaller;
use Magento\Framework\App\Config\Storage\WriterInterface;

class EnableProductProtection extends Value
{
    private ProductInstaller $productInstaller;
    private WriterInterface $writer;

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\Config\ScopeConfigInterface $config,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
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
        $this->productInstaller = $productInstaller;
        $this->writer = $writer;
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
            $this->productInstaller->createProduct();
        } else {
            $this->productInstaller->deleteProduct();
            $this->writer->save(
                \Extend\Integration\Service\Extend::ENABLE_PRODUCT_PROTECTION_CART_OFFER,
                0
            );
        }
        return parent::afterSave();
    }
}
