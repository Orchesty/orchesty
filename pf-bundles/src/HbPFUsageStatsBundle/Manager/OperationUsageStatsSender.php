<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFUsageStatsBundle\Manager;

use DateTime;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\MongoDBException;
use Hanaboso\CommonsBundle\Transport\CurlManagerInterface;
use Hanaboso\PipesFramework\Configurator\Document\TopologyProgress;
use Hanaboso\PipesFramework\UsageStats\Document\OperationBillingData;
use Hanaboso\PipesFramework\UsageStats\Document\UsageStatsEvent;
use Hanaboso\PipesFramework\UsageStats\Enum\EventTypeEnum;
use Hanaboso\Utils\Exception\DateTimeException;

/**
 * Class OperationUsageStatsSender
 *
 * @package Hanaboso\PipesFramework\HbPFUsageStatsBundle\Manager
 */
class OperationUsageStatsSender extends SenderAbstract
{

    /**
     * OperationUsageStatsSender constructor.
     *
     * @param DocumentManager      $dm
     * @param CurlManagerInterface $curlManager
     */
    public function __construct(private readonly DocumentManager $dm, CurlManagerInterface $curlManager)
    {
        parent::__construct(
            $dm,
            $curlManager,
            [EventTypeEnum::OPERATION->value],
        );
    }

    /**
     * @param string $alphaInstanceId
     *
     * @return void
     * @throws DateTimeException
     * @throws MongoDBException
     */
    public function generateOperationEvents(string $alphaInstanceId,): void
    {
        $currentDate               = new DateTime();
        $usageStatsEventRepository = $this->dm->getRepository(UsageStatsEvent::class);
        /** @var UsageStatsEvent[] $lastEvent */
        $lastEvent = $usageStatsEventRepository->findBy(
            ['type' => EventTypeEnum::OPERATION->value],
            ['created' => 'DESC'],
            1,
        );

        $multiCounterRepository = $this->dm->getRepository(TopologyProgress::class);

        $operations = $multiCounterRepository->getDataForOperationEventSending(
            !empty($lastEvent[0]) ? $lastEvent[0]->getCreated() : (new DateTime())->setTimestamp(1),
        );

        $days             = array_map(static fn($item) => $item['_id'], $operations);
        $usageStatsEvents = $usageStatsEventRepository->getExistingProcessCountByDay($days);
        foreach ($operations as $operation) {
            $day   = $operation['_id'];
            $total = $operation['total'] - (!empty($usageStatsEvents[$day]) ? $usageStatsEvents[$day] : 0);
            if (!empty($total)) {
                $event = new UsageStatsEvent($alphaInstanceId, EventTypeEnum::OPERATION->value);
                $event->setCreated($currentDate);
                $event->setOperationBillingData(new OperationBillingData($day, $total));
                $this->dm->persist($event);
            }
        }

        $this->dm->flush();
    }

}
