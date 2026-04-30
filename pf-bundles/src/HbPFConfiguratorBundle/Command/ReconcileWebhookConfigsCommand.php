<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFConfiguratorBundle\Command;

use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\CommonsBundle\Enum\TypeEnum;
use Hanaboso\PipesFramework\Application\Manager\WebhookConfigManager;
use Hanaboso\PipesFramework\Database\Document\Node;
use Hanaboso\PipesFramework\Database\Document\Topology;
use Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\ApplicationController;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

/**
 * Class ReconcileWebhookConfigsCommand
 *
 * @package Hanaboso\PipesFramework\HbPFConfiguratorBundle\Command
 *
 * Reconciles WebhookConfig records from existing Webhook-typed Node documents.
 *
 * Use case: the schema-save cascade ({@see WebhookConfigManager::upsertFromNode})
 * either failed silently or did not run for nodes that pre-date the cascade.
 * This command walks every webhook node and re-applies the upsert with full
 * per-node error reporting so the UI no longer shows "Not configured".
 */
final class ReconcileWebhookConfigsCommand extends Command
{

    private const string CMD_NAME = 'webhook:reconcile-configs';

    /**
     * ReconcileWebhookConfigsCommand constructor.
     *
     * @param DocumentManager      $dm
     * @param WebhookConfigManager $webhookConfigManager
     */
    public function __construct(
        private readonly DocumentManager $dm,
        private readonly WebhookConfigManager $webhookConfigManager,
    )
    {
        parent::__construct(self::CMD_NAME);
    }

    /**
     * @return void
     */
    protected function configure(): void
    {
        $this
            ->setName(self::CMD_NAME)
            ->setDescription('Re-runs WebhookConfigManager::upsertFromNode for every Webhook-typed Node.')
            ->addOption(
                'topology',
                NULL,
                InputOption::VALUE_REQUIRED,
                'Limit reconciliation to a single topology by name.',
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
        $topologyFilter = $input->getOption('topology');

        $criteria   = ['type' => TypeEnum::WEBHOOK->value, 'deleted' => FALSE];
        $topologyId = NULL;

        if ($topologyFilter) {
            $topology = $this->dm->getRepository(Topology::class)->findOneBy(['name' => $topologyFilter]);
            if (!$topology) {
                $output->writeln(sprintf('<error>Topology "%s" not found.</error>', $topologyFilter));

                return 1;
            }
            $topologyId           = $topology->getId();
            $criteria['topology'] = $topologyId;
        }

        /** @var Node[] $nodes */
        $nodes = $this->dm->getRepository(Node::class)->findBy($criteria);
        $output->writeln(sprintf(' Inspecting %d webhook nodes.', count($nodes)));

        $created = 0;
        $skipped = 0;
        $errors  = 0;

        foreach ($nodes as $node) {
            /** @var Topology|null $topology */
            $topology = $this->dm->getRepository(Topology::class)->find($node->getTopology());
            if (!$topology) {
                $skipped++;
                $output->writeln(
                    sprintf('  SKIP node=%s: topology=%s missing', $node->getName(), $node->getTopology()),
                );

                continue;
            }

            $app   = $node->getApplication() ?? '';
            $event = $node->getEventName();
            if ($app === '' || $event === '') {
                $skipped++;
                $output->writeln(
                    sprintf(
                        '  SKIP node=%s @ topology=%s: missing application[%s] or eventName[%s] on Node document',
                        $node->getName(),
                        $topology->getName(),
                        $app,
                        $event,
                    ),
                );

                continue;
            }

            try {
                $config = $this->webhookConfigManager->upsertFromNode(
                    $topology,
                    $node,
                    ApplicationController::SYSTEM_USER,
                );

                if ($config === NULL) {
                    $skipped++;
                    $output->writeln(
                        sprintf(
                            '  SKIP node=%s @ topology=%s: upsertFromNode returned NULL',
                            $node->getName(),
                            $topology->getName(),
                        ),
                    );

                    continue;
                }

                $created++;
                $output->writeln(
                    sprintf(
                        '  + %s @ %s -> app=%s event=%s sdk=%s',
                        $node->getName(),
                        $topology->getName(),
                        $config->getApplication(),
                        $config->getEventName(),
                        $config->getSdk(),
                    ),
                );
            } catch (Throwable $e) {
                $errors++;
                $output->writeln(
                    sprintf(
                        '  <error>FAIL %s @ %s: %s</error>',
                        $node->getName(),
                        $topology->getName(),
                        $e->getMessage(),
                    ),
                );
            }
        }

        $output->writeln(
            sprintf(' Done. Upserted: %d, skipped: %d, errors: %d', $created, $skipped, $errors),
        );

        return $errors > 0 ? 1 : 0;
    }

}
