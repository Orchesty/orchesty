<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Connector\Impl\Magento2;

use Hanaboso\PipesFramework\Commons\Message\MessageInterface;

/**
 * Class Magento2ModulesConnector
 *
 * @package Hanaboso\PipesFramework\Connector\Impl\Magento2
 */
class Magento2ModulesConnector extends Magento2Base
{

    /**
     * @param string           $id
     * @param MessageInterface $message
     *
     * @return MessageInterface
     */
    public function processData(string $id, MessageInterface $message): MessageInterface
    {
        $data = $this->processRequest('GET', '/rest/V1/modules');

        $message->setData($data);

        return $message;
    }

}