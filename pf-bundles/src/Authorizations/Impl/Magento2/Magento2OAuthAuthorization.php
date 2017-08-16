<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 17.3.2017
 * Time: 14:35
 */

namespace Hanaboso\PipesFramework\Authorizations\Impl\Magento2;

use Hanaboso\PipesFramework\Commons\Authorization\Connectors\AuthorizationAbstract;
use Hanaboso\PipesFramework\Commons\Authorization\Exception\AuthorizationException;
use Hanaboso\PipesFramework\Commons\Authorization\UserAction\RedirectUserActionAuth;
use Hanaboso\PipesFramework\Commons\Authorization\UserAction\UserActionAuthObject;
use Hanaboso\PipesFramework\Commons\Authorization\UserAction\UserActionAuthorizationInterface;
use Hanaboso\PipesFramework\Commons\CustomRoute\CustomRoute;
use Hanaboso\PipesFramework\Commons\CustomRoute\CustomRouteableInterface;
use Hanaboso\PipesFramework\Commons\CustomRoute\RouteInterface;
use Hanaboso\PipesFramework\Commons\ServiceStorage\ServiceStorageInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class Magento2OAuthAuthorization
 *
 * @package Hanaboso\PipesFramework\Connector\Impl\Magento2
 */
class Magento2OAuthAuthorization extends AuthorizationAbstract implements UserActionAuthorizationInterface, CustomRouteableInterface, Magento2AuthorizationInterface
{

    /**
     * @var string
     */
    private $consumerKey;

    /**
     * @var string|null
     */
    private $accessToken;

    /**
     * @var string|null
     */
    private $accessTokenSecret;

    /**
     * @var string
     */
    private $url;

    /**
     * @var string
     */
    private $consumerSecret;

    /**
     * Magento2OAuthAuthorization constructor.
     *
     * @param string                  $id
     * @param ServiceStorageInterface $serviceStorage
     * @param string                  $url
     * @param string                  $consumerKey
     * @param string                  $consumerSecret
     */
    public function __construct(
        string $id,
        ServiceStorageInterface $serviceStorage,
        string $url,
        string $consumerKey,
        string $consumerSecret
    )
    {
        parent::__construct($id, $serviceStorage);

        $this->consumerKey    = $consumerKey;
        $this->consumerSecret = $consumerSecret;
        $this->url            = $url;
    }

    /**
     * @return string
     */
    public function getAuthorizationType(): string
    {
        return self::OAUTH2;
    }

    /**
     * @return array
     * @throws AuthorizationException
     */
    public function getHeaders(): array
    {
        if (empty($this->accessToken)) {
            throw new AuthorizationException();
        }

        $headers = [
            'Accept'        => 'application/json',
            'Content-Type'  => 'application/json',
            'Authorization' => 'Bearer ' . $this->accessToken,
        ];

        return $headers;
    }

    /**
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * @param null|string $accessToken
     * @param null|string $accessTokenSecret
     */
    public function setToken(?string $accessToken, ?string $accessTokenSecret): void
    {
        $this->accessToken       = $accessToken;
        $this->accessTokenSecret = $accessTokenSecret;
        $this->saveToken();
    }

    /**
     * @return bool
     */
    public function isAuthorized(): bool
    {
        $this->loadToken();

        return !empty($this->accessToken) && !empty($this->accessTokenSecret);
    }

    /**
     * @return UserActionAuthObject[]
     */
    public function getUserActions(): array
    {
        $str = $this->consumerKey . $this->consumerSecret;

        return [
            new RedirectUserActionAuth('http://www.magento.cz/?red=www.host.com/api/authorizations/magento/custom_routes/save_token' . $str),
        ];
    }

    /**
     * @return RouteInterface[]
     */
    public function getRoutes(): array
    {
        return [
            new CustomRoute('POST', 'save_token', 'Test POST'),
        ];
    }

    /**
     * @param RouteInterface $route
     * @param Request        $request
     *
     * @return array
     */
    public function routeReceive(RouteInterface $route, Request $request): array
    {
        $this->saveToken();

        return [
            'method'  => $request->getMethod(),
            'queries' => $request->query->all(),
            'request' => $request->request->all(),
            'cookies' => $request->cookies->all(),
        ];

    }

    /**
     * --------------------------------------- HELPERS -----------------------------------------
     */

    /**
     * @return bool
     */
    protected function saveToken(): bool
    {
        //@TODO: solve saving of Token

        return FALSE;
    }

    /**
     *
     */
    protected function loadToken(): void
    {
        //@TODO: solve loading of Token

        if (isset($data)) {
            $this->accessToken       = $data['access_token'];
            $this->accessTokenSecret = $data['access_token_secret'];
        } else {
            $this->accessToken       = NULL;
            $this->accessTokenSecret = NULL;
        }
    }

}