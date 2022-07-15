<?php declare(strict_types=1);

namespace PipesFrameworkTests\Controller\HbPFUserTaskBundle\Controller;

use Exception;
use Hanaboso\PipesFramework\UserTask\Document\UserTask;
use Hanaboso\PipesFramework\UserTask\Document\UserTaskMessage;
use Hanaboso\PipesFramework\UserTask\Enum\UserTaskEnum;
use Hanaboso\PipesFramework\UserTask\Model\UserTaskManager;
use Hanaboso\Utils\String\Json;
use Hanaboso\Utils\System\PipesHeaders;
use PipesFrameworkTests\ControllerTestCaseAbstract;
use RabbitMqBundle\Publisher\Publisher;

/**
 * Class UserTaskControllerTest
 *
 * @package PipesFrameworkTests\Controller\HbPFUserTaskBundle\Controller
 *
 * @covers  \Hanaboso\PipesFramework\HbPFUserTaskBundle\Controller\UserTaskController
 * @covers  \Hanaboso\PipesFramework\HbPFUserTaskBundle\Handler\UserTaskHandler
 * @covers  \Hanaboso\PipesFramework\UserTask\Model\UserTaskManager
 * @covers  \Hanaboso\PipesFramework\UserTask\Model\UserTaskFilter
 * @covers  \Hanaboso\PipesFramework\UserTask\Repository\UserTaskRepository
 */
final class UserTaskControllerTest extends ControllerTestCaseAbstract
{

    private const IDS = ['507f191e810c19729de860ea', '507f191e810c19729de860eb'];

    /**
     * @covers \Hanaboso\PipesFramework\HbPFUserTaskBundle\Controller\UserTaskController::getAction
     *
     * @throws Exception
     */
    public function testGet(): void
    {
        $this->prepData();
        $this->assertResponseLogged(
            $this->jwt,
            __DIR__ . '/data/getRequest.json',
            [
                'created' => '2020-02-02 10:10:10',
                'updated' => '2020-02-02 10:10:10',
            ],
        );
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFUserTaskBundle\Controller\UserTaskController::getAction
     *
     * @throws Exception
     */
    public function testGetNotFound(): void
    {
        $this->prepData();
        $this->assertResponseLogged($this->jwt, __DIR__ . '/data/getNotFoundRequest.json');
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFUserTaskBundle\Controller\UserTaskController::filterAction
     *
     * @throws Exception
     */
    public function testFilter(): void
    {
        $this->prepData();
        $this->assertResponseLogged(
            $this->jwt,
            __DIR__ . '/data/filterRequest.json',
            [
                'created' => '2020-02-02 10:10:10',
                'updated' => '2020-02-02 10:10:10',
            ],
        );
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFUserTaskBundle\Controller\UserTaskController::filterAction
     *
     * @throws Exception
     */
    public function testFilterNative(): void
    {
        $this->prepData('accept', 2);
        $this->assertResponseLogged(
            $this->jwt,
            __DIR__ . '/data/filterNativeRequest.json',
            [
                'created' => '2020-02-02T10:10:10Z',
                'updated' => '2020-02-02T10:10:10Z',
            ],
        );
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFUserTaskBundle\Controller\UserTaskController::filterAction
     *
     * @throws Exception
     */
    public function testFilterError(): void
    {
        $this->prepData();
        $this->assertResponseLogged($this->jwt, __DIR__ . '/data/filterErrorRequest.json');
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFUserTaskBundle\Controller\UserTaskController::updateAction
     *
     * @throws Exception
     */
    public function testUpdate(): void
    {
        $this->prepData();
        $this->assertResponseLogged(
            $this->jwt,
            __DIR__ . '/data/updateRequest.json',
            [
                'created' => '2020-02-02 10:10:10',
                'updated' => '2020-02-02 10:10:10',
            ],
        );
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFUserTaskBundle\Controller\UserTaskController::updateAction
     *
     * @throws Exception
     */
    public function testErrorUpdate(): void
    {
        $this->prepData();
        $this->assertResponseLogged($this->jwt, __DIR__ . '/data/updateErrorRequest.json');
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFUserTaskBundle\Controller\UserTaskController::acceptAction
     *
     * @throws Exception
     */
    public function testAccept(): void
    {
        $this->prepData();
        $this->assertResponseLogged($this->jwt, __DIR__ . '/data/acceptRequest.json');

        $repo = $this->dm->getRepository(UserTask::class);
        self::assertEmpty($repo->findAll());
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFUserTaskBundle\Controller\UserTaskController::acceptAction
     *
     * @throws Exception
     */
    public function testAcceptError(): void
    {
        $this->prepData();
        $this->assertResponseLogged($this->jwt, __DIR__ . '/data/acceptErrorRequest.json');

        $repo = $this->dm->getRepository(UserTask::class);
        self::assertNotEmpty($repo->findAll());
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFUserTaskBundle\Controller\UserTaskController::rejectAction
     *
     * @throws Exception
     */
    public function testReject(): void
    {
        $this->prepData('reject');
        $this->assertResponseLogged($this->jwt, __DIR__ . '/data/rejectRequest.json');

        $repo = $this->dm->getRepository(UserTask::class);
        self::assertEmpty($repo->findAll());
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFUserTaskBundle\Controller\UserTaskController::rejectAction
     *
     * @throws Exception
     */
    public function testRejectError(): void
    {
        $this->prepData('reject');
        $this->assertResponseLogged($this->jwt, __DIR__ . '/data/rejectErrorRequest.json');

        $repo = $this->dm->getRepository(UserTask::class);
        self::assertNotEmpty($repo->findAll());
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFUserTaskBundle\Controller\UserTaskController::acceptBatchAction
     *
     * @throws Exception
     */
    public function testAcceptBatchError(): void
    {
        $this->prepData();
        $this->assertResponseLogged($this->jwt, __DIR__ . '/data/acceptBatchErrorRequest.json');

        $repo = $this->dm->getRepository(UserTask::class);
        self::assertNotEmpty($repo->findAll());
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFUserTaskBundle\Controller\UserTaskController::acceptBatchAction
     *
     * @throws Exception
     */
    public function testAcceptBatch(): void
    {
        $this->prepData('accept', 2);
        $this->assertResponseLogged($this->jwt, __DIR__ . '/data/acceptBatchRequest.json');

        $repo = $this->dm->getRepository(UserTask::class);
        self::assertEmpty($repo->findAll());
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFUserTaskBundle\Controller\UserTaskController::rejectBatchAction
     *
     * @throws Exception
     */
    public function testRejectBatch(): void
    {
        $this->prepData('reject');
        $this->assertResponseLogged($this->jwt, __DIR__ . '/data/rejectBatchRequest.json');

        $repo = $this->dm->getRepository(UserTask::class);
        self::assertEmpty($repo->findAll());
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFUserTaskBundle\Controller\UserTaskController::rejectBatchAction
     *
     * @throws Exception
     */
    public function testRejectBatchError(): void
    {
        $this->prepData('reject');
        $this->assertResponseLogged($this->jwt, __DIR__ . '/data/rejectBatchErrorRequest.json');

        $repo = $this->dm->getRepository(UserTask::class);
        self::assertNotEmpty($repo->findAll());
    }

    /**
     * @param string $state
     * @param int    $amount
     *
     * @throws Exception
     */
    private function prepData(string $state = 'accept', int $amount = 1): void
    {
        for ($i = 0; $i < $amount; $i++) {
            $userTask = new UserTask();
            if (count(self::IDS) > $i) {
                $this->setProperty($userTask, 'id', self::IDS[$i]);
            }
            $userTask->setNodeId('node')
                ->setTopologyId('topo')
                ->setReturnExchange('')
                ->setNodeName('')
                ->setTopologyName('')
                ->setReturnRoutingKey('')
                ->setCorrelationId('corr')
                ->setType(UserTaskEnum::USER_TASK)
                ->setMessage((new UserTaskMessage())->setBody('body'));

            $this->dm->persist($userTask);
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
