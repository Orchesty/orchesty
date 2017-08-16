<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Commons\Cryptography;

/**
 * Interface CryptInterface
 *
 * @package Hanaboso\PipesFramework\Commons\Cryptography
 */
interface CryptInterface
{

    /**
     * @param mixed $data
     *
     * @return string
     */
    public function encrypt($data): string;

    /**
     * @param string $data
     *
     * @return mixed
     */
    public function decrypt(string $data);

}