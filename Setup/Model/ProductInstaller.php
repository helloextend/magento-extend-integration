<?php
/*
 * Copyright Extend (c) 2022. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Setup\Model;

use Exception;
use Extend\Integration\Service\Extend;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Api\ProductCustomOptionRepositoryInterface;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Gallery\EntryFactory;
use Magento\Catalog\Model\Product\Gallery\GalleryManagement;
use Magento\Catalog\Model\Product\Type;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\Product\Option;
use Magento\Catalog\Model\Product\OptionFactory;
use Magento\Catalog\Model\ResourceModel\Product as ProductResource;
use Magento\Eav\Api\Data\AttributeSetInterface;
use Magento\Framework\Api\ImageContentFactory;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\StateException;
use Magento\Framework\Filesystem\Io\File;
use Magento\Framework\Module\Dir\Reader;
use Magento\Framework\Phrase;
use Magento\Framework\Registry;
use Magento\Framework\Setup\Exception as SetupException;
use Magento\Store\Model\StoreManagerInterface;

class ProductInstaller
{
    private DirectoryList $directoryList;
    private EntryFactory $entryFactory;
    private File $file;
    private GalleryManagement $galleryManagement;
    private ImageContentFactory $imageContentFactory;
    private Product $product;
    private ProductFactory $productFactory;
    private ProductRepositoryInterface $productRepository;
    private Option $catalogOption;
    private OptionFactory $catalogOptionFactory;
    private ProductCustomOptionRepositoryInterface $optionRepository;
    private ProductResource $productResource;
    private Reader $reader;
    private StoreManagerInterface $storeManager;
    private Registry $registry;

    public function __construct(
        DirectoryList $directoryList,
        EntryFactory $entryFactory,
        File $file,
        GalleryManagement $galleryManagement,
        ImageContentFactory $imageContentFactory,
        Product $product,
        ProductFactory $productFactory,
        ProductCustomOptionRepositoryInterface $optionRepository,
        Option $catalogOption,
        OptionFactory $catalogOptionFactory,
        ProductRepositoryInterface $productRepository,
        ProductResource $productResource,
        Reader $reader,
        StoreManagerInterface $storeManager,
        Registry $registry
    ) {
        $this->directoryList = $directoryList;
        $this->entryFactory = $entryFactory;
        $this->file = $file;
        $this->galleryManagement = $galleryManagement;
        $this->imageContentFactory = $imageContentFactory;
        $this->product = $product;
        $this->productFactory = $productFactory;
        $this->catalogOption = $catalogOption;
        $this->catalogOptionFactory = $catalogOptionFactory;
        $this->optionRepository = $optionRepository;
        $this->productResource = $productResource;
        $this->storeManager = $storeManager;
        $this->productRepository = $productRepository;
        $this->reader = $reader;
        $this->registry = $registry;
    }

    public function createProduct($attributeSet)
    {
        try {
            if ($product = $this->createProtectionPlanProduct($attributeSet)) {
                $this->addImageToPubMedia();
                $this->processMediaGalleryEntry($this->getMediaImagePath(), $product->getSku());
                $this->addOptionsToProtectionPlanProduct($product);
            }
        } catch (Exception $exception) {
            throw new Exception(
                'There was an error creating the Extend Protection Plan Product' . $exception
            );
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
     * Create the protection plan product
     *
     * @param AttributeSetInterface $attributeSet
     * @return false|\Magento\Catalog\Model\Product
     * @throws SetupException
     */
    private function createProtectionPlanProduct(AttributeSetInterface $attributeSet)
    {
        try {
            // If the Extend protection product already exists, don't recreate it.
            $existingProduct = $this->productFactory->create();
            $productId = $this->productResource->getIdBySku(Extend::WARRANTY_PRODUCT_SKU);
            $this->productResource->load($existingProduct, $productId);
            if ($existingProduct->getId()) {
                return false;
            }

            $product = $this->productFactory->create();

            $product
                ->setSku(Extend::WARRANTY_PRODUCT_SKU)
                ->setName('Extend Protection Plan')
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
            throw new SetupException(
                new Phrase('There was a problem create the Extend Protection Product: ', [
                    $exception->getMessage(),
                ])
            );
        }
    }

    /**
     * Adds customizable options to the protection plan product
     *
     * @param Product $product
     * @return void
     * @throws SetupException
     */
    private function addOptionsToProtectionPlanProduct(Product $product)
    {
        try {
            $default_values = [
                'type' => 'field',
                'price_type' => 'fixed',
                'price' => '0.00',
                'sort_order' => 0,
                'is_require' => 1,
            ];

            $options = [
                [
                    'title' => 'Associated Product',
                ],
                [
                    'title' => 'Plan Type',
                ],
                [
                    'title' => 'Plan ID',
                ],
                [
                    'title' => 'Term',
                ],
                [
                    'title' => 'List Price',
                    'is_require' => 0,
                ],
                [
                    'title' => 'Order Offer Plan Id',
                    'is_require' => 0,
                ],
                [
                    'title' => 'Lead Token',
                    'is_require' => 0,
                ],
            ];

            foreach ($options as $arrayOption) {
                // If the input arrays have the same string keys, then the later value for that key will overwrite the previous one.
                $optionData = array_merge($default_values, $arrayOption);

                $option = $this->catalogOptionFactory->create();

                $option
                    ->setProductId($product->getId())
                    ->setStoreId($product->getStoreId())
                    ->setProductSku($product->getSku())
                    ->addData($optionData);

                $this->optionRepository->save($option);
            }
        } catch (Exception $exception) {
            throw new SetupException(
                new Phrase('There was a problem adding the Extend Protection Product options: ', [
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
     * Process media gallery entry
     *
     * @param string $filePath
     * @param string $sku
     *
     * @return void
     *
     * @throws NoSuchEntityException
     * @throws StateException
     * @throws InputException
     */
    private function processMediaGalleryEntry(string $filePath, string $sku)
    {
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
}
