<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Acl\Document;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\PersistentCollection;
use Hanaboso\PipesFramework\Acl\Entity\GroupInterface;
use Hanaboso\PipesFramework\Acl\Entity\RuleInterface;
use Hanaboso\PipesFramework\Commons\Traits\Document\IdTrait;
use Hanaboso\PipesFramework\User\Entity\UserInterface;

/**
 * Class Group
 *
 * @package Hanaboso\PipesFramework\Acl\Document
 *
 * @ODM\Document(repositoryClass="Hanaboso\PipesFramework\Acl\Repository\Document\GroupRepository")
 */
class Group extends DocumentAbstract implements GroupInterface
{

    use IdTrait;

    /**
     * @var string
     *
     * @ODM\Field(type="string")
     */
    private $name;

    /**
     * @var RuleInterface[]|ArrayCollection
     *
     * @ODM\ReferenceMany(targetDocument="Hanaboso\PipesFramework\Acl\Document\Rule")
     */
    private $rules;

    /**
     * @var UserInterface[]|ArrayCollection
     *
     * @ODM\ReferenceMany(targetDocument="Hanaboso\PipesFramework\User\Document\User")
     */
    private $users;

    /**
     * @var int
     *
     * @ODM\Field(type="int")
     */
    protected $level = 999;

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
     * @return GroupInterface
     */
    public function setName(string $name): GroupInterface
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return RuleInterface[]|PersistentCollection|ArrayCollection|null
     */
    public function getRules()
    {
        return $this->rules;
    }

    /**
     * @param array $rules
     *
     * @return GroupInterface
     */
    public function setRules(array $rules): GroupInterface
    {
        $this->rules = $rules;

        return $this;
    }

    /**
     * @param RuleInterface $rule
     *
     * @return GroupInterface
     */
    public function addRule(RuleInterface $rule): GroupInterface
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
     * @return GroupInterface
     */
    public function setUsers($users): GroupInterface
    {
        $this->users = $users;

        return $this;
    }

    /**
     * @param UserInterface $user
     *
     * @return GroupInterface
     */
    public function addUser(UserInterface $user): GroupInterface
    {
        $this->users[] = $user;

        return $this;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return self::TYPE_ODM;
    }

    /**
     * @return int
     */
    public function getLevel(): int
    {
        return $this->level;
    }

    /**
     * @param int $level
     *
     * @return GroupInterface
     */
    public function setLevel(int $level): GroupInterface
    {
        $this->level = $level;

        return $this;
    }

}