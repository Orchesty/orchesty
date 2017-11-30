<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Zoho\Mapper;

use CleverConnectors\AppBundle\Enum\CleverFieldsEnum;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Tests\ConnectorTestCaseAbstract;

/**
 * Class ZohoDeletedContactMapperTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Zoho\Mapper
 */
final class ZohoDeletedContactMapperTest extends ConnectorTestCaseAbstract
{

    /**
     *
     */
    public function testProcess(): void
    {
        $node = $this->container->get('hbpf.custom_node.zoho-deleted-contact-mapper');

        $response = json_decode(
            $node->process((new ProcessDto())->setData($this->getRequest('singleDeletedContactId.json')))
                ->getData(),
            TRUE
        );

        $expt = [
            CleverFieldsEnum::EMAIL      => '',
            CleverFieldsEnum::FOREIGN_ID => '85896000000078215',
            CleverFieldsEnum::REACTIVATE => FALSE,
            CleverFieldsEnum::SEND_OPTIN => FALSE,
        ];

        self::assertEquals($expt, $response);
    }

}