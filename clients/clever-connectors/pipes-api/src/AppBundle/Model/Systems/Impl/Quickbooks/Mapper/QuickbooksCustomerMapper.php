<?php
/**
 * Created by PhpStorm.
 * User: michal.bartl
 * Date: 10/24/17
 * Time: 10:30 AM
 */

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Quickbooks\Mapper;


use CleverConnectors\AppBundle\Model\CM\SubscriptionConnector\CustomerObject\CMSubscriber;
use CleverConnectors\AppBundle\Utils\CMHeaders;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\CustomNode\CustomNodeInterface;

class QuickbooksCustomerMapper implements CustomNodeInterface
{

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     */
    public function process(ProcessDto $dto): ProcessDto
    {
        $data = json_decode($dto->getData(), TRUE);

        if (!array_key_exists('PrimaryEmailAddr', $data) || !array_key_exists('Address', $data['PrimaryEmailAddr'])) {
            return $this->setHeadersToStop($dto);
        }

        $obj = new CMSubscriber();
        $obj->setEmail($data['PrimaryEmailAddr']['Address']);
        $obj->setForeignId($data['Id']);
        $obj->setReactivate($data['Active']);

        if (array_key_exists('GivenName', $data)) {
            $obj->setFirstName($data['GivenName']);
        }

        if (array_key_exists('FamilyName', $data)) {
            $obj->setLastName($data['FamilyName']);
        }

        return $dto->setData(json_encode($obj->toArray()));
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     */
    protected function setHeadersToStop(ProcessDto $dto): ProcessDto
    {
        $headers       = $dto->getHeaders();
        $key           = CMHeaders::createKey(CMHeaders::RESULT_CODE);
        $headers[$key] = 1003;
        $dto->setHeaders($headers);
        return $dto;
    }
}