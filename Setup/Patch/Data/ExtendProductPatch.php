<?php
/*
 * Copyright Extend (c) 2022. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Setup\Patch\Data;

use Exception;
use Extend\Integration\Api\StoreIntegrationRepositoryInterface;
use Extend\Integration\Model\Config\Source\Environment;
use Extend\Integration\Service\Api\Integration;
use Extend\Integration\Service\Api\MetadataBuilder;
use Extend\Integration\Setup\Model\ProductInstaller;
use Extend\Integration\Setup\Model\AttributeSetInstaller;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Phrase;
use Magento\Framework\Setup\Exception as SetupException;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Integration\Api\OauthServiceInterface;
use Magento\Integration\Model\ConfigBasedIntegrationManager;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Integration\Model\IntegrationService;
use Magento\Store\Api\StoreRepositoryInterface as StoreRepositoryInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;

class ExtendProductPatch implements DataPatchInterface, PatchRevertableInterface
{
    private AttributeSetInstaller $attributeSetInstaller;
    private ConfigBasedIntegrationManager $configBasedIntegrationManager;
    private ProductInstaller $productInstaller;
    private State $state;
    private StoreIntegrationRepositoryInterface $integrationStoresRepository;
    private StoreRepositoryInterface $storeRepository;
    private IntegrationService $integrationService;
    private Integration $extendIntegration;
    private MetadataBuilder $metadataBuilder;
    private Environment $environment;
    private OauthServiceInterface $oauthService;
    private SchemaSetupInterface $schemaSetup;
    private WriterInterface $configWriter;

    public function __construct(
        AttributeSetInstaller $attributeSetInstaller,
        ConfigBasedIntegrationManager $configBasedIntegrationManager,
        ProductInstaller $productInstaller,
        StoreIntegrationRepositoryInterface $integrationStoresRepository,
        StoreRepositoryInterface $storeRepository,
        State $state,
        IntegrationService $integrationService,
        Integration $extendIntegration,
        MetadataBuilder $metadataBuilder,
        Environment $environment,
        OauthServiceInterface $oauthService,
        SchemaSetupInterface $schemaSetup,
        WriterInterface $configWriter
    ) {
        $this->attributeSetInstaller = $attributeSetInstaller;
        $this->configBasedIntegrationManager = $configBasedIntegrationManager;
        $this->productInstaller = $productInstaller;
        $this->state = $state;
        $this->integrationStoresRepository = $integrationStoresRepository;
        $this->storeRepository = $storeRepository;
        $this->integrationService = $integrationService;
        $this->extendIntegration = $extendIntegration;
        $this->metadataBuilder = $metadataBuilder;
        $this->environment = $environment;
        $this->oauthService = $oauthService;
        $this->schemaSetup = $schemaSetup;
        $this->configWriter = $configWriter;
    }

    /**
     * @inheritDoc
     */
    public static function getDependencies()
    {
        return [];
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
            if (
                !$this->integrationService
                    ->findByName('Extend Integration - Production')
                    ->getIntegrationId()
            ) {
                $this->configBasedIntegrationManager->processIntegrationConfig([
                    'Extend Integration - Production',
                ]);
            }

            if (
                !$this->integrationService
                    ->findByName('Extend Integration - Demo')
                    ->getIntegrationId()
            ) {
                $this->configBasedIntegrationManager->processIntegrationConfig([
                    'Extend Integration - Demo',
                ]);
            }

            //Set default active environment to demo
            $this->state->emulateAreaCode(Area::AREA_ADMINHTML, function () {
                $defaultEnvironmentId = $this->integrationService
                    ->findByName('Extend Integration - Demo')
                    ->getIntegrationId();
                $this->configWriter->save(
                    $this->extendIntegration::INTEGRATION_ENVIRONMENT_CONFIG,
                    $defaultEnvironmentId,
                    ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                    0
                );
            });
        } catch (Exception $exception) {
            throw new SetupException(
                new Phrase(
                    'There was a problem applying the Extend Integration Product Patch: %1',
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
            $this->state->emulateAreaCode(Area::AREA_ADMINHTML, function () {
                $installer = $this->schemaSetup;
                $installer->startSetup();

                $this->productInstaller->deleteProduct();
                $this->attributeSetInstaller->deleteAttributeSet();

                if (
                    $installer->tableExists(
                        \Extend\Integration\Model\ResourceModel\StoreIntegration::EXTEND_STORE_INTEGRATION_TABLE
                    )
                ) {
                    // body autogenerated by the Metadata Builder but isn't used in this case.  No store ids or pre-existing body provided to the builder.
                    [$headers, $body] = $this->metadataBuilder->execute(
                        [],
                        [
                            'path' =>
                                $this->extendIntegration::EXTEND_INTEGRATION_ENDPOINTS[
                                    'app_uninstall'
                                ],
                        ],
                        []
                    );

                    $integrations = $this->environment->toOptionArray();

                    foreach ($integrations as $integrationData) {
                        if (isset($integrationData['value'])) {
                            $integration = $this->integrationService->get(
                                $integrationData['value']
                            );
                        }
                        $consumerId = $integration->getConsumerId();
                        $oauthConsumerKey = $this->oauthService
                            ->loadConsumer($consumerId)
                            ->getKey();
                        $this->extendIntegration->execute(
                            [
                                'path' =>
                                    $this->extendIntegration::EXTEND_INTEGRATION_ENDPOINTS[
                                        'app_uninstall'
                                    ] .
                                    '?oauth_consumer_key=' .
                                    $oauthConsumerKey,
                                'type' => 'middleware',
                            ],
                            [],
                            $headers
                        );
                        $this->integrationService->delete($integrationData['value']);
                    }
                }

                if (
                    $installer->tableExists(
                        \Extend\Integration\Model\ResourceModel\StoreIntegration::EXTEND_STORE_INTEGRATION_TABLE
                    )
                ) {
                    $installer
                        ->getConnection()
                        ->dropTable(
                            $installer->getTable(
                                \Extend\Integration\Model\ResourceModel\StoreIntegration::EXTEND_STORE_INTEGRATION_TABLE
                            )
                        );
                }

                $installer->endSetup();
            });
        } catch (Exception $exception) {
            throw new SetupException(
                new Phrase(
                    'There was a problem applying the Extend Integration Product Patch (product deletion): %1',
                    [$exception->getMessage()]
                )
            );
        }
    }
}
