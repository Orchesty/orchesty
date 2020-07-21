<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\Database\Document\Dto;

use Hanaboso\Utils\String\Json;
use JsonException;

/**
 * Class SystemConfigDto
 *
 * @package Hanaboso\PipesPhpSdk\Database\Document\Dto
 */
final class SystemConfigDto
{

    public const SDK      = 'sdk';
    public const BRIDGE   = 'bridge';
    public const RABBIT   = 'rabbit';
    public const REPEATER = 'repeater';

    public const HOST     = 'host';
    public const PREFETCH = 'prefetch';
    public const ENABLED  = 'enabled';
    public const HOPS     = 'hops';
    public const INTERVAL = 'interval';

    /**
     * @var string
     */
    private string $sdkHost;

    /**
     * @var string
     */
    private string $bridgeHost;

    /**
     * @var int
     */
    private int $prefetch;

    /**
     * @var bool
     */
    private bool $repeaterEnabled;

    /**
     * @var int
     */
    private int $repeaterHops;

    /**
     * @var int
     */
    private int $repeaterInterval;

    /**
     * SystemConfigDto constructor.
     *
     * @param string $sdkHost
     * @param string $bridgeHost
     * @param int    $prefetch
     * @param bool   $repeaterEnabled
     * @param int    $repeaterHops
     * @param int    $repeaterInterval
     */
    public function __construct(
        $sdkHost = '',
        $bridgeHost = '',
        $prefetch = 1,
        $repeaterEnabled = FALSE,
        $repeaterHops = 0,
        $repeaterInterval = 0
    )
    {
        $this->sdkHost          = $sdkHost;
        $this->bridgeHost       = $bridgeHost;
        $this->prefetch         = $prefetch;
        $this->repeaterEnabled  = $repeaterEnabled;
        $this->repeaterHops     = $repeaterHops;
        $this->repeaterInterval = $repeaterInterval;
    }

    /**
     * @param string $param
     *
     * @return SystemConfigDto
     * @throws JsonException
     */
    public static function fromString(string $param): SystemConfigDto
    {
        $result = Json::decode($param);

        return new SystemConfigDto(
            $result[self::SDK][self::HOST],
            $result[self::BRIDGE][self::HOST],
            $result[self::RABBIT][self::PREFETCH],
            $result[self::REPEATER][self::ENABLED],
            $result[self::REPEATER][self::HOPS],
            $result[self::REPEATER][self::INTERVAL]
        );
    }

    /**
     * @return string
     */
    public function toString(): string
    {
        return Json::encode(
            [
                self::SDK      => [self::HOST => $this->getSdkHost()],
                self::BRIDGE   => [self::HOST => $this->getBridgeHost()],
                self::RABBIT   => [self::PREFETCH => $this->getPrefetch()],
                self::REPEATER => [
                    self::ENABLED  => $this->isRepeaterEnabled(),
                    self::HOPS     => $this->getRepeaterHops(),
                    self::INTERVAL => $this->getRepeaterInterval(),
                ],
            ]
        );
    }

    /**
     * @return string
     */
    public function getSdkHost(): string
    {
        return $this->sdkHost;
    }

    /**
     * @return string
     */
    public function getBridgeHost(): string
    {
        return $this->bridgeHost;
    }

    /**
     * @return int
     */
    public function getPrefetch(): int
    {
        return $this->prefetch;
    }

    /**
     * @return bool
     */
    public function isRepeaterEnabled(): bool
    {
        return $this->repeaterEnabled;
    }

    /**
     * @return int
     */
    public function getRepeaterInterval(): int
    {
        return $this->repeaterInterval;
    }

    /**
     * @return int
     */
    public function getRepeaterHops(): int
    {
        return $this->repeaterHops;
    }

}
