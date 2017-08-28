<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\CustomNode;

/**
 * Created by PhpStorm.
 * User: david.horacek
 * Date: 8/28/17
 * Time: 8:03 AM
 */
interface CustomNodeInterface
{

    /**
     * @param array $data
     *
     * @return string[]
     */
    public function process(array $data): array;

}