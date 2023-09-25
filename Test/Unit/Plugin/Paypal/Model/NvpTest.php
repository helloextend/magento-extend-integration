<?php
/*
 * Copyright Extend (c) 2023. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

namespace Extend\Integration\Test\Unit\Plugin\Paypal\Model;

use Extend\Integration\Api\Data\ShippingProtectionInterface;
use Extend\Integration\Api\ShippingProtectionTotalRepositoryInterface;
use Extend\Integration\Plugin\Paypal\Model\Nvp;
use Extend\Integration\Service\Extend;
use Magento\Checkout\Model\Session;
use Magento\Framework\DataObject;
use Magento\Paypal\Model\Api\AbstractApi;
use Magento\Quote\Api\Data\CartExtensionFactory;
use Magento\Quote\Api\Data\CartExtensionInterface;
use Magento\Quote\Model\Quote;
use PHPUnit\Framework\TestCase;

class NvpTest extends TestCase
{

    /**
     * @var Nvp
     */
    private $nvp;

    /**
     * @var ShippingProtectionTotalRepositoryInterface|(ShippingProtectionTotalRepositoryInterface&object&\PHPUnit\Framework\MockObject\MockObject)|(ShippingProtectionTotalRepositoryInterface&\PHPUnit\Framework\MockObject\MockObject)|(object&\PHPUnit\Framework\MockObject\MockObject)|\PHPUnit\Framework\MockObject\MockObject
     */
    private $shippingProtectionTotalRepository;

    /**
     * @var CartExtensionFactory|(CartExtensionFactory&object&\PHPUnit\Framework\MockObject\MockObject)|(CartExtensionFactory&\PHPUnit\Framework\MockObject\MockObject)|(object&\PHPUnit\Framework\MockObject\MockObject)|\PHPUnit\Framework\MockObject\MockObject
     */
    private $cartExtensionFactory;

    /**
     * @var Extend|(Extend&object&\PHPUnit\Framework\MockObject\MockObject)|(Extend&\PHPUnit\Framework\MockObject\MockObject)|(object&\PHPUnit\Framework\MockObject\MockObject)|\PHPUnit\Framework\MockObject\MockObject
     */
    private $extend;

    /**
     * @var Session|(Session&object&\PHPUnit\Framework\MockObject\MockObject)|(Session&\PHPUnit\Framework\MockObject\MockObject)|(object&\PHPUnit\Framework\MockObject\MockObject)|\PHPUnit\Framework\MockObject\MockObject
     */
    private $_checkoutSession;

    /**
     * @var AbstractApi|(AbstractApi&object&\PHPUnit\Framework\MockObject\MockObject)|(AbstractApi&\PHPUnit\Framework\MockObject\MockObject)|(object&\PHPUnit\Framework\MockObject\MockObject)|\PHPUnit\Framework\MockObject\MockObject
     */
    private $subject;

    /**
     * @var string
     */
    private string $title;
    /**
     * @var Quote|(Quote&object&\PHPUnit\Framework\MockObject\MockObject)|(Quote&\PHPUnit\Framework\MockObject\MockObject)|(object&\PHPUnit\Framework\MockObject\MockObject)|\PHPUnit\Framework\MockObject\MockObject
     */
    private $badQuote;
    /**
     * @var CartExtensionInterface|(CartExtensionInterface&object&\PHPUnit\Framework\MockObject\MockObject)|(CartExtensionInterface&\PHPUnit\Framework\MockObject\MockObject)|(object&\PHPUnit\Framework\MockObject\MockObject)|\PHPUnit\Framework\MockObject\MockObject
     */
    private $quoteExtensionAttributes;
    /**
     * @var ShippingProtectionInterface|(ShippingProtectionInterface&object&\PHPUnit\Framework\MockObject\MockObject)|(ShippingProtectionInterface&\PHPUnit\Framework\MockObject\MockObject)|(object&\PHPUnit\Framework\MockObject\MockObject)|\PHPUnit\Framework\MockObject\MockObject
     */
    private $shippingProtection;

    protected function setUp(): void
    {
        $this->shippingProtectionTotalRepository = $this->createMock(ShippingProtectionTotalRepositoryInterface::class);
        $this->cartExtensionFactory = $this->createMock(CartExtensionFactory::class);
        $this->extend = $this->createMock(Extend::class);
        $this->_checkoutSession = $this->createMock(Session::class);
        $this->abstractApi = $this->createMock(AbstractApi::class);

        $this->nvp = new Nvp(
            $this->shippingProtectionTotalRepository,
            $this->cartExtensionFactory,
            $this->extend,
            $this->_checkoutSession
        );

        $this->subject = $this->createMock(AbstractApi::class);
        $this->title = "This is a title.";
        $this->request = [
            'AMT' => 100.00,
            'ITEMAMT' => 88.00,
            'SHIPPINGAMT' => 5.00,
            'TAXAMT' => 7.00
        ];

        $this->quoteExtensionAttributes = $this->createMock(CartExtensionInterface::class);
        $this->shippingProtection = $this->createConfiguredMock(ShippingProtectionInterface::class, ['getBase' => 1.2]);

        $this->badQuote = $this->createConfiguredMock(Quote::class, ['getStoreId' => 1]);
        $this->quoteWithoutSp = $this->createConfiguredMock(Quote::class, ['getId' => 1]);
        $this->quote = $this->createConfiguredMock(Quote::class, ['getId' => 1]);
    }

    public function testBeforeCallWithExtendIsNotEnabled()
    {
        $this->extend->expects($this->once())->method('isEnabled')->willReturn(false);
        $this->assertEquals(
            $this->nvp->beforeCall($this->subject, $this->title, $this->request),
            [$this->title, $this->request]
        );
    }

    public function testBeforeCallWithNoQuote()
    {
        $this->extend->expects($this->once())->method('isEnabled')->willReturn(true);
        $this->subject->expects($this->once())->method('getData')->with('quote')->willReturn(null);
        $this->_checkoutSession->expects($this->once())->method('getQuote')->willReturn(null);
        $this->assertEquals(
            $this->nvp->beforeCall($this->subject, $this->title, $this->request),
            [$this->title, $this->request]
        );
    }

    public function testBeforeCallWithNoQuoteId()
    {
        $this->extend->expects($this->once())->method('isEnabled')->willReturn(true);
        $this->subject->expects($this->once())->method('getData')->with('quote')->willReturn(null);
        $this->_checkoutSession->expects($this->once())->method('getQuote')->willReturn($this->badQuote);
        $this->assertEquals(
            $this->nvp->beforeCall($this->subject, $this->title, $this->request),
            [$this->title, $this->request]
        );
    }

    public function testBeforeCallWithNoExtensionAttributes()
    {
        $this->extend->expects($this->once())->method('isEnabled')->willReturn(true);
        $this->subject->expects($this->once())->method('getData')->with('quote')->willReturn($this->quote);
        $this->quote->expects($this->once())->method('getExtensionAttributes')->willReturn(null);
        $this->cartExtensionFactory->expects($this->once())->method('create')->willReturn(null);
        $this->assertEquals(
            $this->nvp->beforeCall($this->subject, $this->title, $this->request),
            [$this->title, $this->request]
        );
    }

    public function testBeforeCallWithNoShippingProtection()
    {
        $this->extend->expects($this->once())->method('isEnabled')->willReturn(true);
        $this->subject->expects($this->once())->method('getData')->with('quote')->willReturn($this->quote);
        $this->quote->expects($this->any())->method('getExtensionAttributes')->willReturn($this->quoteExtensionAttributes);
        $this->quoteExtensionAttributes->expects($this->any())->method('getShippingProtection')->willReturn(null);
        $this->shippingProtectionTotalRepository->expects($this->once())->method('getAndSaturateExtensionAttributes');
        $this->assertEquals(
            $this->nvp->beforeCall($this->subject, $this->title, $this->request),
            [$this->title, $this->request]
        );
    }

    public function testBeforeCallWithQuoteFromSession()
    {
        $this->extend->expects($this->once())->method('isEnabled')->willReturn(true);
        $this->subject->expects($this->once())->method('getData')->with('quote')->willReturn(null);
        $this->_checkoutSession->expects($this->once())->method('getQuote')->willReturn($this->quote);
        $this->quote->expects($this->once())->method('getExtensionAttributes')->willReturn($this->quoteExtensionAttributes);
        $this->quoteExtensionAttributes->expects($this->once())->method('getShippingProtection')->willReturn($this->shippingProtection);
        $this->shippingProtection->expects($this->once())->method('getBase')->willReturn(1.2);
        $this->assertEquals(
            $this->nvp->beforeCall($this->subject, $this->title, $this->request),
            [$this->title, [
                    'AMT' => $this->request['AMT'] + 1.2,
                    'ITEMAMT' => $this->request['ITEMAMT'],
                    'SHIPPINGAMT' => $this->request['SHIPPINGAMT'],
                    'TAXAMT' => $this->request['TAXAMT'],
                    'INSURANCEAMT' => 1.2
                ]
            ]
        );
    }

    public function testBeforeCallWithExtensionAttributesAfterCreate()
    {
        $this->extend->expects($this->once())->method('isEnabled')->willReturn(true);
        $this->subject->expects($this->once())->method('getData')->with('quote')->willReturn($this->quote);
        $this->quote->expects($this->once())->method('getExtensionAttributes')->willReturn(null);
        $this->cartExtensionFactory->expects($this->once())->method('create')->willReturn($this->quoteExtensionAttributes);
        $this->quoteExtensionAttributes->expects($this->once())->method('getShippingProtection')->willReturn($this->shippingProtection);
        $this->shippingProtection->expects($this->once())->method('getBase')->willReturn(1.2);
        $this->assertEquals(
            $this->nvp->beforeCall($this->subject, $this->title, $this->request),
            [$this->title, [
                    'AMT' => $this->request['AMT'] + 1.2,
                    'ITEMAMT' => $this->request['ITEMAMT'],
                    'SHIPPINGAMT' => $this->request['SHIPPINGAMT'],
                    'TAXAMT' => $this->request['TAXAMT'],
                    'INSURANCEAMT' => 1.2
                ]
            ]
        );
    }

    public function testBeforeCallWithShippingProtectionAfterSaturation()
    {
        $this->extend->expects($this->once())->method('isEnabled')->willReturn(true);
        $this->subject->expects($this->once())->method('getData')->with('quote')->willReturn($this->quote);
        $this->quote->expects($this->any())->method('getExtensionAttributes')->willReturn($this->quoteExtensionAttributes);
        $this->shippingProtectionTotalRepository->expects($this->once())->method('getAndSaturateExtensionAttributes');
        $this->quoteExtensionAttributes
            ->expects($this->exactly(2))
            ->method('getShippingProtection')
            ->willReturnOnConsecutiveCalls(null, $this->shippingProtection);
        $this->shippingProtection->expects($this->once())->method('getBase')->willReturn(1.2);
        $this->assertEquals(
            $this->nvp->beforeCall($this->subject, $this->title, $this->request),
            [$this->title, [
                    'AMT' => $this->request['AMT'] + 1.2,
                    'ITEMAMT' => $this->request['ITEMAMT'],
                    'SHIPPINGAMT' => $this->request['SHIPPINGAMT'],
                    'TAXAMT' => $this->request['TAXAMT'],
                    'INSURANCEAMT' => 1.2
                ]
            ]
        );
    }

    public function testbeforeCall()
    {
        $this->extend->expects($this->once())->method('isEnabled')->willReturn(true);
        $this->subject->expects($this->once())->method('getData')->with('quote')->willReturn($this->quote);
        $this->quote->expects($this->once())->method('getExtensionAttributes')->willReturn($this->quoteExtensionAttributes);
        $this->quoteExtensionAttributes->expects($this->once())->method('getShippingProtection')->willReturn($this->shippingProtection);
        $this->shippingProtection->expects($this->once())->method('getBase')->willReturn(1.2);
        $this->assertEquals(
            $this->nvp->beforeCall($this->subject, $this->title, $this->request),
                [$this->title, [
                    'AMT' => $this->request['AMT'] + 1.2,
                    'ITEMAMT' => $this->request['ITEMAMT'],
                    'SHIPPINGAMT' => $this->request['SHIPPINGAMT'],
                    'TAXAMT' => $this->request['TAXAMT'],
                    'INSURANCEAMT' => 1.2
                ]
            ]
        );
    }
}
