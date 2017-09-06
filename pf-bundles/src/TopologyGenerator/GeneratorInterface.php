<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: venca
 * Date: 9/5/17
 * Time: 3:07 PM
 */

namespace Hanaboso\PipesFramework\TopologyGenerator;

/**
 * Interface GeneratorInterface
 *
 * @package Hanaboso\PipesFramework\TopologyGenerator
 */
interface GeneratorInterface
{

    /**
     * @param string $targetPath
     */
    public function generate(string $targetPath): void;

}