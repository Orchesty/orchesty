<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Listeners;

use CleverConnectors\AppBundle\Enum\ProgressCounterStatusEnum;
use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\ProgressCounter\ProgressCounterService;
use Hanaboso\PipesFramework\Configurator\Event\ProcessStatusEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class ProgressCounterListener
 *
 * @package CleverConnectors\AppBundle\Listeners
 */
class ProgressCounterListener implements EventSubscriberInterface
{

    /**
     * @var ProgressCounterService
     */
    private $counter;

    /**
     * StatusServiceListener constructor.
     *
     * @param ProgressCounterService $counter
     */
    function __construct(ProgressCounterService $counter)
    {
        $this->counter = $counter;
    }

    /**
     * @param ProcessStatusEvent $ev
     *
     * @throws CleverConnectorsException
     */
    public function updateStatus(ProcessStatusEvent $ev): void
    {
        $this->counter->setStatus(
            $ev->getProcessId(),
            ProgressCounterStatusEnum::from($ev->getStatus())
        );
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents(): array
    {
        return [
            ProcessStatusEvent::PROCESS_FINISHED => 'updateStatus',
        ];
    }

}