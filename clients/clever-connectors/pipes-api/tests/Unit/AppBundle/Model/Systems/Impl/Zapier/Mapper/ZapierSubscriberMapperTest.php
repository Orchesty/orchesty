<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: michal.bartl
 * Date: 10/31/17
 * Time: 1:51 PM
 */

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Zapier\Mapper;

use CleverConnectors\AppBundle\Enum\CleverFieldsEnum;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Nette\Utils\Json;
use Tests\ConnectorTestCaseAbstract;

/**
 * Class ZapierSubscriberMapperTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Zapier\Mapper
 */
class ZapierSubscriberMapperTest extends ConnectorTestCaseAbstract
{

    /**
     *
     */
    public function testProcess(): void
    {
        $connector = $this->container->get('hbpf.custom_node.zapier-subscriber-mapper');

        $response = Json::decode($connector->process(
            (new ProcessDto())->setData(
                $this->getRequest('ZapierWebhookResponse.json')
            ))->getData(), TRUE
        );

        $this->assertEquals([
            CleverFieldsEnum::EMAIL      => 'karel@barel.com',
            CleverFieldsEnum::FIRST_NAME => 'Karel',
            CleverFieldsEnum::LAST_NAME  => 'Barel',
            CleverFieldsEnum::FOREIGN_ID => '6',
            CleverFieldsEnum::REACTIVATE => TRUE,
            CleverFieldsEnum::SEND_OPTIN => FALSE,
        ], $response);
    }

}