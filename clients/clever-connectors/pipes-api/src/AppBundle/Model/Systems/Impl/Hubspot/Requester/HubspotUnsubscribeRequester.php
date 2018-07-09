<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: radek.jirsa
 * Date: 30.10.17
 * Time: 13:28
 */

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Hubspot\Requester;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\Requester\RequesterInterface;
use CleverConnectors\AppBundle\Model\Requester\RequesterTrait;
use CleverConnectors\AppBundle\Model\Systems\Impl\Hubspot\HubspotSystem;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;

/**
 * Class HubspotUnsubscribeRequester
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Hubspot\Requester
 */
final class HubspotUnsubscribeRequester implements RequesterInterface
{

    use RequesterTrait;

    private const WEBHOOK_SUBSCRIPTION_DELETE_URL = 'https://api.hubapi.com/webhooks/v1/%s/subscriptions/%s?hapikey=%s&userId=%s';

    /**
     * @var SystemInstall
     */
    private $systemInstall;

    /**
     * @var array
     */
    private $headers;

    /**
     * HubspotUnsubscribeRequester constructor.
     *
     * @param SystemInstall $systemInstall
     * @param array         $headers
     */
    public function __construct(SystemInstall $systemInstall, array $headers)
    {
        $this->systemInstall = $systemInstall;
        $this->headers       = $headers;
    }

    /**
     * @param array $data
     *
     * @return RequestDto
     * @throws CleverConnectorsException
     */
    public function getRequestDto(array $data): RequestDto
    {
        $url = sprintf(
            self::WEBHOOK_SUBSCRIPTION_DELETE_URL,
            $this->systemInstall->getSettings()[HubspotSystem::APP_ID],
            $this->getWebhookId($data),
            HubspotSystem::HAPI_KEY,
            HubspotSystem::USER_ID
        );
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
        if (in_array($responseDto->getStatusCode(), [200, 204])) {
            return TRUE;
        }

        return FALSE;
    }

}