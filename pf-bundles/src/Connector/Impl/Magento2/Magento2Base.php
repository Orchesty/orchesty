<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 16.3.2017
 * Time: 12:32
 */

namespace Hanaboso\PipesFramework\Connector\Impl\Magento2;

use GuzzleHttp\Client;
use Hanaboso\PipesFramework\Authorizations\Impl\Magento2\Magento2AuthorizationInterface;
use Hanaboso\PipesFramework\Commons\Node\BaseNode;
use Psr\Http\Message\StreamInterface;

/**
 * Class Magento2Base
 *
 * @package Hanaboso\PipesFramework\Connector\Impl\Magento2
 */
abstract class Magento2Base extends BaseNode
{

    /**
     * @var Magento2AuthorizationInterface
     */
    private $authorization;

    /**
     * Magento2Base constructor.
     *
     * @param string                         $id
     * @param Magento2AuthorizationInterface $authorization
     */
    public function __construct(string $id, Magento2AuthorizationInterface $authorization)
    {
        parent::__construct($id);
        $this->authorization = $authorization;
    }

    /**
     * @return string
     */
    public function getServiceType(): string
    {
        return self::CONNECTOR;
    }

    /**
     * @param string $method
     * @param string $urlPart
     * @param null   $body
     *
     * @return StreamInterface|string
     */
    protected function processRequest($method, $urlPart, $body = NULL): StreamInterface
    {
        $httpClient = new Client();
        $params     = ['headers' => $this->authorization->getHeaders()];

        if ($body) {
            $params['body'] = $body;
        }

        $response = $httpClient->request($method, $this->authorization->getUrl() . $urlPart, $params);

        return $response->getBody();
    }

}