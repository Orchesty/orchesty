<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Zendesk\Mapper;

use CleverConnectors\AppBundle\Enum\CleverFieldsEnum;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Nette\Utils\Json;
use Tests\ConnectorTestCaseAbstract;

/**
 * Class ZendeskCreatedEventUserMapperTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Zendesk\Mapper
 */
final class ZendeskCreatedEventUserMapperTest extends ConnectorTestCaseAbstract
{

    /**
     *
     */
    public function testProcessEvent(): void
    {
        $connector = $this->container->get('hbpf.custom_node.zendesk-created-event-user-mapper');

        $response = Json::decode(
            $connector->process((new ProcessDto())->setData($this->getRequest('createdUser.json')))
                ->getData(),
            TRUE
        );

        $this->assertEquals([
            CleverFieldsEnum::EMAIL      => 'eml@eml.com',
            CleverFieldsEnum::LAST_NAME  => 'nagmae',
            CleverFieldsEnum::FOREIGN_ID => '115499307813',
            CleverFieldsEnum::REACTIVATE => TRUE,
            CleverFieldsEnum::SEND_OPTIN => FALSE,
        ], $response);
    }

}