<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Application\Base;

use Hanaboso\PipesFramework\Application\Document\ApplicationInstall;
use Hanaboso\PipesFramework\Authorization\Exception\AuthorizationException;
use Hanaboso\PipesFramework\Authorization\Provider\Dto\OAuth1DtoInterface;
use Hanaboso\PipesFramework\Authorization\Provider\OAuth1Provider;
use OAuthException;

abstract class OAuth1ApplicationAbstract extends BasicApplicationAbstract implements OAuth1ApplicationInterface
{

    /**
     * @var OAuth1Provider
     */
    private $provider;

    /**
     * @var OAuth1DtoInterface;
     */
    private $dto;

    /**
     * OAuth1ApplicationAbstract constructor.
     *
     * @param OAuth1Provider $provider
     */
    public function __construct(OAuth1Provider $provider)
    {
        $this->provider = $provider;
    }

    /**
     * @return string
     */
    public function getAuthorizationType(): string
    {
        return BasicApplicationAbstract::OAUTH;
    }

    /**
     * @param ApplicationInstall $applicationInstall
     *
     * @return bool
     */
    public function isAuthorized(ApplicationInstall $applicationInstall): bool
    {
        return isset($applicationInstall->getSettings()[BasicApplicationInterface::AUTHORIZATION_SETTINGS][BasicApplicationInterface::TOKEN]);
    }

    /**
     * @param ApplicationInstall $applicationInstall
     *
     * @throws AuthorizationException
     * @throws OAuthException
     */
    public function authorize(ApplicationInstall $applicationInstall): void
    {
        $this->provider->authorize
        (
            $this->dto,
            $this->getTokenUrl(),
            $this->getAuthorizeUrl(),
            $this->getRedirectUrl(),
            $this->saveOAuthStaff(),
            $this->getScopes()
        );
    }

    /**
     * @return string
     */
    abstract protected function getTokenUrl(): string;

    /**
     * @return string
     */
    abstract protected function getAuthorizeUrl(): string;

    /**
     * @return string
     */
    abstract protected function getRedirectUrl(): string;

    /**
     * @return callable
     */
    abstract protected function saveOAuthStaff(): callable;

    /**
     * @return array
     */
    abstract protected function getScopes(): array;

}