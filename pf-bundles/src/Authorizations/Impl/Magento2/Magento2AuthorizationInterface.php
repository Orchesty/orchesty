<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: radek.jirsa
 * Date: 10.8.17
 * Time: 12:31
 */

namespace Hanaboso\PipesFramework\Authorizations\Impl\Magento2;

/**
 * Interface Magento2AuthorizationInterface
 *
 * @package Hanaboso\PipesFramework\Connector\Impl\Magento2
 */
interface Magento2AuthorizationInterface
{

    /**
     * @return string
     */
    public function getUrl(): string;

    /**
     * @return array
     */
    public function getHeaders(): array;

}
