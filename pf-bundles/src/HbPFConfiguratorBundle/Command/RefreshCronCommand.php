<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFConfiguratorBundle\Command;

use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\CommonsBundle\Enum\TypeEnum;
use Hanaboso\CommonsBundle\Exception\CronException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\PipesFramework\Configurator\Cron\CronManager;
use Hanaboso\PipesPhpSdk\Database\Document\Node;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class RefreshCronCommand
 *
 * @package Hanaboso\PipesFramework\HbPFConfiguratorBundle\Command
 */
final class RefreshCronCommand extends Command
{

    protected static $defaultName = 'cron:refresh';

    /**
     * RefreshCronCommand constructor.
     *
     * @param DocumentManager $dm
     * @param CronManager     $cronManager
     */
    public function __construct(private readonly DocumentManager $dm, private readonly CronManager $cronManager)
    {
        parent::__construct();
    }

    /**
     *
     */
    protected function configure(): void
    {
        $this->setDescription('Refresh CRONs');
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

        /** @var Node[] $nodes */
        $nodes = array_filter(
            $this->dm->getRepository(Node::class)->findBy(['type' => TypeEnum::CRON, 'deleted' => FALSE]),
            static fn(Node $node): bool => !empty($node->getCron()),
        );

        $output->write(sprintf('Refreshing %s CRONs:', count($nodes)));

        try {
            $this->cronManager->batchUpsert($nodes);
            $output->writeln(' SUCCESS');
        } catch (CronException | CurlException $e) {
            $output->writeln(sprintf(' FAIL (%s)', $e->getMessage()));

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

}
