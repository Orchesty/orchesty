<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 20.3.2017
 * Time: 10:03
 */

namespace Hanaboso\PipesFramework\Commons\CustomRoute;

use Symfony\Component\HttpFoundation\Request;

/**
 * Interface CustomRouteableInterface
 *
 * @package Hanaboso\PipesFramework\Commons\CustomRoute
 */
interface CustomRouteableInterface
{

    /**
     * @return RouteInterface[]
     */
    public function getRoutes(): array;

    /**
     * @param RouteInterface $route
     * @param Request        $request
     *
     * @return mixed
     */
    public function routeReceive(RouteInterface $route, Request $request);

}