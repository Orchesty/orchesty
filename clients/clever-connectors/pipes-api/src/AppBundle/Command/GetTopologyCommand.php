<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: venca
 * Date: 10.10.17
 * Time: 16:09
 */

namespace CleverConnectors\AppBundle\Command;

use Doctrine\ODM\MongoDB\DocumentManager;
use Exception;
use Hanaboso\PipesFramework\Configurator\Document\Node;
use Hanaboso\PipesFramework\Configurator\Document\Topology;
use InvalidArgumentException;
use MongoDB\BSON\ObjectId;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class GetTopologyCommand
 *
 * @package CleverConnectors\AppBundle\Command
 */
class GetTopologyCommand extends Command implements LoggerAwareInterface
{

    /**
     * @var DocumentManager
     */
    private $dm;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * GetSystemCommand constructor.
     *
     * @param DocumentManager $dm
     */
    public function __construct(DocumentManager $dm)
    {
        parent::__construct('react:get-topology');
        $this->dm     = $dm;
        $this->logger = new NullLogger();
    }

    /**
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    /**
     *
     */
    protected function configure(): void
    {
        $this->addArgument('node-id', InputArgument::REQUIRED, 'Node ID');
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            /** @var Node $node */
            $node = $this->dm->getRepository(Node::class)->find($input->getArgument('node-id'));

            if (!$node) {
                throw new InvalidArgumentException(sprintf('The node[id=%s]', $input->getArgument('node-id')));
            }

            $topology = $this
                ->dm->getDocumentCollection(Topology::class)
                ->findOne(
                    ['_id' => new ObjectId($node->getTopology())],
                    ['_id', 'name']
                );

            $output->writeln(json_encode($topology));
        } catch (Exception $e) {
            $this->logger->error($e->getMessage(), ['exception' => $e]);

            return 1;
        }

        return 0;
    }

}