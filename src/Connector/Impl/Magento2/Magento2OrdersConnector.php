<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 16.3.2017
 * Time: 13:42
 */

namespace Hanaboso\PipesFramework\Connector\Impl\Magento2;

use Hanaboso\PipesFramework\Commons\Message\MessageInterface;

/**
 * Class Magento2OrdersConnector
 *
 * @package Hanaboso\PipesFramework\Connector\Impl\Magento2
 */
class Magento2OrdersConnector extends Magento2Base
{

    /**
     * @param string           $id
     * @param MessageInterface $message
     *
     * @return MessageInterface
     */
    public function processData(string $id, MessageInterface $message): MessageInterface
    {
        $data = $this->processRequest('GET', '/rest/V1/orders/');

        $message->setData(json_decode($data));

        return $message;
    }

}