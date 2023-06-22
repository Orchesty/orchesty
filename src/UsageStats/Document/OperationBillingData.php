<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\UsageStats\Document;

/**
 * Class OperationBillingData
 *
 * @package Hanaboso\PipesFramework\UsageStats\Document
 */
final class OperationBillingData
{

    /**
     * OperationBillingData constructor.
     *
     * @param string $day
     * @param int    $total
     */
    public function __construct(private string $day, private int $total)
    {
    }

    /**
     * @return string
     */
    public function getDay(): string
    {
        return $this->day;
    }

    /**
     * @param string $day
     *
     * @return OperationBillingData
     */
    public function setDay(string $day): self
    {
        $this->day = $day;

        return $this;
    }

    /**
     * @return int
     */
    public function getTotal(): int
    {
        return $this->total;
    }

    /**
     * @param int $total
     *
     * @return OperationBillingData
     */
    public function setTotal(int $total): self
    {
        $this->total = $total;

        return $this;
    }

    /**
     * @return mixed[]
     */
    public function toArray(): array
    {
        return [
            'day'   => $this->day,
            'total' => $this->total,
        ];
    }

}
