<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Basecrm\Mapper;

/**
 * Class BasecrmUpdatedContactMapper
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Basecrm\Mapper
 */
class BasecrmUpdatedContactMapper extends BasecrmContactMapperAbstract
{

    /**
     * @var array
     */
    protected static $event_types = ['updated'];

}