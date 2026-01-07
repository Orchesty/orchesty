<?php declare(strict_types=1);

namespace Hanaboso\PipesFrameworkEnterprise\HbPFMcpBundle\DependencyInjection;

use Hanaboso\PipesFrameworkEnterprise\HbPFMcpBundle\HbPFMcpBundle;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Class Configuration
 *
 * @package Hanaboso\PipesFrameworkEnterprise\HbPFMcpBundle\DependencyInjection
 *
 * @codeCoverageIgnore
 */
final class Configuration implements ConfigurationInterface
{

    /**
     * @return TreeBuilder<'array'>
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        return new TreeBuilder(HbPFMcpBundle::KEY);
    }

}
