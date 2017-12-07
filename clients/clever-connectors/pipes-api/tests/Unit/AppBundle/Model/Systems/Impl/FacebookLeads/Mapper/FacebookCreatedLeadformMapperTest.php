<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: michal.bartl
 * Date: 12/7/17
 * Time: 5:13 PM
 */

namespace Tests\Unit\AppBundle\Model\Systems\Impl\FacebookLeads\Mapper;

use CleverConnectors\AppBundle\Enum\CleverFieldsEnum;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Tests\ConnectorTestCaseAbstract;

/**
 * Class FacebookCreatedLeadformMapperTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\FacebookLeads\Mapper
 */
class FacebookCreatedLeadformMapperTest extends ConnectorTestCaseAbstract
{

    /**
     *
     */
    public function testMapper(): void
    {
        $mapper = $this->container->get('hbpf.custom_node.facebook-created-leadform-mapper');

        $dto1 = new ProcessDto();
        $dto1->setData($this->getRequest('LeadformCreated1.json'))->setHeaders([]);

        /** @var ProcessDto $res */
        $res1 = $mapper->process($dto1);

        self::assertEquals(json_encode([
            CleverFieldsEnum::EMAIL      => 'joe@example.com',
            CleverFieldsEnum::REACTIVATE => TRUE,
            CleverFieldsEnum::SEND_OPTIN => FALSE,
            CleverFieldsEnum::FIRST_NAME => 'Joe',
            CleverFieldsEnum::LAST_NAME  => 'Example',
            CleverFieldsEnum::FOREIGN_ID => '666',
        ]), $res1->getData());


        $dto2 = new ProcessDto();
        $dto2->setData($this->getRequest('LeadformCreated2.json'))->setHeaders([]);

        /** @var ProcessDto $res */
        $res = $mapper->process($dto2);

        self::assertEquals(json_encode([
            CleverFieldsEnum::EMAIL      => 'karel@barel.com',
            CleverFieldsEnum::REACTIVATE => TRUE,
            CleverFieldsEnum::SEND_OPTIN => FALSE,
            CleverFieldsEnum::FIRST_NAME => 'Karel',
            CleverFieldsEnum::LAST_NAME  => 'Barel',
            CleverFieldsEnum::FOREIGN_ID => '667',
        ]), $res->getData());

    }

}