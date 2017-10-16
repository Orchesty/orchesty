<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Utils;

use Exception;
use Hanaboso\PipesFramework\Commons\Exception\PipesFrameworkException;
use Hanaboso\PipesFramework\Commons\Utils\PipesHeaders;

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
            $detail  = json_encode($e->getTraceAsString());
        }

        $array = [
            PipesHeaders::createKey(PipesHeaders::RESULT_CODE)    => $code,
            PipesHeaders::createKey(PipesHeaders::RESULT_STATUS)  => $status,
            PipesHeaders::createKey(PipesHeaders::RESULT_MESSAGE) => $message,
            PipesHeaders::createKey(PipesHeaders::RESULT_DETAIL)  => $detail,
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