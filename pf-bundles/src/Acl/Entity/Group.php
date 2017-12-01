<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Acl\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Hanaboso\PipesFramework\Commons\Traits\Entity\IdTrait;
use Hanaboso\PipesFramework\User\Entity\UserInterface;

/**
 * Class Group
 *
 * @package Hanaboso\PipesFramework\Acl\Entity
 *
 * @ORM\Table(name="`group`")
 * @ORM\Entity(repositoryClass="Hanaboso\PipesFramework\Acl\Repository\Entity\GroupRepository")
 */
class Group extends EntityAbstract implements GroupInterface
{

    use IdTrait;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     */
    private $name;

    /**
     * @var RuleInterface[]|ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="Hanaboso\PipesFramework\Acl\Entity\Rule", mappedBy="group")
     */
    private $rules = [];

    /**
     * @var UserInterface[]|ArrayCollection
     *
     * @ORM\ManyToMany(targetEntity="Hanaboso\PipesFramework\User\Entity\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     */
    private $users = [];

    /**
     * @var UserInterface[]
     *
     * @ORM\ManyToMany(targetEntity="Hanaboso\PipesFramework\User\Entity\User")
     * @ORM\JoinColumn(name="group_id", referencedColumnName="id", nullable=true)
     * @ORM\JoinTable(name="group_owner")
     */
    protected $owner = [];

    /**
     * @var int
     *
     * @ORM\Column(type="integer")
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
     * @return RuleInterface[]|ArrayCollection
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
     * @return UserInterface[]|ArrayCollection
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
        return self::TYPE_ORM;
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