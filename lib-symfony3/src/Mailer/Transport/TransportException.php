<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Mailer\Transport;

use RuntimeException;

/**
 * Class TransportException
 *
 * @package Hanaboso\PipesFramework\Mailer\Transport
 */
class TransportException extends RuntimeException
{

    public const SEND_FAILED = 1;

}
