<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: stanislav.kundrat
 * Date: 8/8/17
 * Time: 2:44 PM
 */

namespace Hanaboso\PipesFramework\Commons\Utils;

use Exception;

/**
 * Class ExceptionContextLoader
 */
class ExceptionContextLoader
{

    /**
     * @param Exception $e
     *
     * @return array
     */
    public static function getContextForLogger(?Exception $e = NULL): array
    {
        if ($e === NULL) {
            return [];
        }

        return [
            'exception' => $e,
            'message'   => $e->getMessage(),
            'trace'     => $e->getTraceAsString(),
            'code'      => $e->getCode(),
        ];
    }

}