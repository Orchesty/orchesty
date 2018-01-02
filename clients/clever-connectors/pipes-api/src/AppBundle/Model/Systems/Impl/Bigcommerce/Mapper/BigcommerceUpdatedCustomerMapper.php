<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Bigcommerce\Mapper;

use Hanaboso\PipesFramework\Commons\Process\ProcessDto;

/**
 * Class BigcommerceUpdatedCustomerMapper
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Bigcommerce\Mapper
 */
class BigcommerceUpdatedCustomerMapper extends BigcommerceCustomerMapperAbstract
{

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     */
    public function process(ProcessDto $dto): ProcessDto
    {
        $this->action = self::UPDATE;

        return parent::process($dto);
    }

}