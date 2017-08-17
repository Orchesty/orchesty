<?php
/**
 * Created by PhpStorm.
 * User: radek.jirsa
 * Date: 17.8.17
 * Time: 13:52
 */

namespace Hanaboso\PipesFramework\Authorizations\Provider;

/**
 * Interface ProviderInterface
 *
 * @package Hanaboso\PipesFramework\Authorizations\Provider
 */
interface ProviderInterface
{

    public function authorize(array $data): void;

}