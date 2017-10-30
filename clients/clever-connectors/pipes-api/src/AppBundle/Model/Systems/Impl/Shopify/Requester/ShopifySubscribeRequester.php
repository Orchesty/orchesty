<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: radek.jirsa
 * Date: 30.10.17
 * Time: 11:14
 */

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Shopify\Requester;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\Requester\RequesterInterface;
use CleverConnectors\AppBundle\Model\Requester\RequesterTrait;
use CleverConnectors\AppBundle\Model\Systems\Impl\Shopify\ShopifySystem;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Commons\Transport\Curl\CurlManager;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\ResponseDto;

/**
 * Class ShopifySubscribeRequester
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Shopify\Requester
 */
final class ShopifySubscribeRequester implements RequesterInterface
{

    use RequesterTrait;

    private const WEBHOOK_SUBSCRIBE_URL = 'https://%s.myshopify.com/admin/webhooks.json';

    /**
     * @var array
     */
    private $topics = [
        'shopify-create-customer-connector' => 'customers/create',
        'shopify-update-customer-connector' => 'customers/update',
        'shopify-delete-customer-connector' => 'customers/delete',
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
     * ShopifySubscribeRequester constructor.
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
     */
    public function getRequestDto(array $data): RequestDto
    {
        $subscription = $this->getWebhookSubscribe($data);
        $topic        = $this->topics[$subscription->getNodeName()];
        $systemUrl    = $this->systemInstall->getSettings()[ShopifySystem::SYSTEM_URL];

        $dto = new RequestDto(CurlManager::METHOD_POST, new Uri(sprintf(self::WEBHOOK_SUBSCRIBE_URL, $systemUrl)));

        $dto
            ->setBody(json_encode([
                'webhook' => [
                    'topic'   => $topic,
                    'address' => $this->getWebhookUrl($data),
                    'format'  => 'json',
                ],
            ]))
            ->setHeaders($this->headers);

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
        if (!$body || !array_key_exists('webhook', $body) || !array_key_exists('id', $body['webhook'])) {
            throw new CleverConnectorsException(
                'Missing webhook id data in response.',
                CleverConnectorsException::MISSING_DATA
            );
        }

        return (string) $body['webhook']['id'];
    }

}