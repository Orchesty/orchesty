<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFConfiguratorBundle\Command;

use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\PipesFramework\ApiGateway\Locator\ServiceLocator;
use Hanaboso\PipesFramework\Application\Document\ApplicationInstall;
use Hanaboso\PipesFramework\Application\Document\Webhook;
use Hanaboso\PipesFramework\Configurator\Document\Sdk;
use Hanaboso\PipesFramework\Database\Document\Node;
use Hanaboso\PipesFramework\Database\Document\Topology;
use Hanaboso\PipesFramework\Database\Document\TopologyApplication;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

/**
 * Class MigrateSdkCommand
 *
 * @package Hanaboso\PipesFramework\HbPFConfiguratorBundle\Command
 */
final class MigrateSdkCommand extends Command
{

    private const string CMD_NAME = 'sdk-migrate';

    /**
     * MigrateSdkCommand constructor.
     *
     * @param DocumentManager $dm
     * @param ServiceLocator  $serviceLocator
     */
    public function __construct(private readonly DocumentManager $dm, private readonly ServiceLocator $serviceLocator)
    {
        parent::__construct(self::CMD_NAME);
    }

    /**
     *
     */
    protected function configure(): void
    {
        $this
            ->setName(self::CMD_NAME)
            ->setDescription(
                'Backfills the sdk field on existing ApplicationInstall, Webhook, Node and TopologyApplication documents',
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
        $input;
        $keyToSdk = $this->buildKeyMap($output);

        if ($keyToSdk === []) {
            $output->writeln(' No SDK application keys found. Nothing to migrate.');

            return Command::SUCCESS;
        }

        $migrated = 0;
        $skipped  = 0;

        /** @var ApplicationInstall[] $installs */
        $installs = $this
            ->dm
            ->createQueryBuilder(ApplicationInstall::class)
            ->field(ApplicationInstall::SDK)
            ->in(['', NULL])
            ->getQuery()
            ->toArray();

        $output->writeln(sprintf(' Found %d ApplicationInstall documents with empty sdk.', count($installs)));

        foreach ($installs as $install) {
            $key  = $install->getKey();
            $user = $install->getUser();

            if (isset($keyToSdk[$key])) {
                $install->setSdk($keyToSdk[$key]);
                $migrated++;
                $output->writeln(sprintf('  [OK]   %s (user: %s) -> %s', $key, $user, $keyToSdk[$key]));
            } else {
                $skipped++;
                $output->writeln(sprintf('  [WARN] Key "%s" (user: %s) not found in any SDK, skipping', $key, $user));
            }
        }

        $this->dm->flush();
        $output->writeln(sprintf(' ApplicationInstall done. Migrated: %d, Skipped: %d', $migrated, $skipped));
        $output->writeln('');

        $webhookMigrated = 0;
        $webhookSkipped  = 0;

        /** @var Webhook[] $webhooks */
        $webhooks = $this
            ->dm
            ->createQueryBuilder(Webhook::class)
            ->field('sdk')
            ->in(['', NULL])
            ->getQuery()
            ->toArray();

        $output->writeln(sprintf(' Found %d Webhook documents with empty sdk.', count($webhooks)));

        foreach ($webhooks as $webhook) {
            $appKey = $webhook->getApplication();

            if (isset($keyToSdk[$appKey])) {
                $webhook->setSdk($keyToSdk[$appKey]);
                $webhookMigrated++;
                $output->writeln(
                    sprintf('  [OK]   webhook %s (app: %s) -> %s', $webhook->getName(), $appKey, $keyToSdk[$appKey]),
                );
            } else {
                $webhookSkipped++;
                $output->writeln(
                    sprintf(
                        '  [WARN] Webhook app "%s" (name: %s) not found in any SDK, skipping',
                        $appKey,
                        $webhook->getName(),
                    ),
                );
            }
        }

        $this->dm->flush();
        $output->writeln(sprintf(' Webhook done. Migrated: %d, Skipped: %d', $webhookMigrated, $webhookSkipped));
        $output->writeln('');

        $nodeMigrated = 0;
        $nodeSkipped  = 0;

        /** @var Node[] $nodes */
        $nodes = $this
            ->dm
            ->createQueryBuilder(Node::class)
            ->field('sdk')
            ->in(['', NULL])
            ->field('application')
            ->notIn(['', NULL])
            ->getQuery()
            ->toArray();

        $output->writeln(sprintf(' Found %d Node documents with empty sdk.', count($nodes)));

        foreach ($nodes as $node) {
            $appKey = $node->getApplication();

            if ($appKey === NULL || $appKey === '') {
                continue;
            }

            if (isset($keyToSdk[$appKey])) {
                $node->setSdk($keyToSdk[$appKey]);
                $nodeMigrated++;
                $output->writeln(
                    sprintf('  [OK]   node %s (app: %s) -> %s', $node->getName(), $appKey, $keyToSdk[$appKey]),
                );
            } else {
                $nodeSkipped++;
                $output->writeln(
                    sprintf(
                        '  [WARN] Node "%s" (app: %s) not found in any SDK, skipping',
                        $node->getName(),
                        $appKey,
                    ),
                );
            }
        }

        $this->dm->flush();
        $output->writeln(sprintf(' Node done. Migrated: %d, Skipped: %d', $nodeMigrated, $nodeSkipped));
        $output->writeln('');

        $topoMigrated = 0;
        $topoSkipped  = 0;

        /** @var Topology[] $topologies */
        $topologies = $this->dm->getRepository(Topology::class)->findAll();

        $output->writeln(sprintf(' Found %d Topology documents to check.', count($topologies)));

        foreach ($topologies as $topology) {
            $applications = [];
            $needsUpdate  = FALSE;
            $hasSkipped   = FALSE;

            foreach ($topology->getApplications() as $application) {
                if ($application->getSdk() === '' && isset($keyToSdk[$application->getKey()])) {
                    $applications[] = new TopologyApplication(
                        $application->getKey(),
                        $application->getHost(),
                        $keyToSdk[$application->getKey()],
                    );
                    $needsUpdate    = TRUE;
                } else {
                    if ($application->getSdk() === '' && !isset($keyToSdk[$application->getKey()])) {
                        $hasSkipped = TRUE;
                    }

                    $applications[] = $application;
                }
            }

            if ($needsUpdate) {
                $topology->setApplications($applications);
                $topoMigrated++;
                $output->writeln(
                    sprintf('  [OK]   topology "%s" updated embedded applications', $topology->getName()),
                );
            } elseif ($hasSkipped) {
                $topoSkipped++;
                $output->writeln(
                    sprintf(
                        '  [WARN] Topology "%s" has empty-sdk apps not found in any SDK, skipping',
                        $topology->getName(),
                    ),
                );
            }
        }

        $this->dm->flush();
        $output->writeln(
            sprintf(' TopologyApplication done. Migrated: %d, Skipped: %d', $topoMigrated, $topoSkipped),
        );

        $totalSkipped = $skipped + $webhookSkipped + $nodeSkipped + $topoSkipped;

        return $totalSkipped > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    /**
     * @param OutputInterface $output
     *
     * @return array<string, string>
     */
    private function buildKeyMap(OutputInterface $output): array
    {
        /** @var Sdk[] $sdks */
        $sdks     = $this->dm->getRepository(Sdk::class)->findAll();
        $keyToSdk = [];

        foreach ($sdks as $sdk) {
            $sdkName = $sdk->getName();

            try {
                $apps = $this->serviceLocator->getApps($sdkName);

                foreach ($apps['items'] ?? [] as $app) {
                    $keyToSdk[$app['key']] = $sdkName;
                }
            } catch (Throwable $e) {
                $output->writeln(
                    sprintf('  [WARN] Could not fetch apps for SDK "%s": %s', $sdkName, $e->getMessage()),
                );
            }
        }

        return $keyToSdk;
    }

}
