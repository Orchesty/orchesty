<?php declare(strict_types=1);

namespace Hanaboso\HbPFConnectors\Model\Application\Impl\Mailchimp\Mapper;

use Hanaboso\CommonsBundle\Exception\PipesFrameworkException;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\PipesPhpSdk\CustomNode\CustomNodeAbstract;

/**
 * Class MailchimpCreateContactMapper
 *
 * @package Hanaboso\HbPFConnectors\Model\Application\Impl\Mailchimp\Mapper
 */
class MailchimpCreateContactMapper extends CustomNodeAbstract
{

    /**
     * @param array $data
     *
     * @return array
     */
    private function createBody(array $data): array
    {
        $array  = [];
        $return = [];

        $fields = $this->requestedFields();
        $data   = $this->formatData($data);

        foreach ($fields as $key => $field) {
            if (isset($data[$field]['value'])) {
                $array[$key] = $data[$field]['value'];
            } else {
                $array[$key] = '';
            }
        }

        $return['merge_fields']  = $array;
        $return['status']        = 'subscribed';
        $return['email_address'] = $data['email']['value'] ?? '';

        return $return;
    }

    /**
     * @param array $data
     *
     * @return array
     */
    private function formatData(array $data): array
    {
        $return = $data['properties'];

        $address = [
            'addr1' => $data['properties']['address']['value'] ??  '',
            'city'  => $data['properties']['city']['value'] ??  '',
            'state' => $data['properties']['state']['value'] ?? '',
            'zip'   => $data['properties']['zip']['value'] ?? '',
        ];

        $return['vid']['value'] = $data['vid'] ?? NULL;

        $return['fullAddress']['value'] = $address;

        $return['phone']['value'] = preg_replace('/[^\d]/', '', $data['properties']['phone']['value'] ?? '');

        return $return;
    }

    /**
     * @return array
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

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws PipesFrameworkException
     */
    public function process(ProcessDto $dto): ProcessDto
    {
        $body = json_decode($dto->getData(), TRUE, 512, JSON_THROW_ON_ERROR) ?? NULL;

        if (!isset($body['properties'])) {
            $message = 'There is missing field "properties" in ProcessDto.';
            $dto->setStopProcess(ProcessDto::STOP_AND_FAILED, $message);

            return $dto;
        }

        $dto->setData((string) json_encode($this->createBody((array) $body), JSON_THROW_ON_ERROR, 512));

        return $dto;
    }

}