<?php
/*
 * Copyright Extend (c) 2022. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Service\Api;

use Extend\Integration\Api\StoreIntegrationRepositoryInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\HTTP\Client\Curl;

class AccessTokenBuilder
{
    const TOKEN_EXCHANGE_ENDPOINT = '/auth/oauth/token';
    const TOKEN_GRANT_TYPE = 'client_credentials';
    const AUTH_SCOPE = 'magento:webhook';
    private StoreIntegrationRepositoryInterface $storeIntegrationRepository;
    private ScopeConfigInterface $scopeConfig;
    private Curl $curl;
    private EncryptorInterface $encryptor;
    private ActiveEnvironmentURLBuilder $activeEnvironmentURLBuilder;

    public function __construct(
        StoreIntegrationRepositoryInterface $storeIntegrationRepository,
        ScopeConfigInterface $scopeConfig,
        Curl $curl,
        EncryptorInterface $encryptor,
        ActiveEnvironmentURLBuilder $activeEnvironmentURLBuilder
    ) {
        $this->storeIntegrationRepository = $storeIntegrationRepository;
        $this->scopeConfig = $scopeConfig;
        $this->curl = $curl;
        $this->encryptor = $encryptor;
        $this->activeEnvironmentURLBuilder = $activeEnvironmentURLBuilder;
    }
    /**
     * @return string
     */
    public function getAccessToken(): string
    {
        $integrationId = (int) $this->scopeConfig->getValue(
            \Extend\Integration\Service\Api\Integration::INTEGRATION_ENVIRONMENT_CONFIG
        );
        $storeIds = $this->storeIntegrationRepository->getListByIntegration($integrationId);

        if (count($storeIds) > 0) {
            $storeId = $storeIds[0];
            $integration = $this->storeIntegrationRepository->getByStoreIdAndIntegrationId(
                $storeId,
                $integrationId
            );

            $integrationClientId = $integration->getExtendClientId();
            $integrationClientSecret = $integration->getExtendClientSecret();

            if ($integrationClientId && $integrationClientSecret) {
                $endpoint =
                    $this->activeEnvironmentURLBuilder->getApiURL() . self::TOKEN_EXCHANGE_ENDPOINT;

                $decryptedClientSecret = $this->encryptor->decrypt($integrationClientSecret);

                $payload = [
                    'grant_type' => self::TOKEN_GRANT_TYPE,
                    'client_id' => $integrationClientId,
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
        }

        return '';
    }
}
