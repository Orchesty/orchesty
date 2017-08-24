<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: Pavel Severyn
 * Date: 23.8.17
 * Time: 19:19
 */

namespace Tests\Unit\RabbitMqBundle\DependencyInjection\Compiler;

use Hanaboso\PipesFramework\RabbitMqBundle\DependencyInjection\Compiler\RabbitMqCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\Yaml\Yaml;
use Tests\KernelTestCaseAbstract;

/**
 * Class RabbitMqCompilerPassTest
 *
 * @package Tests\Unit\RabbitMqBundle\DependencyInjection\Compiler
 */
class RabbitMqCompilerPassTest extends KernelTestCaseAbstract
{

	/**
	 * @var RabbitMqCompilerPass
	 */
	protected $pass;

	/**
	 *
	 */
	protected function setUp()
	{
		$this->pass = new  RabbitMqCompilerPass(
			"rabbit-mq",
			"rabbit-mq.client",
			"rabbit-mq.manager",
			"rabbit-mq.channel",
			"command.rabbit-mq.setup",
			"command.rabbit-mq.consumer",
			"command.rabbit-mq.producer"
		);
	}

	/**
	 *
	 */
	public function testProcessNoKey()
	{
		$this->expectException(\InvalidArgumentException::class);
		$container = new ContainerBuilder(
			new ParameterBag()
		);

		$this->pass->process($container);
	}

	/**
	 *
	 */
	public function testProcess()
	{
		$parameters = $this->getParameters('process.yml');
		$container  = new ContainerBuilder(
			new ParameterBag()
		);

		$container->setParameter('rabbit-mq', $parameters['rabbit-mq']);

		$this->pass->process($container);

		$this->assertEquals([
			'service_container',
			'rabbit-mq.producer.demo',
			'rabbit-mq.consumer.demo',
			'rabbit-mq.client',
			'rabbit-mq.manager',
			'rabbit-mq.channel',
			'command.rabbit-mq.setup',
			'command.rabbit-mq.consumer',
		], array_keys($container->getDefinitions()));
	}

	/**
	 * @param $file
	 *
	 * @return array|mixed
	 */
	private function getParameters($file)
	{
		$config = __DIR__ . '/sample/' . $file;
		if (!file_exists($config)) {
			return [];
		}

		return Yaml::parse(file_get_contents($config));
	}

}
