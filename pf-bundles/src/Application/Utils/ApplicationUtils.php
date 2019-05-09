<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Application\Utils;

use Hanaboso\PipesFramework\Application\Base\OAuth2\OAuth2ApplicationInterface;
use Hanaboso\PipesFramework\Application\Document\ApplicationInstall;

/**
 * Class ApplicationUtils
 *
 * @package Hanaboso\PipesFramework\Application\Utils
 */
final class ApplicationUtils
{

    /**
     * @param ApplicationInstall|null $systemInstall
     *
     * @return string
     */
    public static function generateUrl(?ApplicationInstall $systemInstall = NULL): string
    {
        if ($systemInstall) {
            return sprintf('/user/user/%s/authorize_redirect/%s',
                $systemInstall->getUser(),
                $systemInstall->getSettings()[OAuth2ApplicationInterface::FRONTEND_REDIRECT_URL]
            );
        } else {
            return '/user/saveToken';
        }
    }

}