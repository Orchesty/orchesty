<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: venca
 * Date: 13.9.17
 * Time: 9:23
 */

namespace Hanaboso\PipesFramework\Commons\Monolog;

use Monolog\Formatter\NormalizerFormatter;

/**
 * Class LogstashFormatter
 *
 * @package Hanaboso\PipesFramework\Commons\Monolog
 */
class LogstashFormatter extends NormalizerFormatter
{

    /**
     * @var string
     */
    protected $serviceType;

    /**
     * @param string $serviceType
     */
    public function __construct(string $serviceType)
    {
        // logstash requires a ISO 8601 format date with optional millisecond precision.
        parent::__construct('Y-m-d\TH:i:s.uP');
        $this->serviceType = $serviceType;
    }

    /**
     * @param array $record
     *
     * @return string
     */
    public function format(array $record): string
    {
        $record = parent::format($record);

        $message['timestamp'] = round(microtime(TRUE) * 1000);
        $message['hostname']  = gethostname();
        $message['type']      = $this->serviceType;

        if (isset($record['message'])) {
            $message['message'] = $record['message'];
        }

        if (isset($record['channel'])) {
            $message['channel'] = $record['channel'];
        }

        if (isset($record['level_name'])) {
            $message['severity_code'] = $record['level_name'];
        }

        if (isset($record['level'])) {
            $message['severity'] = $record['level'];
        }

        if (isset($record['context']['exception'])) {
            $message['stacktrace'] = $record['context']['exception'];
        }

        if (isset($record['context']['correlation_id'])) {
            $message['correlation_id'] = $record['context']['correlation_id'];
        }

        if (isset($record['context']['node_id'])) {
            $message['node_id'] = $record['context']['node_id'];
        }

        return $this->toJson($message) . "\n";
    }

}