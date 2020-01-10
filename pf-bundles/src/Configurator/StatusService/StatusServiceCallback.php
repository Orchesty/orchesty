<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Configurator\StatusService;

use Hanaboso\CommonsBundle\Event\ProcessStatusEvent;
use Hanaboso\CommonsBundle\Exception\PipesFrameworkException;
use Hanaboso\CommonsBundle\Utils\Json;
use PhpAmqpLib\Message\AMQPMessage;
use RabbitMqBundle\Connection\Connection;
use RabbitMqBundle\Consumer\CallbackInterface;
use RabbitMqBundle\Utils\Message;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Class StatusServiceCallback
 *
 * @package Hanaboso\PipesFramework\Configurator\StatusService
 */
class StatusServiceCallback implements CallbackInterface
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
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @param AMQPMessage $message
     * @param Connection  $connection
     * @param int         $channelId
     *
     * @throws PipesFrameworkException
     */
    public function processMessage(AMQPMessage $message, Connection $connection, int $channelId): void
    {
        $data = Json::decode(Message::getBody($message));

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

        /** @var EventDispatcher $ed */
        $ed = $this->eventDispatcher;
        $ed->dispatch(
            new ProcessStatusEvent($data['process_id'], (bool) $data['success']),
            ProcessStatusEvent::PROCESS_FINISHED
        );

        Message::ack($message, $connection, $channelId);
    }

}
