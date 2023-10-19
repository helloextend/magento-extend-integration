<?php
/*
 * Copyright Extend (c) 2022. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Service\Api;

use Extend\Integration\Api\ExtendOAuthClientRepositoryInterface;
use Extend\Integration\Api\StoreIntegrationRepositoryInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\HTTP\Client\Curl;

class AccessTokenBuilder
{
    public const TOKEN_EXCHANGE_ENDPOINT = '/auth/oauth/token';
    private const TOKEN_GRANT_TYPE = 'client_credentials';
    private const AUTH_SCOPE = 'magento:webhook';

    /**
     * @var ExtendOAuthClientRepositoryInterface
     */
    private $extendOAuthClientRepository;

    /**
     * @var StoreIntegrationRepositoryInterface
     */
    private $storeIntegrationRepository;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var Curl
     */
    private $curl;

    /**
     * @var EncryptorInterface
     */
    private $encryptor;

    /**
     * @var ActiveEnvironmentURLBuilder
     */
    private $activeEnvironmentURLBuilder;

    /**
     * AccessTokenBuilder constructor
     *
     * @param ExtendOAuthClientRepositoryInterface $extendOAuthClientRepository
     * @param StoreIntegrationRepositoryInterface $storeIntegrationRepository
     * @param ScopeConfigInterface $scopeConfig
     * @param Curl $curl
     * @param EncryptorInterface $encryptor
     * @param ActiveEnvironmentURLBuilder $activeEnvironmentURLBuilder
     */
    public function __construct(
        ExtendOAuthClientRepositoryInterface $extendOAuthClientRepository,
        StoreIntegrationRepositoryInterface $storeIntegrationRepository,
        ScopeConfigInterface $scopeConfig,
        Curl $curl,
        EncryptorInterface $encryptor,
        ActiveEnvironmentURLBuilder $activeEnvironmentURLBuilder
    ) {
        $this->extendOAuthClientRepository = $extendOAuthClientRepository;
        $this->storeIntegrationRepository = $storeIntegrationRepository;
        $this->scopeConfig = $scopeConfig;
        $this->curl = $curl;
        $this->encryptor = $encryptor;
        $this->activeEnvironmentURLBuilder = $activeEnvironmentURLBuilder;
    }
    /**
     * Get an Extend access token to make API calls to the Extend Magento service
     *
     * @return string
     */
    public function getAccessToken(): string
    {
        $clientData = $this->getExtendOAuthClientData();

        $extendClientId = $clientData['clientId'];
        $extendClientSecret = $clientData['clientSecret'];

        if ($extendClientId && $extendClientSecret) {
            $endpoint = $this->activeEnvironmentURLBuilder->getApiURL() . self::TOKEN_EXCHANGE_ENDPOINT;

            $decryptedClientSecret = $this->encryptor->decrypt($extendClientSecret);

            $payload = [
                'grant_type' => self::TOKEN_GRANT_TYPE,
                'client_id' => $extendClientId,
                'client_secret' => $decryptedClientSecret,
                'scope' => self::AUTH_SCOPE,
            ];

            $headers = ['Content-Type: application/json'];
            $this->curl->setHeaders($headers);

            // Submit the request and get the response
            $this->curl->post($endpoint, $payload);

            $response = $this->curl->getBody();
            $data = json_decode($response, true);
            if (isset($data['access_token'])) {
                return $data['access_token'];
            }
        }

        return '';
    }

    /**
     * Get the Extend OAuth client_id and client_secret.
     *
     * @return array{clientId: string|null, clientSecret: string|null}
     */
    private function getExtendOAuthClientData(): array
    {
        // Get the integration ID from the configuration
        $integrationId = (int) $this->scopeConfig->getValue(
            \Extend\Integration\Service\Api\Integration::INTEGRATION_ENVIRONMENT_CONFIG
        );

        // First try to get the client_id and client_secret from the ExtendOAuthClient table.
        $clientDataFromExtendOAuthClient = $this->getClientDataFromExtendOAuthClient($integrationId);
        if ($clientDataFromExtendOAuthClient['clientId'] &&
            $clientDataFromExtendOAuthClient['clientSecret']
            ) {
            return $clientDataFromExtendOAuthClient;
        }

        // Fallback to the StoreIntegration table which contained the client data prior to ExtendOAuthClient.
        return $this->getClientDataFromStoreIntegration($integrationId);
    }

    /**
     * Get the Extend OAuth client data from the ExtendOAuthClient table
     *
     * @param integer $integrationId
     * @return array{clientId: string|null, clientSecret: string|null}
     */
    private function getClientDataFromExtendOAuthClient(int $integrationId): array
    {
        try {
            $extendOAuthClient = $this->extendOAuthClientRepository->getByIntegrationId($integrationId);
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            // Avoid throwing an exception here so the fallback to getClientDataFromStoreIntegration can be used.
            return [
                'clientId' => null,
                'clientSecret' => null
            ];
        }

        return [
            'clientId' => $extendOAuthClient->getExtendClientId(),
            'clientSecret' => $extendOAuthClient->getExtendClientSecret()
        ];
    }

    /**
     * Get the Extend OAuth client data from the StoreIntegration table.
     *
     * Note: This will be removed in a future release.
     *
     * @deprecated [PAR-5480] Extend OAuth client data was moved to the ExtendOAuthClient table.
     * @see /Extend/Integration/Service/Api/AccessTokenBuilder - getClientDataFromExtendOAuthClient
     * @param int $integrationId
     * @return array{clientId: string|null, clientSecret: string|null}
     */
    private function getClientDataFromStoreIntegration(int $integrationId): array
    {
        $storeIds = $this->storeIntegrationRepository->getListByIntegration($integrationId);

        if (count($storeIds) > 0) {
            // All stores in the integration have the same client_id and client_secret.
            $storeId = $storeIds[0];
            $integration = $this->storeIntegrationRepository->getByStoreIdAndIntegrationId(
                $storeId,
                $integrationId
            );

            return [
                'clientId' => $integration->getExtendClientId(),
                'clientSecret' => $integration->getExtendClientSecret()
            ];
        }

        return [
            'clientId' => null,
            'clientSecret' => null
        ];
    }
}
