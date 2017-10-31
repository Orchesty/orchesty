<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Configurator\StatusService;

use Bunny\Message;
use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
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
     *
     * @throws CleverConnectorsException
     */
    function handle($data, Message $message): CallbackStatus
    {
        if (!isset($data['process_id'])) {
            throw new CleverConnectorsException(
                'Missing message\'s content in StatusServiceCallback [process_id]. ',
                CleverConnectorsException::MISSING_DATA
            );
        }

        if (!isset($data['success'])) {
            throw new CleverConnectorsException(
                'Missing message\'s content in StatusServiceCallback [success].',
                CleverConnectorsException::MISSING_DATA
            );
        }

        $event = new ProcessStatusEvent($data['process_id'], (bool) $data['success']);

        $this->eventDispatcher->dispatch(ProcessStatusEvent::PROCESS_FINISHED, $event);

        return new CallbackStatus(CallbackStatus::SUCCESS);
    }

}