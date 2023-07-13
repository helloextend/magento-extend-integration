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
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\StateException;
use Magento\Framework\Phrase;
use Magento\Inventory\Model\SourceItemFactory;
use Magento\Catalog\Model\Product\Gallery\EntryFactory;
use Magento\InventoryApi\Api\SourceItemsSaveInterface;
use Magento\Framework\Setup\Exception as SetupException;
use Magento\Framework\Api\ImageContentFactory;
use Magento\Framework\Module\Dir\Reader;
use Magento\Catalog\Model\Product\Gallery\GalleryManagement;

class ProductInstaller
{
    // NOTE: when adding a new version update the use statement, const value and dependency injection
    const CURRENT_VERSION = 'V1';

    private ProductFactory $productFactory;
    private ProductResource $productResource;
    private ProductRepositoryInterface $productRepository;
    private Registry $registry;
    private File $file;
    private DirectoryList $directoryList;
    private ProductProtectionV1 $productProtection;
    private SourceItemFactory $sourceItemFactory;
    private EntryFactory $entryFactory;
    private SourceItemsSaveInterface $sourceItemsSave;
    private ImageContentFactory $imageContentFactory;
    private Reader $reader;
    private GalleryManagement $galleryManagement;

    public function __construct(
        ProductFactory $productFactory,
        ProductResource $productResource,
        ProductRepositoryInterface $productRepository,
        Registry $registry,
        File $file,
        DirectoryList $directoryList,
        ProductProtectionV1 $productProtection,
        SourceItemFactory $sourceItemFactory,
        EntryFactory $entryFactory,
        SourceItemsSaveInterface $sourceItemsSave,
        ImageContentFactory $imageContentFactory,
        Reader $reader,
        GalleryManagement $galleryManagement
    ) {
        $this->productFactory = $productFactory;
        $this->productResource = $productResource;
        $this->productRepository = $productRepository;
        $this->registry = $registry;
        $this->file = $file;
        $this->directoryList = $directoryList;
        $this->productProtection = $productProtection;
        $this->sourceItemFactory = $sourceItemFactory;
        $this->entryFactory = $entryFactory;
        $this->sourceItemsSave = $sourceItemsSave;
        $this->imageContentFactory = $imageContentFactory;
        $this->reader = $reader;
        $this->galleryManagement = $galleryManagement;
    }

    public function createProduct($productProtection = null)
    {
        if ($productProtection === null) {
            // Use the current version
            $product = $this->productProtection->createProduct();
        } else {
            // Use the specified version
            $product = $productProtection->createProduct();
        }

        if ($product) {
            $this->addImageToPubMedia();
            $this->processMediaGalleryEntry($product->getSku());
            $this->createSourceItem();
        }
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
     * Create inventory source item for PP
     *
     * @return void
     * @throws SetupException
     */
    private function createSourceItem()
    {
        try {
            $sourceItem = $this->sourceItemFactory->create();
            $sourceItem->setSourceCode('default');
            $sourceItem->setSku(Extend::WARRANTY_PRODUCT_SKU);
            $sourceItem->setQuantity(1);
            $sourceItem->setStatus(1);
            $this->sourceItemsSave->execute([$sourceItem]);
        } catch (Exception $exception) {
            throw new SetupException(
                new Phrase('There was a problem creating the source item: ', [
                    $exception->getMessage(),
                ])
            );
        }
    }
    /**
     * Get image to pub media
     *
     * @return void
     *
     * @throws FileSystemException
     */
    private function addImageToPubMedia()
    {
        $imagePath = $this->reader->getModuleDir('', 'Extend_Integration');
        $imagePath .= '/Setup/Resource/Extend_icon.png';

        $media = $this->getMediaImagePath();

        $this->file->cp($imagePath, $media);
    }

    /**
     * Process media gallery entry
     *
     * @param string $sku
     *
     * @return void
     *
     * @throws NoSuchEntityException
     * @throws StateException
     * @throws InputException
     */
    private function processMediaGalleryEntry(string $sku)
    {
        $filePath = $this->getMediaImagePath();

        $entry = $this->entryFactory->create();
        $entry->setFile($filePath);
        $entry->setMediaType('image');
        $entry->setDisabled(false);
        $entry->setTypes(['thumbnail', 'image', 'small_image']);

        $imageContent = $this->imageContentFactory->create();
        $imageContent
            ->setType(mime_content_type($filePath))
            ->setName('Extend Protection Plan')
            ->setBase64EncodedData(base64_encode($this->file->read($filePath)));

        $entry->setContent($imageContent);

        $this->galleryManagement->create($sku, $entry);
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
}
