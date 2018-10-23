<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: david.horacek
 * Date: 8/25/17
 * Time: 2:34 PM
 */

namespace Hanaboso\PipesFramework\Joiner\Impl;

use Hanaboso\PipesFramework\Joiner\JoinerAbstract;

/**
 * Class NullJoiner
 *
 * @package Hanaboso\PipesFramework\Joiner\Joiner
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