<?php declare(strict_types=1);

namespace CleverCore\Commons\Exceptions;

/**
 * Class DirectoryException
 *
 * @package CleverCore\Commons\Exceptions
 */
final class DirectoryException extends RangerException
{

    private const OFFSET = self::OFFSET_COMMONS + 100;

    public const DIRECTORY_NOT_FOUND = self::OFFSET + 1;

}