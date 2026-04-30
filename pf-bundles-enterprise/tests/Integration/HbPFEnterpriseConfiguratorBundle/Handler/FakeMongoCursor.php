<?php declare(strict_types=1);

namespace PipesFrameworkEnterpriseTests\Integration\HbPFEnterpriseConfiguratorBundle\Handler;

use Iterator;
use LogicException;
use MongoDB\BSON\Int64;
use MongoDB\Driver\CursorInterface;
use MongoDB\Driver\Server;

/**
 * Class FakeMongoCursor
 *
 * Drop-in CursorInterface implementation backed by a plain in-memory array,
 * used by handler unit tests to fake `Collection::find()` /
 * `Collection::aggregate()` results without reaching for a live MongoDB.
 *
 * @package PipesFrameworkEnterpriseTests\Integration\HbPFEnterpriseConfiguratorBundle\Handler
 */
final class FakeMongoCursor implements CursorInterface, Iterator
{

    /**
     * @var array<int, mixed>
     */
    private array $items;

    private int $pos = 0;

    /**
     * FakeMongoCursor constructor.
     *
     * @param array<int, mixed> $items
     */
    public function __construct(array $items)
    {
        $this->items = array_values($items);
    }

    /**
     * @return object|mixed[]|null
     */
    public function current(): object|array|null
    {
        return $this->items[$this->pos] ?? NULL;
    }

    /**
     * @return int|null
     */
    public function key(): ?int
    {
        return $this->valid() ? $this->pos : NULL;
    }

    /**
     * @return void
     */
    public function next(): void
    {
        $this->pos++;
    }

    /**
     * @return void
     */
    public function rewind(): void
    {
        $this->pos = 0;
    }

    /**
     * @return bool
     */
    public function valid(): bool
    {
        return isset($this->items[$this->pos]);
    }

    /**
     * @return Int64
     */
    public function getId(): Int64
    {
        return new Int64(0);
    }

    /**
     * Cursor server is not modeled in tests; throwing keeps the SUT honest
     * if it ever starts depending on it.
     *
     * @return Server
     */
    public function getServer(): Server
    {
        throw new LogicException('Cursor server not available in tests');
    }

    /**
     * @return bool
     */
    public function isDead(): bool
    {
        return TRUE;
    }

    /**
     * @param mixed[] $typemap unused; kept to satisfy interface signature
     *
     * @return void
     */
    public function setTypeMap(array $typemap): void
    {
        unset($typemap);
    }

    /**
     * @return mixed[]
     */
    public function toArray(): array
    {
        return $this->items;
    }

}
