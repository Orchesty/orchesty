<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: Pavel Severyn
 * Date: 23.8.17
 * Time: 19:19
 */

namespace Tests\Unit\HbPFRabbitMqBundle\DependencyInjection\Compiler;

use Hanaboso\PipesFramework\HbPFRabbitMqBundle\DependencyInjection\Compiler\RabbitMqCompilerPass;
use InvalidArgumentException;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\Yaml\Yaml;
use Tests\KernelTestCaseAbstract;

/**
 * Class RabbitMqCompilerPassTest
 *
 * @package Tests\Unit\HbPFRabbitMqBundle\DependencyInjection\Compiler
 */
class RabbitMqCompilerPassTest extends KernelTestCaseAbstract
{

    /**
     * @var RabbitMqCompilerPass
     */
    protected $pass;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->pass = new  RabbitMqCompilerPass(
            "rabbit-mq",
            "rabbit-mq.client",
            "rabbit-mq.manager",
            "rabbit-mq.channel",
            "command.rabbit-mq.setup",
            "command.rabbit-mq.consumer",
            "command.rabbit-mq.async-consumer",
            "command.rabbit-mq.producer"
        );
    }

    /**
     * @return void
     */
    public function testProcessNoKey(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $container = new ContainerBuilder(
            new ParameterBag()
        );

        $this->pass->process($container);
    }

    /**
     *
     */
    public function testProcess(): void
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
            'command.rabbit-mq.async-consumer',
        ], array_keys($container->getDefinitions()));
    }

    /**
     * @param string $file
     *
     * @return array
     */
    private function getParameters(string $file): array
    {
        $config = __DIR__ . '/sample/' . $file;
        if (!file_exists($config)) {
            return [];
        }

        return Yaml::parse(file_get_contents($config));
    }

}
