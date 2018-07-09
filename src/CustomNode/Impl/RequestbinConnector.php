<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\CustomNode\Impl;

use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesFramework\CustomNode\CustomNodeInterface;

/**
 * Class RequestbinConnector
 *
 * @package Hanaboso\PipesFramework\CustomNode\Impl
 */
class RequestbinConnector implements CustomNodeInterface
{

    /**
     * @var string
     */
    private $url;

    /**
     * @var CurlManager
     */
    private $curl;

    /**
     * RequestbinConnector constructor.
     *
     * @param string      $url
     * @param CurlManager $curl
     */
    public function __construct(string $url, CurlManager $curl)
    {
        $this->url  = $url;
        $this->curl = $curl;
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws GuzzleException
     * @throws CurlException
     */
    public function process(ProcessDto $dto): ProcessDto
    {
        $req = new RequestDto(CurlManager::METHOD_POST, new Uri($this->url));
        $req->setBody($dto->getData())
            ->setHeaders($dto->getHeaders());

        $this->curl->send($req);

        return $dto;
    }

}