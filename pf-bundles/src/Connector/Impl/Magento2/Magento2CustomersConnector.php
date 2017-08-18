<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 16.3.2017
 * Time: 13:29
 */

namespace Hanaboso\PipesFramework\Connector\Impl\Magento2;

use Hanaboso\PipesFramework\Commons\Message\MessageInterface;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\HbPFConnectorBundle\Exception\ConnectorException;

/**
 * Class Magento2CustomersConnector
 *
 * @package Hanaboso\PipesFramework\Connector\Impl\Magento2
 */
class Magento2CustomersConnector extends Magento2Base
{

    /**
     * @param string           $id
     * @param MessageInterface $message
     *
     * @return MessageInterface
     */
    public function processData(string $id, MessageInterface $message): MessageInterface
    {
        $data = $this->processRequest('GET', '/rest/V1/customers/1');

        $message->setData($data);

        return $message;
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