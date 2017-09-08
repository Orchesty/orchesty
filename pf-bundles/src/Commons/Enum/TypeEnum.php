<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Commons\Enum;

/**
 * Class TypeEnum
 *
 * @package Hanaboso\PipesFramework\Commons\Enum
 */
class TypeEnum extends EnumAbstract
{

    public const CONNECTOR  = 'connector';
    public const MAPPER     = 'mapper';
    public const XML_PARSER = 'xml_parser';
    public const API        = 'api';
    public const FTP        = 'ftp';
    public const EMAIL      = 'email';

    /**
     * @var string[]
     */
    protected static $choices = [
        self::CONNECTOR  => 'connector',
        self::MAPPER     => 'mapper',
        self::XML_PARSER => 'xml_parser',
        self::API        => 'api',
        self::FTP        => 'ftp',
        self::EMAIL      => 'email',
    ];

}