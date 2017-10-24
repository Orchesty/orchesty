<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: radek.jirsa
 * Date: 26.9.17
 * Time: 9:54
 */

namespace Hanaboso\PipesFramework\Authorization\Utils;

/**
 * Class ScopeFormater
 *
 * @package Hanaboso\PipesFramework\Authorization\Utils
 */
final class ScopeFormatter
{

    public const COMMA = ',';
    public const SPACE = ' ';

    /**
     * ScopeFormater constructor.
     */
    private function __construct()
    {
    }

    /**
     * @param array  $scopes
     * @param string $separator
     *
     * @return string
     */
    public static function getScopes(array $scopes, string $separator = self::COMMA): string
    {
        if (empty($scopes)) {

            return '';
        }

        $scope = implode($separator, $scopes);

        return sprintf('&scope=%s', $scope);
    }

}