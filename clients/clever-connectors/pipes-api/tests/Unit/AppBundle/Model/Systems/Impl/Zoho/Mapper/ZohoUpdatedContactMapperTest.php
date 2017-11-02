<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Zoho\Mapper;

use CleverConnectors\AppBundle\Enum\CleverFieldsEnum;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Tests\ConnectorTestCaseAbstract;

/**
 * Class ZohoUpdateContactMapperTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Zoho\Mapper
 */
final class ZohoUpdatedContactMapperTest extends ConnectorTestCaseAbstract
{

    /**
     *
     */
    public function testProcess(): void
    {
        $node = $this->container->get('hbpf.custom_node.zoho-updated-contact-mapper');

        $response = json_decode(
            $node->process((new ProcessDto())->setData($this->getRequest('singleContact.json')))
                ->getData(),
            TRUE
        );

        $expt = [
            CleverFieldsEnum::EMAIL       => 'john-buttbenton@gmail.com',
            CleverFieldsEnum::FIRST_NAME  => 'John',
            CleverFieldsEnum::LAST_NAME   => 'Butt',
            CleverFieldsEnum::FOREIGN_ID  => '85896000000078213',
            CleverFieldsEnum::REACTIVATE  => TRUE,
        ];

        self::assertEquals($expt, $response);
    }

}