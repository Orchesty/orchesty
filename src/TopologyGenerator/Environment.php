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

    public const RABBITMQ_HOST  = 'RABBITMQ_HOST';
    public const RABBITMQ_PORT  = 'RABBITMQ_PORT';
    public const RABBITMQ_USER  = 'RABBITMQ_USER';
    public const RABBITMQ_PASS  = 'RABBITMQ_PASS';
    public const RABBITMQ_VHOST = 'RABBITMQ_VHOST';

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

}