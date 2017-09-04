<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\User\Entity;

use Doctrine\ORM\Mapping as ORM;
use Hanaboso\PipesFramework\User\Enum\UserTypeEnum;

/**
 * Class TmpUser
 *
 * @package Hanaboso\PipesFramework\User\Entity
 *
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="Hanaboso\PipesFramework\User\Repository\Entity\TmpUserRepository")
 */
class TmpUser extends UserAbstract implements TmpUserInterface
{

    /**
     * @return string
     */
    public function getType(): string
    {
        return UserTypeEnum::TMP_USER;
    }

    /**
     * Needed by symfony's UserInterface.
     *
     * @return string
     */
    public function getPassword(): string
    {
        return '';
    }

    /**
     * @param string $pwd
     *
     * @return UserInterface
     */
    public function setPassword(string $pwd): UserInterface
    {
        return $this;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [];
    }

}