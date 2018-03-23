<?php declare(strict_types=1);

namespace CleverCore\Commons\Enums;

use CleverApp\Enum\BaseEnum;

/**
 * Class DirectorySourceEnum
 *
 * @package CleverCore\Commons\Enums
 */
final class DirectorySourceEnum extends BaseEnum
{

    public const WORKFLOW = 'DIRECTORY_SOURCE_WORKFLOW';

    /**
     * @var array
     */
    protected static $values = [
        self::WORKFLOW,
    ];

    /**
     * @var array
     */
    protected static $convert = [
        self::WORKFLOW => 0,
    ];

}