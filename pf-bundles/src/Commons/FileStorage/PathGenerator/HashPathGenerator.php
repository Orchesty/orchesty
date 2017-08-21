<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: david.horacek
 * Date: 8/21/17
 * Time: 2:32 PM
 */

namespace Hanaboso\PipesFramework\Commons\FileStorage\PathGenerator;

/**
 * Class HashPathGenerator
 *
 * @package Hanaboso\PipesFramework\Commons\FileStorage\PathGenerator
 */
class HashPathGenerator implements PathGeneratorInterface
{

    /**
     * @var int
     */
    private $levels = 2;

    /**
     * @var int
     */
    private $segment = 2;

    /**
     * @param string|null $filename
     *
     * @return string
     */
    public function generate(?string $filename): string
    {
        $res = '';
        if (!$filename) {
            $filename = uniqid();

            $chunks = str_split($filename, $this->segment);
            for ($i = 0; $i < $this->levels; $i++) {
                $res .= array_shift($chunks) . DIRECTORY_SEPARATOR;
            }
        }

        return $res . $filename;
    }

}