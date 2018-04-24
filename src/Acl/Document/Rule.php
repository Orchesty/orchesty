<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Acl\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Hanaboso\CommonsBundle\Traits\Document\IdTrait;
use Hanaboso\PipesFramework\Acl\Entity\GroupInterface;
use Hanaboso\PipesFramework\Acl\Entity\RuleInterface;

/**
 * Class Rule
 *
 * @package Hanaboso\PipesFramework\Acl\Document
 *
 * @ODM\Document(repositoryClass="Hanaboso\PipesFramework\Acl\Repository\Document\RuleRepository")
 */
class Rule implements RuleInterface
{

    use IdTrait;

    /**
     * @var string
     *
     * @ODM\Field(type="string")
     */
    private $resource;

    /**
     * @var GroupInterface
     *
     * @ODM\ReferenceOne(targetDocument="Hanaboso\PipesFramework\Acl\Document\Group")
     */
    private $group;

    /**
     * @var int
     *
     * @ODM\Field(type="int")
     */
    private $actionMask;

    /**
     * @var int
     *
     * @ODM\Field(type="int")
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