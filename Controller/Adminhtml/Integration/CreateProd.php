<?php
/*
 * Copyright Extend (c) 2026. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Controller\Adminhtml\Integration;

use Extend\Integration\Controller\Adminhtml\Integration\Create;
use Extend\Integration\Logger\ExtendIntegration as IntegrationLogger;
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
     * @param IntegrationLogger $integrationLogger
     */
    public function __construct(
        Context $context,
        IntegrationService $integrationService,
        ConfigBasedIntegrationManager $configBasedIntegrationManager,
        AuthorizationService $authorizationService,
        ManagerInterface $messageManager,
        IntegrationLogger $integrationLogger
    ) {
        parent::__construct($context, $integrationService, $configBasedIntegrationManager, $authorizationService, $messageManager, $integrationLogger);
    }

    protected function getIntegrationName()
    {
        return 'Extend Integration - Production';
    }
}
