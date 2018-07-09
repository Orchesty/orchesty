<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Pipedrive\Requester;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Enum\CleverCustomKeysEnum;
use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\CMEvents\CMEventObject;
use CleverConnectors\AppBundle\Model\Requester\RequesterInterface;
use CleverConnectors\AppBundle\Model\Requester\RequesterTrait;
use CleverConnectors\AppBundle\Model\Systems\Impl\Pipedrive\PipedriveSystem;
use Doctrine\ODM\MongoDB\DocumentManager;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;

/**
 * Class PipedriveCMEventRequester
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Pipedrive\Requester
 */
final class PipedriveCMEventRequester implements RequesterInterface
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
     * @var DocumentManager
     */
    private $dm;

    /**
     * PipedriveRequester constructor.
     *
     * @param SystemInstall   $systemInstall
     * @param array           $headers
     * @param DocumentManager $dm
     */
    function __construct(SystemInstall $systemInstall, array $headers, DocumentManager $dm)
    {
        $this->systemInstall = $systemInstall;
        $this->headers       = $headers;
        $this->dm            = $dm;
    }

    /**
     * @param array $data
     *
     * @return RequestDto
     * @throws CleverConnectorsException
     */
    public function getRequestDto(array $data): RequestDto
    {
        /** @var CMEventObject $obj */
        $obj = $this->getCMEventObject($data);
        $dto = new RequestDto('POST',
            new Uri(sprintf($obj->getUrl(),
                $this->getKey($this->systemInstall->getSettings(), PipedriveSystem::API_TOKEN))));
        $dto->setBody(json_encode([
            'name'       => $obj->getField(),
            'field_type' => 'varchar',
        ]))->setHeaders($this->headers);

        return $dto;
    }

    /**
     * @param ResponseDto   $responseDto
     * @param SystemInstall $systemInstall
     *
     * @return null
     * @throws CleverConnectorsException
     */
    public function processResponse(ResponseDto $responseDto, SystemInstall $systemInstall)
    {
        $data = json_decode($responseDto->getBody(), TRUE);

        if (!is_array($data)
            || !array_key_exists('success', $data)
            || !$data['success']
            || !array_key_exists('data', $data)
            || !array_key_exists('key', $data['data'])
            || !array_key_exists('name', $data['data'])
        ) {
            throw new CleverConnectorsException(
                'Failed to create custom field, PipeDrive requester.' . json_encode($data),
                CleverConnectorsException::REQUEST_FAILED
            );
        }

        $this->systemInstall->setSettings(array_merge(
            $this->systemInstall->getSettings(),
            [
                $data['data']['name'] => $data['data']['key'],
            ]
        ));
        $this->dm->flush();

        return NULL;
    }

    /**
     * @return RequestDto
     * @throws CleverConnectorsException
     */
    public function getListRequestDto(): RequestDto
    {
        $dto = new RequestDto('GET',
            new Uri('https://api.pipedrive.com/v1/personFields?api_token='
                . $this->getKey($this->systemInstall->getSettings(), PipedriveSystem::API_TOKEN))
        );

        return $dto->setHeaders($this->headers);
    }

    /**
     * @param array       $fields
     * @param ResponseDto $responseDto
     *
     * @return array
     * @throws CleverConnectorsException
     */
    public function processListResponse(array $fields, ResponseDto $responseDto): array
    {
        $data = json_decode($responseDto->getBody(), TRUE);
        if (!is_array($data)
            || !array_key_exists('data', $data)
            || !is_array($data['data'])
        ) {
            throw new CleverConnectorsException(
                'Malformed data, PipeDrive CMEventRequester.',
                CleverConnectorsException::MISSING_DATA
            );
        }

        $keys = [];

        foreach ($data['data'] as $field) {
            $type = CleverCustomKeysEnum::getType($field['name']);
            if (in_array($type, $fields)) {
                unset($fields[array_search($type, $fields)]);
                $keys[$field['name']] = $field['key'];
            }
        }

        $this->systemInstall->setSettings(array_merge(
            $this->systemInstall->getSettings(),
            $keys
        ));

        return $fields;
    }

}