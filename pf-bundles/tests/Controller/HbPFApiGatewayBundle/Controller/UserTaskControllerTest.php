<?php declare(strict_types=1);

namespace PipesFrameworkTests\Controller\HbPFApiGatewayBundle\Controller;

use Exception;
use Hanaboso\PipesFramework\Database\Document\Topology;
use Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\UserTaskController;
use Hanaboso\PipesFramework\UserTask\Document\UserTask;
use Hanaboso\PipesFramework\UserTask\Document\UserTaskMessage;
use Hanaboso\PipesFramework\UserTask\Enum\UserTaskEnum;
use Hanaboso\PipesFramework\UserTask\Model\UserTaskManager;
use Hanaboso\Utils\String\Json;
use Hanaboso\Utils\System\PipesHeaders;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PipesFrameworkTests\ControllerTestCaseAbstract;
use RabbitMqBundle\Publisher\Publisher;

/**
 * Class UserTaskControllerTest
 *
 * @package PipesFrameworkTests\Controller\HbPFApiGatewayBundle\Controller
 */
#[CoversClass(UserTaskController::class)]
#[AllowMockObjectsWithoutExpectations]
final class UserTaskControllerTest extends ControllerTestCaseAbstract
{

    private const string ID = '507f191e810c19729de860ea';

    /**
     * @throws Exception
     */
    public function testGet(): void
    {
        $this->prepData();
        $this->assertResponseLogged(
            $this->jwt,
            __DIR__ . '/data/UserTaskController/getRequest.json',
            [
                'created'         => '2020-02-02 10:10:10',
                'topologyId'      => 'topo',
                'topologyVersion' => '0',
                'updated'         => '2020-02-02 10:10:10',
            ],
        );
    }

    /**
     * @throws Exception
     */
    public function testFilter(): void
    {
        $this->prepData();
        $this->assertResponseLogged(
            $this->jwt,
            __DIR__ . '/data/UserTaskController/filterRequest.json',
            [
                'created'         => '2020-02-02 10:10:10',
                'topologyId'      => 'topo',
                'topologyVersion' => '0',
                'updated'         => '2020-02-02 10:10:10',
            ],
        );
    }

    /**
     * @throws Exception
     */
    public function testUpdate(): void
    {
        $this->prepData();
        $this->assertResponseLogged(
            $this->jwt,
            __DIR__ . '/data/UserTaskController/updateRequest.json',
            [
                'created'         => '2020-02-02 10:10:10',
                'topologyId'      => 'topo',
                'topologyVersion' => '0',
                'updated'         => '2020-02-02 10:10:10',
            ],
        );
    }

    /**
     * @throws Exception
     */
    public function testAccept(): void
    {
        $this->prepData();
        $this->assertResponseLogged($this->jwt, __DIR__ . '/data/UserTaskController/acceptRequest.json');

        $repo = $this->dm->getRepository(UserTask::class);
        self::assertEmpty($repo->findAll());
    }

    /**
     * @throws Exception
     */
    public function testReject(): void
    {
        $this->prepData('reject');
        $this->assertResponseLogged($this->jwt, __DIR__ . '/data/UserTaskController/rejectRequest.json');

        $repo = $this->dm->getRepository(UserTask::class);
        self::assertEmpty($repo->findAll());
    }

    /**
     * @throws Exception
     */
    public function testAcceptBatchError(): void
    {
        $this->prepData();
        $this->assertResponseLogged($this->jwt, __DIR__ . '/data/UserTaskController/acceptBatchRequest.json');

        $repo = $this->dm->getRepository(UserTask::class);
        self::assertEmpty($repo->findAll());
    }

    /**
     * @throws Exception
     */
    public function testRejectBatch(): void
    {
        $this->prepData('reject');
        $this->assertResponseLogged($this->jwt, __DIR__ . '/data/UserTaskController/rejectBatchRequest.json');

        $repo = $this->dm->getRepository(UserTask::class);
        self::assertEmpty($repo->findAll());
    }

    /**
     * @param string $state
     *
     * @throws Exception
     */
    private function prepData(string $state = 'accept'): void
    {
        $topology = new Topology();
        $this->pfd($topology, TRUE);

        $userTask = new UserTask();
        $this->setProperty($userTask, 'id', self::ID);
        $userTask->setNodeId('node')
            ->setTopologyId($topology->getId())
            ->setReturnExchange('')
            ->setNodeName('')
            ->setTopologyName('')
            ->setReturnRoutingKey('')
            ->setCorrelationId('corr')
            ->setType(UserTaskEnum::USER_TASK->value)
            ->setMessage((new UserTaskMessage())->setBody('body'));

        $this->dm->persist($userTask);
        $this->dm->flush();

        $publisher = self::createMock(Publisher::class);
        $publisher->method('publish')->willReturnCallback(
            static function (string $body) use ($state): void {
                $parsed = Json::decode($body);
                self::assertEquals('body', $parsed['body']);
                self::assertArrayHasKey('user-task-state', $parsed['headers']);
                self::assertSame(PipesHeaders::get('user-task-state', $parsed['headers']), $state);
            },
        );
        $c   = self::getContainer();
        $svc = new UserTaskManager(
            $this->dm,
            $c->get('hbpf.user_task.filter.user_task'),
            $c->get('hbpf.user_task.aggregation-filter.user_task'),
            $publisher,
        );
        $c->set('hbpf.user_task.manager.user_task', $svc);
    }

}
