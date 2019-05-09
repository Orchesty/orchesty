<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Configurator\StatusService;

use Bunny\Message;
use Hanaboso\CommonsBundle\Exception\PipesFrameworkException;
use Hanaboso\PipesFramework\Configurator\Event\ProcessStatusEvent;
use Hanaboso\PipesFramework\RabbitMq\CallbackStatus;
use Hanaboso\PipesFramework\RabbitMq\Consumer\SyncCallbackAbstract;
use RabbitMqBundle\Connection\Connection;
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
     * @throws PipesFrameworkException
     */
    public function handle($data, Message $message): CallbackStatus
    {
        $message;

        if (!isset($data['process_id'])) {
            throw new PipesFrameworkException(
                'Missing message\'s content in StatusServiceCallback [process_id].',
                PipesFrameworkException::REQUIRED_PARAMETER_NOT_FOUND
            );
        }

        if (!isset($data['success'])) {
            throw new PipesFrameworkException(
                'Missing message\'s content in StatusServiceCallback [success].',
                PipesFrameworkException::REQUIRED_PARAMETER_NOT_FOUND
            );
        }

        $event = new ProcessStatusEvent($data['process_id'], (bool) $data['success']);

        $this->eventDispatcher->dispatch(ProcessStatusEvent::PROCESS_FINISHED, $event);

        return new CallbackStatus(CallbackStatus::SUCCESS);
    }

    /**
     * @param Message    $message
     * @param Connection $connection
     * @param int        $channelId
     */
    public function processMessage(Message $message, Connection $connection, int $channelId): void
    {
        $message;
        $connection;
        $channelId;

        // Not needed, used handle method instead...
    }

}
