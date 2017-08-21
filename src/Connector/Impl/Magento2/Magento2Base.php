<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 16.3.2017
 * Time: 12:32
 */

namespace Hanaboso\PipesFramework\Connector\Impl\Magento2;

use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Authorizations\Impl\Magento2\Magento2AuthorizationInterface;
use Hanaboso\PipesFramework\Commons\Node\BaseNode;
use Hanaboso\PipesFramework\Commons\Transport\Curl\CurlManager;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesFramework\Connector\ConnectorInterface;
use Psr\Http\Message\StreamInterface;

/**
 * Class Magento2Base
 *
 * @package Hanaboso\PipesFramework\Connector\Impl\Magento2
 */
abstract class Magento2Base extends BaseNode implements ConnectorInterface
{

    /**
     * @var Magento2AuthorizationInterface
     */
    private $authorization;

    /**
     * @var CurlManager
     */
    private $curl;

    /**
     * Magento2Base constructor.
     *
     * @param string                         $id
     * @param Magento2AuthorizationInterface $authorization
     * @param CurlManager                    $curl
     */
    public function __construct(string $id, Magento2AuthorizationInterface $authorization, CurlManager $curl)
    {
        parent::__construct($id);
        $this->authorization = $authorization;
        $this->curl          = $curl;
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
     * @param string $body
     *
     * @return StreamInterface|string
     */
    protected function processRequest(string $method, string $urlPart, string $body = ''): StreamInterface
    {

        $dto = new RequestDto($method, new Uri($this->authorization->getUrl() . $urlPart));
        $dto
            ->setHeaders($this->authorization->getHeaders($dto->getMethod(), (string) $dto->getUri()))
            ->setBody($body);
        $response = $this->curl->send($dto);

        return $response->getBody();
    }

}