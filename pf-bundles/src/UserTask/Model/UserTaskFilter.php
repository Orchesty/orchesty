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
        UserTask::CORRELATION_ID => 1,
        UserTask::CREATED        => 1,
        UserTask::ID             => 1,
        UserTask::NODE_ID        => 1,
        UserTask::NODE_NAME      => 1,
        UserTask::TOPOLOGY_ID    => 1,
        UserTask::TOPOLOGY_NAME  => 1,
        UserTask::TYPE           => 1,
        UserTask::UPDATED        => 1,
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
            UserTask::CORRELATION_ID => UserTask::CORRELATION_ID,
            UserTask::CREATED        => UserTask::CREATED,
            UserTask::ID             => UserTask::ID,
            UserTask::MESSAGE        => 'message.body',
            UserTask::NODE_ID        => UserTask::NODE_ID,
            UserTask::NODE_NAME      => UserTask::NODE_NAME,
            UserTask::TOPOLOGY_ID    => UserTask::TOPOLOGY_ID,
            UserTask::TOPOLOGY_NAME  => UserTask::TOPOLOGY_NAME,
            UserTask::TYPE           => UserTask::TYPE,
            UserTask::UPDATED        => UserTask::UPDATED,
            UserTask::USER           => UserTask::USER,
        ];
    }

    /**
     * @return mixed[]
     */
    protected function orderCols(): array
    {
        return [
            UserTask::CREATED   => UserTask::CREATED,
            UserTask::ID        => UserTask::ID,
            UserTask::NODE_NAME => UserTask::NODE_NAME,
            UserTask::UPDATED   => UserTask::UPDATED,
        ];
    }

    /**
     * @return mixed[]
     */
    protected function searchableCols(): array
    {
        return [
            UserTask::MESSAGE,
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
     * @return bool
     */
    protected function useTextSearch(): bool
    {
        return TRUE;
    }

}
