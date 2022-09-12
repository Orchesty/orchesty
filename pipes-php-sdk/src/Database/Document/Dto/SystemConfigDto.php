<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\Database\Document\Dto;

use Hanaboso\Utils\String\Json;

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
    public const TIMEOUT  = 'timeout';
    public const ENABLED  = 'enabled';
    public const HOPS     = 'hops';
    public const INTERVAL = 'interval';

    /**
     * SystemConfigDto constructor.
     *
     * @param string $sdkHost
     * @param string $bridgeHost
     * @param int    $prefetch
     * @param bool   $repeaterEnabled
     * @param int    $repeaterHops
     * @param int    $repeaterInterval
     * @param int    $timeout
     */
    public function __construct(
        private string $sdkHost = '',
        private string $bridgeHost = '',
        private int $prefetch = 1,
        private bool $repeaterEnabled = FALSE,
        private int $repeaterHops = 0,
        private int $repeaterInterval = 0,
        private int $timeout = 60,
    )
    {
    }

    /**
     * @param string $param
     *
     * @return SystemConfigDto
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
            $result[self::REPEATER][self::INTERVAL],
            $result[self::BRIDGE][self::TIMEOUT],
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
                self::BRIDGE   => [
                    self::HOST    => $this->getBridgeHost(),
                    self::TIMEOUT => $this->getTimeout(),
                ],
                self::RABBIT   => [self::PREFETCH => $this->getPrefetch()],
                self::REPEATER => [
                    self::ENABLED  => $this->isRepeaterEnabled(),
                    self::HOPS     => $this->getRepeaterHops(),
                    self::INTERVAL => $this->getRepeaterInterval(),
                ],
            ],
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

    /**
     * @return int
     */
    public function getTimeout(): int
    {
        return $this->timeout;
    }

}
