<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: david.horacek
 * Date: 8/16/17
 * Time: 10:33 AM
 */

namespace Hanaboso\PipesFramework\HbPFAuthorizationBundle\Handler;

use Doctrine\ODM\MongoDB\MongoDBException;
use Hanaboso\CommonsBundle\Exception\PipesFrameworkException;
use Hanaboso\CommonsBundle\Utils\ControllerUtils;
use Hanaboso\PipesFramework\Authorization\Base\OAuthAuthorizationInterface;
use Hanaboso\PipesFramework\Authorization\Exception\AuthorizationException;
use Hanaboso\PipesFramework\HbPFAuthorizationBundle\Loader\AuthorizationLoader;

/**
 * Class AuthorizationHandler
 *
 * @package Hanaboso\PipesFramework\HbPFAuthorizationBundle\Handler
 */
class AuthorizationHandler
{

    /**
     * @var AuthorizationLoader
     */
    private $loader;

    /**
     * AuthorizationHandler constructor.
     *
     * @param AuthorizationLoader $authorizationRepository
     */
    function __construct(AuthorizationLoader $authorizationRepository)
    {
        $this->loader = $authorizationRepository;
    }

    /**
     * @param string $authId
     *
     * @throws AuthorizationException
     */
    public function authorize(string $authId): void
    {
        $authorization = $this->loader->getAuthorization($authId);

        if ($authorization instanceof OAuthAuthorizationInterface) {
            $authorization->authorize();
        }
    }

    /**
     * @param array  $data
     * @param string $authId
     *
     * @throws AuthorizationException
     * @throws PipesFrameworkException
     */
    public function saveSettings(array $data, string $authId): void
    {
        ControllerUtils::checkParameters(['field1', 'field2', 'field3'], $data);
        $authorization = $this->loader->getAuthorization($authId);
        $authorization->saveSettings($data);
    }

    /**
     * @param string $authId
     *
     * @return array
     * @throws AuthorizationException
     */
    public function getSettings(string $authId): array
    {
        return $this->loader->getAuthorization($authId)->getSettings();
    }

    /**
     * @param array  $data
     * @param string $authId
     *
     * @throws AuthorizationException
     */
    public function saveToken(array $data, string $authId): void
    {
        $authorization = $this->loader->getAuthorization($authId);

        if ($authorization instanceof OAuthAuthorizationInterface) {
            $authorization->saveToken($data);
        }
    }

    /**
     * @param string $hostname
     *
     * @return array
     * @throws AuthorizationException
     * @throws MongoDBException
     */
    public function getAuthInfo(string $hostname): array
    {
        $keys = $this->loader->getAllAuthorizationsInfo($hostname);
        $data = [
            'items'  => $keys,
            'total'  => count($keys),
            'count'  => count($keys),
            'offset' => 0,
        ];

        return $data;
    }

}