<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Commons\Enum;

/**
 * Class TypeEnum
 *
 * @package Hanaboso\PipesFramework\Commons\Enum
 */
class TypeEnum extends EnumAbstract
{

    public const API             = 'api';
    public const BATCH           = 'batch';
    public const BATCH_CONNECTOR = 'batch_connector';
    public const CONNECTOR       = 'connector';
    public const CRON            = 'cron';
    public const CUSTOM          = 'custom';
    public const DEBUG           = 'debug';
    public const EMAIL           = 'email';
    public const FTP             = 'ftp';
    public const MAPPER          = 'mapper';
    public const RESEQUENCER     = 'resequencer';
    public const SIGNAL          = 'signal';
    public const SPLITTER        = 'splitter';
    public const TABLE_PARSER    = 'table_parser';
    public const WEBHOOK         = 'webhook';
    public const XML_PARSER      = 'xml_parser';
    public const START           = 'start';
    public const GATEWAY         = 'gateway';

    /**
     * @var string[]
     */
    protected static $choices = [
        self::API             => self::API,
        self::BATCH           => self::BATCH,
        self::BATCH_CONNECTOR => self::BATCH_CONNECTOR,
        self::CONNECTOR       => self::CONNECTOR,
        self::CRON            => self::CRON,
        self::CUSTOM          => self::CUSTOM,
        self::DEBUG           => self::DEBUG,
        self::EMAIL           => self::EMAIL,
        self::FTP             => self::FTP,
        self::MAPPER          => self::MAPPER,
        self::RESEQUENCER     => self::RESEQUENCER,
        self::SIGNAL          => self::SIGNAL,
        self::SPLITTER        => self::SPLITTER,
        self::TABLE_PARSER    => self::TABLE_PARSER,
        self::WEBHOOK         => self::WEBHOOK,
        self::XML_PARSER      => self::XML_PARSER,
        self::START           => self::START,
        self::GATEWAY         => self::GATEWAY,
    ];

}