<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFUserTaskBundle\DependencyInjection;

use Hanaboso\PipesFramework\HbPFUserTaskBundle\HbPFUserTaskBundle;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Class Configuration
 *
 * @package Hanaboso\PipesFramework\HbPFUserTaskBundle\DependencyInjection
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
        return new TreeBuilder(HbPFUserTaskBundle::KEY);
    }

}
