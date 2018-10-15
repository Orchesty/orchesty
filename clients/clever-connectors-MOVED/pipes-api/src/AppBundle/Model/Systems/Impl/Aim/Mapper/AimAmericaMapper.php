<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Aim\Mapper;

use CleverConnectors\AppBundle\Model\Systems\Impl\Aim\AimSystem;

/**
 * Class AimAmericaMapper
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Aim\Mapper
 */
final class AimAmericaMapper extends AimDestinationMapperAbstract
{

    /**
     */
    public function __construct()
    {
        parent::__construct(AimSystem::DESTINATION_AMERICA);
    }

}
