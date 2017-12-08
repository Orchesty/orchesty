<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFCommonsBundle\Command;

use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\PipesFramework\Commons\Cron\CronManager;
use Hanaboso\PipesFramework\Commons\Enum\TypeEnum;
use Hanaboso\PipesFramework\Commons\Exception\CronException;
use Hanaboso\PipesFramework\Configurator\Document\Node;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class RefreshCronCommand
 *
 * @package Hanaboso\PipesFramework\HbPFCommonsBundle\Command
 */
class RefreshCronCommand extends Command
{

    private const CMD_NAME = 'cron:refresh';

    /**
     * @var DocumentManager
     */
    private $dm;

    /**
     * @var CronManager
     */
    private $cronManager;

    /**
     * RefreshCronCommand constructor.
     *
     * @param DocumentManager $dm
     * @param CronManager     $cronManager
     */
    public function __construct(DocumentManager $dm, CronManager $cronManager)
    {
        parent::__construct(self::CMD_NAME);
        $this->dm          = $dm;
        $this->cronManager = $cronManager;
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
     */
    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        /** @var Node[] $nodes */
        $nodes = $this->dm->getRepository(Node::class)->findBy(['type' => TypeEnum::CRON]);
        $output->write(sprintf('Refreshing %s CRONs:', count($nodes)));
        try {
            $this->cronManager->batchCreate($nodes);
            $output->writeln(' SUCCESS');
        } catch (CronException $e) {
            $output->writeln(sprintf(' FAIL (%s)', $e->getMessage()));
        }
    }

}