<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Acl\Document;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\PersistentCollection;
use Hanaboso\PipesFramework\Commons\Traits\IdTrait;
use Hanaboso\PipesFramework\User\Document\UserInterface;

/**
 * Class Group
 *
 * @package Hanaboso\PipesFramework\Acl\Document
 *
 * @ODM\Document(repositoryClass="Hanaboso\PipesFramework\Acl\Repository\GroupRepository")
 */
class Group extends DocumentAbstract
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
     * @var UserInterface[]|null
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
    public function getRules()
    {
        return $this->rules;
    }

    /**
     * @param array $rules
     *
     * @return Group
     */
    public function setRules(array $rules): Group
    {
        $this->rules = $rules;

        return $this;
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
     * @return UserInterface[]|PersistentCollection|ArrayCollection|null
     */
    public function getUsers()
    {
        return $this->users;
    }

    /**
     * @param UserInterface[] $users
     *
     * @return Group
     */
    public function setUsers($users): Group
    {
        $this->users = $users;

        return $this;
    }

    /**
     * @param UserInterface $user
     *
     * @return Group
     */
    public function addUser(UserInterface $user): Group
    {
        $this->users[] = $user;

        return $this;
    }

}