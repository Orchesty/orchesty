<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 16.3.2017
 * Time: 12:32
 */

namespace Hanaboso\PipesFramework\Connector\Impl\Magento2;

use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesFramework\Authorization\Impl\Magento2\Magento2AuthorizationInterface;
use Hanaboso\PipesFramework\Connector\ConnectorInterface;
use Hanaboso\PipesFramework\Connector\Exception\ConnectorException;
use Psr\Http\Message\StreamInterface;

/**
 * Class Magento2Base
 *
 * @package Hanaboso\PipesFramework\Connector\Impl\Magento2Old
 */
abstract class Magento2Base implements ConnectorInterface
{

    /**
     * @var string
     */
    private $id;

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
        $this->id            = $id;
        $this->authorization = $authorization;
        $this->curl          = $curl;
    }

    // @codingStandardsIgnoreStart

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws ConnectorException
     */
    public function processEvent(ProcessDto $dto): ProcessDto
    {
        throw new ConnectorException(
            'Magento2Old has no support for webhooks!',
            ConnectorException::CONNECTOR_DOES_NOT_HAVE_PROCESS_EVENT
        );
    }
    // @codingStandardsIgnoreEnd

    /**
     * @param string $method
     * @param string $urlPart
     * @param string $body
     *
     * @return StreamInterface|string
     * @throws GuzzleException
     * @throws CurlException
     */
    protected function processRequest(string $method, string $urlPart, string $body = '')
    {

        $dto = new RequestDto($method, new Uri($this->authorization->getUrl() . $urlPart));
        $dto
            ->setHeaders($this->authorization->getHeaders($dto->getMethod(), (string) $dto->getUri()))
            ->setBody($body);
        $response = $this->curl->send($dto);

        return $response->getBody();
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

}