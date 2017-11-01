<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Hubspot\Mapper;

use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Utils\CMHeaders;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\CustomNode\CustomNodeInterface;

/**
 * Class HubspotMapperAbstract
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Hubspot\Mapper
 */
abstract class HubspotMapperAbstract implements CustomNodeInterface
{

    /**
     * @param string $key
     * @param array  $data
     *
     * @throws CleverConnectorsException
     */
    protected function continueAfterDataCheck(string $key, array $data): void
    {
        if (!array_key_exists($key, $data)) {
            throw new CleverConnectorsException(
                sprintf('Missing "%s" field in data.', $key),
                CleverConnectorsException::MISSING_DATA
            );
        }
    }

    /**
     * @param array $data
     *
     * @return string
     * @throws CleverConnectorsException
     */
    protected function getEmail(array $data): string
    {
        $this->continueAfterDataCheck('identity-profiles', $data);

        $profiles = $data['identity-profiles'];

        // contact may have more profiles (merged) and a profile may have more identities
        foreach ($profiles as $profile) {
            if ($profile['vid'] == $data['vid']) {
                foreach ($profile['identities'] as $identity) {
                    if (strtolower($identity['type']) == 'email') {
                        return $identity['value'];
                    }
                }
            }
        }

        throw new CleverConnectorsException(
            'Could not find "email" under "identity-profiles".',
            CleverConnectorsException::MISSING_DATA
        );
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

    /**
     * @param string $property
     * @param mixed  $value
     *
     * @return array
     */
    protected function prepareProperty(string $property, $value): array
    {
        return [
            'property' => $property,
            'value'    => $value,
        ];
    }

}