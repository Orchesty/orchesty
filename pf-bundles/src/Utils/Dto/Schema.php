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
     * @var string[]
     */
    private array $startNode = [];

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
     * @return mixed[]
     */
    public function getSequences(): array
    {
        return $this->sequences;
    }

    /**
     * @return string[]
     */
    public function getStartNode(): array
    {
        return $this->startNode;
    }

    /**
     * @param string $startNode
     *
     * @return Schema
     */
    public function addStartNode(string $startNode): Schema
    {
        $this->startNode[] = $startNode;

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

        $topology = [];
        foreach ($this->startNode as $start) {
            $index   = [];
            $index[] = $this->getIndexItem($start);
            $nextIds = $this->getNextIds($start);

            if ($checkInfiniteLoop) {
                $tree  = [];
                $clone = $nextIds;
                $this->addToTree($tree, $start, $clone);
            }

            while (!empty($nextIds)) {
                $nextId  = array_shift($nextIds);
                $index[] = $this->getIndexItem($nextId);
                $ids     = $this->getNextIds($nextId);
                if (!empty($ids) && $checkInfiniteLoop) {
                        $clone = $ids;
                        $this->addToTree($tree, $nextId, $clone);
                }
                foreach ($ids as $follower) {
                    if (!in_array($this->getIndexItem($follower), $index, TRUE)) {
                        $nextIds[] = $follower;
                    }
                }
            }

            if ($checkInfiniteLoop) {
                $this->isInfinity($tree);
            }

            sort($index);
            $topology[] = $index;
        }

        return $topology;
    }

    /**
     * @param mixed[] $tree
     * @param string  $parent
     * @param mixed[] $followers
     * @param bool    $canWrite
     */
    private function addToTree(array &$tree, string $parent, array &$followers, bool $canWrite = TRUE): void
    {
        foreach ($tree as $i => $node) {
            foreach ($node as $name => $f) {
                $isLast = next($tree) === FALSE;
                if ($name === $parent) {
                    foreach ($followers as $k => $follower) {
                        $tree[$i][$parent][$follower] = [];
                        unset($followers[$k]);
                    }
                } else if (!empty($f)) {
                    $this->addToTree($tree[$i][$name], $parent, $followers, $isLast);
                }
            }
        }

        if ($canWrite) {
            foreach ($followers as $j => $follower) {
                $tree[$parent][$follower] = [];
                unset($followers[$j]);
            }
        }
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
                TopologyException::SCHEMA_START_NODE_MISSING,
            );
        }
    }

    /**
     * @param mixed[] $tree
     * @param mixed[] $walked
     *
     * @throws TopologyException
     */
    private function isInfinity(array &$tree, array &$walked = []): void
    {
        foreach ($tree as $name => $node) {
            if (array_search($name, $walked, TRUE)) {
                throw new TopologyException('Invalid schema - infinite loop', TopologyException::SCHEMA_INFINITE_LOOP);
            }

            if (!empty($node)) {
                $walked[] = $name;
                $this->isInfinity($tree[$name], $walked);
            } else if (count($tree) == 1) {
                $walked = [];
            } else {
                unset($tree[$name]);
            }
        }
    }

}
