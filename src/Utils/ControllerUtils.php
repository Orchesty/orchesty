<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Utils;

use Exception;
use Hanaboso\PipesFramework\Commons\Exception\PipesFrameworkException;

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

    /**
     * @param array          $headers
     * @param Exception|null $e
     *
     * @return array
     */
    public static function createHeaders(array $headers = [], ?Exception $e = NULL): array
    {
        $code    = 0;
        $status  = 'OK';
        $message = '';
        $detail  = '';

        if ($e) {
            $code    = $e->getCode();
            $status  = 'ERROR';
            $message = $e->getMessage();
            $detail  = $e->getTraceAsString();
        }

        $array = [
            'result_code'    => $code,
            'result_status'  => $status,
            'result_message' => $message,
            'result_detail'  => $detail,
        ];

        return array_merge($array, $headers);
    }

    /**
     * @param array $parameters
     * @param array $data
     *
     * @throws PipesFrameworkException
     */
    public static function checkParameters(array $parameters, array $data): void
    {
        foreach ($parameters as $parameter) {
            if (!isset($data[$parameter])) {
                throw new PipesFrameworkException(
                    sprintf('Required parameter \'%s\' not found', $parameter),
                    PipesFrameworkException::REQUIRED_PARAMETER_NOT_FOUND
                );
            }
        }
    }

}