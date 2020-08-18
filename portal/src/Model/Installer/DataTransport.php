<?php declare(strict_types=1);

namespace Hanaboso\Portal\Model\Installer;

use Hanaboso\Portal\Model\Installer\Exception\InstallerException;

/**
 * Class DataTransport
 *
 * @package Hanaboso\Portal\Model\Installer
 */
final class DataTransport
{

    /**
     * @var string
     */
    private string $log;

    /**
     * @var string
     */
    private string $metric;

    /**
     * @var bool
     */
    private bool $database;

    /**
     * DataTransport constructor.
     *
     * @param string $log
     * @param string $metric
     * @param bool   $database
     *
     * @throws InstallerException
     */
    public function __construct(
        string $log = Installer::ELASTICSEARCH,
        string $metric = Installer::INFLUXDB,
        bool $database = TRUE
    )
    {

        if ($log === Installer::ELASTICSEARCH || $log === Installer::LOGSTASH) {
            $this->log = $log;
        } else {
            throw new InstallerException('Insert correct value to log', InstallerException::INVALID_INPUT);
        }
        if ($metric === Installer::INFLUXDB || $metric === Installer::MONGO) {
            $this->metric = $metric;
        } else {
            throw new InstallerException('Insert correct value to metric', InstallerException::INVALID_INPUT);
        }
        $this->database = $database;
    }

    /**
     * @return string
     */
    public function getLog(): string
    {
        return $this->log;
    }

    /**
     * @return string
     */
    public function getMetric(): string
    {
        return $this->metric;
    }

    /**
     * @return bool
     */
    public function getDatabase(): bool
    {
        return $this->database;
    }

}
