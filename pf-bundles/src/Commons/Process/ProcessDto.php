<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: david.horacek
 * Date: 8/18/17
 * Time: 3:24 PM
 */

namespace Hanaboso\PipesFramework\Commons\Process;

/**
 * Class ProcessDto
 *
 * @package Hanaboso\PipesFramework\Commons\Process
 */
class ProcessDto
{

    /**
     * @var string[]
     */
    private $data;

    /**
     * @return string[]
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * @param string[] $data
     *
     * @return ProcessDto
     */
    public function setData(array $data): ProcessDto
    {
        $this->data = $data;

        return $this;
    }

}