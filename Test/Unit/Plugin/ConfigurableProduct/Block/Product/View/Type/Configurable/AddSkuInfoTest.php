<?php

namespace Extend\Integration\Test\Unit\Plugin\ConfigurableProduct\Block\Product\View\Type\Configurable;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Extend\Integration\Plugin\ConfigurableProduct\Block\Product\View\Type\Configurable\AddSkuInfo;
use Magento\ConfigurableProduct\Block\Product\View\Type\Configurable as ConfigurableBlock;
use Magento\Framework\Serialize\Serializer\Json;
use Psr\Log\LoggerInterface;
use Magento\Catalog\Model\Product;

class AddSkuInfoTest extends TestCase
{
    /**
     * @var Json|MockObject
     */
    private $jsonSerializerMock;

    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @var AddSkuInfo
     */
    private $testSubject;

    /**
     * @var ConfigurableBlock|MockObject
     */
    private $configurableBlockMock;

    protected function setUp(): void
    {
        $this->jsonSerializerMock = $this->createMock(Json::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);

        $this->testSubject = new AddSkuInfo(
            $this->jsonSerializerMock,
            $this->loggerMock
        );

        $this->configurableBlockMock = $this->createMock(ConfigurableBlock::class);
    }

    /**
     * Test that SKU info is added when not present (MSI disabled scenario)
     */
    public function testAddsSkuInfoWhenNotPresent()
    {
        // Arrange: Create mock products
        $product1 = $this->createProductMock(1, 'SKU-001');
        $product2 = $this->createProductMock(2, 'SKU-002');
        $product3 = $this->createProductMock(3, 'SKU-003');

        $this->configurableBlockMock->method('getAllowProducts')
            ->willReturn([$product1, $product2, $product3]);

        $inputConfig = [
            'attributes' => [],
            'productId' => 100,
        ];

        $expectedConfig = [
            'attributes' => [],
            'productId' => 100,
            'sku' => [
                1 => 'SKU-001',
                2 => 'SKU-002',
                3 => 'SKU-003',
            ],
        ];

        $this->jsonSerializerMock->expects($this->once())
            ->method('unserialize')
            ->with('{"attributes":[],"productId":100}')
            ->willReturn($inputConfig);

        $this->jsonSerializerMock->expects($this->once())
            ->method('serialize')
            ->with($expectedConfig)
            ->willReturn(json_encode($expectedConfig));

        // Act
        $result = $this->testSubject->afterGetJsonConfig(
            $this->configurableBlockMock,
            '{"attributes":[],"productId":100}'
        );

        // Assert
        $this->assertNotEmpty($result);
    }

    /**
     * Test that existing SKU info is preserved (MSI enabled scenario)
     */
    public function testPreservesExistingSkuInfo()
    {
        // Arrange: Config already has SKU data from MSI
        $inputConfig = [
            'attributes' => [],
            'productId' => 100,
            'sku' => [
                1 => 'EXISTING-SKU-001',
                2 => 'EXISTING-SKU-002',
            ],
        ];

        $expectedConfig = [
            'attributes' => [],
            'productId' => 100,
            'sku' => [
                1 => 'EXISTING-SKU-001',
                2 => 'EXISTING-SKU-002',
            ],
        ];

        $this->jsonSerializerMock->expects($this->once())
            ->method('unserialize')
            ->willReturn($inputConfig);

        $this->jsonSerializerMock->expects($this->once())
            ->method('serialize')
            ->with($expectedConfig)
            ->willReturn(json_encode($expectedConfig));

        // Expect getAllowProducts to NOT be called since SKU already exists
        $this->configurableBlockMock->expects($this->never())
            ->method('getAllowProducts');

        // Act
        $result = $this->testSubject->afterGetJsonConfig(
            $this->configurableBlockMock,
            json_encode($inputConfig)
        );

        // Assert
        $this->assertNotEmpty($result);
    }

    /**
     * Test that 'sku' key is added when missing
     */
    public function testAddsSkuKey()
    {
        // Arrange
        $product = $this->createProductMock(1, 'TEST-SKU');

        $this->configurableBlockMock->method('getAllowProducts')
            ->willReturn([$product]);

        $inputConfig = ['productId' => 100];

        $this->jsonSerializerMock->method('unserialize')
            ->willReturn($inputConfig);

        $this->jsonSerializerMock->expects($this->once())
            ->method('serialize')
            ->willReturnCallback(function ($config) {
                // Assert sku key is present
                $this->assertArrayHasKey('sku', $config);
                $this->assertEquals([1 => 'TEST-SKU'], $config['sku']);
                return json_encode($config);
            });

        // Act
        $this->testSubject->afterGetJsonConfig(
            $this->configurableBlockMock,
            json_encode($inputConfig)
        );
    }

    /**
     * Test error handling when JSON deserialization fails
     */
    public function testHandlesDeserializationError()
    {
        // Arrange
        $invalidJson = 'invalid-json';

        $this->jsonSerializerMock->expects($this->once())
            ->method('unserialize')
            ->with($invalidJson)
            ->willThrowException(new \InvalidArgumentException('Invalid JSON'));

        $this->loggerMock->expects($this->once())
            ->method('error')
            ->with(
                'Extend Integration: Error adding SKU info to configurable product config',
                $this->callback(function ($context) {
                    return isset($context['exception']) &&
                           strpos($context['exception'], 'Invalid JSON') !== false;
                })
            );

        // Act - should return original result without throwing
        $result = $this->testSubject->afterGetJsonConfig(
            $this->configurableBlockMock,
            $invalidJson
        );

        // Assert - original result is returned
        $this->assertEquals($invalidJson, $result);
    }

    /**
     * Test error handling when product operations fail
     */
    public function testHandlesProductFetchError()
    {
        // Arrange
        $this->configurableBlockMock->method('getAllowProducts')
            ->willThrowException(new \Exception('Product fetch failed'));

        $inputConfig = ['productId' => 100];

        $this->jsonSerializerMock->method('unserialize')
            ->willReturn($inputConfig);

        $this->loggerMock->expects($this->once())
            ->method('error')
            ->with(
                'Extend Integration: Error adding SKU info to configurable product config',
                $this->callback(function ($context) {
                    return isset($context['exception']);
                })
            );

        // Act
        $result = $this->testSubject->afterGetJsonConfig(
            $this->configurableBlockMock,
            json_encode($inputConfig)
        );

        // Assert - should return original input
        $this->assertEquals(json_encode($inputConfig), $result);
    }

    /**
     * Test handling of empty product list
     */
    public function testHandlesEmptyProductList()
    {
        // Arrange
        $this->configurableBlockMock->method('getAllowProducts')
            ->willReturn([]);

        $inputConfig = ['productId' => 100];

        $expectedConfig = [
            'productId' => 100,
            'sku' => [],
        ];

        $this->jsonSerializerMock->method('unserialize')
            ->willReturn($inputConfig);

        $this->jsonSerializerMock->expects($this->once())
            ->method('serialize')
            ->with($expectedConfig)
            ->willReturn(json_encode($expectedConfig));

        // Act
        $result = $this->testSubject->afterGetJsonConfig(
            $this->configurableBlockMock,
            json_encode($inputConfig)
        );

        // Assert
        $this->assertNotEmpty($result);
    }

    /**
     * Helper method to create a product mock
     *
     * @param int $id
     * @param string $sku
     * @return Product|MockObject
     */
    private function createProductMock(int $id, string $sku)
    {
        $product = $this->createMock(Product::class);
        $product->method('getId')->willReturn($id);
        $product->method('getSku')->willReturn($sku);
        return $product;
    }
}
