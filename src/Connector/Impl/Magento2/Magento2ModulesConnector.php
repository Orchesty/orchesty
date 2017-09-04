<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Connector\Impl\Magento2;

use Hanaboso\PipesFramework\Commons\Message\MessageInterface;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Connector\Exception\ConnectorException;

/**
 * Class Magento2ModulesConnector
 *
 * @package Hanaboso\PipesFramework\Connector\Impl\Magento2
 */
class Magento2ModulesConnector extends Magento2Base
{

    /**
     * @param MessageInterface $message
     *
     * @return MessageInterface
     */
    public function processData(MessageInterface $message): MessageInterface
    {
        $data = $this->processRequest('GET', '/rest/V1/modules');

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