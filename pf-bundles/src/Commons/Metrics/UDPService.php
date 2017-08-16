<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: stanislav.kundrat
 * Date: 8/9/17
 * Time: 9:58 AM
 */

namespace Hanaboso\PipesFramework\Commons\Metrics;

/**
 * Class UDPService
 *
 * @package Hanaboso\PipesFramework\Commons\Metrics
 */
class UDPService
{

    private const MEASUREMENT = 'php-worker';

    /**
     * @var UDPSender
     */
    private $sender;

    /**
     * @var string
     */
    private $message;

    /**
     * UDPService constructor.
     *
     * @param UDPSender $sender
     */
    public function __construct(UDPSender $sender)
    {
        $this->sender = $sender;
    }

    /**
     * @param string $machineHost
     * @param array  $fields
     *
     * @return bool
     */
    public function send(string $machineHost = '', array $fields): bool
    {
        $this->composeMessage($this->composePrefix($machineHost), $fields);

        return $this->sender->send($this->message);
    }

    /**
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * @param string $prefix
     * @param array  $fields
     */
    private function composeMessage(string $prefix, array $fields): void
    {
        $message = '';
        foreach ($fields as $key => $value) {
            $message .= $key . '=' . $value . ',';
        }
        $message       = substr($message, 0, -1);
        $nanoTimestamp = (round(microtime(TRUE) * 1000)) . '000000';

        $this->message = sprintf('%s %s %s', $prefix, $message, $nanoTimestamp);
    }

    /**
     * @param string $machineHost
     *
     * @return string
     */
    private function composePrefix(string $machineHost): string
    {
        if ($machineHost === '') {
            $machineHost = gethostname();
        }

        $machineName = $this->composeMachineName($machineHost);

        return sprintf('%s,name=%s,host=%s', self::MEASUREMENT, $machineName, $machineHost);
    }

    /**
     * @param string $machineName
     *
     * @return string
     */
    private function composeMachineName(string $machineName): string
    {
        $parts = explode('-', $machineName);
        if (count($parts) > 2) {
            array_pop($parts);
            array_pop($parts);
        }

        return implode('-', $parts);
    }

}