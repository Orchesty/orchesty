<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Application\Base\OAuth1;

use Hanaboso\PipesFramework\Application\Base\ApplicationInterface;
use Hanaboso\PipesFramework\Application\Document\ApplicationInstall;

/**
 * Interface OAuth1ApplicationInterface
 *
 * @package Hanaboso\PipesFramework\Application\Base\OAuth1
 */
interface OAuth1ApplicationInterface extends ApplicationInterface
{

    public const  CONSUMER_KEY    = 'consumer_key';
    public const  CONSUMER_SECRET = 'consumer_secret';

    /**
     * @param ApplicationInstall $applicationInstall
     */
    public function authorize(ApplicationInstall $applicationInstall): void;

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
     * @return OAuth1ApplicationInterface
     */
    public function setFrontendRedirectUrl(
        ApplicationInstall $applicationInstall,
        string $redirectUrl
    ): OAuth1ApplicationInterface;

    /**
     * @param ApplicationInstall $applicationInstall
     * @param array              $token
     *
     * @return OAuth1ApplicationInterface
     */
    public function setAuthorizationToken(
        ApplicationInstall $applicationInstall,
        array $token
    ): OAuth1ApplicationInterface;

}