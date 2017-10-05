<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Commons\Utils;
/**
 * Created by PhpStorm.
 * User: radek.jirsa
 * Date: 5.10.17
 * Time: 15:41
 */

final class Base64
{

    /**
     * Base64 constructor.
     */
    private function __construct()
    {
    }

    /**
     * @param string $inputStr
     *
     * @return string
     */
    public static function base64UrlEncode(string $inputStr): string
    {
        return strtr(base64_encode($inputStr), '+/=', '-_,');
    }

    /**
     * @param string $inputStr
     *
     * @return string
     */
    public static function base64UrlDecode(string $inputStr): string
    {
        return base64_decode(strtr($inputStr, '-_,', '+/='));
    }

}