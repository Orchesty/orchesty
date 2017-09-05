<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Commons\Utils;

/**
 * Class UriParams
 *
 * @package Hanaboso\PipesFramework\Commons\Utils
 */
class UriParams
{

    /**
     * @param null $orderBy
     *
     * @return array
     */
    public static function parseOrderBy($orderBy = NULL): array
    {
        $sort = [];

        if (!empty($orderBy)) {
            foreach (explode(',', $orderBy) as $item) {
                $name        = substr($item, 0, -1);
                $direction   = substr($item, -1);
                $sort[$name] = $direction;
            }
        }

        return $sort;
    }

}