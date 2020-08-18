<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFLogsBundle\DependencyInjection\Compiler;

use Hanaboso\PipesFramework\HbPFLogsBundle\HbPFLogsBundle;
use Hanaboso\PipesFramework\Logs\ElasticLogs;
use Hanaboso\PipesFramework\Logs\LogsFilter;
use Hanaboso\PipesFramework\Logs\LogsInterface;
use Hanaboso\PipesFramework\Logs\MongoDbLogs;
use Hanaboso\PipesFramework\Logs\StartingPointsFilter;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

/**
 * Class LogsCompilerPass
 *
 * @package Hanaboso\PipesFramework\HbPFLogsBundle\DependencyInjection\Compiler
 *
 * @codeCoverageIgnore
 */
final class LogsCompilerPass implements CompilerPassInterface
{

    /**
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container): void
    {
        $config = $container->getParameter(HbPFLogsBundle::KEY);

        $dm                   = $container->getDefinition('doctrine_mongodb.odm.default_document_manager');
        $startingPointsFilter = new Definition(StartingPointsFilter::class, [$dm]);

        if ($config['type'] == 'mongodb') {
            $logsFilter = new Definition(LogsFilter::class, [$dm]);
            $mongoDb    = new Definition(MongoDbLogs::class, [$dm, $logsFilter, $startingPointsFilter]);

            $container->setDefinition(LogsInterface::class, $mongoDb);
        }

        if ($config['type'] == 'elastic') {
            $elastic = (new Definition(
                ElasticLogs::class,
                [
                    $dm,
                    $startingPointsFilter,
                    $container->getDefinition('elastica.client'),
                ]
            ))->addMethodCall('setIndex', [$config['storage_name']]);

            $container->setDefinition(LogsInterface::class, $elastic);
        }
    }

}
