<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFAuthorizationBundle\Controller;

use FOS\RestBundle\Controller\Annotations\Route;
use FOS\RestBundle\Controller\FOSRestController;
use Hanaboso\PipesFramework\Authorization\Exception\AuthorizationException;
use Hanaboso\PipesFramework\HbPFAuthorizationBundle\Handler\AuthorizationHandler;
use Hanaboso\PipesFramework\Utils\ControllerUtils;
use InvalidArgumentException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class AuthorizationController
 *
 * @package Hanaboso\PipesFramework\HbPFCommonsBundle\Controller
 *
 * @Route(service="hbpf.authorization.controller.authorization")
 */
class AuthorizationController extends FOSRestController
{

    /**
     * @var AuthorizationHandler
     */
    private $handler;

    /**
     * @Route("/authorizations/{authorizationId}/authorize", defaults={}, requirements={"authorizationId": "\w+"})
     * @Method({"GET", "POST", "OPTIONS"})
     *
     * @param Request $request
     * @param string  $authorizationId
     *
     * @return Response
     */
    public function authorizationAction(Request $request, string $authorizationId): Response
    {
        $this->construct();
        try {
            $this->handler->authorize($authorizationId);
            $response = new RedirectResponse($request->request->get('redirect_url'));
        } catch (AuthorizationException | InvalidArgumentException $e) {
            $response = new JsonResponse(ControllerUtils::createExceptionData($e), 500);
        }

        return $response;
    }

    /**
     * @Route("/authorizations/{authorizationId}/settings", defaults={}, requirements={"authorizationId": "\w+"})
     * @Method({"GET", "OPTIONS"})
     *
     * @param string $authorizationId
     *
     * @return Response
     */
    public function getSettingsAction(string $authorizationId): Response
    {
        try {
            $response = new JsonResponse($this->handler->getSettings($authorizationId));
        } catch (AuthorizationException $e) {
            $response = new JsonResponse(ControllerUtils::createExceptionData($e), 500);
        }

        return $response;
    }

    /**
     * @Route("/authorizations/{authorizationId}/save_settings", defaults={}, requirements={"authorizationId": "\w+"})
     * @Method({"POST", "OPTIONS"})
     *
     * @param Request $request
     * @param string  $authorizationId
     *
     * @return Response
     */
    public function saveSettingsAction(Request $request, string $authorizationId): Response
    {
        try {
            $this->handler->saveSettings($request->request->all(), $authorizationId);
            $response = new JsonResponse([]);
        } catch (AuthorizationException $e) {
            $response = new JsonResponse(ControllerUtils::createExceptionData($e), 500);
        }

        return $response;
    }

    /**
     * @Route("/authorizations/{authorizationId}/save_token", defaults={}, requirements={"authorizationId": "\w+"})
     * @Method({"POST", "OPTIONS"})
     *
     * @param Request $request
     * @param string  $authorizationId
     *
     * @return Response
     */
    public function saveTokenAction(Request $request, string $authorizationId): Response
    {
        $this->construct();
        try {
            $this->handler->saveToken($request->request->all(), $authorizationId);
            $response = new RedirectResponse($this->container->getParameter('frontend_host'). '/close-me.html');
        } catch (AuthorizationException $e) {
            $response = new JsonResponse(ControllerUtils::createExceptionData($e), 500);
        }

        return $response;
    }

    /**
     * @Route("/authorization/info")
     * @Method({"GET", "OPTIONS"})
     *
     * @param Request $request
     *
     * @return Response
     */
    public function getAuthorizationsInfoAction(Request $request): Response
    {
        $this->construct();
        return new JsonResponse($this->handler->getAuthInfo($request->getSchemeAndHttpHost()), 200);
    }

    /**
     *
     */
    private function construct(): void
    {
        if (!$this->handler) {
            $this->handler = $this->container->get('hbpf.handler.authorization');
        }
    }

}
