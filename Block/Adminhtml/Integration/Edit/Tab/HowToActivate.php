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
	private Environment $environment;
	private IntegrationServiceInterface $integrationService;
	private Context $context;
	private AccessTokenBuilder $accessTokenBuilder;

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
		$integrationsStatuses = [];
		$environmentOptions = $this->environment->toOptionArray();
		if ($environmentOptions) {
			foreach ($environmentOptions as $environmentOption) {
				$integration = $this->integrationService->get($environmentOption['value']);
				$integrationsStatuses[] = [
					'integration_id' => $integration->getId(),
					'activation_status' => $this->getStatus($integration)
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
}
