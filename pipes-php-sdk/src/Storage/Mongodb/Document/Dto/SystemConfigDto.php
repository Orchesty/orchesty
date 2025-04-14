<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\Storage\Mongodb\Document\Dto;

use Hanaboso\Utils\String\Json;

/**
 * Class SystemConfigDto
 *
 * @package Hanaboso\PipesPhpSdk\Storage\Mongodb\Document\Dto
 */
final class SystemConfigDto
{

    public const string SDK      = 'sdk';
    public const string BRIDGE   = 'bridge';
    public const string RABBIT   = 'rabbit';
    public const string REPEATER = 'repeater';

    public const string HOST     = 'host';
    public const string PREFETCH = 'prefetch';
    public const string TIMEOUT  = 'timeout';
    public const string ENABLED  = 'enabled';
    public const string HOPS     = 'hops';
    public const string INTERVAL = 'interval';

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
     * @return self
     */
    public static function fromString(string $param): self
    {
        $result = Json::decode($param);

        return new self(
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
                self::SDK      => [self::HOST => $this->getSdkHost()],
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
