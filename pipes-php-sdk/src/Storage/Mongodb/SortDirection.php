<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\Storage\Mongodb;

/**
 * Class SortDirection
 *
 * @package Hanaboso\PipesPhpSdk\Storage\Mongodb
 */
enum SortDirection: string
{

    case Asc  = 'asc';
    case Desc = 'desc';

}
