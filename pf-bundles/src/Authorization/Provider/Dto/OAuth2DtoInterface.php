<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: radek.jirsa
 * Date: 26.9.17
 * Time: 8:08
 */

namespace Hanaboso\PipesFramework\Authorization\Provider\Dto;

/**
 * Interface OAuth2DtoInterface
 *
 * @package Hanaboso\PipesFramework\Authorization\Provider\Dto
 */
interface OAuth2DtoInterface
{

    /**
     * @return string
     */
    public function getClientId(): string;

    /**
     * @return string
     */
    public function getClientSecret(): string;

    /**
     * @return string
     */
    public function getRedirectUrl(): string;

    /**
     * @return string
     */
    public function getAuthorizeUrl(): string;

    /**
     * @return string
     */
    public function getTokenUrl(): string;

}