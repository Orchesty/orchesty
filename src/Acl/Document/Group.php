<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Acl\Document;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\PersistentCollection;
use Hanaboso\PipesFramework\Commons\Traits\IdTrait;
use Hanaboso\PipesFramework\User\Document\User;

/**
 * Class Group
 *
 * @package Hanaboso\PipesFramework\Acl\Document
 *
 * @ODM\Document(repositoryClass="Hanaboso\PipesFramework\Acl\Repository\GroupRepository")
 */
class Group
{

    use IdTrait;

    /**
     * @var string
     *
     * @ODM\Field(type="string")
     */
    private $name;

    /**
     * @var Rule[]|null
     *
     * @ODM\ReferenceMany(targetDocument="Hanaboso\PipesFramework\Acl\Document\Rule")
     */
    private $rules;

    /**
     * @var User[]|null
     *
     * @ODM\ReferenceMany(targetDocument="Hanaboso\PipesFramework\User\Document\User")
     */
    private $users;

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return Group
     */
    public function setName(string $name): Group
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return Rule[]|PersistentCollection|ArrayCollection|null
     */
    public function getRules(): ?PersistentCollection
    {
        return $this->rules;
    }

    /**
     * @param Rule $rule
     *
     * @return Group
     */
    public function addRule(Rule $rule): Group
    {
        $this->rules[] = $rule;

        return $this;
    }

    /**
     * @return User[]|PersistentCollection|ArrayCollection|null
     */
    public function getUsers(): ?PersistentCollection
    {
        return $this->users;
    }

    /**
     * @param User $user
     *
     * @return Group
     */
    public function addUser(User $user): Group
    {
        $this->users[] = $user;

        return $this;
    }

}