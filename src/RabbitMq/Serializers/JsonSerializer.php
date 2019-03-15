<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\RabbitMq\Serializers;

/**
 * Class JsonSerializer
 *
 * @package Hanaboso\PipesFramework\RabbitMq\Serializers
 */
class JsonSerializer implements IMessageSerializer
{

    /**
     * Returns instance of this meta class
     *
     * @return IMessageSerializer
     */
    public static function getInstance(): IMessageSerializer
    {
        return new self();
    }

    /**
     * @param string $json
     *
     * @return array
     */
    public static function fromJson(string $json): array
    {
        return json_decode($json, TRUE);
    }

    /**
     * @param mixed $object
     *
     * @return string
     */
    public static function toJson($object): string
    {
        return (string) json_encode($object);
    }

}
