<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\HbPFTableParserBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Class Configuration
 *
 * @package Hanaboso\PipesPhpSdk\HbPFTableParserBundle\DependencyInjection
 *
 * @codeCoverageIgnore
 */
final class Configuration implements ConfigurationInterface
{

    /**
     * @return TreeBuilder
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('hbpf');
        /** @var ArrayNodeDefinition $rootNode */
        $rootNode = $treeBuilder->getRootNode();
        $rootNode->children()->arrayNode('tableParser');

        return $treeBuilder;
    }

}
