<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Commons\Node\Embed;

use Hanaboso\PipesFramework\Commons\Node\Document\Node;

/**
 * Class EmbedNode
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
     *
     * @return string
     */
    protected function setId($id)
    {
        return $this->id = $id;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return EmbedNode
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @param Node $node
     *
     * @return self
     */
    public static function from(Node $node)
    {
        $e = new self();
        $e->setId($node->getId());
        $e->setName($node->getName());

        return $e;
    }

}