<?php

/*
 * Copyright Extend (c) 2023. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Block\Adminhtml\Integration\Edit\Tab;

use Extend\Integration\Api\StoreIntegrationRepositoryInterface;
use Extend\Integration\Model\Config\Source\Environment;
use Extend\Integration\Service\Api\AccessTokenBuilder;
use Extend\Integration\Service\Api\Integration;
use Extend\Integration\Service\Api\ActiveEnvironmentURLBuilder;
use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Exception\IntegrationException;
use Magento\Integration\Api\IntegrationServiceInterface;

/**
 * Class Intro
 *
 * Renders Intro Field
 */
class FinishIntegration extends Field
{
    const INACTIVE_INTEGRATION = 0;
    const ACTIVE_INTEGRATION_WITHOUT_CURRENT_STORE = 1;
    const ACTIVE_INTEGRATION_WITH_CURRENT_STORE = 2;
    const ERROR_SEARCHING_FOR_DELETED_INTEGRATION = 3;
    const ERROR_SEARCHING_FOR_INTEGRATION = 4;
    const ERROR_EXTEND_ACCOUNT = 5;

    /**
     * Path to template file in theme
     *
     * @var string
     */

    protected $_template = 'Extend_Integration::system/config/finish-integration-steps.phtml';
    private Environment $environment;
    private IntegrationServiceInterface $integrationService;
    private Context $context;
    private AccessTokenBuilder $accessTokenBuilder;
    private ScopeConfigInterface $scopeConfig;
    private StoreIntegrationRepositoryInterface $storeIntegrationRepository;
    private ActiveEnvironmentURLBuilder $activeEnvironmentURLBuilder;

    /**
     * Intro constructor
     *
     * @param Context $context
     * @param Environment $environment
     * @param IntegrationServiceInterface $integrationService
     * @param AccessTokenBuilder $accessTokenBuilder
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreIntegrationRepositoryInterface $storeIntegrationRepository
     * @param ActiveEnvironmentURLBuilder $activeEnvironmentURLBuilder
     * @param array $data
     */
    public function __construct(
        Context                     $context,
        Environment                 $environment,
        IntegrationServiceInterface $integrationService,
        AccessTokenBuilder $accessTokenBuilder,
        ScopeConfigInterface $scopeConfig,
        StoreIntegrationRepositoryInterface $storeIntegrationRepository,
        ActiveEnvironmentURLBuilder $activeEnvironmentURLBuilder,
        array                       $data = []
    ) {
        parent::__construct($context, $data);
        $this->environment = $environment;
        $this->integrationService = $integrationService;
        $this->context = $context;
        $this->accessTokenBuilder = $accessTokenBuilder;
        $this->scopeConfig = $scopeConfig;
        $this->storeIntegrationRepository = $storeIntegrationRepository;
        $this->activeEnvironmentURLBuilder = $activeEnvironmentURLBuilder;
    }

    /**
     * Return element html
     *
     * @param AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element): string
    {
        return $this->_toHtml();
    }

    /**
     * Gets the status of the currently selected integration for the current store
     *
     * @return int
     * @throws IntegrationException
     */
    public function getActiveIntegrationStatusOnStore(): int
    {
        try {
            $activeIntegration = $this->scopeConfig->getValue(Integration::INTEGRATION_ENVIRONMENT_CONFIG);
            $integration = $this->integrationService->get($activeIntegration);

            if ($this->getStatus($integration) === 1 && $integration->getId()) {
                $storeListForActiveIntegration = $this->storeIntegrationRepository->getListByIntegration($integration->getId());
                $currentStore = (int) $this->getRequest()->getParam('store');


                if (in_array($currentStore, $storeListForActiveIntegration)) {
                    $storeIntegration = $this->storeIntegrationRepository->getByStoreIdAndIntegrationId(
                        $currentStore,
                        $activeIntegration
                    );
                    if ($storeIntegration->getIntegrationError() !== null) {
                        return self::ERROR_EXTEND_ACCOUNT;
                    }
                    return self::ACTIVE_INTEGRATION_WITH_CURRENT_STORE;
                }

                return self::ACTIVE_INTEGRATION_WITHOUT_CURRENT_STORE;
            }
        } catch (\Exception $exception) {
            // Attempting to fetch an integration that doesn't exist will throw an exception that will prevent
            // the page from rendering. This can happen if they previously selected an integration from the dropdown
            // and then deleted it.
            if ($exception instanceof IntegrationException && strpos($exception->getMessage(), "doesn't exist") !== false) {
                return self::ERROR_SEARCHING_FOR_DELETED_INTEGRATION;
            }

            return self::ERROR_SEARCHING_FOR_INTEGRATION;
        }

        return self::INACTIVE_INTEGRATION;
    }

    public function getExtendStoreUuid(): ?string
    {
        try {
            $currentStore = (int) $this->getRequest()->getParam('store');
            $activeIntegration = $this->scopeConfig->getValue(Integration::INTEGRATION_ENVIRONMENT_CONFIG);
            $storeIntegration = $this->storeIntegrationRepository->getByStoreIdAndIntegrationId(
                $currentStore,
                $activeIntegration
            );
            return $storeIntegration->getExtendStoreUuid();
        } catch (\Exception $exception) {
            return null;
        }
    }

    /**
     * Builds a URL that will take the user back to the global integration settings page instead of a store specific one
     *
     * @return string
     */
    public function getDefaultScopeUrl(): string
    {
        $urlBuilder = $this->context->getUrlBuilder();
        return $urlBuilder->getUrl('admin/system_config/edit/section/extend', ['key' => $urlBuilder->getSecretKey('adminhtml', 'system_config', 'edit')]);
    }

    /**
     * Builds a URL that will go to the Extend integration settings page
     *
     * @return string
     */
    public function getIntegrationUrl(): string
    {
        $urlBuilder = $this->context->getUrlBuilder();
        return $urlBuilder->getUrl('admin/integration', ['key' => $urlBuilder->getSecretKey('adminhtml', 'integration', 'index')]);
    }

    /**
     * Fetches the name of the currently selected integration
     *
     * @return string
     */
    public function getCurrentIntegrationName(): string
    {
        $activeIntegration = $this->scopeConfig->getValue(Integration::INTEGRATION_ENVIRONMENT_CONFIG);
        $integration = $this->integrationService->get($activeIntegration);
        return $integration->getName();
    }

    /**
     * Determines whether the passed in integration is enabled and we've done the handshake with Extend
     * to initialize the integration.
     *
     * @param \Magento\Integration\Model\Integration $integration
     * @return int
     */
    private function getStatus($integration): int
    {
        $integrationStatus = $integration->getStatus();

        $clientData = $this->accessTokenBuilder->getExtendOAuthClientData($integration->getId());

        if ($integrationStatus === 1 &&
            isset($clientData['clientId']) &&
            isset($clientData['clientSecret']) &&
            $clientData['clientId'] &&
            $clientData['clientSecret']
        ) {
            return 1;
        } else {
            return 0;
        }
    }
}
