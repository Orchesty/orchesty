<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Zoho\Mapper;

use CleverConnectors\AppBundle\Enum\CleverFieldsEnum;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Utils\PipesHeaders;
use Nette\Utils\Json;
use Nette\Utils\Strings;
use Tests\ConnectorTestCaseAbstract;

/**
 * Class ZohoUpdatedContactMapperTest
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
        $connector = $this->ownContainer->get('hbpf.custom_node.zoho-updated-contact-mapper');
        $data      = Json::decode(Strings::replace(
            $this->getRequest('singleContact.json'),
            '#2017-10-18 09:49:57#',
            '2017-10-18 09:49:56',
            1), TRUE
        );

        $response = Json::decode($connector->process($this->prepareConnectorProcessDto([
            'auth_token' => '05361930f1c8c009d9a1e30e07b23126',
            'list'       => 'ffdfe93e-7a4e-0629-2a1a-27aee18a840a',
        ], $data, [], TRUE))->getData(), TRUE);

        $this->assertEquals([
            CleverFieldsEnum::EMAIL      => 'User01@User01.com',
            CleverFieldsEnum::FIRST_NAME => 'User01',
            CleverFieldsEnum::LAST_NAME  => 'User01',
            CleverFieldsEnum::FOREIGN_ID => '85896000000078213',
            CleverFieldsEnum::REACTIVATE => TRUE,
            CleverFieldsEnum::SEND_OPTIN => FALSE,
        ], $response);
    }

    /**
     *
     */
    public function testProcessInvalid(): void
    {
        $connector = $this->ownContainer->get('hbpf.custom_node.zoho-updated-contact-mapper');

        $dto = $connector->process(
            (new ProcessDto())->setData(
                $this->getRequest('singleContact.json')
            )->setHeaders([])
        );

        $this->assertEquals([
            PipesHeaders::createKey(PipesHeaders::RESULT_CODE)    => 1003,
            PipesHeaders::createKey(PipesHeaders::RESULT_MESSAGE) => 'Data does not contains contact update event',
        ], $dto->getHeaders());
    }

}