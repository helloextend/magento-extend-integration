<?php
/*
 * Copyright Extend (c) 2023. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Controller\Adminhtml\Extend;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Message\ManagerInterface;

class About extends \Magento\Backend\App\Action
{
    const EXTEND_ABOUT_URL = 'https://www.extend.com';
    const FALLBACK_URL = '/admin/dashboard/index';

    /**
     * @param Context $context
     * @param ManagerInterface $messageManager
     */
    public function __construct(Context $context, ManagerInterface $messageManager)
    {
        parent::__construct($context);
        $this->messageManager = $messageManager;
    }

    /**
     * Redirects to Extend.com
     *
     * @return void
     */
    public function execute()
    {
        try {
            $this->getResponse()
                ->setRedirect(self::EXTEND_ABOUT_URL)
                ->sendResponse();
        } catch (\Exception $exception) {
            $this->messageManager->addErrorMessage('Could not redirect to Extend.com.');
            $this->getResponse()->setRedirect($this->_redirect->getRedirectUrl(self::FALLBACK_URL));
        }
    }
}
