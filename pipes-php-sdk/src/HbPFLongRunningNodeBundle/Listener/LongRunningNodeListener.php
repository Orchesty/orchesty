<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\HbPFLongRunningNodeBundle\Listener;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\MongoDBException;
use Hanaboso\CommonsBundle\Event\ProcessStatusEvent;
use Hanaboso\PipesPhpSdk\LongRunningNode\Document\LongRunningNodeData;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class LongRunningNodeListener
 *
 * @package Hanaboso\PipesPhpSdk\HbPFLongRunningNodeBundle\Listener
 *
 * @codeCoverageIgnore
 */
final class LongRunningNodeListener implements EventSubscriberInterface
{

    /**
     * @var DocumentManager
     */
    private DocumentManager $dm;

    /**
     * @var bool
     */
    private bool $enabled;

    /**
     * LongRunningNodeListener constructor.
     *
     * @param DocumentManager $dm
     * @param bool            $enabled
     */
    public function __construct(DocumentManager $dm, bool $enabled)
    {
        $this->dm      = $dm;
        $this->enabled = $enabled;
    }

    /**
     * @param ProcessStatusEvent $event
     *
     * @throws MongoDBException
     */
    public function onFinish(ProcessStatusEvent $event): void
    {
        if ($event->getStatus() && $this->enabled) {
            $repo       = $this->dm->getRepository(LongRunningNodeData::class);
            $processIds = [$event->getProcessId()];
            $removedIds = [];

            while ($processIds) {
                $document = $repo->getProcessed((string) array_pop($processIds));

                if ($document) {
                    if (!in_array($document->getId(), $removedIds, TRUE)) {
                        $processIds[] = $document->getParentProcess();
                        $removedIds[] = $document->getId();
                        $this->dm->remove($document);
                    }
                }
            }

            $this->dm->flush();
        }
    }

    /**
     * @return array<string, array<int|string, array<int|string, int|string>|int|string>|string>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            ProcessStatusEvent::PROCESS_FINISHED => 'onFinish',
        ];
    }

}
