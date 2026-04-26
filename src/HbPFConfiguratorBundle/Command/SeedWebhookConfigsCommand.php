<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFConfiguratorBundle\Command;

use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\PipesFramework\Application\Document\Webhook;
use Hanaboso\PipesFramework\Application\Document\WebhookConfig;
use Hanaboso\PipesFramework\Application\Repository\WebhookConfigRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

/**
 * Backfills WebhookConfig records from existing live Webhook documents so the
 * UI-driven webhook flow has the same intent visible as before the migration.
 *
 * The command is idempotent — re-running it skips webhooks that already have
 * a matching config (by topology + node + application + user + sdk).
 */
final class SeedWebhookConfigsCommand extends Command
{

    private const string CMD_NAME = 'webhook:seed-configs';

    public function __construct(private readonly DocumentManager $dm)
    {
        parent::__construct(self::CMD_NAME);
    }

    protected function configure(): void
    {
        $this
            ->setName(self::CMD_NAME)
            ->setDescription('Seeds WebhookConfig records from existing Webhook (live registration) documents.')
            ->addOption(
                'dry-run',
                NULL,
                InputOption::VALUE_NONE,
                'Print what would be inserted without persisting any changes.',
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $dryRun = (bool) $input->getOption('dry-run');

        try {
            /** @var WebhookConfigRepository $configRepo */
            $configRepo = $this->dm->getRepository(WebhookConfig::class);
            $webhookRepo = $this->dm->getRepository(Webhook::class);

            $webhooks = $webhookRepo->findAll();
            $output->writeln(sprintf(' Found %d Webhook documents to inspect.', count($webhooks)));

            $created = 0;
            $skipped = 0;

            foreach ($webhooks as $webhook) {
                if (!$webhook->getTopology() || !$webhook->getNode()) {
                    $skipped++;
                    $output->writeln(
                        sprintf(
                            '  SKIP webhook [%s] of [%s]: missing topology or node.',
                            $webhook->getName(),
                            $webhook->getApplication(),
                        ),
                    );
                    continue;
                }

                $existing = $configRepo->findOneBy([
                    'topologyName' => $webhook->getTopology(),
                    'nodeName'     => $webhook->getNode(),
                    'application'  => $webhook->getApplication(),
                    'user'         => $webhook->getUser(),
                    'sdk'          => $webhook->getSdk(),
                ]);

                if ($existing) {
                    $skipped++;
                    continue;
                }

                $config = (new WebhookConfig())
                    ->setTopologyName($webhook->getTopology())
                    ->setNodeName($webhook->getNode())
                    ->setApplication($webhook->getApplication())
                    ->setUser($webhook->getUser())
                    ->setSdk($webhook->getSdk())
                    ->setEventName($webhook->getName())
                    ->setParameters([])
                    ->setEnabled(TRUE);

                if (!$dryRun) {
                    $this->dm->persist($config);
                }

                $created++;
                $output->writeln(
                    sprintf(
                        '  + %s :: %s/%s -> %s',
                        $webhook->getApplication(),
                        $webhook->getTopology(),
                        $webhook->getNode(),
                        $webhook->getName(),
                    ),
                );
            }

            if (!$dryRun && $created > 0) {
                $this->dm->flush();
            }

            $output->writeln(
                sprintf(' Done. Created: %d, skipped: %d%s', $created, $skipped, $dryRun ? ' (dry-run)' : ''),
            );

            return 0;
        } catch (Throwable $e) {
            $output->writeln(sprintf(' FAIL (%s)', $e->getMessage()));
            return 1;
        }
    }

}
