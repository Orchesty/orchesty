<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Configurator\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Doctrine\ODM\MongoDB\Mapping\Annotations\Index;
use Doctrine\ODM\MongoDB\PersistentCollection;
use Hanaboso\CommonsBundle\Enum\HandlerEnum;
use Hanaboso\CommonsBundle\Enum\TypeEnum;
use Hanaboso\CommonsBundle\Traits\Document\DeletedTrait;
use Hanaboso\CommonsBundle\Traits\Document\IdTrait;
use Hanaboso\PipesFramework\Configurator\Document\Embed\EmbedNode;
use Hanaboso\PipesFramework\Configurator\Exception\NodeException;

/**
 * Class Node
 *
 * @MongoDB\Document(repositoryClass="Hanaboso\PipesFramework\Configurator\Repository\NodeRepository")
 *
 * @package Hanaboso\PipesFramework\Configurator\Document
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
    protected $schemaId;

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
     * @Index()
     */
    protected $topology;

    /**
     * @var EmbedNode[]
     *
     * @MongoDB\EmbedMany(targetDocument="Hanaboso\PipesFramework\Configurator\Document\Embed\EmbedNode")
     */
    protected $next = [];

    /**
     * @var string
     *
     * @MongoDB\Field(type="string")
     */
    protected $type;

    /**
     * @var string
     *
     * @MongoDB\Field(type="string")
     */
    protected $handler;

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
    protected $cron;

    /**
     * @var string|null
     *
     * @MongoDB\Field(type="string")
     */
    protected $cronParams;

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
     * @return EmbedNode[]|iterable|PersistentCollection
     */
    public function getNext(): iterable
    {
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
     * @param null|string $cron
     *
     * @return Node
     */
    public function setCron(?string $cron): Node
    {
        $this->cron = $cron;

        return $this;
    }

    /**
     * @return null|string
     */
    public function getCronParams(): ?string
    {
        return $this->cronParams;
    }

    /**
     * @param null|string $cronParams
     *
     * @return Node
     */
    public function setCronParams(?string $cronParams): Node
    {
        $this->cronParams = $cronParams;

        return $this;
    }

}