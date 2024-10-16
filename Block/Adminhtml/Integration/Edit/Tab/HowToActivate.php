<?php

/*
 * Copyright Extend (c) 2023. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Block\Adminhtml\Integration\Edit\Tab;

use Extend\Integration\Model\Config\Source\Environment;
use Extend\Integration\Service\Api\AccessTokenBuilder;
use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Exception\IntegrationException;
use Magento\Integration\Api\IntegrationServiceInterface;

/**
 * Class Intro
 *
 * Renders Intro Field
 */
class HowToActivate extends Field
{
	/**
	 * Path to template file in theme
	 *
	 * @var string
	 */
	protected $_template = 'Extend_Integration::system/config/how-to-activate.phtml';
	// TODO: MINT-2855 Switch to the following template instead of the one above
	// protected $_template = 'Extend_Integration::system/config/integration-status.phtml';
	private Environment $environment;
	private IntegrationServiceInterface $integrationService;
	private Context $context;
	private AccessTokenBuilder $accessTokenBuilder;
	private $stepMap = [
		0 => 'activation_required',
		1 => 'identity_link_required',
		2 => 'complete'
	];

	/**
	 * Intro constructor
	 *
	 * @param Context $context
	 * @param Environment $environment
	 * @param IntegrationServiceInterface $integrationService
	 * @param array $data
	 */
	public function __construct(
		Context                     $context,
		Environment                 $environment,
		IntegrationServiceInterface $integrationService,
		AccessTokenBuilder $accessTokenBuilder,
		array                       $data = []
	)
	{
		parent::__construct($context, $data);
		$this->environment = $environment;
		$this->integrationService = $integrationService;
		$this->context = $context;
		$this->accessTokenBuilder = $accessTokenBuilder;
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
	 * Loops through integrations dropdown and gets activation status of each one.
	 *
	 * @return array
	 * @throws IntegrationException
	 */
	public function getIntegrations()
	{
		$urlBuilder = $this->context->getUrlBuilder();
		$integrationsStatuses = [];
		$environmentOptions = $this->environment->toOptionArray();
		if ($environmentOptions) {
			foreach ($environmentOptions as $environmentOption) {
				$integration = $this->integrationService->get($environmentOption['value']);

				$identityLinkUrl = $integration->getIdentityLinkUrl() . '?oauth_consumer_key=' . $integration->getConsumerKey() . '&success_call_back=' . $urlBuilder->getCurrentUrl();;
				$isAuthHandshakeComplete = $this->isAuthHandshakeComplete($integration);
				$isIdentityLinkConfirmed = $this->isIdentityLinkConfirmed($integration);
				$isIntegrationComplete = $isAuthHandshakeComplete && $isIdentityLinkConfirmed;

				$integrationCreatedAt = $integration->getCreatedAt();
				$integrationUpdatedAt = $integration->getUpdatedAt();

				$oauthActivatedAt = $isAuthHandshakeComplete && $integrationUpdatedAt > $integrationCreatedAt ? $integrationUpdatedAt : null;

				$prevActivationFailed = !$isAuthHandshakeComplete && $integrationUpdatedAt > $integrationCreatedAt;

				$currentStep = $this->stepMap[0];
				if ($isIntegrationComplete) {
					$currentStep = $this->stepMap[2];
				} elseif ($isAuthHandshakeComplete) {
					$currentStep = $this->stepMap[1];
				}

				$integrationsStatuses[] = [
					'activation_status' => $this->getStatus($integration), // TODO: MINT-2855 Remove the activation status as this was only used by old template
					'current_step' => $currentStep,
					'identity_link_url' => $identityLinkUrl,
					'integration_id' => $integration->getId(),
					'integration_name' => $environmentOption['label'],
					'oauth_activated_at' => $oauthActivatedAt,
					'prev_activation_failed' => $prevActivationFailed
				];
			}
		}

		return $integrationsStatuses;
	}

	public function getIntegrationUrl()
	{
		$urlBuilder = $this->context->getUrlBuilder();
		return $urlBuilder->getUrl('admin/integration', ['key' => $urlBuilder->getSecretKey('adminhtml', 'integration', 'index')]);
	}

	private function getStatus($integration)
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

	/**
	 * Determines whether the integration has successfully performed the oauth handshake with Extend.
	 * This is performed after the merchant clicks on Activate on the Magento Integrations page for
	 * the Extend integration.
	 *
	 * @param \Magento\Integration\Model\Integration $integration
	 * @return bool
	 */
	private function isAuthHandshakeComplete($integration): bool
	{
		$integrationStatus = $integration->getStatus();

		return $integrationStatus === 1;
	}

	/**
	 * Determines whether the merchant has completed the identity link handshake with Extend.
	 * This is performed when the identity link pops up to the Merchant Portal and the merchant
	 * successfully connects their Extend account to their Magento instance.
	 *
	 * @param \Magento\Integration\Model\Integration $integration
	 * @return bool
	 */
	private function isIdentityLinkConfirmed($integration): bool
	{
		$clientData = $this->accessTokenBuilder->getExtendOAuthClientData($integration->getId());

		if (isset($clientData['clientId']) && isset($clientData['clientSecret'])
		) {
			return true;
		} else {
			return false;
		}
	}
}
