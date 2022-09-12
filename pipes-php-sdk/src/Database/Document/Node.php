<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\Database\Document;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
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
 * @package Hanaboso\PipesPhpSdk\Database\Document
 *
 * @ODM\Document(repositoryClass="Hanaboso\PipesPhpSdk\Database\Repository\NodeRepository")
 */
class Node
{

    use IdTrait;
    use DeletedTrait;

    /**
     * @var string|null
     *
     * @ODM\Field(type="string")
     */
    protected ?string $application;

    /**
     * @var string
     *
     * @ODM\Field(type="string")
     */
    protected string $schemaId;

    /**
     * @var string
     *
     * @ODM\Field(type="string")
     */
    protected string $name;

    /**
     * @var string
     *
     * @ODM\Field(type="string")
     * @ODM\Index()
     */
    protected string $topology;

    /**
     * @var mixed[]|Collection<string, EmbedNode>
     *
     * @ODM\EmbedMany(targetDocument="Hanaboso\PipesPhpSdk\Database\Document\Embed\EmbedNode")
     */
    protected $next;

    /**
     * @var string
     *
     * @ODM\Field(type="string")
     */
    protected string $type = TypeEnum::CUSTOM;

    /**
     * @var string
     *
     * @ODM\Field(type="string")
     */
    protected string $handler = HandlerEnum::EVENT;

    /**
     * @var bool
     *
     * @ODM\Field(type="boolean", options={"default":"1"})
     */
    protected bool $enabled = TRUE;

    /**
     * @var string|null
     *
     * @ODM\Field(type="string")
     */
    protected ?string $cron = NULL;

    /**
     * @var string|null
     *
     * @ODM\Field(type="string")
     */
    protected ?string $cronParams = NULL;

    /**
     * @var string|null
     *
     * @ODM\Field(type="string")
     */
    protected ?string $systemConfigs = NULL;

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
        $this->application   = '';
    }

    /**
     * @return string|null
     */
    public function getApplication(): string |null
    {
        return $this->application;
    }

    /**
     * @param string $application
     *
     * @return Node
     */
    public function setApplication(string $application): Node
    {
        $this->application = $application;

        return $this;
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
     */
    public function setType(string $type): Node
    {
        try {
            TypeEnum::isValid($type);
            $this->type = $type;
        } catch (EnumException $e) {
            $e;

            throw new NodeException(
                sprintf('Invalid node type "%s"', $type),
                NodeException::INVALID_TYPE,
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
     */
    public function setHandler(string $handler): Node
    {
        try {
            HandlerEnum::isValid($handler);
            $this->handler = $handler;
        } catch (EnumException $e) {
            $e;

            throw new NodeException(
                sprintf('Invalid node handler value "%s"', $handler),
                NodeException::INVALID_HANDLER,
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

    /**
     * @return mixed[]
     */
    public function toArray(): array
    {
        return [
            '_id'         => $this->getId(),
            'name'        => $this->getName(),
            'topology_id' => $this->getTopology(),
            'next'        => $this->getNext(),
            'type'        => $this->getType(),
            'handler'     => $this->getHandler(),
            'enabled'     => $this->isEnabled(),
            'schema_id'   => $this->getSchemaId(),
        ];
    }

}
