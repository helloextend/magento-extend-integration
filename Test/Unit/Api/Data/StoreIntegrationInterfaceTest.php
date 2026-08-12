<?php

namespace Extend\Integration\Test\Unit\Api\Data;

use Extend\Integration\Api\Data\StoreIntegrationInterface;
use Laminas\Code\Reflection\ClassReflection;
use Magento\Framework\Reflection\TypeProcessor;
use PHPUnit\Framework\TestCase;

class StoreIntegrationInterfaceTest extends TestCase
{
    public function testMagentoTypeProcessorCanProcessStoreIntegrationInterface(): void
    {
        $typeProcessor = new TypeProcessor();
        $classReflection = new ClassReflection(StoreIntegrationInterface::class);
        $getterCount = 0;
        $foundIntegrationErrorGetter = false;
        $foundIntegrationErrorSetter = false;

        foreach ($classReflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $methodReflection) {
            $methodName = $methodReflection->getName();

            $this->assertNotFalse(
                $methodReflection->getDocComment(),
                sprintf('%s::%s() must have a doc block', StoreIntegrationInterface::class, $methodName)
            );

            if (strpos($methodName, 'get') === 0) {
                $getterCount++;
                $returnType = $typeProcessor->getGetterReturnType($methodReflection);
                $this->assertTrue(is_string($returnType['type']) && $returnType['type'] !== '');

                if ($methodName === 'getIntegrationError') {
                    $foundIntegrationErrorGetter = true;
                    $this->assertSame('string', $returnType['type']);
                    $this->assertFalse($returnType['isRequired']);
                }
            }

            if ($methodName !== 'setIntegrationError') {
                continue;
            }

            $foundIntegrationErrorSetter = true;
            $parameters = $methodReflection->getParameters();
            $this->assertCount(1, $parameters);
            $this->assertSame('integrationError', $parameters[0]->getName());
            $this->assertTrue($parameters[0]->getType()->allowsNull());
            $this->assertSame('string', $typeProcessor->getParamType($parameters[0]));

            $paramTags = $methodReflection->getDocBlock()->getTags('param');
            $this->assertCount(1, $paramTags);
            $this->assertSame(['string', 'null'], $paramTags[0]->getTypes());
        }

        $this->assertTrue($getterCount > 0);
        $this->assertTrue($foundIntegrationErrorGetter);
        $this->assertTrue($foundIntegrationErrorSetter);
    }
}
