<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: venca
 * Date: 3/20/18
 * Time: 10:59 AM
 */

namespace Tests\Unit\Logs;

use Doctrine\MongoDB\Query\Query;
use Hanaboso\PipesFramework\Logs\MongoDbLogs;
use Hanaboso\PipesFramework\Logs\MongoDbStorage;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Class MongoDbLogsTest
 *
 * @coversDefaultClass Hanaboso\PipesFramework\Logs\MongoDbLogs
 * @package            Tests\Unit\Logs
 */
class MongoDbLogsTest extends TestCase
{

    /**
     * @param array $data
     *
     * @return Query
     * @throws \ReflectionException
     */
    private function createQuery(array $data): Query
    {
        /** @var Query|MockObject $query */
        $query = $this->createMock(Query::class);
        $query->method('toArray')->willReturn($data);
        $query->method('count')->willReturn(count($data));

        return $query;
    }

    /**
     * @covers ::getData()
     * @throws
     */
    public function testGetDataEmpty(): void
    {
        /** @var MongoDbStorage|MockObject $mongoDbStorage */
        $mongoDbStorage = $this->createMock(MongoDbStorage::class);
        $mongoDbStorage->method('getLogsQuery')->willReturn($this->createQuery([]));
        $mongoDbStorage->method('getStartingPointQuery')->willReturn($this->createQuery([]));
        $mongoDbStorage->method('getNodeData')->willReturn([]);

        $logs = new MongoDbLogs($mongoDbStorage);

        $data = $logs->getData('20', '0');

        $this->assertSame(
            [
                'limit'  => '20',
                'offset' => '0',
                'count'  => '0',
                'total'  => '0',
                'items'  => [],
            ],
            $data
        );
    }

    /**
     * @covers ::getData()
     * @throws
     */
    public function testGetData(): void
    {
        /** @var MongoDbStorage|MockObject $mongoDbStorage */
        $mongoDbStorage = $this->createMock(MongoDbStorage::class);
        $mongoDbStorage->method('getLogsQuery')->willReturn($this->createQuery([
            [
                'pipes' => [],
            ],
            [
                'pipes' => [
                    'correlation_id' => '123',
                    'node_id'        => '123',
                ],
            ],
        ]));
        $mongoDbStorage->method('getStartingPointQuery')->willReturn($this->createQuery([
            [
                'pipes' => [
                    'correlation_id' => '123',
                    'topology_id'    => '456',
                    'topology_name'  => 'top',
                ],
            ],
        ]));
        $mongoDbStorage->method('getNodeData')->willReturn([
            'name' => 'nod',
        ]);

        $logs = new MongoDbLogs($mongoDbStorage);

        $data = $logs->getData('20', '0');

        $this->assertSame(
            [
                'limit'  => '20',
                'offset' => '0',
                'count'  => '2',
                'total'  => '2',
                'items'  => [
                    [
                        'id'             => '',
                        'severity'       => '',
                        'message'        => '',
                        'type'           => '',
                        'correlation_id' => '',
                        'topology_id'    => '',
                        'topology_name'  => '',
                        'node_id'        => '',
                        'node_name'      => '',
                        'timestamp'      => '',
                    ],
                    [
                        'id'             => '',
                        'severity'       => '',
                        'message'        => '',
                        'type'           => '',
                        'correlation_id' => '123',
                        'topology_id'    => '456',
                        'topology_name'  => 'top',
                        'node_id'        => '123',
                        'node_name'      => 'nod',
                        'timestamp'      => '',
                    ],
                ],
            ],
            $data
        );
    }

}