<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Pipedrive\Mapper;

/**
 * Class PipedriveCreatedEventPersonMapper
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Pipedrive\Mapper
 */
class PipedriveCreatedEventPersonMapper extends PipedrivePersonMapperAbstract
{

    /**
     * @param array $data
     *
     * @return array
     */
    protected function getInnerData(array $data): array
    {
        return $data['data'] ?? [];
    }

}