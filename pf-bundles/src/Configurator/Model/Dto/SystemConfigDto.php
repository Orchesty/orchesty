<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Configurator\Model\Dto;

use Exception;

/**
 * Class SystemConfDto
 *
 * @package Hanaboso\PipesFramework\Configurator\Model\Dto
 */
class SystemConfigDto
{

    private const SDK      = 'sdk';
    private const BRIDGE   = 'bridge';
    private const RABBIT   = 'rabbit';
    private const REPEATER = 'repeater';

    private const HOST     = 'host';
    private const PREFETCH = 'prefetch';
    private const ENABLED  = 'enabled';
    private const HOPS     = 'hops';
    private const INTERVAL = 'interval';

    /**
     * @var string
     */
    private $sdkHost;

    /**
     * @var string
     */
    private $bridgeHost;

    /**
     * @var int
     */
    private $prefetch;

    /**
     * @var bool
     */
    private $repeaterEnabled;

    /**
     * @var int
     */
    private $repeaterHops;

    /**
     * @var int
     */
    private $repeaterInterval;

    /**
     * SystemConfDto constructor.
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
     * @return string
     */
    public function toString(): string
    {
        return (string) json_encode([
            self::SDK      => [self::HOST => $this->getSdkHost()],
            self::BRIDGE   => [self::HOST => $this->getBridgeHost()],
            self::RABBIT   => [self::PREFETCH => $this->getPrefetch()],
            self::REPEATER => [
                self::ENABLED  => $this->isRepeaterEnabled(),
                self::HOPS     => $this->getRepeaterHops(),
                self::INTERVAL => $this->getRepeaterInterval(),
            ],
        ]);
    }

    /**
     * @param string $param
     *
     * @return SystemConfigDto
     * @throws Exception
     */
    public function fromString(string $param): SystemConfigDto
    {
        $result = json_decode($param, TRUE, 512, JSON_THROW_ON_ERROR);

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