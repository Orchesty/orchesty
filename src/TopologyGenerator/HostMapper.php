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
                return 'connector-api';
            case TypeEnum::MAPPER:
                return 'mapper-api';
            case TypeEnum::XML_PARSER:
                return 'xml-parser-api';
            case TypeEnum::API:
                return 'monolith-api';
            case TypeEnum::FTP:
                return 'ftp-api';
            case TypeEnum::EMAIL:
                return 'mailer-api';
            case TypeEnum::CUSTOM:
                return 'monolith-api';
            default:
                throw new InvalidArgumentException(sprintf('Type "%s" does not exist.', $enum->getValue()));
                break;
        }
    }

    /**
     * @param TypeEnum $enum
     * @param string   $serviceId
     *
     * @return string
     */
    public function getRoute(TypeEnum $enum, string $serviceId): string
    {
        switch ($enum->getValue()) {
            case TypeEnum::CONNECTOR:
                $route = 'api/connector/{service_id}';
                break;
            case TypeEnum::MAPPER:
                $route = 'api/mapper/{service_id}';
                break;
            case TypeEnum::XML_PARSER:
                $route = 'api/xml-parser/{service_id}';
                break;
            case TypeEnum::API:
                $route = 'api/connector/{service_id}';
                break;
            case TypeEnum::FTP:
                $route = 'api/connector/{service_id}';
                break;
            case TypeEnum::EMAIL:
                $route = 'api/mailer/{service_id}';
                break;
            case TypeEnum::CUSTOM:
                $route = '/{service_id}/process';
                break;
            default:
                throw new InvalidArgumentException(sprintf('Type "%s" does not exist.', $enum->getValue()));
                break;
        }

        return preg_replace('/{service_id}/', $serviceId, $route);
    }

    /**
     * @param TypeEnum $enum
     * @param string   $serviceId
     *
     * @return string
     */
    public function getUrl(TypeEnum $enum, string $serviceId): string
    {
        return sprintf('http://%s/%s', $this->getHost($enum), $this->getRoute($enum, $serviceId));
    }

}
