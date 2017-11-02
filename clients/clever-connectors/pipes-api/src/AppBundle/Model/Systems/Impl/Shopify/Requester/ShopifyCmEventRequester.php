<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Shopify\Requester;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\Requester\RequesterInterface;
use CleverConnectors\AppBundle\Model\Requester\RequesterTrait;
use CleverConnectors\AppBundle\Model\Systems\Impl\Shopify\ShopifySystem;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\ResponseDto;

/**
 * Class ShopifyCmEventRequester
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Shopify\Requester
 */
class ShopifyCmEventRequester implements RequesterInterface
{

    use RequesterTrait;

    /**
     * @var SystemInstall
     */
    private $systemInstall;

    /**
     * @var array
     */
    private $headers;

    /**
     * ShopifyRequester constructor.
     *
     * @param SystemInstall $systemInstall
     * @param array         $headers
     */
    function __construct(SystemInstall $systemInstall, array $headers)
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
        $sett   = $this->systemInstall->getSettings();
        $object = $this->getCMEventObject($data);

        $dto = new RequestDto('POST', new Uri(sprintf($object->getUrl(), $sett[ShopifySystem::SYSTEM_URL])));
        $dto->setHeaders($this->headers)
            ->setBody(json_encode([ // TODO
//                'user_field' => [
//                    'type'  => 'checkbox',
//                    'title' => $object->getField(),
//                    'key'   => $object->getField(),
//                ],
            ]));

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
        return $systemInstall;
    }

}