<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: michal.bartl
 * Date: 10/25/17
 * Time: 5:20 PM
 */

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Quickbooks\Mapper;

use CleverConnectors\AppBundle\Enum\CleverFieldsEnum;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Nette\Utils\Json;
use Tests\ConnectorTestCaseAbstract;

/**
 * Class QuickbooksUpdatedCustomerMapperTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Quickbooks\Mapper
 */
final class QuickbooksUpdatedCustomerMapperTest extends ConnectorTestCaseAbstract
{

    /**
     *
     */
    public function testProcessEvent(): void
    {
        $connector = $this->container->get('hbpf.custom_node.quickbooks-updated-customer-mapper');

        $response = Json::decode(
            $connector->process((new ProcessDto())->setData($this->getRequest('QuickbooksCustomerMapper.json')))
                ->getData(),
            TRUE
        );

        $this->assertEquals([
            CleverFieldsEnum::EMAIL      => 'jdrew2@myemail.com',
            CleverFieldsEnum::FIRST_NAME => 'James2',
            CleverFieldsEnum::LAST_NAME  => 'King2',
            CleverFieldsEnum::SEND_OPTIN => FALSE,
            CleverFieldsEnum::FOREIGN_ID => '2',
            CleverFieldsEnum::REACTIVATE => TRUE,
        ], $response);
    }

}