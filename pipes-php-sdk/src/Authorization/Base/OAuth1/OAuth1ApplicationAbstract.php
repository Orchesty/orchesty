<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\Authorization\Base\OAuth1;

use Hanaboso\CommonsBundle\Enum\AuthorizationTypeEnum;
use Hanaboso\PipesPhpSdk\Application\Base\ApplicationAbstract;
use Hanaboso\PipesPhpSdk\Application\Base\ApplicationInterface;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Application\Exception\ApplicationInstallException;
use Hanaboso\PipesPhpSdk\Application\Model\Form\Field;
use Hanaboso\PipesPhpSdk\Application\Repository\ApplicationInstallRepository;
use Hanaboso\PipesPhpSdk\Authorization\Exception\AuthorizationException;
use Hanaboso\PipesPhpSdk\Authorization\Provider\Dto\OAuth1Dto;
use Hanaboso\PipesPhpSdk\Authorization\Provider\OAuth1Provider;
use OAuthException;

/**
 * Class OAuth1ApplicationAbstract
 *
 * @package Hanaboso\PipesPhpSdk\Authorization\Base\OAuth1
 */
abstract class OAuth1ApplicationAbstract extends ApplicationAbstract implements OAuth1ApplicationInterface
{

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
    abstract protected function getAccessTokenUrl(): string;

    /**
     * OAuth1ApplicationAbstract constructor.
     *
     * @param OAuth1Provider $provider
     */
    public function __construct(protected OAuth1Provider $provider)
    {
    }

    /**
     * @return string
     */
    public function getAuthorizationType(): string
    {
        return AuthorizationTypeEnum::OAUTH->value;
    }

    /**
     * @param ApplicationInstall $applicationInstall
     *
     * @return bool
     */
    public function isAuthorized(ApplicationInstall $applicationInstall): bool
    {
        return isset(
            $applicationInstall->getSettings()[ApplicationInterface::AUTHORIZATION_FORM][ApplicationInterface::TOKEN],
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

        return $form;
    }

    /**
     * @param ApplicationInstall $applicationInstall
     *
     * @return string
     * @throws AuthorizationException
     * @throws OAuthException
     */
    public function authorize(ApplicationInstall $applicationInstall): string
    {
        return $this->provider->authorize(
            $this->createDto($applicationInstall),
            $this->getTokenUrl(),
            $this->getAuthorizeUrl(),
            $this->saveOauthStuff(),
        );
    }

    /**
     * @param ApplicationInstall $applicationInstall
     * @param mixed[]            $token
     *
     * @return OAuth1ApplicationInterface
     * @throws AuthorizationException
     * @throws OAuthException
     */
    public function setAuthorizationToken(
        ApplicationInstall $applicationInstall,
        array $token,
    ): OAuth1ApplicationInterface
    {
        $token = $this->provider->getAccessToken(
            $this->createDto($applicationInstall),
            $token,
            $this->getAccessTokenUrl(),
        );

        $settings = $applicationInstall->getSettings();

        $settings[ApplicationInterface::AUTHORIZATION_FORM][ApplicationInterface::TOKEN] = $token;
        $applicationInstall->addSettings($settings);

        return $this;
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
     * @return OAuth1ApplicationInterface
     */
    public function setFrontendRedirectUrl(
        ApplicationInstall $applicationInstall,
        string $redirectUrl,
    ): OAuth1ApplicationInterface
    {
        $settings = $applicationInstall->getSettings();

        $settings[ApplicationInterface::AUTHORIZATION_FORM][ApplicationInterface::FRONTEND_REDIRECT_URL] = $redirectUrl;
        $applicationInstall->addSettings($settings);

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
     * @return callable
     */
    protected function saveOauthStuff(): callable
    {
        return static function (ApplicationInstallRepository $applicationInstallRepository, OAuth1Dto $dto, array $data): void {
            $dto->getApplicationInstall()->addSettings(
                [ApplicationInterface::AUTHORIZATION_FORM => [OAuth1ApplicationInterface::OAUTH => $data]],
            );

            $applicationInstallRepository->update($dto->getApplicationInstall());
        };
    }

}
