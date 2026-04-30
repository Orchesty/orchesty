<?php declare(strict_types=1);

namespace Hanaboso\PipesFrameworkEnterprise\HbPFEnterpriseConfiguratorBundle\Command;

use Hanaboso\PipesFrameworkEnterprise\HbPFEnterpriseConfiguratorBundle\Handler\CloudLimitsHandler;
use Hanaboso\PipesFrameworkEnterprise\HbPFEnterpriseConfiguratorBundle\Publisher\CloudEventsPublisher;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

/**
 * Class CloudLimitsTickCommand
 *
 * Long-running 1-minute loop that recomputes cloud plan-limit usage,
 * persists a single-doc snapshot in `cloud_limit_usage`, and emits Notifier
 * events for any resource currently at or above the warning band. The
 * Notifier itself handles per-preset 2 h throttling so this command can fire
 * an event every tick without re-implementing throttling here.
 *
 * Mirrors the bridge's `LIMITS_CHECK_INTERVAL` cadence (60 s) so dashboard
 * cards and warning banners agree with what the bridge actually enforces.
 *
 * @package Hanaboso\PipesFrameworkEnterprise\HbPFEnterpriseConfiguratorBundle\Command
 */
final class CloudLimitsTickCommand extends Command
{

    private const string NAME = 'orchesty:limits:tick';

    private const int DEFAULT_INTERVAL_SECONDS = 60;

    /**
     * CloudLimitsTickCommand constructor.
     *
     * @param CloudLimitsHandler   $handler
     * @param CloudEventsPublisher $publisher
     */
    public function __construct(
        private readonly CloudLimitsHandler $handler,
        private readonly CloudEventsPublisher $publisher,
    )
    {
        parent::__construct();
    }

    /**
     * @return void
     */
    protected function configure(): void
    {
        $this
            ->setName(self::NAME)
            ->setDescription(
                'Recompute cloud plan-limit usage every minute, persist a snapshot, and emit Notifier events when bands are crossed.',
            )
            ->addOption('once', NULL, InputOption::VALUE_NONE, 'Run a single tick and exit (used by tests/CI).')
            ->addOption(
                'interval',
                NULL,
                InputOption::VALUE_REQUIRED,
                'Override tick interval in seconds.',
                (string) self::DEFAULT_INTERVAL_SECONDS,
            );
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $once     = (bool) $input->getOption('once');
        $interval = max(1, (int) $input->getOption('interval'));

        $output->writeln(sprintf(
            '<info>orchesty:limits:tick started (interval=%ds, once=%s)</info>',
            $interval,
            $once ? 'true' : 'false',
        ));

        do {
            try {
                $this->tick($output);
            } catch (Throwable $t) {
                $output->writeln(sprintf('<error>tick failed: %s</error>', $t->getMessage()));
            }

            if ($once) {
                break;
            }

            sleep($interval);
        } while (TRUE);

        return self::SUCCESS;
    }

    /**
     * @param OutputInterface $output
     *
     * @return void
     */
    private function tick(OutputInterface $output): void
    {
        $usage = $this->handler->computeUsage();
        $this->handler->persistSnapshot($usage);

        $bands = CloudLimitsHandler::bandsToReport($usage);
        foreach ($bands as $band) {
            $output->writeln(sprintf(
                '<comment>cloud limit %s: band=%s percent=%s</comment>',
                $band['resource'],
                $band['band'],
                $band['percent'] ?? 'n/a',
            ));

            $this->publisher->publishLimitThreshold(
                $band['resource'],
                $band['band'],
                $band['current'],
                $band['limit'],
                $band['percent'],
            );
        }
    }

}
