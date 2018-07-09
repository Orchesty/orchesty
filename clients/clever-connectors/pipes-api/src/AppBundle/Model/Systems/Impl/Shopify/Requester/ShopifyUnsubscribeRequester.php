<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: radek.jirsa
 * Date: 30.10.17
 * Time: 11:25
 */

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Shopify\Requester;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\Requester\RequesterInterface;
use CleverConnectors\AppBundle\Model\Requester\RequesterTrait;
use CleverConnectors\AppBundle\Model\Systems\Impl\Shopify\ShopifySystem;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;

/**
 * Class ShopifyUnsubscribeRequester
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Shopify\Requester
 */
final class ShopifyUnsubscribeRequester implements RequesterInterface
{

    use RequesterTrait;

    private const WEBHOOK_UNSUBSCRIBE_URL = 'https://%s.myshopify.com/admin/webhooks/%s.json';

    /**
     * @var SystemInstall
     */
    private $systemInstall;

    /**
     * @var array
     */
    private $headers;

    /**
     * ShopifyUnsubscribeRequester constructor.
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
            self::WEBHOOK_UNSUBSCRIBE_URL,
            $this->systemInstall->getSettings()[ShopifySystem::SYSTEM_URL],
            $this->getWebhookId($data)
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
        if ($responseDto->getStatusCode() == 200) {
            return TRUE;
        }

        return FALSE;
    }

}