<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Configurator\Model\Filters;

use Doctrine\ODM\MongoDB\Query\Builder;
use Hanaboso\MongoDataGrid\GridFilterAbstract;
use Hanaboso\PipesFramework\Configurator\Document\ApiToken;
use Hanaboso\Utils\Date\DateTimeUtils;

/**
 * Class ApiTokenFilter
 *
 * @package Hanaboso\PipesFramework\Configurator\Model\Filters
 */
final class ApiTokenFilter extends GridFilterAbstract
{

    protected const DATE_FORMAT = DateTimeUtils::DATE_TIME_UTC;

    /**
     * @return string[]
     */
    protected function filterCols(): array
    {
        return [
            ApiToken::CREATED => ApiToken::CREATED,
            ApiToken::KEY => ApiToken::KEY,
            ApiToken::USER => ApiToken::USER,
        ];
    }

    /**
     * @return string[]
     */
    protected function orderCols(): array
    {
        return [
            ApiToken::CREATED => ApiToken::CREATED,
            ApiToken::KEY => ApiToken::KEY,
            ApiToken::USER => ApiToken::USER,
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
            ->sort(ApiToken::CREATED, 'DESC');
    }

    /**
     * @return void
     */
    protected function setDocument(): void
    {
        $this->document = ApiToken::class;
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
