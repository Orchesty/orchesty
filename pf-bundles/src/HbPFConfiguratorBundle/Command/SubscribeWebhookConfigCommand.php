<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFConfiguratorBundle\Command;

use Hanaboso\PipesFramework\Application\Manager\WebhookConfigManager;
use Hanaboso\Utils\String\Json;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

/**
 * Class SubscribeWebhookConfigCommand
 *
 * @package Hanaboso\PipesFramework\HbPFConfiguratorBundle\Command
 *
 * Diagnostic CLI for triggering the subscribe / unsubscribe flow on a single
 * WebhookConfig without going through the authenticated HTTP gateway. Useful
 * when reproducing problems where the UI reports success but the underlying
 * `Webhook` document never appears in MongoDB.
 */
final class SubscribeWebhookConfigCommand extends Command
{

    private const string CMD_NAME = 'webhook:subscribe-config';

    /**
     * SubscribeWebhookConfigCommand constructor.
     *
     * @param WebhookConfigManager $manager
     */
    public function __construct(private readonly WebhookConfigManager $manager)
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
            ->setDescription(
                'Calls WebhookConfigManager::subscribe (or unsubscribe) for a single (topology, node) pair.',
            )
            ->addArgument('topology', InputArgument::REQUIRED, 'Topology name')
            ->addArgument('node', InputArgument::REQUIRED, 'Node name (e.g. webhook-test.order.updated)')
            ->addArgument('action', InputArgument::OPTIONAL, 'subscribe | unsubscribe | list', 'subscribe');
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $topology = (string) $input->getArgument('topology');
        $node     = (string) $input->getArgument('node');
        $action   = (string) $input->getArgument('action');

        if ($action === 'list') {
            $output->writeln(sprintf(' Listing webhook configs for topology=%s', $topology));
            foreach ($this->manager->listForTopology($topology) as $item) {
                $output->writeln(sprintf(
                    '  - node=%s registered=%s webhookId=%s enabled=%s orphan=%s',
                    $item['nodeName'] ?? 'orphan',
                    $item['registered'] ?? FALSE ? 'yes' : 'no',
                    $item['webhookId'] ?? '',
                    $item['enabled'] ?? FALSE ? 'yes' : 'no',
                    $item['orphan'] ?? FALSE ? 'yes' : 'no',
                ));
            }

            return 0;
        }

        try {
            // Use the same UI-facing entrypoints as the controller so the CLI
            // exercises the lazy-create path (subscribe) and the idempotent
            // noop path (unsubscribe without an existing config).
            $payload = $action === 'unsubscribe'
                ? $this->manager->unsubscribeForNode($topology, $node)
                : $this->manager->subscribeForNode($topology, $node);

            $output->writeln(sprintf('<info>%s OK</info>', $action));
            $output->writeln(sprintf(' payload: %s', Json::encode($payload)));

            return 0;
        } catch (Throwable $t) {
            $output->writeln(sprintf('<error>%s FAILED: %s</error>', $action, $t->getMessage()));
            $output->writeln(sprintf(' (%s) at %s:%d', $t::class, $t->getFile(), $t->getLine()));
            if ($output->isVerbose()) {
                $output->writeln($t->getTraceAsString());
            }

            return 1;
        }
    }

}
