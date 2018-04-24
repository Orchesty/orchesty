<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Command;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\DocumentRepository;
use Hanaboso\PipesFramework\Configurator\Document\Topology;
use Hanaboso\PipesFramework\Configurator\Repository\TopologyRepository;
use Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\RequestHandler;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class RunTopologyCommand
 *
 * @package CleverConnectors\AppBundle\Command
 */
class RunTopologyCommand extends Command
{

    private const CMD_NAME = 'topology:run-all';

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
            ->setDescription('Runs all public enabled topologies');
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        /** @var TopologyRepository|DocumentRepository $repository */
        $repository      = $this->dm->getRepository(Topology::class);
        $topologies      = $repository->getPublicEnabledTopologies();
        $topologiesCount = count($topologies);

        $iterator = 1;
        foreach ($topologies as $topology) {
            $output->writeln(sprintf(
                '%s[%s / %s] Starting topology \'%s\':',
                PHP_EOL,
                $iterator++,
                $topologiesCount,
                $topology->getName()
            ));
            $this->requestHandler->generateTopology($topology->getId());
            $this->requestHandler->runTopology($topology->getId());
        }
    }

}