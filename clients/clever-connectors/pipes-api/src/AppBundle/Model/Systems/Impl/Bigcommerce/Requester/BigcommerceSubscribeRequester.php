<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Bigcommerce\Requester;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\Requester\RequesterInterface;
use CleverConnectors\AppBundle\Model\Requester\RequesterTrait;
use CleverConnectors\AppBundle\Model\Systems\Impl\Bigcommerce\BigcommerceSystem;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;

/**
 * Class SubscribeRequester
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Bigcommerce\Requester
 */
final class BigcommerceSubscribeRequester implements RequesterInterface
{

    use RequesterTrait;

    private const  WEBHOOK_SUBSCRIBE_URL = 'https://api.bigcommerce.com/stores/%s/v2/hooks';

    /**
     * @var array
     */
    private $topics = [
        'bigcommerce-created-customer-connector' => 'store/customer/created',
        'bigcommerce-updated-customer-connector' => 'store/customer/updated',
        'bigcommerce-deleted-customer-connector' => 'store/customer/deleted',
    ];

    /**
     * @var array
     */
    private $headers;

    /**
     * @var SystemInstall
     */
    private $systemInstall;

    /**
     * SubscribeRequester constructor.
     *
     * @param SystemInstall $systemInstall
     * @param array         $headers
     */
    public function __construct(SystemInstall $systemInstall, array $headers)
    {
        $this->headers       = $headers;
        $this->systemInstall = $systemInstall;
    }

    /**
     * @param array $data
     *
     * @return RequestDto
     */
    public function getRequestDto(array $data): RequestDto
    {
        $url = sprintf(self::WEBHOOK_SUBSCRIBE_URL, $this->systemInstall->getSettings()[BigcommerceSystem::STORE_ID]);

        $subscription = $this->getWebhookSubscribe($data);
        $request      = new RequestDto(CurlManager::METHOD_POST, new Uri($url));
        $request
            ->setBody(
                json_encode([
                    'scope'       => $this->topics[$subscription->getNodeName()],
                    'destination' => $this->getWebhookUrl($data),
                ]))
            ->setHeaders($this->headers);

        return $request;
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
        if (!$data || !array_key_exists('id', $data)) {
            throw new CleverConnectorsException(
                'Missing webhook id data in response.',
                CleverConnectorsException::MISSING_DATA
            );
        }

        return (string) $data['id'];
    }

}