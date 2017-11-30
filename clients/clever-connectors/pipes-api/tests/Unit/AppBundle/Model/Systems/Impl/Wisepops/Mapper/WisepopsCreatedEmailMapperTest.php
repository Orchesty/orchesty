<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Wisepops\Mapper;

use CleverConnectors\AppBundle\Enum\CleverFieldsEnum;
use CleverConnectors\AppBundle\Model\Systems\Impl\Wisepops\Mapper\WisepopsCreateEmailMapper;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Nette\Utils\Json;
use Tests\ConnectorTestCaseAbstract;

/**
 * Class WisepopsCreatedEmailMapperTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Wisepops\Mapper
 */
final class WisepopsCreatedEmailMapperTest extends ConnectorTestCaseAbstract
{

    /**
     * @covers WisepopsCreateEmailMapper::process()
     */
    public function testProcessEvent(): void
    {
        $connector = $this->container->get('hbpf.custom_node.wisepops-created-email-mapper');

        $response = Json::decode(
            $connector->process((new ProcessDto())->setData($this->getRequest('WisepopsCreatedEmailItem.json')))
                ->getData(),
            TRUE
        );

        $this->assertEquals([
            CleverFieldsEnum::EMAIL      => 'sfg@sfd.cfg',
            CleverFieldsEnum::REACTIVATE => TRUE,
            CleverFieldsEnum::SEND_OPTIN => FALSE,
        ], $response);
    }

}