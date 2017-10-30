<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: radek.jirsa
 * Date: 30.10.17
 * Time: 9:02
 */

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Bigcommerce\Requester;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\Requester\RequesterInterface;
use CleverConnectors\AppBundle\Model\Requester\RequesterTrait;
use CleverConnectors\AppBundle\Model\Systems\Impl\Bigcommerce\BigcommerceSystem;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Commons\Transport\Curl\CurlManager;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\ResponseDto;

/**
 * Class BigcommerceUnsubscribeRequester
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Bigcommerce\Requester
 */
final class BigcommerceUnsubscribeRequester implements RequesterInterface
{

    use RequesterTrait;

    private const  WEBHOOK_UNSUBSCRIBE_URL = 'https://api.bigcommerce.com/stores/%s/v2/hooks/%s';

    /**
     * @var SystemInstall
     */
    private $systemInstall;

    /**
     * @var array
     */
    private $headers;

    /**
     * BigcommerceUnsubscribeRequester constructor.
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
        $url = sprintf(
            self::WEBHOOK_UNSUBSCRIBE_URL,
            $this->systemInstall->getSettings()[BigcommerceSystem::STORE_ID],
            $this->getWebhookId($data)
        );

        $request = new RequestDto(CurlManager::METHOD_DELETE, new Uri($url));
        $request->setHeaders($this->headers);

        return $request;
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