<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: venca
 * Date: 8.9.17
 * Time: 9:06
 */

namespace Hanaboso\PipesFramework\TopologyGenerator;

use Hanaboso\PipesFramework\Commons\Enum\TypeEnum;
use InvalidArgumentException;

/**
 * Class HostMapper
 *
 * @package Hanaboso\PipesFramework\TopologyGenerator
 */
class HostMapper
{

    /**
     * @param TypeEnum $enum
     *
     * @return bool
     */
    public static function isPhpType(TypeEnum $enum): bool
    {
        return in_array($enum->getValue(), [
                TypeEnum::CONNECTOR,
                TypeEnum::MAPPER,
                TypeEnum::API,
                TypeEnum::FTP,
                TypeEnum::EMAIL,
            ]
        );
    }

    /**
     * @param TypeEnum $enum
     *
     * @return string
     * @throws \Exception
     */
    public function getHost(TypeEnum $enum): string
    {
        switch ($enum->getValue()) {
            case TypeEnum::CONNECTOR:
                return 'pipes-api';
            case TypeEnum::MAPPER:
                return 'pipes-api';
            case TypeEnum::XML_PARSER:
                return 'xml-parser';
            case TypeEnum::API:
                return 'pipes-api';
            case TypeEnum::FTP:
                return 'pipes-api';
            case TypeEnum::EMAIL:
                return 'pipes-api';
            default:
                throw new InvalidArgumentException(sprintf('Type "%s" does not exist.', $enum->getValue()));
                break;
        }
    }

    /**
     * @param TypeEnum $enum
     *
     * @return string
     * @throws \Exception
     */
    public function getRoute(TypeEnum $enum): string
    {
        switch ($enum->getValue()) {
            case TypeEnum::CONNECTOR:
                return 'api/connector';
            case TypeEnum::MAPPER:
                return 'api/mapper';
            case TypeEnum::XML_PARSER:
                return 'api/parser';
            case TypeEnum::API:
                return 'api/connector';
            case TypeEnum::FTP:
                return 'api/connector';
            case TypeEnum::EMAIL:
                return 'api/mailer';
            default:
                throw new InvalidArgumentException(sprintf('Type "%s" does not exist.', $enum->getValue()));
                break;
        }
    }

    /**
     * @param TypeEnum $enum
     *
     * @return string
     */
    public function getUrl(TypeEnum $enum): string
    {
        return sprintf('%s/%s', $this->getHost($enum), $this->getRoute($enum));
    }

}