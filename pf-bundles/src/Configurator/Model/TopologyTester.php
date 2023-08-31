<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Configurator\Model;

use Doctrine\ODM\MongoDB\DocumentManager;
use Generator;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Promise\Each;
use GuzzleHttp\RequestOptions;
use Hanaboso\CommonsBundle\Database\Locator\DatabaseManagerLocator;
use Hanaboso\PipesFramework\Configurator\Exception\TopologyConfigException;
use Hanaboso\PipesFramework\Configurator\Exception\TopologyException;
use Hanaboso\PipesFramework\Database\Document\Node;
use Hanaboso\PipesFramework\Database\Repository\NodeRepository;
use Throwable;

/**
 * Class TopologyTester
 *
 * @package Hanaboso\PipesFramework\Configurator\Model
 */
final class TopologyTester
{

    /**
     * @var NodeRepository
     */
    private NodeRepository $nodeRepository;

    /**
     * TopologyTester constructor.
     *
     * @param Client                 $client
     * @param TopologyConfigFactory  $topologyConfigFactory
     * @param DatabaseManagerLocator $databaseManagerLocator
     */
    public function __construct(
        private Client $client,
        private TopologyConfigFactory $topologyConfigFactory,
        DatabaseManagerLocator $databaseManagerLocator,
    ) {
        /** @var DocumentManager $dm */
        $dm                   = $databaseManagerLocator->getDm();
        $this->nodeRepository = $dm->getRepository(Node::class);
    }

    /**
     * @param string $topologyId
     *
     * @return array<int, array{id: string, name: string, status: string, reason: string}>
     * @throws TopologyConfigException
     * @throws TopologyException
     */
    public function testTopology(string $topologyId): array
    {
        $data       = [];
        $nodes      = $this->nodeRepository->getNodesByTopology($topologyId);
        $nodeNames  = array_column(array_map(static fn(Node $n) => [$n->getId(), $n->getName()], $nodes), '1', '0');
        $innerNodes = [];

        foreach ($nodes as $node) {
            $id     = $node->getId();
            $worker = $this->topologyConfigFactory->getWorkers($node);

            $host    = $worker[TopologyConfigFactory::SETTINGS][TopologyConfigFactory::HOST] ?? NULL;
            $port    = $worker[TopologyConfigFactory::SETTINGS][TopologyConfigFactory::PORT] ?? NULL;
            $path    = $worker[TopologyConfigFactory::SETTINGS][TopologyConfigFactory::STATUS_PATH] ?? NULL;
            $headers = $worker[TopologyConfigFactory::SETTINGS][TopologyConfigFactory::HEADERS] ?? NULL;

            if ($host !== NULL && $port !== NULL && $path !== NULL && isset($nodeNames[$id])) {
                $innerNodes[] = [$id, $nodeNames[$id], sprintf('http://%s:%s%s', $host, $port, $path), $headers];
            }
        }

        $getRequests = function () use ($innerNodes, &$data): Generator {
            foreach ($innerNodes as $node) {
                [$id, $name, $requestUrl, $headers] = $node;

                yield $this->client->getAsync($requestUrl, [RequestOptions::HEADERS => $headers])->then(
                    static function () use ($id, $name, &$data): void {
                        $data[] = [
                            'id'     => $id,
                            'name'   => $name,
                            'status' => 'ok',
                        ];
                    },
                    static function (Throwable $throwable) use ($id, $name, &$data): void {
                        $response = $throwable instanceof RequestException ? $throwable->getResponse() : NULL;

                        $data[] = [
                            'id'     => $id,
                            'name'   => $name,
                            'status' => 'nok',
                            'reason' => $response ? $response->getBody()->getContents() : $throwable->getMessage(),
                        ];
                    },
                );
            }
        };

        Each::ofLimit($getRequests(), 10)->wait();

        return $data;
    }

}
