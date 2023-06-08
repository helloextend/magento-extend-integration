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
use Exception;

class ProductProtection extends \Magento\Framework\Model\AbstractModel implements
    ProductProtectionInterface
{
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
        LoggerInterface $logger
    ) {
        $this->logger = $logger;
        $this->quoteRepository = $quoteRepository;
        $this->itemFactory = $itemFactory;
        $this->itemOptionFactory = $itemOptionFactory;
        $this->checkoutSession = $checkoutSession;
        $this->integration = $integration;
        $this->storeManager = $storeManager;
        $this->productRepository = $productRepository;
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
            $product = $this->productRepository->get(Extend::WARRANTY_PRODUCT_SKU);
            $quote = $this->checkoutSession->getQuote();
            $this->logger->debug('1: initial quote state', ['quote' => $quote->getData()]);
            $quoteId = $quote->getId();

            if ($price === 0) {
                throw new LocalizedException(
                    new Phrase('Cannot add/update product protection with a price of 0')
                );
            }

            $quote->setData('_xtd_is_extend_quote_save', true);

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

            // if quote has not been saved yet, save it so that we have an id
            if (!isset($quoteId)) {
                $this->quoteRepository->save($quote);
                $quote = $this->checkoutSession->getQuote();
                $this->logger->debug('2: quote state after save', ['quote' => $quote->getData()]);
                $quoteId = $quote->getId();
            }

            $item->setQuoteId($quoteId);
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
                'Plan Type' => $coverageType,
                'Term' => $term,
                'List Price' => $listPrice,
                'Order Offer Plan Id' => $orderOfferPlanId,
                'Lead Token' => $leadToken,
                'Associated Product' => $productId,
                'Plan ID' => $planId,
            ];

            if (isset($leadToken) && !isset($cartItemId)) {
                $optionValues['Lead Quantity'] = $quantity;
            }

            $options = $this->createOptions($product, $item, $optionValues);
            $item->setOptions($options);
            $quote->addItem($item);
            $this->quoteRepository->save($quote->collectTotals());
            $this->logger->debug('3: final quote state', [
                'quote from session' => $this->checkoutSession->getQuote()->getData(),
            ]);
            $this->logger->debug('4: quote items', [
                'items' => $this->checkoutSession
                    ->getQuote()
                    ->getItemsCollection()
                    ->getData(),
            ]);
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
        $optionIds = [];
        foreach ($product->getOptions() as $o) {
            $optionId = $o->getId();
            $existingOption = $item->getOptionByCode("option_$optionId");
            // if the option is in the updated options/values, create a new option
            // if there is no update to the option, use the existing option
            if (isset($updatedOptions[$o->getTitle()])) {
                $option = $this->itemOptionFactory->create();
                $option->setProduct($product);
                $option->setCode("option_$optionId");
                $option->setValue($updatedOptions[$o->getTitle()]);
                $options[] = $option;
                $optionIds[] = $optionId;
            } elseif (isset($existingOption)) {
                $options[] = $existingOption;
                $optionIds[] = $optionId;
            }
        }
        // build record of option ids for the product options that have values
        $option = $this->itemOptionFactory->create();
        $option->setProduct($product);
        $option->setCode('option_ids');
        $option->setValue(join(',', $optionIds));
        $options[] = $option;
        return $options;
    }
}
