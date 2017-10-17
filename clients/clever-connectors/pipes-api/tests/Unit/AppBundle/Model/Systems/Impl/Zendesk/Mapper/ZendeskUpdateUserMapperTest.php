<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Zendesk\Mapper;

use CleverConnectors\AppBundle\Enum\CleverFieldsEnum;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Nette\Utils\Json;
use Tests\ConnectorTestCaseAbstract;

/**
 * Class ZendeskUpdateUserMapperTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Zendesk\Mapper
 */
final class ZendeskUpdateUserMapperTest extends ConnectorTestCaseAbstract
{

    /**
     *
     */
    public function testProcessEvent(): void
    {
        $connector = $this->container->get('hbpf.custom_node.zendesk-update-user-mapper');

        $response = Json::decode(
            $connector->process((new ProcessDto())->setData($this->getRequest('singleItemSync.json')))
                ->getData(),
            TRUE
        );

        $this->assertEquals([
            CleverFieldsEnum::EMAIL      => 'customer@example.com',
            CleverFieldsEnum::FIRST_NAME => 'Sample',
            CleverFieldsEnum::LAST_NAME  => 'customer',
            CleverFieldsEnum::FOREIGN_ID => '115316687153',
            CleverFieldsEnum::REACTIVATE => TRUE,
        ], $response);
    }

}