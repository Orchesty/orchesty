<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFNotificationBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Class Configuration
 *
 * @package Hanaboso\PipesFramework\HbPFNotificationBundle\DependencyInjection
 *
 * @codeCoverageIgnore
 */
class Configuration implements ConfigurationInterface
{

    /**
     * @return TreeBuilder
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('hbpf');
        /** @var ArrayNodeDefinition $root */
        $root = $treeBuilder->getRootNode();
        $root->children()->arrayNode('notification');

        return $treeBuilder;
    }

}
