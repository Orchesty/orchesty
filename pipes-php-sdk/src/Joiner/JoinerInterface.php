<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\Joiner;

/**
 * Interface JoinerInterface
 *
 * @package Hanaboso\PipesPhpSdk\Joiner
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
