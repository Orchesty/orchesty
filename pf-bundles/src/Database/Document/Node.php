<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Database\Document;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Hanaboso\CommonsBundle\Database\Traits\Document\DeletedTrait;
use Hanaboso\CommonsBundle\Database\Traits\Document\IdTrait;
use Hanaboso\CommonsBundle\Enum\HandlerEnum;
use Hanaboso\CommonsBundle\Enum\TypeEnum;
use Hanaboso\CommonsBundle\Exception\NodeException;
use Hanaboso\PipesFramework\Database\Document\Dto\SystemConfigDto;
use Hanaboso\PipesFramework\Database\Document\Embed\EmbedNode;

/**
 * Class Node
 *
 * @package Hanaboso\PipesFramework\Database\Document
 *
 * @ODM\Document(repositoryClass="Hanaboso\PipesFramework\Database\Repository\NodeRepository")
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
     * @ODM\EmbedMany(targetDocument="Hanaboso\PipesFramework\Database\Document\Embed\EmbedNode")
     */
    protected $next;

    /**
     * @var string
     *
     * @ODM\Field(type="string")
     */
    protected string $type = TypeEnum::CUSTOM->value;

    /**
     * @var string
     *
     * @ODM\Field(type="string")
     */
    protected string $handler = HandlerEnum::EVENT->value;

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
        $this->handler       = HandlerEnum::EVENT->value;
        $this->name          = '';
        $this->schemaId      = '';
        $this->systemConfigs = NULL;
        $this->type          = TypeEnum::CUSTOM->value;
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
        if (TypeEnum::tryFrom($type)) {
            $this->type = $type;

            return $this;
        }

        throw new NodeException(
            sprintf('Invalid node type "%s"', $type),
            NodeException::INVALID_TYPE,
        );
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
        if (HandlerEnum::tryFrom($handler)) {
            $this->handler = $handler;

            return $this;
        }

        throw new NodeException(
            sprintf('Invalid node handler value "%s"', $handler),
            NodeException::INVALID_HANDLER,
        );
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
            'cron_time'   => $this->getCron(),
            'cron_params' => $this->getCronParams(),
        ];
    }

}
