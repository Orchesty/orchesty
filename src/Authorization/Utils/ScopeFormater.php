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
final class ScopeFormater
{

    /**
     * ScopeFormater constructor.
     */
    private function __construct()
    {
    }

    /**
     * @param array $scopes
     *
     * @return string
     */
    public static function getScopes(array $scopes): string
    {
        if (empty($scopes)) {

            return '';
        }

        $scope = implode(',', $scopes);

        return sprintf('&scope=%s', $scope);
    }

}