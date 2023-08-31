<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFConfiguratorBundle\Command;

use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

/**
 * Class UpdateMongoIndexesCommand
 *
 * @package Hanaboso\PipesFramework\HbPFConfiguratorBundle\Command
 */
final class UpdateMongoIndexesCommand extends Command
{

    private const CMD_NAME = 'mongodb:index:update';

    /**
     * UpdateMongoIndexesCommand constructor.
     *
     * @param DocumentManager $ddm
     * @param DocumentManager $mdm
     */
    public function __construct(private DocumentManager $ddm, private DocumentManager $mdm)
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
            ->setDescription('Updates all mongodb indexes');
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

        try {
            $this->ddm->getSchemaManager()->deleteIndexes();
            $output->writeln(' Indexes for default database has been deleted.');
            $this->mdm->getSchemaManager()->deleteIndexes();
            $output->writeln(' Indexes for metrics database has been deleted.');

            $this->ddm->getSchemaManager()->updateIndexes();
            $output->writeln(' Indexes for default database has been updated.');
            $this->mdm->getSchemaManager()->updateIndexes();
            $output->writeln(' Indexes for metrics database has been updated.');
        } catch (Throwable $e) {
            $output->writeln(sprintf(' FAIL (%s)', $e->getMessage()));

            return 1;
        }

        return 0;
    }

}
