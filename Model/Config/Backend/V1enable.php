<?php
/*
 * Copyright Extend (c) 2023. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Model\Config\Backend;

use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\Config\Value;

class V1enable extends Value
{
    private WriterInterface $writer;

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\Config\ScopeConfigInterface $config,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        WriterInterface $writer
    ){
        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection);
        $this->writer = $writer;
    }


    /**
     * This will disable Product Protection on the new module if it's enabled on the old module.
     *
     * @return V1enable
     */
    public function afterSave()
    {
        $value = (int)$this->getValue();
        if ($value === 1) {
            $this->writer->save(\Extend\Integration\Service\Extend::ENABLE_PRODUCT_PROTECTION, 0);
        }
        return parent::afterSave(); // TODO: Change the autogenerated stub
    }
}