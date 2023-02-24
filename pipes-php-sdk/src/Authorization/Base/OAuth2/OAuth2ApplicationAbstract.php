<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\Authorization\Base\OAuth2;

use Hanaboso\CommonsBundle\Enum\AuthorizationTypeEnum;
use Hanaboso\PipesPhpSdk\Application\Base\ApplicationAbstract;
use Hanaboso\PipesPhpSdk\Application\Base\ApplicationInterface;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Application\Exception\ApplicationInstallException;
use Hanaboso\PipesPhpSdk\Application\Model\Form\Field;
use Hanaboso\PipesPhpSdk\Authorization\Exception\AuthorizationException;
use Hanaboso\PipesPhpSdk\Authorization\Provider\Dto\OAuth2Dto;
use Hanaboso\PipesPhpSdk\Authorization\Provider\OAuth2Provider;
use Hanaboso\PipesPhpSdk\Authorization\Utils\ScopeFormatter;
use Hanaboso\Utils\Date\DateTimeUtils;
use Hanaboso\Utils\Exception\DateTimeException;

/**
 * Class OAuth2ApplicationAbstract
 *
 * @package Hanaboso\PipesPhpSdk\Authorization\Base\OAuth2
 */
abstract class OAuth2ApplicationAbstract extends ApplicationAbstract implements OAuth2ApplicationInterface
{

    protected const SCOPE_SEPARATOR = ScopeFormatter::COMMA;
    protected const CREDENTIALS     = [
        OAuth2ApplicationInterface::CLIENT_ID,
        OAuth2ApplicationInterface::CLIENT_SECRET,
    ];

    /**
     * @return string
     */
    abstract public function getAuthUrl(): string;

    /**
     * @return string
     */
    abstract public function getTokenUrl(): string;

    /**
     * OAuth2ApplicationAbstract constructor.
     *
     * @param OAuth2Provider $provider
     */
    public function __construct(protected OAuth2Provider $provider)
    {
    }

    /**
     * @return string
     */
    public function getAuthorizationType(): string
    {
        return AuthorizationTypeEnum::OAUTH2->value;
    }

    /**
     * @param ApplicationInstall $applicationInstall
     *
     * @return string
     */
    public function authorize(ApplicationInstall $applicationInstall): string
    {
        return $this->provider->authorize(
            $this->createDto($applicationInstall),
            $this->getScopes($applicationInstall),
            static::SCOPE_SEPARATOR,
        );
    }

    /**
     * @param ApplicationInstall $applicationInstall
     *
     * @return bool
     */
    public function isAuthorized(ApplicationInstall $applicationInstall): bool
    {
        return isset(
            $applicationInstall->getSettings(
            )[ApplicationInterface::AUTHORIZATION_FORM][ApplicationInterface::TOKEN][OAuth2Provider::ACCESS_TOKEN],
        );
    }

    /**
     * @param ApplicationInstall $applicationInstall
     *
     * @return mixed[]
     * @throws ApplicationInstallException
     */
    public function getApplicationForms(ApplicationInstall $applicationInstall): array
    {
        $form = parent::getApplicationForms($applicationInstall);

        if ($form[ApplicationInterface::AUTHORIZATION_FORM]) {
            $form[ApplicationInterface::AUTHORIZATION_FORM][ApplicationInterface::FIELDS] = array_merge(
                $form[ApplicationInterface::AUTHORIZATION_FORM][ApplicationInterface::FIELDS],
                [
                    (new Field(
                        Field::TEXT,
                        ApplicationInterface::OAUTH_REDIRECT_URL,
                        'Redirect URL',
                        $this->provider->getRedirectUri(),
                    )
                    )->setReadOnly(TRUE)->toArray(),
                ],
            );
        }

        return $form;
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
            $this->getTokens($applicationInstall),
        );

        if (isset($token[OAuth2Provider::EXPIRES])) {
            $applicationInstall->setExpires(
                DateTimeUtils::getUtcDateTime()
                    ->setTimestamp($token[OAuth2Provider::EXPIRES]),
            );
        }

        $settings                                                                        = $applicationInstall->getSettings();
        $settings[ApplicationInterface::AUTHORIZATION_FORM][ApplicationInterface::TOKEN] = $token;
        $applicationInstall->addSettings($settings);

        return $applicationInstall;
    }

    /**
     * @param ApplicationInstall $applicationInstall
     *
     * @return string
     */
    public function getFrontendRedirectUrl(ApplicationInstall $applicationInstall): string
    {
        return $applicationInstall->getSettings(
        )[ApplicationInterface::AUTHORIZATION_FORM][ApplicationInterface::FRONTEND_REDIRECT_URL];
    }

    /**
     * @param ApplicationInstall $applicationInstall
     * @param string             $redirectUrl
     *
     * @return OAuth2ApplicationInterface
     */
    public function setFrontendRedirectUrl(
        ApplicationInstall $applicationInstall,
        string $redirectUrl,
    ): OAuth2ApplicationInterface
    {
        $settings = $applicationInstall->getSettings();

        $settings[ApplicationInterface::AUTHORIZATION_FORM][ApplicationInterface::FRONTEND_REDIRECT_URL] = $redirectUrl;
        $applicationInstall->addSettings($settings);

        return $this;
    }

    /**
     * @param ApplicationInstall $applicationInstall
     * @param mixed[]            $token
     *
     * @return OAuth2ApplicationInterface
     * @throws AuthorizationException
     */
    public function setAuthorizationToken(
        ApplicationInstall $applicationInstall,
        array $token,
    ): OAuth2ApplicationInterface
    {
        $token = $this->provider->getAccessToken($this->createDto($applicationInstall), $token);
        if (array_key_exists('expires', $token)) {
            $applicationInstall->setExpires(DateTimeUtils::getUtcDateTimeFromTimeStamp($token['expires']));
        }

        $settings = $applicationInstall->getSettings();

        $settings[ApplicationInterface::AUTHORIZATION_FORM][ApplicationInterface::TOKEN] = $token;
        $applicationInstall->addSettings($settings);

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
        if (isset($applicationInstall->getSettings()[ApplicationInterface::AUTHORIZATION_FORM][ApplicationInterface::TOKEN][OAuth2Provider::ACCESS_TOKEN])) {
            return $applicationInstall->getSettings(
            )[ApplicationInterface::AUTHORIZATION_FORM][ApplicationInterface::TOKEN][OAuth2Provider::ACCESS_TOKEN];
        } else {
            throw new ApplicationInstallException(
                'There is no access token',
                ApplicationInstallException::AUTHORIZATION_OAUTH2_ERROR,
            );
        }
    }

    /**
     * @param ApplicationInstall $applicationInstall
     * @param string|null        $redirectUrl
     *
     * @return OAuth2Dto
     */
    protected function createDto(ApplicationInstall $applicationInstall, ?string $redirectUrl = NULL): OAuth2Dto
    {
        $dto = new OAuth2Dto($applicationInstall, $this->getAuthUrl(), $this->getTokenUrl());
        $dto->setCustomAppDependencies($applicationInstall->getUser() ?? '', $applicationInstall->getKey() ?? '');

        if ($redirectUrl) {
            $dto->setRedirectUrl($redirectUrl);
        }

        return $dto;
    }

    /**
     * @param ApplicationInstall $applicationInstall
     *
     * @return mixed[]
     */
    protected function getTokens(ApplicationInstall $applicationInstall): array
    {
        return $applicationInstall->getSettings(
        )[ApplicationInterface::AUTHORIZATION_FORM][ApplicationInterface::TOKEN];
    }

    /**
     * @param ApplicationInstall $applicationInstall
     *
     * @return mixed[]
     */
    protected function getScopes(ApplicationInstall $applicationInstall): array
    {
        $applicationInstall;

        return [];
    }

}
