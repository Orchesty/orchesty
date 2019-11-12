<?php declare(strict_types=1);

namespace Hanaboso\HbPFConnectors\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Class Configuration
 *
 * @package Hanaboso\HbPFConnectors\DependencyInjection
 */
class Configuration implements ConfigurationInterface
{

    /**
     * @return TreeBuilder
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder();
        /** @var ArrayNodeDefinition $rootNode */
        $rootNode = $treeBuilder->root('hbpf');

        $rootNode->children()
            ->arrayNode('connectors');

        return $treeBuilder;
    }

}
