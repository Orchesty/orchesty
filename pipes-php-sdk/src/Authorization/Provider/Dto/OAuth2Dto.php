<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\Authorization\Provider\Dto;

use Hanaboso\PipesPhpSdk\Application\Base\ApplicationInterface;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Authorization\Base\OAuth2\OAuth2ApplicationInterface;

/**
 * Class OAuth2Dto
 *
 * @package Hanaboso\PipesPhpSdk\Authorization\Provider\Dto
 */
final class OAuth2Dto implements OAuth2DtoInterface
{

    /**
     * @var string
     */
    private string $clientId;

    /**
     * @var string
     */
    private string $clientSecret;

    /**
     * @var string
     */
    private string $redirectUrl;

    /**
     * @var string
     */
    private string $user = '';

    /**
     * @var string
     */
    private string $applicationKey = '';

    /**
     * OAuth2Dto constructor.
     *
     * @param ApplicationInstall $authorization
     * @param string             $authorizeUrl
     * @param string             $tokenUrl
     */
    public function __construct(
        ApplicationInstall $authorization,
        private string $authorizeUrl,
        private string $tokenUrl,
    )
    {
        $this->clientId     = $authorization->getSettings()
                              [ApplicationInterface::AUTHORIZATION_FORM][OAuth2ApplicationInterface::CLIENT_ID] ?? '';
        $this->clientSecret = $authorization->getSettings()
                              [ApplicationInterface::AUTHORIZATION_FORM][OAuth2ApplicationInterface::CLIENT_SECRET] ?? '';
    }

    /**
     * @return string
     */
    public function getClientId(): string
    {
        return $this->clientId;
    }

    /**
     * @return string
     */
    public function getClientSecret(): string
    {
        return $this->clientSecret;
    }

    /**
     * @param string $redirectUrl
     *
     * @return OAuth2DtoInterface
     */
    public function setRedirectUrl(string $redirectUrl): OAuth2DtoInterface
    {
        $this->redirectUrl = $redirectUrl;

        return $this;
    }

    /**
     *
     */
    public function isRedirectUrl(): bool
    {
        return isset($this->redirectUrl);
    }

    /**
     * @return string
     */
    public function getRedirectUrl(): string
    {
        return $this->redirectUrl;
    }

    /**
     * @return string
     */
    public function getAuthorizeUrl(): string
    {
        return $this->authorizeUrl;
    }

    /**
     * @return string
     */
    public function getTokenUrl(): string
    {
        return $this->tokenUrl;
    }

    /**
     * @return bool
     */
    public function isCustomApp(): bool
    {
        return $this->user === '' && $this->applicationKey === '';
    }

    /**
     * @return string
     */
    public function getUser(): string
    {
        return $this->user;
    }

    /**
     * @return string
     */
    public function getApplicationKey(): string
    {
        return $this->applicationKey;
    }

    /**
     * @param string $user
     * @param string $applicationKey
     */
    public function setCustomAppDependencies(string $user, string $applicationKey): void
    {
        $this->user           = $user;
        $this->applicationKey = $applicationKey;
    }

}
