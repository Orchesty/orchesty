<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFUserTaskBundle\Handler;

use Doctrine\ODM\MongoDB\Mapping\MappingException;
use Doctrine\ODM\MongoDB\MongoDBException;
use Hanaboso\MongoDataGrid\Exception\GridException;
use Hanaboso\MongoDataGrid\GridHandlerTrait;
use Hanaboso\MongoDataGrid\GridRequestDto;
use Hanaboso\MongoDataGrid\GridRequestDtoInterface;
use Hanaboso\PipesFramework\UserTask\Document\UserTask;
use Hanaboso\PipesFramework\UserTask\Document\UserTaskMessage;
use Hanaboso\PipesFramework\UserTask\Model\UserTaskManager;
use Hanaboso\Utils\Validations\Validations;

/**
 * Class UserTaskHandler
 *
 * @package Hanaboso\PipesFramework\HbPFUserTaskBundle\Handler
 */
final class UserTaskHandler
{

    use GridHandlerTrait;

    private const IDS            = 'ids';
    private const NODE_ID        = 'nodeId';
    private const TOPOLOGY_ID    = 'topologyId';
    private const CORRELATION_ID = 'correlationId';
    private const TYPE           = 'type';

    /**
     * UserTaskHandler constructor.
     *
     * @param UserTaskManager $manager
     */
    public function __construct(private UserTaskManager $manager)
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
        return $this->manager->get($id)->toArray();
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
     * @param string $id
     *
     * @return mixed[]
     * @throws MappingException
     * @throws MongoDBException
     */
    public function accept(string $id): array
    {
        $this->manager->accept($this->manager->get($id));

        return [];
    }

    /**
     * @param mixed[] $filterData
     *
     * @return mixed[]
     * @throws GridException
     * @throws MongoDBException
     */
    public function acceptBatch(array $filterData): array
    {
        $tasks = $this->manager->filter($this->filterBody($filterData));
        foreach ($tasks as $task) {
            $this->accept($task['id']);
        }

        return [];
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
        $this->manager->reject($this->manager->get($id));

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
        $tasks = $this->manager->filter($this->filterBody($filterData));
        foreach ($tasks as $task) {
            $this->reject($task['id']);
        }

        return [];
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
     * @param mixed[] $data
     *
     * @return GridRequestDto
     * @throws GridException
     */
    private function filterBody(array $data): GridRequestDto
    {
        $fields = [self::IDS, self::CORRELATION_ID, self::TOPOLOGY_ID, self::NODE_ID, self::TYPE];
        Validations::checkParamsAny($fields, $data);
        $dto = new GridRequestDto([]);
        $dto->setItemsPerPage(99);

        foreach ($fields as $field) {
            if (array_key_exists($field, $data)) {
                $dto->setAdditionalFilters(
                    [
                        [
                            $field === self::IDS ? [
                                'operator' => 'IN',
                                'column'   => UserTask::ID,
                                'value'    => $data[$field],
                            ] : [
                                'operator' => 'EQ',
                                'column'   => $field,
                                'value'    => [$data[$field]],
                            ],
                        ],
                    ],
                );
            }
        }

        return $dto;
    }

}
