<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Hubspot\Requester;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\Requester\RequesterInterface;
use CleverConnectors\AppBundle\Model\Requester\Traits\CMEventRequesterTrait;
use CleverConnectors\AppBundle\Model\Systems\Impl\Hubspot\HubspotSystem;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\ResponseDto;

/**
 * Class HubspotRequester
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Hubspot\Requester
 */
class HubspotRequester implements RequesterInterface
{

    use CMEventRequesterTrait;

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
        $object = $this->getObject($data);

        $dto = new RequestDto('POST', new Uri(sprintf($object->getUrl(), HubspotSystem::HAPI_KEY)));
        $dto->setHeaders($this->headers)
            ->setBody(json_encode([
                'user_field' => [
                    'type'      => 'string',
                    'fieldType' => 'booleancheckbox',
                    'name'      => $object->getField(),
                    'label'     => $object->getField(),
                    'groupName' => 'contactinformation',
                ],
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