<?php
/*
 * Copyright Extend (c) 2023. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Test\Unit\ViewModel;

use PHPUnit\Framework\TestCase;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\Data\CategoryInterface;
use Extend\Integration\ViewModel\Category as CategoryViewModel;

class CategoryTest extends TestCase
{
    /**
     * @var CategoryRepositoryInterface
     */
    protected $categoryRepository;

    /** @var CategoryViewModel */
    private $categoryViewModel;

    protected function setUp(): void
    {
        $this->categoryRepository = $this->createStub(CategoryRepositoryInterface::class);
        $this->categoryViewModel = new CategoryViewModel(
          $this->categoryRepository
      );
    }

    public function testGetCategoryNameById()
    {
        $categoryMock = $this->createStub(CategoryInterface::class);
        $categoryMock->method('getName')->willReturn('Tools');
        $this->categoryRepository->method('get')->willReturn($categoryMock);
        $this->assertEquals('Tools', $this->categoryViewModel->getCategoryNameById(1));
    }
}
