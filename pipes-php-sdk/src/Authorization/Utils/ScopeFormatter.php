<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\Authorization\Utils;

/**
 * Class ScopeFormatter
 *
 * @package Hanaboso\PipesPhpSdk\Authorization\Utils
 */
final class ScopeFormatter
{

    public const COMMA = ',';
    public const SPACE = ' ';

    /**
     * @param string[] $scopes
     * @param string   $separator
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
