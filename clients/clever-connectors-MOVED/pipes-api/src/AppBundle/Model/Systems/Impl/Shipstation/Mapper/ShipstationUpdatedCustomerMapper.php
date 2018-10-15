<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Shipstation\Mapper;

use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use Hanaboso\CommonsBundle\Process\ProcessDto;

/**
 * Class ShipstationUpdateCustomerMapper
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Shipstation\Mapper
 */
class ShipstationUpdatedCustomerMapper extends ShipstationCustomerMapperAbstract
{

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws CleverConnectorsException
     */
    public function process(ProcessDto $dto): ProcessDto
    {
        $this->action = self::UPDATE;

        return parent::process($dto);
    }

}