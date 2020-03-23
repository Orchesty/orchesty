<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\Database\Document\Embed;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Hanaboso\PipesPhpSdk\Database\Document\Node;

/**
 * Class EmbedNode
 *
 * @package Hanaboso\PipesPhpSdk\Database\Document\Embed
 *
 * @ODM\EmbeddedDocument
 */
class EmbedNode
{

    /**
     * @var string
     *
     * @ODM\Field(type="string")
     */
    protected string $id;

    /**
     * @var string
     *
     * @ODM\Field(type="string")
     */
    protected string $name;

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
     *
     * @return EmbedNode
     */
    public function setName($name): EmbedNode
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @param string $id
     */
    protected function setId($id): void
    {
        $this->id = $id;
    }

}
