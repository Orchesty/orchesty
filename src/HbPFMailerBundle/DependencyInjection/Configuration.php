<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFMailerBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\ScalarNode;

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
        $rootNode    = $treeBuilder->root('hb_pf_mailer');

        $defaultNode = $rootNode->children()->arrayNode("default_values")->normalizeKeys(FALSE)->cannotBeEmpty()->isRequired();
        $from        = $defaultNode->children()->arrayNode('from');
        $from->children()->scalarNode('user_manager')->isRequired();

        $subject = $defaultNode->children()->arrayNode('subject')->normalizeKeys(FALSE)->cannotBeEmpty()->isRequired();
        $subject->children()->scalarNode('user_manager')->isRequired();

        $to = $defaultNode->children()->arrayNode('to')->isRequired();

        $bcc = $defaultNode->children()->arrayNode('bcc')->isRequired();

        return $treeBuilder;
    }

    public function addSubModul()
    {

    }

}
