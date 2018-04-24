<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: radek.jirsa
 * Date: 26.10.17
 * Time: 10:15
 */

namespace Tests\Integration\AppBundle\Model\Systems\Impl;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\CMEvents\CMEventObject;
use CleverConnectors\AppBundle\Model\Requester\RequesterInterface;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;

/**
 * Class NullRequester
 *
 * @package Tests\Integration\AppBundle\Model\Systems\Impl
 */
final class NullRequester implements RequesterInterface
{

    /**
     * @var array
     */
    private $headers;

    /**
     * NullRequester constructor.
     *
     * @param array $headers
     */
    public function __construct(array $headers)
    {
        $this->headers = $headers;
    }

    /**
     * @param array $data
     *
     * @return RequestDto
     */
    public function getRequestDto(array $data): RequestDto
    {
        /** @var CMEventObject $obj */
        $obj = $data[self::OBJECT];

        $req = new RequestDto(CurlManager::METHOD_POST, new Uri(''));
        $req->setHeaders($this->headers);

        return $req;
    }

    /**
     * @param ResponseDto   $responseDto
     * @param SystemInstall $systemInstall
     *
     * @return mixed
     */
    public function processResponse(ResponseDto $responseDto, SystemInstall $systemInstall)
    {
        return '';
    }

}