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

    private const HOST     = 'host';
    private const BRIDGE   = 'bridge';
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
            'sdk'      => [self::HOST => $this->getSdkHost()],
            'bridge'   => [self::BRIDGE => $this->getBridgeHost()],
            'rabbit'   => [self::PREFETCH => $this->getPrefetch()],
            'repeater' => [
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
        $result = json_decode($param, TRUE);
        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new Exception('Unable to parse response body into JSON: ' . json_last_error());
        }

        return new SystemConfigDto(
            $result['sdk'][self::HOST],
            $result['bridge'][self::BRIDGE],
            $result['rabbit'][self::PREFETCH],
            $result['repeater'][self::ENABLED],
            $result['repeater'][self::HOPS],
            $result['repeater'][self::INTERVAL]
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