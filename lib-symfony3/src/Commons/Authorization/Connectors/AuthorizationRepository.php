<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 16.3.2017
 * Time: 16:25
 */

namespace Hanaboso\PipesFramework\Commons\Authorization\Connectors;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class AuthorizationRepository
 *
 * @package Hanaboso\PipesFramework\Commons\Authorization\Connectors
 */
class AuthorizationRepository
{

    private const AUTHORIZATION_SERVICE_PREFIX = 'hbpf.authorizations.';

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * AuthorizationRepository constructor.
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @param string $authorizationStr
     *
     * @return AuthorizationInterface
     */
    public function get(string $authorizationStr): AuthorizationInterface
    {
        /** @var AuthorizationInterface $authorization */
        $authorization = $this->container->get(self::AUTHORIZATION_SERVICE_PREFIX . $authorizationStr);

        return $authorization;
    }

    /**
     * @return array
     */
    public function getAuthorizationIDs(): array
    {
        $res = [];
        //@TODO: refactor
        //		foreach ($this->container->getServiceIds() as $serviceId) {
        //			if (substr($serviceId, 0,
        //					strlen(self::AUTHORIZATION_SERVICE_PREFIX)) === self::AUTHORIZATION_SERVICE_PREFIX
        //			) {
        //				$res[] = $serviceId;
        //			}
        //		}

        return $res;
    }

    /**
     * @return array
     */
    public function getAuthorizations(): array
    {
        $res = [];
        foreach ($this->getAuthorizationIDs() as $serviceId) {
            $res[] = $this->container->get($serviceId);
        }

        return $res;
    }

}