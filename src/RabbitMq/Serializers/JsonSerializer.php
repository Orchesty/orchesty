<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: Pavel Severyn
 * Date: 23.8.17
 * Time: 14:00
 */

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
        return json_encode($object);
    }

}
