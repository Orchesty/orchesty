<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: radek.jirsa
 * Date: 30.10.17
 * Time: 13:12
 */

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Wisepops\Requester;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\Requester\RequesterInterface;
use CleverConnectors\AppBundle\Model\Requester\RequesterTrait;
use CleverConnectors\AppBundle\Model\Systems\Impl\Wisepops\WisepopsSystem;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Commons\Transport\Curl\CurlManager;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\ResponseDto;

/**
 * Class WisepopsSubscribeRequester
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Wisepops\Requester
 */
final class WisepopsSubscribeRequester implements RequesterInterface
{

    use RequesterTrait;

    /**
     * @var array
     */
    private $headers;

    /**
     * WisepopsSubscribeRequester constructor.
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
     */
    public function getRequestDto(array $data): RequestDto
    {
        $targetUrl = sprintf('%s?hash=%s', $this->getWebhookUrl($data), md5(strval(time())));

        $dto = new RequestDto(CurlManager::METHOD_POST, new Uri(WisepopsSystem::WEBHOOK_URL));
        $dto->setHeaders($this->headers);
        $dto->setBody(sprintf('{"event":"email", "target_url":"%s"}', $targetUrl));

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
        $data = json_decode($responseDto->getBody(), TRUE);
        if (!isset($data['id'])) {
            throw new CleverConnectorsException(
                'Missing webhookId in response from subscription request.',
                CleverConnectorsException::MISSING_DATA
            );
        }

        return (string) $data['id'];
    }

}