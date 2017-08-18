<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 17.3.2017
 * Time: 14:35
 */

namespace Hanaboso\PipesFramework\Authorizations\Impl\Magento2;

use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\PipesFramework\Authorizations\Provider\Dto\OAuth1Dto;
use Hanaboso\PipesFramework\Authorizations\Provider\OAuth1Provider;
use Hanaboso\PipesFramework\Commons\Authorization\Connectors\OAuthAuthorizationAbstract;
use Hanaboso\PipesFramework\Commons\Authorization\Exception\AuthorizationException;

/**
 * Class Magento2OAuthAuthorization
 *
 * @package Hanaboso\PipesFramework\Connector\Impl\Magento2
 */
class Magento2OAuthAuthorization extends OAuthAuthorizationAbstract implements Magento2AuthorizationInterface
{

    private const URL             = 'url';
    private const CONSUMER_KEY    = 'consumer_key';
    private const CONSUMER_SECRET = 'consumer_secret';

    /**
     * @var OAuth1Provider
     */
    private $auth1Provider;

    /**
     * Magento2OAuthAuthorization constructor.
     *
     * @param DocumentManager $dm
     * @param OAuth1Provider  $auth1Provider
     * @param string          $id
     * @param string          $name
     * @param string          $description
     * @param string          $url
     * @param string          $consumerKey
     * @param string          $consumerSecret
     */
    public function __construct(
        DocumentManager $dm,
        OAuth1Provider $auth1Provider,
        string $id,
        string $name,
        string $description,
        string $url,
        string $consumerKey,
        string $consumerSecret
    )
    {
        parent::__construct($id, $name, $description, $dm);

        $this->setConfig([
            self::URL             => $url,
            self::CONSUMER_KEY    => $consumerKey,
            self::CONSUMER_SECRET => $consumerSecret,
        ]);
        $this->auth1Provider = $auth1Provider;
    }

    /**
     * @return string
     */
    public function getAuthorizationType(): string
    {
        return self::OAUTH;
    }

    /**
     * @param string $method
     * @param string $url
     *
     * @return array
     * @throws AuthorizationException
     */
    public function getHeaders(string $method, string $url): array
    {

        if (!$this->isAuthorized()) {
            //TODO log this
            throw new AuthorizationException();
        }

        $headers = [
            'Accept'        => 'application/json',
            'Content-Type'  => 'application/json',
            'Authorization' => $this->auth1Provider->getAuthorizeHeader($this->buildDto(), $method, $url),
        ];

        return $headers;
    }

    /**
     * @return string
     */
    public function getUrl(): string
    {
        return $this->getParam($this->getConfig(), self::URL);
    }

    /**
     * @return bool
     */
    public function isAuthorized(): bool
    {
        $this->load();

        if (
            empty($this->getParam($this->authorization->getToken(), OAuth1Provider::OAUTH_TOKEN)) ||
            empty($this->getParam($this->authorization->getToken(), OAuth1Provider::OAUTH_TOKEN_SECRET))
        ) {
            return FALSE;
        };

        return TRUE;
    }

    /**
     *
     */
    public function authorize(): void
    {
        $this->load();
        $this->auth1Provider->authorize($this->buildDto(), $this->getRequestTokenUrl(), $this->getAuthorizeUrl(), []);
    }

    /**
     * @param string[] $data
     */
    public function saveToken(array $data): void
    {
        $this->load();
        $this->save($this->auth1Provider->getAccessToken($this->buildDto(), $data, $this->getAccessTokenUrl()));
    }

    /**
     * ------------------------------------------ HELPERS ----------------------------------------
     */

    /**
     * @return OAuth1Dto
     */
    private function buildDto(): OAuth1Dto
    {
        return new OAuth1Dto(
            $this->authorization,
            $this->getParam($this->getConfig(), self::CONSUMER_KEY),
            $this->getParam($this->getConfig(), self::CONSUMER_SECRET)
        );
    }

    /**
     * @return string
     */
    private function getRequestTokenUrl(): string
    {
        return $this->getParam($this->getConfig(), self::URL) . '/oauth/initiate';
    }

    /**
     * @return string
     */
    private function getAuthorizeUrl(): string
    {
        return $this->getParam($this->getConfig(), self::URL) . '/admin/oauth_authorize';
    }

    /**
     * @return string
     */
    private function getAccessTokenUrl(): string
    {
        return $this->getParam($this->getConfig(), self::URL) . '/oauth/token';
    }

}