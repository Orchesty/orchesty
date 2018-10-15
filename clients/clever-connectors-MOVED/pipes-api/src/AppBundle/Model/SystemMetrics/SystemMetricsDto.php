<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\SystemMetrics;

use CleverConnectors\AppBundle\Enum\SystemMetricsIntervalEnum;
use DateTime;
use Hanaboso\CommonsBundle\Exception\EnumException;

/**
 * Class SystemMetricsDto
 *
 * @package CleverConnectors\AppBundle\Model\SystemMetrics
 */
class SystemMetricsDto
{

    /**
     * @var array
     */
    private $systemKeys;

    /**
     * @var DateTime|NULL
     */
    private $from;

    /**
     * @var DateTime|NULL
     */
    private $to;

    /**
     * @var string
     */
    private $interval;

    /**
     * @var string|NULL
     */
    private $guid;

    /**
     * SystemMetricsDto constructor.
     *
     * @param array         $systemKeys
     * @param DateTime|null $from
     * @param DateTime|null $to
     * @param null|string   $interval
     * @param null|string   $guid
     *
     * @throws EnumException
     */
    public function __construct(
        array $systemKeys,
        ?DateTime $from = NULL,
        ?DateTime $to = NULL,
        ?string $interval = NULL,
        ?string $guid = NULL
    )
    {
        $this->systemKeys = $systemKeys;
        $this->from       = $from;
        $this->to         = $to;
        $this->interval   = SystemMetricsIntervalEnum::isValid($interval ?? SystemMetricsIntervalEnum::DAY);
        $this->guid       = $guid;
    }

    /**
     * @return array
     */
    public function getSystemKeys(): array
    {
        return $this->systemKeys;
    }

    /**
     * @param array $systemKeys
     *
     * @return SystemMetricsDto
     */
    public function setSystemKeys(array $systemKeys): SystemMetricsDto
    {
        $this->systemKeys = $systemKeys;

        return $this;
    }

    /**
     * @param string $systemKey
     *
     * @return SystemMetricsDto
     */
    public function addSystemKey(string $systemKey): SystemMetricsDto
    {
        $this->systemKeys[] = $systemKey;

        return $this;
    }

    /**
     * @return DateTime|NULL
     */
    public function getFrom(): ?DateTime
    {
        return $this->from;
    }

    /**
     * @param DateTime|NULL $from
     *
     * @return SystemMetricsDto
     */
    public function setFrom(?DateTime $from): SystemMetricsDto
    {
        $this->from = $from;

        return $this;
    }

    /**
     * @return DateTime|NULL
     */
    public function getTo(): ?DateTime
    {
        return $this->to;
    }

    /**
     * @param DateTime|NULL $to
     *
     * @return SystemMetricsDto
     */
    public function setTo(?DateTime $to): SystemMetricsDto
    {
        $this->to = $to;

        return $this;
    }

    /**
     * @return string
     */
    public function getInterval(): string
    {
        return $this->interval;
    }

    /**
     * @param string $interval
     *
     * @return SystemMetricsDto
     * @throws EnumException
     */
    public function setInterval(string $interval): SystemMetricsDto
    {
        $this->interval = SystemMetricsIntervalEnum::isValid($interval);

        return $this;
    }

    /**
     * @return NULL|string
     */
    public function getGuid(): ?string
    {
        return $this->guid;
    }

    /**
     * @param NULL|string $guid
     *
     * @return SystemMetricsDto
     */
    public function setGuid(?string $guid): SystemMetricsDto
    {
        $this->guid = $guid;

        return $this;
    }

}