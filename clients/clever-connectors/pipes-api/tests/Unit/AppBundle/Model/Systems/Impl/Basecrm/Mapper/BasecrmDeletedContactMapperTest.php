<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Basecrm\Mapper;

use CleverConnectors\AppBundle\Enum\CleverFieldsEnum;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Nette\Utils\Json;
use Tests\ConnectorTestCaseAbstract;

/**
 * Class BasecrmDeletedContactMapperTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Basecrm\Mapper
 */
final class BasecrmDeletedContactMapperTest extends ConnectorTestCaseAbstract
{

    /**
     *
     */
    public function testMapper(): void
    {
        $node = $this->container->get('hbpf.custom_node.basecrm-deleted-contact-mapper');

        $response = Json::decode($node->process(
            (new ProcessDto())->setData(
                $this->getRequest('contactItemDeleted.json')
            ))->getData(), TRUE
        );

        $expt = [
            CleverFieldsEnum::EMAIL      => '',
            CleverFieldsEnum::FOREIGN_ID => '187643117',
            CleverFieldsEnum::REACTIVATE => FALSE,
            'send_optin'                 => FALSE,
        ];

        self::assertEquals($expt, $response);
    }

}