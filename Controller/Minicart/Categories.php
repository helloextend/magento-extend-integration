<?php
/*
 * Copyright Extend (c) 2023. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Controller\Minicart;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\NoSuchEntityException;

class Categories extends \Magento\Framework\App\Action\Action
{
    /**
     * @var CheckoutSession
     */
    protected $checkoutSession;

    /**
     * @var CategoryRepositoryInterface
     */
    protected $categoryRepository;

    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;

    public function __construct(
      Context $context,
      JsonFactory $resultJsonFactory,
      CheckoutSession $checkoutSession,
      CategoryRepositoryInterface $categoryRepository,
      ProductRepositoryInterface $productRepository
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->checkoutSession = $checkoutSession;
        $this->categoryRepository = $categoryRepository;
        $this->productRepository = $productRepository;
        parent::__construct($context);
    }

    public function execute()
    {
        $items = [];
        // this endpoint now accepts a query parameter `product_ids` which is a comma-separated list of product ids.
        // this enables us to bypass a getQuote() call and simply get the category for the products specified in the request.
        if ($this->getRequest()->getParam('product_ids') !== null) {
          $productIds = array_filter(
            array_map('trim', explode(',', $this->getRequest()->getParam('product_ids'))),
            function ($id) {
              return $id !== '' && is_numeric($id);
            }
          );
          foreach ($productIds as $productId) {
            try {
              /** @var \Magento\Catalog\Model\Product $product */
              $product = $this->productRepository->getById($productId);
              $categories = $product->getCategoryIds();
              if (count($categories) === 0) {
                continue;
              }
              $category = $this->categoryRepository->get($categories[0]);
              $items[$productId] = $category->getName();
            } catch (NoSuchEntityException $e) {
              continue;
            }
          }
        }
        $resultJson = $this->resultJsonFactory->create();
        return $resultJson->setData($items);
    }
}
