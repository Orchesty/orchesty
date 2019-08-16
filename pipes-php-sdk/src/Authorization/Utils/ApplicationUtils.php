<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\Authorization\Utils;

use Hanaboso\PipesPhpSdk\Authorization\Document\ApplicationInstall;

/**
 * Class ApplicationUtils
 *
 * @package Hanaboso\PipesPhpSdk\Authorization\Utils
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
