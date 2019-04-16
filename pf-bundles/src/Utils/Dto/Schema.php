<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Utils\Dto;

use Hanaboso\PipesFramework\Configurator\Exception\TopologyException;

/**
 * Class Schema
 *
 * @package Hanaboso\PipesFramework\Utils\Dto
 */
class Schema
{

    private const LIMIT = 100;

    /**
     * @var array|NodeSchemaDto[]
     */
    private $nodes = [];

    /**
     * @var array
     */
    private $sequences = [];

    /**
     * @var string
     */
    private $startNode = '';

    /**
     * @return array|NodeSchemaDto[]
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
     * @return array
     * @throws TopologyException
     */
    public function buildIndex(): array
    {
        if (!empty($this->nodes)) {
            $this->checkStartNode();
        } else {
            return [];
        }

        $count   = 1;
        $index   = [];
        $index[] = $this->getIndexItem($this->startNode);
        $nextIds = $this->sequences[$this->startNode];

        while ($nextIds) {
            $ids = [];
            foreach ($nextIds as $nextId) {
                $index[] = $this->getIndexItem($nextId);
                if (isset($this->sequences[$nextId])) {
                    $this->checkInfiniteLoop($count);
                    $ids = array_merge($ids, $this->sequences[$nextId]);
                    $count++;
                }
            }

            $nextIds = $ids;
        }

        sort($index);

        return $index;
    }

    /**
     * @param string $id
     *
     * @return string
     */
    private function getIndexItem(string $id): string
    {
        $node = $this->nodes[$id];

        return sprintf('%s:%s', $node->getName(), $node->getPipesType());
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
     * @param int $count
     *
     * @throws TopologyException
     */
    private function checkInfiniteLoop(int $count): void
    {
        if ($count >= self::LIMIT) {
            throw new TopologyException(
                'Invalid schema - infinite loop',
                TopologyException::SCHEMA_INFINITE_LOOP
            );
        }
    }

}
