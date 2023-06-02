<?php
/*
 * Copyright Extend (c) 2022. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */
namespace Extend\Integration\Model\Config\Source;

class Environment implements \Magento\Framework\Data\OptionSourceInterface
{
    private \Magento\Integration\Model\ResourceModel\Integration\CollectionFactory $collectionFactory;

    public function __construct(
        \Magento\Integration\Model\ResourceModel\Integration\CollectionFactory $collectionFactory
    ) {
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * {@inheritdoc}
     *
     * @codeCoverageIgnore
     */
    public function toOptionArray(): array
    {
        $integrations = $this->collectionFactory->create();

        $integrations
            ->addFieldToFilter('endpoint', ['like' => '%extend.com%'])
            ->addFieldToFilter('endpoint', ['like' => '%integ-mage%'])
            ->addFieldToSelect(['integration_id', 'name'])
            ->load();

        $rows = $integrations->getItems();

        $options = [];

        if ($rows) {
            foreach ($rows as $row) {
                $option = [];
                $option['value'] = $row->getData('integration_id');
                $option['label'] = $row->getData('name');
                $options[] = $option;
            }
        }

        return $options;
    }
}
