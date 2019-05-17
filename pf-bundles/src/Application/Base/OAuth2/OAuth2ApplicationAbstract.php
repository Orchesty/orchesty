<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Application\Base\OAuth2;

use Hanaboso\CommonsBundle\Enum\AuthorizationTypeEnum;
use Hanaboso\PipesFramework\Application\Base\ApplicationAbstract;
use Hanaboso\PipesFramework\Application\Base\ApplicationInterface;
use Hanaboso\PipesFramework\Application\Document\ApplicationInstall;
use Hanaboso\PipesFramework\Application\Utils\ApplicationUtils;
use Hanaboso\PipesFramework\Authorization\Exception\AuthorizationException;
use Hanaboso\PipesFramework\Authorization\Provider\Dto\OAuth2Dto;
use Hanaboso\PipesFramework\Authorization\Provider\OAuth2Provider;

/**
 * Class OAuth2ApplicationAbstract
 *
 * @package Hanaboso\PipesFramework\Application\Base\OAuth2
 */
abstract class OAuth2ApplicationAbstract extends ApplicationAbstract implements OAuth2ApplicationInterface
{

    /**
     * @var OAuth2Provider
     */
    private $provider;

    /**
     * OAuth2ApplicationAbstract constructor.
     *
     * @param OAuth2Provider $provider
     */
    public function __construct(OAuth2Provider $provider)
    {
        $this->provider = $provider;
    }

    /**
     * @return string
     */
    abstract public function getAuthUrl(): string;

    /**
     * @return string
     */
    abstract public function getTokenUrl(): string;

    /**
     * @return string
     */
    public function getAuthorizationType(): string
    {
        return AuthorizationTypeEnum::OAUTH2;
    }

    /**
     * @param ApplicationInstall $applicationInstall
     */
    public function authorize(ApplicationInstall $applicationInstall): void
    {
        $this->provider->authorize($this->createDto($applicationInstall));
    }

    /**
     * @param ApplicationInstall $applicationInstall
     *
     * @return bool
     */
    public function isAuthorize(ApplicationInstall $applicationInstall): bool
    {
        return isset($applicationInstall->getSettings()[ApplicationInterface::AUTHORIZATION_SETTINGS][OAuth2ApplicationInterface::TOKEN]);
    }

    /**
     * @param ApplicationInstall $applicationInstall
     *
     * @return ApplicationInstall
     * @throws AuthorizationException
     */
    public function refreshAuthorization(ApplicationInstall $applicationInstall): ApplicationInstall
    {
        $token = $this->provider->refreshAccessToken(
            $this->createDto($applicationInstall),
            $this->getTokens($applicationInstall)
        );

        return $applicationInstall->setSettings([ApplicationInterface::AUTHORIZATION_SETTINGS => [ApplicationInterface::TOKEN => $token]]);
    }

    /**
     * @param ApplicationInstall $applicationInstall
     *
     * @return string
     */
    public function getFrontendRedirectUrl(ApplicationInstall $applicationInstall): string
    {
        return $applicationInstall->getSettings()[ApplicationInterface::AUTHORIZATION_SETTINGS][ApplicationInterface::REDIRECT_URL];
    }

    /**
     * @param ApplicationInstall $applicationInstall
     * @param string             $redirectUrl
     *
     * @return OAuth2ApplicationInterface
     */
    public function setFrontendRedirectUrl(
        ApplicationInstall $applicationInstall,
        string $redirectUrl): OAuth2ApplicationInterface
    {
        $applicationInstall->setSettings([ApplicationInterface::AUTHORIZATION_SETTINGS => [ApplicationInterface::REDIRECT_URL => $redirectUrl]]);

        return $this;
    }

    /**
     * @param ApplicationInstall $applicationInstall
     * @param array              $token
     *
     * @return OAuth2ApplicationInterface
     */
    public function setAuthorizationToken(
        ApplicationInstall $applicationInstall,
        array $token): OAuth2ApplicationInterface
    {
        $applicationInstall->setSettings([ApplicationInterface::AUTHORIZATION_SETTINGS => [ApplicationInterface::TOKEN => $token]]);

        return $this;
    }

    /**
     * @param ApplicationInstall $applicationInstall
     *
     * @return OAuth2Dto
     */
    protected function createDto(ApplicationInstall $applicationInstall): OAuth2Dto
    {
        $redirectUrl = ApplicationUtils::generateUrl();

        $dto = new OAuth2Dto($applicationInstall, $redirectUrl, $this->getAuthUrl(), $this->getTokenUrl());
        $dto->setCustomAppDependencies($applicationInstall->getUser(), $applicationInstall->getKey());

        return $dto;
    }

    /**
     * @param ApplicationInstall $applicationInstall
     *
     * @return array
     */
    protected function getTokens(ApplicationInstall $applicationInstall): array
    {
        return $applicationInstall->getSettings()[ApplicationInterface::AUTHORIZATION_SETTINGS][ApplicationInterface::TOKEN];
    }

}
