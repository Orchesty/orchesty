<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Nutshell\Mapper;

use CleverConnectors\AppBundle\Enum\CleverFieldsEnum;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Nette\Utils\Json;
use Tests\ConnectorTestCaseAbstract;

/**
 * Class NutshellSyncUpdateContactMapperTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Nutshell\Mapper
 */
final class NutshellSyncUpdateContactMapperTest extends ConnectorTestCaseAbstract
{

    /**
     *
     */
    public function testProcess(): void
    {
        $connector = $this->container->get('hbpf.custom_node.nutshell-sync-update-contact-mapper');

        $response = Json::decode($connector->process(
            (new ProcessDto())->setData(
                $this->getRequest('NutshellSingleContactItem.json')
            ))->getData(), TRUE
        );

        $this->assertEquals([
            CleverFieldsEnum::EMAIL       => 'User01@User01.com',
            CleverFieldsEnum::FIRST_NAME  => 'User01',
            CleverFieldsEnum::LAST_NAME   => 'User01',
            CleverFieldsEnum::FOREIGN_ID  => '1',
            CleverFieldsEnum::REACTIVATE  => TRUE,
        ], $response);
    }

}