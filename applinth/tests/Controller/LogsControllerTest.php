<?php declare(strict_types=1);

namespace ApplinthTests\Controller;

use ApplinthTests\ControllerTestCaseAbstract;
use Doctrine\ODM\MongoDB\MongoDBException;
use Hanaboso\PipesFramework\Logs\Document\Logs;

/**
 * Class LogsControllerTest
 *
 * @package ApplinthTests\Controller
 */
final class LogsControllerTest extends ControllerTestCaseAbstract
{

    /**
     * @return void
     * @throws MongoDBException
     */
    public function testGetDataForTableAction(): void
    {
        $this->prepData();
        $this->assertResponseLogged(
            $this->getJwsToken(),
            __DIR__ . '/data/LogsController/getDataForTableActionRequest.json',
            [
                'created'    => '2020-02-02 10:10:10',
                'id'         => 'id',
                'topologyId' => 'topo',
                'updated'    => '2020-02-02 10:10:10',
            ],
        );
    }

    /**
     * @return void
     * @throws MongoDBException
     */
    public function testGetDataForTableActionFiltered(): void
    {
        $this->prepData();
        $this->assertResponseLogged(
            $this->getJwsToken(),
            __DIR__ . '/data/LogsController/getDataForTableActionFilteredRequest.json',
            [
                'created'    => '2020-02-02 10:10:10',
                'id'         => 'id',
                'topologyId' => 'topo',
                'updated'    => '2020-02-02 10:10:10',
            ],
        );
    }

    /**
     * @return void
     * @throws MongoDBException
     */
    private function prepData(): void
    {
        for ($i = 0; $i < 3; $i++) {
            $this->dm->createQueryBuilder(Logs::class)
                ->insert()
                ->setNewObj(
                    [
                        'host'      => 'host',
                        'message'   => 'msg',
                        'pipes'     => [
                            'correlation_id' => '1',
                            'node_id'        => strval($i),
                            'process_id'     => '2',
                            'service'        => 'sdk',
                            'severity'       => 'info',
                            'timestamp'      => 2_222,
                            'topology_id'    => '2',
                            'user_id'        => 'endUser',
                        ],
                        'timestamp' => '1111',
                        'version'   => '1.2',
                    ],
                )
                ->getQuery()
                ->execute();
        }

        $this->dm->createQueryBuilder(Logs::class)
            ->insert()
            ->setNewObj(
                [
                    'host'      => 'host',
                    'message'   => 'msg',
                    'pipes'     => [
                        'correlation_id' => '1',
                        'node_id'        => '1',
                        'process_id'     => '2',
                        'service'        => 'sdk',
                        'severity'       => 'info',
                        'timestamp'      => 2_222,
                        'topology_id'    => '2',
                        'user_id'        => 'User2',
                    ],
                    'timestamp' => '1111',
                    'version'   => '1.2',
                ],
            )
            ->getQuery()
            ->execute();
    }

}
