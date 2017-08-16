<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: marcel.pavlicek
 * Date: 3/13/17
 * Time: 6:11 PM
 */

namespace Hanaboso\PipesFramework\Mailer\MessageHandler;

use Hanaboso\PipesFramework\Mailer\Transport\TransportMessageInterface;

/**
 * Interface MessageHandlerInterface
 *
 * @package Hanaboso\PipesFramework\Mailer\MessageHandler
 */
interface MessageHandlerInterface
{

    /**
     * @param array $data
     *
     * @return TransportMessageInterface
     */
    public function buildTransportMessage(array $data): TransportMessageInterface;

}
