<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Utils;

use Hanaboso\PipesFramework\Commons\Exception\PipesFrameworkException;
use Hanaboso\PipesFramework\Commons\Utils\PipesHeaders;
use Throwable;

/**
 * Class ControllerUtils
 *
 * @package Hanaboso\PipesFramework\Utils
 */
class ControllerUtils
{

    /**
     * @param Throwable $exception
     * @param bool      $asJson
     *
     * @return string|array
     */
    public static function createExceptionData(Throwable $exception, bool $asJson = FALSE)
    {
        $output = [
            'status'     => 'ERROR',
            'error_code' => 2001,
            'type'       => get_class($exception),
            'message'    => $exception->getMessage(),
        ];

        if ($asJson) {
            return json_encode($output);
        }

        return $output;
    }

    /**
     * @param array          $headers
     * @param Throwable|null $e
     *
     * @return array
     */
    public static function createHeaders(array $headers = [], ?Throwable $e = NULL): array
    {
        $code    = 0;
        $status  = 'OK';
        $message = '';
        $detail  = '';

        if ($e) {
            $code    = 2001;
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

        return array_merge($array, PipesHeaders::clear($headers));
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