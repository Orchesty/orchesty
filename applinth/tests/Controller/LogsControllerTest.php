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
                'updated'    => '2020-02-02 10:10:10',
                'topologyId' => 'topo',
                'id'         => 'id'
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
                'updated'    => '2020-02-02 10:10:10',
                'topologyId' => 'topo',
                'id'         => 'id'
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
                        'timestamp' => '1111',
                        'version'   => '1.2',
                        'message'   => 'msg',
                        'host'      => 'host',
                        'pipes'     => [
                            'user_id'        => 'endUser',
                            'severity'       => 'info',
                            'service'        => 'sdk',
                            'timestamp'      => 2_222,
                            'node_id'        => strval($i),
                            'topology_id'    => '2',
                            'correlation_id' => '1',
                            'process_id'     => '2',
                        ],
                    ],
                )
                ->getQuery()
                ->execute();
        }

        $this->dm->createQueryBuilder(Logs::class)
            ->insert()
            ->setNewObj(
                [
                    'timestamp' => '1111',
                    'version'   => '1.2',
                    'message'   => 'msg',
                    'host'      => 'host',
                    'pipes'     => [
                        'user_id'        => 'User2',
                        'severity'       => 'info',
                        'service'        => 'sdk',
                        'timestamp'      => 2_222,
                        'node_id'        => '1',
                        'topology_id'    => '2',
                        'correlation_id' => '1',
                        'process_id'     => '2',
                    ],
                ],
            )
            ->getQuery()
            ->execute();

    }

}
