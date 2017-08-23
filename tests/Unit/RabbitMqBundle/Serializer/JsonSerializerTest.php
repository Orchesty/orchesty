<?php
/**
 * Created by PhpStorm.
 * User: Pavel Severyn
 * Date: 23.8.17
 * Time: 14:07
 */

namespace Tests\Unit\RabbitMqBundle\Serializer;

use RabbitMqBundle\Serializers\IMessageSerializer;
use RabbitMqBundle\Serializers\JsonSerializer;
use Tests\KernelTestCaseAbstract;

/**
 * Class JsonSerializerTest
 *
 * @package Tests\Unit\RabbitMqBundle\Serializer
 */
class JsonSerializerTest extends KernelTestCaseAbstract
{

	/**
	 * @var IMessageSerializer
	 */
	protected $serializer;

	/**
	 *
	 */
	protected function setUp()
	{
		parent::setUp();
		$this->serializer = new JsonSerializer();
	}

	/**
	 *
	 */
	public function testGetInstance()
	{
		$this->assertInstanceOf(JsonSerializer::class, $this->serializer->getInstance());
	}

	/**
	 *
	 */
	public function testToJson()
	{
		$dataProvider = $this->toJsonProvider();
		while (list($src, $result) = current($dataProvider)) {

			$serialized = $this->serializer->toJson($src);

			$this->assertEquals($result, $serialized);

			next($dataProvider);
		}
	}

	/**
	 *
	 */
	public function testFromJson()
	{
		$dataProvider = $this->fromJsonProvider();
		while (list($src, $result) = current($dataProvider)) {

			$serialized = $this->serializer->fromJson($src);

			$this->assertEquals($result, $serialized);

			next($dataProvider);
		}
	}

	/**
	 * @return array
	 */
	public function toJsonProvider()
	{
		return [
			[[1, 2, 3], '[1,2,3]'],
			[['root' => ['body' => 1]], '{"root":{"body":1}}'],
		];
	}

	/**
	 * @return array
	 */
	public function fromJsonProvider()
	{
		return [
			['[1,2,3]', [1, 2, 3]],
			['{"root":{"body":1}}', ['root' => ['body' => 1]]],
		];
	}

}
