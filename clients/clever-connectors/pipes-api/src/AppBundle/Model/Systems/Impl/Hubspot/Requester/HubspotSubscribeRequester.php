<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: radek.jirsa
 * Date: 30.10.17
 * Time: 13:23
 */

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Hubspot\Requester;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\Requester\RequesterInterface;
use CleverConnectors\AppBundle\Model\Requester\RequesterTrait;
use CleverConnectors\AppBundle\Model\Systems\Impl\Hubspot\HubspotSystem;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;

/**
 * Class HubspotSubscribeRequester
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Hubspot\Requester
 */
final class HubspotSubscribeRequester implements RequesterInterface
{

    use RequesterTrait;

    private const WEBHOOK_SUBSCRIPTION_CREATE_URL = 'https://api.hubapi.com/webhooks/v1/%s/subscriptions?hapikey=%s&userId=%s';
    /**
     * @var SystemInstall
     */
    private $systemInstall;
    /**
     * @var array
     */
    private $headers;

    /**
     * HubspotSubscribeRequester constructor.
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
     * @throws CurlException
     */
    public function getRequestDto(array $data): RequestDto
    {
        $subscription = $this->getWebhookSubscribe($data);
        $requestUrl   = sprintf(
            self::WEBHOOK_SUBSCRIPTION_CREATE_URL,
            $this->systemInstall->getSettings()[HubspotSystem::APP_ID],
            HubspotSystem::HAPI_KEY,
            HubspotSystem::USER_ID
        );

        $dto  = new RequestDto(CurlManager::METHOD_POST, new Uri($requestUrl));
        $body = [
            'subscriptionDetails' => $subscription->getParams(),
            'enabled'             => TRUE,
        ];

        $dto->setBody(json_encode($body));
        $dto->setHeaders($this->headers);

        return $dto;
    }

    /**
     * @param ResponseDto   $responseDto
     * @param SystemInstall $systemInstall
     *
     * @return mixed
     * @throws CleverConnectorsException
     */
    public function processResponse(ResponseDto $responseDto, SystemInstall $systemInstall)
    {
        $body = json_decode($responseDto->getBody(), TRUE);
        if (!$body || !array_key_exists('id', $body)) {
            throw new CleverConnectorsException(
                'Missing webhook id data in response.',
                CleverConnectorsException::MISSING_DATA
            );
        }

        return (string) $body['id'];
    }

}