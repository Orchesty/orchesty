<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: venca
 * Date: 15.9.17
 * Time: 11:05
 */

namespace Hanaboso\PipesFramework\Commons\Monolog;

use Hanaboso\PipesFramework\Commons\Metrics\UDPSender;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;

/**
 * Class UdpHandler
 *
 * @package Hanaboso\PipesFramework\Commons\Monolog
 */
class UdpHandler extends AbstractProcessingHandler
{

    /**
     * @var UDPSender
     */
    private $UDPSender;

    /**
     * UdpHandler constructor.
     *
     * @param UDPSender $UDPSender
     * @param int       $level
     * @param bool      $bubble
     */
    public function __construct(UDPSender $UDPSender, $level = Logger::DEBUG, $bubble = TRUE)
    {
        parent::__construct($level, $bubble);
        $this->UDPSender = $UDPSender;
    }

    /**
     * Writes the record down to the log of the implementing handler
     *
     * @param  array $record
     *
     * @return void
     */
    protected function write(array $record): void
    {
        $this->UDPSender->send($record['formatted']);
    }

}