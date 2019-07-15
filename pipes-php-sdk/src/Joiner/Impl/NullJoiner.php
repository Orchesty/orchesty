<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\Joiner\Impl;

use Hanaboso\PipesPhpSdk\Joiner\JoinerAbstract;

/**
 * Class NullJoiner
 *
 * @package Hanaboso\PipesPhpSdk\Joiner\Impl
 */
class NullJoiner extends JoinerAbstract
{

    /**
     * @param array $data
     */
    public function save(array $data): void
    {
        $data;
    }

    /**
     * @param int $count
     *
     * @return bool
     */
    public function isDataComplete(int $count): bool
    {
        $count;

        return TRUE;
    }

    /**
     * @return string[]
     */
    public function runCallback(): array
    {
        return [];
    }

}
