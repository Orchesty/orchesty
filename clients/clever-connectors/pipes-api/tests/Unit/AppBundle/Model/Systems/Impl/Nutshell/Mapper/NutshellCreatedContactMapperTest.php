<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Nutshell\Mapper;

use CleverConnectors\AppBundle\Enum\CleverFieldsEnum;
use Hanaboso\PipesFramework\Commons\Utils\PipesHeaders;
use Nette\Utils\Json;
use Tests\ConnectorTestCaseAbstract;

/**
 * Class NutshellCreatedContactMapperTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Nutshell\Mapper
 */
final class NutshellCreatedContactMapperTest extends ConnectorTestCaseAbstract
{

    /**
     *
     */
    public function testProcess(): void
    {
        $connector = $this->container->get('hbpf.custom_node.nutshell-created-contact-mapper');

        $response = Json::decode($connector->process($this->prepareConnectorProcessDto([
            'username' => 'nutshell@mailinator.com',
            'api_key'  => '967b1f7b321e6305d18e6656a650c32420aba98d',
        ], Json::decode($this->getRequest('NutshellWebhookResponse.json'), TRUE), [], TRUE))->getData(), TRUE);

        $this->assertEquals([
            CleverFieldsEnum::EMAIL      => 'User01@User01.com',
            CleverFieldsEnum::FIRST_NAME => 'User01',
            CleverFieldsEnum::LAST_NAME  => 'User01',
            CleverFieldsEnum::FOREIGN_ID => '1',
            CleverFieldsEnum::REACTIVATE => TRUE,
            CleverFieldsEnum::SEND_OPTIN => FALSE,
        ], $response);
    }

    /**
     *
     */
    public function testProcessList(): void
    {
        $connector = $this->container->get('hbpf.custom_node.nutshell-created-contact-mapper');

        $response = Json::decode($connector->process($this->prepareConnectorProcessDto([
            'username' => 'nutshell@mailinator.com',
            'api_key'  => '967b1f7b321e6305d18e6656a650c32420aba98d',
            'list'     => '4b04d334-3db9-b290-d0aa-099642329856',
        ], Json::decode($this->getRequest('NutshellWebhookResponse.json'), TRUE), [], TRUE))->getData(), TRUE);

        $this->assertEquals([
            CleverFieldsEnum::EMAIL      => 'User01@User01.com',
            CleverFieldsEnum::FIRST_NAME => 'User01',
            CleverFieldsEnum::LAST_NAME  => 'User01',
            CleverFieldsEnum::FOREIGN_ID => '1',
            CleverFieldsEnum::REACTIVATE => TRUE,
            CleverFieldsEnum::SEND_OPTIN => FALSE,
            CleverFieldsEnum::LISTS      => ['4b04d334-3db9-b290-d0aa-099642329856'],
        ], $response);
    }

    /**
     *
     */
    public function testProcessInvalid(): void
    {
        $connector = $this->container->get('hbpf.custom_node.nutshell-created-contact-mapper');
        $data      = Json::decode(str_replace(
            '"create"',
            '"update"',
            $this->getRequest('NutshellWebhookResponse.json')),
            TRUE
        );

        $dto = $connector->process($this->prepareConnectorProcessDto([
            'username' => 'nutshell@mailinator.com',
            'api_key'  => '967b1f7b321e6305d18e6656a650c32420aba98d',
        ], $data, [], TRUE));

        $headers = $dto->getHeaders();
        unset($headers['pf-guid'], $headers['pf-token'], $headers['pf-system-key']);

        $this->assertEquals([
            PipesHeaders::createKey(PipesHeaders::RESULT_CODE)    => 1003,
            PipesHeaders::createKey(PipesHeaders::RESULT_MESSAGE) => 'Data does not contains contact create event',
        ], $headers);

    }

}