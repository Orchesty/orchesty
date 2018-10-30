<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: venca
 * Date: 3/20/18
 * Time: 8:59 AM
 */

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
     * @return array
     */
    public function getData(GridRequestDto $dto): array;

}