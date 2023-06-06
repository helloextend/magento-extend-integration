<?php
/*
 * Copyright Extend (c) 2022. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Plugin\Catalog\Model;

use Magento\Catalog\Api\Data\ProductExtensionFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable;
use Magento\Catalog\Api\Data\ProductSearchResultsInterface;
use Magento\Eav\Model\Config;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Psr\log\LoggerInterface;
use Exception;

class ProductRepositoryPlugin
{
    /**
     * @var ProductExtensionFactory
     */
    protected $productExtensionFactory;

    /**
     * @var Config
     */
    protected $eavConfig;

    /**
     * @var Configurable
     */
    protected $configurable;

    /**
     * @var CategoryRepositoryInterface
     */
    protected $categoryRepository;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        ProductExtensionFactory $productExtensionFactory,
        Config $eavConfig,
        Configurable $configurable,
        CategoryRepositoryInterface $categoryRepository,
        LoggerInterface $logger
    ) {
        $this->productExtensionFactory = $productExtensionFactory;
        $this->eavConfig = $eavConfig;
        $this->configurable = $configurable;
        $this->categoryRepository = $categoryRepository;
        $this->logger = $logger;
    }

    /**
     * This plugin injects Extend specific extension attributes when retrieving a single product
     *
     * @param ProductRepositoryInterface $subject
     * @param ProductInterface $result
     * @return ProductInterface
     */
    public function afterGet(ProductRepositoryInterface $subject, ProductInterface $result)
    {
        try {
            $this->addProductExtensions($subject, $result);
        } catch (Exception $e) {
            // Ignore any errors that get thrown so that merchant site can continue functioning
            $this->logger->error('Error encountered adding product extension attributes');
        }

        return $result;
    }

    /**
     * This plugin injects Extend specific extension attributes when retrieving a list of products
     *
     * @param ProductRepositoryInterface $subject
     * @param ProductSearchResultsInterface $result
     * @return ProductSearchResultsInterface
     */
    public function afterGetList(
        ProductRepositoryInterface $subject,
        ProductSearchResultsInterface $result
    ) {
        try {
            $items = $result->getItems();

            if (count($items)) {
                foreach ($items as $item) {
                    $this->addProductExtensions($subject, $item);
                }
            }
        } catch (Exception $e) {
            // Ignore any errors that get thrown so that merchant site can continue functioning
            $this->logger->error('Error encountered adding product extension attributes');
        }

        return $result;
    }

    /**
     * Adds Extend specific extension attributes to a product
     *
     * @param ProductRepositoryInterface $productRepository
     * @param ProductInterface $product
     * @return void
     */
    private function addProductExtensions(
        ProductRepositoryInterface $productRepository,
        ProductInterface $product
    ) {
        $productExtension = $product->getExtensionAttributes();
        if ($productExtension === null) {
            $productExtension = $this->productExtensionFactory->create();
        }

        $parentProductIds = $this->configurable->getParentIdsByChild($product->getId());

        $parentProductId = null;
        $parentProductSku = null;
        if (isset($parentProductIds[0])) {
            $parentProductId = $parentProductIds[0];

            $parentProduct = $productRepository->getById($parentProductId);

            if ($parentProduct) {
                $parentProductSku = $parentProduct->getSku();
            }
        }

        $productExtension->setExtendParentId($parentProductId);
        $productExtension->setExtendParentSku($parentProductSku);

        $manufacturerOptionId = $product->getManufacturer();
        if (
            $manufacturerOptionId &&
            ($optionText = $this->getAttributeOptionText('manufacturer', $manufacturerOptionId))
        ) {
            $productExtension->setExtendManufacturer($optionText);
        }

        $categoryIds = $product->getCategoryIds();
        if (count($categoryIds)) {
            $categoryNames = array_map(function ($categoryId) {
                return $this->categoryRepository->get($categoryId)->getName();
            }, $categoryIds);
            $productExtension->setExtendCategories($categoryNames);
        }

        $product->setExtensionAttributes($productExtension);
    }

    /**
     * Helper to get the text associated to an attribute option via the option's id
     *
     * @param string $code
     * @param string $optionId
     * @return mixed
     */
    private function getAttributeOptionText(string $code, string $optionId)
    {
        $attributeConfig = $this->eavConfig->getAttribute('catalog_product', $code);
        if ($attributeConfig) {
            $optionText = $attributeConfig->getSource()->getOptionText($optionId);
            if ($optionText) {
                return $optionText;
            }
        }

        return null;
    }
}
