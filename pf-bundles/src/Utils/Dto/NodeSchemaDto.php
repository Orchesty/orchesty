<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Utils\Dto;

use Hanaboso\PipesPhpSdk\Database\Document\Dto\SystemConfigDto;

/**
 * Class NodeSchemaDto
 *
 * @package Hanaboso\PipesFramework\Utils\Dto
 */
final class NodeSchemaDto
{

    private const HANDLER        = 'handler';
    private const ID             = 'id';
    private const NAME           = 'name';
    private const CRON_TIME      = 'cron_time';
    private const CRON_PARAMS    = 'cron_params';
    private const PIPES_TYPE     = 'pipes_type';
    private const SYSTEM_CONFIGS = 'system_configs';

    /**
     * NodeSchemaDto constructor.
     *
     * @param string          $handler
     * @param string          $id
     * @param string          $pipesType
     * @param SystemConfigDto $systemConfigs
     * @param string          $name
     * @param string          $cronTime
     * @param string          $cronParams
     */
    public function __construct(
        private string $handler,
        private string $id,
        private string $pipesType,
        private SystemConfigDto $systemConfigs,
        private string $name,
        private string $cronTime = '',
        private string $cronParams = '',
    )
    {
    }

    /**
     * @return string
     */
    public function getHandler(): string
    {
        return $this->handler;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getCronTime(): string
    {
        return $this->cronTime;
    }

    /**
     * @return string
     */
    public function getCronParams(): string
    {
        return $this->cronParams;
    }

    /**
     * @return string
     */
    public function getPipesType(): string
    {
        return $this->pipesType;
    }

    /**
     * @return SystemConfigDto
     */
    public function getSystemConfigs(): SystemConfigDto
    {
        return $this->systemConfigs;
    }

    /**
     * @return mixed[]
     */
    public function getSystemConfigsArray(): array
    {
        return [
            'sdkHost' => $this->systemConfigs->getSdkHost(),
            'bridgeHost' => $this->systemConfigs->getBridgeHost(),
            'prefetch' => $this->systemConfigs->getPrefetch(),
            'repeaterEnabled' => $this->systemConfigs->isRepeaterEnabled(),
            'repeaterHops' => $this->systemConfigs->getRepeaterHops(),
            'repeaterInterval' => $this->systemConfigs->getRepeaterInterval(),
        ];
    }

    /**
     * @return mixed[]
     */
    public function toArray(): array
    {
        return [
            self::HANDLER        => $this->getHandler(),
            self::ID             => $this->getId(),
            self::NAME           => $this->getName(),
            self::CRON_TIME      => $this->getCronTime(),
            self::CRON_PARAMS    => $this->getCronParams(),
            self::PIPES_TYPE     => $this->getPipesType(),
            self::SYSTEM_CONFIGS => $this->getSystemConfigs(),
        ];
    }

}
