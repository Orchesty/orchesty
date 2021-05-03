<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\CustomNode\Impl;

use GuzzleHttp\Psr7\Uri;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesPhpSdk\CustomNode\CustomNodeAbstract;

/**
 * Class RequestbinConnector
 *
 * @package Hanaboso\PipesPhpSdk\CustomNode\Impl
 */
final class RequestbinConnector extends CustomNodeAbstract
{

    /**
     * RequestbinConnector constructor.
     *
     * @param string      $url
     * @param CurlManager $curl
     */
    public function __construct(private string $url, private CurlManager $curl)
    {
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
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
