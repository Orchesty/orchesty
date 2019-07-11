<?php declare(strict_types=1);

namespace Tests;

use ReflectionClass;
use ReflectionException;
use ReflectionObject;

/**
 * Trait PrivateTrait
 *
 * @package Tests
 */
trait PrivateTrait
{

    /**
     * @param mixed  $object
     * @param string $propertyName
     * @param mixed  $value
     *
     * @throws ReflectionException
     */
    protected function setProperty($object, $propertyName, $value): void
    {
        $reflection = new ReflectionObject($object);

        do {
            if ($reflection->hasProperty($propertyName)) {
                break;
            }

            /** @var ReflectionObject $reflection */
            $reflection = $reflection->getParentClass();
        } while ($reflection);

        $property = $reflection->getProperty($propertyName);
        $property->setAccessible(TRUE);
        $property->setValue($object, $value);
    }

    /**
     * @param mixed  $object
     * @param string $propertyName
     *
     * @return mixed
     * @throws ReflectionException
     */
    protected function getProperty($object, $propertyName)
    {
        $reflection = new ReflectionObject($object);

        do {
            if ($reflection->hasProperty($propertyName)) {
                break;
            }

            /** @var ReflectionObject $reflection */
            $reflection = $reflection->getParentClass();
        } while ($reflection);

        $property = $reflection->getProperty($propertyName);
        $property->setAccessible(TRUE);

        return $property->getValue($object);
    }

    /**
     * @param object $object
     * @param string $methodName
     * @param array  $parameters
     *
     * @return mixed
     * @throws ReflectionException
     */
    protected function invokeMethod($object, $methodName, array $parameters = [])
    {
        $reflection = new ReflectionClass(get_class($object));

        do {
            if ($reflection->hasMethod($methodName)) {
                break;
            }

            /** @var ReflectionObject $reflection */
            $reflection = $reflection->getParentClass();
        } while ($reflection);

        $method = $reflection->getMethod($methodName);
        $method->setAccessible(TRUE);

        return $method->invokeArgs($object, $parameters);
    }

}
