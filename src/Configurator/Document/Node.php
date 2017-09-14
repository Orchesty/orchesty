<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Configurator\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Doctrine\ODM\MongoDB\Mapping\Annotations\Index;
use Doctrine\ODM\MongoDB\PersistentCollection;
use Hanaboso\PipesFramework\Commons\Enum\HandlerEnum;
use Hanaboso\PipesFramework\Commons\Enum\TypeEnum;
use Hanaboso\PipesFramework\Commons\Traits\Document\IdTrait;
use Hanaboso\PipesFramework\Configurator\Document\Embed\EmbedNode;
use Hanaboso\PipesFramework\Configurator\Exception\NodeException;
use Nette\Utils\Strings;

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
     * @var EmbedNode[]|PersistentCollection
     *
     * @MongoDB\EmbedMany(targetDocument="Hanaboso\PipesFramework\Commons\Node\Document\Embed\EmbedNode")
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
        $this->name = Strings::webalize($name);

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

}