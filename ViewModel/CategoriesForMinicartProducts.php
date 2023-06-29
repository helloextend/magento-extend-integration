<?php
/*
 * Copyright Extend (c) 2023. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\ViewModel;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Checkout\Model\Session;

class CategoriesForMinicartProducts implements
    \Magento\Framework\View\Element\Block\ArgumentInterface
{
    private Session $cartSession;
    private CategoryRepositoryInterface $categoryRepository;

    public function __construct(
        Session $cartSession,
        CategoryRepositoryInterface $categoryRepository
    ) {
        $this->cartSession = $cartSession;
        $this->categoryRepository = $categoryRepository;
    }

    public function getProductCategories()
    {
        $products = [];
        $quote = $this->cartSession->getQuote();
        foreach ($quote->getAllVisibleItems() as $item) {
            $product = $item->getProduct();
            if ($product->getCategoryIds()) {
                $categoryAssoc = [];
                foreach ($product->getCategoryIds() as $categoryId) {
                    $category = $this->categoryRepository->get($categoryId);
                    $categoryAssoc[] = $category->getName();
                }
                $products[$product->getSku()]['categories'] = $categoryAssoc;
            }
        }
        return $products;
    }
}
