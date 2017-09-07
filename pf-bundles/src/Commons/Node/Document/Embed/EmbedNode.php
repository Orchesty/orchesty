<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Commons\Node\Document\Embed;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Hanaboso\PipesFramework\Commons\Node\Document\Node;

/**
 * Class EmbedNode
 *
 * @MongoDB\EmbeddedDocument
 *
 * @package Hanaboso\PipesFramework\Commons\Node\Embed
 */
class EmbedNode
{

    /**
     * @var string
     * @MongoDB\Field(type="string")
     */
    protected $id;

    /**
     * @var string
     * @MongoDB\Field(type="string")
     */
    protected $name;

    /**
     * @param string $id
     */
    protected function setId($id): void
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
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
     */
    public function setName($name): void
    {
        $this->name = $name;
    }

    /**
     * @param Node $node
     *
     * @return self
     */
    public static function from(Node $node): EmbedNode
    {
        $e = new self();
        $e->setId($node->getId());
        $e->setName($node->getName());

        return $e;
    }

}