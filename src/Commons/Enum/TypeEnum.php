<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Commons\Enum;

/**
 * Class TypeEnum
 *
 * @package Hanaboso\PipesFramework\Commons\Enum
 */
class TypeEnum extends EnumAbstract
{

    public const API          = 'api';
    public const CONNECTOR    = 'connector';
    public const CRON         = 'cron';
    public const CUSTOM       = 'custom';
    public const EMAIL        = 'email';
    public const FTP          = 'ftp';
    public const MAPPER       = 'mapper';
    public const SPLITTER     = 'splitter';
    public const TABLE_PARSER = 'table_parser';
    public const WEBHOOK      = 'webhook';
    public const XML_PARSER   = 'xml_parser';
    /**
     * @var string[]
     */
    protected static $choices = [
        self::API          => 'api',
        self::CONNECTOR    => 'connector',
        self::CRON         => 'cron',
        self::CUSTOM       => 'custom',
        self::EMAIL        => 'email',
        self::FTP          => 'ftp',
        self::MAPPER       => 'mapper',
        self::SPLITTER     => 'splitter',
        self::TABLE_PARSER => 'table_parser',
        self::WEBHOOK      => 'webhook',
        self::XML_PARSER   => 'xml_parser',
    ];

}