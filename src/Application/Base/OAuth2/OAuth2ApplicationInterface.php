<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Application\Base\OAuth2;

use Hanaboso\PipesFramework\Application\Base\ApplicationInterface;
use Hanaboso\PipesFramework\Application\Document\ApplicationInstall;

/**
 * Interface OAuth2ApplicationInterface
 *
 * @package Hanaboso\PipesFramework\Application\Base\OAuth2
 */
interface OAuth2ApplicationInterface extends ApplicationInterface
{

    public const  OAUTH2                = 'oauth2';
    public const  CLIENT_ID             = 'client_id';
    public const  CLIENT_SECRET         = 'client_secret';
    public const  FRONTEND_REDIRECT_URL = 'frontend_redirect_url';

    /**
     * @param ApplicationInstall $applicationInstall
     */
    public function authorize(ApplicationInstall $applicationInstall): void;

    /**
     * @param ApplicationInstall $applicationInstall
     *
     * @return ApplicationInstall
     */
    public function refreshAuthorization(ApplicationInstall $applicationInstall): ApplicationInstall;

    /**
     * @param ApplicationInstall $applicationInstall
     *
     * @return string
     */
    public function getAuthorizationRedirectUrl(ApplicationInstall $applicationInstall): string;

    /**
     * @param ApplicationInstall $applicationInstall
     * @param string             $redirectUrl
     *
     * @return OAuth2ApplicationInterface
     */
    public function setAuthorizationRedirectUrl(
        ApplicationInstall $applicationInstall,
        string $redirectUrl): OAuth2ApplicationInterface;

    /**
     * @param ApplicationInstall $applicationInstall
     * @param array              $token
     *
     * @return OAuth2ApplicationInterface
     */
    public function setAuthorizationToken(
        ApplicationInstall $applicationInstall,
        array $token): OAuth2ApplicationInterface;

}