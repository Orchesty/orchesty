<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\Database\Document;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Doctrine\ODM\MongoDB\Mapping\Annotations\Index;
use Hanaboso\CommonsBundle\Database\Traits\Document\DeletedTrait;
use Hanaboso\CommonsBundle\Database\Traits\Document\IdTrait;
use Hanaboso\CommonsBundle\Enum\HandlerEnum;
use Hanaboso\CommonsBundle\Enum\TypeEnum;
use Hanaboso\CommonsBundle\Exception\NodeException;
use Hanaboso\PipesPhpSdk\Database\Document\Dto\SystemConfigDto;
use Hanaboso\PipesPhpSdk\Database\Document\Embed\EmbedNode;
use Hanaboso\Utils\Exception\EnumException;

/**
 * Class Node
 *
 * @MongoDB\Document(repositoryClass="Hanaboso\PipesPhpSdk\Database\Repository\NodeRepository")
 *
 * @package Hanaboso\PipesPhpSdk\Database\Document
 */
class Node
{

    use IdTrait;
    use DeletedTrait;

    /**
     * @var string
     *
     * @MongoDB\Field(type="string")
     */
    protected $schemaId = '';

    /**
     * @var string
     *
     * @MongoDB\Field(type="string")
     */
    protected $name = '';

    /**
     * @var string
     *
     * @MongoDB\Field(type="string")
     * @Index()
     */
    protected $topology = '';

    /**
     * @var mixed[]|Collection<string, EmbedNode>
     *
     * @MongoDB\EmbedMany(targetDocument="Hanaboso\CommonsBundle\Database\Document\Embed\EmbedNode")
     */
    protected $next;

    /**
     * @var string
     *
     * @MongoDB\Field(type="string")
     */
    protected $type = TypeEnum::CUSTOM;

    /**
     * @var string
     *
     * @MongoDB\Field(type="string")
     */
    protected $handler = HandlerEnum::EVENT;

    /**
     * @var bool
     *
     * @MongoDB\Field(type="boolean", options={"default":"1"})
     */
    protected $enabled = TRUE;

    /**
     * @var string|null
     *
     * @MongoDB\Field(type="string")
     */
    protected $cron = NULL;

    /**
     * @var string|null
     *
     * @MongoDB\Field(type="string")
     */
    protected $cronParams = NULL;

    /**
     * @var string|null
     *
     * @MongoDB\Field(type="string")
     */
    protected $systemConfigs = NULL;

    /**
     * Node constructor.
     */
    public function __construct()
    {
        $this->next          = new ArrayCollection();
        $this->cron          = NULL;
        $this->cronParams    = NULL;
        $this->enabled       = TRUE;
        $this->handler       = HandlerEnum::EVENT;
        $this->name          = '';
        $this->schemaId      = '';
        $this->systemConfigs = NULL;
        $this->type          = TypeEnum::CUSTOM;
    }

    /**
     * @return string
     */
    public function getSchemaId(): string
    {
        return $this->schemaId;
    }

    /**
     * @param string $schemaId
     *
     * @return Node
     */
    public function setSchemaId(string $schemaId): Node
    {
        $this->schemaId = $schemaId;

        return $this;
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
     * @return Node
     */
    public function setName(string $name): Node
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getTopology(): string
    {
        return $this->topology;
    }

    /**
     * @param string $topology
     *
     * @return Node
     */
    public function setTopology(string $topology): Node
    {
        $this->topology = $topology;

        return $this;
    }

    /**
     * @return EmbedNode[]
     */
    public function getNext(): array
    {
        if (!is_array($this->next)) {
            $this->next = $this->next->toArray();
        }

        return $this->next;
    }

    /**
     * @param EmbedNode $next
     *
     * @return Node
     */
    public function addNext(EmbedNode $next): Node
    {
        $this->next[] = $next;

        return $this;
    }

    /**
     * @param EmbedNode[] $next
     *
     * @return Node
     */
    public function setNext(array $next): Node
    {
        $this->next = $next;

        return $this;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string $type
     *
     * @return Node
     * @throws NodeException
     * @throws EnumException
     */
    public function setType(string $type): Node
    {
        if (TypeEnum::isValid($type)) {
            $this->type = $type;
        } else {
            throw new NodeException(
                sprintf('Invalid node type "%s"', $type),
                NodeException::INVALID_TYPE
            );
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getHandler(): string
    {
        return $this->handler;
    }

    /**
     * @param string $handler
     *
     * @return Node
     * @throws NodeException
     * @throws EnumException
     */
    public function setHandler(string $handler): Node
    {
        if (HandlerEnum::isValid($handler)) {
            $this->handler = $handler;
        } else {
            throw new NodeException(
                sprintf('Invalid node handler value "%s"', $handler),
                NodeException::INVALID_HANDLER
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
     * @return Node
     */
    public function setEnabled(bool $enabled): Node
    {
        $this->enabled = $enabled;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getCron(): ?string
    {
        return $this->cron;
    }

    /**
     * @param string|null $cron
     *
     * @return Node
     */
    public function setCron(?string $cron): Node
    {
        $this->cron = $cron;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getCronParams(): ?string
    {
        return $this->cronParams;
    }

    /**
     * @param string|null $cronParams
     *
     * @return Node
     */
    public function setCronParams(?string $cronParams): Node
    {
        $this->cronParams = $cronParams;

        return $this;
    }

    /**
     * @param SystemConfigDto $dto
     *
     * @return Node
     */
    public function setSystemConfigs(SystemConfigDto $dto): Node
    {
        $this->systemConfigs = $dto->toString();

        return $this;
    }

    /**
     * @return SystemConfigDto|null
     */
    public function getSystemConfigs(): ?SystemConfigDto
    {
        if (!$this->systemConfigs) {
            return NULL;
        }

        return SystemConfigDto::fromString($this->systemConfigs);
    }

}
