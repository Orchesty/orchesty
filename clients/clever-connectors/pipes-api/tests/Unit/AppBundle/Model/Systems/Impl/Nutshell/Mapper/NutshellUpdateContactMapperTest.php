<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Nutshell\Mapper;

use CleverConnectors\AppBundle\Enum\CleverFieldsEnum;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Nette\Utils\Json;
use Tests\ConnectorTestCaseAbstract;

/**
 * Class NutshellUpdateContactMapperTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Nutshell\Mapper
 */
final class NutshellUpdateContactMapperTest extends ConnectorTestCaseAbstract
{

    /**
     *
     */
    public function testProcess(): void
    {
        $connector = $this->container->get('hbpf.custom_node.nutshell-update-contact-mapper');

        $response = Json::decode($connector->process((new ProcessDto())->setData(Json::encode([
            CleverFieldsEnum::FOREIGN_ID => 1,
            'result'                     => ['rev' => 1],
        ])))->getData(), TRUE);

        $this->assertEquals([
            'jsonrpc' => '2.0',
            'id'      => 'contact',
            'method'  => 'editContact',
            'params'  => [
                'contactId' => 1,
                'rev'       => 1,
                'contact'   => [
                    'customFields' => [],
                ],
            ],
        ], $response);
    }

}