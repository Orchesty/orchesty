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
    public function encrypt($data): string;

    /**
     * @param string $data
     *
     * @return mixed
     */
    public function decrypt(string $data);

}