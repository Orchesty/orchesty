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

    private const CMD_NAME = 'cron:refresh';

    /**
     * RefreshCronCommand constructor.
     *
     * @param DocumentManager $dm
     * @param CronManager     $cronManager
     */
    public function __construct(private DocumentManager $dm, private CronManager $cronManager)
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
            ->setDescription('Refresh CRONs');
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
        $nodes = $this->dm->getRepository(Node::class)->findBy(['type' => TypeEnum::CRON]);
        $output->write(sprintf('Refreshing %s CRONs:', count($nodes)));
        try {
            $this->cronManager->batchCreate($nodes);
            $output->writeln(' SUCCESS');
        } catch (CronException | CurlException $e) {
            $output->writeln(sprintf(' FAIL (%s)', $e->getMessage()));

            return 1;
        }

        return 0;
    }

}
