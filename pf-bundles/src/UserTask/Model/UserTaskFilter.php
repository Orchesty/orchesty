<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\UserTask\Model;

use Doctrine\ODM\MongoDB\Query\Builder;
use Hanaboso\MongoDataGrid\GridFilterAbstract;
use Hanaboso\PipesFramework\UserTask\Document\UserTask;

/**
 * Class UserTaskFilter
 *
 * @package Hanaboso\PipesFramework\UserTask\Model
 */
final class UserTaskFilter extends GridFilterAbstract
{

    /**
     * @var bool
     */
    protected bool $allowNative = TRUE;

    /**
     * @var mixed[]
     */
    protected array $projection = [
        UserTask::ID             => 1,
        UserTask::CREATED        => 1,
        UserTask::NODE_ID        => 1,
        UserTask::NODE_NAME      => 1,
        UserTask::TYPE           => 1,
        UserTask::TOPOLOGY_ID    => 1,
        UserTask::TOPOLOGY_NAME  => 1,
        UserTask::UPDATED        => 1,
        UserTask::CORRELATION_ID => 1,
    ];

    /**
     *
     */
    protected function setDocument(): void
    {
        $this->document = UserTask::class;
    }

    /**
     * @return mixed[]
     */
    protected function filterCols(): array
    {
        return [
            UserTask::ID             => UserTask::ID,
            UserTask::NODE_ID        => UserTask::NODE_ID,
            UserTask::NODE_NAME      => UserTask::NODE_NAME,
            UserTask::TOPOLOGY_ID    => UserTask::TOPOLOGY_ID,
            UserTask::TOPOLOGY_NAME  => UserTask::TOPOLOGY_NAME,
            UserTask::CORRELATION_ID => UserTask::CORRELATION_ID,
            UserTask::TYPE           => UserTask::TYPE,
            UserTask::CREATED        => UserTask::CREATED,
            UserTask::UPDATED        => UserTask::UPDATED,
        ];
    }

    /**
     * @return mixed[]
     */
    protected function orderCols(): array
    {
        return [
            UserTask::ID        => UserTask::ID,
            UserTask::CREATED   => UserTask::CREATED,
            UserTask::UPDATED   => UserTask::UPDATED,
            UserTask::NODE_NAME => UserTask::NODE_NAME,
        ];
    }

    /**
     * @return Builder
     */
    protected function prepareSearchQuery(): Builder
    {
        return $this
            ->getRepository()
            ->createQueryBuilder()
            ->select(array_keys($this->projection));
    }

    /**
     * @return mixed[]
     */
    protected function searchableCols(): array
    {
        return [];
    }

    /**
     * @return bool
     */
    protected function useTextSearch(): bool
    {
        return FALSE;
    }

}
