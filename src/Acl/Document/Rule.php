<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Acl\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Hanaboso\PipesFramework\Commons\Traits\IdTrait;

/**
 * Class Rule
 *
 * @package Hanaboso\PipesFramework\Acl\Document
 *
 * @ODM\Document(repositoryClass="Hanaboso\PipesFramework\Acl\Repository\RuleRepository")
 */
class Rule
{

    use IdTrait;

    /**
     * @var string
     *
     * @ODM\Field(type="string")
     */
    private $resource;

    /**
     * @var Group
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
     * @return Rule
     */
    public function setResource(string $resource): Rule
    {
        $this->resource = $resource;

        return $this;
    }

    /**
     * @return Group
     */
    public function getGroup(): Group
    {
        return $this->group;
    }

    /**
     * @param Group $group
     *
     * @return Rule
     */
    public function setGroup(Group $group): Rule
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
     * @return Rule
     */
    public function setActionMask(int $actionMask): Rule
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
     * @return Rule
     */
    public function setPropertyMask(int $propertyMask): Rule
    {
        $this->propertyMask = $propertyMask;

        return $this;
    }

}