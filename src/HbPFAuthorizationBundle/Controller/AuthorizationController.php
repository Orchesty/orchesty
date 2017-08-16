<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFAuthorizationBundle\Controller;

use FOS\RestBundle\Controller\FOSRestController;
use Hanaboso\PipesFramework\HbPFAuthorizationBundle\Handler\AuthorizationHandler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class AuthorizationController
 *
 * @package Hanaboso\PipesFramework\HbPFCommonsBundle\Controller
 */
class AuthorizationController extends FOSRestController
{

    /**
     * @var AuthorizationHandler
     */
    private $handler;

    /**
     * @Route("/api/authorizations/{authorizationId}/user_actions", defaults={}, requirements={"authorizationId": "\w+"})
     *
     * @param string $authorizationId
     *
     * @return Response
     */
    public function userAuthorizationAction(string $authorizationId): Response
    {
        $result = $this->handler->getUserAuthorization($authorizationId);
        if (empty($result)) {
            throw $this->createNotFoundException();
        }

        return $this->handleView($this->view($result));
    }

    /**
     * @Route("/api/authorizations/{authorizationId}/custom_routes", defaults={}, requirements={"authorizationId": "\w+"})
     *
     * @param string $authorizationId
     *
     * @return Response
     */
    public function getCustomRoutesForAuthorization(string $authorizationId): Response
    {
        $result = $this->handler->getUserAuthorizationRoutes($authorizationId);
        if (empty($result)) {
            throw $this->createNotFoundException();
        }

        return $this->handleView($this->view($result));
    }

    /**
     * @Route("/api/authorizations/{authorizationId}/custom_routes/{partUrl}", defaults={}, requirements={"authorizationId":"\w+"}, requirements={"partUrl":".+"})
     *
     * @param Request $request
     * @param string  $authorizationId
     * @param string  $partUrl
     *
     * @return Response
     */
    public function authorizationCustomRouteAction(Request $request, string $authorizationId, string $partUrl): Response
    {
        $result = $this->handler->getUserAuthorizationCustomRoutes($authorizationId, $partUrl, $request);
        if (empty($result)) {
            throw $this->createNotFoundException();
        }

        return $this->handleView($this->view($result[0]));
    }

}
