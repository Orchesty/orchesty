<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: Pavel Severyn
 * Date: 22.8.17
 * Time: 12:49
 */

namespace Hanaboso\PipesFramework\RabbitMqBundle\Serializers;

/**
 * Interface IMessageSerializer
 *
 * @package Hanaboso\PipesFramework\RabbitMqBundle\Serializers
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
