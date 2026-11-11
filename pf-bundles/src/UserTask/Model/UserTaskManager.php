<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\UserTask\Model;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\MappingException;
use Doctrine\ODM\MongoDB\MongoDBException;
use Doctrine\Persistence\ObjectRepository;
use Exception;
use Hanaboso\MongoDataGrid\GridRequestDtoInterface;
use Hanaboso\PipesFramework\Database\Document\Node;
use Hanaboso\PipesFramework\Database\Document\Topology;
use Hanaboso\PipesFramework\Database\Repository\NodeRepository;
use Hanaboso\PipesFramework\Database\Repository\TopologyRepository;
use Hanaboso\PipesFramework\UserTask\Document\UserTask;
use Hanaboso\PipesFramework\UserTask\Enum\UserTaskEnum;
use Hanaboso\PipesFramework\UserTask\Exception\UserTaskAnswerException;
use Hanaboso\PipesFramework\UserTask\Exception\UserTaskConflictException;
use Hanaboso\PipesFramework\UserTask\Exception\UserTaskException;
use Hanaboso\PipesFramework\UserTask\Repository\UserTaskRepository;
use Hanaboso\PipesFramework\Utils\JsonSchemaArgsValidator;
use Hanaboso\Utils\Date\DateTimeUtils;
use Hanaboso\Utils\String\Json;
use JsonException;
use LogicException;
use MongoDB\BSON\UTCDateTime;
use RabbitMqBundle\Publisher\Publisher;
use stdClass;
use Throwable;

/**
 * Class UserTaskManager
 *
 * @package Hanaboso\PipesFramework\UserTask\Model
 */
final readonly class UserTaskManager
{

    private const string STATE_HEADER = 'user-task-state';
    private const string STATE_ACCEPT = 'accept';
    private const string STATE_REJECT = 'reject';

    // Set only when a live Confirm / Question task is decided. The bridge routes Yes / No on it, so a plain
    // trash retry of a parked Confirm (which carries only `user-task-state`) parks it again instead of saying Yes.
    private const string DECISION_HEADER = 'pulse-decision';

    // A claim older than this is treated as abandoned (the deciding request died between claim and delete)
    private const string CLAIMED_AT        = 'claimedAt';
    private const int    CLAIM_TTL_SECONDS = 60;

    /**
     * @var ObjectRepository<UserTask>&UserTaskRepository
     */
    private UserTaskRepository $userTaskRepository;

    /**
     * @var ObjectRepository<Topology>&TopologyRepository
     */
    private TopologyRepository $topologyRepository;

    /**
     * @var ObjectRepository<Node>&NodeRepository
     */
    private NodeRepository $nodeRepository;

    /**
     * UserTaskManager constructor.
     *
     * @param DocumentManager           $dm
     * @param UserTaskFilter            $filter
     * @param UserTaskAggregationFilter $aggregationFilter
     * @param Publisher                 $publisher
     */
    public function __construct(
        private DocumentManager $dm,
        private UserTaskFilter $filter,
        private UserTaskAggregationFilter $aggregationFilter,
        private Publisher $publisher,
    )
    {
        $this->userTaskRepository = $dm->getRepository(UserTask::class);
        $this->topologyRepository = $dm->getRepository(Topology::class);
        $this->nodeRepository     = $dm->getRepository(Node::class);
    }

    /**
     * @param string $id
     *
     * @return UserTask
     * @throws MappingException
     * @throws MongoDBException
     */
    public function get(string $id): UserTask
    {
        /** @var UserTask|null $doc */
        $doc = $this->userTaskRepository->find($id);
        if (!$doc) {
            throw new UserTaskException(
                sprintf('UserTask with id [%s] has been not found', $id),
            );
        }

        return $doc;
    }

    /**
     * @param string $topologyId
     *
     * @return UserTask[]
     */
    public function getAllUserTasks(string $topologyId): array
    {
        return $this->userTaskRepository->findBy(['topologyId' => $topologyId]);
    }

    /**
     * @param string $topologyId
     *
     * @return void
     * @throws MongoDBException
     */
    public function removeAllUserTasks(string $topologyId): void
    {
        $this
            ->userTaskRepository
            ->createQueryBuilder()
            ->remove()
            ->field('topologyId')
            ->equals($topologyId)
            ->getQuery()
            ->execute();
    }

    /**
     * @param UserTask $userTask
     * @param mixed[]  $data
     *
     * @return UserTask
     * @throws MongoDBException
     */
    public function update(UserTask $userTask, array $data): UserTask
    {
        $message = $userTask->getMessage();
        $userTask->addAuditLog($message->toPublicArray());
        $message->fromArray($data);
        $this->dm->flush();

        return $userTask;
    }

    /**
     * @param UserTask $userTask
     *
     * @throws MongoDBException
     */
    public function accept(UserTask $userTask): void
    {
        $this->assertTopologyNotDeleted($userTask);
        $this->claim($userTask);
        $this->publishOrRelease($userTask, TRUE);
        $this->delete($userTask);
    }

    /**
     * @param mixed[] $ids
     *
     * @throws MongoDBException
     */
    public function acceptMany(array $ids): void
    {
        $this->processMany($ids, TRUE);
    }

    /**
     * @param mixed[] $ids
     *
     * @throws MongoDBException
     */
    public function rejectMany(array $ids): void
    {
        $this->processMany($ids, FALSE);
    }

    /**
     * @param UserTask $userTask
     * @param bool     $deletedTopology
     *
     * @throws MongoDBException
     */
    public function reject(UserTask $userTask, bool $deletedTopology): void
    {
        $this->claim($userTask);
        if (!$deletedTopology) {
            $this->publishOrRelease($userTask, FALSE);
        }
        $this->delete($userTask);
    }

    /**
     * - With declared `properties` only those keys may be answered.
     * - Without them (schema-less Question) an answer may only add keys, never overwrite upstream values.
     * - The parked body must be a JSON object; nested empty objects stay objects.
     *
     * @param UserTask $userTask
     * @param mixed[]  $answer
     *
     * @throws MongoDBException
     * @throws UserTaskAnswerException
     * @throws UserTaskConflictException
     */
    public function answer(UserTask $userTask, array $answer): void
    {
        if ($userTask->getType() !== UserTaskEnum::QUESTION->value) {
            throw new UserTaskAnswerException(
                sprintf('UserTask with id [%s] is not a question and cannot be answered.', $userTask->getId()),
            );
        }

        if ($answer !== [] && array_is_list($answer)) {
            throw new UserTaskAnswerException('Answer must be an object of field values.');
        }

        $schema = $userTask->getSchema();
        $answer = JsonSchemaArgsValidator::coerce($schema, $answer);
        $errors = JsonSchemaArgsValidator::errors($schema, $answer);
        if ($errors !== []) {
            throw new UserTaskAnswerException(sprintf('Invalid answer: %s', implode(' ', $errors)));
        }

        $body       = $this->decodeObjectBody($userTask);
        $properties = is_array($schema['properties'] ?? NULL) ? $schema['properties'] : [];
        $unknown    = $properties === []
            ? array_values(
                array_filter(array_keys($answer), static fn($key): bool => property_exists($body, (string) $key)),
            )
            : array_values(array_diff(array_keys($answer), array_keys($properties)));
        if ($unknown !== []) {
            throw new UserTaskAnswerException(
                sprintf(
                    $properties === []
                        ? 'Answer may not overwrite existing fields: %s.'
                        : 'Answer contains fields the question does not ask for: %s.',
                    implode(', ', $unknown),
                ),
            );
        }

        $this->assertTopologyNotDeleted($userTask);
        $this->claim($userTask);

        foreach ($answer as $key => $value) {
            $body->{(string) $key} = $value;
        }
        $userTask->getMessage()->setBody(Json::encode($body));

        $this->publishOrRelease($userTask, TRUE);
        $this->delete($userTask);
    }

    /**
     * Every failure looks the same, so the error never reveals that a task exists.
     *
     * @param UserTask     $userTask
     * @param string|null  $identity
     * @param UserTaskEnum ...$types
     */
    public function assertPulseTask(UserTask $userTask, ?string $identity, UserTaskEnum ...$types): void
    {
        $accepted = array_map(static fn(UserTaskEnum $type): string => $type->value, $types);
        if ($identity === NULL
            || $identity === ''
            || $userTask->getIdentity() !== $identity
            || !in_array($userTask->getType(), $accepted, TRUE)
        ) {
            throw new UserTaskException(
                sprintf('UserTask with id [%s] has been not found', $userTask->getId()),
            );
        }
    }

    /**
     * @param string $identity
     *
     * @return UserTask[]
     */
    public function findPendingConfirmsByIdentity(string $identity): array
    {
        return $this->userTaskRepository->findBy([
            'identity' => $identity,
            'type'     => ['$in' => [UserTaskEnum::CONFIRM->value, UserTaskEnum::QUESTION->value]],
        ]);
    }

    /**
     * @param GridRequestDtoInterface $dto
     * @param bool                    $enrich
     *
     * @return mixed[]
     * @throws MongoDBException
     */
    public function filter(GridRequestDtoInterface $dto, bool $enrich = TRUE): array
    {
        $res = array_map(
            static function (array $doc): array {
                // ODM stores the Pulse fields (with defaults) on every task; only Confirm / Question carry them
                $pulse = [UserTaskEnum::CONFIRM->value, UserTaskEnum::QUESTION->value];
                if (!in_array($doc[UserTask::TYPE] ?? NULL, $pulse, TRUE)) {
                    unset($doc[UserTask::IDENTITY], $doc[UserTask::QUESTION]);
                }

                return $doc;
            },
            $this->filter->getData($dto)->toArray(),
        );

        if (!$enrich) {
            return $res;
        }

        return array_map(function (array $doc) {
            $topo = $this->dm->getRepository(Topology::class)->findOneBy(['id' => $doc[UserTask::TOPOLOGY_ID]]);

            return array_merge(
                $doc,
                [
                    UserTask::TOPOLOGY_DELETED => $topo?->isDeleted() ?? FALSE,
                    UserTask::TOPOLOGY_DESCR   => $topo?->getDescr() ?? '',
                    UserTask::TOPOLOGY_VERSION => $topo?->getVersion() ?? 0,
                ],
            );
        }, $res);
    }

    /**
     * @param GridRequestDtoInterface $dto
     *
     * @return array<mixed>
     * @throws Exception
     */
    public function getUserTasks(GridRequestDtoInterface $dto): array
    {
        return $this->aggregationFilter->getData($dto)->toArray();
    }

    /**
     * @param UserTask    $userTask
     * @param string|null $newTopologyId
     * @param string|null $newNodeId
     *
     * @return bool
     */
    public function retargetUserTask(UserTask $userTask, ?string $newTopologyId, ?string $newNodeId): bool
    {
        if (!$newTopologyId || !$newNodeId) {
            return FALSE;
        }

        $newTopology = $this->topologyRepository->getTopologyById($newTopologyId);
        $newNode     = $this->nodeRepository->getNodeById($newNodeId);
        if ($userTask->getTopologyName() === $newTopology->getName() &&
            $userTask->getNodeName() === $newNode->getName()) {
            $userTask->setTopologyId($newTopologyId); // Used for deleted check
            $userTask->setReturnExchange(
                str_replace($userTask->getNodeId(), $newNodeId, $userTask->getReturnExchange()),
            );

            return FALSE;
        }

        $this->trashWithoutPublish($userTask, 'Node cannot be retargeted');

        return TRUE;
    }

    /**
     * Flip a live parked message into trash without republishing into Rabbit.
     *
     * @param UserTask $userTask
     * @param string   $reason
     */
    public function trashWithoutPublish(UserTask $userTask, string $reason): void
    {
        $live = [UserTaskEnum::USER_TASK->value, UserTaskEnum::CONFIRM->value, UserTaskEnum::QUESTION->value];
        if (!in_array($userTask->getType(), $live, TRUE)) {
            return;
        }

        $userTask->setType(UserTaskEnum::TRASH->value);
        $headers                   = $userTask->getMessage()->getHeaders();
        $headers['result-message'] = $reason;
        $userTask->getMessage()->setHeaders($headers);
        $this->dm->flush();
    }

    /**
     * @param UserTask $userTask
     * @param bool     $accept
     */
    private function publish(UserTask $userTask, bool $accept): void
    {
        $message = $userTask->getMessage();
        $this->publisher->setExchange($userTask->getReturnExchange());
        $this->publisher->setRoutingKey($userTask->getReturnRoutingKey());

        $headers = $message->getHeaders();

        $headers[self::STATE_HEADER] = $accept ? self::STATE_ACCEPT : self::STATE_REJECT;
        if ($userTask->isPulseTask()) {
            $headers[self::DECISION_HEADER] = $headers[self::STATE_HEADER];
        }
        $this->publisher->publish(
            Json::encode(
                [
                    'body'    => $message->getBody(),
                    'headers' => $headers,
                ],
            ),
            [
                'published-timestamp' => DateTimeUtils::getUtcDateTime()->getTimestamp() * 1_000,
            ],
        );
    }

    /**
     * @param UserTask $userTask
     *
     * @throws MongoDBException
     */
    private function delete(UserTask $userTask): void
    {
        $this->dm->remove($userTask);
        $this->dm->flush();
    }

    /**
     * @param mixed[] $ids
     * @param bool    $accept
     *
     * @throws MongoDBException
     */
    private function processMany(array $ids, bool $accept): void
    {
        /** @var UserTask[] $tasks */
        $tasks = $this
            ->userTaskRepository
            ->createQueryBuilder()
            ->field('id')
            ->in($ids)
            ->getQuery()
            ->toArray();

        /** @var array<string, Topology|null> $topologies */
        $topologies = [];

        foreach ($tasks as $task) {
            $topologyId = $task->getTopologyId();

            if (!array_key_exists($topologyId, $topologies)) {
                $topologies[$topologyId] = $this->topologyRepository->find($topologyId);
            }

            $topology = $topologies[$topologyId];

            try {
                $this->claim($task);
            } catch (UserTaskException) {
                // Someone else is deciding this task right now
                continue;
            }

            if ($topology !== NULL && !$topology->isDeleted()) {
                $this->publishOrRelease($task, $accept);
            }

            $this->dm->remove($task);
        }

        $this->dm->flush();
        $this->dm->clear();
    }

    /**
     * @param UserTask $userTask
     */
    private function assertTopologyNotDeleted(UserTask $userTask): void
    {
        /** @var Topology|null $topology */
        $topology = $this->topologyRepository->find($userTask->getTopologyId());
        if ($topology === NULL || $topology->isDeleted()) {
            throw new LogicException('Cannot accept userTask of deleted topology');
        }
    }

    /**
     * Atomic, so two concurrent decisions can't both resume the run.
     *
     * @param UserTask $userTask
     */
    private function claim(UserTask $userTask): void
    {
        $now     = DateTimeUtils::getUtcDateTime()->getTimestamp();
        $claimed = $this->dm->getDocumentCollection(UserTask::class)->findOneAndUpdate(
            [
                '$or' => [
                    [self::CLAIMED_AT => NULL],
                    [self::CLAIMED_AT => ['$lt' => new UTCDateTime(($now - self::CLAIM_TTL_SECONDS) * 1_000)]],
                ],
                '_id' => $this->databaseId($userTask),
            ],
            ['$set' => [self::CLAIMED_AT => new UTCDateTime($now * 1_000)]],
        );

        if ($claimed === NULL) {
            throw new UserTaskException(
                sprintf('UserTask with id [%s] has been not found', $userTask->getId()),
            );
        }
    }

    /**
     * @param UserTask $userTask
     * @param bool     $accept
     *
     * @throws Throwable
     */
    private function publishOrRelease(UserTask $userTask, bool $accept): void
    {
        try {
            $this->publish($userTask, $accept);
        } catch (Throwable $t) {
            $this->dm->getDocumentCollection(UserTask::class)->updateOne(
                ['_id' => $this->databaseId($userTask)],
                ['$unset' => [self::CLAIMED_AT => '']],
            );

            throw $t;
        }
    }

    /**
     * @param UserTask $userTask
     *
     * @return mixed
     */
    private function databaseId(UserTask $userTask): mixed
    {
        return $this->dm->getClassMetadata(UserTask::class)->getDatabaseIdentifierValue($userTask->getId());
    }

    /**
     * @param UserTask $userTask
     *
     * @return stdClass
     * @throws UserTaskConflictException
     */
    private function decodeObjectBody(UserTask $userTask): stdClass
    {
        $raw = trim($userTask->getMessage()->getBody());
        if ($raw === '' || $raw === '[]') {
            return new stdClass();
        }

        try {
            // Objects, not Json::decode() arrays: a nested `{}` must be written back as `{}`, not `[]`
            $body = json_decode($raw, FALSE, 512, JSON_THROW_ON_ERROR); // @phpstan-ignore-line
        } catch (JsonException) {
            $body = NULL;
        }

        if (!$body instanceof stdClass) {
            throw new UserTaskConflictException(
                sprintf(
                    'UserTask with id [%s] does not carry a JSON object body, so it cannot take an answer.',
                    $userTask->getId(),
                ),
            );
        }

        return $body;
    }

}
