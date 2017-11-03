<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Quickbooks\Mapper;

use Hanaboso\PipesFramework\Commons\Process\ProcessDto;

/**
 * Class QuickbooksCreatedCustomerMapper
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Quickbooks\Mapper
 */
class QuickbooksCreatedCustomerMapper extends QuickbooksUpdatedCustomerMapper
{

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     */
    public function process(ProcessDto $dto): ProcessDto
    {
        $data = json_decode($dto->getData(), TRUE);
        $data = json_decode($data['body'], TRUE);

        return $this->processData($data['Customer'] ?? [], $dto);
    }

}