<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 17.3.2017
 * Time: 14:35
 */

namespace Hanaboso\PipesFramework\Authorizations\Impl\Magento2;

use Doctrine\ODM\MongoDB\DocumentManager;
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
    private const ACCESS_TOKEN    = 'access_token';
    //private const ACCESS_TOKEN_SECRET = 'access_token_secret';

    /**
     * Magento2OAuthAuthorization constructor.
     *
     * @param DocumentManager $dm
     * @param string          $id
     * @param string          $url
     * @param string          $consumerKey
     * @param string          $consumerSecret
     *
     * @throws AuthorizationException
     */
    public function __construct(
        DocumentManager $dm,
        string $id,
        string $url,
        string $consumerKey,
        string $consumerSecret
    )
    {
        parent::__construct($id, $dm);

        $this->setConfig([
            self::URL             => $url,
            self::CONSUMER_KEY    => $consumerKey,
            self::CONSUMER_SECRET => $consumerSecret,
        ]);
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
        if (empty($this->getParam($this->getConfig(), self::ACCESS_TOKEN))) {
            throw new AuthorizationException();
        }

        $headers = [
            'Accept'        => 'application/json',
            'Content-Type'  => 'application/json',
            'Authorization' => 'Bearer ' . $this->getParam($this->authorization->getToken(), self::ACCESS_TOKEN),
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
     *
     */
    public function authorize(): void
    {
        //TODO redirect for oauth
    }

    /**
     * @param string[] $data
     *
     * @return string
     */
    public function saveToken(array $data): string
    {
        $this->save($data);

        return '';
    }

    /**
     *
     */
    protected function setInfo(): void
    {
        $this->name        = 'magento2 - oauth';
        $this->description = 'magento2 - oauth';
    }

}