<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\User\Filter;

use Doctrine\ODM\MongoDB\Query\Builder;
use Hanaboso\MongoDataGrid\GridFilterAbstract;
use Hanaboso\UserBundle\Document\TmpUser;

/**
 * Class TmpUserDocumentFilter
 *
 * @package Hanaboso\PipesFramework\User\Filter
 */
final class TmpUserDocumentFilter extends GridFilterAbstract
{

    /**
     * @var string[]
     */
    protected array $filterCols = [
        'created' => 'created',
        'email'   => 'email',
    ];

    /**
     * @var string[]
     */
    protected array $orderCols = [
        'created' => 'created',
        'email'   => 'email',
        'id'      => 'id',
    ];

    /**
     * @var string[]
     */
    protected array $searchableCols = [
        'created',
        'email',
    ];

    /**
     * @return Builder
     */
    protected function prepareSearchQuery(): Builder
    {
        return $this->getRepository()
            ->createQueryBuilder()
            ->select(
                [
                    'created',
                    'email',
                ],
            );
    }

    /**
     * @return void
     */
    protected function setDocument(): void
    {
        $this->document = TmpUser::class;
    }

    /**
     * @return string[]
     */
    protected function filterCols(): array
    {
        return $this->filterCols;
    }

    /**
     * @return string[]
     */
    protected function orderCols(): array
    {
        return $this->orderCols;
    }

    /**
     * @return string[]
     */
    protected function searchableCols(): array
    {
        return $this->searchableCols;
    }

    /**
     * @return bool
     */
    protected function useTextSearch(): bool
    {
        return FALSE;
    }

}
