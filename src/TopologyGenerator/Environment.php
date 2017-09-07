<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: venca
 * Date: 9/6/17
 * Time: 8:43 AM
 */

namespace Hanaboso\PipesFramework\TopologyGenerator;

/**
 * Class Environment
 *
 * @package Hanaboso\PipesFramework\TopologyGenerator
 */
class Environment
{

    public const RABBITMQ_HOST       = 'RABBITMQ_HOST';
    public const RABBITMQ_PORT       = 'RABBITMQ_PORT';
    public const RABBITMQ_USER       = 'RABBITMQ_USER';
    public const RABBITMQ_PASS       = 'RABBITMQ_PASS';
    public const RABBITMQ_VHOST      = 'RABBITMQ_VHOST';
    public const XML_PARSER_HOST     = 'PARSER_HOST';
    public const XML_PARSER_PORT     = 'PARSER_PORT';
    public const XML_PARSER_RELOADED = 'PARSER_RELOADED';
    public const METRICS_HOST        = 'METRICS_HOST';
    public const METRICS_PORT        = 'METRICS_PORT';
    public const DEV_UID             = 'DEV_UID';
    public const DEV_GID             = 'DEV_GID';

    /**
     * @var string
     */
    protected $rabbitMqHost = 'rabbitmq';

    /**
     * @var string
     */
    protected $rabbitMqPort = '5672';

    /**
     * @var string
     */
    protected $rabbitMqUser = 'guest';

    /**
     * @var string
     */
    protected $rabbitMqPass = 'guest';

    /**
     * @var string
     */
    protected $rabbitMqVHost = '/';

    /**
     * @var string
     */
    protected $xmlParserHost = 'xml-parser';

    /**
     * @var int
     */
    protected $xmlParserPort = 80;

    /**
     * @var int
     */
    protected $xmlParserReloaded = 1;

    /**
     * @var string
     */
    protected $metricsHost = 'metrics';

    /**
     * @var int
     */
    protected $metricsPort = 5555;

    /**
     * @var string
     */
    protected $devUid = '${DEV_UID}';

    /**
     * @var string
     */
    protected $devGid = '${DEV_GID}';

    /**
     * @return string
     */
    public function getRabbitMqHost(): string
    {
        return $this->rabbitMqHost;
    }

    /**
     * @param string $rabbitMqHost
     *
     * @return Environment
     */
    public function setRabbitMqHost(string $rabbitMqHost): Environment
    {
        $this->rabbitMqHost = $rabbitMqHost;

        return $this;
    }

    /**
     * @return string
     */
    public function getRabbitMqPort(): string
    {
        return $this->rabbitMqPort;
    }

    /**
     * @param string $rabbitMqPort
     *
     * @return Environment
     */
    public function setRabbitMqPort(string $rabbitMqPort): Environment
    {
        $this->rabbitMqPort = $rabbitMqPort;

        return $this;
    }

    /**
     * @return string
     */
    public function getRabbitMqUser(): string
    {
        return $this->rabbitMqUser;
    }

    /**
     * @param string $rabbitMqUser
     *
     * @return Environment
     */
    public function setRabbitMqUser(string $rabbitMqUser): Environment
    {
        $this->rabbitMqUser = $rabbitMqUser;

        return $this;
    }

    /**
     * @return string
     */
    public function getRabbitMqPass(): string
    {
        return $this->rabbitMqPass;
    }

    /**
     * @param string $rabbitMqPass
     *
     * @return Environment
     */
    public function setRabbitMqPass(string $rabbitMqPass): Environment
    {
        $this->rabbitMqPass = $rabbitMqPass;

        return $this;
    }

    /**
     * @return string
     */
    public function getRabbitMqVHost(): string
    {
        return $this->rabbitMqVHost;
    }

    /**
     * @param string $rabbitMqVHost
     *
     * @return Environment
     */
    public function setRabbitMqVHost(string $rabbitMqVHost): Environment
    {
        $this->rabbitMqVHost = $rabbitMqVHost;

        return $this;
    }

    /**
     * @return string
     */
    public function getXmlParserHost(): string
    {
        return $this->xmlParserHost;
    }

    /**
     * @param string $xmlParserHost
     *
     * @return Environment
     */
    public function setXmlParserHost(string $xmlParserHost): Environment
    {
        $this->xmlParserHost = $xmlParserHost;

        return $this;
    }

    /**
     * @return int
     */
    public function getXmlParserPort(): int
    {
        return $this->xmlParserPort;
    }

    /**
     * @param int $xmlParserPort
     *
     * @return Environment
     */
    public function setXmlParserPort(int $xmlParserPort): Environment
    {
        $this->xmlParserPort = $xmlParserPort;

        return $this;
    }

    /**
     * @return int
     */
    public function getXmlParserReloaded(): int
    {
        return $this->xmlParserReloaded;
    }

    /**
     * @param int $xmlParserReloaded
     *
     * @return Environment
     */
    public function setXmlParserReloaded(int $xmlParserReloaded): Environment
    {
        $this->xmlParserReloaded = $xmlParserReloaded;

        return $this;
    }

    /**
     * @return string
     */
    public function getMetricsHost(): string
    {
        return $this->metricsHost;
    }

    /**
     * @param string $metricsHost
     *
     * @return Environment
     */
    public function setMetricsHost(string $metricsHost): Environment
    {
        $this->metricsHost = $metricsHost;

        return $this;
    }

    /**
     * @return int
     */
    public function getMetricsPort(): int
    {
        return $this->metricsPort;
    }

    /**
     * @param int $metricsPort
     *
     * @return Environment
     */
    public function setMetricsPort(int $metricsPort): Environment
    {
        $this->metricsPort = $metricsPort;

        return $this;
    }

    /**
     * @return string
     */
    public function getDevUid(): string
    {
        return $this->devUid;
    }

    /**
     * @param string $devUid
     *
     * @return Environment
     */
    public function setDevUid(string $devUid): Environment
    {
        $this->devUid = $devUid;

        return $this;
    }

    /**
     * @return string
     */
    public function getDevGid(): string
    {
        return $this->devGid;
    }

    /**
     * @param string $devGid
     *
     * @return Environment
     */
    public function setDevGid(string $devGid): Environment
    {
        $this->devGid = $devGid;

        return $this;
    }

}