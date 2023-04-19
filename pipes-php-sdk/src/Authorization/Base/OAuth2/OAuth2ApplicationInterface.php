<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\Authorization\Base\OAuth2;

use Hanaboso\PipesPhpSdk\Application\Base\ApplicationInterface;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;

/**
 * Interface OAuth2ApplicationInterface
 *
 * @package Hanaboso\PipesPhpSdk\Authorization\Base\OAuth2
 */
interface OAuth2ApplicationInterface extends ApplicationInterface
{

    public const  CLIENT_ID     = 'client_id';
    public const  CLIENT_SECRET = 'client_secret';

    /**
     * @param ApplicationInstall $applicationInstall
     *
     * @return string
     */
    public function authorize(ApplicationInstall $applicationInstall): string;

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
    public function getFrontendRedirectUrl(ApplicationInstall $applicationInstall): string;

    /**
     * @param ApplicationInstall $applicationInstall
     * @param string             $redirectUrl
     *
     * @return self
     */
    public function setFrontendRedirectUrl(ApplicationInstall $applicationInstall, string $redirectUrl): self;

    /**
     * @param ApplicationInstall $applicationInstall
     * @param mixed[]            $token
     *
     * @return self
     */
    public function setAuthorizationToken(ApplicationInstall $applicationInstall, array $token): self;

}
