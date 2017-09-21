<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 16.3.2017
 * Time: 13:42
 */

namespace Hanaboso\PipesFramework\Connector\Impl\Magento2;

use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Connector\Exception\ConnectorException;

/**
 * Class Magento2OrdersConnector
 *
 * @package Hanaboso\PipesFramework\Connector\Impl\Magento2
 */
class Magento2OrdersConnector extends Magento2Base
{

    /**
     * @param array $data
     *
     * @return ProcessDto
     */
    public function processAction(array $data): ProcessDto
    {
        $data = $this->processRequest('GET', '/rest/V1/orders/');

        $dto = new ProcessDto();
        $dto->setData(json_decode($data, TRUE));

        return $dto;
    }

    /**
     * @param array $data
     *
     * @return ProcessDto|void
     * @throws ConnectorException
     */
    public function processEvent(array $data): ProcessDto
    {
        throw new ConnectorException(
            'Connector doesn\'n have process event',
            ConnectorException::CONNECTOR_DOES_NOT_HAVE_PROCESS_EVENT
        );
    }

}