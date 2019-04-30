<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFApplicationBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Class Configuration
 *
 * @package Hanaboso\PipesFramework\HbPFApplicationBundle\DependencyInjection
 */
class Configuration implements ConfigurationInterface
{

    /**
     * @return TreeBuilder
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder();
        $rootNode    = $treeBuilder->root('hbpf');

        $rootNode->children()
            ->arrayNode('application');

        return $treeBuilder;
    }

}