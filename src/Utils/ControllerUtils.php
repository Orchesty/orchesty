<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Utils;

use Exception;

/**
 * Class ControllerUtils
 *
 * @package Hanaboso\PipesFramework\Utils
 */
class ControllerUtils
{

    /**
     * @param Exception $exception
     *
     * @return array
     */
    public static function createExceptionData(Exception $exception): array
    {
        return [
            'status'     => 'ERROR',
            'error_code' => $exception->getCode(),
            'type'       => get_class($exception),
            'message'    => $exception->getMessage(),
        ];
    }

}