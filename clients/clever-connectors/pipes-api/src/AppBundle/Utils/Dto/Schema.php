<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Utils\Dto;

/**
 * Class Schema
 *
 * @package CleverConnectors\AppBundle\Utils\Dto
 */
class Schema
{

    /**
     * @var array
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
     * @return mixed
     */
    public function getNodes()
    {
        return $this->nodes;
    }

    /**
     * @param string $id
     * @param array  $node
     */
    public function addNode(string $id, array $node): void
    {
        $this->nodes[$id] = $node;
    }

    /**
     * @return mixed
     */
    public function getSequences()
    {
        return $this->sequences;
    }

    /**
     * @param string $source
     * @param string $target
     */
    public function addSequence(string $source, string $target): void
    {
        $this->sequences[$source][] = $target;
    }

    /**
     * @param string $startNode
     */
    public function setStartNode(string $startNode): void
    {
        $this->startNode = $startNode;
    }

    /**
     * @return array
     */
    public function buildIndex(): array
    {
        if (empty($this->startNode)) {
            // todo throw exception
        }

        $index     = [];
        $history   = [];
        $history[] = $this->startNode;
        $index[]   = $this->getIndexItem($this->startNode);
        $nextIds   = $this->sequences[$this->startNode];

        // todo keep history in case of infinite loop
        // todo make sure while loop will end
        // todo sort targets of single source

        while ($nextIds) {
            foreach ($nextIds as $nextId) {
                $index[] = $this->getIndexItem($nextId);
            }

            $nextIds = isset($this->sequences[$nextId]) ? $this->sequences[$nextId] : FALSE;
        }
    }

    /**
     * @param string $id
     *
     * @return array
     */
    private function getIndexItem(string $id): array
    {
        $node = $this->nodes[$id];

        return [
            'name' => $node['name'],
            'type' => $node['pipes_type'],
        ];
    }

}