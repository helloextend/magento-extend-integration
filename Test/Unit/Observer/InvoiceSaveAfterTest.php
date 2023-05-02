<?php
/*
 * Copyright Extend (c) 2022. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Test\Unit\Observer;

use Extend\Integration\Service\Api\Integration;
use Extend\Integration\Service\Api\OrderObserverHandler;
use Extend\Integration\Observer\InvoiceSaveAfter;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\Store;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Invoice;
use Magento\Framework\Event\Observer;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Exception;

class InvoiceSaveAfterTest extends TestCase
{
    /**
     * @var string
     */
    private string $invoiceId;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var InvoiceSaveAfter
     */
    private $import;

    /**
     * @var StoreManagerInterface|MockObject
     */
    private $storeManager;

    /**
     * @var Store|MockObject
     */
    private $store;

    /**
     * @var OrderObserverHandler|MockObject
     */
    private $orderObserverHandler;

    /**
     * @var Integration|MockObject
     */
    private $integration;

    /**
     * @var Order|MockObject
     */
    private $orderMock;

    /**
     * @var Observer|MockObject
     */
    private $observer;

    /**
     * @var Invoice|MockObject
     */
    private $invoice;

    /**
     * @var ObjectManager
     */
    protected $objectManager;

    protected function setUp(): void
    {
        $this->invoiceId = 'test';
        $this->logger = $this->getMockBuilder(LoggerInterface::class)->getMock();
        $this->orderObserverHandler = $this->getMockBuilder(OrderObserverHandler::class)
            ->setMethods(['execute'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->integration = $this->getMockBuilder(Integration::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->store = $this->getMockBuilder(Store::class)
            ->setMethods(['getId'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->storeManager = $this->getMockBuilder(StoreManagerInterface::class)
            ->setMethods(['getStore'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->storeManager
            ->expects($this->any())
            ->method('getStore')
            ->willReturn($this->store);
        $this->orderMock = $this->createMock(Order::class);
        $this->invoice = $this->getMockBuilder(Invoice::class)
            ->setMethods(['getOrder', 'getId'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->invoice
            ->expects($this->any())
            ->method('getOrder')
            ->willReturn($this->orderMock);
        $this->invoice
            ->expects($this->any())
            ->method('getId')
            ->willReturn($this->invoiceId);
        $this->observer = $this->createPartialMock(Observer::class, ['getInvoice']);
        $this->observer
            ->expects($this->any())
            ->method('getInvoice')
            ->willReturn($this->invoice);
        $this->objectManager = new ObjectManager($this);
        $this->import = $this->objectManager->getObject(InvoiceSaveAfter::class, [
            'logger' => $this->logger,
            'orderObserverHandler' => $this->orderObserverHandler,
            'integration' => $this->integration,
            'storeManager' => $this->storeManager,
        ]);
    }

    public function testExecutesOrdersObserver()
    {
        $this->orderObserverHandler
            ->expects($this->once())
            ->method('execute')
            ->with(
                $this->equalTo([
                    'path' => Integration::EXTEND_INTEGRATION_ENDPOINTS['webhooks_orders_create'],
                    'type' => 'middleware',
                ]),
                $this->equalTo($this->orderMock),
                $this->equalTo(['invoice_id' => $this->invoiceId])
            );
        $this->import->execute($this->observer);
    }

    public function testLogsErrorsToLoggingService()
    {
        $this->orderObserverHandler
            ->expects($this->once())
            ->method('execute')
            ->willThrowException(new Exception());
        $this->logger->expects($this->once())->method('error');
        $this->integration->expects($this->once())->method('logErrorToLoggingService');
        $this->import->execute($this->observer);
    }
}
