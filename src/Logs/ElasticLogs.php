<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Logs;

use Hanaboso\MongoDataGrid\GridRequestDto;

/**
 * Class ElasticLogs
 *
 * @package Hanaboso\PipesFramework\Logs
 */
class ElasticLogs implements LogsInterface
{

    /**
     * @param GridRequestDto $dto
     *
     * @return mixed[]
     */
    public function getData(GridRequestDto $dto): array
    {
        return [
            'limit'  => $dto->getLimit(),
            'offset' => ((int) ($dto->getPage() ?? 1) - 1) * $dto->getLimit(),
            'count'  => '0',
            'total'  => '0',
            'items'  => [],
        ];
    }

}
