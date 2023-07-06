<?php
/*
 * Copyright Extend (c) 2023. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Model;

use Extend\Integration\Service\Extend;
use Extend\Integration\Service\Api\Integration;
use Extend\Integration\Api\ProductProtectionInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Checkout\Model\Session;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote\ItemFactory;
use Magento\Quote\Model\Quote\Item\OptionFactory;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\SerializerInterface;
use Exception;

class ProductProtection extends \Magento\Framework\Model\AbstractModel implements
    ProductProtectionInterface
{
    const PLAN_TYPE_CODE = 'plan_type';
    const TERM_CODE = 'term';
    const LIST_PRICE_CODE = 'list_price';
    const OFFER_PLAN_ID_CODE = 'offer_plan_id';
    const LEAD_TOKEN_CODE = 'lead_token';
    const ASSOCIATED_PRODUCT_SKU_CODE = 'associated_product_sku';
    const ASSOCIATED_PRODUCT_NAME_CODE = 'associated_product_name';
    const PLAN_ID_CODE = 'plan_id';
    const LEAD_QUANTITY_CODE = 'lead_quantity';

    const CUSTOM_OPTION_CODES = [
        self::PLAN_TYPE_CODE,
        self::TERM_CODE,
        self::LIST_PRICE_CODE,
        self::OFFER_PLAN_ID_CODE,
        self::LEAD_TOKEN_CODE,
        self::ASSOCIATED_PRODUCT_SKU_CODE,
        self::ASSOCIATED_PRODUCT_NAME_CODE,
        self::PLAN_ID_CODE,
        self::LEAD_QUANTITY_CODE,
    ];

    /**
     * @var ItemFactory
     */
    private ItemFactory $itemFactory;
    /**
     * @var OptionFactory
     */
    private OptionFactory $itemOptionFactory;
    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;
    /**
     * @var CartRepositoryInterface
     */
    private CartRepositoryInterface $quoteRepository;
    /**
     * @var Session
     */
    private Session $checkoutSession;
    /**
     * @var Integration
     */
    private Integration $integration;
    /**
     * @var StoreManagerInterface
     */
    private StoreManagerInterface $storeManager;
    /**
     * @var ProductRepositoryInterface
     */
    private ProductRepositoryInterface $productRepository;

    /**
     * @var SerializerInterface
     */
    private SerializerInterface $serializer;

    /**
     * @return void
     */
    public function __construct(
        CartRepositoryInterface $quoteRepository,
        ProductRepositoryInterface $productRepository,
        ItemFactory $itemFactory,
        OptionFactory $itemOptionFactory,
        Session $checkoutSession,
        Integration $integration,
        StoreManagerInterface $storeManager,
        LoggerInterface $logger,
        SerializerInterface $serializer
    ) {
        $this->logger = $logger;
        $this->quoteRepository = $quoteRepository;
        $this->itemFactory = $itemFactory;
        $this->itemOptionFactory = $itemOptionFactory;
        $this->checkoutSession = $checkoutSession;
        $this->integration = $integration;
        $this->storeManager = $storeManager;
        $this->productRepository = $productRepository;
        $this->serializer = $serializer;
    }

    /**
     * Set plan_id
     *
     * @param string $planId
     * @return void
     */
    public function setPlanId(?string $planId)
    {
        $this->setData(self::PLAN_ID, $planId);
    }

    /**
     * Set plan_type
     *
     * @param string $planType
     * @return void
     */
    public function setPlanType(?string $planType)
    {
        $this->setData(self::PLAN_TYPE, $planType);
    }

    /**
     * Set associated_product
     *
     * @param string $associatedProduct
     * @return void
     */
    public function setAssociatedProduct(?string $associatedProduct)
    {
        $this->setData(self::ASSOCIATED_PRODUCT, $associatedProduct);
    }

    /**
     * Set term
     *
     * @param string $term
     * @return void
     */
    public function setTerm(?string $term)
    {
        $this->setData(self::TERM, $term);
    }

    /**
     * Set offer_plan_id
     *
     * @param string $offerPlanId
     * @return void
     */
    public function setOfferPlanId(?string $offerPlanId)
    {
        $this->setData(self::OFFER_PLAN_ID, $offerPlanId);
    }

    /**
     * Set lead_token
     *
     * @param string $leadToken
     * @return void
     */
    public function setLeadToken(?string $leadToken)
    {
        $this->setData(self::LEAD_TOKEN, $leadToken);
    }

    /**
     * Set lead_quantity
     *
     * @param int $leadQuantity
     * @return void
     */
    public function setLeadQuantity(?int $leadQuantity)
    {
        $this->setData(self::LEAD_QUANTITY, $leadQuantity);
    }

    /**
     * Set list_price
     *
     * @param string $listPrice
     * @return void
     */
    public function setListPrice(?string $listPrice)
    {
        $this->setData(self::LIST_PRICE, $listPrice);
    }

    /**
     * Get plan_id
     *
     * @return string
     */
    public function getPlanId(): ?string
    {
        return $this->getData(self::PLAN_ID);
    }

    /**
     * Get plan_type
     *
     * @return string
     */
    public function getPlanType(): ?string
    {
        return $this->getData(self::PLAN_TYPE);
    }

    /**
     * Get associated_product
     *
     * @return string
     */
    public function getAssociatedProduct(): ?string
    {
        return $this->getData(self::ASSOCIATED_PRODUCT);
    }

    /**
     * Get term
     *
     * @return string
     */
    public function getTerm(): ?string
    {
        return $this->getData(self::TERM);
    }

    /**
     * Get offer_plan_id
     *
     * @return string
     */
    public function getOfferPlanId(): ?string
    {
        return $this->getData(self::OFFER_PLAN_ID);
    }

    /**
     * Get lead_token
     *
     * @return string
     */
    public function getLeadToken(): ?string
    {
        return $this->getData(self::LEAD_TOKEN);
    }

    /**
     * Get lead_quantity
     *
     * @return int
     */
    public function getLeadQuantity(): ?int
    {
        return $this->getData(self::LEAD_QUANTITY);
    }

    /**
     * Get list_price
     *
     * @return string
     */
    public function getListPrice(): ?string
    {
        return $this->getData(self::LIST_PRICE);
    }

    /**
     * Upsert product protection in cart
     *
     * @param int|null $quantity
     * @param string|null $cartItemId
     * @param string|null $productId
     * @param string|null $planId
     * @param int|null $price
     * @param int|null $term
     * @param string|null $coverageType
     * @param string|null $leadToken
     * @param string|null $listPrice
     * @param string|null $orderOfferPlanId
     * @return void
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function upsert(
        int $quantity = null,
        string $cartItemId = null,
        string $productId = null,
        string $planId = null,
        int $price = null,
        int $term = null,
        string $coverageType = null,
        string $leadToken = null,
        string $listPrice = null,
        string $orderOfferPlanId = null
    ): void {
        try {
            if ($price === 0) {
                throw new LocalizedException(
                    new Phrase('Cannot add/update product protection with a price of 0')
                );
            }

            if (isset($cartItemId)) {
                $item = $this->checkoutSession->getQuote()->getItemById($cartItemId);
                if ($item->getProduct()->getSku() !== Extend::WARRANTY_PRODUCT_SKU) {
                    throw new LocalizedException(
                        new Phrase('Cannot update non product-protection item')
                    );
                }
            }

            if ($quantity === 0 && !isset($cartItemId)) {
                throw new LocalizedException(
                    new Phrase('Cannot remove product protection without cart item id')
                );
            }

            // get the quote
            $quote = $this->checkoutSession->getQuote();

            // if quantity is 0, remove the item from the quote
            if ($quantity === 0 && isset($cartItemId)) {
                $quote->removeItem($cartItemId);
                $this->quoteRepository->save($quote->collectTotals());
                return;
            }

            // if we are adding pp, or we didn't find an existing item, create a new one
            if (!isset($item) || $item === false) {
                // ensure that we have the required properties to create the protection plan
                if (
                    !isset($quantity) ||
                    !isset($productId) ||
                    !isset($planId) ||
                    !isset($price) ||
                    !isset($term) ||
                    !isset($coverageType)
                ) {
                    throw new LocalizedException(
                        new Phrase('Missing required parameters to add product protection to cart.')
                    );
                }
                $item = $this->itemFactory->create();
            }

            $product = $this->productRepository->get(Extend::WARRANTY_PRODUCT_SKU);
            $item->setProduct($product);

            if (isset($quantity)) {
                $item->setQty($quantity);
            }

            if (isset($price)) {
                $item
                    ->setCustomPrice($price / 100)
                    ->setOriginalCustomPrice($price / 100)
                    ->getProduct()
                    ->setIsSuperMode(true);
            }

            $optionValues = [
                self::PLAN_TYPE_CODE => $coverageType,
                self::TERM_CODE => $term,
                self::LIST_PRICE_CODE => $listPrice,
                self::OFFER_PLAN_ID_CODE => $orderOfferPlanId,
                self::LEAD_TOKEN => $leadToken,
                self::ASSOCIATED_PRODUCT_SKU_CODE => $productId,
                self::ASSOCIATED_PRODUCT_NAME_CODE => 'JOMAR',
                self::PLAN_ID_CODE => $planId,
            ];

            if (isset($leadToken) && !isset($cartItemId)) {
                $optionValues[self::LEAD_QUANTITY_CODE] = $quantity;
            }

            $options = $this->createOptions($product, $item, $optionValues);
            $item->setOptions($options);

            // add the item to the quote and persist the quote so that the item <-> quote relationship is created
            $quote->addItem($item);
            $this->quoteRepository->save($quote);

            // fetch the quote once more so that the new item is loaded
            $quote = $this->checkoutSession->getQuote();

            //save the quote once more with the totals collected
            $this->quoteRepository->save($quote->collectTotals());
        } catch (Exception | LocalizedException $exception) {
            $this->logger->error(
                'Extend Product Protection Upsert Encountered the Following Exception ' .
                    $exception->getMessage()
            );
            $this->integration->logErrorToLoggingService(
                $exception->getMessage(),
                $this->storeManager->getStore()->getId(),
                'error'
            );
            // this is handled by magento error handler
            throw $exception;
        }
    }

    /**
     * Creates the product options for the product protection item
     *
     * @param $product
     * @param $item
     * @param $updatedOptions
     * @return array
     */
    private function createOptions($product, $item, $updatedOptions): array
    {
        $options = [];
        foreach (self::CUSTOM_OPTION_CODES as $optionCode) {
            $existingOption = $item->getOptionByCode($optionCode);
            // if the option is in the updated options/values, create a new option
            // if there is no update to the option, use the existing option
            if (isset($updatedOptions[$optionCode])) {
                $option = $this->itemOptionFactory->create();
                $option->setProduct($product);
                $option->setCode($optionCode);
                $option->setValue($updatedOptions[$optionCode]);
                $options[] = $option;
            } elseif (isset($existingOption)) {
                $options[] = $existingOption;
            }
        }

        $item->addOption([
            'product_id' => $item->getProductId(),
            'code' => 'additional_options',
            'value' => $this->serializer->serialize([
                [
                    'label' => 'Product Name',
                    'value' => 'JOMAR1',
                ],
                [
                    'label' => 'SKU',
                    'value' => 'JOMAR2',
                ],
                [
                    'label' => 'Term',
                    'value' => 'JOMAR3',
                ],
            ]),
        ]);

        return $options;
    }
}
