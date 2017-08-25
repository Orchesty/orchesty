<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: sep
 * Date: 22.8.17
 * Time: 8:57
 */

namespace Hanaboso\PipesFramework\RabbitMqBundle\DependencyInjection;

use InvalidArgumentException;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

/**
 * Class RabbitMqExtension
 *
 * @package Hanaboso\PipesFramework\RabbitMqBundle\DependencyInjection
 */
class RabbitMqExtension extends Extension implements ConfigurationInterface
{

    /**
     * @return string
     */
    public function getAlias(): string
    {
        return 'rabbit-mq';
    }

    /**
     * Generates the configuration tree builder.
     *
     * @return TreeBuilder The tree builder
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder();

        $rootNode = $treeBuilder->root("rabbit-mq");

        $rootNode->children()->scalarNode("host")->defaultValue("127.0.0.1");
        $rootNode->children()->scalarNode("port")->defaultValue(5672);
        $rootNode->children()->scalarNode("vhost")->defaultValue("/");
        $rootNode->children()->scalarNode("user")->defaultValue("guest");
        $rootNode->children()->scalarNode("password")->defaultValue("guest");
        $rootNode->children()->scalarNode("heartbeat")->defaultValue(60);

        /** @var ArrayNodeDefinition $exchangesNode */
        $exchangesNode = $rootNode->children()->arrayNode("exchanges")->normalizeKeys(FALSE)->defaultValue([])
            ->prototype("array");
        $exchangesNode->children()->scalarNode("type");
        $exchangesNode->children()->booleanNode("durable")->defaultValue(FALSE);
        $exchangesNode->children()->booleanNode("auto_delete")->defaultValue(FALSE);
        $exchangesNode->children()->booleanNode("internal")->defaultValue(FALSE);
        $exchangesNode->children()->arrayNode("arguments")->normalizeKeys(FALSE)->prototype("scalar")->defaultValue([]);

        /** @var ArrayNodeDefinition $exchangesBindingsNode */
        $exchangesBindingsNode = $exchangesNode->children()->arrayNode("bindings")->normalizeKeys(FALSE)
            ->defaultValue([])->prototype("array");
        $exchangesBindingsNode->children()->scalarNode("exchange")->isRequired();
        $exchangesBindingsNode->children()->scalarNode("routing_key")->defaultValue("");
        $exchangesBindingsNode->children()->arrayNode("arguments")->normalizeKeys(FALSE)->prototype("scalar")
            ->defaultValue([]);

        /** @var ArrayNodeDefinition $queuesNode */
        $queuesNode = $rootNode->children()->arrayNode('queues')->normalizeKeys(FALSE)->defaultValue([])
            ->prototype("array");
        $queuesNode->children()->booleanNode("durable")->defaultValue(FALSE);
        $queuesNode->children()->booleanNode("exclusive")->defaultValue(FALSE);
        $queuesNode->children()->booleanNode("auto_delete")->defaultValue(FALSE);
        $queuesNode->children()->arrayNode("arguments")->normalizeKeys(FALSE)->prototype("scalar")->defaultValue([]);

        /** @var ArrayNodeDefinition $queuesBindingsNode */
        $queuesBindingsNode = $queuesNode->children()->arrayNode("bindings")->normalizeKeys(FALSE)->defaultValue([])
            ->prototype("array");
        $queuesBindingsNode->children()->scalarNode("exchange")->isRequired();
        $queuesBindingsNode->children()->scalarNode("routing_key")->defaultValue("");
        $queuesBindingsNode->children()->arrayNode("arguments")->normalizeKeys(FALSE)->prototype("scalar")
            ->defaultValue([]);

        $producersNode = $rootNode->children()->arrayNode('producers')->normalizeKeys(FALSE)->defaultValue([])
            ->prototype('array');
        $producersNode->children()->scalarNode('class')->isRequired();
        $producersNode->children()->scalarNode('serializer')->isRequired();
        $producersNode->children()->scalarNode('exchange')->defaultValue('');
        $producersNode->children()->scalarNode('routing_key')->defaultValue('');
        $producersNode->children()->booleanNode('mandatory')->defaultFalse();
        $producersNode->children()->booleanNode('immediate')->defaultFalse();
        $producersNode->children()->scalarNode('before_method')->defaultNull();
        $producersNode->children()->scalarNode('content_type')->defaultValue('application/json');

        $consumersNode = $rootNode->children()->arrayNode('consumers')->normalizeKeys(FALSE)->defaultValue([])
            ->prototype('array');
        $consumersNode->children()->scalarNode('class')->isRequired();
        $consumersNode->children()->scalarNode('queue')->isRequired();
        $consumersNode->children()->scalarNode('serializer')->isRequired();
        $consumersNode->children()->scalarNode('exchange')->defaultValue(NULL);
        $consumersNode->children()->scalarNode('routing_key')->defaultValue('');
        $consumersNode->children()->scalarNode('consumer_tag')->defaultValue('');
        $consumersNode->children()->booleanNode('no_local')->defaultFalse();
        $consumersNode->children()->booleanNode('no_ack')->defaultFalse();
        $consumersNode->children()->booleanNode('exclusive')->defaultFalse();
        $consumersNode->children()->booleanNode('nowait')->defaultFalse();
        $consumersNode->children()->scalarNode('prefetch_count')->defaultValue(1);
        $consumersNode->children()->scalarNode('prefetch_size')->defaultValue(0);
        $consumersNode->children()->scalarNode('set_up_method')->defaultValue(NULL);
        $consumersNode->children()->scalarNode('tick_method')->defaultValue(NULL);
        $consumersNode->children()->scalarNode('tick_seconds')->defaultValue(NULL);
        $consumersNode->children()->scalarNode('tick_seconds')->defaultValue(NULL);
        $consumersNode->children()->scalarNode('max_messages')->defaultValue(NULL);
        $consumersNode->children()->scalarNode('max_seconds')->defaultValue(NULL);
        $consumersNode->children()->arrayNode("arguments")->normalizeKeys(FALSE)->prototype("scalar")->defaultValue([]);

        return $treeBuilder;
    }

    /**
     * Loads a specific configuration.
     *
     * @param array            $configs   An array of configuration values
     * @param ContainerBuilder $container A ContainerBuilder instance
     *
     * @return void
     * @throws InvalidArgumentException When provided tag is not defined in this extension
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');

        $container->setParameter("rabbit-mq", $this->processConfiguration($this, $configs));
    }

}
