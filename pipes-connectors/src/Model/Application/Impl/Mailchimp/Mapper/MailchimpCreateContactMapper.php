<?php declare(strict_types=1);

namespace Hanaboso\HbPFConnectors\Model\Application\Impl\Mailchimp\Mapper;

use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\PipesPhpSdk\CustomNode\CustomNodeAbstract;
use Hanaboso\Utils\Exception\PipesFrameworkException;
use Hanaboso\Utils\String\Json;
use JsonException;

/**
 * Class MailchimpCreateContactMapper
 *
 * @package Hanaboso\HbPFConnectors\Model\Application\Impl\Mailchimp\Mapper
 */
final class MailchimpCreateContactMapper extends CustomNodeAbstract
{

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws PipesFrameworkException
     * @throws JsonException
     */
    public function process(ProcessDto $dto): ProcessDto
    {
        $body = Json::decode($dto->getData()) ?? NULL;

        if (!isset($body['properties'])) {
            $message = 'There is missing field "properties" in ProcessDto.';
            $dto->setStopProcess(ProcessDto::STOP_AND_FAILED, $message);

            return $dto;
        }

        $dto->setData(Json::encode($this->createBody($body)));

        return $dto;
    }

    /**
     * @param mixed[] $data
     *
     * @return mixed[]
     */
    private function createBody(array $data): array
    {
        $array  = [];
        $return = [];

        $fields = $this->requestedFields();
        $data   = $this->formatData($data);

        foreach ($fields as $key => $field) {
            $array[$key] = $data[$field]['value'] ?? '';
        }

        $return['merge_fields']  = $array;
        $return['status']        = 'subscribed';
        $return['email_address'] = $data['email']['value'] ?? '';

        return $return;
    }

    /**
     * @param mixed[] $data
     *
     * @return mixed[]
     */
    private function formatData(array $data): array
    {
        $return = $data['properties'];

        $address = [
            'addr1' => $data['properties']['address']['value'] ?? '',
            'city'  => $data['properties']['city']['value'] ?? '',
            'state' => $data['properties']['state']['value'] ?? '',
            'zip'   => $data['properties']['zip']['value'] ?? '',
        ];

        $return['vid']['value'] = $data['vid'] ?? NULL;

        $return['fullAddress']['value'] = $address;

        $return['phone']['value'] = preg_replace('/[^\d]/', '', $data['properties']['phone']['value'] ?? '');

        return $return;
    }

    /**
     * @return mixed[]
     *
     * keys (on the left) are required Mailchimp fields, values (on the right) are provided Hubspot fields
     * field email_address is processed in createBody method
     * field ADDRESS is processed in formatData method, includes array of street/city/zip code and state
     */
    private function requestedFields(): array
    {
        return [
            'FNAME'     => 'firstname',
            'LNAME'     => 'lastname',
            'PHONE'     => 'phone',
            'ADDRESS'   => 'fullAddress',
            'HUBSPOTID' => 'vid',
        ];
    }

}
