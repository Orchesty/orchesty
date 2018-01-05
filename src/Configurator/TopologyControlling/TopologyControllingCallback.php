<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: pavel.severyn
 * Date: 12.12.17
 * Time: 11:50
 */

namespace Hanaboso\PipesFramework\Configurator\TopologyControlling;

use Bunny\Message;
use Hanaboso\PipesFramework\Configurator\TopologyControlling\Messages\TopologyMessage;
use Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\GeneratorHandler;
use Hanaboso\PipesFramework\RabbitMq\CallbackStatus;
use Hanaboso\PipesFramework\RabbitMq\Consumer\SyncCallbackAbstract;
use Hanaboso\PipesFramework\TopologyGenerator\Exception\TopologyGeneratorException;
use Http\Client\Exception;

/**
 * Class TopologyControllingCallback
 *
 * @package Hanaboso\PipesFramework\Configurator\TopologyControlling
 */
class TopologyControllingCallback extends SyncCallbackAbstract
{

    /**
     * @var GeneratorHandler
     */
    protected $generatorHandler;

    /**
     * TopologyControllingCallback constructor.
     *
     * @param GeneratorHandler $generatorHandler
     */
    public function __construct(GeneratorHandler $generatorHandler)
    {
        parent::__construct();
        $this->generatorHandler = $generatorHandler;
    }

    /**
     * @param mixed   $data
     * @param Message $message
     *
     * @return CallbackStatus
     */
    function handle($data, Message $message): CallbackStatus
    {
        if ($this->generatorHandler && array_key_exists('topologyId', $data) && array_key_exists('action', $data)) {

            switch ($data['action']) {
                case TopologyMessage::DELETE:
                    try {
                        $this->generatorHandler->stopTopology($data['topologyId']);
                        $this->generatorHandler->destroyTopology($data['topologyId']);

                        return new CallbackStatus(CallbackStatus::SUCCESS);
                    } catch (Exception | TopologyGeneratorException $e) {
                        return new CallbackStatus(CallbackStatus::RESEND);
                    }
                    break;
                case TopologyMessage::STOP:
                    try {
                        $this->generatorHandler->stopTopology($data['topologyId']);

                        return new CallbackStatus(CallbackStatus::SUCCESS);
                    } catch (Exception | TopologyGeneratorException $e) {
                        return new CallbackStatus(CallbackStatus::RESEND);
                    }
                    break;
            }
        }

        return new CallbackStatus(CallbackStatus::FAILED);
    }

}
