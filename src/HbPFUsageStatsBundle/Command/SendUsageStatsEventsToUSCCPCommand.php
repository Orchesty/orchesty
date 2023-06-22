<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFUsageStatsBundle\Command;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\MongoDBException;
use Doctrine\Persistence\ObjectRepository;
use Hanaboso\CommonsBundle\Database\Locator\DatabaseManagerLocator;
use Hanaboso\CommonsBundle\Transport\CurlManagerInterface;
use Hanaboso\PipesFramework\HbPFUsageStatsBundle\Manager\AppInstallUsageStatsSender;
use Hanaboso\PipesFramework\HbPFUsageStatsBundle\Manager\OperationUsageStatsSender;
use Hanaboso\PipesFramework\HbPFUsageStatsBundle\Manager\SenderManager;
use Hanaboso\PipesFramework\UsageStats\Document\UsageStatsEvent;
use Hanaboso\PipesFramework\UsageStats\Repository\UsageStatsEventRepository;
use Hanaboso\Utils\Exception\DateTimeException;
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
     * @param string                 $alphaInstanceId
     * @param CurlManagerInterface   $curlManager
     */
    public function __construct(
        DatabaseManagerLocator $dml,
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
     * @throws DateTimeException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $input;

        $operationUsageStatsSender = new OperationUsageStatsSender($this->dm, $this->curlManager);
        $operationUsageStatsSender->generateOperationEvents($this->alphaInstanceId);

        $manager = new SenderManager();
        $manager->registerSender(
            [
                new AppInstallUsageStatsSender($this->dm, $this->curlManager),
                $operationUsageStatsSender,
            ],
        );

        return $manager->send($this->alphaInstanceId, $this->usageStatsRepository, $output);
    }

}
