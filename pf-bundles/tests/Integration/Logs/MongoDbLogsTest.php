<?php declare(strict_types=1);

namespace Tests\Integration\Logs;

use DateTime;
use Exception;
use Hanaboso\CommonsBundle\Enum\TypeEnum;
use Hanaboso\MongoDataGrid\GridRequestDto;
use Hanaboso\PipesFramework\Configurator\Document\Node;
use Hanaboso\PipesFramework\Logs\Document\Logs;
use Hanaboso\PipesFramework\Logs\Document\Pipes;
use Hanaboso\PipesFramework\Logs\Document\Stacktrace;
use Tests\DatabaseTestCaseAbstract;
use Tests\PrivateTrait;

/**
 * Class MongoDbLogsTest
 *
 * @package Tests\Integration\Logs
 */
final class MongoDbLogsTest extends DatabaseTestCaseAbstract
{

    use PrivateTrait;

    /**
     * @throws Exception
     */
    public function testGetData(): void
    {
        $this->prepareData();

        $result = $this->ownContainer->get('mongodb.logs')->getData(new GridRequestDto([
            'filter' => '{"correlation_id":"Correlation ID 5"}',
        ]));

        $this->assertEquals([
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
        ], $result);
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
            $this->setProperty($pipes, 'hostname', sprintf('Hostname '));
            $this->setProperty($pipes, 'channel', sprintf('Channel %s', $i));
            $this->setProperty($pipes, 'severity', 'ERROR');
            $this->setProperty($pipes, 'correlation_id', sprintf('Correlation ID %s', $i));
            $this->setProperty($pipes, 'topology_id', sprintf('Topology ID %s', $i));
            $this->setProperty($pipes, 'topology_name', sprintf('Topology Name %s', $i));
            $this->setProperty($pipes, 'node_id', $node->getId());
            $this->setProperty($pipes, 'node_name', sprintf('Node Name %s', $i));
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