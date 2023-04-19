<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\Authorization\Provider\Dto;

/**
 * Interface OAuth2DtoInterface
 *
 * @package Hanaboso\PipesPhpSdk\Authorization\Provider\Dto
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
     * @param string $redirectUrl
     *
     * @return self
     */
    public function setRedirectUrl(string $redirectUrl): self;

    /**
     *
     */
    public function isRedirectUrl(): bool;

    /**
     * @return string|null
     */
    public function getRedirectUrl(): ?string;

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
