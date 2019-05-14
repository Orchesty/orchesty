<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Application\Base;

/**
 * Class ApplicationAbstract
 *
 * @package Hanaboso\PipesFramework\Application\Base
 */
abstract class ApplicationAbstract implements ApplicationInterface
{

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'name'        => $this->getName(),
            'type'        => $this->getType(),
            'key'         => $this->getKey(),
            'description' => $this->getDescription(),
        ];
    }

}