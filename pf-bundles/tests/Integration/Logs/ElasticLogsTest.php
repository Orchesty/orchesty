<?php declare(strict_types=1);

namespace PipesFrameworkTests\Integration\Logs;

use Doctrine\ODM\MongoDB\LockException;
use Elastica\Client;
use Elastica\Document;
use Elastica\Exception\ResponseException;
use Elastica\Request;
use Elastica\Response;
use Exception;
use Hanaboso\CommonsBundle\Enum\TypeEnum;
use Hanaboso\MongoDataGrid\GridFilterAbstract;
use Hanaboso\MongoDataGrid\GridRequestDto;
use Hanaboso\PipesFramework\Logs\ElasticLogs;
use Hanaboso\PipesPhpSdk\Database\Document\Node;
use Hanaboso\Utils\Date\DateTimeUtils;
use PipesFrameworkTests\DatabaseTestCaseAbstract;

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
     *
     * @throws Exception
     */
    public function testGetData(): void
    {
        $this->prepareData();

        $logs = $this->getManager();
        $logs->setIndex('');
        $result = $logs->getData(
            new GridRequestDto(
                [
                    'filter' => [
                        [
                            [
                                GridFilterAbstract::COLUMN   => 'severity',
                                GridFilterAbstract::OPERATOR => GridFilterAbstract::EQ,
                                GridFilterAbstract::VALUE    => 'ERROR',
                            ],
                        ],
                    ],
                ],
            ),
            0,
        );

        self::assertEquals(
            [
                'items'  =>
                    [
                        [
                            'id'             => $result['items'][0]['id'],
                            'severity'       => 'ERROR',
                            'message'        => 'Message 5',
                            'type'           => 'starting_point',
                            'correlation_id' => 'Correlation ID 5',
                            'topology_id'    => 'Topology ID 5',
                            'topology_name'  => '',
                            'node_id'        => $result['items'][0]['node_id'],
                            'node_name'      => 'Node',
                            'timestamp'      => $result['items'][0]['timestamp'],
                        ],
                    ],
                'filter' =>
                    [
                        [
                            [
                                'column'   => 'severity',
                                'operator' => 'EQ',
                                'value'    => 'ERROR',
                            ],
                        ],
                    ],
                'sorter' => [],
                'search' => NULL,
                'paging' => [
                    'page'         => 1,
                    'itemsPerPage' => 10,
                    'total'        => 1,
                    'nextPage'     => 1,
                    'lastPage'     => 1,
                    'previousPage' => 1,
                ],
            ],
            $result,
        );
    }

    /**
     * @covers \Hanaboso\PipesFramework\Logs\ElasticLogs::getData
     *
     * @throws Exception
     */
    public function testGetDataErr(): void
    {
        $client = self::createPartialMock(Client::class, ['request']);
        $client->expects(self::any())->method('request')->willThrowException(
            new ResponseException(
                new Request(''),
                new Response(['error' => 'in order to sort on']),
            ),
        );
        $elLogs = new ElasticLogs($this->dm, $client);
        $elLogs->setIndex('');
        $this->setProperty($elLogs, 'client', $client);
        self::expectException(ResponseException::class);
        $elLogs->getData(
            new GridRequestDto(
                [
                    'filter' => [
                        [
                            [
                                GridFilterAbstract::COLUMN   => 'severity',
                                GridFilterAbstract::OPERATOR => GridFilterAbstract::EQ,
                                GridFilterAbstract::VALUE    => 'ERROR',
                            ],
                        ],
                    ],
                ],
            ),
            0,
        );
    }

    /**
     * @covers \Hanaboso\PipesFramework\Logs\ElasticLogs::getData
     *
     * @throws Exception
     */
    public function testGetDataErr2(): void
    {
        $logs = $this->getManager();
        $logs->setIndex('');
        $client = self::createPartialMock(Client::class, ['request']);
        $client->expects(self::any())->method('request')->willThrowException(
            new ResponseException(
                new Request(''),
                new Response(''),
            ),
        );
        $this->setProperty($logs, 'client', $client);

        self::expectException(ResponseException::class);
        $logs->getData(
            new GridRequestDto(
                [
                    'filter' => [
                        [
                            [
                                GridFilterAbstract::COLUMN   => 'severity',
                                GridFilterAbstract::OPERATOR => GridFilterAbstract::EQ,
                                GridFilterAbstract::VALUE    => 'ERROR',
                            ],
                        ],
                    ],
                ],
            ),
            0,
        );
    }

    /**
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
     * @throws Exception
     */
    public function testGetFilterAndSorter(): void
    {
        $logs = $this->getManager();
        $logs->setIndex('');
        $result = $this->invokeMethod(
            $logs,
            'getFilterAndSorter',
            [
                new GridRequestDto(
                    [
                        'search' => 'search',
                        'sorter' => [
                            [
                                GridFilterAbstract::COLUMN    => 'topology_id',
                                GridFilterAbstract::DIRECTION => GridFilterAbstract::ASCENDING,
                            ],
                        ],
                    ],
                ),
            ],
        );

        self::assertEquals(2, count($result));
    }

    /**
     * @covers \Hanaboso\PipesFramework\Logs\LogsAbstract::processStartingPoints
     *
     * @throws Exception
     */
    public function testProcessStartingPointsErr(): void
    {
        $logs = $this->getManager();
        $logs->setIndex('');

        self::expectException(LockException::class);
        $this->invokeMethod($logs, 'processStartingPoints', [['1' => ['correlation_id' => []]]]);
    }

    /**
     * @covers \Hanaboso\PipesFramework\Logs\LogsAbstract::getNodeName
     *
     * @throws Exception
     */
    public function testGetNodeName(): void
    {
        $logs = $this->getManager();
        $logs->setIndex('');

        $result = $this->invokeMethod($logs, 'getNodeName', ['1']);
        self::assertEmpty($result);
    }

    /**
     * @throws Exception
     */
    private function prepareData(): void
    {
        $client = self::getContainer()->get('elastica.client');
        $index  = $client->getIndex('logstash');
        $index->create([], TRUE);

        $node = (new Node())->setType(TypeEnum::START)->setName('Node');
        $this->dm->persist($node);
        $this->dm->flush();

        for ($i = 1; $i <= 10; $i++) {
            $index->addDocument(
                new Document(
                    (string) $i,
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
                    ],
                ),
            );
        }

        $index->refresh();
    }

    /**
     * @return ElasticLogs
     */
    private function getManager(): ElasticLogs {
        $documentManager = self::getContainer()->get('doctrine_mongodb.odm.default_document_manager');
        $client          = self::getContainer()->get('elastica.client');

        return new ElasticLogs($documentManager, $client);
    }

}