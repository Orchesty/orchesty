<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\UserTask\Model;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\MappingException;
use Doctrine\ODM\MongoDB\MongoDBException;
use Hanaboso\MongoDataGrid\GridRequestDtoInterface;
use Hanaboso\PipesFramework\Database\Document\Node;
use Hanaboso\PipesFramework\Database\Document\Topology;
use Hanaboso\PipesFramework\Database\Repository\NodeRepository;
use Hanaboso\PipesFramework\Database\Repository\TopologyRepository;
use Hanaboso\PipesFramework\UserTask\Document\UserTask;
use Hanaboso\PipesFramework\UserTask\Exception\UserTaskException;
use Hanaboso\PipesFramework\UserTask\Repository\UserTaskRepository;
use Hanaboso\Utils\Date\DateTimeUtils;
use Hanaboso\Utils\String\Json;
use LogicException;
use RabbitMqBundle\Publisher\Publisher;

/**
 * Class UserTaskManager
 *
 * @package Hanaboso\PipesFramework\UserTask\Model
 */
final class UserTaskManager
{

    private const STATE_HEADER = 'user-task-state';
    private const STATE_ACCEPT = 'accept';
    private const STATE_REJECT = 'reject';

    /**
     * @var UserTaskRepository
     */
    private UserTaskRepository $userTaskRepository;

    /**
     * @var TopologyRepository
     */
    private TopologyRepository $topologyRepository;

    /**
     * @var NodeRepository
     */
    private NodeRepository $nodeRepository;

    /**
     * UserTaskManager constructor.
     *
     * @param DocumentManager $dm
     * @param UserTaskFilter  $filter
     * @param Publisher       $publisher
     */
    public function __construct(
        private DocumentManager $dm,
        private UserTaskFilter $filter,
        private Publisher $publisher,
    )
    {
        $userTaskRepository       = $dm->getRepository(UserTask::class);
        $this->userTaskRepository = $userTaskRepository;

        $topologyRepository       = $dm->getRepository(Topology::class);
        $this->topologyRepository = $topologyRepository;

        $nodeRepository       = $dm->getRepository(Node::class);
        $this->nodeRepository = $nodeRepository;
    }

    /**
     * @param string $id
     *
     * @return UserTask
     * @throws MongoDBException
     * @throws MappingException
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
        $this->deleteMany($this->getAllUserTasks($topologyId));
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
        $userTask->addAuditLog($message->toArray());
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
        /** @var Topology|null $topology */
        $topology = $this->dm->getRepository(Topology::class)->find($userTask->getTopologyId());
        $deleted  = $topology == NULL || $topology->isDeleted();
        if ($deleted) {
            throw new LogicException('Cannot accept userTask of deleted topology');
        }

        $this->publish($userTask, TRUE);
        $this->delete($userTask);
    }

    /**
     * @param UserTask $userTask
     * @param bool     $deletedTopology
     *
     * @throws MongoDBException
     */
    public function reject(UserTask $userTask, bool $deletedTopology): void
    {
        if (!$deletedTopology) {
            $this->publish($userTask, FALSE);
        }
        $this->delete($userTask);
    }

    /**
     * @param GridRequestDtoInterface $dto
     *
     * @return mixed[]
     * @throws MongoDBException
     */
    public function filter(GridRequestDtoInterface $dto): array
    {
        $res = $this->filter->getData($dto)->toArray();

        return array_map(function (array $doc) {
            $topo = $this->dm->getRepository(Topology::class)->findOneBy(['id' => $doc[UserTask::TOPOLOGY_ID]]);

            return [
                ...$doc,
                UserTask::TOPOLOGY_DESCR   => $topo?->getDescr() ?? '',
                UserTask::TOPOLOGY_VERSION => $topo?->getVersion() ?? 0,
                UserTask::TOPOLOGY_DELETED => $topo?->isDeleted() ?? FALSE,
            ];
        }, $res);
    }

    /**
     * @param UserTask    $userTask
     * @param string|null $newTopologyId
     * @param string|null $newNodeId
     *
     * @return void
     */
    public function retargetUserTask(UserTask $userTask, ?string $newTopologyId, ?string $newNodeId): void
    {
        if ($newTopologyId && $newNodeId) {
            $newTopology = $this->topologyRepository->getTopologyById($newTopologyId);
            $newNode     = $this->nodeRepository->getNodeById($newNodeId);
            if ($userTask->getTopologyName() === $newTopology->getName() &&
                $userTask->getNodeName() === $newNode->getName()) {
                $userTask->setTopologyId($newTopologyId); // Used for deleted check
                $userTask->setReturnExchange(
                    str_replace($userTask->getNodeId(), $newNodeId, $userTask->getReturnExchange()),
                );
            }
        }
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
     * @param UserTask[] $userTasks
     *
     * @return void
     * @throws MongoDBException
     */
    private function deleteMany(array $userTasks): void
    {
        foreach ($userTasks as $userTask) {
            $this->delete($userTask);
        }
    }

}
