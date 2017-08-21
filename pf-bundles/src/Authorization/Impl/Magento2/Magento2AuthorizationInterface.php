<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: radek.jirsa
 * Date: 10.8.17
 * Time: 12:31
 */

namespace Hanaboso\PipesFramework\Authorization\Impl\Magento2;

/**
 * Interface Magento2AuthorizationInterface
 *
 * @package Hanaboso\PipesFramework\Authorization\Impl\Magento2
 */
interface Magento2AuthorizationInterface
{

    /**
     * @return string
     */
    public function getUrl(): string;

    /**
     * @param string $method
     * @param string $url
     *
     * @return array
     */
    public function getHeaders(string $method, string $url): array;

}
