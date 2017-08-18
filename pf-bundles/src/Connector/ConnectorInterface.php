<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: david.horacek
 * Date: 8/18/17
 * Time: 2:40 PM
 */

namespace Hanaboso\PipesFramework\Connector;

use GuzzleHttp\Exception\ConnectException;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;

/**
 * Interface ConnectorInterface
 *
 * @package Hanaboso\PipesFramework\Connector
 */
interface ConnectorInterface
{

    /**
     * @param string[] $data
     *
     * @return ProcessDto|void
     * @throws ConnectException
     */
    public function processEvent(array $data): ProcessDto;

}