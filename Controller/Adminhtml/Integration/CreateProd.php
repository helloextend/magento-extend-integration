<?php
/*
 * Copyright Extend (c) 2024. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Controller\Adminhtml\Integration;

use Extend\Integration\Controller\Adminhtml\Integration\Create;
use Magento\Integration\Model\IntegrationService;
use Magento\Integration\Model\ConfigBasedIntegrationManager;
use Magento\Integration\Model\AuthorizationService;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Message\ManagerInterface;

class CreateProd extends Create
{

    /**
     * @param Context $context
     * @param IntegrationService $integrationService
     * @param ConfigBasedIntegrationManager $configBasedIntegrationManager
     * @param AuthorizationService $authorizationService;
     * @param ManagerInterface $messageManager
     */
    public function __construct(
        Context $context,
        IntegrationService $integrationService,
        ConfigBasedIntegrationManager $configBasedIntegrationManager,
        AuthorizationService $authorizationService,
        ManagerInterface $messageManager
    ) {
        parent::__construct($context, $integrationService, $configBasedIntegrationManager, $authorizationService, $messageManager);
    }

    protected function getIntegrationName()
    {
        return 'Extend Integration - Production';
    }
}
