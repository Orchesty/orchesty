<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\RabbitMq\Serializers;

/**
 * Interface IMessageSerializer
 *
 * @package Hanaboso\PipesFramework\RabbitMq\Serializers
 */
interface IMessageSerializer
{

    /**
     * Returns instance of this meta class
     *
     * @return IMessageSerializer
     */
    public static function getInstance(): IMessageSerializer;

    /**
     * @param string $json
     *
     * @return array
     */
    public static function fromJson(string $json): array;

    /**
     * @param mixed $object
     *
     * @return string
     */
    public static function toJson($object): string;

}
