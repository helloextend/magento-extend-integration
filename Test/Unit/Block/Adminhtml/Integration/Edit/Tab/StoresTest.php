<?php
/*
 * Copyright Extend (c) 2023. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Test\Unit\Block\Adminhtml\Integration\Edit\Tab;

use Extend\Integration\Api\StoreIntegrationRepositoryInterface;
use Extend\Integration\Block\Adminhtml\Integration\Edit\Tab\Stores;
use Extend\Integration\Service\Extend;
use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Container;
use Magento\Framework\Data\FormFactory;
use Magento\Framework\View\Element\TemplateFactory;
use Magento\Integration\Helper\Data;
use Magento\Store\Api\StoreRepositoryInterface;
use PHPUnit\Framework\TestCase;

class StoresTest extends TestCase
{
    /**
     * @var (StoreIntegrationRepositoryInterface&\PHPUnit\Framework\MockObject\MockObject)|\PHPUnit\Framework\MockObject\MockObject
     */
    private $integrationStoresRepository;

    /**
     * @var (\Magento\Framework\Registry&\PHPUnit\Framework\MockObject\MockObject)|\PHPUnit\Framework\MockObject\MockObject
     */
    private $registry;

    /**
     * @var (FormFactory&\PHPUnit\Framework\MockObject\MockObject)|\PHPUnit\Framework\MockObject\MockObject
     */
    private $formFactory;

    /**
     * @var (\Magento\Framework\Data\Form&\PHPUnit\Framework\MockObject\MockObject)|\PHPUnit\Framework\MockObject\MockObject
     */
    private $form;

    /**
     * @var (Extend&\PHPUnit\Framework\MockObject\MockObject)|\PHPUnit\Framework\MockObject\MockObject
     */
    private $extend;

    public function setUp(): void
    {
        $this->context = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->registry = $this->getMockBuilder(\Magento\Framework\Registry::class)
            ->onlyMethods(['registry'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->formFactory = $this->getMockBuilder(FormFactory::class)
            ->onlyMethods(['create'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->storeRepository = $this->getMockBuilder(StoreRepositoryInterface::class)
            ->onlyMethods(['getList'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->integrationStoresRepository = $this->getMockBuilder(StoreIntegrationRepositoryInterface::class)
            ->onlyMethods(['getListByIntegration'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->container = $this->getMockBuilder(Container::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->integrationHelper = $this->getMockBuilder(Data::class)
            ->onlyMethods(['isConfigType'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->tepmplateFactory = $this->getMockBuilder(TemplateFactory::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->extend = $this->getMockBuilder(Extend::class)
            ->onlyMethods(['isEnabled'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->stores = new Stores(
            $this->context,
            $this->registry,
            $this->formFactory,
            $this->storeRepository,
            $this->integrationStoresRepository,
            $this->container,
            $this->integrationHelper,
            $this->tepmplateFactory,
            $this->extend
        );

        $this->form = $this->getMockBuilder(\Magento\Framework\Data\Form::class)
            ->onlyMethods(['addValues', 'addFieldSet'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
    }

    public function testCanShowTabReturnsTrueWithExtendEnabledAndIntegrationIdAndValidEndpoint()
    {
        $this->extend->expects($this->once())->method('isEnabled')->willReturn(true);

        $returnedIntegrationData = [
            'integration_id' => 1,
            'endpoint' => 'https://integ-mage-demo.extend.com/auth/start'
        ];

        $this->registry->expects($this->once())->method('registry')->willReturn($returnedIntegrationData);

        $canShowTab = $this->stores->canShowTab();

        $this->assertTrue($canShowTab);
    }

    public function testCanShowTabReturnsFalseWithExtendDisabled()
    {
        $this->extend->expects($this->once())->method('isEnabled')->willReturn(false);

        $canShowTab = $this->stores->canShowTab();

        $this->assertFalse($canShowTab);
    }

    public function testCanShowTabReturnsFalseWithExtendEnabledButNoIntegrationId()
    {
        $this->extend->expects($this->once())->method('isEnabled')->willReturn(true);

        $returnedIntegrationData = [
            'endpoint' => 'https://integ-mage-demo.extend.com/auth/start'
        ];

        $this->registry->expects($this->once())->method('registry')->willReturn($returnedIntegrationData);

        $canShowTab = $this->stores->canShowTab();

        $this->assertFalse($canShowTab);
    }

    public function testCanShowTabReturnsFalseWithExtendEnabledButNoExtendDomainInEndpoint()
    {
        $this->extend->expects($this->once())->method('isEnabled')->willReturn(true);

        $returnedIntegrationData = [
            'integration_id' => 1,
            'endpoint' => 'https://integ-mage.test.com/'
        ];

        $this->registry->expects($this->once())->method('registry')->willReturn($returnedIntegrationData);

        $canShowTab = $this->stores->canShowTab();

        $this->assertFalse($canShowTab);
    }

    public function testCanShowTabReturnsFalseWithExtendEnabledButNoIntegMageInEndpoint()
    {
        $this->extend->expects($this->once())->method('isEnabled')->willReturn(true);

        $returnedIntegrationData = [
            'integration_id' => 1,
            'endpoint' => 'https://my-test.extend.com/'
        ];

        $this->registry->expects($this->once())->method('registry')->willReturn($returnedIntegrationData);

        $canShowTab = $this->stores->canShowTab();

        $this->assertFalse($canShowTab);
    }
}
