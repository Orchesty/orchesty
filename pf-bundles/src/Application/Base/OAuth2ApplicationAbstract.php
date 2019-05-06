<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Application\Base;

use Hanaboso\PipesFramework\Application\Document\ApplicationInstall;
use Hanaboso\PipesFramework\Application\Utils\ApplicationUtils;
use Hanaboso\PipesFramework\Authorization\Exception\AuthorizationException;
use Hanaboso\PipesFramework\Authorization\Provider\Dto\OAuth2Dto;
use Hanaboso\PipesFramework\Authorization\Provider\Dto\OAuth2DtoInterface;
use Hanaboso\PipesFramework\Authorization\Provider\OAuth2Provider;

/**
 * Class OAuth2ApplicationAbstract
 *
 * @package Hanaboso\PipesFramework\Application\Base
 */
abstract class OAuth2ApplicationAbstract extends ApplicationAbstract implements OAuth2ApplicationInterface
{

    private const AUTHORIZE_URL = 'https://appcenter.intuit.com/connect/oauth2';
    private const TOKEN_URL     = 'https://oauth.platform.intuit.com/oauth2/v1/tokens/bearer';

    /**
     * @var OAuth2Provider
     */
    private $provider;

    /**
     * @var OAuth2DtoInterface
     */
    private $dto;

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
    public function getAuthorizationType(): string
    {
        return OAuth2ApplicationInterface::OAUTH2;
    }

    /**
     * @param ApplicationInstall $applicationInstall
     */
    public function authorize(ApplicationInstall $applicationInstall): void
    {
        $redirectUrl = ApplicationUtils::generateUrl();
        $this->dto   = new OAuth2Dto($applicationInstall, $redirectUrl, self::AUTHORIZE_URL, self::TOKEN_URL);

        $this->provider->authorize($this->dto);
    }

    /**
     * @param ApplicationInstall $applicationInstall
     *
     * @return ApplicationInstall
     * @throws AuthorizationException
     */
    public function refreshAuthorization(ApplicationInstall $applicationInstall): ApplicationInstall
    {
        $accessToken = $this->provider->refreshAccessToken($this->dto, $this->getTokens());

        return $applicationInstall->setSettings([
            BasicApplicationInterface::AUTHORIZATION_SETTINGS => [BasicApplicationInterface::TOKEN => $accessToken],
        ]);

    }

    /**
     * @return array
     */
    abstract protected function getTokens(): array;

}