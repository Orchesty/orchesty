<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Configurator\Model\Dto;

/**
 * Class SystemConfDto
 *
 * @package Hanaboso\PipesFramework\Configurator\Model\Dto
 */
class SystemConfDto
{

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
    public function __construct($sdkHost = '',
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
     * @param array $fields
     *
     * @return string
     */
    public function toString(array $fields): string
    {
        return (string) json_encode([
            'sdk'      => [$fields['sdkHost'] => $this->getSdkHost()],
            'bridge'   => [$fields['bridgeHost'] => $this->getBridgeHost()],
            'rabbit'   => [$fields['prefetch'] => $this->getPrefetch()],
            'repeater' => [
                $fields['repeaterEnabled']  => $this->isRepeaterEnabled(),
                $fields['repeaterHops']     => $this->getRepeaterHops(),
                $fields['repeaterInterval'] => $this->getRepeaterInterval(),
            ],
        ]);
    }

    /**
     * @param string $param
     * @param array  $fields
     *
     * @return SystemConfDto
     */
    public function fromString(string $param, array $fields): SystemConfDto
    {
        $result = json_decode($param);

        return new SystemConfDto(
            $result->sdk->{$fields['sdkHost']},
            $result->bridge->{$fields['bridgeHost']},
            $result->rabbit->{$fields['prefetch']},
            $result->repeater->{$fields['repeaterEnabled']},
            $result->repeater->{$fields['repeaterHops']},
            $result->repeater->{$fields['repeaterInterval']}
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
     * @param int $repeaterHops
     *
     * @return SystemConfDto
     */
    public function setRepeaterHops(int $repeaterHops): SystemConfDto
    {
        $this->repeaterHops = $repeaterHops;

        return $this;
    }

    /**
     * @param string $sdkHost
     *
     * @return SystemConfDto
     */
    public function setSdkHost(string $sdkHost): SystemConfDto
    {
        $this->sdkHost = $sdkHost;

        return $this;
    }

    /**
     * @param string $bridgeHost
     *
     * @return SystemConfDto
     */
    public function setBridgeHost(string $bridgeHost): SystemConfDto
    {
        $this->bridgeHost = $bridgeHost;

        return $this;
    }

    /**
     * @param int $prefetch
     *
     * @return SystemConfDto
     */
    public function setPrefetch(int $prefetch): SystemConfDto
    {
        $this->prefetch = $prefetch;

        return $this;
    }

    /**
     * @param bool $repeaterEnabled
     *
     * @return SystemConfDto
     */
    public function setRepeaterEnabled(bool $repeaterEnabled): SystemConfDto
    {
        $this->repeaterEnabled = $repeaterEnabled;

        return $this;
    }

    /**
     * @return int
     */
    public function getRepeaterHops(): int
    {
        return $this->repeaterHops;
    }

    /**
     * @param int $repeaterInterval
     *
     * @return SystemConfDto
     */
    public function setRepeaterInterval(int $repeaterInterval): SystemConfDto
    {
        $this->repeaterInterval = $repeaterInterval;

        return $this;
    }

}