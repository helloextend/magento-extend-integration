<?php

namespace Extend\Integration\Test\Unit; /* add \Path\To\Dir */

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
// use Extend\Integration\Path\To\SomeTestClass
// use Magento\Framework\Path\To\SomeClassToMock

class ShippingProtectionTest extends TestCase
{

  /**
   * @var // someprimitivetype
   */
  private $someTestValue;

  /**
   * @var // SomeTestClass
   */
  private $testSubject;

  /**
   * @var // SomeClassToMock | MockObject
   */
  private $thingMock;

  protected function setUp(): void
  {
    // set primitive test values
    // $this->someTestValue = 123.45

    // create mock constructor args for the tested class
    // $this->thingMock = $this->createStub(/* SomeClass::class */);

    // create the class to test
    // $this->testSubject = new /* SomeClass */($this->thingMock);

    // create arguments for tested method(s)

    // additional setup needed to cover the permutations in the test cases below

  }

  public function testToImplement1()
  {
    $this->setTestConditions([
      'condition_1' => false,
    ]);
    $this->expectNothingToHappen();
    $this->markTestIncomplete();
  }

  public function testToImplement2()
  {
    $this->setTestConditions([
      'condition_1' => true,
    ]);
    $this->expectSomeThingsToHappen();
    $this->markTestIncomplete();
  }

  /* =================================================================================================== */
  /* ========================== helper methods for setting up test conditions ========================== */
  /* =================================================================================================== */

  /**
   * @param array $conditions
   * 1. condition_1
   * 2. ...etc
   */

  private function setTestConditions(
    array $conditions
  ) {
    $this->setSingleCondition($conditions['condition_1'] ?? false);
  }

  private function setSingleCondition(bool $condition)
  {
    if (isset($condition) && $condition) {
      // do one setup thing
    } else {
      // do another setup thing
    }
  }

  /* =================================================================================================== */
  /* ============================== helper methods for validating results ============================== */
  /* =================================================================================================== */

  private function expectNothingToHappen()
  {
    $this->thingMock->expects($this->never())->method('doSomething');
    $this->thingMock->expects($this->never())->method('doAnotherThing');
  }

  private function expectSomeThingsToHappen()
  {
    $this->thingMock->expects($this->once())->method('doSomething')->with('these', 'arguments')->willReturn($this->someTestValue);
    $this->thingMock->expects($this->once())->method('doAnotherThing')->with('these', 'different', 'arguments');
  }
}
