<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: david.horacek
 * Date: 8/16/17
 * Time: 10:33 AM
 */

namespace Hanaboso\PipesFramework\HbPFAuthorizationBundle\Handler;

use Hanaboso\PipesFramework\Authorization\Base\OAuthAuthorizationInterface;
use Hanaboso\PipesFramework\HbPFAuthorizationBundle\Loader\AuthorizationLoader;
use Hanaboso\PipesFramework\Utils\ControllerUtils;

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
     */
    public function getSettings(string $authId): array
    {
        return $this->loader->getAuthorization($authId)->getSettings();
    }

    /**
     * @param array  $data
     * @param string $authId
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