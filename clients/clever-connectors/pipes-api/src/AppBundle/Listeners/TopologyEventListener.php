<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Listeners;

use CleverConnectors\AppBundle\Handler\TopologyHandler;
use CleverConnectors\AppBundle\Model\Systems\Exceptions\SystemException;
use Hanaboso\PipesFramework\Configurator\Event\TopologyEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class TopologyEventListener
 *
 * @package CleverConnectors\AppBundle\Listeners
 */
class TopologyEventListener implements EventSubscriberInterface
{

    /**
     * @var TopologyHandler
     */
    private $handler;

    /**
     * TopologyEventListener constructor.
     *
     * @param TopologyHandler $handler
     */
    function __construct(TopologyHandler $handler)
    {
        $this->handler = $handler;
    }

    /**
     * @param TopologyEvent $event
     *
     * @throws SystemException
     */
    public function unsubscribeWebhooks(TopologyEvent $event): void
    {
        $this->handler->deleteWebhooksByTopologyName($event->getTopologyName());
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents(): array
    {
        return [
            TopologyEvent::EVENT => 'unsubscribeWebhooks',
        ];
    }

}