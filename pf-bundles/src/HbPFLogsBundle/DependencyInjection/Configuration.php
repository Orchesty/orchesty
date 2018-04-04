<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFLogsBundle\DependencyInjection;

use Hanaboso\PipesFramework\HbPFLogsBundle\HbPFLogsBundle;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/configuration.html}
 */
class Configuration implements ConfigurationInterface
{

    /**
     * @return TreeBuilder
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder();
        $rootNode    = $treeBuilder->root(HbPFLogsBundle::KEY);

        $rootNode->children()
            ->enumNode("type")->values(['mongodb', 'elastic'])->isRequired()
            ->end();

        $rootNode->children()
            ->scalarNode("storage_name")->isRequired()
            ->info('Set name of mongodb database or elastic index.')
            ->end();

        return $treeBuilder;
    }

}
