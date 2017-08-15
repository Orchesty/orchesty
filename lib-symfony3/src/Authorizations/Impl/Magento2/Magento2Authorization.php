<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Authorizations\Impl\Magento2;

use GuzzleHttp\Client;
use Hanaboso\PipesFramework\Commons\Authorization\Connectors\AuthorizationAbstract;
use Hanaboso\PipesFramework\Commons\ServiceStorage\ServiceStorageInterface;

/**
 * Class Magento2Authorization
 *
 * @package Hanaboso\PipesFramework\Connector\Impl\Magento2
 */
class Magento2Authorization extends AuthorizationAbstract implements Magento2AuthorizationInterface
{

    /**
     * @var string
     */
    private $token;

    /**
     * @var string
     */
    private $url;

    /**
     * @var string
     */
    private $username;

    /**
     * @var string
     */
    private $password;

    /**
     * Magento2Authorization constructor.
     *
     * @param string                  $id
     * @param ServiceStorageInterface $serviceStorage
     * @param string                  $url
     * @param string                  $username
     * @param string                  $password
     */
    public function __construct(
        string $id,
        ServiceStorageInterface $serviceStorage,
        string $url,
        string $username,
        string $password
    )
    {
        parent::__construct($id, $serviceStorage);
        $this->url      = $url;
        $this->username = $username;
        $this->password = $password;
    }

    /**
     * @return string
     */
    public function getAuthorizationType(): string
    {
        return self::BASIC;
    }

    /**
     * @return array
     */
    public function getHeaders(): array
    {

        if (empty($this->token)) {
            if (!$this->loadToken()) {
                $this->authenticate();
            }
        }

        $headers = [
            'Accept'        => 'application/json',
            'Content-Type'  => 'application/json',
            'Authorization' => 'Bearer ' . $this->token,
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
     * @return bool
     */
    public function isAuthorized(): bool
    {
        $this->loadToken();

        return !empty($this->token) || (!empty($this->url) && !empty($this->username) && !empty($this->password));
    }


    /**
     * --------------------------------------- HELPERS -------------------------------------------------
     */

    /**
     *
     */
    private function authenticate(): void
    {
        $httpClient = new Client();
        $response   = $httpClient->request('POST',
            $this->getAuthorizationUrl(),
            [
                'headers' => [
                    'Accept'       => 'application/json',
                    'Content-Type' => 'application/json',
                ],
                'body'    => '{"username":"' . $this->username . '", "password":"' . $this->password . '"}',
            ]
        );
        $data       = json_decode($response->getBody(), TRUE);
        $this->saveToken($data);
    }

    /**
     * @return string
     */
    private function getAuthorizationUrl(): string
    {
        return $this->url . '/rest/V1/integration/admin/token';
    }

    /**
     * @return bool
     */
    private function loadToken(): bool
    {
        if (empty($this->token)) {
            //@TODO: solve loading of Token
            $this->token = '';
        }

        return TRUE;
    }

    /**
     * @param array $data
     *
     * @return bool
     */
    private function saveToken(array $data): bool
    {
        //@TODO: solve saving of Token
        $this->token = $data[0];

        return FALSE;
    }

}