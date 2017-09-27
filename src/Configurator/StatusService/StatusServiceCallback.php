<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Configurator\StatusService;

use Bunny\Message;
use Hanaboso\PipesFramework\Configurator\Event\ProcessStatusEvent;
use Hanaboso\PipesFramework\RabbitMq\CallbackStatus;
use Hanaboso\PipesFramework\RabbitMq\Consumer\SyncCallbackAbstract;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class StatusServiceCallback
 *
 * @package Hanaboso\PipesFramework\Configurator\StatusService
 */
class StatusServiceCallback extends SyncCallbackAbstract
{

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * StatusServiceCallback constructor.
     *
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(EventDispatcherInterface $eventDispatcher)
    {
        parent::__construct();

        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @param mixed   $data
     * @param Message $message
     *
     * @return CallbackStatus
     */
    function handle($data, Message $message): CallbackStatus
    {
        $this->eventDispatcher->dispatch(ProcessStatusEvent::PROCESS_FINISHED, new ProcessStatusEvent($data));

        return new CallbackStatus(CallbackStatus::SUCCESS);
    }

}