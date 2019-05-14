<?php declare(strict_types=1);

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

    /**
     * @return bool
     */
    public function isCustomApp(): bool;

    /**
     * @return string
     */
    public function getUser(): string;

    /**
     * @return string
     */
    public function getApplicationKey(): string;

    /**
     * @param string $user
     * @param string $applicationKey
     */
    public function setCustomAppDependencies(string $user, string $applicationKey): void;

}
