<?php
/*
 * Copyright Extend (c) 2026. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Test\Unit\Plugin\Model\Convert;

use Extend\Integration\Api\Data\ShippingProtectionTotalInterface;
use Extend\Integration\Api\ShippingProtectionTotalRepositoryInterface;
use Extend\Integration\Model\ShippingProtectionTotal;
use Extend\Integration\Model\ShippingProtectionTotalRepository;
use Extend\Integration\Model\ShippingProtection;
use Extend\Integration\Model\ShippingProtectionFactory;
use Extend\Integration\Plugin\Model\Convert\OrderPlugin;
use Extend\Integration\Service\Extend;
use Magento\Framework\App\Request\Http;
use Magento\Framework\DataObject\Copy;
use Magento\Sales\Api\Data\InvoiceExtensionFactory;
use Magento\Sales\Api\Data\InvoiceExtensionInterface;
use Magento\Sales\Api\Data\OrderExtensionFactory;
use Magento\Sales\Api\Data\OrderExtensionInterface;
use Magento\Sales\Api\Data\CreditmemoExtensionFactory;
use Magento\Sales\Model\Convert\Order as ConvertOrder;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Order\Creditmemo;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class OrderPluginTest extends TestCase
{
    /** @var InvoiceExtensionFactory|MockObject */
    private $invoiceExtensionFactory;

    /** @var OrderExtensionFactory|MockObject */
    private $orderExtensionFactory;

    /** @var Copy|MockObject */
    private $objectCopyService;

    /** @var ShippingProtectionTotalRepository|MockObject */
    private $shippingProtectionTotalRepository;

    /** @var Http|MockObject */
    private $http;

    /** @var ShippingProtectionFactory|MockObject */
    private $shippingProtectionFactory;

    /** @var CreditmemoExtensionFactory|MockObject */
    private $creditmemoExtensionFactory;

    /** @var Extend|MockObject */
    private $extend;

    /** @var LoggerInterface|MockObject */
    private $logger;

    /** @var ConvertOrder|MockObject */
    private $subject;

    /** @var Order|MockObject */
    private $order;

    /** @var Invoice|MockObject */
    private $invoice;

    /** @var Creditmemo|MockObject */
    private $creditmemo;

    /** @var OrderPlugin */
    private $plugin;

    protected function setUp(): void
    {
        $this->invoiceExtensionFactory = $this->createMock(InvoiceExtensionFactory::class);
        $this->orderExtensionFactory = $this->createMock(OrderExtensionFactory::class);
        $this->objectCopyService = $this->createMock(Copy::class);
        $this->shippingProtectionTotalRepository = $this->createMock(ShippingProtectionTotalRepository::class);
        $this->http = $this->createMock(Http::class);
        $this->shippingProtectionFactory = $this->createMock(ShippingProtectionFactory::class);
        $this->creditmemoExtensionFactory = $this->createMock(CreditmemoExtensionFactory::class);
        $this->extend = $this->createMock(Extend::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->subject = $this->createMock(ConvertOrder::class);

        $this->order = $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getExtensionAttributes', 'setExtensionAttributes', 'getEntityId', 'getQuoteId'])
            ->getMock();

        $this->invoice = $this->getMockBuilder(Invoice::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getExtensionAttributes', 'setExtensionAttributes'])
            ->getMock();

        $this->creditmemo = $this->getMockBuilder(Creditmemo::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getExtensionAttributes', 'setExtensionAttributes', 'setData'])
            ->getMock();

        $this->plugin = new OrderPlugin(
            $this->invoiceExtensionFactory,
            $this->orderExtensionFactory,
            $this->objectCopyService,
            $this->shippingProtectionTotalRepository,
            $this->http,
            $this->shippingProtectionFactory,
            $this->creditmemoExtensionFactory,
            $this->extend,
            $this->logger
        );
    }

    /* ======================================== afterToInvoice ======================================== */

    public function testAfterToInvoiceWhenExtendDisabledReturnsResultUnmodified()
    {
        $this->extend->method('isEnabled')->willReturn(false);
        $this->shippingProtectionTotalRepository
            ->expects($this->never())
            ->method('get');

        $result = $this->plugin->afterToInvoice($this->subject, $this->invoice, $this->order);
        $this->assertSame($this->invoice, $result);
    }

    public function testAfterToInvoicePersistedOrderWithOrderSpUsesOrderRecord()
    {
        $this->extend->method('isEnabled')->willReturn(true);

        $orderExtAttrs = $this->createMock(OrderExtensionInterface::class);
        $orderExtAttrs->method('getShippingProtection')->willReturn(null, $this->createMock(ShippingProtection::class));
        $this->order->method('getExtensionAttributes')->willReturn($orderExtAttrs);
        $this->order->method('getEntityId')->willReturn(123);
        $this->order->method('getQuoteId')->willReturn(456);

        $spTotalData = $this->createSpTotalDataMock();

        // Should look up by ORDER entity type and find a record
        $this->shippingProtectionTotalRepository
            ->expects($this->once())
            ->method('get')
            ->with(123, ShippingProtectionTotalInterface::ORDER_ENTITY_TYPE_ID)
            ->willReturn($spTotalData);

        $shippingProtection = $this->createMock(ShippingProtection::class);
        $this->shippingProtectionFactory->method('create')->willReturn($shippingProtection);

        $invoiceExtAttrs = $this->createMock(InvoiceExtensionInterface::class);
        $this->invoice->method('getExtensionAttributes')->willReturn($invoiceExtAttrs);

        $this->objectCopyService
            ->expects($this->once())
            ->method('copyFieldsetToTarget')
            ->with(
                'extend_integration_sales_convert_order',
                'to_invoice',
                $this->order,
                $this->invoice
            );

        $result = $this->plugin->afterToInvoice($this->subject, $this->invoice, $this->order);
        $this->assertSame($this->invoice, $result);
    }

    /**
     * THE BUG FIX: A persisted order (entityId != null) with no ORDER-type SP record
     * must NOT fall back to the QUOTE entity type. Before the fix, stale QUOTE records
     * from the Guidance module would be picked up, injecting phantom SP onto the invoice.
     */
    public function testAfterToInvoicePersistedOrderWithoutOrderSpDoesNotFallBackToQuote()
    {
        $this->extend->method('isEnabled')->willReturn(true);

        $orderExtAttrs = $this->createMock(OrderExtensionInterface::class);
        $orderExtAttrs->method('getShippingProtection')->willReturn(null);
        $this->order->method('getExtensionAttributes')->willReturn($orderExtAttrs);
        $this->order->method('getEntityId')->willReturn(123);
        $this->order->method('getQuoteId')->willReturn(456);

        // ORDER lookup returns empty model (getData() returns [] in real Magento models)
        $emptySpData = $this->createMock(ShippingProtectionTotal::class);
        $emptySpData->method('getData')->willReturn([]);

        $this->shippingProtectionTotalRepository
            ->expects($this->once())
            ->method('get')
            ->with(123, ShippingProtectionTotalInterface::ORDER_ENTITY_TYPE_ID)
            ->willReturn($emptySpData);

        // QUOTE lookup should NEVER be called for a persisted order
        // (only one call to get() expected above — if a second call happened with QUOTE type, the test fails)

        $this->shippingProtectionFactory->expects($this->never())->method('create');

        $result = $this->plugin->afterToInvoice($this->subject, $this->invoice, $this->order);
        $this->assertSame($this->invoice, $result);
    }

    public function testAfterToInvoiceUnpersistedOrderFallsBackToQuote()
    {
        $this->extend->method('isEnabled')->willReturn(true);

        $orderExtAttrs = $this->createMock(OrderExtensionInterface::class);
        $orderExtAttrs->method('getShippingProtection')->willReturn(null, $this->createMock(ShippingProtection::class));
        $this->order->method('getExtensionAttributes')->willReturn($orderExtAttrs);
        $this->order->method('getEntityId')->willReturn(null);
        $this->order->method('getQuoteId')->willReturn(456);

        $spTotalData = $this->createSpTotalDataMock();

        // ORDER lookup is skipped (entity ID is null), QUOTE lookup returns data
        $this->shippingProtectionTotalRepository
            ->expects($this->once())
            ->method('get')
            ->with(456, ShippingProtectionTotalInterface::QUOTE_ENTITY_TYPE_ID)
            ->willReturn($spTotalData);

        $shippingProtection = $this->createMock(ShippingProtection::class);
        $this->shippingProtectionFactory->method('create')->willReturn($shippingProtection);

        $invoiceExtAttrs = $this->createMock(InvoiceExtensionInterface::class);
        $this->invoice->method('getExtensionAttributes')->willReturn($invoiceExtAttrs);

        $this->objectCopyService
            ->expects($this->once())
            ->method('copyFieldsetToTarget')
            ->with(
                'extend_integration_sales_convert_order',
                'to_invoice',
                $this->order,
                $this->invoice
            );

        $this->plugin->afterToInvoice($this->subject, $this->invoice, $this->order);
    }

    /* ======================================== afterToCreditmemo ======================================== */

    public function testAfterToCreditmemoWhenExtendDisabledReturnsResultUnmodified()
    {
        $this->extend->method('isEnabled')->willReturn(false);
        $this->shippingProtectionTotalRepository
            ->expects($this->never())
            ->method('get');

        $result = $this->plugin->afterToCreditmemo($this->subject, $this->creditmemo, $this->order);
        $this->assertSame($this->creditmemo, $result);
    }

    /**
     * Same bug fix validation for creditmemo: persisted order without ORDER SP
     * must not fall back to QUOTE.
     */
    public function testAfterToCreditmemoPersistedOrderWithoutOrderSpDoesNotFallBackToQuote()
    {
        $this->extend->method('isEnabled')->willReturn(true);

        $orderExtAttrs = $this->createMock(OrderExtensionInterface::class);
        $orderExtAttrs->method('getShippingProtection')->willReturn(null);
        $this->order->method('getExtensionAttributes')->willReturn($orderExtAttrs);
        $this->order->method('getEntityId')->willReturn(123);
        $this->order->method('getQuoteId')->willReturn(456);

        // ORDER lookup returns empty model (getData() returns [] in real Magento models)
        $emptySpData = $this->createMock(ShippingProtectionTotal::class);
        $emptySpData->method('getData')->willReturn([]);

        $this->shippingProtectionTotalRepository
            ->expects($this->once())
            ->method('get')
            ->with(123, ShippingProtectionTotalInterface::ORDER_ENTITY_TYPE_ID)
            ->willReturn($emptySpData);

        $this->shippingProtectionFactory->expects($this->never())->method('create');

        $result = $this->plugin->afterToCreditmemo($this->subject, $this->creditmemo, $this->order);
        $this->assertSame($this->creditmemo, $result);
    }

    public function testAfterToCreditmemoUnpersistedOrderFallsBackToQuote()
    {
        $this->extend->method('isEnabled')->willReturn(true);

        $orderExtAttrs = $this->createMock(OrderExtensionInterface::class);
        // Mirrors a real extension attribute: absent until the plugin builds and sets it,
        // present on every read after. The set value must be a real ShippingProtection so the
        // plugin's array access on it resolves.
        $realSp = $this->createShippingProtectionWithData('EXTEND');
        $spCallCount = 0;
        $orderExtAttrs->method('getShippingProtection')
            ->willReturnCallback(function () use (&$spCallCount, $realSp) {
                return $spCallCount++ === 0 ? null : $realSp;
            });
        $this->order->method('getExtensionAttributes')->willReturn($orderExtAttrs);
        $this->order->method('getEntityId')->willReturn(null);
        $this->order->method('getQuoteId')->willReturn(456);

        $spTotalData = $this->createSpTotalDataMock();

        $this->shippingProtectionTotalRepository
            ->expects($this->once())
            ->method('get')
            ->with(456, ShippingProtectionTotalInterface::QUOTE_ENTITY_TYPE_ID)
            ->willReturn($spTotalData);

        $shippingProtection = $this->createMock(ShippingProtection::class);
        $this->shippingProtectionFactory->method('create')->willReturn($shippingProtection);

        $this->http->method('getPost')->willReturn(null);

        $creditmemoExtAttrs = $this->createMock(\Magento\Sales\Api\Data\CreditmemoExtensionInterface::class);
        $this->creditmemo->method('getExtensionAttributes')->willReturn($creditmemoExtAttrs);

        $this->objectCopyService
            ->expects($this->once())
            ->method('copyFieldsetToTarget')
            ->with(
                'extend_integration_sales_convert_order',
                'to_cm',
                $this->order,
                $this->creditmemo
            );

        $result = $this->plugin->afterToCreditmemo($this->subject, $this->creditmemo, $this->order);
        $this->assertSame($this->creditmemo, $result);
    }

    /**
     * POST creditmemo branch: when the admin submits the credit memo form with a
     * shipping_protection value, the plugin uses that POST value as the SP price
     * and reads base_currency / currency from the existing SP via array access.
     */
    public function testAfterToCreditmemoPostCreditmemoBranchUsesPostPrice()
    {
        $this->extend->method('isEnabled')->willReturn(true);

        $orderSp = $this->createShippingProtectionWithData('EXTEND');
        $orderExtAttrs = $this->createMock(OrderExtensionInterface::class);
        $orderExtAttrs->method('getShippingProtection')->willReturn($orderSp);
        $this->order->method('getExtensionAttributes')->willReturn($orderExtAttrs);

        $this->http->method('getPost')
            ->with('creditmemo')
            ->willReturn(['shipping_protection' => '5.00']);

        $newSp = $this->createMock(ShippingProtection::class);
        $newSp->expects($this->once())->method('setBase')->with('5.00');
        $newSp->expects($this->once())->method('setBaseCurrency')->with('USD');
        $newSp->expects($this->once())->method('setPrice')->with('5.00');
        $newSp->expects($this->once())->method('setCurrency')->with('USD');
        $newSp->expects($this->once())->method('setSpQuoteId')->with('sp-quote-123');
        $newSp->expects($this->once())->method('setShippingProtectionTax')->with(0.0);
        $newSp->expects($this->once())->method('setOfferType')->with('EXTEND');
        $this->shippingProtectionFactory->method('create')->willReturn($newSp);

        $creditmemoExtAttrs = $this->createMock(\Magento\Sales\Api\Data\CreditmemoExtensionInterface::class);
        $creditmemoExtAttrs->expects($this->once())->method('setShippingProtection')->with($newSp);
        $this->creditmemo->method('getExtensionAttributes')->willReturn($creditmemoExtAttrs);

        $setDataCalls = [];
        $this->creditmemo->method('setData')
            ->willReturnCallback(function ($key, $value = null) use (&$setDataCalls) {
                $setDataCalls[$key] = $value;
                return $this->creditmemo;
            });

        // POST branch returns early, so copyFieldsetToTarget must not be called
        $this->objectCopyService->expects($this->never())->method('copyFieldsetToTarget');

        $result = $this->plugin->afterToCreditmemo($this->subject, $this->creditmemo, $this->order);
        $this->assertSame($this->creditmemo, $result);
        $this->assertArrayHasKey('original_shipping_protection', $setDataCalls);
        $this->assertSame(2.46, $setDataCalls['original_shipping_protection']);
        $this->assertArrayNotHasKey('spg_sp_removed_from_credit_memo', $setDataCalls);
    }

    /**
     * POST creditmemo branch, SPG case: if the offer type is SAFE_PACKAGE and the POST value
     * is an empty string (admin cleared the input), the plugin sets price to 0 and marks the
     * credit memo as having SP removed.
     */
    public function testAfterToCreditmemoSpgWithEmptyPostMarksRemoved()
    {
        $this->extend->method('isEnabled')->willReturn(true);

        $orderSp = $this->createShippingProtectionWithData(
            ShippingProtectionTotalRepositoryInterface::OFFER_TYPE_SAFE_PACKAGE
        );
        $orderExtAttrs = $this->createMock(OrderExtensionInterface::class);
        $orderExtAttrs->method('getShippingProtection')->willReturn($orderSp);
        $this->order->method('getExtensionAttributes')->willReturn($orderExtAttrs);

        $this->http->method('getPost')
            ->with('creditmemo')
            ->willReturn(['shipping_protection' => '']);

        $newSp = $this->createMock(ShippingProtection::class);
        $newSp->expects($this->once())->method('setBase')->with(0);
        $newSp->expects($this->once())->method('setPrice')->with(0);
        $this->shippingProtectionFactory->method('create')->willReturn($newSp);

        $creditmemoExtAttrs = $this->createMock(\Magento\Sales\Api\Data\CreditmemoExtensionInterface::class);
        $this->creditmemo->method('getExtensionAttributes')->willReturn($creditmemoExtAttrs);

        $setDataCalls = [];
        $this->creditmemo->method('setData')
            ->willReturnCallback(function ($key, $value = null) use (&$setDataCalls) {
                $setDataCalls[$key] = $value;
                return $this->creditmemo;
            });

        $this->objectCopyService->expects($this->never())->method('copyFieldsetToTarget');

        $result = $this->plugin->afterToCreditmemo($this->subject, $this->creditmemo, $this->order);
        $this->assertSame($this->creditmemo, $result);
        $this->assertArrayHasKey('spg_sp_removed_from_credit_memo', $setDataCalls);
        $this->assertTrue($setDataCalls['spg_sp_removed_from_credit_memo']);
    }

    /* ======================================== helpers ======================================== */

    /**
     * Build a real ShippingProtection instance (bypassing the AbstractModel constructor)
     * so that array access (['base'], ['base_currency'], ['currency']) and method access
     * (getOfferType, getSpQuoteId, getShippingProtectionTax) both resolve against the same
     * backing data. A bare createMock() does not satisfy the array-access reads in the POST
     * creditmemo branch.
     *
     * @return ShippingProtection
     */
    private function createShippingProtectionWithData(string $offerType): ShippingProtection
    {
        $sp = (new \ReflectionClass(ShippingProtection::class))->newInstanceWithoutConstructor();
        $sp->setData('base', 2.46);
        $sp->setData('base_currency', 'USD');
        $sp->setData('price', 2.46);
        $sp->setData('currency', 'USD');
        $sp->setData('sp_quote_id', 'sp-quote-123');
        $sp->setData('shipping_protection_tax', 0.0);
        $sp->setData('offer_type', $offerType);
        return $sp;
    }

    /**
     * @return ShippingProtectionTotal|MockObject
     */
    private function createSpTotalDataMock(): ShippingProtectionTotal
    {
        $spTotalData = $this->createMock(ShippingProtectionTotal::class);
        $spTotalData->method('getData')->willReturn(['some_data' => true]);
        $spTotalData->method('getShippingProtectionBasePrice')->willReturn(2.46);
        $spTotalData->method('getShippingProtectionBaseCurrency')->willReturn('USD');
        $spTotalData->method('getShippingProtectionPrice')->willReturn(2.46);
        $spTotalData->method('getShippingProtectionCurrency')->willReturn('USD');
        $spTotalData->method('getSpQuoteId')->willReturn('sp-quote-123');
        $spTotalData->method('getShippingProtectionTax')->willReturn(0.0);
        $spTotalData->method('getOfferType')->willReturn(ShippingProtectionTotalRepositoryInterface::OFFER_TYPE_SAFE_PACKAGE);
        return $spTotalData;
    }
}
