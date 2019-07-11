<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFConfiguratorBundle\Command;

use Doctrine\ODM\MongoDB\MongoDBException;
use Hanaboso\PipesFramework\Configurator\Exception\TopologyException;
use Hanaboso\PipesFramework\TopologyInstaller\InstallManager;
use Hanaboso\PipesPhpSdk\Connector\Exception\ConnectorException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class InstallTopologyCommand
 *
 * @package Hanaboso\PipesFramework\HbPFConfiguratorBundle\Command
 */
class InstallTopologyCommand extends Command
{

    private const CREATE = 'create';
    private const UPDATE = 'update';
    private const DELETE = 'delete';
    private const FORCE  = 'force';

    /**
     * @var InstallManager
     */
    private $manager;

    /**
     * InstallTopologyCommand constructor.
     *
     * @param InstallManager $manager
     */
    public function __construct(InstallManager $manager)
    {
        parent::__construct('topology:install');

        $this->manager = $manager;
    }

    /**
     *
     */
    protected function configure(): void
    {
        $this->addOption(self::CREATE, 'c', InputOption::VALUE_NONE, 'Create');
        $this->addOption(self::UPDATE, 'u', InputOption::VALUE_NONE, 'Update');
        $this->addOption(self::DELETE, 'd', InputOption::VALUE_NONE, 'Delete');
        $this->addOption(self::FORCE, 'force', InputOption::VALUE_NONE, 'Force');
        $this->setDescription('Possible params are: -c for create, -u for update, -d for delete, --force for apply your changes.');
        $this->setHelp('Possible params are: -c for create, -u for update, -d for delete, --force for apply your changes.');
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
     * @throws ConnectorException
     * @throws MongoDBException
     * @throws TopologyException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $create = FALSE;
        $update = FALSE;
        $delete = FALSE;
        $force  = FALSE;

        if ($input->getOption(self::CREATE)) {
            $create = TRUE;
        }

        if ($input->getOption(self::UPDATE)) {
            $update = TRUE;
        }

        if ($input->getOption(self::DELETE)) {
            $delete = TRUE;
        }

        if ($input->getOption(self::FORCE)) {
            $force = TRUE;
        }

        $result = $this->manager->prepareInstall($create, $update, $delete, $force);

        $table = new Table($output);
        $table->setHeaders(['Topology name', 'Action', 'Error']);

        $this->insertRows($table, $result[self::CREATE] ?? [], self::CREATE, $force);
        $this->insertRows($table, $result[self::UPDATE] ?? [], self::UPDATE, $force);
        $this->insertRows($table, $result[self::DELETE] ?? [], self::DELETE, $force);

        $table->render();

        return 0;
    }

    /**
     * @param Table  $table
     * @param array  $data
     * @param string $action
     * @param bool   $force
     */
    private function insertRows(Table $table, array $data, string $action, bool $force): void
    {
        foreach ($data as $key => $item) {
            if ($force) {
                $table->addRow([$key, $action, $item ?? '']);
            } else {
                $table->addRow([$item ?? '', $action]);
            }
        }
    }

}
