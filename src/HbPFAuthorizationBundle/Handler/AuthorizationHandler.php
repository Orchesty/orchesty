<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: david.horacek
 * Date: 8/16/17
 * Time: 10:33 AM
 */

namespace Hanaboso\PipesFramework\HbPFAuthorizationBundle\Handler;

use Hanaboso\PipesFramework\Commons\Authorization\Connectors\OAuthAuthorizationInterface;
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
    function __construct(
        AuthorizationLoader $authorizationRepository
    )
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
    public function saveToken(array $data, string $authId): void
    {
        $authorization = $this->loader->getAuthorization($authId);

        if ($authorization instanceof OAuthAuthorizationInterface) {
            $authorization->saveToken($data);
        }
    }

    /**
     * @return string[]
     */
    public function getAuthInfo(): array
    {
        $keys = $this->loader->getAllAuthorizationsInfo();

        return $keys;
    }

}