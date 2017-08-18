<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: radek.jirsa
 * Date: 17.8.17
 * Time: 16:28
 */

namespace Hanaboso\PipesFramework\Commons\Redirect;

/**
 * Interface RedirectInterface
 *
 * @package Hanaboso\PipesFramework\Commons\Redirect
 */
interface RedirectInterface
{

    /**
     * @param string $url
     */
    public function make(string $url): void;

}