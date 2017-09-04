<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Commons\Topology\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Hanaboso\PipesFramework\Commons\Enum\TopologyStatusEnum;
use Hanaboso\PipesFramework\Commons\Exception\TopologyException;
use Hanaboso\PipesFramework\Commons\Traits\Document\IdTrait;

/**
 * Class Topology
 *
 * @MongoDB\Document(repositoryClass="Hanaboso\PipesFramework\Commons\Topology\TopologyRepository")
 *
 * @package Hanaboso\PipesFramework\Commons\Topology\Document
 */
class Topology
{

    use IdTrait;

    /**
     * @var string
     *
     * @MongoDB\Field(type="string")
     */
    protected $name;

    /**
     * @var string
     *
     * @MongoDB\Field(type="string")
     */
    protected $descr;

    /**
     * @var string
     *
     * @MongoDB\Field(type="string")
     */
    protected $status;

    /**
     * @var bool
     *
     * @MongoDB\Field(type="boolean", options={"default":"1"})
     */
    protected $enabled;

    /**
     * @var string
     *
     * @MongoDB\Field(type="string")
     */
    protected $bpmn;

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
     * @return Topology
     */
    public function setName(string $name): Topology
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getDescr(): string
    {
        return $this->descr;
    }

    /**
     * @param string $descr
     *
     * @return Topology
     */
    public function setDescr(string $descr): Topology
    {
        $this->descr = $descr;

        return $this;
    }

    /**
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * @param string $status
     *
     * @return Topology
     * @throws TopologyException
     */
    public function setStatus(string $status): Topology
    {
        if (TopologyStatusEnum::isValid($status)) {
            $this->status = $status;
        } else {
            throw new TopologyException(
                sprintf('Invalid topology status "%s"', $status),
                TopologyException::INVALID_TOPOLOGY_TYPE
            );
        }

        return $this;
    }

    /**
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * @param bool $enabled
     *
     * @return Topology
     */
    public function setEnabled(bool $enabled): Topology
    {
        $this->enabled = $enabled;

        return $this;
    }

    /**
     * @return string
     */
    public function getBpmn(): string
    {
        return $this->bpmn;
    }

    /**
     * @param string $bpmn
     *
     * @return Topology
     */
    public function setBpmn(string $bpmn): Topology
    {
        $this->bpmn = $bpmn;

        return $this;
    }

}