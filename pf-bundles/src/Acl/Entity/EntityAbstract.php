<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Acl\Entity;

use Doctrine\ORM\Mapping as ORM;
use Hanaboso\PipesFramework\User\Entity\UserInterface;

/**
 * Class EntityAbstract
 *
 * @package Hanaboso\PipesFramework\Acl\Entity
 */
abstract class EntityAbstract
{

    /**
     * @var UserInterface|null
     *
     * @ORM\OneToMany(targetEntity="User")
     */
    protected $owner;

    /**
     * EntityAbstract constructor.
     *
     * @param UserInterface|null $owner
     */
    function __construct(?UserInterface $owner)
    {
        $this->owner = $owner;
    }

    /**
     * @return UserInterface|null
     */
    public function getOwner(): ?UserInterface
    {
        return $this->owner;
    }

    /**
     * @param UserInterface|null $owner
     *
     * @return EntityAbstract
     */
    public function setOwner(?UserInterface $owner): ?EntityAbstract
    {
        $this->owner = $owner;

        return $this;
    }

}