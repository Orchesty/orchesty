<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: david.horacek
 * Date: 8/16/17
 * Time: 10:33 AM
 */

namespace Hanaboso\PipesFramework\HbPFAuthorizationBundle\Handler;

use Hanaboso\PipesFramework\Commons\Authorization\Connectors\OAuthAuthorizationInterface;
use Hanaboso\PipesFramework\HbPFConnectorBundle\Loaders\AuthorizationLoader;

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
     *
     * @return string
     */
    public function saveToken(array $data, string $authId): string
    {
        $authorization = $this->loader->getAuthorization($authId);
        $res           = '';

        if ($authorization instanceof OAuthAuthorizationInterface) {
            $res = $authorization->saveToken($data);
        }

        return $res;
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