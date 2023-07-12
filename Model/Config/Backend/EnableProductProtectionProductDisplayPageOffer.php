<?php
/*
 * Copyright Extend (c) 2023. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Model\Config\Backend;

use Magento\Framework\App\Config\Value;
use Magento\Framework\App\Config\Storage\WriterInterface;

class EnableProductProtectionProductDisplayPageOffer extends Value
{
    protected $cacheTypeList;

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\Config\ScopeConfigInterface $config,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
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
        $this->cacheTypeList = $cacheTypeList;
    }

    public function afterSave()
    {
        // need to clean these cache types in order to immediately propagate the new setting to the storefront
        // and eliminate the need for the admin to flush the cache manually in order to see the change in the UI.
        $this->cacheTypeList->cleanType('config');
        $this->cacheTypeList->cleanType('full_page');
        return parent::afterSave();
    }
}
