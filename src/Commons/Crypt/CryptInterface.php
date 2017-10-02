<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Commons\Crypt;

/**
 * Interface CryptInterface
 *
 * @package Hanaboso\PipesFramework\Commons\Crypt
 */
interface CryptInterface
{

    /**
     * @param mixed $data
     *
     * @return string
     */
    public static function encrypt($data): string;

    /**
     * @param string $data
     *
     * @return mixed
     */
    public static function decrypt(string $data);

}