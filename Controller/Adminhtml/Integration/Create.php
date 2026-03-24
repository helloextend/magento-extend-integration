<?php
/*
 * Copyright Extend (c) 2026. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Controller\Adminhtml\Integration ;

use Extend\Integration\Logger\ExtendIntegration as IntegrationLogger;
use Magento\Integration\Model\IntegrationService;
use Magento\Integration\Model\ConfigBasedIntegrationManager;
use Magento\Integration\Model\AuthorizationService;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Message\ManagerInterface;

/**
 * Extend this class to generate a controller route for creating a new integration.
 */
abstract class Create extends \Magento\Backend\App\Action
{

    /**
     * @var IntegrationLogger
     */
    private IntegrationLogger $integrationLogger;

   /**
    * @var IntegrationService
    */
    private IntegrationService $integrationService;

    /**
     * @var ConfigBasedIntegrationManager
     */
    private ConfigBasedIntegrationManager $configBasedIntegrationManager;

    /**
     * @var AuthorizationService
     */
    private AuthorizationService $authorizationService;

    /**
     * @var string
     */
    private string $integrationName;

    /**
     * @var array
     */
    private $DEFAULT_INTEGRATION_RESOURCES = [
      "Magento_Backend::admin",
      "Magento_Backend::system",
      "Magento_Backend::stores",
      "Magento_Backend::store",
      "Extend_Integration::manage",
      "Magento_Sales::sales",
      "Magento_Sales::sales_operation",
      "Magento_Sales::sales_creditmemo",
      "Magento_Sales::actions_view",
      "Magento_Sales::shipment",
      "Magento_Catalog::catalog",
      "Magento_Catalog::products",
      "Magento_Catalog::categories",
    ];
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
        ManagerInterface $messageManager,
        IntegrationLogger $integrationLogger
    ) {
        parent::__construct($context);
        $this->integrationService = $integrationService;
        $this->configBasedIntegrationManager = $configBasedIntegrationManager;
        $this->authorizationService = $authorizationService;
        $this->messageManager = $messageManager;
        $this->integrationName = $this->getIntegrationName();
        $this->integrationLogger = $integrationLogger;
    }

    /**
     * Implement this method to return the name of the integration that the controller route should create
     */
    abstract protected function getIntegrationName();

        /**
         * Create the integration
         *
         * @return void
         */
    public function execute()
    {
        try {
            $this->integrationLogger->info('Integration creation requested', ['name' => $this->integrationName]);

            if (!$this->integrationService
                  ->findByName($this->integrationName)
                  ->getIntegrationId()
            ) {
                $this->integrationLogger->info('Creating integration via config', ['name' => $this->integrationName]);
                $this->configBasedIntegrationManager->processIntegrationConfig([
                  $this->integrationName,
                ]);

                // need to set the setup_type of the new integration to 0 to make it editable/deletable
                $updatedIntegration = $this->integrationService
                  ->findByName($this->integrationName)
                  ->setSetupType(0);

                // apply the update
                $this->integrationService->update($updatedIntegration->getData());
                $this->integrationLogger->info('Integration created and made mutable', [
                    'name' => $this->integrationName,
                    'integration_id' => $updatedIntegration->getId(),
                ]);

                $this->integrationLogger->info('Granting permissions to integration', [
                    'name' => $this->integrationName,
                    'integration_id' => $updatedIntegration->getId(),
                    'resources' => $this->DEFAULT_INTEGRATION_RESOURCES,
                ]);
                $this->authorizationService->grantPermissions($updatedIntegration->getId(), $this->DEFAULT_INTEGRATION_RESOURCES);
                $this->integrationLogger->info('Permissions granted to integration', [
                    'name' => $this->integrationName,
                    'integration_id' => $updatedIntegration->getId(),
                ]);

                $this->messageManager->addSuccessMessage(
                    __('Successfully created ' . $this->integrationName)
                );
            } else {
                $this->integrationLogger->info('Integration already exists, skipping creation', ['name' => $this->integrationName]);
                $this->messageManager->addErrorMessage(
                    $this->integrationName . ' already exists.'
                );
            }
            $this->getResponse()->setRedirect($this->_redirect->getRedirectUrl($this->getUrl('*')));
        } catch (\Exception $exception) {
            $this->integrationLogger->error('Integration creation failed', [
                'name' => $this->integrationName,
                'error' => $exception->getMessage(),
            ]);
            $this->messageManager->addErrorMessage(
                'Error creating ' . $this->integrationName . '. Error message: ' . $exception->getMessage()
            );
            $this->getResponse()->setRedirect($this->_redirect->getRedirectUrl($this->getUrl('*')));
        }
    }
}
