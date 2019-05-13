<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Application\Base\OAuth1;

use Hanaboso\PipesFramework\Application\Base\ApplicationAbstract;
use Hanaboso\PipesFramework\Application\Base\Basic\BasicApplicationInterface;
use Hanaboso\PipesFramework\Application\Document\ApplicationInstall;
use Hanaboso\PipesFramework\Authorization\Exception\AuthorizationException;
use Hanaboso\PipesFramework\Authorization\Provider\Dto\OAuth1Dto;
use Hanaboso\PipesFramework\Authorization\Provider\OAuth1Provider;
use OAuthException;

/**
 * Class OAuth1ApplicationAbstract
 *
 * @package Hanaboso\PipesFramework\Application\Base\OAuth1
 */
abstract class OAuth1ApplicationAbstract extends ApplicationAbstract implements OAuth1ApplicationInterface
{

    /**
     * @var OAuth1Provider
     */
    protected $provider;

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
        return OAuth1ApplicationInterface::OAUTH;
    }

    /**
     * @param ApplicationInstall $applicationInstall
     *
     * @return bool
     */
    public function isAuthorize(ApplicationInstall $applicationInstall): bool
    {
        return isset($applicationInstall->getSettings()[BasicApplicationInterface::AUTHORIZATION_SETTINGS][OAuth1ApplicationInterface::OAUTH]);
    }

    /**
     * @param ApplicationInstall $applicationInstall
     *
     * @throws AuthorizationException
     * @throws OAuthException
     */
    public function authorize(ApplicationInstall $applicationInstall): void
    {
        $this->provider->authorize(
            $this->createDto($applicationInstall),
            $this->getTokenUrl(),
            $this->getAuthorizeUrl(),
            $this->getRedirectUrl(),
            $this->saveOAuthStaff(),
            );
    }

    /**
     * @param ApplicationInstall $applicationInstall
     *
     * @return string
     */
    public function getAuthorizationRedirectUrl(ApplicationInstall $applicationInstall): string
    {
        return $applicationInstall->getSettings()[BasicApplicationInterface::AUTHORIZATION_SETTINGS][BasicApplicationInterface::REDIRECT_URL];
    }

    /**
     * @param ApplicationInstall $applicationInstall
     * @param string             $redirectUrl
     *
     * @return OAuth1ApplicationInterface
     */
    public function setAuthorizationRedirectUrl(
        ApplicationInstall $applicationInstall,
        string $redirectUrl): OAuth1ApplicationInterface
    {
        $applicationInstall->setSettings([
            BasicApplicationInterface::AUTHORIZATION_SETTINGS => [BasicApplicationInterface::REDIRECT_URL => $redirectUrl],
        ]);

        return $this;
    }

    /**
     * @param ApplicationInstall $applicationInstall
     * @param array              $token
     *
     * @return OAuth1ApplicationInterface
     */
    public function setAuthorizationToken(
        ApplicationInstall $applicationInstall,
        array $token): OAuth1ApplicationInterface
    {
        $applicationInstall->setSettings([
            BasicApplicationInterface::AUTHORIZATION_SETTINGS => [BasicApplicationInterface::TOKEN => $token],
        ]);

        return $this;
    }

    /**
     * @param ApplicationInstall $applicationInstall
     *
     * @return OAuth1Dto
     */
    protected function createDto(ApplicationInstall $applicationInstall): OAuth1Dto
    {
        return new OAuth1Dto($applicationInstall);
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
    abstract protected function saveOauthStaff(): callable;

}
