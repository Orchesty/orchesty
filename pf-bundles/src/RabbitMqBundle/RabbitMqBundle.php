<?php
/**
 * Created by PhpStorm.
 * User: Pavel Severyn
 * Date: 22.8.17
 * Time: 8:54
 */

namespace RabbitMqBundle;

use RabbitMqBundle\DependencyInjection\Compiler\RabbitMqCompilerPass;
use RabbitMqBundle\DependencyInjection\RabbitMqExtension;
use Symfony\Component\Console\Application;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class RabbitMqBundle extends Bundle
{

	public function getContainerExtension()
	{
		if ($this->extension === NULL) {
			$this->extension = new RabbitMqExtension();
		}

		return $this->extension;
	}

	public function build(ContainerBuilder $container)
	{
		$container->addCompilerPass(
			new RabbitMqCompilerPass(
				"rabbit-mq",
				"rabbit-mq.client",
				"rabbit-mq.manager",
				"rabbit-mq.channel",
				"command.rabbit-mq.setup",
				"command.rabbit-mq.consumer",
				"command.rabbit-mq.producer"
			),
			PassConfig::TYPE_OPTIMIZE
		);
	}

	public function registerCommands(Application $application)
	{
		/** @var Command[] $commands */
		$commands = [
			$this->container->get("command.rabbit-mq.setup"),
			$this->container->get("command.rabbit-mq.consumer"),
			$this->container->get("command.rabbit-mq.producer"),
		];

		foreach ($commands as $command) {
			$application->add($command);
		}
	}

}
