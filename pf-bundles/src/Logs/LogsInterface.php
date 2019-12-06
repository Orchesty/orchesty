<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Logs;

use Hanaboso\MongoDataGrid\GridRequestDto;

/**
 * Interface LogsInterface
 *
 * @package Hanaboso\PipesFramework\Logs
 */
interface LogsInterface
{

    /**
     * @param GridRequestDto $dto
     *
     * @return mixed[]
     */
    public function getData(GridRequestDto $dto): array;

}
