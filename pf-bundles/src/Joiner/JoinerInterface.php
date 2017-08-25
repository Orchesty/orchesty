<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: david.horacek
 * Date: 8/25/17
 * Time: 2:23 PM
 */

namespace Hanaboso\PipesFramework\Joiner;

/**
 * Interface JoinerInterface
 *
 * @package Hanaboso\PipesFramework\Joiner
 */
interface JoinerInterface
{

    /**
     * @param array $data
     */
    public function save(array $data): void;

    /**
     * @param int $count
     *
     * @return bool
     */
    public function isDataComplete(int $count): bool;

    /**
     * @return string[]
     */
    public function runCallback(): array;

    /**
     * @param array $data
     * @param int   $count
     *
     * @return string[]
     */
    public function process(array $data, int $count): array;

}