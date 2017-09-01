<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\User\Entity;

/**
 * Class TmpUser
 *
 * @package Hanaboso\PipesFramework\User\Entity
 */
interface TmpUserInterface extends UserInterface
{

    /**
     * @return string
     */
    public function getType(): string;

}