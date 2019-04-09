<?php declare(strict_types=1);

namespace Tests;

use Exception;
use ReflectionClass;
use ReflectionObject;

/**
 * Trait PrivateTrait
 *
 * @package Tests
 */
trait PrivateTrait
{

    /**
     * @param object $object
     * @param string $propertyName
     * @param mixed  $value
     *
     * @throws Exception
     */
    protected function setProperty($object, $propertyName, $value): void
    {
        $reflection = new ReflectionObject($object);
        $property   = $reflection->getProperty($propertyName);
        $property->setAccessible(TRUE);
        $property->setValue($object, $value);
    }

    /**
     * @param object $object
     * @param string $propertyName
     *
     * @return mixed
     * @throws Exception
     */
    protected function getProperty($object, $propertyName)
    {
        $reflection = new ReflectionObject($object);
        $property   = $reflection->getProperty($propertyName);
        $property->setAccessible(TRUE);

        return $property->getValue($object);
    }

    /**
     * @param object $object
     * @param string $methodName
     * @param array  $parameters
     *
     * @return mixed
     * @throws Exception
     */
    protected function invokeMethod($object, $methodName, array $parameters = [])
    {
        $reflection = new ReflectionClass(get_class($object));
        $method     = $reflection->getMethod($methodName);
        $method->setAccessible(TRUE);

        return $method->invokeArgs($object, $parameters);
    }

}
