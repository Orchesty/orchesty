<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Utils\Dto;

use Hanaboso\PipesFramework\Configurator\Exception\TopologyException;

/**
 * Class Schema
 *
 * @package Hanaboso\PipesFramework\Utils\Dto
 */
final class Schema
{

    /**
     * @var NodeSchemaDto[]
     */
    private array $nodes = [];

    /**
     * @var mixed[]
     */
    private array $sequences = [];

    /**
     * @var string
     */
    private string $startNode = '';

    /**
     * @return NodeSchemaDto[]
     */
    public function getNodes(): array
    {
        return $this->nodes;
    }

    /**
     * @param string        $id
     * @param NodeSchemaDto $dto
     *
     * @return Schema
     */
    public function addNode(string $id, NodeSchemaDto $dto): Schema
    {
        $this->nodes[$id] = $dto;

        return $this;
    }

    /**
     * @param string $source
     * @param string $target
     *
     * @return Schema
     */
    public function addSequence(string $source, string $target): Schema
    {
        $this->sequences[$source][] = $target;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getSequences()
    {
        return $this->sequences;
    }

    /**
     * @return string
     */
    public function getStartNode(): string
    {
        return $this->startNode;
    }

    /**
     * @param string $startNode
     *
     * @return Schema
     */
    public function setStartNode(string $startNode): Schema
    {
        $this->startNode = $startNode;

        return $this;
    }

    /**
     * Creates index used to
     *
     * @param bool $checkInfiniteLoop
     *
     * @return mixed[]
     * @throws TopologyException
     */
    public function buildIndex(bool $checkInfiniteLoop = TRUE): array
    {
        if (!empty($this->nodes)) {
            $this->checkStartNode();
        } else {
            return [];
        }

        $index   = [];
        $index[] = $this->getIndexItem($this->startNode);
        $nextIds = $this->getNextIds($this->startNode);

        while (!empty($nextIds)) {
            $nextId  = array_shift($nextIds);
            $index[] = $this->getIndexItem($nextId);
            foreach ($this->getNextIds($nextId) as $follower) {
                if (!in_array($this->getIndexItem($follower), $index, TRUE)) {
                    $nextIds[] = $follower;
                } else if ($checkInfiniteLoop) {
                    $this->isInfinity();
                }
            }
        }

        sort($index);

        return $index;
    }

    /**
     * @param string $id
     *
     * @return mixed[]
     */
    private function getNextIds(string $id): array
    {
        $ids = [];
        foreach ($this->sequences[$id] ?? [] as $child) {
            $ids[] = $child;
        }

        return $ids;
    }

    /**
     * @param string $id
     *
     * @return string
     */
    private function getIndexItem(string $id): string
    {
        $node = $this->nodes[$id];

        return sprintf('%s:%s:%s', $node->getId(), $node->getName(), $node->getPipesType());
    }

    /**
     * @throws TopologyException
     */
    private function checkStartNode(): void
    {
        if (empty($this->startNode)) {
            throw new TopologyException(
                'Invalid schema - starting node was not found',
                TopologyException::SCHEMA_START_NODE_MISSING
            );
        }
    }

    /**
     * @throws TopologyException
     */
    private function isInfinity(): void
    {
        throw new TopologyException('Invalid schema - infinite loop', TopologyException::SCHEMA_INFINITE_LOOP);
    }

}
