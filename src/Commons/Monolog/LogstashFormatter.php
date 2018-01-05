<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: venca
 * Date: 13.9.17
 * Time: 9:23
 */

namespace Hanaboso\PipesFramework\Commons\Monolog;

use Exception;
use InvalidArgumentException;
use Monolog\Formatter\NormalizerFormatter;
use SoapFault;
use Throwable;

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
    protected $serviceType = '';

    /**
     * @param string $serviceType
     */
    public function __construct(string $serviceType = '')
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

        if ($this->serviceType === '') {
            $message['type'] = $record['channel'] ?? '';
        }

        if (isset($record['message'])) {
            $message['message'] = $record['message'];
        }

        if (isset($record['channel'])) {
            $message['channel'] = $record['channel'];
        }

        if (isset($record['level_name'])) {
            $message['severity'] = $record['level_name'];
        }

        if (isset($record['context']['exception'])) {
            $message['stacktrace'] = $record['context']['exception'];
            unset($record['context']['exception']);
        }

        if (isset($record['context']['correlation_id'])) {
            $message['correlation_id'] = $record['context']['correlation_id'];
            unset($record['context']['correlation_id']);
        }

        if (isset($record['context']['node_id'])) {
            $message['node_id'] = $record['context']['node_id'];
            unset($record['context']['node_id']);
        }

        if (!empty($record['context'])) {
            foreach ($record['context'] as $key => $val) {
                $message[$key] = $val;
            }
        }

        return $this->toJson($message) . "\n";
    }

    /**
     * @param Exception|Throwable $e
     *
     * @return array
     */
    protected function normalizeException($e): array
    {
        // TODO 2.0 only check for Throwable
        if (!$e instanceof Exception && !$e instanceof Throwable) {
            throw new InvalidArgumentException('Exception/Throwable expected, got ' . gettype($e) . ' / ' . get_class($e));
        }

        $data = [
            'class'   => get_class($e),
            'message' => $e->getMessage(),
            'code'    => $e->getCode(),
            'file'    => $e->getFile() . ':' . $e->getLine(),
        ];

        if ($e instanceof SoapFault) {
            if (isset($e->faultcode)) {
                $data['faultcode'] = $e->faultcode;
            }

            if (isset($e->faultactor)) {
                $data['faultactor'] = $e->faultactor;
            }

            if (isset($e->detail)) {
                $data['detail'] = $e->detail;
            }
        }

        $data['trace'] = $this->toJson($e->getTraceAsString());

        if ($e->getPrevious()) {
            $data['previous'] = $this->normalizeException($e->getPrevious());
        }

        return $data;
    }

}