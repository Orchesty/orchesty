<?php
/**
 * Created by PhpStorm.
 * User: Pavel Severyn
 * Date: 22.8.17
 * Time: 12:49
 */

namespace RabbitMqBundle\Serializers;

/**
 * Interface IMessageSerializer
 *
 * @package RabbitMqBundle\Serializers
 */
interface IMessageSerializer
{

	/**
	 * Returns instance of this meta class
	 *
	 * @return $this
	 */
	public static function getInstance();

	/**
	 * @param string $json
	 *
	 * @return object
	 */
	public static function fromJson(string $json): object;

	/**
	 * @param mixed $object
	 *
	 * @return array
	 */
	public static function toJson($object): array;

}
