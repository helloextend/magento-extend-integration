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

class ProductProtectionV1
{
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
     * @return \Magento\Catalog\Model\Product
     */
    public function createProduct()
    {
        try {
            $product = $this->productFactory->create();
            $attributeSet = $this->attributeSetInstaller->createAttributeSet();

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
        } catch (Exception $exception) {
            throw new Exception(
                'There was an error creating the Extend Protection Plan Product' . $exception
            );
        }
    }
}
