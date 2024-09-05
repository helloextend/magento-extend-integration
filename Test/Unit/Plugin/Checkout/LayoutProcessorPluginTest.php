<?php

namespace Extend\Integration\Test\Unit\Plugin\Checkout;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

use Extend\Integration\Service\Extend as ExtendService;
use Extend\Integration\Plugin\Checkout\LayoutProcessorPlugin;
use Magento\Checkout\Block\Checkout\LayoutProcessorInterface;

class LayoutProcessorPluginTest extends TestCase
{

    /**
     * @var ExtendService | MockObject
     */
    private $extendServiceMock;

  /**
   * @var LayoutProcessorPlugin
   */
    private $testSubject;

  /**
   * @var LayoutProcessorInterface | MockObject
   */
    private $layoutProcessorSubjectMock;

    /**
     * @var array
     */
    private $jsLayoutMock;

    /**
     * @var array
     */
    private $shippingAddressLayout;

    /**
     * @var array
     */
    private $sidebarTotalsLayout;

    protected function setUp(): void
    {
        // create mock constructor arg for the tested class
        $this->extendServiceMock = $this->createStub(ExtendService::class);

        // create an instance of the class to test
        $this->testSubject = new LayoutProcessorPlugin($this->extendServiceMock);

        // create required mock object
        $this->layoutProcessorSubjectMock = $this->createStub(LayoutProcessorInterface::class);

        // create the pre-existing jsLayout array structure for the shipping address component
        $this->jsLayoutMock['components']['checkout']['children']['steps']
        ['children']['shipping-step']['children']['shippingAddress']['children'] = [
          'some-existing-child-component' => true
        ];

        // create the pre-existing jsLayout array structure for the sidebar component
        $this->jsLayoutMock['components']['checkout']['children']['sidebar']['children']['summary']['children']['totals']['children'] = [
          'some-existing-child-component' => true
        ];

        // create short-hand accessors for the child arrays that we're going to verify against
        $this->shippingAddressLayout = &$this->jsLayoutMock['components']['checkout']['children']['steps']
        ['children']['shipping-step']['children']['shippingAddress']['children'];

        $this->sidebarTotalsLayout = &$this->jsLayoutMock['components']['checkout']['children']['sidebar']['children']['summary']['children']['totals']['children'];
    }

    public function testAddsShippingProtectionLayoutsWhenExtendShippingProtectionIsEnabled()
    {
        $this->extendServiceMock->method('isShippingProtectionEnabled')->willReturn(true);
        $this->testSubject->afterProcess($this->layoutProcessorSubjectMock, $this->jsLayoutMock);
        $this->expectShippingProtectionComponentsToBeAddedToJSLayout();
    }

    public function testDoesNothingWhenExtendShippingProtectionIsDisabled()
    {
        $this->extendServiceMock->method('isShippingProtectionEnabled')->willReturn(false);
        $this->testSubject->afterProcess($this->layoutProcessorSubjectMock, $this->jsLayoutMock);
        $this->expectJsLayoutNotToBeChanged();
    }


  /* =================================================================================================== */
  /* ============================== helper methods for validating results ============================== */
  /* =================================================================================================== */

    private function expectExistingChildComponentsToBePresentAndUnaltered()
    {
        $this->assertArrayHasKey('some-existing-child-component', $this->shippingAddressLayout);
        $this->assertEquals(true, $this->shippingAddressLayout['some-existing-child-component']);
        $this->assertArrayHasKey('some-existing-child-component', $this->sidebarTotalsLayout);
        $this->assertEquals(true, $this->sidebarTotalsLayout['some-existing-child-component']);
    }

    private function expectShippingProtectionOfferLayoutConfigToBeCorrect()
    {
        $this->assertArrayHasKey('sp-offer', $this->shippingAddressLayout);
        $this->assertEquals('uiComponent', $this->shippingAddressLayout['sp-offer']['component']);
        $this->assertEquals('shippingAdditional', $this->shippingAddressLayout['sp-offer']['displayArea']);
        $this->assertArrayHasKey('additional_block', $this->shippingAddressLayout['sp-offer']['children']);
        $this->assertEquals('Extend_Integration/js/view/checkout/summary/shipping-protection-offer', $this->shippingAddressLayout['sp-offer']['children']['additional_block']['component']);
    }

    private function expectShippingProtectionSidebarLayoutConfigToBeCorrect()
    {
        $this->assertArrayHasKey('sp-summary-line', $this->sidebarTotalsLayout);
        $this->assertEquals('Extend_Integration/js/view/checkout/summary/shipping-protection', $this->sidebarTotalsLayout['sp-summary-line']['component']);
        $this->assertEquals(24, $this->sidebarTotalsLayout['sp-summary-line']['sortOrder']);
        $this->assertArrayHasKey('title', $this->sidebarTotalsLayout['sp-summary-line']['config']);
        $this->assertEquals('Shipping Protection', $this->sidebarTotalsLayout['sp-summary-line']['config']['title']);
    }

    private function expectJsLayoutNotToBeChanged()
    {
        $this->expectExistingChildComponentsToBePresentAndUnaltered();
        $this->assertArrayNotHasKey('sp-offer', $this->shippingAddressLayout);
        $this->assertArrayNotHasKey('sp-summary-line', $this->sidebarTotalsLayout);
    }

    private function expectShippingProtectionComponentsToBeAddedToJSLayout()
    {
        $this->expectExistingChildComponentsToBePresentAndUnaltered();
        $this->expectShippingProtectionOfferLayoutConfigToBeCorrect();
        $this->expectShippingProtectionSidebarLayoutConfigToBeCorrect();
    }
}
