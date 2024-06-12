<?php
/*
 * Copyright Extend (c) 2024. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Controller\Adminhtml\Integration ;

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
        ManagerInterface $messageManager
    ) {
        parent::__construct($context);
        $this->integrationService = $integrationService;
        $this->configBasedIntegrationManager = $configBasedIntegrationManager;
        $this->authorizationService = $authorizationService;
        $this->messageManager = $messageManager;
        $this->integrationName = $this->getIntegrationName();
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

            if (!$this->integrationService
                  ->findByName($this->integrationName)
                  ->getIntegrationId()
            ) {
                $this->configBasedIntegrationManager->processIntegrationConfig([
                  $this->integrationName,
                ]);

                // need to set the setup_type of the new integration to 0 to make it editable/deletable
                $updatedIntegration = $this->integrationService
                  ->findByName($this->integrationName)
                  ->setSetupType(0);

                // apply the update
                $this->integrationService->update($updatedIntegration->getData());


                $this->authorizationService->grantPermissions($updatedIntegration->getId(), $this->DEFAULT_INTEGRATION_RESOURCES);
                $this->messageManager->addSuccessMessage(
                    __('Successfully created ' . $this->integrationName)
                );
            } else {
                $this->messageManager->addErrorMessage(
                    $this->integrationName . ' already exists.'
                );
            }
            $this->getResponse()->setRedirect($this->_redirect->getRedirectUrl($this->getUrl('*')));
        } catch (\Exception $exception) {
            $this->messageManager->addErrorMessage(
                'Error creating ' . $this->integrationName . '. Error message: ' . $exception->getMessage()
            );
            $this->getResponse()->setRedirect($this->_redirect->getRedirectUrl($this->getUrl('*')));
        }
    }
}
