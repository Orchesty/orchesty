<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: Pavel Severyn
 * Date: 22.8.17
 * Time: 8:54
 */

namespace Hanaboso\PipesFramework\HbPFRabbitMqBundle;

use Hanaboso\PipesFramework\HbPFRabbitMqBundle\DependencyInjection\Compiler\RabbitMqCompilerPass;
use Hanaboso\PipesFramework\HbPFRabbitMqBundle\DependencyInjection\RabbitMqExtension;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Class HbPFRabbitMqBundle
 *
 * @package Hanaboso\PipesFramework\HbPFRabbitMqBundle
 */
class HbPFRabbitMqBundle extends Bundle
{

    /**
     * @return RabbitMqExtension
     */
    public function getContainerExtension(): RabbitMqExtension
    {
        if ($this->extension === NULL) {
            $this->extension = new RabbitMqExtension();
        }

        return $this->extension;
    }

    /**
     * @param ContainerBuilder $container
     *
     * @return void
     */
    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(
            new RabbitMqCompilerPass(
                "rabbit-mq",
                "rabbit-mq.client",
                "rabbit-mq.manager",
                "rabbit-mq.channel",
                "command.rabbit-mq.setup",
                "command.rabbit-mq.consumer",
                "command.rabbit-mq.async-consumer",
                "command.rabbit-mq.producer"
            ),
            PassConfig::TYPE_OPTIMIZE
        );
    }

    /**
     * @param Application $application
     *
     * @return void
     */
    public function registerCommands(Application $application): void
    {
        /** @var Command[] $commands */
        $commands = [
            $this->container->get("command.rabbit-mq.setup"),
            $this->container->get("command.rabbit-mq.consumer"),
            $this->container->get("command.rabbit-mq.async-consumer"),
        ];

        foreach ($commands as $command) {
            $application->add($command);
        }
    }

}
