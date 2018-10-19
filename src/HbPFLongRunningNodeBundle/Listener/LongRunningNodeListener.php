<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFLongRunningNodeBundle\Listener;

use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\PipesFramework\Configurator\Event\ProcessStatusEvent;
use Hanaboso\PipesFramework\LongRunningNode\Document\LongRunningNodeData;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class LongRunningNodeListener
 *
 * @package Hanaboso\PipesFramework\HbPFLongRunningNodeBundle\Listener
 */
final class LongRunningNodeListener implements EventSubscriberInterface
{

    /**
     * @var DocumentManager
     */
    private $dm;

    /**
     * LongRunningNodeListener constructor.
     *
     * @param DocumentManager $dm
     */
    public function __construct(DocumentManager $dm)
    {
        $this->dm = $dm;
    }

    /**
     * @param ProcessStatusEvent $event
     */
    public function onFinish(ProcessStatusEvent $event): void
    {
        if ($event->getStatus()) {
            $repo       = $this->dm->getRepository(LongRunningNodeData::class);
            $processIds = [$event->getProcessId()];
            $removedIds = [];

            while (!empty($processIds)) {
                $processId = array_pop($processIds);
                /** @var LongRunningNodeData|null $doc */
                $doc = $repo->findOneBy(['processId' => $processId]);

                if ($doc) {
                    if (!in_array($doc->getId(), $removedIds)) {
                        $processIds[] = $doc->getParentProcess();
                        $removedIds[] = $doc->getId();
                        $this->dm->remove($doc);
                    }
                }
            }

            $this->dm->flush();
        }
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents(): array
    {
        return [
            ProcessStatusEvent::PROCESS_FINISHED => 'onFinish',
        ];
    }

}