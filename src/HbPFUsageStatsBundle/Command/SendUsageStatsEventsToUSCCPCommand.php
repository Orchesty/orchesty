<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFUsageStatsBundle\Command;

use DateTime;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\MongoDBException;
use Doctrine\Persistence\ObjectRepository;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\CommonsBundle\Database\Locator\DatabaseManagerLocator;
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
use Hanaboso\Utils\String\Json;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class SendUsageStatsEventsToUSCCPCommand
 *
 * @package Hanaboso\PipesFramework\HbPFUsageStatsBundle\Command
 */
final class SendUsageStatsEventsToUSCCPCommand extends Command
{

    private const CMD_NAME = 'usage_stats:send-events';

    private const BATCH_SIZE            = 100;
    private const BATCH_TIME_LIMIT      = 45;
    private const BATCH_SEND_AGAIN_TIME = 5;

    /**
     * @var ObjectRepository<UsageStatsEvent>&UsageStatsEventRepository
     */
    private UsageStatsEventRepository $usageStatsRepository;

    /**
     * @var DocumentManager $dm
     */
    private DocumentManager $dm;

    /**
     * SendUsageStatsEventsToUSCCPCommand constructor.
     *
     * @param DatabaseManagerLocator $dml
     * @param string                 $usccpUri
     * @param string                 $alphaInstanceId
     * @param CurlManagerInterface   $curlManager
     */
    public function __construct(
        DatabaseManagerLocator $dml,
        private string $usccpUri,
        private string $alphaInstanceId,
        private CurlManagerInterface $curlManager,
    )
    {
        parent::__construct(self::CMD_NAME);

        /** @var DocumentManager $dm */
        $dm                         = $dml->getDm();
        $this->dm                   = $dm;
        $this->usageStatsRepository = $this->dm->getRepository(UsageStatsEvent::class);
    }

    /**
     *
     */
    protected function configure(): void
    {
        $this
            ->setName(self::CMD_NAME)
            ->setDescription('Send billing events to USCCP');
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
     * @throws MongoDBException
     * @throws CurlException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $input;

        $startDateTime = new DateTime();
        $startTime     = $startDateTime->getTimestamp();

        $startHearthBeat = (new UsageStatsEvent($this->alphaInstanceId, EventTypeEnum::HEARTHBEAT))
            ->setHeartBeatData(
                new HearthBeatData(
                    $this->usageStatsRepository->getRemainingEventCount($startDateTime),
                    HeartBeatTypeEnum::START,
                ),
            );

        if ($this->sendRequest($startHearthBeat, $startTime, $output)) {
            while ($events = $this->usageStatsRepository->findBillingEvents($startDateTime, self::BATCH_SIZE)) {
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
            }
        } else {
            return 0;
        }

        $endHearthBeat = (new UsageStatsEvent($this->alphaInstanceId, EventTypeEnum::HEARTHBEAT))
            ->setHeartBeatData(
                new HearthBeatData(
                    $this->usageStatsRepository->getRemainingEventCount($startDateTime),
                    HeartBeatTypeEnum::END,
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
        $dto = new RequestDto(new Uri($this->usccpUri), CurlManager::METHOD_PUT, new ProcessDto(), '', [
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
