<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Commons\Transport\Ftp;

/**
 * Class FtpConfig
 *
 * @package Hanaboso\PipesFramework\Commons\Transport\Ftp
 */
class FtpConfig
{

    /**
     * @var string
     */
    private $host;

    /**
     * @var bool
     */
    private $ssl;

    /**
     * @var int
     */
    private $port;

    /**
     * @var int
     */
    private $timeout;

    /**
     * @var string
     */
    private $username;

    /**
     * @var string
     */
    private $password;

    /**
     * FtpConfig constructor.
     *
     * @param string $host
     * @param bool   $ssl
     * @param int    $port
     * @param int    $timeout
     * @param string $username
     * @param string $password
     */
    public function __construct(
        string $host,
        bool $ssl = FALSE,
        int $port = 21,
        int $timeout = 15,
        string $username,
        string $password
    )
    {
        $this->host     = $host;
        $this->ssl      = $ssl;
        $this->port     = $port;
        $this->timeout  = $timeout;
        $this->username = $username;
        $this->password = $password;
    }

    /**
     * @return string
     */
    public function getHost(): string
    {
        return $this->host;
    }

    /**
     * @return bool
     */
    public function isSsl(): bool
    {
        return $this->ssl;
    }

    /**
     * @return int
     */
    public function getPort(): int
    {
        return $this->port;
    }

    /**
     * @return int
     */
    public function getTimeout(): int
    {
        return $this->timeout;
    }

    /**
     * @return string
     */
    public function getUsername(): string
    {
        return $this->username;
    }

    /**
     * @return string
     */
    public function getPassword(): string
    {
        return $this->password;
    }

}