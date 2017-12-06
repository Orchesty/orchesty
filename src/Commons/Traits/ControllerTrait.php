<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Commons\Traits;

use Hanaboso\PipesFramework\Utils\ControllerUtils;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Trait ControllerTrait
 *
 * @package Hanaboso\PipesFramework\Commons\Traits
 */
trait ControllerTrait
{

    /**
     * @param mixed $data
     * @param int   $code
     * @param array $headers
     *
     * @return Response
     */
    protected function getResponse($data, int $code = 200, array $headers = []): Response
    {
        if (!is_string($data)) {
            $data = json_encode($data);
        } else if (!json_decode($data)) {
            $data = json_encode($data);
        }

        return new Response($data, $code, $headers);
    }

    /**
     * @param Throwable $e
     * @param int       $code
     * @param array     $headers
     *
     * @return Response
     */
    protected function getErrorResponse(Throwable $e, int $code = 500, array $headers = []): Response
    {
        $msg = ControllerUtils::createExceptionData($e);

        return new Response($msg, $code, $headers);
    }

}