<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\UsageStats\Document;

/**
 * Class HearthBeatData
 *
 * @package Hanaboso\PipesFramework\UsageStats\Document
 */
final class HearthBeatData
{

    /**
     * HearthBeatData constructor.
     *
     * @param int    $count
     * @param string $type
     */
    public function __construct(private int $count, private string $type)
    {
    }

    /**
     * @return int
     */
    public function getCount(): int
    {
        return $this->count;
    }

    /**
     * @param int $count
     */
    public function setCount(int $count): void
    {
        $this->count = $count;
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
     */
    public function setType(string $type): void
    {
        $this->type = $type;
    }

    /**
     * @return mixed[]
     */
    public function toArray(): array
    {
        return [
            'count' => $this->count,
            'type'  => $this->type,
        ];
    }

}
