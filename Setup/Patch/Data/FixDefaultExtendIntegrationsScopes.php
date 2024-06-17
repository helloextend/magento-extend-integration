<?php
/*
 * Copyright Extend (c) 2022. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Setup\Patch\Data;

use Exception;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Phrase;
use Magento\Framework\Setup\Exception as SetupException;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;
use Magento\Integration\Model\IntegrationService;
use Magento\Integration\Model\AuthorizationService;

/**
 * This patch will add the scopes back to the default Extend integrations
 */
class FixDefaultExtendIntegrationsScopes implements DataPatchInterface, PatchRevertableInterface
{
    private IntegrationService $integrationService;
    private AuthorizationService $authorizationService;

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

    public function __construct(
        IntegrationService $integrationService,
        AuthorizationService $authorizationService
    ) {
        $this->integrationService = $integrationService;
        $this->authorizationService = $authorizationService;
      }

    /**
     * This patch depends on the MakeDefaultExtendIntegrationsMutable patch since it re-applies the default scopes which were removed by that patch
     */
    public static function getDependencies()
    {
        return [
          \Extend\Integration\Setup\Patch\Data\MakeDefaultExtendIntegrationsMutable::class
        ];
    }

    /**
     * @inheritDoc
     */
    public function getAliases()
    {
        return [];
    }

    /**
     * @inheritDoc
     * @throws SetupException
     */
    public function apply()
    {
        try {
            if ($prodIntegration = $this->integrationService->findByName('Extend Integration - Production')) {
              $this->authorizationService->grantPermissions($prodIntegration->getId(), $this->DEFAULT_INTEGRATION_RESOURCES);
            }
            if ($demoIntegration = $this->integrationService->findByName('Extend Integration - Demo')) {
              $this->authorizationService->grantPermissions($demoIntegration->getId(), $this->DEFAULT_INTEGRATION_RESOURCES);
            }
        } catch (Exception $exception) {
            throw new SetupException(
                new Phrase(
                    'There was a problem applying the Extend Integration Patch to fix the default scopes: %1',
                    [$exception->getMessage()]
                )
            );
        }
    }

    /**
     * @inheritDoc
     * @throws FileSystemException|SetupException
     */
    public function revert()
    {
        try {
            // for rollback, we need to set the scopes back to empty since the last patch removed them
            $prodIntegration = $this->integrationService->findByName('Extend Integration - Production');
            if ($prodIntegration) {
              $this->authorizationService->grantPermissions($prodIntegration->getId(), []);
            }

            $demoIntegration = $this->integrationService->findByName('Extend Integration - Demo');
            if ($demoIntegration) {
              $this->authorizationService->grantPermissions($demoIntegration->getId(), []);
            }
        } catch (Exception $exception) {
            throw new SetupException(
                new Phrase(
                    'There was a problem reverting the Extend Integration Patch to fix the default scopes: %1',
                    [$exception->getMessage()]
                )
            );
        }
    }
}
