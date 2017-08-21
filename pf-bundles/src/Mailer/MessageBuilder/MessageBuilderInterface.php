<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: marcel.pavlicek
 * Date: 3/13/17
 * Time: 6:11 PM
 */

namespace Hanaboso\PipesFramework\Mailer\MessageBuilder;

use Hanaboso\PipesFramework\Mailer\Transport\TransportMessageInterface;

/**
 * Interface MessageBuilderInterface
 *
 * @package Hanaboso\PipesFramework\Mailer\MessageBuilder
 */
interface MessageBuilderInterface
{

    /**
     * @param array $data
     *
     * @return TransportMessageInterface
     */
    public function buildTransportMessage(array $data): TransportMessageInterface;

}
