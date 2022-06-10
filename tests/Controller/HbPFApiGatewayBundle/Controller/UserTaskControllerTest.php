<?php declare(strict_types=1);

namespace PipesFrameworkTests\Controller\HbPFApiGatewayBundle\Controller;

use Exception;
use Hanaboso\PipesFramework\UserTask\Document\UserTask;
use Hanaboso\PipesFramework\UserTask\Document\UserTaskMessage;
use Hanaboso\PipesFramework\UserTask\Enum\UserTaskEnum;
use Hanaboso\PipesFramework\UserTask\Model\UserTaskManager;
use Hanaboso\Utils\System\PipesHeaders;
use PipesFrameworkTests\ControllerTestCaseAbstract;
use RabbitMqBundle\Publisher\Publisher;

/**
 * Class UserTaskControllerTest
 *
 * @package PipesFrameworkTests\Controller\HbPFApiGatewayBundle\Controller
 *
 * @covers  \Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\UserTaskController
 */
final class UserTaskControllerTest extends ControllerTestCaseAbstract
{

    private const ID = '507f191e810c19729de860ea';

    /**
     * @covers \Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\UserTaskController::getAction
     *
     * @throws Exception
     */
    public function testGet(): void
    {
        $this->prepData();
        $this->assertResponseLogged(
            $this->jwt,
            __DIR__ . '/data/UserTaskController/getRequest.json',
            [
                'created' => '2020-02-02 10:10:10',
                'updated' => '2020-02-02 10:10:10',
            ],
        );
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\UserTaskController::filterAction
     *
     * @throws Exception
     */
    public function testFilter(): void
    {
        $this->prepData();
        $this->assertResponseLogged(
            $this->jwt,
            __DIR__ . '/data/UserTaskController/filterRequest.json',
            [
                'created' => '2020-02-02 10:10:10',
                'updated' => '2020-02-02 10:10:10',
            ],
        );
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\UserTaskController::updateAction
     *
     * @throws Exception
     */
    public function testUpdate(): void
    {
        $this->prepData();
        $this->assertResponseLogged(
            $this->jwt,
            __DIR__ . '/data/UserTaskController/updateRequest.json',
            [
                'created' => '2020-02-02 10:10:10',
                'updated' => '2020-02-02 10:10:10',
            ],
        );
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\UserTaskController::acceptAction
     *
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
     * @covers \Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\UserTaskController::rejectAction
     *
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
     * @covers \Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\UserTaskController::acceptAction
     *
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
     * @covers \Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\UserTaskController::rejectBatchAction
     *
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
        $userTask = new UserTask();
        $this->setProperty($userTask, 'id', self::ID);
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
        $this->dm->flush();

        $publisher = self::createMock(Publisher::class);
        $publisher->method('publish')->willReturnCallback(
            static function (string $body, array $headers) use ($state): void {
                self::assertEquals('body', $body);
                self::assertArrayHasKey(PipesHeaders::createKey('user-task-state'), $headers);
                self::assertEquals(PipesHeaders::get('user-task-state', $headers), $state);
            },
        );
        $c   = self::getContainer();
        $svc = new UserTaskManager($this->dm, $c->get('hbpf.user_task.filter.user_task'), $publisher);
        $c->set('hbpf.user_task.manager.user_task', $svc);
    }

}
