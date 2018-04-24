<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 16.3.2017
 * Time: 13:29
 */

namespace Hanaboso\PipesFramework\Connector\Impl\Magento2;

use Hanaboso\CommonsBundle\Process\ProcessDto;

/**
 * Class Magento2CustomersConnector
 *
 * @package Hanaboso\PipesFramework\Connector\Impl\Magento2Old
 */
class Magento2CustomersConnector extends Magento2Base
{

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     */
    public function processAction(ProcessDto $dto): ProcessDto
    {
        $data = $this->processRequest('GET', '/rest/V1/customers/1');

        $dto = new ProcessDto();
        $dto->setData(json_decode($data, TRUE));

        return $dto;
    }

}