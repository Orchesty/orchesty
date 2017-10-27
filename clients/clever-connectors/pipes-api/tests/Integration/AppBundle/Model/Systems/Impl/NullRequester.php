<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: radek.jirsa
 * Date: 26.10.17
 * Time: 10:15
 */

namespace Tests\Integration\AppBundle\Model\Systems\Impl;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\Requester\RequesterInterface;
use Hanaboso\PipesFramework\Commons\Transport\Curl\CurlManager;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\ResponseDto;

/**
 * Class NullRequester
 *
 * @package Tests\Integration\AppBundle\Model\Systems\Impl
 */
class NullRequester implements RequesterInterface
{

    /**
     * @param array $data
     *
     * @return RequestDto
     */
    public function getRequestDto(array $data): RequestDto
    {
        $req = new RequestDto(CurlManager::METHOD_POST, $data['uri']);
        $req->setHeaders($data['headers']);

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
        return $systemInstall;
    }

}