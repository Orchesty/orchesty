<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFMailerBundle\DependencyInjection;

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
        $rootNode    = $treeBuilder->root('hbpf');

        $mailerNode =  $rootNode->children()->arrayNode('mailer');

        $defaultNode = $mailerNode->children()->arrayNode("default_values")->normalizeKeys(FALSE)->isRequired();
        $from        = $defaultNode->children()->arrayNode('from');
        $from->children()->scalarNode('user_manager')->isRequired()->cannotBeEmpty();

        $subject = $defaultNode->children()->arrayNode('subject')->normalizeKeys(FALSE)->isRequired();
        $subject->children()->scalarNode('user_manager')->isRequired();

        $defaultNode->children()->arrayNode('to')->isRequired();

        $defaultNode->children()->arrayNode('bcc')->isRequired();

        return $treeBuilder;
    }

}
