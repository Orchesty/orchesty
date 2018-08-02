<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: radek.jirsa
 * Date: 14.11.17
 * Time: 9:45
 */

namespace CleverConnectors\AppBundle\Command;

use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\Installer\InstallManager;
use Doctrine\ODM\MongoDB\MongoDBException;
use Hanaboso\PipesFramework\Configurator\Exception\TopologyException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class InstallTopologyCommand
 *
 * @package CleverConnectors\AppBundle\Command
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
     * @throws CleverConnectorsException
     * @throws MongoDBException
     * @throws TopologyException
     */
    protected function execute(InputInterface $input, OutputInterface $output): void
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

        $this->doLoop($table, $result[self::CREATE] ?? [], self::CREATE, $force);
        $this->doLoop($table, $result[self::UPDATE] ?? [], self::UPDATE, $force);
        $this->doLoop($table, $result[self::DELETE] ?? [], self::DELETE, $force);

        $table->render();
    }

    /**
     * @param Table  $table
     * @param array  $data
     * @param string $action
     * @param bool   $force
     */
    private function doLoop(Table $table, array $data, string $action, bool $force): void
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