<?php
/*
 * Copyright Extend (c) 2023. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\ViewModel;

use Magento\Catalog\Api\CategoryRepositoryInterface;

class Category implements
    \Magento\Framework\View\Element\Block\ArgumentInterface
{
    /**
     * @var CategoryRepositoryInterface
     */
    protected $categoryRepository;

    /**
     * Category constructor
     *
     * @param CategoryRepositoryInterface $categoryRepository
     */
    public function __construct(
      CategoryRepositoryInterface $categoryRepository
    ) {
        $this->categoryRepository = $categoryRepository;
    }

    public function getCategoryNameById($categoryId): string
    {
        $category = $this->categoryRepository->get($categoryId);
        return $category->getName();
    }
}
