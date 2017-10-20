<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Hubspot\Mapper;

use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\Systems\Impl\Hubspot\HubspotSystem;
use CleverConnectors\AppBundle\Utils\CMHeaders;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;

/**
 * Class HubspotMapperAbstract
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Hubspot\Mapper
 */
class HubspotMapperAbstract
{

    /**
     * @param array $data
     *
     * @throws CleverConnectorsException
     */
    protected function continueAfterBasicDataCheck(array $data): void
    {
        $message = '';
        if (!array_key_exists(HubspotSystem::SUBSCRIPTION_TYPE_KEY, $data)) {
            $message = 'Missing "subscriptionType" field in data.';
        } elseif (!array_key_exists('properties', $data)) {
            $message = 'Missing "properties" field in data.';
        }

        if ($message != '') {
            throw new CleverConnectorsException(
                'Missing "properties" field in data.',
                CleverConnectorsException::MISSING_DATA
            );
        }
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