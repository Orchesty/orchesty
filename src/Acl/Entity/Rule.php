<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Acl\Entity;

use Doctrine\ORM\Mapping as ORM;
use Hanaboso\CommonsBundle\Traits\Entity\IdTrait;

/**
 * Class Rule
 *
 * @package Hanaboso\PipesFramework\Acl\Entity
 *
 * @ORM\Table(name="rule")
 * @ORM\Entity(repositoryClass="Hanaboso\PipesFramework\Acl\Repository\Entity\RuleRepository")
 */
class Rule implements RuleInterface
{

    use IdTrait;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     */
    private $resource;

    /**
     * @var GroupInterface
     *
     * @ORM\ManyToOne(targetEntity="Hanaboso\PipesFramework\Acl\Entity\Group", inversedBy="rules")
     */
    private $group;

    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     */
    private $actionMask;

    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     */
    private $propertyMask;

    /**
     * @return string
     */
    public function getResource(): string
    {
        return $this->resource;
    }

    /**
     * @param string $resource
     *
     * @return RuleInterface
     */
    public function setResource(string $resource): RuleInterface
    {
        $this->resource = $resource;

        return $this;
    }

    /**
     * @return GroupInterface
     */
    public function getGroup(): GroupInterface
    {
        return $this->group;
    }

    /**
     * @param GroupInterface $group
     *
     * @return RuleInterface
     */
    public function setGroup(GroupInterface $group): RuleInterface
    {
        $this->group = $group;

        return $this;
    }

    /**
     * @return int
     */
    public function getActionMask(): int
    {
        return $this->actionMask;
    }

    /**
     * @param int $actionMask
     *
     * @return RuleInterface
     */
    public function setActionMask(int $actionMask): RuleInterface
    {
        $this->actionMask = $actionMask;

        return $this;
    }

    /**
     * @return int
     */
    public function getPropertyMask(): int
    {
        return $this->propertyMask;
    }

    /**
     * @param int $propertyMask
     *
     * @return RuleInterface
     */
    public function setPropertyMask(int $propertyMask): RuleInterface
    {
        $this->propertyMask = $propertyMask;

        return $this;
    }

}