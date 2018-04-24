<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Hubspot\Requester;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\Requester\RequesterInterface;
use CleverConnectors\AppBundle\Model\Requester\RequesterTrait;
use CleverConnectors\AppBundle\Model\Systems\Impl\Hubspot\HubspotSystem;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;

/**
 * Class HubspotRequester
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Hubspot\Requester
 */
class HubspotRequester implements RequesterInterface
{

    use RequesterTrait;

    /**
     * @var array
     */
    private $headers;

    /**
     * HubspotRequester constructor.
     *
     * @param array $headers
     */
    function __construct(array $headers)
    {
        $this->headers = $headers;
    }

    /**
     * @param array $data
     *
     * @return RequestDto
     */
    public function getRequestDto(array $data): RequestDto
    {
        $object = $this->getCMEventObject($data);

        $dto = new RequestDto(CurlManager::METHOD_POST, new Uri(sprintf($object->getUrl(), HubspotSystem::HAPI_KEY)));
        $dto->setHeaders($this->headers)
            ->setBody(json_encode([
                'type'      => 'string',
                'fieldType' => 'booleancheckbox',
                'name'      => $object->getField(),
                'label'     => $object->getField(),
                'groupName' => 'contactinformation',
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