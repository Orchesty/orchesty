<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\Authorization\Provider;

/**
 * Interface OAuthProviderInterface
 *
 * @package Hanaboso\PipesPhpSdk\Authorization\Provider
 */
interface OAuthProviderInterface
{

    /**
     * @return string
     */
    public function getRedirectUri(): string;

}
