<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Mailer\MessageBuilder;

use Hanaboso\PipesFramework\Commons\Exception\PipesFrameworkException;

/**
 * Class MessageBuilderException
 *
 * @package Hanaboso\PipesFramework\Mailer\MessageBuilder
 */
final class MessageBuilderException extends PipesFrameworkException
{

    protected const OFFSET = 500;

    public const INVALID_DATA = self::OFFSET + 1;

}
