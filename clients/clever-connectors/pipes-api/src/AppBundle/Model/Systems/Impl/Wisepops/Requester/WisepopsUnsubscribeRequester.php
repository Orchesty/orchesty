<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: radek.jirsa
 * Date: 30.10.17
 * Time: 13:17
 */

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Wisepops\Requester;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\Requester\RequesterInterface;
use CleverConnectors\AppBundle\Model\Requester\RequesterTrait;
use CleverConnectors\AppBundle\Model\Systems\Impl\Wisepops\WisepopsSystem;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;

/**
 * Class WisepopsUnsubscribeRequester
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Wisepops\Requester
 */
final class WisepopsUnsubscribeRequester implements RequesterInterface
{

    use RequesterTrait;

    /**
     * @var array
     */
    private $headers;

    /**
     * WisepopsUnsubscribeRequester constructor.
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
     * @throws CleverConnectorsException
     * @throws CurlException
     */
    public function getRequestDto(array $data): RequestDto
    {
        $url = sprintf(WisepopsSystem::WEBHOOK_URL . '?hook_id=%s', $this->getWebhookId($data));
        $dto = new RequestDto(CurlManager::METHOD_DELETE, new Uri($url));
        $dto->setHeaders($this->headers);

        return $dto;
    }

    /**
     * @param ResponseDto   $responseDto
     * @param SystemInstall $systemInstall
     *
     * @return mixed
     */
    public function processResponse(ResponseDto $responseDto, SystemInstall $systemInstall)
    {
        if ($responseDto->getStatusCode() == 200) {
            return TRUE;
        }

        return FALSE;
    }

}