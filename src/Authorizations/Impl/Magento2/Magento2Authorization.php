<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Authorizations\Impl\Magento2;

use Doctrine\ODM\MongoDB\DocumentManager;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Commons\Authorization\Connectors\AuthorizationAbstract;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesFramework\Commons\Transport\CurlManagerInterface;
use PHPUnit_Framework_MockObject_MockObject;

/**
 * Class Magento2Authorization
 *
 * @package Hanaboso\PipesFramework\Connector\Impl\Magento2
 */
class Magento2Authorization extends AuthorizationAbstract implements Magento2AuthorizationInterface
{

    private const URL      = 'url';
    private const USERNAME = 'username';
    private const PASSWORD = 'password';
    private const TOKEN    = 'token';

    /**
     * @var CurlManagerInterface|PHPUnit_Framework_MockObject_MockObject
     */
    private $curl;

    /**
     * Magento2Authorization constructor.
     *
     * @param DocumentManager                                              $documentManager
     * @param CurlManagerInterface|PHPUnit_Framework_MockObject_MockObject $curl
     * @param string                                                       $id
     * @param string                                                       $url
     * @param string                                                       $username
     * @param string                                                       $password
     */
    public function __construct(
        DocumentManager $documentManager,
        CurlManagerInterface $curl,
        string $id,
        string $url,
        string $username,
        string $password
    )
    {
        parent::__construct($id, $documentManager);
        $this->curl = $curl;
        $this->setConfig([
            self::URL      => $url,
            self::USERNAME => $username,
            self::PASSWORD => $password,
        ]);
    }

    /**
     * @return string
     */
    public function getAuthorizationType(): string
    {
        return self::BASIC;
    }

    /**
     * @return bool
     */
    public function isAuthorized(): bool
    {
        return $this->load();
    }

    /**
     * @return array
     */
    public function getHeaders(): array
    {
        if (!$this->isAuthorized()) {
            $this->authenticate();
        }

        $headers = [
            'Accept'        => 'application/json',
            'Content-Type'  => 'application/json',
            'Authorization' => 'Bearer ' . $this->getParam($this->authorization->getToken(), self::TOKEN),
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
     * --------------------------------------- HELPERS -------------------------------------------------
     */

    /**
     *
     */
    private function authenticate(): void
    {
        $dto = new RequestDto('POST', new Uri($this->getAuthorizationUrl()));
        $dto
            ->setHeaders([
                'Accept'       => 'application/json',
                'Content-Type' => 'application/json',
            ])
            ->setBody(
                sprintf(
                    '{"username":"%s", "password":"%s"}',
                    $this->getParam($this->getConfig(), self::USERNAME),
                    $this->getParam($this->getConfig(), self::PASSWORD)
                )
            );
        $response = $this->curl->send($dto);
        $data     = json_decode($response->getBody(), TRUE);

        $this->save($data);
    }

    /**
     * @return string
     */
    private function getAuthorizationUrl(): string
    {
        return $this->getParam($this->getConfig(), self::URL) . '/rest/V1/integration/admin/token';
    }

    /**
     *
     */
    protected function setInfo(): void
    {
        $this->name        = 'magento2 - auth';
        $this->description = 'magento2 - auth';
    }

}