<?php declare(strict_types=1);

namespace PipesFrameworkTests\Integration\Logs;

use DateTime;
use Exception;
use Hanaboso\CommonsBundle\Enum\TypeEnum;
use Hanaboso\MongoDataGrid\GridRequestDto;
use Hanaboso\PipesFramework\Logs\Document\Logs;
use Hanaboso\PipesFramework\Logs\Document\Pipes;
use Hanaboso\PipesFramework\Logs\Document\Stacktrace;
use Hanaboso\PipesPhpSdk\Database\Document\Node;
use PipesFrameworkTests\DatabaseTestCaseAbstract;

/**
 * Class MongoDbLogsTest
 *
 * @package PipesFrameworkTests\Integration\Logs
 */
final class MongoDbLogsTest extends DatabaseTestCaseAbstract
{

    /**
     * @covers \Hanaboso\PipesFramework\Logs\MongoDbLogs
     * @covers \Hanaboso\PipesFramework\Logs\MongoDbLogs::getData
     * @covers \Hanaboso\PipesFramework\Logs\MongoDbLogs::getNonEmptyValue
     * @covers \Hanaboso\PipesFramework\Logs\MongoDbLogs::processStartingPoints
     * @covers \Hanaboso\PipesFramework\Logs\LogsFilter::filterCols
     * @covers \Hanaboso\PipesFramework\Logs\LogsFilter::orderCols
     * @covers \Hanaboso\PipesFramework\Logs\LogsFilter::searchableCols
     * @covers \Hanaboso\PipesFramework\Logs\LogsFilter::useTextSearch
     * @covers \Hanaboso\PipesFramework\Logs\LogsFilter::prepareSearchQuery
     * @covers \Hanaboso\PipesFramework\Logs\LogsFilter::setDocument
     * @covers \Hanaboso\PipesFramework\Logs\StartingPointsFilter::setDocument
     * @covers \Hanaboso\PipesFramework\Logs\LogsAbstract
     *
     * @throws Exception
     */
    public function testGetData(): void
    {
        $this->prepareData();

        $result = self::$container->get('hbpf.mongodb.logs')->getData(
            new GridRequestDto(
                [
                    'filter' => '{"correlation_id":"Correlation ID 5"}',
                ],
            ),
        );

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
            $result,
        );
    }

    /**
     * @throws Exception
     */
    private function prepareData(): void
    {
        $node = (new Node())->setType(TypeEnum::START)->setName('Node');
        $this->dm->persist($node);
        $this->dm->flush();

        for ($i = 1; $i <= 10; $i++) {
            $stacktrace = new Stacktrace();
            $this->setProperty($stacktrace, 'message', sprintf('Message %s', $i));
            $this->setProperty($stacktrace, 'class', sprintf('Class %s', $i));
            $this->setProperty($stacktrace, 'file', sprintf('File %s', $i));
            $this->setProperty($stacktrace, 'trace', sprintf('Trace %s', $i));
            $this->setProperty($stacktrace, 'code', sprintf('Code %s', $i));

            $pipes = new Pipes();
            $this->setProperty($pipes, 'timestamp', new DateTime());
            $this->setProperty($pipes, 'type', 'starting_point');
            $this->setProperty($pipes, 'hostname', 'Hostname ');
            $this->setProperty($pipes, 'channel', sprintf('Channel %s', $i));
            $this->setProperty($pipes, 'severity', 'ERROR');
            $this->setProperty($pipes, 'correlationId', sprintf('Correlation ID %s', $i));
            $this->setProperty($pipes, 'topologyId', sprintf('Topology ID %s', $i));
            $this->setProperty($pipes, 'topologyName', sprintf('Topology Name %s', $i));
            $this->setProperty($pipes, 'nodeId', $node->getId());
            $this->setProperty($pipes, 'nodeName', sprintf('Node Name %s', $i));
            $this->setProperty($pipes, 'stacktrace', $stacktrace);
            $this->dm->persist($pipes);

            $logs = new Logs();
            $this->setProperty($logs, 'timestamp', new DateTime());
            $this->setProperty($logs, 'pipes', $pipes);
            $this->setProperty($logs, 'version', sprintf('Version %s', $i));
            $this->setProperty($logs, 'message', sprintf('Message %s', $i));
            $this->setProperty($logs, 'host', sprintf('Host %s', $i));
            $this->dm->persist($logs);
        }

        $this->dm->flush();
    }

}
