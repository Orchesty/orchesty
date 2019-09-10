<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\Application\Utils;

use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;

/**
 * Class ApplicationUtils
 *
 * @package Hanaboso\PipesPhpSdk\Application\Utils
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
            return sprintf('/api/applications/%s/users/%s/authorize/token',
                $systemInstall->getKey(),
                $systemInstall->getUser(),
            );
        } else {
            return '/api/applications/authorize/token';
        }
    }

}
