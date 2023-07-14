<?php
/*
 * Copyright Extend (c) 2023. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Setup\Model\ProductProtection;

use Exception;
use Extend\Integration\Service\Extend;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Type;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ProductFactory;
use Magento\Eav\Api\Data\AttributeSetInterface;
use Extend\Integration\Setup\Model\AttributeSetInstaller;
use Magento\Store\Model\StoreManagerInterface;

// NOTE: make sure to update ProductInstaller's use statement with this class if it is the current version
class ProtectionPlanProduct20230714 implements ProtectionPlanProductInterface
{
    const VERSION = '2023-07-14';

    private ProductFactory $productFactory;
    private ProductRepositoryInterface $productRepository;
    private AttributeSetInstaller $attributeSetInstaller;
    private StoreManagerInterface $storeManager;

    public function __construct(
        ProductFactory $productFactory,
        ProductRepositoryInterface $productRepository,
        AttributeSetInstaller $attributeSetInstaller,
        StoreManagerInterface $storeManager
    ) {
        $this->productFactory = $productFactory;
        $this->productRepository = $productRepository;
        $this->attributeSetInstaller = $attributeSetInstaller;
        $this->storeManager = $storeManager;
    }

    /**
     * Create the protection plan product
     *
     * @param AttributeSetInterface $attributeSet
     * @return Magento\Catalog\Model\Product;
     */
    public function createProduct($attributeSet)
    {
        $product = $this->productFactory->create();

        $product
            ->setSku(Extend::WARRANTY_PRODUCT_SKU)
            ->setName(Extend::WARRANTY_PRODUCT_NAME)
            ->setWebsiteIds(array_keys($this->storeManager->getWebsites()))
            ->setAttributeSetId($attributeSet->getAttributeSetId())
            ->setStatus(Status::STATUS_ENABLED)
            ->setVisibility(Visibility::VISIBILITY_NOT_VISIBLE)
            ->setTypeId(Type::TYPE_VIRTUAL)
            ->setPrice(0.0)
            ->setTaxClassId(0) //None
            ->setCreatedAt(strtotime('now'))
            ->setStockData([
                'use_config_manage_stock' => 0,
                'is_in_stock' => 1,
                'qty' => 1,
                'manage_stock' => 0,
                'use_config_notify_stock_qty' => 0,
            ]);

        $this->productRepository->save($product);

        return $product;
    }
}
