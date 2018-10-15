<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: radek.jirsa
 * Date: 30.10.17
 * Time: 13:07
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
 * Class PipedriveUnsubscribeRequester
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Pipedrive\Requester
 */
final class PipedriveUnsubscribeRequester implements RequesterInterface
{

    use RequesterTrait;

    private const WEBHOOK_UNSUBSCRIPTION_URL = 'https://api.pipedrive.com/v1/webhooks/%s?api_token=%s';

    /**
     * @var SystemInstall
     */
    private $systemInstall;

    /**
     * @var array
     */
    private $headers;

    /**
     * PipedriveUnsubscribeRequester constructor.
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
        $url = sprintf(
            self::WEBHOOK_UNSUBSCRIPTION_URL,
            $this->getWebhookId($data),
            $this->systemInstall->getSettings()[PipedriveSystem::API_TOKEN]
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