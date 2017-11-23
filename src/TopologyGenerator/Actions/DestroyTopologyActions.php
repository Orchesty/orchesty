<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: sep
 * Date: 11.10.17
 * Time: 16:15
 */

namespace Hanaboso\PipesFramework\TopologyGenerator\Actions;

use Hanaboso\PipesFramework\Commons\Docker\Handler\DockerHandler;
use Hanaboso\PipesFramework\Configurator\Document\Node;
use Hanaboso\PipesFramework\Configurator\Document\Topology;
use Hanaboso\PipesFramework\Configurator\StartingPoint\StartingPoint;
use Hanaboso\PipesFramework\RabbitMq\Handler\RabbitMqHandler;
use Hanaboso\PipesFramework\TopologyGenerator\DockerCompose\Generator;

/**
 * Class DestroyTopology
 *
 * @package Hanaboso\PipesFramework\TopologyGenerator\Actions
 */
class DestroyTopologyActions extends ActionsAbstract
{

    /**
     * @var RabbitMqHandler
     */
    protected $rabbitMqHandler;

    /**
     * DestroyTopology constructor.
     *
     * @param DockerHandler   $dockerHandler
     * @param RabbitMqHandler $rabbitMqHandler
     * @param string          $mode
     */
    public function __construct(DockerHandler $dockerHandler, RabbitMqHandler $rabbitMqHandler, string $mode)
    {
        parent::__construct($dockerHandler, $mode);
        $this->rabbitMqHandler = $rabbitMqHandler;
    }

    /**
     * @param Topology $topology
     * @param Node[]   $nodes
     */
    public function deleteQueues(Topology $topology, array $nodes): void
    {
        $queues   = [];
        $queues[] = StartingPoint::createCounterQueueName($topology);

        foreach ($nodes as $node) {
            $queues[] = StartingPoint::createQueueName($topology, $node);
        }

        if (count($queues)) {
            $this->rabbitMqHandler->deleteQueues($queues);
        }

        $this->rabbitMqHandler->deleteExchange(StartingPoint::createExchangeName($topology));
    }

    /**
     * @param Topology $topology
     * @param string   $dstDirectory
     * @param string   $topologyPrefix
     *
     * @return bool
     */
    public function deleteTopologyDir(Topology $topology, string $dstDirectory, string $topologyPrefix): bool
    {
        $dstTopologyDirectory = Generator::getTopologyDir($topology, $dstDirectory);
        $cli                  = $this->getDockerComposeCli($dstTopologyDirectory, $topologyPrefix);

        return $cli->destroy();
    }

}
