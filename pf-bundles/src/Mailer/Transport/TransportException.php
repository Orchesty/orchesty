<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Mailer\Transport;

use Hanaboso\PipesFramework\Commons\Exception\PipesFrameworkException;

/**
 * Class TransportException
 *
 * @package Hanaboso\PipesFramework\Mailer\Transport
 */
class TransportException extends PipesFrameworkException
{

    protected const OFFSET = 600;

    public const SEND_FAILED = self::OFFSET + 1;

}
