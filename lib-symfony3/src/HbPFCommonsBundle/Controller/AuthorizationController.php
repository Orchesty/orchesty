<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFCommonsBundle\Controller;

use FOS\RestBundle\Controller\FOSRestController;
use Hanaboso\PipesFramework\Commons\Authorization\UserAction\UserActionAuthorizationInterface;
use Hanaboso\PipesFramework\Commons\CustomRoute\CustomRouteableInterface;
use Hanaboso\PipesFramework\Commons\CustomRoute\CustomRouteManager;
use Hanaboso\PipesFramework\HbPFConnectorBundle\Loaders\AuthorizationLoader;
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
     * @Route("/api/authorizations/{authorizationId}/user_actions", defaults={}, requirements={"authorizationId": "\w+"})
     *
     * @param string $authorizationId
     *
     * @return Response
     */
    public function userAuthorizationAction(string $authorizationId): Response
    {
        /** @var AuthorizationLoader $loader */
        $loader = $this->container->get('hbpf.loader.authorization');

        /** @var UserActionAuthorizationInterface $authorization */
        $authorization = $loader->getAuthorization($authorizationId);

        $result = [];
        if ($authorization instanceof UserActionAuthorizationInterface) {
            $result = $authorization->getUserActions();
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
        /** @var AuthorizationLoader $loader */
        $loader = $this->container->get('hbpf.loader.authorization');

        /** @var CustomRouteableInterface $authorization */
        $authorization = $loader->getAuthorization($authorizationId);

        $result = [];
        if ($authorization instanceof CustomRouteableInterface) {
            $result = $authorization->getRoutes();
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
        /** @var AuthorizationLoader $loader */
        $loader = $this->container->get('hbpf.loader.authorization');

        /** @var UserActionAuthorizationInterface $authorization */
        $authorization = $loader->getAuthorization($authorizationId);

        if ($authorization instanceof CustomRouteableInterface) {
            /** @var CustomRouteManager $customRouteManager */
            $customRouteManager = $this->container->get('hbpf.custom_route_manager');

            return $this->handleView(
                $this->view($customRouteManager->processRoute($authorization, $request, $partUrl))
            );
        } else {
            throw $this->createNotFoundException();
        }

    }

}
