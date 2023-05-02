<?php
/*
 * Copyright Extend (c) 2022. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Setup\Model;

use Exception;
use Extend\Integration\Service\Extend;
use Magento\Catalog\Setup\CategorySetupFactory;
use Magento\Eav\Api\AttributeSetRepositoryInterface;
use Magento\Eav\Api\Data\AttributeSetInterface;
use Magento\Eav\Model\Entity\Attribute\SetFactory as AttributeSetFactory;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Set as AttributeSetResource;
use Magento\Framework\Phrase;
use Magento\Framework\Setup\Exception as SetupException;

class AttributeSetInstaller
{
    private AttributeSetFactory $attributeSetFactory;
    private AttributeSetResource $attributeSetResource;
    private AttributeSetRepositoryInterface $attributeSetRepository;
    private CategorySetupFactory $categorySetupFactory;

    public function __construct(
        AttributeSetFactory $attributeSetFactory,
        AttributeSetRepositoryInterface $attributeSetRepository,
        AttributeSetResource $attributeSetResource,
        CategorySetupFactory $categorySetupFactory
    ) {
        $this->attributeSetFactory = $attributeSetFactory;
        $this->attributeSetRepository = $attributeSetRepository;
        $this->attributeSetResource = $attributeSetResource;
        $this->categorySetupFactory = $categorySetupFactory;
    }

    /**
     * Create the attribute set for the protection plan product
     *
     * @return AttributeSetInterface
     * @throws SetupException
     */
    public function createAttributeSet(): AttributeSetInterface
    {
        try {
            // If the Extend products attribute set already exists, don't recreate it.
            $existingExtendAttributeSet = $this->attributeSetFactory->create();
            if (
                $this->attributeSetResource->load(
                    $existingExtendAttributeSet,
                    Extend::WARRANTY_PRODUCT_ATTRIBUTE_SET_NAME,
                    'attribute_set_name'
                ) &&
                $existingExtendAttributeSet->getAttributeSetId()
            ) {
                return $existingExtendAttributeSet;
            }

            $extendProductAttributeSet = $this->attributeSetFactory->create();
            $category = $this->categorySetupFactory->create();

            $entityTypeId = $category->getEntityTypeId(\Magento\Catalog\Model\Product::ENTITY);
            $defaultAttributeSetId = $category->getDefaultAttributeSetId($entityTypeId);

            $data = [
                'attribute_set_name' => Extend::WARRANTY_PRODUCT_ATTRIBUTE_SET_NAME,
                'entity_type_id' => $entityTypeId,
                'sort_order' => 200,
            ];

            $extendProductAttributeSet->setData($data)->validate();
            $extendProductAttributeSet->save();
            $extendProductAttributeSet->initFromSkeleton($defaultAttributeSetId);

            return $this->attributeSetRepository->save($extendProductAttributeSet);
        } catch (Exception $exception) {
            throw new SetupException(
                new Phrase('Error when creating the Extend Attribute Set: %1', [
                    $exception->getMessage(),
                ])
            );
        }
    }

    public function deleteAttributeSet()
    {
        try {
            $existingExtendAttributeSet = $this->attributeSetFactory->create();
            $this->attributeSetResource->load(
                $existingExtendAttributeSet,
                Extend::WARRANTY_PRODUCT_ATTRIBUTE_SET_NAME,
                'attribute_set_name'
            );
            if ($existingExtendAttributeSet->getAttributeSetId()) {
                $this->attributeSetRepository->delete($existingExtendAttributeSet);
            }
        } catch (Exception $exception) {
            throw new SetupException(
                new Phrase('here was an error deleting the Extend Attribute Set: %1', [
                    $exception->getMessage(),
                ])
            );
        }
    }
}
