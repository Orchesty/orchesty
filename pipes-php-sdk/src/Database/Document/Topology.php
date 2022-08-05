<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\Database\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Hanaboso\CommonsBundle\Database\Traits\Document\DeletedTrait;
use Hanaboso\CommonsBundle\Database\Traits\Document\IdTrait;
use Hanaboso\CommonsBundle\Enum\StatusEnum;
use Hanaboso\CommonsBundle\Enum\TopologyStatusEnum;
use Hanaboso\Utils\Exception\EnumException;
use Hanaboso\Utils\String\Json;

/**
 * Class Topology
 *
 * @package Hanaboso\PipesPhpSdk\Database\Document
 *
 * @ODM\Document(
 *     repositoryClass="Hanaboso\PipesPhpSdk\Database\Repository\TopologyRepository",
 *     indexes={
 *         @ODM\Index(keys={"name": "asc", "version": "asc"}, unique=true)
 *     }
 * )
 */
class Topology
{

    use IdTrait;
    use DeletedTrait;

    /**
     * @var string
     *
     * @ODM\Field(type="string")
     */
    protected string $name = '';

    /**
     * @var int
     *
     * @ODM\Field(type="int", options={"default":"1"})
     */
    protected int $version = 1;

    /**
     * @var string
     *
     * @ODM\Field(type="string")
     */
    protected string $descr = '';

    /**
     * @var string
     *
     * @ODM\Field(type="string", options={"default":"draft"})
     */
    protected string $visibility = TopologyStatusEnum::DRAFT;

    /**
     * @var string
     *
     * @ODM\Field(type="string")
     */
    protected string $status = StatusEnum::NEW;

    /**
     * @var bool
     *
     * @ODM\Field(type="boolean", options={"default":"0"})
     */
    protected bool $enabled = FALSE;

    /**
     * @var string
     *
     * @ODM\Field(type="string")
     */
    protected string $bpmn = '';

    /**
     * @var string
     *
     * @ODM\Field(type="string")
     */
    protected string $rawBpmn = '';

    /**
     * @var string|null
     *
     * @ODM\Field(type="string")
     * @ODM\Index()
     */
    protected ?string $category = NULL;

    /**
     * @var string
     *
     * @ODM\Field(type="string")
     */
    protected string $contentHash = '';

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
     * @return mixed[]
     */
    public function getBpmn(): array
    {
        return $this->bpmn ? Json::decode($this->bpmn) : [];
    }

    /**
     * @param mixed[] $bpmn
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
