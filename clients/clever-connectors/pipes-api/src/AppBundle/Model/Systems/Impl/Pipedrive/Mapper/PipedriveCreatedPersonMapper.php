<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Pipedrive\Mapper;

/**
 * Class PipedriveCreatedPersonMapper
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Pipedrive\Mapper
 */
class PipedriveCreatedPersonMapper extends PipedriveUpdatedPersonMapper
{

    /**
     * @var bool
     */
    protected $includeList = TRUE;

    /**
     * @param array $data
     *
     * @return array|string
     */
    protected function getInnerData(array $data)
    {
        if ($data['current']['update_time'] !== $data['current']['add_time']) {
            return self::OMMIT;
        }

        return $data['current'];
    }

}