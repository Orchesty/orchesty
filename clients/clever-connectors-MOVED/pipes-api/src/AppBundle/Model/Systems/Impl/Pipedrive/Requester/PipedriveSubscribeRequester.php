<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: radek.jirsa
 * Date: 30.10.17
 * Time: 11:47
 */

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Pipedrive\Requester;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\Requester\RequesterInterface;
use CleverConnectors\AppBundle\Model\Requester\RequesterTrait;
use CleverConnectors\AppBundle\Model\Systems\Impl\Pipedrive\PipedriveSystem;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;

/**
 * Class PipedriveSubscribeRequester
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Pipedrive\Requester
 */
final class PipedriveSubscribeRequester implements RequesterInterface
{

    use RequesterTrait;

    private const WEBHOOK_SUBSCRIPTION_URL = 'https://api.pipedrive.com/v1/webhooks?api_token=%s';

    /**
     * @var array
     */
    private $events = [
        'pipedrive-updated-person-connector' => [
            'updated', 'person',
        ],
        'pipedrive-deleted-person-connector' => [
            'deleted', 'person',
        ],
    ];

    /**
     * @var SystemInstall
     */
    private $systemInstall;

    /**
     * @var array
     */
    private $headers;

    /**
     * PipedriveSubscribeRequester constructor.
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
        $event        = $this->events[$subscription->getNodeName()];
        $url          = sprintf(
            self::WEBHOOK_SUBSCRIPTION_URL,
            $this->systemInstall->getSettings()[PipedriveSystem::API_TOKEN]
        );

        $dto = new RequestDto(CurlManager::METHOD_POST, new Uri($url));
        $dto->setHeaders($this->headers)
            ->setBody(json_encode([
                'subscription_url' => $this->getWebhookUrl($data),
                'event_action'     => $event[0],
                'event_object'     => $event[1],
            ]));

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

        if (!array_key_exists('data', $body) || !array_key_exists('id', $body['data'])) {
            throw new CleverConnectorsException(
                'Missing webhook data in response.',
                CleverConnectorsException::MISSING_DATA
            );
        }

        return (string) $body['data']['id'];
    }

}