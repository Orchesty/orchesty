<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Pipedrive\Mapper;

use CleverConnectors\AppBundle\Enum\CleverFieldsEnum;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Nette\Utils\Json;
use Tests\ConnectorTestCaseAbstract;

/**
 * Class PipedriveCreatedEventPersonMapperTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Pipedrive\Mapper
 */
final class PipedriveCreatedEventPersonMapperTest extends ConnectorTestCaseAbstract
{

    /**
     *
     */
    public function testProcessEvent(): void
    {
        $connector = $this->container->get('hbpf.custom_node.pipedrive-created-event-person-mapper');

        $response = Json::decode(
            $connector->process((new ProcessDto())->setData($this->getRequest('personCreated.json')))
                ->getData(),
            TRUE
        );

        $this->assertEquals([
            CleverFieldsEnum::EMAIL      => 'gfgs@asd.com',
            CleverFieldsEnum::FIRST_NAME => 'fdgdvb',
            CleverFieldsEnum::LAST_NAME  => 'asd',
            CleverFieldsEnum::FOREIGN_ID => '14',
            CleverFieldsEnum::REACTIVATE => TRUE,
            CleverFieldsEnum::SEND_OPTIN => FALSE,
        ], $response);
    }

}