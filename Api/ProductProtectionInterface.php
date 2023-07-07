<?php
/*
 * Copyright Extend (c) 2022. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Api;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

interface ProductProtectionInterface
{
    const PLAN_ID = 'plan_id';

    const PLAN_TYPE = 'plan_type';

    const ASSOCIATED_PRODUCT = 'associated_product';

    const TERM = 'term';

    const OFFER_PLAN_ID = 'offer_plan_id';

    const LEAD_TOKEN = 'lead_token';

    const LEAD_QUANTITY = 'lead_quantity';

    const LIST_PRICE = 'list_price';

    /**
     * Set plan_id
     *
     * @param string $planId
     * @return void
     */
    public function setPlanId(?string $planId);

    /**
     * Set plan_type
     *
     * @param string $planType
     * @return void
     */
    public function setPlanType(?string $planType);

    /**
     * Set associated_product
     *
     * @param string $associatedProduct
     * @return void
     */
    public function setAssociatedProduct(?string $associatedProduct);

    /**
     * Set term
     *
     * @param string $term
     * @return void
     */
    public function setTerm(?string $term);

    /**
     * Set offer_plan_id
     *
     * @param string $offerPlanId
     * @return void
     */
    public function setOfferPlanId(?string $offerPlanId);

    /**
     * Set lead_token
     *
     * @param string $leadToken
     * @return void
     */
    public function setLeadToken(?string $leadToken);
    /**
     * Set lead_quantity
     *
     * @param int $leadQuantity
     * @return void
     */
    public function setLeadQuantity(?int $leadQuantity);
    /**
     * Set list_price
     *
     * @param string $listPrice
     * @return void
     */
    public function setListPrice(?string $listPrice);

    /**
     * Get plan_id
     *
     * @return string
     */
    public function getPlanId(): ?string;

    /**
     * Get plan_type
     *
     * @return string
     */
    public function getPlanType(): ?string;

    /**
     * Get associated_product
     *
     * @return string
     */
    public function getAssociatedProduct(): ?string;

    /**
     * Get term
     *
     * @return string
     */
    public function getTerm(): ?string;

    /**
     * Get offer_plan_id
     *
     * @return string
     */
    public function getOfferPlanId(): ?string;

    /**
     * Get lead_token
     *
     * @return string
     */
    public function getLeadToken(): ?string;

    /**
     * Get lead_quantity
     *
     * @return int
     */
    public function getLeadQuantity(): ?int;

    /**
     * Get list_price
     *
     * @return string
     */
    public function getListPrice(): ?string;

    /**
     * Upsert product protection in cart via checkout session
     * Utilizes checkout session which is only available for calls made from the storefront
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
    public function upsertSession(
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
    ): void;

    /**
     * Upsert product protection in cart via cart id
     *
     * @param int|null $quantity
     * @param string|null $cartId
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
    public function upsertCartId(
        int $quantity = null,
        string $cartId = null,
        string $cartItemId = null,
        string $productId = null,
        string $planId = null,
        int $price = null,
        int $term = null,
        string $coverageType = null,
        string $leadToken = null,
        string $listPrice = null,
        string $orderOfferPlanId = null
    ): void;
}
