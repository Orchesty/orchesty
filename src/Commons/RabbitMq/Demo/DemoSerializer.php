<?php
/**
 * Created by PhpStorm.
 * User: sep
 * Date: 22.8.17
 * Time: 13:47
 */

namespace Commons\RabbitMq\Demo;

use RabbitMqBundle\Serializers\IMessageSerializer;

class DemoSerializer implements IMessageSerializer
{

	/**
	 * Returns instance of this meta class
	 *
	 * @return $this
	 */
	public static function getInstance()
	{
		return new DemoSerializer();
	}

	/**
	 * @param string $json
	 *
	 * @return object
	 */
	public static function fromJson(string $json): object
	{
		// TODO: Implement fromJson() method.
	}

	/**
	 * @param mixed $object
	 *
	 * @return array
	 */
	public static function toJson($object): array
	{
		// TODO: Implement toJson() method.
	}

}
