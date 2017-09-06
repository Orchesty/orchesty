<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: venca
 * Date: 9/6/17
 * Time: 10:56 AM
 */

namespace Hanaboso\PipesFramework\TopologyGenerator;

use Nette\Utils\Strings;

/**
 * Class GeneratorUtils
 *
 * @package Hanaboso\PipesFramework\TopologyGenerator
 */
class GeneratorUtils
{

    /**
     * @param string $id
     * @param string $name
     *
     * @return string
     */
    public static function normalizeName(string $id, string $name): string
    {
        return sprintf('%s-%s', $id, Strings::webalize($name));
    }

}