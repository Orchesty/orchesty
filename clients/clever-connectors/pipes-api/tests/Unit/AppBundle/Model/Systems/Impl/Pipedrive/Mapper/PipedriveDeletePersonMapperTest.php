<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Pipedrive\Mapper;

use CleverConnectors\AppBundle\Enum\CleverFieldsEnum;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Nette\Utils\Json;
use Tests\ConnectorTestCaseAbstract;

/**
 * Class PipedriveDeletePersonMapperTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Pipedrive\Mapper
 */
final class PipedriveDeletePersonMapperTest extends ConnectorTestCaseAbstract
{

    /**
     *
     */
    public function testProcessEvent(): void
    {
        $connector = $this->container->get('hbpf.custom_node.pipedrive-delete-person-mapper');

        $response = Json::decode(
            $connector->process((new ProcessDto())->setData($this->getRequest('personDeleteWebhook.json')))
                ->getData(),
            TRUE
        );

        $this->assertEquals([
            CleverFieldsEnum::EMAIL      => 'asd@asd.com',
            CleverFieldsEnum::FIRST_NAME => '',
            CleverFieldsEnum::LAST_NAME  => '',
            CleverFieldsEnum::FOREIGN_ID => '6',
            CleverFieldsEnum::REACTIVATE => TRUE,
        ], $response);
    }

}