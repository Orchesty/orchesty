<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: david.horacek
 * Date: 8/16/17
 * Time: 10:33 AM
 */

namespace Hanaboso\PipesFramework\HbPFAuthorizationBundle\Handler;

use Hanaboso\PipesFramework\Commons\Authorization\UserAction\UserActionAuthObject;
use Hanaboso\PipesFramework\Commons\Authorization\UserAction\UserActionAuthorizationInterface;
use Hanaboso\PipesFramework\Commons\CustomRoute\CustomRouteableInterface;
use Hanaboso\PipesFramework\Commons\CustomRoute\CustomRouteManager;
use Hanaboso\PipesFramework\Commons\CustomRoute\RouteInterface;
use Hanaboso\PipesFramework\HbPFConnectorBundle\Loaders\AuthorizationLoader;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class AuthorizationHandler
 *
 * @package Hanaboso\PipesFramework\HbPFAuthorizationBundle\Handler
 */
class AuthorizationHandler
{

    /**
     * @var AuthorizationLoader
     */
    private $loader;

    /**
     * @var  CustomRouteManager
     */
    private $customRouteManager;

    /**
     * AuthorizationHandler constructor.
     *
     * @param AuthorizationLoader $authorizationRepository
     * @param CustomRouteManager  $customRouteManager
     */
    function __construct(
        AuthorizationLoader $authorizationRepository,
        CustomRouteManager $customRouteManager
    )
    {
        $this->loader             = $authorizationRepository;
        $this->customRouteManager = $customRouteManager;
    }

    /**
     * @param string $authId
     *
     * @return UserActionAuthObject[]|null
     */
    public function getUserAuthorization(string $authId): ?array
    {
        $authorization = $this->getAuthorization($authId);

        if ($authorization instanceof UserActionAuthorizationInterface) {
            return $authorization->getUserActions();
        }

        return NULL;
    }

    /**
     * @param string $authId
     *
     * @return RouteInterface[]
     */
    public function getUserAuthorizationRoutes(string $authId): ?array
    {
        $authorization = $this->getAuthorization($authId);

        if ($authorization instanceof CustomRouteableInterface) {
            return $authorization->getRoutes();
        }

        return NULL;
    }

    /**
     * @param string  $authId
     * @param string  $partUrl
     * @param Request $request
     *
     * @return mixed|null
     */
    public function getUserAuthorizationCustomRoutes(string $authId, string $partUrl, Request $request)
    {
        $authorization = $this->getAuthorization($authId);

        if ($authorization instanceof CustomRouteableInterface) {
            return $this->customRouteManager->processRoute($authorization, $request, $partUrl);
        }

        return NULL;
    }

    /**
     * @param string $id
     *
     * @return UserActionAuthorizationInterface
     */
    private function getAuthorization(string $id): UserActionAuthorizationInterface
    {
        /** @var UserActionAuthorizationInterface $authorization */
        $authorization = $this->loader->getAuthorization($id);

        return $authorization;
    }

}