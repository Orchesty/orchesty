<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: radek.jirsa
 * Date: 10.8.17
 * Time: 12:31
 */

namespace Hanaboso\PipesFramework\Authorization\Impl\Magento2;

use Hanaboso\PipesFramework\Authorization\Base\AuthorizationInterface;

/**
 * Interface Magento2AuthorizationInterface
 *
 * @package Hanaboso\PipesFramework\Authorization\Impl\Magento2Old
 */
interface Magento2AuthorizationInterface extends AuthorizationInterface
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
