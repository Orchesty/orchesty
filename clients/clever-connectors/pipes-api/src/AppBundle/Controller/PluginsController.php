<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Controller;

use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Handler\PluginsHandler;
use CleverConnectors\AppBundle\Model\Systems\Exceptions\SystemException;
use Exception;
use FOS\RestBundle\Controller\FOSRestController;
use Hanaboso\PipesFramework\Commons\Exception\PipesFrameworkException;
use Hanaboso\PipesFramework\Utils\ControllerUtils;
use LogicException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class PluginsController
 *
 * @package CleverConnectors\AppBundle\Controller
 *
 * @Route(service="cc.plugins.controller")
 */
class PluginsController extends FOSRestController
{

    /**
     * @var PluginsHandler
     */
    private $handler;

    /**
     * PluginsController constructor.
     *
     * @param PluginsHandler $handler
     */
    public function __construct(PluginsHandler $handler)
    {
        $this->handler = $handler;
    }

    /**
     * @Route("/install")
     * @Method({"POST"})
     *
     * @param Request $request
     *
     * @return Response
     */
    public function installAction(Request $request): Response
    {
        try {
            return new JsonResponse($this->handler->install($request), 200);
        } catch (Exception $e) {
            return self::processException($e);
        }
    }

    /**
     * @Route("/check")
     * @Method({"POST"})
     *
     * @param Request $request
     *
     * @return Response
     */
    public function checkAction(Request $request): Response
    {
        try {
            return new JsonResponse($this->handler->check($request), 200);
        } catch (Exception $e) {
            return self::processException($e);
        }
    }

    /**
     * @Route("/subscriber")
     * @Method({"POST"})
     *
     * @param Request $request
     *
     * @return Response
     */
    public function createSubscriberAction(Request $request): Response
    {
        try {
            $this->handler->createSubscriber($request->request->all());

            return new JsonResponse('', 200);
        } catch (Exception $e) {
            return self::processException($e);
        }
    }

    /**
     * @Route("/subscriber")
     * @Method({"PUT"})
     *
     * @param Request $request
     *
     * @return Response
     */
    public function updateSubscriberAction(Request $request): Response
    {
        try {
            $this->handler->updateSubscriber($request->request->all());

            return new JsonResponse('', 200);
        } catch (Exception $e) {
            return self::processException($e);
        }
    }

    /**
     * @Route("/subscriber")
     * @Method({"DELETE"})
     *
     * @param Request $request
     *
     * @return Response
     */
    public function deleteSubscriberAction(Request $request): Response
    {
        try {
            $this->handler->deleteSubscriber($request->request->all());

            return new JsonResponse('', 200);
        } catch (Exception $e) {
            return self::processException($e);
        }
    }

    /**
     * @param Exception $e
     *
     * @return Response
     */
    private static function processException(Exception $e): Response
    {
        $code = 500;
        $className = get_class($e);

        if ($className === SystemException::class) {
            $sysNotFound = [
                SystemException::SYSTEM_NOT_FOUND,
                SystemException::SYSTEM_OR_USER_NOT_FOUND,
                SystemException::SYSTEM_PROPERTY_NOT_FOUND,
                SystemException::MISMATCH_URL,
            ];
            if (in_array($e->getCode(), $sysNotFound)) {
                $code = 404;
            }
        } else if ($className === LogicException::class) {
            $code = 404;
        } else if ($className === CleverConnectorsException::class || $className === PipesFrameworkException::class) {
            $code = 400;
        }

        return new Response(ControllerUtils::createExceptionData($e), $code);
    }

}
