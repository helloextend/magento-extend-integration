<?php
namespace Extend\Integration\Test\Utils;

class PHPUnitUtils
{
  /**
   * Call a protected/private method of a class. For unit testing only.
   * 
   * @param object $obj to invoke the method on
   * @param string $methodName of the method to call
   * @param array $args to pass to the method
   * 
   * @return mixed
   */
  public static function callMethod($obj, $methodName, array $args = [])
  {
    $class = new \ReflectionClass($obj);
    $method = $class->getMethod($methodName);
    $method->setAccessible(true); // Use this if you are running PHP older than 8.1.0
    return $method->invokeArgs($obj, $args);
  }
}
