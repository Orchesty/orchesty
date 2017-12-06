<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: stanislav.kundrat
 * Date: 10/16/17
 * Time: 11:16 AM
 */

namespace CleverConnectors\AppBundle\Controller;

use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use Exception;
use FOS\RestBundle\Controller\Annotations\Route;
use Hanaboso\PipesFramework\Commons\Traits\ControllerTrait;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;

/**
 * Class ExceptionController
 *
 * @package CleverConnectors\AppBundle\Controller
 * @Route(service="cc.exception.controller")
 */
class ExceptionController
{

    use ControllerTrait;

    /**
     * @param Exception $exception
     *
     * @return Response
     */
    public function showAction(Exception $exception): Response
    {
        $code      = 500;
        $className = get_class($exception);

        if (in_array($className, [BadRequestHttpException::class])) {
            $code = 400;
        } elseif (in_array($className, [NotFoundHttpException::class])) {
            $code = 404;
        } elseif (in_array($className, [MethodNotAllowedException::class])) {
            $code = 405;
        } elseif (
            in_array($className, [CleverConnectorsException::class])
            && $exception->getCode() == CleverConnectorsException::USER_TOKEN_NOT_EXISTS
        ) {
            $code = 403;
        } elseif (
            in_array($className, [CleverConnectorsException::class])
            && $exception->getCode() == CleverConnectorsException::WEBHOOK_NOT_FOUND
        ) {
            $code = 404;
        }

        return $this->getErrorResponse($exception, $code);
    }

}