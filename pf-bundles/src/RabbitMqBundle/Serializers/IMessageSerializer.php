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
