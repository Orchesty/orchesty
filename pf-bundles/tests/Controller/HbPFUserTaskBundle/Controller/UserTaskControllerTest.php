<?php declare(strict_types=1);

namespace PipesFrameworkTests\Controller\HbPFUserTaskBundle\Controller;

use ArrayObject;
use Exception;
use Hanaboso\PipesFramework\Database\Document\Topology;
use Hanaboso\PipesFramework\HbPFUserTaskBundle\Controller\UserTaskController;
use Hanaboso\PipesFramework\HbPFUserTaskBundle\Handler\UserTaskHandler;
use Hanaboso\PipesFramework\UserTask\Document\UserTask;
use Hanaboso\PipesFramework\UserTask\Document\UserTaskMessage;
use Hanaboso\PipesFramework\UserTask\Enum\UserTaskEnum;
use Hanaboso\PipesFramework\UserTask\Model\UserTaskFilter;
use Hanaboso\PipesFramework\UserTask\Model\UserTaskManager;
use Hanaboso\PipesFramework\UserTask\Repository\UserTaskRepository;
use Hanaboso\PipesFramework\Utils\JsonSchemaArgsValidator;
use Hanaboso\Utils\String\Json;
use Hanaboso\Utils\System\PipesHeaders;
use MongoDB\BSON\UTCDateTime;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PipesFrameworkTests\ControllerTestCaseAbstract;
use RabbitMqBundle\Publisher\Publisher;

/**
 * Class UserTaskControllerTest
 *
 * @package PipesFrameworkTests\Controller\HbPFUserTaskBundle\Controller
 */
#[CoversClass(UserTaskController::class)]
#[CoversClass(UserTaskHandler::class)]
#[CoversClass(UserTaskManager::class)]
#[CoversClass(UserTaskFilter::class)]
#[CoversClass(UserTaskRepository::class)]
#[CoversClass(JsonSchemaArgsValidator::class)]
#[AllowMockObjectsWithoutExpectations]
final class UserTaskControllerTest extends ControllerTestCaseAbstract
{

    private const array IDS = ['507f191e810c19729de860ea', '507f191e810c19729de860eb'];

    /**
     * @throws Exception
     */
    public function testGet(): void
    {
        $this->prepData();
        $this->assertResponseLogged(
            $this->jwt,
            __DIR__ . '/data/getRequest.json',
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
    public function testGetNotFound(): void
    {
        $this->prepData();
        $this->assertResponseLogged($this->jwt, __DIR__ . '/data/getNotFoundRequest.json');
    }

    /**
     * @throws Exception
     */
    public function testFilter(): void
    {
        $this->prepData();
        $this->assertResponseLogged(
            $this->jwt,
            __DIR__ . '/data/filterRequest.json',
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
    public function testFilterNative(): void
    {
        $this->prepData('accept', 2);
        $this->assertResponseLogged(
            $this->jwt,
            __DIR__ . '/data/filterNativeRequest.json',
            [
                'created'         => '2020-02-02T10:10:10Z',
                'topologyId'      => 'topo',
                'topologyVersion' => '0',
                'updated'         => '2020-02-02T10:10:10Z',
            ],
        );
    }

    /**
     * @throws Exception
     */
    public function testFilterError(): void
    {
        $this->prepData();
        $this->assertResponseLogged($this->jwt, __DIR__ . '/data/filterErrorRequest.json');
    }

    /**
     * @throws Exception
     */
    public function testUpdate(): void
    {
        $this->prepData();
        $this->assertResponseLogged(
            $this->jwt,
            __DIR__ . '/data/updateRequest.json',
            [
                'created'    => '2020-02-02 10:10:10',
                'topologyId' => 'topo',
                'updated'    => '2020-02-02 10:10:10',
            ],
        );
    }

    /**
     * @throws Exception
     */
    public function testErrorUpdate(): void
    {
        $this->prepData();
        $this->assertResponseLogged($this->jwt, __DIR__ . '/data/updateErrorRequest.json');
    }

    /**
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
     * @throws Exception
     */
    public function testAnswer(): void
    {
        $published = $this->prepQuestion();
        $this->assertResponseLogged($this->jwt, __DIR__ . '/data/answerRequest.json');

        self::assertEmpty($this->dm->getRepository(UserTask::class)->findAll());
        self::assertCount(1, $published);
        $message = $published->getArrayCopy()[0];
        self::assertSame(
            ['city' => 'Prague', 'keep' => 'yes', 'count' => 3],
            Json::decode($message['body']),
        );
        self::assertSame('accept', PipesHeaders::get('user-task-state', $message['headers']));
        self::assertSame('accept', $message['headers']['pulse-decision'] ?? NULL);
    }

    /**
     * @throws Exception
     */
    public function testAnswerUnknownField(): void
    {
        $published = $this->prepQuestion();
        $res       = $this->sendPost(
            sprintf('/pulse/user-task/%s/answer', self::IDS[0]),
            [],
            ['answer' => ['city' => 'Prague', 'keep' => 'no']],
        );

        self::assertSame(400, $res->status);
        self::assertStringContainsString('keep', $res->content->message);
        self::assertCount(0, $published);
    }

    /**
     * @throws Exception
     */
    public function testAnswerSchemaLessOnlyAddsFields(): void
    {
        // Two requests: a kernel reboot between them would drop the mocked publisher
        $this->client->disableReboot();
        $published = $this->prepQuestion(schema: []);

        $res = $this->sendPost(
            sprintf('/pulse/user-task/%s/answer', self::IDS[0]),
            [],
            ['answer' => ['city' => 'Prague']],
        );
        self::assertSame(400, $res->status);
        self::assertCount(0, $published);

        $res = $this->sendPost(
            sprintf('/pulse/user-task/%s/answer', self::IDS[0]),
            [],
            ['answer' => ['zip' => '60200']],
        );
        self::assertSame(200, $res->status);
        self::assertSame(
            ['city' => 'Brno', 'keep' => 'yes', 'zip' => '60200'],
            Json::decode($published->getArrayCopy()[0]['body']),
        );
    }

    /**
     * @throws Exception
     */
    public function testAnswerKeepsNestedObjects(): void
    {
        $published = $this->prepQuestion(body: '{"city":"Brno","meta":{}}');
        $res       = $this->sendPost(
            sprintf('/pulse/user-task/%s/answer', self::IDS[0]),
            [],
            ['answer' => ['city' => 'Prague']],
        );

        self::assertSame(200, $res->status);
        self::assertSame('{"city":"Prague","meta":{}}', $published->getArrayCopy()[0]['body']);
    }

    /**
     * @throws Exception
     */
    public function testAnswerNonObjectBody(): void
    {
        $published = $this->prepQuestion(body: '[1,2]');
        $res       = $this->sendPost(
            sprintf('/pulse/user-task/%s/answer', self::IDS[0]),
            [],
            ['answer' => ['city' => 'Prague']],
        );

        self::assertSame(409, $res->status);
        self::assertCount(0, $published);
    }

    /**
     * @throws Exception
     */
    public function testAnswerAlreadyClaimed(): void
    {
        $published = $this->prepQuestion();
        $this->dm->getDocumentCollection(UserTask::class)->updateOne(
            [],
            ['$set' => ['claimedAt' => new UTCDateTime()]],
        );

        $res = $this->sendPost(
            sprintf('/pulse/user-task/%s/answer', self::IDS[0]),
            [],
            ['answer' => ['city' => 'Prague']],
        );

        self::assertSame(404, $res->status);
        self::assertCount(0, $published);
        self::assertNotEmpty($this->dm->getRepository(UserTask::class)->findAll());
    }

    /**
     * @throws Exception
     */
    public function testPulsePendingHidesMessage(): void
    {
        $this->prepQuestion();
        $this->prepQuestion(identity: 'other@example.com', id: self::IDS[1], mockPublisher: FALSE);

        $res = $this->sendGet('/pulse/user-tasks/pending', TRUE);

        self::assertSame(200, $res->status);
        $items = (array) $res->content;
        self::assertCount(1, $items);
        $item = (array) $items[0];
        self::assertSame(self::IDS[0], $item['id']);
        self::assertSame('Which city and how many?', $item['question']);
        self::assertArrayNotHasKey('message', $item);
        self::assertArrayNotHasKey('replyId', $item);
        self::assertArrayNotHasKey('identity', $item);
    }

    /**
     * @throws Exception
     */
    public function testReplyHeadersAreHiddenAndKeptOnUpdate(): void
    {
        $this->client->disableReboot();
        $this->prepQuestion(mockPublisher: FALSE);
        /** @var UserTask $task */
        $task = $this->dm->getRepository(UserTask::class)->find(self::IDS[0]);
        $task->getMessage()->setHeaders(
            ['identity' => 'test@example.com', 'node-id' => 'node', 'reply-id' => 'r1', 'reply-to' => 'http://trace'],
        );
        $this->dm->flush();

        $detail = (array) $this->sendGet(sprintf('/user-task/%s', self::IDS[0]), TRUE)->content;
        self::assertSame(['identity' => 'test@example.com', 'node-id' => 'node'], $detail['message']['headers']);
        self::assertArrayNotHasKey('replyId', $detail);

        $list = (array) $this->sendGet('/user-tasks', TRUE)->content;
        self::assertSame(['identity' => 'test@example.com', 'node-id' => 'node'], $list['items'][0]['headers']);

        $res = $this->sendPut(
            sprintf('/user-task/%s', self::IDS[0]),
            [],
            ['body' => '{}', 'headers' => ['node-id' => 'node', 'reply-to' => 'http://evil']],
        );
        self::assertSame(200, $res->status);

        $this->dm->clear();
        /** @var UserTask $task */
        $task = $this->dm->getRepository(UserTask::class)->find(self::IDS[0]);
        self::assertEquals(
            ['identity' => 'test@example.com', 'node-id' => 'node', 'reply-id' => 'r1', 'reply-to' => 'http://trace'],
            $task->getMessage()->getHeaders(),
        );
        self::assertStringNotContainsString('r1', Json::encode($task->getAuditLogs()));
    }

    /**
     * @throws Exception
     */
    public function testPulseAcceptQuestionRefused(): void
    {
        $published = $this->prepQuestion();
        $res       = $this->sendPost(sprintf('/pulse/user-task/%s/accept', self::IDS[0]), []);

        self::assertSame(404, $res->status);
        self::assertCount(0, $published);
    }

    /**
     * @throws Exception
     */
    public function testPulseRejectConfirm(): void
    {
        $published = $this->prepQuestion(UserTaskEnum::CONFIRM->value);
        $res       = $this->sendPost(sprintf('/pulse/user-task/%s/reject', self::IDS[0]), []);

        self::assertSame(200, $res->status);
        self::assertCount(1, $published);
        $headers = $published->getArrayCopy()[0]['headers'];
        self::assertSame('reject', PipesHeaders::get('user-task-state', $headers));
        self::assertSame('reject', $headers['pulse-decision'] ?? NULL);
        self::assertEmpty($this->dm->getRepository(UserTask::class)->findAll());
    }

    /**
     * @throws Exception
     */
    public function testPulseRejectCancelsQuestion(): void
    {
        $published = $this->prepQuestion();
        $res       = $this->sendPost(sprintf('/pulse/user-task/%s/reject', self::IDS[0]), []);

        self::assertSame(200, $res->status);
        self::assertSame('reject', $published->getArrayCopy()[0]['headers']['pulse-decision'] ?? NULL);
    }

    /**
     * @throws Exception
     */
    public function testPulseDecideOtherUsersConfirm(): void
    {
        $published = $this->prepQuestion(UserTaskEnum::CONFIRM->value, 'other@example.com');
        $res       = $this->sendPost(sprintf('/pulse/user-task/%s/accept', self::IDS[0]), []);

        self::assertSame(404, $res->status);
        self::assertCount(0, $published);
    }

    /**
     * @throws Exception
     */
    public function testAnswerInvalid(): void
    {
        $published = $this->prepQuestion();
        $this->assertResponseLogged($this->jwt, __DIR__ . '/data/answerInvalidRequest.json');

        self::assertNotEmpty($this->dm->getRepository(UserTask::class)->findAll());
        self::assertCount(0, $published);
    }

    /**
     * @throws Exception
     */
    public function testAnswerMissing(): void
    {
        $this->prepQuestion();
        $this->assertResponseLogged($this->jwt, __DIR__ . '/data/answerMissingRequest.json');

        self::assertNotEmpty($this->dm->getRepository(UserTask::class)->findAll());
    }

    /**
     * @throws Exception
     */
    public function testAnswerNotQuestion(): void
    {
        $this->prepQuestion(UserTaskEnum::CONFIRM->value);
        $this->assertResponseLogged($this->jwt, __DIR__ . '/data/answerNotQuestionRequest.json');

        self::assertNotEmpty($this->dm->getRepository(UserTask::class)->findAll());
    }

    /**
     * @throws Exception
     */
    public function testAnswerIdentity(): void
    {
        // The task belongs to someone else; the forged identity header in the request is ignored
        $this->prepQuestion(identity: 'other@example.com');
        $this->assertResponseLogged($this->jwt, __DIR__ . '/data/answerIdentityRequest.json');

        self::assertNotEmpty($this->dm->getRepository(UserTask::class)->findAll());
    }

    /**
     * @param string       $type
     * @param string       $identity
     * @param mixed[]|null $schema
     * @param string       $body
     * @param string       $id
     * @param bool         $mockPublisher
     *
     * @return ArrayObject<int, array{body: string, headers: mixed[]}>
     * @throws Exception
     */
    private function prepQuestion(
        string $type = UserTaskEnum::QUESTION->value,
        string $identity = 'test@example.com',
        ?array $schema = NULL,
        string $body = '{"city":"Brno","keep":"yes"}',
        string $id = self::IDS[0],
        bool $mockPublisher = TRUE,
    ): ArrayObject
    {
        $topology = new Topology();
        $this->pfd($topology, TRUE);

        $userTask = new UserTask();
        $this->setProperty($userTask, 'id', $id);
        $userTask->setNodeId('node')
            ->setTopologyId($topology->getId())
            ->setReturnExchange('')
            ->setNodeName('')
            ->setTopologyName('')
            ->setReturnRoutingKey('')
            ->setCorrelationId('corr')
            ->setType($type)
            ->setMessage((new UserTaskMessage())->setBody($body));
        $userTask
            ->setIdentity($identity)
            ->setQuestion('Which city and how many?')
            ->setSchema(
                $schema ?? [
                    'properties' => [
                        'city'  => ['type' => 'string'],
                        'count' => ['type' => 'integer'],
                    ],
                    'required'   => ['city'],
                    'type'       => 'object',
                ],
            );
        $this->dm->persist($userTask);
        $this->dm->flush();

        /** @var ArrayObject<int, array{body: string, headers: mixed[]}> $published */
        $published = new ArrayObject();
        if (!$mockPublisher) {
            return $published;
        }

        $publisher = self::createMock(Publisher::class);
        $publisher->method('publish')->willReturnCallback(
            static function (string $body) use ($published): void {
                $decoded = Json::decode($body);
                $published->append(['body' => (string) $decoded['body'], 'headers' => (array) $decoded['headers']]);
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

        return $published;
    }

    /**
     * @param string $state
     * @param int    $amount
     *
     * @throws Exception
     */
    private function prepData(string $state = 'accept', int $amount = 1): void
    {
        $topology = new Topology();
        $this->pfd($topology, TRUE);

        for ($i = 0; $i < $amount; $i++) {
            $userTask = new UserTask();
            if (count(self::IDS) > $i) {
                $this->setProperty($userTask, 'id', self::IDS[$i]);
            }
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
        }
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
