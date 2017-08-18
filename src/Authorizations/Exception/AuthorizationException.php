<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Authorizations\Exception;

use Hanaboso\PipesFramework\Commons\Exception\PipesFrameworkException;

/**
 * Class ConnectorException
 *
 * @package Hanaboso\PipesFramework\HbPFConnectorBundle\Exception
 */
class AuthorizationException extends PipesFrameworkException
{

    protected const OFFSET = 200;

    public const AUTHORIZATION_SERVICE_NOT_FOUND = self::OFFSET + 1;
    public const AUTHORIZATION_OAUTH1_ERROR      = self::OFFSET + 2;

}