<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\Authorization\Provider\Dto;

use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Authorization\Base\Basic\BasicApplicationInterface;
use Hanaboso\PipesPhpSdk\Authorization\Base\OAuth2\OAuth2ApplicationInterface;

/**
 * Class OAuth2Dto
 *
 * @package Hanaboso\PipesPhpSdk\Authorization\Provider\Dto
 */
class OAuth2Dto implements OAuth2DtoInterface
{

    /**
     * @var string
     */
    private $clientId;

    /**
     * @var string
     */
    private $clientSecret;

    /**
     * @var string
     */
    private $redirectUrl;

    /**
     * @var string
     */
    private $authorizeUrl;

    /**
     * @var string
     */
    private $tokenUrl;

    /**
     * @var string
     */
    private $user = '';

    /**
     * @var string
     */
    private $applicationKey = '';

    /**
     * OAuth2Dto constructor.
     *
     * @param ApplicationInstall $authorization
     * @param string             $redirectUrl
     * @param string             $authorizeUrl
     * @param string             $tokenUrl
     */
    public function __construct(
        ApplicationInstall $authorization,
        string $redirectUrl,
        string $authorizeUrl,
        string $tokenUrl
    )
    {
        $this->redirectUrl  = $redirectUrl;
        $this->authorizeUrl = $authorizeUrl;
        $this->tokenUrl     = $tokenUrl;
        $this->clientId     = $authorization->getSettings()
                              [BasicApplicationInterface::AUTHORIZATION_SETTINGS][OAuth2ApplicationInterface::CLIENT_ID] ?? '';
        $this->clientSecret = $authorization->getSettings()
                              [BasicApplicationInterface::AUTHORIZATION_SETTINGS][OAuth2ApplicationInterface::CLIENT_SECRET] ?? '';
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
        return empty($this->user) && empty($this->applicationKey);
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
