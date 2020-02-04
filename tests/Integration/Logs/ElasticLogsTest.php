<?php declare(strict_types=1);

namespace PipesFrameworkTests\Integration\Logs;

use Doctrine\ODM\MongoDB\LockException;
use Doctrine\ODM\MongoDB\MongoDBException;
use Elastica\Client;
use Elastica\Document;
use Elastica\Exception\ResponseException;
use Elastica\Request;
use Elastica\Response;
use Exception;
use Hanaboso\CommonsBundle\Enum\TypeEnum;
use Hanaboso\MongoDataGrid\GridRequestDto;
use Hanaboso\PipesFramework\Logs\ElasticLogs;
use Hanaboso\PipesFramework\Logs\StartingPointsFilter;
use Hanaboso\PipesPhpSdk\Database\Document\Node;
use Hanaboso\Utils\Date\DateTimeUtils;
use PipesFrameworkTests\DatabaseTestCaseAbstract;
use ReflectionException;

/**
 * Class ElasticLogsTest
 *
 * @package PipesFrameworkTests\Integration\Logs
 */
final class ElasticLogsTest extends DatabaseTestCaseAbstract
{

    /**
     * @covers \Hanaboso\PipesFramework\Logs\ElasticLogs
     * @covers \Hanaboso\PipesFramework\Logs\ElasticLogs::getData
     * @covers \Hanaboso\PipesFramework\Logs\ElasticLogs::getFilterAndSorter
     * @covers \Hanaboso\PipesFramework\Logs\ElasticLogs::getInnerData
     * @covers \Hanaboso\PipesFramework\Logs\ElasticLogs::setIndex
     * @covers \Hanaboso\PipesFramework\Logs\LogsAbstract::getNonEmptyValue
     * @covers \Hanaboso\PipesFramework\Logs\LogsAbstract::processStartingPoints
     * @covers \Hanaboso\PipesFramework\Logs\LogsAbstract::getNodeName
     * @covers \Hanaboso\PipesFramework\Logs\StartingPointsFilter::prepareSearchQuery
     *
     * @throws Exception
     */
    public function testGetData(): void
    {
        $this->prepareData();

        $logs   = self::$container->get('hbpf.elastic.logs');
        $result = $logs->getData(
            new GridRequestDto(
                [
                    'filter' => '{"severity":"ERROR"}',
                ]
            )
        );
        $logs->setIndex('index');

        self::assertEquals(
            [
                'limit'  => 10,
                'offset' => 0,
                'count'  => 1,
                'total'  => 1,
                'items'  => [
                    [
                        'id'             => $result['items'][0]['id'],
                        'severity'       => 'ERROR',
                        'message'        => 'Message 5',
                        'type'           => 'starting_point',
                        'correlation_id' => 'Correlation ID 5',
                        'topology_id'    => 'Topology ID 5',
                        'topology_name'  => 'Topology Name 5',
                        'node_id'        => $result['items'][0]['node_id'],
                        'node_name'      => 'Node',
                        'timestamp'      => $result['items'][0]['timestamp'],
                    ],
                ],
            ],
            $result
        );
    }

    /**
     * @covers \Hanaboso\PipesFramework\Logs\ElasticLogs::getData
     * @throws Exception
     */
    public function testGetDataErr(): void
    {
        $client = self::createPartialMock(Client::class, ['request']);
        $client->expects(self::any())->method('request')->willThrowException(
            new ResponseException(
                new Request(''),
                new Response(['error' => 'in order to sort on'])
            )
        );
        $elLogs = new ElasticLogs($this->dm, new StartingPointsFilter($this->dm), $client);
        $this->setProperty($elLogs, 'client', $client);
        self::expectException(ResponseException::class);
        $elLogs->getData(new GridRequestDto(['filter' => '{"severity":"ERROR"}']));
    }

    /**
     * @covers \Hanaboso\PipesFramework\Logs\ElasticLogs::getData
     *
     * @throws MongoDBException
     * @throws ReflectionException
     */
    public function testGetDataErr2(): void
    {
        $logs   = self::$container->get('hbpf.elastic.logs');
        $client = self::createPartialMock(Client::class, ['request']);
        $client->expects(self::any())->method('request')->willThrowException(
            new ResponseException(
                new Request(''),
                new Response('')
            )
        );
        $this->setProperty($logs, 'client', $client);

        self::expectException(ResponseException::class);
        $logs->getData(new GridRequestDto(['filter' => '{"severity":"ERROR"}']));
    }

    /**
     * @covers \Hanaboso\PipesFramework\Logs\StartingPointsFilter::filterCols
     * @covers \Hanaboso\PipesFramework\Logs\StartingPointsFilter::orderCols
     * @covers \Hanaboso\PipesFramework\Logs\StartingPointsFilter::searchableCols
     * @covers \Hanaboso\PipesFramework\Logs\StartingPointsFilter::useTextSearch
     * @covers \Hanaboso\PipesFramework\Logs\StartingPointsFilter::prepareSearchQuery
     * @covers \Hanaboso\PipesFramework\Logs\MongoDbLogs
     * @covers \Hanaboso\PipesFramework\Logs\MongoDbLogs::getData
     * @covers \Hanaboso\PipesFramework\Logs\LogsFilter::filterCols
     * @covers \Hanaboso\PipesFramework\Logs\LogsFilter::orderCols
     * @covers \Hanaboso\PipesFramework\Logs\LogsFilter::searchableCols
     * @covers \Hanaboso\PipesFramework\Logs\LogsFilter::useTextSearch
     * @covers \Hanaboso\PipesFramework\Logs\LogsFilter::prepareSearchQuery
     * @covers \Hanaboso\PipesFramework\Logs\LogsFilter::setDocument
     * @covers \Hanaboso\PipesFramework\Logs\ElasticLogs::getFilterAndSorter
     *
     * @throws ReflectionException
     */
    public function testGetFilterAndSorter(): void
    {
        $logs   = self::$container->get('hbpf.elastic.logs');
        $result = $this->invokeMethod(
            $logs,
            'getFilterAndSorter',
            [new GridRequestDto(['filter' => '{"_MODIFIER_SEARCH":"search"}', 'orderby' => 'topology_id'])]
        );

        self::assertEquals(2, count($result));
    }

    /**
     * @covers \Hanaboso\PipesFramework\Logs\LogsAbstract::processStartingPoints
     * @throws ReflectionException
     */
    public function testProcessStartingPoints(): void
    {
        $logs = self::$container->get('hbpf.elastic.logs');
        $dto  = new GridRequestDto([]);

        self::expectException(LockException::class);
        $this->invokeMethod($logs, 'processStartingPoints', [$dto, ['1' => ['correlation_id' => []]]]);
    }

    /**
     * @covers \Hanaboso\PipesFramework\Logs\LogsAbstract::processStartingPoints
     * @throws ReflectionException
     */
    public function testProcessStartingPointsErr(): void
    {
        $logs = self::$container->get('hbpf.elastic.logs');
        $dto  = new GridRequestDto([]);

        self::expectException(LockException::class);
        $this->invokeMethod($logs, 'processStartingPoints', [$dto, ['1' => ['node_id' => []]]]);
    }

    /**
     * @covers \Hanaboso\PipesFramework\Logs\LogsAbstract::getNodeName
     * @throws ReflectionException
     */
    public function testGetNodeName(): void
    {
        $logs = self::$container->get('hbpf.elastic.logs');

        $result = $this->invokeMethod($logs, 'getNodeName', ['1']);
        self::assertEmpty($result);
    }

    /**
     * @throws Exception
     */
    private function prepareData(): void
    {
        $client = self::$container->get('elastica.client');
        $index  = $client->getIndex('logstash');
        $index->create([], TRUE);

        $node = (new Node())->setType(TypeEnum::START)->setName('Node');
        $this->dm->persist($node);
        $this->dm->flush();

        for ($i = 1; $i <= 10; $i++) {
            $index->getType('_doc')->addDocument(
                new Document(
                    $i,
                    [
                        '@timestamp' => DateTimeUtils::getUtcDateTime(),
                        'version'    => sprintf('Version %s', $i),
                        'message'    => sprintf('Message %s', $i),
                        'host'       => sprintf('Host %s', $i),
                        'pipes'      => [
                            '@timestamp'     => DateTimeUtils::getUtcDateTime(),
                            'type'           => 'starting_point',
                            'hostname'       => sprintf('Hostname %s', $i),
                            'channel'        => sprintf('Channel %s', $i),
                            'severity'       => $i === 5 ? 'ERROR' : 'INFO',
                            'correlation_id' => sprintf('Correlation ID %s', $i),
                            'topology_id'    => sprintf('Topology ID %s', $i),
                            'topology_name'  => sprintf('Topology Name %s', $i),
                            'node_id'        => $node->getId(),
                            'node_name'      => sprintf('Node Name %s', $i),
                            'stacktrace'     => [
                                'message' => sprintf('Message %s', $i),
                                'class'   => sprintf('Class %s', $i),
                                'file'    => sprintf('File %s', $i),
                                'trace'   => sprintf('Trace %s', $i),
                                'code'    => sprintf('Code %s', $i),
                            ],
                        ],
                    ]
                )
            );
        }

        $index->refresh();
    }

}
