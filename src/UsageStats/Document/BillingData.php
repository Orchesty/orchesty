<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\UsageStats\Document;

/**
 * Class BillingData
 *
 * @package Hanaboso\PipesFramework\UsageStats\Document
 */
final class BillingData
{

    /**
     * BillingData constructor.
     *
     * @param string $aid
     * @param string $euid
     */
    public function __construct(private string $aid, private string $euid)
    {
    }

    /**
     * @return string
     */
    public function getAid(): string
    {
        return $this->aid;
    }

    /**
     * @param string $aid
     */
    public function setAid(string $aid): void
    {
        $this->aid = $aid;
    }

    /**
     * @return string
     */
    public function getEuid(): string
    {
        return $this->euid;
    }

    /**
     * @param string $euid
     */
    public function setEuid(string $euid): void
    {
        $this->euid = $euid;
    }

    /**
     * @return mixed[]
     */
    public function toArray(): array
    {
        return [
            'aid'     => $this->aid,
            'euid'    => $this->euid,
        ];
    }

}
