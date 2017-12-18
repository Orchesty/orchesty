<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Shipstation\Mapper;

use Hanaboso\PipesFramework\Commons\Process\ProcessDto;

/**
 * Class ShipstationCreatedCustomerMapper
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Shipstation\Mapper
 */
class ShipstationCreatedCustomerMapper extends ShipstationCustomerMapperAbstract
{

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     */
    public function process(ProcessDto $dto): ProcessDto
    {
        $this->action = self::CREATE;

        return parent::process($dto);
    }

}