<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Aim\Mapper;

use CleverConnectors\AppBundle\Model\Systems\Impl\Aim\AimSystem;

/**
 * Class AimEuropeMapper
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Aim\Mapper
 */
final class AimEuropeMapper extends AimDestinationMapperAbstract
{

    /**
     */
    public function __construct()
    {
        parent::__construct(AimSystem::DESTINATION_EUROPE);
    }

}
