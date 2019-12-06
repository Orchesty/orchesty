<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFLogsBundle\DependencyInjection;

use Hanaboso\PipesFramework\HbPFLogsBundle\HbPFLogsBundle;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Class Configuration
 *
 * @package Hanaboso\PipesFramework\HbPFLogsBundle\DependencyInjection
 * @codeCoverageIgnore
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
        $rootNode = $treeBuilder->root(HbPFLogsBundle::KEY);

        $rootNode->children()
            ->enumNode('type')->values(['mongodb', 'elastic'])->isRequired()
            ->end();

        $rootNode->children()
            ->scalarNode('storage_name')->isRequired()
            ->info('Set name of mongodb database or elastic index.')
            ->end();

        return $treeBuilder;
    }

}
