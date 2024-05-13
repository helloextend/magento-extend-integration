<?php
/*
 * Copyright Extend (c) 2023. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Controller\Minicart;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;

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
     * @var JsonFactory
     */
    protected $resultJsonFactory;

    public function __construct(
      Context $context,
      JsonFactory $resultJsonFactory,
      CheckoutSession $checkoutSession,
      CategoryRepositoryInterface $categoryRepository
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->checkoutSession = $checkoutSession;
        $this->categoryRepository = $categoryRepository;
        parent::__construct($context);
    }

    public function execute()
    {
        $items = [];
        $cart = $this->checkoutSession->getQuote();
        foreach ($cart->getAllVisibleItems() as $item) {
            $product = $item->getProduct();

            if (!$product) {
                continue;
            }

            $categories = $product->getCategoryIds();
            $id = $item->getId();

            if (count($categories) > 0) {
              $category = $this->categoryRepository->get($categories[0]);
              $items[$id] = $category->getName();
            }
        }
        $resultJson = $this->resultJsonFactory->create();
        return $resultJson->setData($items);
    }
}
