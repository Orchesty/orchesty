<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: david.horacek
 * Date: 8/15/17
 * Time: 10:41 AM
 */

namespace Hanaboso\PipesFramework\HbPFConnectorBundle\Exception;

use Hanaboso\PipesFramework\Commons\Exception\PipesFrameworkException;

/**
 * Class ConnectorException
 *
 * @package Hanaboso\PipesFramework\HbPFConnectorBundle\Exception
 */
class AuthorizationException extends PipesFrameworkException
{

    protected const OFFSET = 1200;

    public const AUTHORIZATION_SERVICE_NOT_FOUND = self::OFFSET + 1;

}