<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: Pavel Severyn
 * Date: 3.10.17
 * Time: 18:47
 */

namespace Hanaboso\PipesFramework\Commons\ProgressCounter;

/**
 * Trait ProgressCounterTrait
 *
 * @package Hanaboso\PipesFramework\Commons\ProgressCounter
 */
trait ProgressCounterTrait
{

    /**
     * @param string $processId
     * @param string $suffix
     *
     * @return string
     */
    public static function getKey(string $processId, string $suffix): string
    {
        return sprintf('%s:%s', $processId, $suffix);
    }

}
