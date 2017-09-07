<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Acl\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Hanaboso\PipesFramework\Acl\Annotation\OwnerAnnotation as OWNER;
use Hanaboso\PipesFramework\User\Entity\UserInterface;

/**
 * Class DocumentAbstract
 *
 * @package Hanaboso\PipesFramework\Acl\Document
 */
abstract class DocumentAbstract
{

    /**
     * @var UserInterface|null
     *
     * @ODM\ReferenceOne(targetDocument="Hanaboso\PipesFramework\User\Document\User")
     * @OWNER()
     */
    protected $owner;

    /**
     * DocumentAbstract constructor.
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
     * @return DocumentAbstract
     */
    public function setOwner(?UserInterface $owner): ?DocumentAbstract
    {
        $this->owner = $owner;

        return $this;
    }

}