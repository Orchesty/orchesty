<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: marcel.pavlicek
 * Date: 3/13/17
 * Time: 6:07 PM
 */

namespace Hanaboso\PipesFramework\Mailer\Transport;

/**
 * Interface TransportInterface
 *
 * @package Hanaboso\PipesFramework\Mailer\Transport
 */
interface TransportInterface
{

    /**
     * @param TransportMessageInterface $message
     *
     * @return mixed
     */
    public function send(TransportMessageInterface $message);

}

