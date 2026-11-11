<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Utils\Dto;

use Hanaboso\PipesFramework\Configurator\Exception\TopologyException;
use Hanaboso\PipesFramework\Database\Document\TopologyApplication;

/**
 * Class Schema
 *
 * @package Hanaboso\PipesFramework\Utils\Dto
 */
final class Schema
{

    public const array ROUTING_OUTPUTS = ['no', 'yes'];

    /**
     * @var NodeSchemaDto[]
     */
    private array $nodes = [];

    /**
     * @var mixed[]
     */
    private array $sequences = [];

    /**
     * Optional source-output key (yes/no) per source→target edge.
     *
     * @var array<string, array<string, string>>
     */
    private array $sequenceOutputs = [];

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
    public function addNode(string $id, NodeSchemaDto $dto): self
    {
        $this->nodes[$id] = $dto;

        return $this;
    }

    /**
     * @param string $source
     * @param string $target
     * @param string $output
     *
     * @return Schema
     */
    public function addSequence(string $source, string $target, string $output = ''): self
    {
        $this->sequences[$source][] = $target;
        if ($output !== '') {
            $this->sequenceOutputs[$source][$target] = $output;
        }

        return $this;
    }

    /**
     * @param string $source
     * @param string $target
     *
     * @return string
     */
    public function getSequenceOutput(string $source, string $target): string
    {
        return $this->sequenceOutputs[$source][$target] ?? '';
    }

    /**
     * Ordinary edges are saved with "output", which must not change the stored content hashes.
     *
     * @param string $source
     * @param string $target
     *
     * @return string
     */
    public function getRoutingOutput(string $source, string $target): string
    {
        $output = $this->getSequenceOutput($source, $target);

        return in_array($output, self::ROUTING_OUTPUTS, TRUE) ? $output : '';
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
    public function addStartNode(string $startNode): self
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
        if ($this->nodes !== []) {
            $this->checkStartNode();
        } else {
            return [];
        }

        $topology = [];
        foreach ($this->startNode as $start) {
            $index   = [];
            $index[] = $this->getIndexItem($start);
            $visited = [$start => $start];
            $nextIds = $this->getNextIds($start);
            $tree    = [];

            if ($checkInfiniteLoop) {
                $clone = $nextIds;
                $this->addToTree($tree, $start, $clone);
            }

            while ($nextIds !== []) {
                $nextId           = array_shift($nextIds);
                $index[]          = $this->getIndexItem($nextId);
                $visited[$nextId] = $nextId;
                $ids              = $this->getNextIds($nextId);
                if ($ids !== [] && $checkInfiniteLoop) {
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

            foreach ($visited as $id) {
                foreach (array_unique($this->getNextIds($id)) as $target) {
                    $output = $this->getRoutingOutput($id, $target);
                    if ($output !== '') {
                        $index[] = sprintf(
                            '%s>%s:%s',
                            $this->getIndexItem($id),
                            $this->getIndexItem($target),
                            $output,
                        );
                    }
                }
            }

            sort($index);
            $topology[] = $index;
        }

        return $topology;
    }

    /**
     * @return TopologyApplication[]
     */
    public function getApplicationList(): array {

        $appList = [];

        foreach ($this->getNodes() as $node) {
            $app    = $node->getApplication();
            $host   = $node->getSystemConfigs()->getSdkHost();
            $worker = $node->getWorker();

            if ($app && $host) {
                $appList[sprintf('%s_%s', $app, $host)] = new TopologyApplication($app, $host, $worker);
            }
        }

        return array_values($appList);
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
                } else if ($f !== []) {
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

        return sprintf(
            '%s:%s:%s:%s',
            $node->getId(),
            $node->getName(),
            $node->getPipesType(),
            $node->getWorker(),
        );
    }

    /**
     * @throws TopologyException
     */
    private function checkStartNode(): void
    {
        if ($this->startNode === []) {
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

            if ($node !== []) {
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
