<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Connector\Impl\Magento2;

use Hanaboso\CommonsBundle\Process\ProcessDto;

/**
 * Class Magento2ModulesConnector
 *
 * @package Hanaboso\PipesFramework\Connector\Impl\Magento2Old
 */
class Magento2ModulesConnector extends Magento2Base
{

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     */
    public function processAction(ProcessDto $dto): ProcessDto
    {
        $data = $this->processRequest('GET', '/rest/V1/modules');

        $dto = new ProcessDto();
        $dto->setData(json_decode($data, TRUE));

        return $dto;
    }

}