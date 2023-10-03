<?php
/*
 * Copyright Extend (c) 2022. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Test\Unit\Observer;

use Extend\Integration\Service\Api\Integration;
use Extend\Integration\Api\Data\ShippingProtectionInterface;
use Extend\Integration\Api\ShippingProtectionTotalRepositoryInterface;
use Extend\Integration\Service\Extend as ExtendService;
use Extend\Integration\Service\Api\OrderObserverHandler;
use Extend\Integration\Observer\InvoiceSaveAfter;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\Store;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Api\Data\InvoiceExtension;
use Magento\Framework\Event\Observer;
use Magento\Sales\Api\Data\InvoiceExtensionFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Exception;

class InvoiceSaveAfterTest extends TestCase
{
    /**
     * @var Observer|MockObject
     */
    private $observer;

    /**
     * @var Invoice|MockObject
     */
    private $invoice;

    /**
     * @var InvoiceExtension|MockObject
     */
    private $invoiceExtension;

    /**
     * @var ShippingProtectionInterface|MockObject
     */
    private $shippingProtection;

    /**
     * @var Order|MockObject
     */
    private $orderMock;

    /**
     * @var string
     */
    private string $invoiceId = 'test';

    /**
     * @var Store|MockObject
     */
    private $store;

    /**
     * @var LoggerInterface|MockObject
     */
    private $logger;

    /**
     * @var ExtendService|MockObject
     */
    private $extendService;

    /**
     * @var Integration|MockObject
     */
    private $integration;

    /**
     * @var StoreManagerInterface|MockObject
     */
    private $storeManager;

    /**
     * @var InvoiceExtensionFactory|MockObject
     */
    private $invoiceExtensionFactory;

    /**
     * @var ShippingProtectionTotalRepositoryInterface|MockObject
     */
    private $shippingProtectionTotalRepository;

    /**
     * @var OrderObserverHandler|MockObject
     */
    private $orderObserverHandler;

    /**
     * @var InvoiceSaveAfter
     */
    private $import;

    protected function setUp(): void
    {
        $this->orderMock = $this->createMock(Order::class);
        $this->invoice = $this->createConfiguredMock(Invoice::class, [
            'getOrder' => $this->orderMock,
            'getId' => $this->invoiceId,
        ]);
        $this->observer = $this->getMockBuilder(Observer::class)
            ->addMethods(['getInvoice'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->observer
            ->method('getInvoice')
            ->willReturn($this->invoice);
        $this->shippingProtection = $this->createMock(ShippingProtectionInterface::class);
        $this->invoiceExtension = $this->getMockBuilder(InvoiceExtension::class)
            ->onlyMethods(['getShippingProtection'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->store = $this->createMock(Store::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->extendService = $this->createMock(ExtendService::class);
        $this->integration = $this->createMock(Integration::class);
        $this->storeManager = $this->createConfiguredMock(StoreManagerInterface::class, [
            'getStore' => $this->store
        ]);
        $this->invoiceExtensionFactory = $this->createMock(InvoiceExtensionFactory::class);
        $this->shippingProtectionTotalRepository = $this->createMock(ShippingProtectionTotalRepositoryInterface::class);
        $this->orderObserverHandler = $this->createMock(OrderObserverHandler::class);
        $this->import = new InvoiceSaveAfter(
            $this->logger,
            $this->extendService,
            $this->integration,
            $this->storeManager,
            $this->invoiceExtensionFactory,
            $this->shippingProtectionTotalRepository,
            $this->orderObserverHandler
        );
    }

    public function testPersistsShippingProtectionExtensionAttributeAndExecutesOrdersObserverWhenExtendIsEnabledAndExtensionAttributesHaveShippingProtection()
    {
        $this->extendService
            ->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);
        $this->observer
            ->expects($this->once())
            ->method('getInvoice');
        $this->invoice
            ->expects($this->once())
            ->method('getExtensionAttributes')
            ->willReturn($this->invoiceExtension);
        $this->invoiceExtension
            ->expects($this->once())
            ->method('getShippingProtection')
            ->willReturn($this->shippingProtection);
        $this->invoice
            ->expects($this->once())
            ->method('getOrder');
        $this->invoice
            ->expects($this->once())
            ->method('getId');
        $this->orderObserverHandler
            ->expects($this->once())
            ->method('execute')
            ->with(
                [
                    'path' => Integration::EXTEND_INTEGRATION_ENDPOINTS['webhooks_orders_create'],
                    'type' => 'middleware',
                ],
                $this->orderMock,
                ['invoice_id' => $this->invoiceId]
            );
        $this->import->execute($this->observer);
    }

    public function testCreatesExtensionAttributesAndPersistsShippingProtectionExtensionAttributeAndExecutesOrdersObserverWhenExtendIsEnabledWhenInvoiceDoesNotHaveExtensionAttributesAndExtensionAttributesHaveShippingProtection()
    {
        $this->extendService
            ->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);
        $this->observer
            ->expects($this->once())
            ->method('getInvoice');
        $this->invoice
            ->expects($this->once())
            ->method('getExtensionAttributes')
            ->willReturn(null);
        $this->invoiceExtensionFactory
            ->expects($this->once())
            ->method('create')
            ->willReturn($this->invoiceExtension);
        $this->invoiceExtension
            ->expects($this->once())
            ->method('getShippingProtection')
            ->willReturn($this->shippingProtection);
        $this->invoice
            ->expects($this->once())
            ->method('getOrder');
        $this->invoice
            ->expects($this->once())
            ->method('getId');
        $this->orderObserverHandler
            ->expects($this->once())
            ->method('execute')
            ->with(
                [
                    'path' => Integration::EXTEND_INTEGRATION_ENDPOINTS['webhooks_orders_create'],
                    'type' => 'middleware',
                ],
                $this->orderMock,
                ['invoice_id' => $this->invoiceId]
            );
        $this->import->execute($this->observer);
    }

    public function testDoesNotPersistsShippingProtectionExtensionAttributeAndExecutesOrdersObserverWhenExtendIsEnabledAndExtensionAttributesDoNotHaveShippingProtection()
    {
        $this->extendService
            ->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);
        $this->observer
            ->expects($this->once())
            ->method('getInvoice');
        $this->invoice
            ->expects($this->once())
            ->method('getExtensionAttributes')
            ->willReturn($this->invoiceExtension);
        $this->invoiceExtension
            ->expects($this->once())
            ->method('getShippingProtection')
            ->willReturn(null);
        $this->invoice
            ->expects($this->once())
            ->method('getOrder');
        $this->invoice
            ->expects($this->once())
            ->method('getId');
        $this->orderObserverHandler
            ->expects($this->once())
            ->method('execute')
            ->with(
                [
                    'path' => Integration::EXTEND_INTEGRATION_ENDPOINTS['webhooks_orders_create'],
                    'type' => 'middleware',
                ],
                $this->equalTo($this->orderMock),
                ['invoice_id' => $this->invoiceId]
            );
        $this->import->execute($this->observer);
    }

    public function testSkipsExecutionIfExtendIsNotEnabled()
    {
        $this->extendService
            ->expects($this->once())
            ->method('isEnabled')
            ->willReturn(false);
        $this->orderObserverHandler
            ->expects($this->never())
            ->method('execute');
        $this->import->execute($this->observer);
    }

    public function testLogsErrorsToLoggingService()
    {
        $this->extendService
            ->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);
        $this->observer
            ->expects($this->once())
            ->method('getInvoice');
        $this->invoice
            ->expects($this->once())
            ->method('getExtensionAttributes')
            ->willReturn($this->invoiceExtension);
        $this->invoiceExtension
            ->expects($this->once())
            ->method('getShippingProtection')
            ->willReturn(null);
        $this->invoice
            ->expects($this->once())
            ->method('getOrder');
        $this->invoice
            ->expects($this->once())
            ->method('getId');
        $this->orderObserverHandler
            ->expects($this->once())
            ->method('execute')
            ->willThrowException(new Exception());
        $this->logger
            ->expects($this->once())
            ->method('error');
        $this->integration
            ->expects($this->once())
            ->method('logErrorToLoggingService');
        $this->storeManager
            ->expects($this->once())
            ->method('getStore');
        $this->import->execute($this->observer);
    }
}
