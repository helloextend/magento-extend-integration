<?php
/*
 * Copyright Extend (c) 2023. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Controller\Adminhtml\Productprotection;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Message\ManagerInterface;

class Create extends \Magento\Backend\App\Action
{
    private \Extend\Integration\Setup\Model\AttributeSetInstaller $attributeSetInstaller;
    private \Extend\Integration\Setup\Model\ProductInstaller $productInstaller;

    /**
     * @param Context $context
     */
    public function __construct(
        Context $context,
        \Extend\Integration\Setup\Model\AttributeSetInstaller $attributeSetInstaller,
        \Extend\Integration\Setup\Model\ProductInstaller $productInstaller,
        ManagerInterface $messageManager
    ) {
        parent::__construct($context);
        $this->attributeSetInstaller = $attributeSetInstaller;
        $this->productInstaller = $productInstaller;
        $this->messageManager = $messageManager;
    }

    public function execute()
    {
        try {
            $this->attributeSetInstaller->deleteAttributeSet();
            $this->productInstaller->deleteProduct();
            $attributeSet = $this->attributeSetInstaller->createAttributeSet();
            $this->productInstaller->createProduct($attributeSet);
            $this->messageManager->addSuccessMessage(
                __('The Product Protection product and attribute set has been recreated.')
            );
            $this->getResponse()->setRedirect($this->_redirect->getRedirectUrl($this->getUrl('*')));
        } catch (\Exception $exception) {
            $this->messageManager->addErrorMessage(
                'The Product Protection product could not be recreated.' . $exception->getMessage()
            );
            $this->getResponse()->setRedirect($this->_redirect->getRedirectUrl($this->getUrl('*')));
        }
    }
}
