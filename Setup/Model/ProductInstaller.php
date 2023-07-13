<?php
/*
 * Copyright Extend (c) 2023. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Setup\Model;

use Exception;
use Extend\Integration\Service\Extend;
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\ResourceModel\Product as ProductResource;
use Extend\Integration\Setup\Model\ProductProtection\ProductProtectionV1;
use Magento\Framework\Exception\FileSystemException;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Registry;
use Magento\Framework\Filesystem\Io\File;
use Magento\Framework\App\Filesystem\DirectoryList;

class ProductInstaller
{
    // NOTE: update use statement, const value and type hint when adding a new version
    const CURRENT_VERSION = 'V1';

    private ProductFactory $productFactory;
    private ProductResource $productResource;
    private ProductRepositoryInterface $productRepository;
    private Registry $registry;
    private File $file;
    private DirectoryList $directoryList;
    private ProductProtectionV1 $productProtection;

    public function __construct(
        ProductFactory $productFactory,
        ProductResource $productResource,
        ProductRepositoryInterface $productRepository,
        Registry $registry,
        File $file,
        DirectoryList $directoryList,
        ProductProtectionV1 $productProtection
    ) {
        $this->productFactory = $productFactory;
        $this->productResource = $productResource;
        $this->productRepository = $productRepository;
        $this->registry = $registry;
        $this->file = $file;
        $this->directoryList = $directoryList;
        $this->productProtection = $productProtection;
    }

    public function createProduct($attributeSet)
    {
        $this->productProtection->createProduct($attributeSet);
    }

    public function deleteProduct()
    {
        try {
            $existingProduct = $this->productFactory->create();
            $productId = $this->productResource->getIdBySku(Extend::WARRANTY_PRODUCT_SKU);
            $this->productResource->load($existingProduct, $productId);
            if ($existingProduct->getId()) {
                $productToBeDeleted = $this->productRepository->get(Extend::WARRANTY_PRODUCT_SKU);
                $this->registry->register('isSecureArea', true);
                $this->productRepository->delete($productToBeDeleted);
                $this->deleteImageFromPubMedia();
            }
        } catch (Exception $exception) {
            throw new Exception(
                'There was an error deleting the Extend Protection Plan Product' . $exception
            );
        }
    }

    /**
     * Delete image from pub/media
     *
     * @return void
     * @throws FileSystemException
     */
    private function deleteImageFromPubMedia()
    {
        $imageWarranty = $this->getMediaImagePath();
        $this->file->rm($imageWarranty);
    }

    /**
     * Get media image path
     *
     * @return string
     *
     * @throws FileSystemException
     */
    private function getMediaImagePath(): string
    {
        $path = $this->directoryList->getPath('media');
        $path .= '/Extend_icon.png';

        return $path;
    }
}
