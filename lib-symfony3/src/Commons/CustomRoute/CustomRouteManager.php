<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 20.3.2017
 * Time: 10:40
 */

namespace Hanaboso\PipesFramework\Commons\CustomRoute;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class CustomRouteManager
 *
 * @package Hanaboso\PipesFramework\Commons\CustomRoute
 */
class CustomRouteManager
{

    /**
     * @param CustomRouteableInterface $customRoute
     * @param Request                  $request
     * @param string                   $partUrl
     *
     * @return mixed
     */
    public function processRoute(CustomRouteableInterface $customRoute, Request $request, string $partUrl)
    {
        $routes = $customRoute->getRoutes();
        foreach ($routes as $route) {
            if ($route->isSuitable($request, $partUrl)) {
                return $customRoute->routeReceive($route, $request);
            }
        }
        throw new NotFoundHttpException('Invalid route');
    }

}