<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: venca
 * Date: 3/20/18
 * Time: 9:38 AM
 */

namespace Hanaboso\PipesFramework\HbPFLogsBundle\DependencyInjection\Compiler;

use Hanaboso\PipesFramework\HbPFLogsBundle\HbPFLogsBundle;
use Hanaboso\PipesFramework\Logs\ElasticLogs;
use Hanaboso\PipesFramework\Logs\LogsInterface;
use Hanaboso\PipesFramework\Logs\MongoDbLogs;
use Hanaboso\PipesFramework\Logs\MongoDbStorage;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

/**
 * Class LogsCompilerPass
 *
 * @package Hanaboso\PipesFramework\HbPFLogsBundle\DependencyInjection\Compiler
 */
class LogsCompilerPass implements CompilerPassInterface
{

    /**
     * You can modify the container here before it is dumped to PHP code.
     *
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container): void
    {
        $config = $container->getParameter(HbPFLogsBundle::KEY);

        if ($config['type'] == 'mongodb') {

            $mongoDbStorage = new Definition(MongoDbStorage::class, [
                $container->getDefinition('doctrine_mongodb.odm.default_document_manager'),
                $config['storage_name'],
            ]);
            $mongoDb        = new Definition(MongoDbLogs::class, [$mongoDbStorage]);

            $container->setDefinition(LogsInterface::class, $mongoDb);
        }

        if ($config['type'] == 'elastic') {
            $elastic = new Definition(ElasticLogs::class, [
                //@todo add elastic manager
                $config['storage_name'],
            ]);

            $container->setDefinition(LogsInterface::class, $elastic);
        }

    }

}