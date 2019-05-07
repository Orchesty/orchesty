<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Application\Base;

use Hanaboso\PipesFramework\Application\Document\ApplicationInstall;
use Hanaboso\PipesFramework\Authorization\Exception\AuthorizationException;
use Hanaboso\PipesFramework\Authorization\Provider\Dto\OAuth1Dto;
use Hanaboso\PipesFramework\Authorization\Provider\Dto\OAuth1DtoInterface;
use Hanaboso\PipesFramework\Authorization\Provider\OAuth1Provider;
use OAuthException;

/**
 * Class OAuth1ApplicationAbstract
 *
 * @package Hanaboso\PipesFramework\Application\Base
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

    protected function createDto(ApplicationInstall $applicationInstall)
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