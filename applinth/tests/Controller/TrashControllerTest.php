<?php declare(strict_types=1);

namespace ApplinthTests\Controller;

use ApplinthTests\ControllerTestCaseAbstract;
use Doctrine\ODM\MongoDB\MongoDBException;
use Exception;
use Hanaboso\PipesFramework\UserTask\Document\UserTask;
use Hanaboso\PipesFramework\UserTask\Document\UserTaskMessage;
use Hanaboso\PipesFramework\UserTask\Enum\UserTaskEnum;
use Hanaboso\PipesFramework\UserTask\Model\UserTaskManager;
use Hanaboso\Utils\Exception\EnumException;
use Hanaboso\Utils\String\Json;
use Hanaboso\Utils\System\PipesHeaders;
use RabbitMqBundle\Publisher\Publisher;

/**
 * Class TrashControllerTest
 *
 * @package ApplinthTests\Controller
 */
final class TrashControllerTest extends ControllerTestCaseAbstract
{

    private const IDS = ['507f191e810c19729de860ea', '507f191e810c19729de860eb'];

    /**
     * @return void
     * @throws EnumException
     * @throws MongoDBException
     * @throws Exception
     */
    public function testGetTrashItems(): void
    {
        $this->prepData();
        $this->assertResponseLogged(
            $this->getJwsToken(),
            __DIR__ . '/data/TrashController/getTrashItemsRequest.json',
            [
                'created' => '2020-02-02 10:10:10',
                'updated' => '2020-02-02 10:10:10',
            ],
        );
    }

    /**
     * @return void
     * @throws EnumException
     * @throws MongoDBException
     * @throws Exception
     */
    public function testGetTrashItemDetail(): void
    {
        $this->prepData();
        $this->assertResponseLogged(
            $this->getJwsToken(),
            __DIR__ . '/data/TrashController/getTrashItemDetailRequest.json',
            [
                'created' => '2020-02-02 10:10:10',
                'updated' => '2020-02-02 10:10:10',
            ],
        );
    }

    /**
     * @return void
     * @throws EnumException
     * @throws MongoDBException
     * @throws Exception
     */
    public function testUpdateTrashItem(): void
    {
        $this->prepData();
        $this->assertResponseLogged(
            $this->getJwsToken(),
            __DIR__ . '/data/TrashController/updateTrashItemRequest.json',
            [
                'created' => '2020-02-02 10:10:10',
                'updated' => '2020-02-02 10:10:10',
            ],
        );
    }

    /**
     * @return void
     * @throws EnumException
     * @throws MongoDBException
     * @throws Exception
     */
    public function testAcceptTrashItem(): void
    {
        $this->prepData();
        $this->assertResponseLogged(
            $this->getJwsToken(),
            __DIR__ . '/data/TrashController/acceptTrashItemRequest.json',
        );
    }

    /**
     * @return void
     * @throws EnumException
     * @throws MongoDBException
     * @throws Exception
     */
    public function testRejectTrashItem(): void
    {
        $this->prepData('reject');
        $this->assertResponseLogged(
            $this->getJwsToken(),
            __DIR__ . '/data/TrashController/rejectTrashItemRequest.json',
        );
    }

    /**
     * @return void
     * @throws EnumException
     * @throws MongoDBException
     * @throws Exception
     */
    public function testAcceptTrashItems(): void
    {
        $this->prepData();
        $this->assertResponseLogged(
            $this->getJwsToken(),
            __DIR__ . '/data/TrashController/acceptTrashItemsRequest.json',
        );

        $repo = $this->dm->getRepository(UserTask::class);
        self::assertCount(1, $repo->findAll());
    }

    /**
     * @return void
     * @throws EnumException
     * @throws MongoDBException
     * @throws Exception
     */
    public function testRejectTrashItems(): void
    {
        $this->prepData('reject');
        $this->assertResponseLogged(
            $this->getJwsToken(),
            __DIR__ . '/data/TrashController/rejectTrashItemsRequest.json',
        );

        $repo = $this->dm->getRepository(UserTask::class);
        self::assertCount(1, $repo->findAll());
    }

    /**
     * @param string $state
     * @param int    $amount
     *
     * @return void
     * @throws MongoDBException
     * @throws EnumException
     */
    private function prepData(string $state = 'accept', int $amount = 1): void
    {
        $userTask = new UserTask();
        $this->setProperty($userTask, 'id', '107f191e810c19729de860ac');
        $userTask->setNodeId('node')
            ->setTopologyId('topo')
            ->setReturnExchange('')
            ->setNodeName('')
            ->setTopologyName('')
            ->setReturnRoutingKey('')
            ->setCorrelationId('corr1')
            ->setType(UserTaskEnum::USER_TASK)
            ->setMessage((new UserTaskMessage())->setBody('body'))
            ->setUser('endUser');

        $this->dm->persist($userTask);

        for ($i = 0; $i < $amount; $i++) {
            $trashUserTask = new UserTask();
            if (count(self::IDS) > $i) {
                $this->setProperty($trashUserTask, 'id', self::IDS[$i]);
            }
            $trashUserTask->setNodeId('node')
                ->setTopologyId('topo')
                ->setReturnExchange('')
                ->setNodeName('')
                ->setTopologyName('')
                ->setReturnRoutingKey('')
                ->setCorrelationId('corr')
                ->setType(UserTaskEnum::TRASH)
                ->setMessage((new UserTaskMessage())->setBody('body'))
                ->setUser('endUser');

            $this->dm->persist($trashUserTask);
        }
        $this->dm->flush();

        $publisher = self::createMock(Publisher::class);
        $publisher->method('publish')->willReturnCallback(
            static function (string $body) use ($state): void {
                $parsed = Json::decode($body);
                self::assertEquals('body', $parsed['body']);
                self::assertArrayHasKey('user-task-state', $parsed['headers']);
                self::assertEquals(PipesHeaders::get('user-task-state', $parsed['headers']), $state);
            },
        );
        $c   = self::getContainer();
        $svc = new UserTaskManager($this->dm, $c->get('hbpf.user_task.filter.user_task'), $publisher);
        $c->set('hbpf.user_task.manager.user_task', $svc);
    }

}
