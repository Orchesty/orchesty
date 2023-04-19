<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\Authorization\Base\OAuth1;

use Hanaboso\PipesPhpSdk\Application\Base\ApplicationInterface;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;

/**
 * Interface OAuth1ApplicationInterface
 *
 * @package Hanaboso\PipesPhpSdk\Authorization\Base\OAuth1
 */
interface OAuth1ApplicationInterface extends ApplicationInterface
{

    public const  OAUTH           = 'oauth';
    public const  CONSUMER_KEY    = 'consumer_key';
    public const  CONSUMER_SECRET = 'consumer_secret';

    /**
     * @param ApplicationInstall $applicationInstall
     *
     * @return string
     */
    public function authorize(ApplicationInstall $applicationInstall): string;

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
