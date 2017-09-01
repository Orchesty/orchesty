<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Commons\Document;

use Hanaboso\PipesFramework\Commons\Traits\IdTrait;
use MongoDB\Collection;

/**
 * Class Topology
 *
 * @MongoDB\Document(repositoryClass="Hanaboso\PipesFramework\Commons\Document\Repository\TopologyRepository")
 *
 * @package Hanaboso\PipesFramework\Commons\Document
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
     * @var bool
     *
     * @MongoDB\Field(type="boolean", options={"default":"1"})
     */
    protected $status;

    /**
     * @var string
     *
     * @MongoDB\Field(type="string")
     */
    protected $bpmn;

    /**
     * @var Node[]|Collection
     *
     * @MongoDB\ReferenceMany(targetDocument="Node", mappedBy="Topology")
     */
    protected $nodes;

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
     * @return bool
     */
    public function isStatus(): bool
    {
        return $this->status;
    }

    /**
     * @param bool $status
     *
     * @return Topology
     */
    public function setStatus(bool $status): Topology
    {
        $this->status = $status;

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

    /**
     * @return Node[]|Collection
     */
    public function getNodes()
    {
        return $this->nodes;
    }

    /**
     * @param Node[]|Collection $nodes
     */
    public function setNodes($nodes)
    {
        $this->nodes = $nodes;
    }

}