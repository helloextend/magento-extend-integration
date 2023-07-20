<?php
/*
 * Copyright Extend (c) 2023. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Controller\Adminhtml\Extend;

class About extends \Magento\Backend\App\Action
{
    /**
     * Redirects to Extend.com
     *
     * @return void
     */
    public function execute()
    {
        try {
            $this->getResponse()->setRedirect(
                $this->_redirect->getRedirectUrl($this->getUrl('https://www.extend.com/'))
            );
        } catch (\Exception $exception) {
            $this->messageManager->addErrorMessage(
                'Could not redirect to Extend.' . $exception->getMessage()
            );
            $this->getResponse()->setRedirect($this->_redirect->getRedirectUrl($this->getUrl('*')));
        }
    }
}