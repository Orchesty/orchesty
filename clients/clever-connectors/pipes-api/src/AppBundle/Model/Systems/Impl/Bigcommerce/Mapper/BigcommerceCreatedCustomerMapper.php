<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Bigcommerce\Mapper;

use Hanaboso\CommonsBundle\Process\ProcessDto;

/**
 * Class BigcommerceCreatedCustomerMapper
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Bigcommerce\Mapper
 */
class BigcommerceCreatedCustomerMapper extends BigcommerceCustomerMapperAbstract
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