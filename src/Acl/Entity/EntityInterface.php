<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Acl\Entity;

use Hanaboso\PipesFramework\Acl\Document\DocumentAbstract;
use Hanaboso\UserBundle\Entity\UserInterface;

/**
 * Interface EntityInterface
 *
 * @package Hanaboso\PipesFramework\Acl\Entity
 */
interface EntityInterface
{

    /**
     * @return UserInterface|null
     */
    public function getOwner(): ?UserInterface;

    /**
     * @param UserInterface|null $owner
     *
     * @return EntityAbstract|DocumentAbstract|null
     */
    public function setOwner(?UserInterface $owner);

}