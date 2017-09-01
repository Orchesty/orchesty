<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Acl\Entity;

use Hanaboso\PipesFramework\User\Entity\UserInterface;

/**
 * Class EntityAbstract
 *
 * @package Hanaboso\PipesFramework\Acl\Entity
 */
abstract class EntityAbstract
{

    /**
     * @var UserInterface[]|null
     */
    protected $owner;

    /**
     * EntityAbstract constructor.
     *
     * @param UserInterface|null $owner
     */
    function __construct(?UserInterface $owner)
    {
        $this->owner[0] = $owner;
    }

    /**
     * @return UserInterface|null
     */
    public function getOwner(): ?UserInterface
    {
        return $this->owner[0];
    }

    /**
     * @param UserInterface|null $owner
     *
     * @return EntityAbstract
     */
    public function setOwner(?UserInterface $owner): ?EntityAbstract
    {
        $this->owner[0] = $owner;

        return $this;
    }

}