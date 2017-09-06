<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Commons\Topology\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Hanaboso\PipesFramework\Commons\Enum\StatusEnum;
use Hanaboso\PipesFramework\Commons\Enum\TopologyStatusEnum;
use Hanaboso\PipesFramework\Commons\Exception\TopologyException;
use Hanaboso\PipesFramework\Commons\Traits\Document\IdTrait;
use Nette\Utils\Json;

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
     * @MongoDB\Field(type="string", options={"default":"draft"})
     */
    protected $visibility = TopologyStatusEnum::DRAFT;

    /**
     * @var string
     *
     * @MongoDB\Field(type="string")
     */
    protected $status = StatusEnum::NEW;

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
     * @var string
     *
     * @MongoDB\Field(type="string")
     */
    protected $rawBpmn;

    /**
     * Topology constructor.
     */
    public function __construct()
    {
        $this->visibility = TopologyStatusEnum::DRAFT;
        $this->status     = StatusEnum::NEW;
    }

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
    public function getVisibility(): string
    {
        return $this->visibility;
    }

    /**
     * @param string $visibility
     *
     * @return Topology
     * @throws TopologyException
     */
    public function setVisibility(string $visibility): Topology
    {
        if (TopologyStatusEnum::isValid($visibility)) {
            $this->visibility = $visibility;
        } else {
            throw new TopologyException(
                sprintf('Invalid topology visibility "%s"', $visibility),
                TopologyException::INVALID_TOPOLOGY_TYPE
            );
        }

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
     */
    public function setStatus(string $status): Topology
    {
        $this->status = (new StatusEnum($status))->getValue();

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
     * @return array
     */
    public function getBpmn(): array
    {
        return Json::decode($this->bpmn, Json::FORCE_ARRAY);
    }

    /**
     * @param array $bpmn
     *
     * @return Topology
     */
    public function setBpmn(array $bpmn): Topology
    {
        $this->bpmn = Json::encode($bpmn);

        return $this;
    }

    /**
     * @return string
     */
    public function getRawBpmn(): string
    {
        return $this->rawBpmn;
    }

    /**
     * @param string $rawBpmn
     *
     * @return Topology
     */
    public function setRawBpmn(string $rawBpmn): Topology
    {
        $this->rawBpmn = $rawBpmn;

        return $this;
    }

}