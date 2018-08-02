<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Configurator\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Doctrine\ODM\MongoDB\Mapping\Annotations\Index;
use Hanaboso\CommonsBundle\Enum\StatusEnum;
use Hanaboso\CommonsBundle\Enum\TopologyStatusEnum;
use Hanaboso\CommonsBundle\Exception\EnumException;
use Hanaboso\CommonsBundle\Traits\Document\DeletedTrait;
use Hanaboso\CommonsBundle\Traits\Document\IdTrait;

/**
 * Class Topology
 *
 * @MongoDB\Document(
 *     repositoryClass="Hanaboso\PipesFramework\Configurator\Repository\TopologyRepository",
 *     indexes={
 *         @MongoDB\Index(keys={"name": "asc", "version": "asc"}, unique="true")
 *     }
 * )
 *
 * @package Hanaboso\PipesFramework\Configurator\Document
 */
class Topology
{

    use IdTrait;
    use DeletedTrait;

    /**
     * @var string
     *
     * @MongoDB\Field(type="string")
     */
    protected $name;

    /**
     * @var int
     *
     * @MongoDB\Field(type="int", options={"default":"1"})
     */
    protected $version;

    /**
     * @var string
     *
     * @MongoDB\Field(type="string")
     */
    protected $descr = '';

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
    protected $enabled = TRUE;

    /**
     * @var string
     *
     * @MongoDB\Field(type="string")
     */
    protected $bpmn = '';

    /**
     * @var string
     *
     * @MongoDB\Field(type="string")
     */
    protected $rawBpmn = '';

    /**
     * @var string|null
     *
     * @MongoDB\Field(type="string")
     * @Index()
     */
    protected $category;

    /**
     * @var string
     *
     * @MongoDB\Field(type="string")
     */
    protected $contentHash = '';

    /**
     * Topology constructor.
     */
    public function __construct()
    {
        $this->visibility = TopologyStatusEnum::DRAFT;
        $this->status     = StatusEnum::NEW;
        $this->version    = 1;
        $this->name       = '';
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
     * @return int
     */
    public function getVersion(): int
    {
        return $this->version;
    }

    /**
     * @param int $version
     *
     * @return Topology
     */
    public function setVersion(int $version): Topology
    {
        $this->version = $version;

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
     * @throws EnumException
     */
    public function setVisibility(string $visibility): Topology
    {
        $this->visibility = TopologyStatusEnum::isValid($visibility);

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
     * @throws EnumException
     */
    public function setStatus(string $status): Topology
    {
        $this->status = StatusEnum::isValid($status);

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
        return $this->bpmn ? json_decode($this->bpmn, TRUE) : [];
    }

    /**
     * @param array $bpmn
     *
     * @return Topology
     */
    public function setBpmn(array $bpmn): Topology
    {
        $this->bpmn = json_encode($bpmn);

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

    /**
     * @return string|null
     */
    public function getCategory(): ?string
    {
        return $this->category;
    }

    /**
     * @param string|null $category
     *
     * @return Topology
     */
    public function setCategory(?string $category): Topology
    {
        $this->category = $category;

        return $this;
    }

    /**
     * @param string $contentHash
     *
     * @return Topology
     */
    public function setContentHash(string $contentHash): Topology
    {
        $this->contentHash = $contentHash;

        return $this;
    }

    /**
     * @return string
     */
    public function getContentHash(): string
    {
        return $this->contentHash;
    }

}