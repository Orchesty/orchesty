<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Basecrm\Mapper;

use CleverConnectors\AppBundle\Enum\CleverFieldsEnum;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Nette\Utils\Json;
use Tests\ConnectorTestCaseAbstract;

/**
 * Class BasecrmUpdateContactMapperTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Basecrm\Mapper
 */
final class BasecrmUpdateContactMapperTest extends ConnectorTestCaseAbstract
{

    /**
     *
     */
    public function testMapper(): void
    {
        $node = $this->container->get('hbpf.custom_node.basecrm-update-contact-mapper');

        $response = Json::decode($node->process(
            (new ProcessDto())->setData(
                $this->getRequest('contactItem.json')
            ))->getData(), TRUE
        );

        $expt = [
            CleverFieldsEnum::EMAIL       => 'asd@asd.com',
            CleverFieldsEnum::FIRST_NAME  => 'Base',
            CleverFieldsEnum::FOREIGN_ID  => '187596661',
            CleverFieldsEnum::REACTIVATE  => TRUE,
        ];

        self::assertEquals($expt, $response);
    }

}