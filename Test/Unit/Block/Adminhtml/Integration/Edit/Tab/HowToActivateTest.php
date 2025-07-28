<?php
/*
 * Copyright Extend (c) 2023. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Test\Unit\Block\Adminhtml\Integration\Edit\Tab;

use Extend\Integration\Block\Adminhtml\Integration\Edit\Tab\HowToActivate;
use Extend\Integration\Model\Config\Source\Environment;
use Extend\Integration\Service\Api\AccessTokenBuilder;
use Magento\Integration\Api\IntegrationServiceInterface;
use PHPUnit\Framework\TestCase;

class HowToActivateTest extends TestCase
{
    /**
     * @var HowToActivate
     */
    private HowToActivate $howToActivate;

  /**
   * @var \Magento\Framework\Url&\PHPUnit\Framework\MockObject\Stub
   */
    private $urlBuilder;

    /**
     * @var \Magento\Backend\Block\Template\Context|\PHPUnit\Framework\MockObject\Stub
     */
    private $context;

    /**
     * @var Environment|\PHPUnit\Framework\MockObject\MockObject
     */
    private $environment;

    /**
     * @var IntegrationServiceInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $integrationService;

    /**
     * @var array
     */
    private array $data;

    /**
     * @var array|array[]
     */
    private array $environmentOptionArrayData;

    /**
     * @var array|array[]
     */
    private array $activationStatusData;

    /**
     * @var \Magento\Integration\Model\Integration|\PHPUnit\Framework\MockObject\MockObject
     */
    private $integrationModel1;

    /**
     * @var \Magento\Integration\Model\Integration|\PHPUnit\Framework\MockObject\MockObject
     */
    private $integrationModel2;
    /**
     * @var AccessTokenBuilder|(AccessTokenBuilder&object&\PHPUnit\Framework\MockObject\MockObject)|(AccessTokenBuilder&\PHPUnit\Framework\MockObject\MockObject)|(object&\PHPUnit\Framework\MockObject\MockObject)|\PHPUnit\Framework\MockObject\MockObject
     */
    private $accessTokenBuilder;

    /**
     * @var string
     */
    private $callbackUrl = 'https://magento-instance.com/admin';

    /**
     * @var array
     */
    private $integration1OauthClientData = [
    'clientId' => '89rjh89tyrhug3897y',
    'clientSecret' => 'fbhn39ry34rhfsdfi98',
    ];

    /**
     * @var array
     */
    private $integration2OauthClientData = [
    'clientId' => null,
    'clientSecret' => null,
    ];

    public function setUp(): void
    {
        $this->urlBuilder = $this->createMock(\Magento\Framework\Url::class);
        $this->urlBuilder->method('getCurrentUrl')->willReturn($this->callbackUrl);
        $this->context = $this->createStub(\Magento\Backend\Block\Template\Context::class);
        $this->context->method('getUrlBuilder')->willReturn($this->urlBuilder);
        $this->environment = $this->createMock(Environment::class);
        $this->integrationService = $this->createMock(IntegrationServiceInterface::class);
        $this->accessTokenBuilder = $this->createMock(AccessTokenBuilder::class);
        $this->data = [];

        $this->howToActivate = new HowToActivate(
            $this->context,
            $this->environment,
            $this->integrationService,
            $this->accessTokenBuilder,
            $this->data
        );

        $this->environmentOptionArrayData  = [
            ['value' => 1, 'label' => 'Extend Integration - Prod'],
            ['value' => 2, 'label' => 'Extend Integration - Demo'],
        ];

        $this->integrationModel1 = $this->getMockBuilder(\Magento\Integration\Model\Integration::class)
        ->addMethods(
            ['getIdentityLinkUrl', 'getConsumerKey']
        )
         ->onlyMethods(['getId', 'getStatus'])
         ->disableOriginalConstructor()
         ->getMock();
        $this->integrationModel1->method('getId')->willReturn(1);
        $this->integrationModel1->method('getStatus')->willReturn(1);
        $this->integrationModel1->method('getIdentityLinkUrl')->willReturn('https://merchants.extend.com/magento');
        $this->integrationModel1->method('getConsumerKey')->willReturn('prodConsumerKey');

        $this->integrationModel2 = $this->getMockBuilder(\Magento\Integration\Model\Integration::class)
         ->addMethods(
             ['getIdentityLinkUrl', 'getConsumerKey']
         )
         ->onlyMethods(['getId', 'getStatus'])
         ->disableOriginalConstructor()
         ->getMock();
        $this->integrationModel2->method('getId')->willReturn(2);
        $this->integrationModel2->method('getStatus')->willReturn(0);
        $this->integrationModel2->method('getIdentityLinkUrl')->willReturn('https://merchants.demo.extend.com/magento');
        $this->integrationModel2->method('getConsumerKey')->willReturn('demoConsumerKey');

        $this->activationStatusData = [
            [
        'integration_id' => 1,
        'current_step' => 'complete',
        'identity_link_url' => 'https://merchants.extend.com/magento?oauth_consumer_key=prodConsumerKey&success_call_back='.$this->callbackUrl,
        'integration_name' => 'Extend Integration - Prod',
        'oauth_activated_at' => null,
        'prev_activation_failed' => false,
            ],
            [
            'integration_id' => 2,
            'current_step' => 'activation_required',
            'identity_link_url' => 'https://merchants.demo.extend.com/magento?oauth_consumer_key=demoConsumerKey&success_call_back='.$this->callbackUrl,
            'integration_name' => 'Extend Integration - Demo',
            'oauth_activated_at' => null,
            'prev_activation_failed' => false,
            ],
        ];
    }

    public function testGetIntegrationsIfNoIntegrations()
    {
        $this->environment->expects($this->once())->method('toOptionArray')->willReturn([]);
        $this->assertEquals($this->howToActivate->getIntegrations(), []);
    }

    public function testGetIntegrations()
    {
        $this->environment->expects($this->once())->method('toOptionArray')->willReturn($this->environmentOptionArrayData);

        $this->integrationService->expects($this->exactly(2))->method('get')
            ->willReturnOnConsecutiveCalls($this->integrationModel1, $this->integrationModel2);

        $this->accessTokenBuilder->expects(($this->exactly(2)))
         ->method('getExtendOAuthClientData')
         ->willReturnOnConsecutiveCalls(
             $this->integration1OauthClientData,
             $this->integration1OauthClientData,
             $this->integration2OauthClientData,
             $this->integration2OauthClientData
         );

        $this->assertEquals($this->howToActivate->getIntegrations(), $this->activationStatusData);
    }
}
