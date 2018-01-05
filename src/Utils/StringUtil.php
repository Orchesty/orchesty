<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: venca
 * Date: 5.10.17
 * Time: 10:23
 */

namespace Hanaboso\PipesFramework\Utils;

use Nette\Utils\Strings;

/**
 * Class Strings
 *
 * @package Hanaboso\PipesFramework\Utils
 */
class StringUtil
{

    /**
     * @param string $string
     * @param bool   $firstLower
     *
     * @return string
     */
    public static function toCamelCase(string $string, bool $firstLower = FALSE): string
    {
        $camelCase = Strings::replace($string, '#(\.\w|_\w)#', function ($matches) {
            return Strings::firstUpper(Strings::substring($matches[0], 1));
        });

        if ($firstLower === TRUE) {
            return Strings::firstLower($camelCase);
        }

        return Strings::firstUpper($camelCase);
    }

    /**
     * @param mixed $object
     *
     * @return string
     */
    public static function getShortClassName($object): string
    {
        return substr(strrchr(get_class($object), "\\"), 1);
    }

}