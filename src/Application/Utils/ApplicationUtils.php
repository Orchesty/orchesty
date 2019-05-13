<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Application\Utils;

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
            return sprintf('/applications/%s/users/%s/authorize/token',
                $systemInstall->getKey(),
                $systemInstall->getUser(),
            );
        } else {
            return '/applications/authorize/token';
        }
    }

}