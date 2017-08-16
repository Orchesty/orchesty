<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 20.3.2017
 * Time: 10:08
 */

namespace Hanaboso\PipesFramework\Commons\CustomRoute;

use Symfony\Component\HttpFoundation\Request;

/**
 * Interface RouteInterface
 *
 * @package Hanaboso\PipesFramework\Commons\CustomRoute
 */
interface RouteInterface
{

    /**
     * @param Request $request
     * @param string  $partUrl
     *
     * @return bool
     */
    public function isSuitable(Request $request, string $partUrl): bool;

    /**
     * @param string $baseUrl
     *
     * @return string
     */
    public function getUrl(string $baseUrl): string;

    /**
     * @return string
     */
    public function getMethod(): string;

    /**
     * @return string|null
     */
    public function getCaption(): ?string;

}