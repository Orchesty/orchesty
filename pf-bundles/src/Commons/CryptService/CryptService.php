<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: david.horacek
 * Date: 8/15/17
 * Time: 2:38 PM
 */

namespace Hanaboso\PipesFramework\Commons\CryptService;

/**
 * Class CryptService
 *
 * @package Hanaboso\PipesFramework\Commons\CryptService
 */
class CryptService
{

    /**
     * @param array $data
     *
     * @return string
     */
    public function encrypt(array $data): string
    {
        return base64_encode(json_encode($data));
    }

    /**
     * @param string $hash
     *
     * @return string[]
     */
    public function decrypt(string $hash): array
    {
        $res = json_decode(base64_decode($hash), TRUE);
        $res = $res ?? [];

        return $res;
    }

}