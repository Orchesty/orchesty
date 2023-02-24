<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\UsageStats\Listener;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\MongoDBException;
use Hanaboso\CommonsBundle\Database\Locator\DatabaseManagerLocator;
use Hanaboso\PipesFramework\UsageStats\Document;
use Hanaboso\PipesFramework\UsageStats\Enum\EventTypeEnum;
use Hanaboso\PipesFramework\UsageStats\Event\BillingEvent;
use Hanaboso\Utils\Exception\DateTimeException;
use Hanaboso\Utils\Exception\EnumException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class UsageStatsEventListener
 *
 * @package Hanaboso\PipesFramework\UsageStats\Listener
 */
final class UsageStatsEventListener implements EventSubscriberInterface
{

    /**
     * @var DocumentManager $dm
     */
    private DocumentManager $dm;

    /**
     * UsageStatsEventListener constructor.
     *
     * @param DatabaseManagerLocator $dml
     * @param string                 $alphaInstanceId
     */
    public function __construct(DatabaseManagerLocator $dml, private string $alphaInstanceId)
    {
        /** @var DocumentManager $dm */
        $dm       = $dml->get();
        $this->dm = $dm;
    }

    /**
     * @param BillingEvent $event
     *
     * @return void
     * @throws MongoDBException
     * @throws EnumException
     * @throws DateTimeException
     */
    public function onProcessBillingEvent(BillingEvent $event): void
    {
        if (!EventTypeEnum::tryFrom($event->getType())) {
            throw new EnumException();
        }
        $billingEvent = Document\UsageStatsEvent::createFromBillingEvent($this->alphaInstanceId, $event);
        $this->dm->persist($billingEvent);
        $this->dm->flush();
    }

    /**
     * @return array<string, array<int|string, array<int|string, int|string>|int|string>|string>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            BillingEvent::NAME => 'onProcessBillingEvent',
        ];
    }

}
