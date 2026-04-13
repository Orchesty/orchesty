<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFUserTaskBundle\Handler;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\MappingException;
use Doctrine\ODM\MongoDB\MongoDBException;
use Exception;
use Hanaboso\MongoDataGrid\Exception\GridException;
use Hanaboso\MongoDataGrid\GridHandlerTrait;
use Hanaboso\MongoDataGrid\GridRequestDto;
use Hanaboso\MongoDataGrid\GridRequestDtoInterface;
use Hanaboso\PipesFramework\Database\Document\Topology;
use Hanaboso\PipesFramework\UserTask\Document\UserTask;
use Hanaboso\PipesFramework\UserTask\Document\UserTaskMessage;
use Hanaboso\PipesFramework\UserTask\Model\UserTaskManager;
use Hanaboso\Utils\Validations\Validations;

/**
 * Class UserTaskHandler
 *
 * @package Hanaboso\PipesFramework\HbPFUserTaskBundle\Handler
 */
final readonly class UserTaskHandler
{

    use GridHandlerTrait;

    public const string IDS            = 'ids';
    public const string RESULT_MESSAGE = 'resultMessage';
    public const string SEARCH         = 'search';
    public const string DATE_FROM      = 'dateFrom';
    public const string DATE_TO        = 'dateTo';

    /**
     * UserTaskHandler constructor.
     *
     * @param UserTaskManager $manager
     * @param DocumentManager $dm
     */
    public function __construct(private UserTaskManager $manager, private DocumentManager $dm)
    {
    }

    /**
     * @param string $id
     *
     * @return mixed[]
     * @throws MappingException
     * @throws MongoDBException
     */
    public function get(string $id): array
    {
        $doc = $this->manager->get($id);

        $topo = $this->dm->getRepository(Topology::class)->findOneBy(['id' => $doc->getTopologyId()]);

        return array_merge(
            $doc->toArray(),
            [
                UserTask::TOPOLOGY_DELETED => $topo?->isDeleted() ?? FALSE,
                UserTask::TOPOLOGY_DESCR   => $topo?->getDescr() ?? '',
                UserTask::TOPOLOGY_VERSION => $topo?->getVersion() ?? 0,
            ],
        );
    }

    /**
     * @param string $topologyId
     *
     * @return UserTask[]
     */
    public function getAllUserTasks(string $topologyId): array
    {
        return $this->manager->getAllUserTasks($topologyId);
    }

    /**
     * @param string $topologyId
     *
     * @return void
     * @throws MongoDBException
     */
    public function removeAllUserTasks(string $topologyId): void
    {
        $this->manager->removeAllUserTasks($topologyId);
    }

    /**
     * @param string  $id
     * @param mixed[] $data
     *
     * @return mixed[]
     * @throws MappingException
     * @throws MongoDBException
     */
    public function update(string $id, array $data): array
    {
        Validations::checkParams([UserTaskMessage::BODY, UserTaskMessage::HEADERS], $data);

        return $this->manager->update($this->manager->get($id), $data)->toArray();
    }

    /**
     * @param string      $id
     * @param string|null $topologyId
     * @param string|null $nodeId
     *
     * @return mixed[]
     * @throws MappingException
     * @throws MongoDBException
     */
    public function accept(string $id, ?string $topologyId, ?string $nodeId): array
    {
        $userTask = $this->manager->get($id);
        $this->manager->retargetUserTask($userTask, $topologyId, $nodeId);
        $this->manager->accept($userTask);

        return [];
    }

    /**
     * @param mixed[]     $filterData
     * @param string|null $topologyId
     * @param string|null $nodeId
     *
     * @return mixed[]
     * @throws GridException
     * @throws MappingException
     * @throws MongoDBException
     */
    public function acceptBatch(array $filterData, ?string $topologyId = NULL, ?string $nodeId = NULL): array
    {
        while (TRUE) {
            $tasks = $this->manager->filter($this->filterBody($filterData));

            if (!$tasks) {
                return [];
            }

            foreach ($tasks as $task) {
                $this->accept($task['id'], $topologyId, $nodeId);
            }

            $this->dm->flush();
        }
    }

    /**
     * @param string $id
     *
     * @return mixed[]
     * @throws MappingException
     * @throws MongoDBException
     */
    public function reject(string $id): array
    {
        $userTask = $this->manager->get($id);

        /** @var Topology|null $topology */
        $topology = $this->dm->getRepository(Topology::class)->find($userTask->getTopologyId());
        $deleted  = $topology == NULL || $topology->isDeleted();

        $this->manager->reject($userTask, $deleted);

        return [];
    }

    /**
     * @param mixed[] $filterData
     *
     * @return mixed[]
     * @throws GridException
     * @throws MongoDBException
     */
    public function rejectBatch(array $filterData): array
    {
        while (TRUE) {
            $tasks = $this->manager->filter($this->filterBody($filterData));

            if (!$tasks) {
                return [];
            }

            foreach ($tasks as $task) {
                $this->reject($task['id']);
            }

            $this->dm->flush();
        }
    }

    /**
     * @param GridRequestDtoInterface $dto
     *
     * @return mixed[]
     * @throws MongoDBException
     */
    public function filter(GridRequestDtoInterface $dto): array
    {
        $items = $this->manager->filter($dto);

        return $this->getGridResponse($dto, $items);
    }

    /**
     * @param GridRequestDtoInterface $dto
     *
     * @return mixed[]
     * @throws Exception
     */
    public function getUserTasks(GridRequestDtoInterface $dto): array
    {
        return $this->getGridResponse($dto, $this->manager->getUserTasks($dto));
    }

    /**
     * @param mixed[] $data
     *
     * @return GridRequestDto
     * @throws GridException
     */
    private function filterBody(array $data): GridRequestDto
    {
        $fields = [self::IDS, UserTask::CORRELATION_ID, UserTask::TOPOLOGY_ID, UserTask::NODE_ID, UserTask::TYPE, self::RESULT_MESSAGE];
        Validations::checkParamsAny($fields, $data);

        $dtoHeaders = [];
        if (isset($data[self::SEARCH]) && $data[self::SEARCH] !== '') {
            $dtoHeaders[GridRequestDto::SEARCH] = $data[self::SEARCH];
        }

        $dto = new GridRequestDto($dtoHeaders);
        $dto->setItemsPerPage(100);

        foreach ($fields as $field) {
            if (array_key_exists($field, $data)) {
                if ($field === self::IDS) {
                    $column   = UserTask::ID;
                    $operator = 'IN';
                    $value    = $data[$field];
                } elseif ($field === self::RESULT_MESSAGE) {
                    $column = 'resultMessage';
                    if ($data[$field] === '' || $data[$field] === NULL) {
                        $operator = 'EMPTY';
                        $value    = [''];
                    } else {
                        $operator = 'EQ';
                        $value    = [$data[$field]];
                    }
                } else {
                    $column   = $field;
                    $operator = 'EQ';
                    $value    = is_array($data[$field]) ? $data[$field] : [$data[$field]];
                }

                $dto->setAdditionalFilters(
                    [
                        [
                            [
                                'column'   => $column,
                                'operator' => $operator,
                                'value'    => $value,
                            ],
                        ],
                    ],
                );
            }
        }

        if (isset($data[self::DATE_FROM]) && $data[self::DATE_FROM] !== '') {
            $operator = isset($data[self::DATE_TO]) && $data[self::DATE_TO] !== '' ? 'BETWEEN' : 'GTE';
            $value    = $operator === 'BETWEEN'
                ? [$data[self::DATE_FROM], $data[self::DATE_TO]]
                : [$data[self::DATE_FROM]];

            $dto->setAdditionalFilters(
                [
                    [
                        [
                            'column'   => UserTask::CREATED,
                            'operator' => $operator,
                            'value'    => $value,
                        ],
                    ],
                ],
            );
        }

        return $dto;
    }

}
