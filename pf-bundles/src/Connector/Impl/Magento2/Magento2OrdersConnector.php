<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 16.3.2017
 * Time: 13:42
 */

namespace Hanaboso\PipesFramework\Connector\Impl\Magento2;

use GuzzleHttp\Exception\GuzzleException;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;

/**
 * Class Magento2OrdersConnector
 *
 * @package Hanaboso\PipesFramework\Connector\Impl\Magento2Old
 */
class Magento2OrdersConnector extends Magento2Base
{

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws GuzzleException
     * @throws CurlException
     */
    public function processAction(ProcessDto $dto): ProcessDto
    {
        $data = $this->processRequest('GET', '/rest/V1/orders/');

        $dto = new ProcessDto();
        $dto->setData(json_decode($data, TRUE));

        return $dto;
    }

}