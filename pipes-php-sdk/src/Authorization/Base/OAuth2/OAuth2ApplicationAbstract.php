<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\Authorization\Base\OAuth2;

use Hanaboso\CommonsBundle\Enum\AuthorizationTypeEnum;
use Hanaboso\CommonsBundle\Exception\DateTimeException;
use Hanaboso\CommonsBundle\Utils\DateTimeUtils;
use Hanaboso\PipesPhpSdk\Application\Base\ApplicationAbstract;
use Hanaboso\PipesPhpSdk\Application\Base\ApplicationInterface;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Application\Exception\ApplicationInstallException;
use Hanaboso\PipesPhpSdk\Application\Utils\ApplicationUtils;
use Hanaboso\PipesPhpSdk\Authorization\Base\Basic\BasicApplicationInterface;
use Hanaboso\PipesPhpSdk\Authorization\Exception\AuthorizationException;
use Hanaboso\PipesPhpSdk\Authorization\Provider\Dto\OAuth2Dto;
use Hanaboso\PipesPhpSdk\Authorization\Provider\OAuth2Provider;
use Hanaboso\PipesPhpSdk\Authorization\Utils\ScopeFormatter;

/**
 * Class OAuth2ApplicationAbstract
 *
 * @package Hanaboso\PipesPhpSdk\Authorization\Base\OAuth2
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
     * @param array              $scopes
     * @param string             $separator
     */
    public function authorize(
        ApplicationInstall $applicationInstall,
        array $scopes = [],
        string $separator = ScopeFormatter::COMMA
    ): void
    {
        $this->provider->authorize($this->createDto($applicationInstall), $scopes, $separator);
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
     * @throws DateTimeException
     */
    public function refreshAuthorization(ApplicationInstall $applicationInstall): ApplicationInstall
    {
        $token = $this->provider->refreshAccessToken(
            $this->createDto($applicationInstall),
            $this->getTokens($applicationInstall)
        );

        if (isset($token[OAuth2Provider::EXPIRES])) {
            $applicationInstall->setExpires(DateTimeUtils::getUtcDateTime()
                ->setTimestamp($token[OAuth2Provider::EXPIRES]));
        }

        $settings                                                                            = $applicationInstall->getSettings();
        $settings[ApplicationInterface::AUTHORIZATION_SETTINGS][ApplicationInterface::TOKEN] = $token;
        $applicationInstall->setSettings($settings);

        return $applicationInstall;
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
        string $redirectUrl
    ): OAuth2ApplicationInterface
    {
        $settings                                                                                   = $applicationInstall->getSettings();
        $settings[ApplicationInterface::AUTHORIZATION_SETTINGS][ApplicationInterface::REDIRECT_URL] = $redirectUrl;
        $applicationInstall->setSettings($settings);

        return $this;
    }

    /**
     * @param ApplicationInstall $applicationInstall
     * @param array              $token
     *
     * @return OAuth2ApplicationInterface
     * @throws AuthorizationException
     */
    public function setAuthorizationToken(
        ApplicationInstall $applicationInstall,
        array $token
    ): OAuth2ApplicationInterface
    {
        $token = $this->provider->getAccessToken($this->createDto($applicationInstall), $token);
        if (array_key_exists('expires', $token)) {
            $applicationInstall->setExpires(DateTimeUtils::getUtcDateTimeFromTimeStamp($token['expires']));
        }

        $settings = $applicationInstall->getSettings();

        $settings[ApplicationInterface::AUTHORIZATION_SETTINGS][ApplicationInterface::TOKEN] = $token;
        $applicationInstall->setSettings($settings);

        return $this;
    }

    /**
     * @param ApplicationInstall $applicationInstall
     *
     * @return string
     * @throws ApplicationInstallException
     */
    public function getAccessToken(ApplicationInstall $applicationInstall): string
    {
        if (isset($applicationInstall->getSettings()[ApplicationInterface::AUTHORIZATION_SETTINGS][ApplicationInterface::TOKEN][OAuth2Provider::ACCESS_TOKEN])) {

            return $applicationInstall->getSettings()[ApplicationInterface::AUTHORIZATION_SETTINGS][ApplicationInterface::TOKEN][OAuth2Provider::ACCESS_TOKEN];

        } else {

            throw new ApplicationInstallException('There is no access token',
                ApplicationInstallException::AUTHORIZATION_OAUTH2_ERROR);
        }
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

    /**
     * @param ApplicationInstall $applicationInstall
     * @param array              $settings
     *
     * @return ApplicationInstall
     */
    public function setApplicationSettings(ApplicationInstall $applicationInstall, array $settings): ApplicationInstall
    {
        $applicationInstall = parent::setApplicationSettings($applicationInstall, $settings);

        foreach ($applicationInstall->getSettings()[ApplicationAbstract::FORM] ?? [] as $key => $value) {
            if (in_array($key, [
                OAuth2ApplicationInterface::CLIENT_ID,
                OAuth2ApplicationInterface::CLIENT_SECRET,
            ], TRUE)) {
                $settings                                                          = $applicationInstall->getSettings();
                $settings[BasicApplicationInterface::AUTHORIZATION_SETTINGS][$key] = $value;
                $applicationInstall->setSettings($settings);
            }
        }

        return $applicationInstall;
    }

}
