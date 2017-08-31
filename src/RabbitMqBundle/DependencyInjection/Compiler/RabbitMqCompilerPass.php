<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: Pavel Severyn
 * Date: 22.8.17
 * Time: 9:04
 */

namespace Hanaboso\PipesFramework\RabbitMqBundle\DependencyInjection\Compiler;

use InvalidArgumentException;
use Psr\Log\LoggerAwareInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Class RabbitMqCompilerPass
 *
 * @package Hanaboso\PipesFramework\RabbitMqBundle\DependencyInjection\Compiler
 */
class RabbitMqCompilerPass implements CompilerPassInterface
{

    /**
     * @var string
     */
    private $configKey;

    /**
     * @var string
     */
    private $clientServiceId;

    /**
     * @var string
     */
    private $managerServiceId;

    /**
     * @var string
     */
    private $channelServiceId;

    /**
     * @var string
     */
    private $setupCommandServiceId;

    /**
     * @var string
     */
    private $consumerCommandServiceId;

    /**
     * @var string
     */
    private $producerCommandServiceId;

    /**
     * RabbitMqCompilerPass constructor.
     *
     * @param string $configKey
     * @param string $clientServiceId
     * @param string $managerServiceId
     * @param string $channelServiceId
     * @param string $setupCommandServiceId
     * @param string $consumerCommandServiceId
     * @param string $producerCommandServiceId
     */
    public function __construct(
        string $configKey,
        string $clientServiceId,
        string $managerServiceId,
        string $channelServiceId,
        string $setupCommandServiceId,
        string $consumerCommandServiceId,
        string $producerCommandServiceId
    )
    {

        $this->configKey                = $configKey;
        $this->clientServiceId          = $clientServiceId;
        $this->managerServiceId         = $managerServiceId;
        $this->channelServiceId         = $channelServiceId;
        $this->setupCommandServiceId    = $setupCommandServiceId;
        $this->consumerCommandServiceId = $consumerCommandServiceId;
        $this->producerCommandServiceId = $producerCommandServiceId;
    }

    /**
     * You can modify the container here before it is dumped to PHP code.
     *
     * @param ContainerBuilder $container
     *
     * @return void
     */
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasParameter($this->configKey)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Container doesn\'t have parameter \'%s\', RabbitMqBunnyExtension probably haven\'t processed config.',
                    $this->configKey
                )
            );
        }

        $config = $container->getParameter($this->configKey);
        $consumers = [];
        $producers = [];

        if (!array_key_exists('producers', $config)) {
            throw new InvalidArgumentException(
                'Container doesn\'t have config parameter \'producers\', RabbitMqBunnyExtension probably haven\'t processed config.'
            );
        }

        foreach ($config['producers'] as $key => $value) {

            $definition = new Definition($value['class'], [
                $value['exchange'],
                $value['routing_key'],
                $value['mandatory'],
                $value['immediate'],
                $value['serializer'],
                $value['before_method'],
                $value['content_type'],
                new Reference($this->managerServiceId),
            ]);

            if (array_key_exists(LoggerAwareInterface::class, class_implements($value['class']))) {
                $definition->addMethodCall('setLogger', [
                    new Reference('monolog.logger.rabbit-mq', ContainerInterface::IGNORE_ON_INVALID_REFERENCE),
                ]);
            }

            $producers[$key] = $definition;

            $serviceName = sprintf('rabbit-mq.producer.%s', $key);
            $container->setDefinition($serviceName, $definition);
        }

        if (!array_key_exists('consumers', $config)) {
            throw new InvalidArgumentException(
                'Container doesn\'t have config parameter \'consumers\', RabbitMqBunnyExtension probably haven\'t processed config.'
            );
        }

        foreach ($config['consumers'] as $key => $value) {

            $definition = new Definition($value['class'], [
                $value['exchange'],
                $value['routing_key'],
                $value['queue'],
                $value['consumer_tag'],
                $value['no_local'],
                $value['no_ack'],
                $value['exclusive'],
                $value['nowait'],
                $value['arguments'],
                $value['prefetch_count'],
                $value['prefetch_size'],
                $value['serializer'],
                $value['set_up_method'],
                $value['tick_method'],
                $value['tick_seconds'],
                $value['max_messages'],
                $value['max_seconds'],
            ]);

            if (array_key_exists(LoggerAwareInterface::class, class_implements($value['class']))) {
                $definition->addMethodCall('setLogger', [
                    new Reference('monolog.logger.rabbit-mq', ContainerInterface::IGNORE_ON_INVALID_REFERENCE),
                ]);
            }

            $definition->addMethodCall('setCallback', [
                 [new Reference($value['callback'], ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE), 'handleMessage'],
            ]);

            $consumers[$key] = $definition;

            $serviceName = sprintf('rabbit-mq.consumer.%s', $key);
            $container->setDefinition($serviceName, $definition);
        }

        /**
         * Connection definition
         */
        $container->setDefinition($this->clientServiceId, new Definition('%rabbit-mq.bunny-client%', [
            [
                'host'      => $config["host"],
                'port'      => $config["port"],
                'vhost'     => $config["vhost"],
                'user'      => $config["user"],
                'password'  => $config["password"],
                'heartbeat' => $config["heartbeat"],
            ],
        ]));

        /**
         * BunnyManager
         */
        $container->setDefinition($this->managerServiceId, new Definition('%rabbit-mq.bunny-manager%', [
            new Reference('service_container'),
            $this->clientServiceId,
            $config,
        ]));

        /**
         * Bunny channel
         */
        $channel = new Definition('%rabbit-mq.bunny-channel%');
        $channel->setFactory([new Reference($this->managerServiceId), "getChannel"]);
        $container->setDefinition($this->channelServiceId, $channel);

        $container->setDefinition($this->setupCommandServiceId,
            new Definition('%rabbit-mq.command.setup%', [
                new Reference($this->managerServiceId),
            ]));

        $container->setDefinition($this->consumerCommandServiceId,
            new Definition('%rabbit-mq.command.consumer%', [
                new Reference("service_container"),
                new Reference($this->managerServiceId),
                $consumers,
            ]));
    }

    /**
     * @return string
     */
    public function getConfigKey(): string
    {
        return $this->configKey;
    }

    /**
     * @return string
     */
    public function getClientServiceId(): string
    {
        return $this->clientServiceId;
    }

    /**
     * @return string
     */
    public function getManagerServiceId(): string
    {
        return $this->managerServiceId;
    }

    /**
     * @return string
     */
    public function getChannelServiceId(): string
    {
        return $this->channelServiceId;
    }

    /**
     * @return string
     */
    public function getSetupCommandServiceId(): string
    {
        return $this->setupCommandServiceId;
    }

    /**
     * @return string
     */
    public function getConsumerCommandServiceId(): string
    {
        return $this->consumerCommandServiceId;
    }

    /**
     * @return string
     */
    public function getProducerCommandServiceId(): string
    {
        return $this->producerCommandServiceId;
    }

}
