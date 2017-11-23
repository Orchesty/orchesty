<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: Pavel Severyn
 * Date: 11.10.17
 * Time: 17:48
 */

namespace Hanaboso\PipesFramework\TopologyGenerator\Actions;

use Hanaboso\PipesFramework\Commons\Docker\Handler\DockerHandler;
use Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\GeneratorHandler;
use Hanaboso\PipesFramework\RabbitMq\Handler\RabbitMqHandler;
use Hanaboso\PipesFramework\TopologyGenerator\DockerCompose\VolumePathDefinitionFactory;
use Hanaboso\PipesFramework\TopologyGenerator\Exception\TopologyGeneratorException;

/**
 * Class TopologyActionsFactory
 *
 * @package Hanaboso\PipesFramework\TopologyGenerator\Actions
 */
class TopologyActionsFactory
{

    public const START    = 'start';
    public const STOP     = 'stop';
    public const DESTROY  = 'destroy';
    public const GENERATE = 'generate';

    /**
     * @var StartTopologyActions|null
     */
    protected $startAction = NULL;

    /**
     * @var StopTopologyActions|null
     */
    protected $stopAction = NULL;

    /**
     * @var DestroyTopologyActions|null
     */
    protected $destroyAction = NULL;

    /**
     * @var GenerateTopologyActions|null
     */
    protected $generateAction = NULL;

    /**
     * @var DockerHandler
     */
    protected $dockerHandler;

    /**
     * @var RabbitMqHandler
     */
    protected $rabbitMqHandler;

    /**
     * @var VolumePathDefinitionFactory
     */
    protected $volumePathDefinitionFactory;

    /**
     * DestroyTopology constructor.
     *
     * @param DockerHandler               $dockerHandler
     * @param RabbitMqHandler             $rabbitMqHandler
     * @param VolumePathDefinitionFactory $volumePathDefinitionFactory
     */
    public function __construct(
        DockerHandler $dockerHandler,
        RabbitMqHandler $rabbitMqHandler,
        VolumePathDefinitionFactory $volumePathDefinitionFactory
    )
    {
        $this->dockerHandler               = $dockerHandler;
        $this->rabbitMqHandler             = $rabbitMqHandler;
        $this->volumePathDefinitionFactory = $volumePathDefinitionFactory;
    }

    /**
     * @param string $action
     * @param string $mode
     *
     * @return ActionsAbstract
     * @throws TopologyGeneratorException
     */
    public function getTopologyAction(string $action, string $mode = GeneratorHandler::MODE_SWARM): ActionsAbstract
    {
        if ($action == self::START) {
            if (!$this->startAction) {
                $this->startAction = new StartTopologyActions($this->dockerHandler, $mode);
            }

            return $this->startAction;
        } elseif ($action == self::STOP) {
            if (!$this->stopAction) {
                $this->stopAction = new StopTopologyActions($this->dockerHandler, $mode);
            }

            return $this->stopAction;
        } elseif ($action == self::DESTROY) {
            if (!$this->destroyAction) {
                $this->destroyAction = new DestroyTopologyActions($this->dockerHandler, $this->rabbitMqHandler, $mode);
            }

            return $this->destroyAction;
        } elseif ($action == self::GENERATE) {
            if (!$this->generateAction) {
                $this->generateAction = new GenerateTopologyActions(
                    $this->dockerHandler,
                    $this->volumePathDefinitionFactory,
                    $mode
                );
            }

            return $this->generateAction;
        } else {
            throw new TopologyGeneratorException(
                sprintf('Topology action %s not found', $action),
                TopologyGeneratorException::TOPOLOGY_ACTION_FOUND
            );
        }
    }

}
