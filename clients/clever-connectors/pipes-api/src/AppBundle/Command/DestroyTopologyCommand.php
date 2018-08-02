<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Command;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\DocumentRepository;
use Doctrine\ODM\MongoDB\MongoDBException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\PipesFramework\Configurator\Document\Topology;
use Hanaboso\PipesFramework\Configurator\Repository\TopologyRepository;
use Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\RequestHandler;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class DestroyTopologyCommand
 *
 * @package CleverConnectors\AppBundle\Command
 */
class DestroyTopologyCommand extends Command
{

    private const CMD_NAME = 'topology:destroy-all';
    private const FORCE    = 'force';

    /**
     * @var DocumentManager
     */
    private $dm;

    /**
     * @var RequestHandler
     */
    private $requestHandler;

    /**
     * RunTopologyCommand constructor.
     *
     * @param DocumentManager $dm
     * @param RequestHandler  $requestHandler
     */
    public function __construct(DocumentManager $dm, RequestHandler $requestHandler)
    {
        parent::__construct(self::CMD_NAME);
        $this->dm             = $dm;
        $this->requestHandler = $requestHandler;
    }

    /**
     *
     */
    protected function configure(): void
    {
        $this
            ->setName(self::CMD_NAME)
            ->setDescription('Stops and removes all topologies')
            ->addOption(self::FORCE, 'force', InputOption::VALUE_NONE, 'Force');
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @throws CurlException
     * @throws MongoDBException
     */
    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        /** @var TopologyRepository|DocumentRepository $repository */
        $repository = $this->dm->getRepository(Topology::class);
        $topologies = $repository->getPublicEnabledTopologies();

        if ($input->getOption(self::FORCE)) {
            $this->destroyTopologies($topologies, $output);
        } else {
            $this->listTopologies($topologies, $output);
        }
    }

    /**
     * @param array           $topologies
     * @param OutputInterface $output
     *
     * @throws CurlException
     */
    private function destroyTopologies(array $topologies, OutputInterface $output): void
    {
        $iterator        = 1;
        $topologiesCount = count($topologies);

        foreach ($topologies as $topology) {
            $output->writeln(sprintf(
                '%s[%s / %s] Destroying topology \'%s\':',
                PHP_EOL,
                $iterator++,
                $topologiesCount,
                $topology->getName()
            ));
            $this->requestHandler->deleteTopology($topology->getId());
        }
    }

    /**
     * @param array           $topologies
     * @param OutputInterface $output
     */
    private function listTopologies(array $topologies, OutputInterface $output): void
    {
        $table = new Table($output);
        $table->setHeaders(['Topology ID', 'Topology name']);

        /** @var Topology $topology */
        foreach ($topologies as $topology) {
            $table->addRow([$topology->getId(), $topology->getName()]);
        }

        $table->render();
    }

}