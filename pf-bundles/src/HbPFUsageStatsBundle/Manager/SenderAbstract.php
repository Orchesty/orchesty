<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFUsageStatsBundle\Manager;

use DateTime;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\MongoDBException;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\CommonsBundle\Transport\CurlManagerInterface;
use Hanaboso\PipesFramework\UsageStats\Document\HearthBeatData;
use Hanaboso\PipesFramework\UsageStats\Document\UsageStatsEvent;
use Hanaboso\PipesFramework\UsageStats\Enum\EventTypeEnum;
use Hanaboso\PipesFramework\UsageStats\Enum\HeartBeatTypeEnum;
use Hanaboso\PipesFramework\UsageStats\Repository\UsageStatsEventRepository;
use Hanaboso\Utils\Exception\DateTimeException;
use Hanaboso\Utils\String\Json;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class SenderAbstract
 *
 * @package Hanaboso\PipesFramework\HbPFUsageStatsBundle\Manager
 */
abstract class SenderAbstract implements SenderInterface
{

    protected const USCCP_URI = 'https://usccp.cloud.orchesty.io';

    protected const BATCH_SIZE            = 100;
    protected const BATCH_TIME_LIMIT      = 45;
    protected const BATCH_SEND_AGAIN_TIME = 5;

    /**
     * SenderAbstract constructor.
     *
     * @param DocumentManager      $dm
     * @param CurlManagerInterface $curlManager
     * @param mixed[]              $types
     */
    public function __construct(
        private readonly DocumentManager $dm,
        private readonly CurlManagerInterface $curlManager,
        private readonly array $types,
    )
    {
    }

    /**
     * @param string                    $alphaInstanceId
     * @param UsageStatsEventRepository $usageStatsEventRepository
     * @param OutputInterface           $output
     *
     * @return int
     * @throws CurlException
     * @throws DateTimeException
     * @throws MongoDBException
     */
    public function send(
        string $alphaInstanceId,
        UsageStatsEventRepository $usageStatsEventRepository,
        OutputInterface $output,
    ): int
    {
        $startDateTime = new DateTime();
        $startTime     = $startDateTime->getTimestamp();

        $startHearthBeat = (new UsageStatsEvent($alphaInstanceId, EventTypeEnum::HEARTHBEAT->value))
            ->setHeartBeatData(
                new HearthBeatData(
                    $usageStatsEventRepository->getRemainingEventCount($startDateTime),
                    HeartBeatTypeEnum::START->value,
                ),
            );

        if ($this->sendRequest($startHearthBeat, $startTime, $output)) {
            $events = $usageStatsEventRepository->findBillingEventsByTypesForSender(
                $startDateTime,
                self::BATCH_SIZE,
                $this->types,
            );
            while ($events) {
                foreach ($events as $billingEvent) {
                    if (time() - $startTime > self::BATCH_TIME_LIMIT) {
                        $output->writeln('Timeout limit reached.');
                        $this->dm->flush();

                        return 0;
                    }
                    $res = $this->sendRequest($billingEvent, $startTime, $output);
                    if ($res) {
                        $billingEvent->setSent(time());
                    } else {
                        return 0;
                    }
                }
                $this->dm->flush();

                $events = $usageStatsEventRepository->findBillingEventsByTypesForSender(
                    $startDateTime,
                    self::BATCH_SIZE,
                    $this->types,
                );
            }
        } else {
            return 0;
        }

        $endHearthBeat = (new UsageStatsEvent($alphaInstanceId, EventTypeEnum::HEARTHBEAT->value))
            ->setHeartBeatData(
                new HearthBeatData(
                    $usageStatsEventRepository->getRemainingEventCount($startDateTime),
                    HeartBeatTypeEnum::END->value,
                ),
            );
        $this->sendRequest($endHearthBeat, $startTime, $output);

        $output->writeln('All events sent successfully');

        return 0;
    }

    /**
     * @param UsageStatsEvent $billingEvent
     * @param int             $startTime
     * @param OutputInterface $output
     *
     * @return bool
     * @throws CurlException
     */
    private function sendRequest(UsageStatsEvent $billingEvent, int $startTime, OutputInterface $output): bool
    {
        $dto = new RequestDto(new Uri(self::USCCP_URI), CurlManager::METHOD_PUT, new ProcessDto(), '', [
            'Content-Type' => 'application/json',
        ]);
        $dto->setBody(Json::encode($billingEvent->toArray()));
        try {
            $response = $this->curlManager->send($dto);

            return $response->getStatusCode() < 300;
        } catch (CurlException) {
            if (time() + self::BATCH_SEND_AGAIN_TIME - $startTime < self::BATCH_TIME_LIMIT) {
                sleep(self::BATCH_SEND_AGAIN_TIME);

                return $this->sendRequest($billingEvent, $startTime, $output);
            } else {
                $output->writeln('Problem with sending events!');

                return FALSE;
            }
        }
    }

}
