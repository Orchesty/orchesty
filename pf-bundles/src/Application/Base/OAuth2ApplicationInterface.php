<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Application\Base;

use Hanaboso\PipesFramework\Application\Document\ApplicationInstall;

/**
 * Interface OAuth2ApplicationInterface
 *
 * @package Hanaboso\PipesFramework\Application\Base
 */
interface OAuth2ApplicationInterface extends OAuth1ApplicationInterface
{

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

}